<?php
/**
 * Purchase Order manager
 * Single endpoint that handles PO CRUD + dashboard aggregates.
 * Receiving a PO automatically:
 *   1. Adds stock to ingredients (via inventory_movements type 'purchase')
 *   2. Writes an expense row (category = 'Ingredient Purchase')
 *   3. Updates default_unit_cost + ingredient_cost_history
 * This keeps recipes, inventory, and finance consistent in one transaction.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'dbconn.php';

function po_respond($payload) {
    echo json_encode($payload);
    exit;
}

function po_generate_number(mysqli $conn): string {
    $prefix = 'PO-' . date('Ymd') . '-';
    $row = $conn->query("SELECT po_number FROM purchase_orders WHERE po_number LIKE '{$prefix}%' ORDER BY po_id DESC LIMIT 1");
    $next = 1;
    if ($row && $row->num_rows) {
        $last = $row->fetch_assoc()['po_number'];
        $next = ((int) substr($last, -4)) + 1;
    }
    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function po_list(mysqli $conn): array {
    $rows = [];
    $sql = "SELECT po.po_id, po.po_number, po.po_date, po.total_amount, po.status, po.notes,
                   po.supplier_id, COALESCE(s.supplier_name, 'Unknown') AS supplier_name,
                   (SELECT COUNT(*) FROM purchase_order_items pi WHERE pi.po_id = po.po_id) AS item_count,
                   (SELECT COALESCE(SUM(pi.quantity), 0) FROM purchase_order_items pi WHERE pi.po_id = po.po_id) AS total_qty
            FROM purchase_orders po
            LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
            ORDER BY po.po_date DESC, po.po_id DESC";
    $res = $conn->query($sql);
    while ($res && $row = $res->fetch_assoc()) $rows[] = $row;
    return $rows;
}

function po_detail(mysqli $conn, int $poId): ?array {
    $stmt = $conn->prepare("SELECT po.*, COALESCE(s.supplier_name,'Unknown') AS supplier_name
                            FROM purchase_orders po
                            LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
                            WHERE po.po_id = ?");
    $stmt->bind_param('i', $poId);
    $stmt->execute();
    $po = $stmt->get_result()->fetch_assoc();
    if (!$po) return null;

    $stmt = $conn->prepare("SELECT pi.*, i.ingredient_name
                            FROM purchase_order_items pi
                            INNER JOIN ingredients i ON i.ingredient_id = pi.ingredient_id
                            WHERE pi.po_id = ?");
    $stmt->bind_param('i', $poId);
    $stmt->execute();
    $items = [];
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $items[] = $row;

    $po['items'] = $items;
    return $po;
}

function po_create(mysqli $conn, array $input): array {
    $supplierId = isset($input['supplier_id']) && $input['supplier_id'] !== '' ? (int) $input['supplier_id'] : null;
    $poDate     = isset($input['po_date']) && $input['po_date'] !== '' ? $input['po_date'] : date('Y-m-d');
    $notes      = trim((string) ($input['notes'] ?? ''));
    $items      = $input['items'] ?? [];
    if (!is_array($items) || count($items) === 0) throw new Exception('Add at least one item.');

    $createdBy = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id']
               : (isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);

    $conn->begin_transaction();
    try {
        $poNumber = po_generate_number($conn);

        $total = 0.0;
        foreach ($items as $it) {
            $qty  = (float) ($it['quantity'] ?? 0);
            $cost = (float) ($it['unit_cost'] ?? 0);
            if ($qty <= 0 || $cost < 0) throw new Exception('Invalid quantity or unit cost.');
            $total += $qty * $cost;
        }
        $total = round($total, 2);

        $stmt = $conn->prepare("INSERT INTO purchase_orders
            (po_number, supplier_id, po_date, total_amount, status, notes, created_by)
            VALUES (?, ?, ?, ?, 'received', ?, ?)");
        $stmt->bind_param('sisdsi', $poNumber, $supplierId, $poDate, $total, $notes, $createdBy);
        $stmt->execute();
        $poId = $conn->insert_id;

        $insertItem = $conn->prepare("INSERT INTO purchase_order_items
            (po_id, ingredient_id, quantity, unit, unit_cost, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)");

        $logMovement = $conn->prepare("INSERT INTO inventory_movements
            (ingredient_id, movement_type, quantity, unit_cost, reference_type, reference_id, notes, movement_date, created_by)
            VALUES (?, 'purchase', ?, ?, 'purchase_order', ?, ?, ?, ?)");

        $updateStock = $conn->prepare("UPDATE ingredients
            SET stock = stock + ?, default_unit_cost = ?, updated_at = NOW()
            WHERE ingredient_id = ?");

        $costHistory = $conn->prepare("INSERT INTO ingredient_cost_history
            (ingredient_id, unit_cost, effective_date, source_type, source_id)
            VALUES (?, ?, ?, 'purchase_order', ?)");

        foreach ($items as $it) {
            $ingredientId = (int)   $it['ingredient_id'];
            $qty          = (float) $it['quantity'];
            $cost         = (float) $it['unit_cost'];
            $unit         = trim((string) ($it['unit'] ?? ''));
            $subtotal     = round($qty * $cost, 2);

            if ($unit === '') {
                $unitRow = $conn->query("SELECT unit FROM ingredients WHERE ingredient_id = {$ingredientId}");
                $unit    = $unitRow && $unitRow->num_rows ? $unitRow->fetch_assoc()['unit'] : 'pcs';
            }

            $insertItem->bind_param('iidsdd', $poId, $ingredientId, $qty, $unit, $cost, $subtotal);
            $insertItem->execute();

            $noteTxt = "Stock-in from {$poNumber}";
            $logMovement->bind_param('iddisis', $ingredientId, $qty, $cost, $poId, $noteTxt, $poDate, $createdBy);
            $logMovement->execute();

            $updateStock->bind_param('ddi', $qty, $cost, $ingredientId);
            $updateStock->execute();

            $costHistory->bind_param('idsi', $ingredientId, $cost, $poDate, $poId);
            $costHistory->execute();
        }

        // Finance: record expense + cash outflow so reports stay in sync
        $supplierName = 'Unknown Supplier';
        if ($supplierId) {
            $s = $conn->query("SELECT supplier_name FROM suppliers WHERE supplier_id = {$supplierId}");
            if ($s && $s->num_rows) $supplierName = $s->fetch_assoc()['supplier_name'];
        }
        $description = "Ingredient Purchase · {$poNumber} · {$supplierName}";
        $stmt = $conn->prepare("INSERT INTO expenses
            (expense_date, category, description, amount, payment_method, vendor_name, status, created_by)
            VALUES (?, 'Ingredient Purchase', ?, ?, 'cash', ?, 'posted', ?)");
        $stmt->bind_param('ssdsi', $poDate, $description, $total, $supplierName, $createdBy);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO cash_transactions
            (transaction_date, transaction_type, activity_type, amount, direction, reference_type, reference_id, notes)
            VALUES (?, 'Purchase Order', 'operating', ?, 'outflow', 'purchase_order', ?, ?)");
        $stmt->bind_param('sdis', $poDate, $total, $poId, $description);
        $stmt->execute();

        $conn->commit();
        return ['success' => true, 'po_id' => $poId, 'po_number' => $poNumber, 'total' => $total];
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function po_create_supplier(mysqli $conn, array $input): array {
    if (!isset($_SESSION['admin_id'])) {
        throw new Exception('Unauthorized');
    }
    $name = trim((string) ($input['supplier_name'] ?? ''));
    if ($name === '') {
        throw new Exception('Supplier name is required.');
    }
    $contact = trim((string) ($input['contact_person'] ?? ''));
    $phone   = trim((string) ($input['phone'] ?? ''));
    $email   = trim((string) ($input['email'] ?? ''));
    $address = trim((string) ($input['address'] ?? ''));
    $status  = isset($input['status']) && $input['status'] === 'inactive' ? 'inactive' : 'active';

    $check = $conn->prepare('SELECT supplier_id FROM suppliers WHERE LOWER(supplier_name) = LOWER(?) LIMIT 1');
    $check->bind_param('s', $name);
    $check->execute();
    $dup = $check->get_result()->fetch_assoc();
    $check->close();
    if ($dup) {
        throw new Exception('A supplier with this name already exists.');
    }

    $stmt = $conn->prepare(
        'INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ssssss', $name, $contact, $phone, $email, $address, $status);
    $stmt->execute();
    $id = (int) $conn->insert_id;
    $stmt->close();

    return ['success' => true, 'supplier_id' => $id, 'supplier_name' => $name];
}

function po_cancel(mysqli $conn, int $poId): array {
    $stmt = $conn->prepare("UPDATE purchase_orders SET status='cancelled' WHERE po_id=? AND status<>'cancelled'");
    $stmt->bind_param('i', $poId);
    $stmt->execute();
    return ['success' => true, 'affected' => $stmt->affected_rows];
}

try {
    $action = $_REQUEST['action'] ?? '';
    switch ($action) {
        case 'list':
            po_respond(['success' => true, 'data' => po_list($conn)]);

        case 'detail':
            $poId = (int) ($_REQUEST['po_id'] ?? 0);
            $po   = po_detail($conn, $poId);
            po_respond($po ? ['success' => true, 'data' => $po]
                           : ['success' => false, 'error' => 'Not found']);

        case 'create':
            $raw   = $_SERVER['REQUEST_METHOD'] === 'POST' ? file_get_contents('php://input') : '';
            $input = $raw ? json_decode($raw, true) : $_POST;
            if (!is_array($input)) throw new Exception('Invalid payload');
            po_respond(po_create($conn, $input));

        case 'cancel':
            po_respond(po_cancel($conn, (int) ($_REQUEST['po_id'] ?? 0)));

        case 'create_supplier':
            $raw   = $_SERVER['REQUEST_METHOD'] === 'POST' ? file_get_contents('php://input') : '';
            $input = $raw ? json_decode($raw, true) : $_POST;
            if (!is_array($input)) {
                throw new Exception('Invalid payload');
            }
            po_respond(po_create_supplier($conn, $input));

        case 'options':
            $suppliers = [];
            $sRes = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE status='active' ORDER BY supplier_name ASC");
            while ($sRes && $row = $sRes->fetch_assoc()) $suppliers[] = $row;

            $ingredients = [];
            $iRes = $conn->query("SELECT i.ingredient_id, i.ingredient_name, i.unit,
                                         i.stock, i.default_unit_cost, COALESCE(c.category_name,'Uncategorized') AS category
                                  FROM ingredients i
                                  LEFT JOIN categories c ON c.category_id = i.category_id
                                  WHERE i.status = 'active'
                                  ORDER BY i.ingredient_name ASC");
            while ($iRes && $row = $iRes->fetch_assoc()) $ingredients[] = $row;

            po_respond(['success' => true, 'data' => [
                'suppliers'   => $suppliers,
                'ingredients' => $ingredients,
            ]]);

        default:
            po_respond(['success' => false, 'error' => 'Unknown action']);
    }
} catch (Throwable $e) {
    po_respond(['success' => false, 'error' => $e->getMessage()]);
}
