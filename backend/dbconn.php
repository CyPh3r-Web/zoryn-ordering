    <?php
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "zoryn";

    // Create connection
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]);
        exit;
    }

    // Set charset to utf8
    $conn->set_charset("utf8");

    // Philippines local time — avoids UTC wall-clock in DB vs browser (e.g. 12:51 shown vs 8:51 PM actual)
    date_default_timezone_set('Asia/Manila');
    @$conn->query("SET time_zone = '+08:00'");

    if (!function_exists('zoryn_datetime_to_iso8601')) {
        /**
         * MySQL datetime string (session TZ) → ISO-8601 for JSON / JavaScript Date.
         */
        function zoryn_datetime_to_iso8601(?string $mysqlDatetime): string {
            if ($mysqlDatetime === null || $mysqlDatetime === '') {
                return '';
            }
            try {
                $dt = new DateTimeImmutable(trim($mysqlDatetime));
                return $dt->format(DateTimeInterface::ATOM);
            } catch (Throwable $e) {
                return (string) $mysqlDatetime;
            }
        }
    }

    /* ------------------------------------------------------------------
    * Auto-migrations (idempotent, MySQL 5.7 / 8.x compatible).
    * Runs on every request; each helper checks information_schema first.
    * ------------------------------------------------------------------ */

    /** Add a column only when it is missing (portable replacement for
     *  MariaDB's `ADD COLUMN IF NOT EXISTS`). */
    function zoryn_add_column_if_missing(mysqli $conn, string $table, string $column, string $definition): void {
        $tableEsc  = $conn->real_escape_string($table);
        $columnEsc = $conn->real_escape_string($column);
        $sql = "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = '{$tableEsc}'
                AND COLUMN_NAME  = '{$columnEsc}'";
        $res = @$conn->query($sql);
        if (!$res) return;
        $row = $res->fetch_assoc();
        $res->free();
        if ((int)($row['c'] ?? 0) > 0) return;
        @$conn->query("ALTER TABLE `{$tableEsc}` ADD COLUMN `{$columnEsc}` {$definition}");
    }

    /** Run a statement, swallow errors so a single failure does not break the request. */
    function zoryn_silent_query(mysqli $conn, string $sql): void {
        try { @$conn->query($sql); } catch (Throwable $e) {
            error_log("Zoryn migration skipped: " . $e->getMessage());
        }
    }

    // Pre-existing column (kept for backwards compatibility)
    zoryn_add_column_if_missing($conn, 'orders', 'payment_status',
        "ENUM('unpaid','pending','verified') DEFAULT 'unpaid'");

    // New columns for dine-in / take-out + VAT breakdown
    zoryn_add_column_if_missing($conn, 'orders', 'table_number', "VARCHAR(20) DEFAULT NULL");
    zoryn_add_column_if_missing($conn, 'orders', 'subtotal',     "DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    zoryn_add_column_if_missing($conn, 'orders', 'tax_amount',   "DECIMAL(10,2) NOT NULL DEFAULT 0.00");

    // Widen order_type enum. MODIFY is idempotent.
    zoryn_silent_query($conn,
        "ALTER TABLE `orders`
            MODIFY COLUMN `order_type`
            ENUM('walk-in','account-order','dine-in','take-out')
            CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci
            DEFAULT 'walk-in'");

    // Purchase Order tables (CREATE TABLE IF NOT EXISTS is standard SQL — safe).
    zoryn_silent_query($conn, "CREATE TABLE IF NOT EXISTS `purchase_orders` (
        `po_id`        INT NOT NULL AUTO_INCREMENT,
        `po_number`    VARCHAR(30) NOT NULL,
        `supplier_id`  INT DEFAULT NULL,
        `po_date`      DATE NOT NULL,
        `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `status`       ENUM('draft','received','cancelled') NOT NULL DEFAULT 'received',
        `notes`        TEXT,
        `created_by`   INT DEFAULT NULL,
        `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`po_id`),
        UNIQUE KEY `uniq_po_number` (`po_number`),
        KEY `idx_po_supplier` (`supplier_id`),
        KEY `idx_po_date` (`po_date`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");

    zoryn_silent_query($conn, "CREATE TABLE IF NOT EXISTS `purchase_order_items` (
        `po_item_id`    INT NOT NULL AUTO_INCREMENT,
        `po_id`         INT NOT NULL,
        `ingredient_id` INT NOT NULL,
        `quantity`      DECIMAL(10,2) NOT NULL,
        `unit`          VARCHAR(20) NOT NULL,
        `unit_cost`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `subtotal`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (`po_item_id`),
        KEY `idx_po_items_po` (`po_id`),
        KEY `idx_po_items_ingredient` (`ingredient_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");

    // Cashier shift scheduling tables
    zoryn_silent_query($conn, "CREATE TABLE IF NOT EXISTS `cashier_shifts` (
        `shift_id` INT NOT NULL AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `shift_date` DATE NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `status` ENUM('scheduled','closed') NOT NULL DEFAULT 'scheduled',
        `notes` VARCHAR(255) DEFAULT NULL,
        `created_by` INT DEFAULT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`shift_id`),
        UNIQUE KEY `uniq_cashier_shift` (`user_id`, `shift_date`),
        KEY `idx_cashier_shifts_date` (`shift_date`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");

    zoryn_silent_query($conn, "CREATE TABLE IF NOT EXISTS `cashier_shift_cash_counts` (
        `cash_count_id` INT NOT NULL AUTO_INCREMENT,
        `shift_id` INT NOT NULL,
        `count_1000` INT NOT NULL DEFAULT 0,
        `count_500` INT NOT NULL DEFAULT 0,
        `count_100` INT NOT NULL DEFAULT 0,
        `count_50` INT NOT NULL DEFAULT 0,
        `count_20` INT NOT NULL DEFAULT 0,
        `total_cash` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `recorded_by` INT NOT NULL,
        `recorded_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`cash_count_id`),
        UNIQUE KEY `uniq_shift_cash_count` (`shift_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");

    // Rename legacy "user" role to "waiter" (non-destructive, idempotent)
    zoryn_silent_query($conn, "UPDATE users SET role = 'waiter' WHERE role = 'user'");
    ?>
