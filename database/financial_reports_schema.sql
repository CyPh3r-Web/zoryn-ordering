-- ================================================================
-- Zoryn Financial Reports Schema
-- Compatible with MySQL 8.0.31
-- Run this ONCE against the `zoryn` database in phpMyAdmin
-- ================================================================

-- Step 1: Add cost columns to ingredients
-- Run these ONE AT A TIME if you get "Duplicate column" errors
ALTER TABLE `ingredients`
    ADD COLUMN `reorder_level` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `stock`,
    ADD COLUMN `default_unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `reorder_level`;

-- Step 2: Expenses table for operating costs (rent, utilities, payroll, etc.)
CREATE TABLE IF NOT EXISTS `expenses` (
    `expense_id` int NOT NULL AUTO_INCREMENT,
    `expense_date` date NOT NULL,
    `category` varchar(100) NOT NULL,
    `description` text,
    `amount` decimal(12,2) NOT NULL,
    `payment_method` varchar(50) DEFAULT NULL,
    `vendor_name` varchar(120) DEFAULT NULL,
    `status` enum('draft','posted','void') NOT NULL DEFAULT 'posted',
    `created_by` int DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`expense_id`),
    KEY `idx_expenses_date` (`expense_date`),
    KEY `idx_expenses_category` (`category`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Step 3: Inventory movement log
CREATE TABLE IF NOT EXISTS `inventory_movements` (
    `movement_id` int NOT NULL AUTO_INCREMENT,
    `ingredient_id` int NOT NULL,
    `movement_type` enum('stock_in','stock_out','purchase','usage','sale','waste','return_in','adjustment_add','adjustment_less') NOT NULL,
    `quantity` decimal(10,2) NOT NULL,
    `unit_cost` decimal(10,2) DEFAULT 0.00,
    `reference_type` varchar(50) DEFAULT NULL,
    `reference_id` int DEFAULT NULL,
    `notes` text,
    `movement_date` date NOT NULL,
    `created_by` int DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`movement_id`),
    KEY `idx_inventory_movements_ingredient` (`ingredient_id`),
    KEY `idx_inventory_movements_date` (`movement_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Step 4: Cash transactions for investing/financing activities
CREATE TABLE IF NOT EXISTS `cash_transactions` (
    `cash_txn_id` int NOT NULL AUTO_INCREMENT,
    `transaction_date` date NOT NULL,
    `transaction_type` varchar(80) NOT NULL,
    `activity_type` enum('operating','investing','financing') NOT NULL,
    `amount` decimal(12,2) NOT NULL,
    `direction` enum('inflow','outflow') NOT NULL,
    `reference_type` varchar(50) DEFAULT NULL,
    `reference_id` int DEFAULT NULL,
    `notes` text,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`cash_txn_id`),
    KEY `idx_cash_transactions_date` (`transaction_date`),
    KEY `idx_cash_transactions_activity` (`activity_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Step 5: Equity transactions (owner capital, withdrawals)
CREATE TABLE IF NOT EXISTS `equity_transactions` (
    `equity_txn_id` int NOT NULL AUTO_INCREMENT,
    `transaction_date` date NOT NULL,
    `equity_type` enum('capital','withdrawal','retained_earnings_adjustment') NOT NULL,
    `amount` decimal(12,2) NOT NULL,
    `notes` text,
    `reference_type` varchar(50) DEFAULT NULL,
    `reference_id` int DEFAULT NULL,
    `created_by` int DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`equity_txn_id`),
    KEY `idx_equity_transactions_date` (`transaction_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
