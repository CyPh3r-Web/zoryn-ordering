<?php
/**
 * FIFO stock layers per ingredient + optional fifo_group_key pooling across SKU rows.
 * Requires table ingredient_lots (see database/zoryn.sql).
 */

if (!defined('FIFO_SOURCE_OPENING')) {
    define('FIFO_SOURCE_OPENING', 'opening_balance');
}

/**
 * Whether the FIFO lots table exists (safe before migration).
 */
function fifo_lots_table_exists(mysqli $conn): bool {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $r = $conn->query("SHOW TABLES LIKE 'ingredient_lots'");
    $cache = $r && $r->num_rows > 0;
    return $cache;
}

/**
 * Active ingredient IDs that share FIFO with this recipe ingredient (same fifo_group_key), or [$id] if ungrouped.
 *
 * @return int[]
 */
function fifo_cluster_ids(mysqli $conn, int $ingredientId): array {
    $stmt = $conn->prepare(
        'SELECT fifo_group_key FROM ingredients WHERE ingredient_id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $ingredientId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $key = trim((string) ($row['fifo_group_key'] ?? ''));
    if ($key === '') {
        return [$ingredientId];
    }
    $stmt = $conn->prepare(
        "SELECT ingredient_id FROM ingredients WHERE fifo_group_key = ? AND status = 'active'"
    );
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $rs = $stmt->get_result();
    $ids = [];
    while ($r = $rs->fetch_assoc()) {
        $ids[] = (int) $r['ingredient_id'];
    }
    $stmt->close();
    sort($ids);
    return $ids ?: [$ingredientId];
}

/** @param int[] $ingredientIds */
function fifo_sum_lots_qty(mysqli $conn, array $ingredientIds): float {
    if (!$ingredientIds) {
        return 0.0;
    }
    $inList = implode(',', array_map('intval', $ingredientIds));
    $sql = "SELECT COALESCE(SUM(qty_remaining), 0) AS s FROM ingredient_lots WHERE ingredient_id IN ({$inList})";
    $r = $conn->query($sql);
    return (float) ($r->fetch_assoc()['s'] ?? 0);
}

/** @param int[] $ingredientIds */
function fifo_sum_ingredient_stocks(mysqli $conn, array $ingredientIds): float {
    if (!$ingredientIds) {
        return 0.0;
    }
    $inList = implode(',', array_map('intval', $ingredientIds));
    $r = $conn->query(
        "SELECT COALESCE(SUM(stock), 0) AS s FROM ingredients WHERE ingredient_id IN ({$inList})"
    );
    return (float) ($r->fetch_assoc()['s'] ?? 0);
}

/**
 * If there are no lot rows yet but ingredient.stock > 0, create one legacy opening lot per ingredient row.
 *
 * @param int[] $ingredientIds
 */
function fifo_backfill_legacy_opening_lots(mysqli $conn, array $ingredientIds): void {
    foreach ($ingredientIds as $iid) {
        $st = $conn->prepare(
            'SELECT i.stock, i.default_unit_cost, i.created_at, i.ingredient_name
             FROM ingredients i WHERE i.ingredient_id = ?'
        );
        $st->bind_param('i', $iid);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$row) {
            continue;
        }
        $stock = round((float) $row['stock'], 6);
        if ($stock <= 0) {
            continue;
        }
        $already = fifo_sum_lots_qty($conn, [$iid]);
        if ($already >= 0.00001) {
            continue;
        }
        $unitCost = (float) ($row['default_unit_cost'] ?? 0);
        $created = $row['created_at'];
        $recv = null;
        if ($created !== null && $created !== '') {
            $recv = substr((string) $created, 0, 10);
        }
        if (!$recv || $recv === '0000-00-00') {
            $recv = date('Y-m-d');
        }
        $ins = $conn->prepare(
            'INSERT INTO ingredient_lots
                (ingredient_id, qty_remaining, unit_cost, received_at, source_po_item_id, source_label)
             VALUES (?, ?, ?, ?, NULL, ?)'
        );
        $label = FIFO_SOURCE_OPENING;
        $ins->bind_param('iddss', $iid, $stock, $unitCost, $recv, $label);
        $ins->execute();
        $ins->close();
    }
}

/**
 * Total qty available for a recipe-linked ingredient (cluster pool), after legacy backfill.
 */
function fifo_cluster_available(mysqli $conn, int $recipeIngredientId): float {
    $ids = fifo_cluster_ids($conn, $recipeIngredientId);
    if (!fifo_lots_table_exists($conn)) {
        return fifo_sum_ingredient_stocks($conn, $ids);
    }
    fifo_backfill_legacy_opening_lots($conn, $ids);
    $sumLots = fifo_sum_lots_qty($conn, $ids);
    if ($sumLots > 0.00001) {
        return round($sumLots, 6);
    }
    return fifo_sum_ingredient_stocks($conn, $ids);
}

/**
 * Persist ingredients.stock column from SUM(lots) per id.
 *
 * @param int[] $ingredientIds
 */
function fifo_resync_stock_from_lots(mysqli $conn, array $ingredientIds): void {
    foreach ($ingredientIds as $iid) {
        $stmt = $conn->prepare(
            'UPDATE ingredients SET stock = COALESCE((
                SELECT SUM(l.qty_remaining) FROM ingredient_lots l WHERE l.ingredient_id = ?
            ), 0), updated_at = NOW() WHERE ingredient_id = ?'
        );
        $stmt->bind_param('ii', $iid, $iid);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Core FIFO deduction from an explicit set of ingredient IDs (cluster or single SKU).
 *
 * @param int[] $poolIds
 * @return float weighted cost
 * @throws Exception
 */
function fifo_deduct_from_pool(
    mysqli $conn,
    array $poolIds,
    float $qty,
    int $referenceOrderId,
    string $movementDate,
    string $notePrefix,
    string $movementType = 'sale'
): float {
    if ($qty <= 0) {
        return 0.0;
    }
    if (!fifo_lots_table_exists($conn)) {
        throw new Exception('FIFO is not initialized: ingredient_lots table missing.');
    }
    $ids = array_values(array_unique(array_map('intval', $poolIds)));
    if (!$ids) {
        throw new Exception('Invalid FIFO pool.');
    }
    fifo_backfill_legacy_opening_lots($conn, $ids);

    $available = fifo_sum_lots_qty($conn, $ids);
    if ($available + 0.00001 < $qty) {
        $names = fifo_ingredient_names($conn, $ids);
        $label = implode(' / ', $names);
        throw new Exception(
            'Not enough stock (FIFO pool) for ' . $label
            . '. Required: ' . number_format($qty, 4) . ', Available: ' . number_format($available, 4)
        );
    }

    $inList = implode(',', $ids);

    $refType = $referenceOrderId > 0 ? 'order' : 'manual_adjustment';
    if (!in_array($movementType, ['sale', 'adjustment_less'], true)) {
        $movementType = 'sale';
    }
    $logStmt = $conn->prepare(
        "INSERT INTO inventory_movements
            (ingredient_id, movement_type, quantity, unit_cost,
             reference_type, reference_id, notes, movement_date)
        VALUES (?, '{$movementType}', ?, ?, ?, ?, ?, ?)"
    );

    $weightedCost = 0.0;
    $remain = round($qty, 6);
    $noteSuffix = $referenceOrderId > 0
        ? "{$notePrefix} order #{$referenceOrderId} (FIFO)"
        : "{$notePrefix} (FIFO)";

    while ($remain > 0.000001) {
        $res = $conn->query(
            "SELECT lot_id, ingredient_id, qty_remaining, unit_cost
             FROM ingredient_lots
             WHERE ingredient_id IN ({$inList}) AND qty_remaining > 0.000001
             ORDER BY received_at ASC, lot_id ASC
             LIMIT 1"
        );
        if (!$res || !($lot = $res->fetch_assoc())) {
            throw new Exception('FIFO layers exhausted prematurely.');
        }
        $lid = (int) $lot['lot_id'];
        $ownerId = (int) $lot['ingredient_id'];
        $inLot = round((float) $lot['qty_remaining'], 6);
        $uc = round((float) $lot['unit_cost'], 4);
        $take = round(min($remain, $inLot), 6);
        $newQr = round($inLot - $take, 6);

        $up = $conn->prepare(
            'UPDATE ingredient_lots SET qty_remaining = ? WHERE lot_id = ?'
        );
        $up->bind_param('di', $newQr, $lid);
        $up->execute();
        $up->close();

        $rid = $referenceOrderId > 0 ? $referenceOrderId : 0;
        $logStmt->bind_param(
            'iddsiss',
            $ownerId,
            $take,
            $uc,
            $refType,
            $rid,
            $noteSuffix,
            $movementDate
        );
        $logStmt->execute();

        $weightedCost += $take * $uc;
        $remain = round($remain - $take, 6);
    }

    $logStmt->close();
    fifo_resync_stock_from_lots($conn, $ids);
    fifo_notify_cluster_low_stock($conn, $ids);

    return round($weightedCost, 4);
}

/**
 * Deduct qty (in ingredient stock unit) from FIFO lots spanning the ingredient cluster.
 *
 * @return float total unit cost applied (weighted; for reference)
 * @throws Exception
 */
function fifo_deduct_for_sale(mysqli $conn, int $recipeIngredientId, float $qty, int $orderId, ?string $movementDate = null): float {
    $date = ($movementDate && $movementDate !== '0000-00-00') ? $movementDate : date('Y-m-d');
    $ids = fifo_cluster_ids($conn, $recipeIngredientId);
    return fifo_deduct_from_pool($conn, $ids, $qty, $orderId, $date, 'Sale deduction for', 'sale');
}

/**
 * Return cancelled line quantity to the newest FIFO layer on the recipe ingredient only (same SKU bucket).
 *
 * @throws Exception
 */
function fifo_return_from_sale(mysqli $conn, int $recipeIngredientId, float $qty, int $orderId, ?string $movementDate = null): void {
    if ($qty <= 0) {
        return;
    }
    if (!fifo_lots_table_exists($conn)) {
        throw new Exception('FIFO table missing.');
    }
    $date = ($movementDate && $movementDate !== '0000-00-00') ? $movementDate : date('Y-m-d');
    fifo_backfill_legacy_opening_lots($conn, [$recipeIngredientId]);

    $cost = fifo_default_unit_cost($conn, $recipeIngredientId);
    $st = $conn->prepare(
        'SELECT lot_id, qty_remaining FROM ingredient_lots
         WHERE ingredient_id = ?
         ORDER BY received_at DESC, lot_id DESC LIMIT 1'
    );
    $st->bind_param('i', $recipeIngredientId);
    $st->execute();
    $top = $st->get_result()->fetch_assoc();
    $st->close();

    if ($top) {
        $lid = (int) $top['lot_id'];
        $nr = round((float) $top['qty_remaining'] + $qty, 6);
        $up = $conn->prepare('UPDATE ingredient_lots SET qty_remaining = ? WHERE lot_id = ?');
        $up->bind_param('di', $nr, $lid);
        $up->execute();
        $up->close();
    } else {
        $ins = $conn->prepare(
            'INSERT INTO ingredient_lots
                (ingredient_id, qty_remaining, unit_cost, received_at, source_po_item_id, source_label)
             VALUES (?, ?, ?, ?, NULL, ?)'
        );
        $label = 'return_in';
        $ins->bind_param('iddss', $recipeIngredientId, $qty, $cost, $date, $label);
        $ins->execute();
        $ins->close();
    }

    $logStmt = $conn->prepare(
        'INSERT INTO inventory_movements (ingredient_id, movement_type, quantity, unit_cost,
            reference_type, reference_id, notes, movement_date)
         VALUES (?, \'return_in\', ?, ?, \'order\', ?, ?, ?)'
    );
    $note = 'Line restock / cancel for order #' . $orderId;
    $logStmt->bind_param(
        'iddiss',
        $recipeIngredientId,
        $qty,
        $cost,
        $orderId,
        $note,
        $date
    );
    $logStmt->execute();
    $logStmt->close();

    fifo_resync_stock_from_lots($conn, [$recipeIngredientId]);
}

/** @param int[] $ids */
function fifo_ingredient_names(mysqli $conn, array $ids): array {
    if (!$ids) {
        return [];
    }
    $inList = implode(',', array_map('intval', $ids));
    $names = [];
    $r = $conn->query(
        "SELECT ingredient_name FROM ingredients WHERE ingredient_id IN ({$inList}) ORDER BY ingredient_id ASC"
    );
    while ($r && $row = $r->fetch_assoc()) {
        $names[] = (string) $row['ingredient_name'];
    }
    return $names;
}

function fifo_default_unit_cost(mysqli $conn, int $ingredientId): float {
    $st = $conn->prepare(
        'SELECT COALESCE(default_unit_cost, 0) AS u FROM ingredients WHERE ingredient_id = ?'
    );
    $st->bind_param('i', $ingredientId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    return round((float) ($row['u'] ?? 0), 4);
}

/**
 * Add a purchased layer and keep ingredients.stock aligned.
 */
function fifo_add_lot_from_purchase(
    mysqli $conn,
    int $ingredientId,
    float $qty,
    float $unitCost,
    string $receivedDate,
    ?int $poItemId = null
): void {
    if (!fifo_lots_table_exists($conn) || $qty <= 0) {
        return;
    }
    $label = 'purchase_order';
    if ($poItemId) {
        $ins = $conn->prepare(
            'INSERT INTO ingredient_lots (ingredient_id, qty_remaining, unit_cost, received_at, source_po_item_id, source_label)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->bind_param('iddsis', $ingredientId, $qty, $unitCost, $receivedDate, $poItemId, $label);
    } else {
        $ins = $conn->prepare(
            'INSERT INTO ingredient_lots (ingredient_id, qty_remaining, unit_cost, received_at, source_po_item_id, source_label)
             VALUES (?, ?, ?, ?, NULL, ?)'
        );
        $ins->bind_param('iddss', $ingredientId, $qty, $unitCost, $receivedDate, $label);
    }
    $ins->execute();
    $ins->close();

    fifo_resync_stock_from_lots($conn, [$ingredientId]);
}

/**
 * Manual stock adjustment: add = new FIFO layer dated today at default cost.
 */
function fifo_manual_stock_in(mysqli $conn, int $ingredientId, float $qty): void {
    if (!fifo_lots_table_exists($conn)) {
        throw new Exception('FIFO table missing.');
    }
    fifo_backfill_legacy_opening_lots($conn, [$ingredientId]);
    $cost = fifo_default_unit_cost($conn, $ingredientId);
    $today = date('Y-m-d');
    $ins = $conn->prepare(
        'INSERT INTO ingredient_lots
            (ingredient_id, qty_remaining, unit_cost, received_at, source_po_item_id, source_label)
         VALUES (?, ?, ?, ?, NULL, ?)'
    );
    $label = 'manual_adjustment_add';
    $ins->bind_param('iddss', $ingredientId, $qty, $cost, $today, $label);
    $ins->execute();
    $ins->close();
    fifo_resync_stock_from_lots($conn, [$ingredientId]);

    $noteText = 'Manual stock-in (admin)';
    $logStmt = $conn->prepare(
        "INSERT INTO inventory_movements
            (ingredient_id, movement_type, quantity, unit_cost,
             reference_type, reference_id, notes, movement_date)
         VALUES (?, 'adjustment_add', ?, 0, 'manual_adjustment', NULL, ?, CURDATE())"
    );
    $logStmt->bind_param('ids', $ingredientId, $qty, $noteText);
    $logStmt->execute();
    $logStmt->close();
}

/** Manual subtract: FIFO out from this SKU only (not cluster-wide). */
function fifo_manual_stock_out(mysqli $conn, int $ingredientId, float $qty): void {
    $date = date('Y-m-d');
    fifo_deduct_from_pool($conn, [$ingredientId], $qty, 0, $date, 'Manual stock-out', 'adjustment_less');
}

/**
 * Notify all active admins when any cluster member is at or below reorder level.
 *
 * @param int[] $ingredientIds
 */
function fifo_notify_cluster_low_stock(mysqli $conn, array $ingredientIds): void {
    foreach (array_unique(array_map('intval', $ingredientIds)) as $iid) {
        fifo_notify_low_stock_if_needed($conn, $iid);
    }
}

function fifo_notifications_has_link_columns(mysqli $conn): bool {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $r = $conn->query("SHOW COLUMNS FROM notifications LIKE 'link_url'");
    $cache = $r && $r->num_rows > 0;
    return $cache;
}

function fifo_notify_low_stock_if_needed(mysqli $conn, int $ingredientId): void {
    $st = $conn->prepare(
        'SELECT ingredient_name, stock, reorder_level FROM ingredients WHERE ingredient_id = ?'
    );
    $st->bind_param('i', $ingredientId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row) {
        return;
    }
    $stock = (float) $row['stock'];
    $reorder = (float) $row['reorder_level'];
    $name = (string) $row['ingredient_name'];

    $isLow = false;
    if ($reorder > 0 && $stock <= $reorder) {
        $isLow = true;
    } elseif ($reorder <= 0 && $stock <= 0) {
        $isLow = true;
    }
    if (!$isLow) {
        return;
    }

    $levelMsg = ($stock <= 0)
        ? 'out of stock'
        : "low stock (≤ reorder {$reorder})";
    $message = "{$name}: {$levelMsg}. Current balance: {$stock}. Open Inventory → Stock Management to replenish.";

    $admins = $conn->query(
        "SELECT user_id FROM users WHERE role = 'admin' AND (account_status = 'active' OR account_status IS NULL)"
    );
    if (!$admins) {
        return;
    }

    $link = 'inventory.php#' . (int) $ingredientId;

    $extended = fifo_notifications_has_link_columns($conn);
    $dupChkSql = $extended
        ? "SELECT COUNT(*) AS c FROM notifications
           WHERE user_id = ? AND COALESCE(notification_type,'order') = 'inventory_low'
             AND COALESCE(related_ingredient_id, 0) = ?
             AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        : "SELECT COUNT(*) AS c FROM notifications
           WHERE user_id = ? AND message = ?
           AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $dupChk = $conn->prepare($dupChkSql);

    if ($extended) {
        $ins = $conn->prepare(
            "INSERT INTO notifications
                (user_id, order_id, message, is_read, notification_type, link_url, related_ingredient_id)
             VALUES (?, 0, ?, 0, 'inventory_low', ?, ?)"
        );
    } else {
        $ins = $conn->prepare(
            "INSERT INTO notifications (user_id, order_id, message, is_read)
             VALUES (?, 0, ?, 0)"
        );
    }

    while ($a = $admins->fetch_assoc()) {
        $uid = (int) $a['user_id'];
        if ($extended) {
            $dupChk->bind_param('ii', $uid, $ingredientId);
        } else {
            $dupChk->bind_param('is', $uid, $message);
        }
        $dupChk->execute();
        $d = $dupChk->get_result()->fetch_assoc();
        if ((int) ($d['c'] ?? 0) > 0) {
            continue;
        }
        if ($extended) {
            $ins->bind_param('issi', $uid, $message, $link, $ingredientId);
        } else {
            $ins->bind_param('is', $uid, $message);
        }
        $ins->execute();
    }
    $dupChk->close();
    $ins->close();
}
