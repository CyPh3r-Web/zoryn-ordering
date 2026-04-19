-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 18, 2026 at 01:24 PM
-- Server version: 8.0.31
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `zoryn`
--

-- --------------------------------------------------------

--
-- Table structure for table `cash_transactions`
--

DROP TABLE IF EXISTS `cash_transactions`;
CREATE TABLE IF NOT EXISTS `cash_transactions` (
  `cash_txn_id` int NOT NULL AUTO_INCREMENT,
  `transaction_date` date NOT NULL,
  `transaction_type` varchar(80) NOT NULL,
  `activity_type` enum('operating','investing','financing') NOT NULL,
  `account_id` int DEFAULT NULL,
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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cash_transactions`
--

INSERT INTO `cash_transactions` (`cash_txn_id`, `transaction_date`, `transaction_type`, `activity_type`, `account_id`, `amount`, `direction`, `reference_type`, `reference_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2026-04-18', 'Purchase Order', 'operating', NULL, '2000.00', 'outflow', 'purchase_order', 1, 'Ingredient Purchase · PO-20260418-0001 · Unknown Supplier', '2026-04-18 12:52:51', '2026-04-18 12:52:51'),
(2, '2026-04-18', 'Purchase Order', 'operating', NULL, '2000.00', 'outflow', 'purchase_order', 2, 'Ingredient Purchase · PO-20260418-0002 · Fresh Farms', '2026-04-18 12:55:56', '2026-04-18 12:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `created_at`) VALUES
(1, 'Proteins', '2026-04-14 13:16:18'),
(2, 'Vegetables', '2026-04-14 13:16:18'),
(3, 'Fruits', '2026-04-14 13:16:18'),
(4, 'Dairy Products', '2026-04-14 13:16:18'),
(5, 'Grains and Cereals', '2026-04-14 13:16:18'),
(6, 'Legumes and Nuts', '2026-04-14 13:16:18'),
(7, 'Herbs and Spices', '2026-04-14 13:16:18'),
(8, 'Condiments and Sauces', '2026-04-14 13:16:18'),
(9, 'Sweeteners and Baking Ingredients', '2026-04-14 13:16:18'),
(10, 'Oils and Fats', '2026-04-14 13:16:18'),
(11, 'Beverages', '2026-04-14 13:16:18'),
(12, 'Frozen and Processed Items', '2026-04-14 13:16:18'),
(13, 'Packaging Materials', '2026-04-14 13:16:18');

-- --------------------------------------------------------

--
-- Table structure for table `equity_transactions`
--

DROP TABLE IF EXISTS `equity_transactions`;
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

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE IF NOT EXISTS `expenses` (
  `expense_id` int NOT NULL AUTO_INCREMENT,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `account_id` int DEFAULT NULL,
  `vendor_name` varchar(120) DEFAULT NULL,
  `status` enum('draft','posted','void') NOT NULL DEFAULT 'posted',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`expense_id`),
  KEY `idx_expenses_date` (`expense_date`),
  KEY `idx_expenses_category` (`category`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `expense_date`, `category`, `description`, `amount`, `payment_method`, `account_id`, `vendor_name`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2026-04-18', 'Ingredient Purchase', 'Ingredient Purchase · PO-20260418-0001 · Unknown Supplier', '2000.00', 'cash', NULL, 'Unknown Supplier', 'posted', 4, '2026-04-18 12:52:51', '2026-04-18 12:52:51'),
(2, '2026-04-18', 'Ingredient Purchase', 'Ingredient Purchase · PO-20260418-0002 · Fresh Farms', '2000.00', 'cash', NULL, 'Fresh Farms', 'posted', 4, '2026-04-18 12:55:56', '2026-04-18 12:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `finance_accounts`
--

DROP TABLE IF EXISTS `finance_accounts`;
CREATE TABLE IF NOT EXISTS `finance_accounts` (
  `account_id` int NOT NULL AUTO_INCREMENT,
  `account_code` varchar(30) NOT NULL,
  `account_name` varchar(120) NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense') NOT NULL,
  `account_subtype` varchar(80) DEFAULT NULL,
  `is_cash_account` tinyint(1) NOT NULL DEFAULT '0',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `uniq_finance_account_code` (`account_code`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `finance_accounts`
--

INSERT INTO `finance_accounts` (`account_id`, `account_code`, `account_name`, `account_type`, `account_subtype`, `is_cash_account`, `is_system`, `status`, `created_at`, `updated_at`) VALUES
(1, '1000', 'Cash on Hand', 'asset', 'Cash', 1, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(2, '1100', 'Accounts Receivable', 'asset', 'Accounts Receivable', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(3, '1200', 'Inventory Asset', 'asset', 'Inventory', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(4, '1300', 'Equipment', 'asset', 'Equipment', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(5, '2000', 'Accounts Payable', 'liability', 'Accounts Payable', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(6, '2100', 'Loans Payable', 'liability', 'Loans', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(7, '2200', 'Other Obligations', 'liability', 'Other Obligations', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(8, '3000', 'Owner Capital', 'equity', 'Owner Capital', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(9, '3100', 'Retained Earnings', 'equity', 'Retained Earnings', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(10, '4000', 'Sales Revenue', 'revenue', 'Sales', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(11, '5000', 'Cost of Goods Sold', 'expense', 'COGS', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50'),
(12, '6100', 'Operating Expenses', 'expense', 'Operating Expenses', 0, 1, 'active', '2026-04-16 14:21:50', '2026-04-16 14:21:50');

-- --------------------------------------------------------

--
-- Table structure for table `finance_journal_entries`
--

DROP TABLE IF EXISTS `finance_journal_entries`;
CREATE TABLE IF NOT EXISTS `finance_journal_entries` (
  `entry_id` int NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `memo` text,
  `status` enum('draft','posted','void') NOT NULL DEFAULT 'posted',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`),
  KEY `idx_finance_entry_date` (`entry_date`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `finance_journal_entries`
--

INSERT INTO `finance_journal_entries` (`entry_id`, `entry_date`, `reference_type`, `reference_id`, `memo`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2026-04-16', 'Purchase', NULL, 'Test Journal Entry', 'posted', 4, '2026-04-16 15:23:09', '2026-04-16 15:23:09');

-- --------------------------------------------------------

--
-- Table structure for table `finance_journal_lines`
--

DROP TABLE IF EXISTS `finance_journal_lines`;
CREATE TABLE IF NOT EXISTS `finance_journal_lines` (
  `line_id` int NOT NULL AUTO_INCREMENT,
  `entry_id` int NOT NULL,
  `account_id` int NOT NULL,
  `debit_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `credit_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`line_id`),
  KEY `idx_finance_line_entry` (`entry_id`),
  KEY `idx_finance_line_account` (`account_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `finance_journal_lines`
--

INSERT INTO `finance_journal_lines` (`line_id`, `entry_id`, `account_id`, `debit_amount`, `credit_amount`, `description`) VALUES
(1, 1, 1, '100.00', '0.00', 'test'),
(2, 1, 6, '0.00', '100.00', 'test');

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

DROP TABLE IF EXISTS `ingredients`;
CREATE TABLE IF NOT EXISTS `ingredients` (
  `ingredient_id` int NOT NULL AUTO_INCREMENT,
  `ingredient_name` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `stock` decimal(10,2) NOT NULL,
  `reorder_level` decimal(10,2) NOT NULL DEFAULT '0.00',
  `default_unit_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ingredient_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`ingredient_id`, `ingredient_name`, `image_path`, `category_id`, `stock`, `reorder_level`, `default_unit_cost`, `unit`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Cheese', 'assets/images/ingredients/69de6010a040a.jpg', 4, '29.36', '0.00', '200.00', 'kg', 'active', '2026-04-14 15:41:04', '2026-04-18 12:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_cost_history`
--

DROP TABLE IF EXISTS `ingredient_cost_history`;
CREATE TABLE IF NOT EXISTS `ingredient_cost_history` (
  `cost_id` int NOT NULL AUTO_INCREMENT,
  `ingredient_id` int NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cost_id`),
  KEY `idx_ingredient_cost_history_ingredient` (`ingredient_id`),
  KEY `idx_ingredient_cost_history_effective_date` (`effective_date`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ingredient_cost_history`
--

INSERT INTO `ingredient_cost_history` (`cost_id`, `ingredient_id`, `unit_cost`, `effective_date`, `source_type`, `source_id`, `created_at`) VALUES
(1, 1, '200.00', '2026-04-18', 'purchase_order', 1, '2026-04-18 12:52:51'),
(2, 1, '200.00', '2026-04-18', 'purchase_order', 2, '2026-04-18 12:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_suppliers`
--

DROP TABLE IF EXISTS `ingredient_suppliers`;
CREATE TABLE IF NOT EXISTS `ingredient_suppliers` (
  `ingredient_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `last_cost` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ingredient_id`,`supplier_id`),
  KEY `idx_ingredient_suppliers_supplier` (`supplier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

DROP TABLE IF EXISTS `inventory_movements`;
CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `movement_id` int NOT NULL AUTO_INCREMENT,
  `ingredient_id` int NOT NULL,
  `movement_type` enum('stock_in','stock_out','purchase','usage','sale','waste','return_in','adjustment_add','adjustment_less') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `notes` text,
  `movement_date` date NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`movement_id`),
  KEY `idx_inventory_movements_ingredient` (`ingredient_id`),
  KEY `idx_inventory_movements_date` (`movement_date`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`movement_id`, `ingredient_id`, `movement_type`, `quantity`, `unit_cost`, `reference_type`, `reference_id`, `notes`, `movement_date`, `created_by`, `created_at`) VALUES
(1, 1, 'sale', '0.10', '0.00', 'order', 1, 'Sale deduction for order #1', '2026-04-18', NULL, '2026-04-18 12:10:37'),
(2, 1, 'sale', '0.01', '0.00', 'order', 2, 'Sale deduction for order #2', '2026-04-18', NULL, '2026-04-18 12:25:10'),
(3, 1, 'sale', '0.10', '0.00', 'order', 2, 'Sale deduction for order #2', '2026-04-18', NULL, '2026-04-18 12:25:10'),
(4, 1, 'purchase', '10.00', '200.00', 'purchase_order', 1, 'Stock-in from PO-20260418-0001', '0000-00-00', 4, '2026-04-18 12:52:51'),
(5, 1, 'purchase', '10.00', '200.00', 'purchase_order', 2, 'Stock-in from PO-20260418-0002', '0000-00-00', 4, '2026-04-18 12:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_id` int NOT NULL,
  `message` text NOT NULL,
  `is_completed` tinyint(1) DEFAULT '0',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`)
) ENGINE=MyISAM AUTO_INCREMENT=130 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `order_id`, `message`, `is_completed`, `is_read`, `created_at`) VALUES
(3, 3, 2, 'Your order #2 is now being prepared', 0, 0, '2025-05-01 02:17:50'),
(2, 2, 1, 'Your order #1 has been completed', 1, 1, '2025-04-28 10:23:48'),
(4, 3, 2, 'Your order #2 has been completed', 1, 0, '2025-05-01 02:18:01'),
(17, 16, 9, 'Hi kendi, your order of Dark Choco-ey (Total: ₱49.00) is now being prepared', 0, 0, '2025-05-13 15:05:44'),
(74, 15, 22, 'Cash payment of ₱49.00 for your order has been received and verified.', 0, 0, '2025-05-21 00:40:11'),
(20, 16, 9, 'Hi kendi, your order of Dark Choco-ey (Total: ₱49.00) has been completed', 1, 0, '2025-05-13 15:17:10'),
(71, 15, 22, 'Hi cashier anne, your order of Matcha Milky (Total: ₱49.00) has been placed successfully. Please proceed to the counter for payment.', 0, 0, '2025-05-21 00:39:58'),
(72, 15, 22, 'Hi cashier anne, your Matcha Milky (₱49.00) is now being prepared. ', 0, 0, '2025-05-21 00:40:05'),
(87, 15, 13, 'Hi Jh, your Spanish Latte (₱196.00) has been completed. ', 0, 0, '2025-05-21 00:50:22'),
(88, 15, 14, 'Hi jj reddick, your Spanish Latte and Hot Black Gold Series (₱98.00) has been completed. ', 0, 0, '2025-05-21 00:50:23'),
(89, 15, 15, 'Hi cashier anne, your Matcha Milky (₱49.00) has been completed. ', 0, 0, '2025-05-21 00:50:25'),
(94, 15, 22, 'Hi cashier anne, your Matcha Milky (₱49.00) has been completed. ', 0, 0, '2025-05-21 00:51:46'),
(96, 15, 16, 'Hi cashier anne, your Ube Milky (₱49.00) has been completed. ', 0, 0, '2025-05-21 00:51:50'),
(97, 15, 12, 'Hi cashier anne, your Java Chip (₱49.00) has been completed. ', 0, 0, '2025-05-21 00:51:52'),
(103, 12, 28, 'Hi cypher, your order of Matcha Milky (Total: ₱49.00) has been placed successfully. Please proceed to the counter for payment.', 0, 0, '2025-07-27 23:39:26'),
(104, 12, 29, 'Hi cypher, your order of Ube Milky, Strawberry Milky, Matcha Milky, Iced Pure Black, Hot Black Gold Series (Total: ₱245.00) has been placed successfully. Please wait for payment verification.', 0, 0, '2025-07-28 01:03:57'),
(108, 12, 29, 'Hi cypher, your Ube Milky, Strawberry Milky, Matcha Milky, Iced Pure Black and Hot Black Gold Series (₱245.00) is now being prepared. ', 0, 0, '2025-11-13 07:42:44'),
(109, 12, 28, 'Hi cypher, your Matcha Milky (₱49.00) is now being prepared. ', 0, 0, '2025-11-13 07:42:47'),
(110, 12, 29, 'Hi cypher, your Ube Milky, Strawberry Milky, Matcha Milky, Iced Pure Black and Hot Black Gold Series (₱245.00) has been completed. ', 0, 0, '2025-11-13 07:42:49'),
(111, 12, 28, 'Hi cypher, your Matcha Milky (₱49.00) has been completed. ', 0, 0, '2025-11-13 07:42:53'),
(123, 3, 2, 'Hi Guest, your order of Beef ala King, Baked Spaghetti (Total: ₱398.00) has been placed successfully. Please proceed to the counter for payment.', 0, 0, '2026-04-18 12:25:10'),
(124, 3, 2, 'Hi Guest, your Beef ala King and Baked Spaghetti (₱398.00) is now being prepared. ', 0, 0, '2026-04-18 12:31:10'),
(125, 3, 2, 'Cash payment of ₱398.00 for your order has been received and verified.', 0, 0, '2026-04-18 12:34:29'),
(129, 3, 2, 'Hi Guest, your Beef ala King and Baked Spaghetti (₱398.00) has been completed. ', 0, 0, '2026-04-18 12:34:51');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) DEFAULT NULL,
  `order_type` enum('walk-in','account-order','dine-in','take-out') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'walk-in',
  `order_status` enum('pending','preparing','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `proof_of_payment` varchar(255) DEFAULT NULL,
  `payment_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `feedback_comment` text,
  `feedback_ratings` json DEFAULT NULL,
  `feedback_date` datetime DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `table_number` varchar(20) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_name`, `order_type`, `order_status`, `total_amount`, `payment_type`, `proof_of_payment`, `payment_status`, `created_at`, `updated_at`, `feedback_comment`, `feedback_ratings`, `feedback_date`, `user_id`, `table_number`, `subtotal`, `tax_amount`) VALUES
(1, 'Jav', 'dine-in', 'completed', '199.00', 'cash', NULL, 'verified', '2026-04-18 12:10:37', '2026-04-18 12:34:49', NULL, NULL, NULL, 1, 'T-16', '177.68', '21.32'),
(2, 'Guest', 'dine-in', 'completed', '398.00', 'cash', NULL, 'verified', '2026-04-18 12:25:09', '2026-04-18 12:34:51', NULL, NULL, NULL, 3, 'T-17', '355.36', '42.64');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, '199.00'),
(2, 2, 3, 1, '199.00'),
(3, 2, 1, 1, '199.00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `category_id` int DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '12.00',
  `description` text,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `category_id`, `price`, `tax_rate`, `description`, `image_path`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Baked Spaghetti', 1, '199.00', '12.00', 'Baked for perfection', 'assets/zoryn/products/baked-meals.jpg', 'active', '2026-04-14 15:48:54', '2026-04-18 13:13:11'),
(2, 'Baked Macaroni', 1, '199.00', '12.00', 'Baked to perfection', 'assets/zoryn/products/baked-meals.jpg', 'active', '2026-04-16 13:31:28', '2026-04-18 13:13:11'),
(3, 'Beef ala King', 1, '199.00', '12.00', '', 'assets/zoryn/products/baked-meals.jpg', 'active', '2026-04-16 13:35:27', '2026-04-18 13:13:11'),
(4, 'Lasagna Supreme', 1, '249.00', '12.00', 'Demo: layered pasta bake', 'assets/zoryn/products/baked-meals.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(5, 'Chicken Casserole', 1, '229.00', '12.00', 'Demo: oven-baked casserole', 'assets/zoryn/products/baked-meals.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(6, 'Lumpiang Shanghai Platter', 2, '189.00', '12.00', 'Demo: sharing platter', 'assets/zoryn/products/barkada-meryenda.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(7, 'Nachos Overload', 2, '199.00', '12.00', 'Demo: loaded nachos', 'assets/zoryn/products/barkada-meryenda.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(8, 'Fries Bucket', 2, '149.00', '12.00', 'Demo: crispy fries', 'assets/zoryn/products/barkada-meryenda.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(9, 'Chicken Wings Sampler', 2, '219.00', '12.00', 'Demo: mixed wings', 'assets/zoryn/products/barkada-meryenda.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(10, 'Onion Rings Tower', 2, '169.00', '12.00', 'Demo: stacked rings', 'assets/zoryn/products/barkada-meryenda.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(11, 'Chef Kare-Kare', 3, '299.00', '12.00', 'Demo: house specialty', 'assets/zoryn/products/best-choice.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(12, 'Grilled Salmon Plate', 3, '349.00', '12.00', 'Demo: seafood pick', 'assets/zoryn/products/best-choice.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(13, 'Signature Beef Steak', 3, '329.00', '12.00', 'Demo: premium cut', 'assets/zoryn/products/best-choice.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(14, 'BBQ Ribs Full Rack', 3, '399.00', '12.00', 'Demo: slow-cooked ribs', 'assets/zoryn/products/best-choice.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(15, 'Gourmet Burger', 3, '259.00', '12.00', 'Demo: stacked burger', 'assets/zoryn/products/best-choice.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(16, 'Chicken Adobo Rice', 4, '189.00', '12.00', 'Demo: classic adobo', 'assets/zoryn/products/chicken & beef.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(17, 'Beef Caldereta', 4, '229.00', '12.00', 'Demo: tomato stew', 'assets/zoryn/products/chicken & beef.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(18, 'Chicken Inasal', 4, '199.00', '12.00', 'Demo: grilled chicken', 'assets/zoryn/products/chicken & beef.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(19, 'Beef Steak Tagalog', 4, '249.00', '12.00', 'Demo: pan steak', 'assets/zoryn/products/chicken & beef.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(20, 'Crispy Chicken Cutlet', 4, '209.00', '12.00', 'Demo: breaded cutlet', 'assets/zoryn/products/chicken & beef.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(21, 'Leche Flan', 5, '89.00', '12.00', 'Demo: custard', 'assets/zoryn/products/dessert.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(22, 'Buko Pandan', 5, '99.00', '12.00', 'Demo: chilled dessert', 'assets/zoryn/products/dessert.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(23, 'Mango Graham', 5, '109.00', '12.00', 'Demo: layered cup', 'assets/zoryn/products/dessert.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(24, 'Brownie Sundae', 5, '129.00', '12.00', 'Demo: ice cream brownie', 'assets/zoryn/products/dessert.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(25, 'Halo-Halo Junior', 5, '119.00', '12.00', 'Demo: small halo-halo', 'assets/zoryn/products/dessert.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(26, 'Iced Tea Pitcher', 6, '149.00', '12.00', 'Demo: house blend', 'assets/zoryn/products/drinks.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(27, 'Fresh Orange Juice', 6, '99.00', '12.00', 'Demo: chilled juice', 'assets/zoryn/products/drinks.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(28, 'House Lemonade', 6, '89.00', '12.00', 'Demo: citrus cooler', 'assets/zoryn/products/drinks.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(29, 'Cucumber Cooler', 6, '89.00', '12.00', 'Demo: refresh drink', 'assets/zoryn/products/drinks.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(30, 'Soda Float', 6, '99.00', '12.00', 'Demo: ice cream float', 'assets/zoryn/products/drinks.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(31, 'Family Fiesta Set A', 7, '1299.00', '12.00', 'Demo: bundle for 4-5', 'assets/zoryn/products/family-set.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(32, 'Family Fiesta Set B', 7, '1399.00', '12.00', 'Demo: bundle variant', 'assets/zoryn/products/family-set.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(33, 'Weekend Group Bundle', 7, '1199.00', '12.00', 'Demo: weekend promo', 'assets/zoryn/products/family-set.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(34, 'Celebration Pack', 7, '1499.00', '12.00', 'Demo: party set', 'assets/zoryn/products/family-set.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(35, 'Group Feast Tray', 7, '1599.00', '12.00', 'Demo: large tray', 'assets/zoryn/products/family-set.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(36, 'Classic Halo-Halo', 8, '129.00', '12.00', 'Demo: shaved ice mix', 'assets/zoryn/products/halo-halo.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(37, 'Ube Halo-Halo', 8, '139.00', '12.00', 'Demo: ube special', 'assets/zoryn/products/halo-halo.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(38, 'Mais Con Yelo', 8, '119.00', '12.00', 'Demo: corn dessert', 'assets/zoryn/products/halo-halo.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(39, 'Halo-Halo Special', 8, '149.00', '12.00', 'Demo: loaded toppings', 'assets/zoryn/products/halo-halo.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(40, 'Avocado Halo-Halo', 8, '139.00', '12.00', 'Demo: avocado twist', 'assets/zoryn/products/halo-halo.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(41, 'Iced Latte', 9, '129.00', '12.00', 'Demo: espresso + milk', 'assets/zoryn/products/iced-coffee.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(42, 'Iced Americano', 9, '109.00', '12.00', 'Demo: black iced', 'assets/zoryn/products/iced-coffee.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(43, 'Iced Mocha', 9, '139.00', '12.00', 'Demo: chocolate coffee', 'assets/zoryn/products/iced-coffee.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(44, 'Caramel Iced Coffee', 9, '139.00', '12.00', 'Demo: caramel syrup', 'assets/zoryn/products/iced-coffee.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(45, 'Vanilla Iced Coffee', 9, '139.00', '12.00', 'Demo: vanilla syrup', 'assets/zoryn/products/iced-coffee.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(46, 'Seafood Platter', 10, '599.00', '12.00', 'Demo: mixed seafood', 'assets/zoryn/products/platter.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(47, 'Mixed Grill Platter', 10, '549.00', '12.00', 'Demo: meats combo', 'assets/zoryn/products/platter.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(48, 'Appetizer Platter', 10, '399.00', '12.00', 'Demo: starters mix', 'assets/zoryn/products/platter.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(49, 'Vegetable Platter', 10, '329.00', '12.00', 'Demo: grilled veg', 'assets/zoryn/products/platter.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(50, 'Cold Cuts Platter', 10, '449.00', '12.00', 'Demo: deli selection', 'assets/zoryn/products/platter.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(51, 'Pork Rice Platter', 11, '199.00', '12.00', 'Demo: rice + pork', 'assets/zoryn/products/rice_platter.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(52, 'Chicken Rice Platter', 11, '199.00', '12.00', 'Demo: rice + chicken', 'assets/zoryn/products/rice_platter.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(53, 'Beef Rice Platter', 11, '219.00', '12.00', 'Demo: rice + beef', 'assets/zoryn/products/rice_platter.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(54, 'Seafood Rice Platter', 11, '229.00', '12.00', 'Demo: rice + seafood', 'assets/zoryn/products/rice_platter.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(55, 'Veggie Rice Platter', 11, '179.00', '12.00', 'Demo: rice + vegetables', 'assets/zoryn/products/rice_platter.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(56, 'Caesar Salad', 12, '169.00', '12.00', 'Demo: classic caesar', 'assets/zoryn/products/salad.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(57, 'Garden Fresh Salad', 12, '149.00', '12.00', 'Demo: greens mix', 'assets/zoryn/products/salad.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(58, 'Chicken Caesar Salad', 12, '189.00', '12.00', 'Demo: with grilled chicken', 'assets/zoryn/products/salad.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(59, 'Fruit Salad Bowl', 12, '139.00', '12.00', 'Demo: seasonal fruits', 'assets/zoryn/products/salad.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(60, 'Potato Salad', 12, '129.00', '12.00', 'Demo: creamy potato', 'assets/zoryn/products/salad.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(61, 'Grilled Fish Fillet', 13, '229.00', '12.00', 'Demo: catch of the day', 'assets/zoryn/products/seafood.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(62, 'Shrimp Gambas', 13, '279.00', '12.00', 'Demo: garlic shrimp', 'assets/zoryn/products/seafood.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(63, 'Buttered Squid', 13, '249.00', '12.00', 'Demo: tender squid', 'assets/zoryn/products/seafood.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(64, 'Crispy Fish Fillet', 13, '219.00', '12.00', 'Demo: breaded fish', 'assets/zoryn/products/seafood.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(65, 'Sinigang na Hipon', 13, '269.00', '12.00', 'Demo: sour soup shrimp', 'assets/zoryn/products/seafood.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(66, 'Solo Burger Meal', 14, '189.00', '12.00', 'Demo: burger combo', 'assets/zoryn/products/solo-meals.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(67, 'Solo Rice Meal', 14, '179.00', '12.00', 'Demo: one viand rice', 'assets/zoryn/products/solo-meals.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(68, 'Solo Noodle Meal', 14, '169.00', '12.00', 'Demo: noodle bowl', 'assets/zoryn/products/solo-meals.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(69, 'Solo Snack Box', 14, '149.00', '12.00', 'Demo: light solo', 'assets/zoryn/products/solo-meals.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(70, 'Solo Pasta Meal', 14, '199.00', '12.00', 'Demo: pasta portion', 'assets/zoryn/products/solo-meals.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(71, 'Sinigang Soup', 15, '199.00', '12.00', 'Demo: sour broth', 'assets/zoryn/products/soup.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(72, 'Bulalo Soup', 15, '249.00', '12.00', 'Demo: bone marrow', 'assets/zoryn/products/soup.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(73, 'Molo Soup', 15, '169.00', '12.00', 'Demo: dumpling soup', 'assets/zoryn/products/soup.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(74, 'Tinola', 15, '189.00', '12.00', 'Demo: ginger chicken soup', 'assets/zoryn/products/soup.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11'),
(75, 'Cream of Mushroom', 15, '149.00', '12.00', 'Demo: creamy soup', 'assets/zoryn/products/soup.jpg', 'active', '2026-04-18 13:06:48', '2026-04-18 13:13:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE IF NOT EXISTS `product_categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`category_id`, `category_name`, `description`, `image_path`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Baked Meals', 'Oven-baked dishes such as lasagna, baked macaroni, and casseroles.', 'assets/zoryn/products/baked-meals.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(2, 'Barkada Meryenda', 'Snack platters ideal for sharing with friends or groups.', 'assets/zoryn/products/barkada-meryenda.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(3, 'Best Choice', 'Chef-recommended or best-selling menu items.', 'assets/zoryn/products/best-choice.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(4, 'Chicken & Beef', 'Main dishes featuring chicken and beef selections.', 'assets/zoryn/products/chicken & beef.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(5, 'Desserts', 'Sweet treats including cakes, pastries, and traditional desserts.', 'assets/zoryn/products/dessert.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(6, 'Drinks', 'Beverages such as soft drinks, juices, and specialty drinks.', 'assets/zoryn/products/drinks.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(7, 'Family Set', 'Meal bundles designed for families or large groups.', 'assets/zoryn/products/family-set.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(8, 'Halo Halo', 'Traditional Filipino shaved ice dessert with mixed ingredients.', 'assets/zoryn/products/halo-halo.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(9, 'Iced Coffee', 'Cold coffee beverages including lattes and flavored iced coffee.', 'assets/zoryn/products/iced-coffee.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(10, 'Platter', 'Large servings of assorted dishes suitable for sharing.', 'assets/zoryn/products/platter.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(11, 'Rice Platter', 'Rice-based meals served with various viands.', 'assets/zoryn/products/rice_platter.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(12, 'Salad', 'Fresh vegetable or fruit-based salad dishes.', 'assets/zoryn/products/salad.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(13, 'Seafood', 'Dishes made from fish, shrimp, squid, and other seafood.', 'assets/zoryn/products/seafood.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(14, 'Solo Meals', 'Individual meal portions perfect for single diners.', 'assets/zoryn/products/solo-meals.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36'),
(15, 'Soup', 'Hot and comforting soup-based dishes.', 'assets/zoryn/products/soup.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 14:11:36');

-- --------------------------------------------------------

--
-- Table structure for table `product_feedback`
--

DROP TABLE IF EXISTS `product_feedback`;
CREATE TABLE IF NOT EXISTS `product_feedback` (
  `feedback_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `rating` int DEFAULT NULL,
  PRIMARY KEY (`feedback_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_ingredients`
--

DROP TABLE IF EXISTS `product_ingredients`;
CREATE TABLE IF NOT EXISTS `product_ingredients` (
  `product_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  PRIMARY KEY (`product_id`,`ingredient_id`),
  KEY `ingredient_id` (`ingredient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_ingredients`
--

INSERT INTO `product_ingredients` (`product_id`, `ingredient_id`, `quantity`, `unit`) VALUES
(1, 1, '100.00', 'g'),
(2, 1, '10.00', 'g'),
(3, 1, '10.00', 'g'),
(4, 1, '10.00', 'g'),
(5, 1, '10.00', 'g'),
(6, 1, '10.00', 'g'),
(7, 1, '10.00', 'g'),
(8, 1, '10.00', 'g'),
(9, 1, '10.00', 'g'),
(10, 1, '10.00', 'g'),
(11, 1, '10.00', 'g'),
(12, 1, '10.00', 'g'),
(13, 1, '10.00', 'g'),
(14, 1, '10.00', 'g'),
(15, 1, '10.00', 'g'),
(16, 1, '10.00', 'g'),
(17, 1, '10.00', 'g'),
(18, 1, '10.00', 'g'),
(19, 1, '10.00', 'g'),
(20, 1, '10.00', 'g'),
(21, 1, '10.00', 'g'),
(22, 1, '10.00', 'g'),
(23, 1, '10.00', 'g'),
(24, 1, '10.00', 'g'),
(25, 1, '10.00', 'g'),
(26, 1, '10.00', 'g'),
(27, 1, '10.00', 'g'),
(28, 1, '10.00', 'g'),
(29, 1, '10.00', 'g'),
(30, 1, '10.00', 'g'),
(31, 1, '10.00', 'g'),
(32, 1, '10.00', 'g'),
(33, 1, '10.00', 'g'),
(34, 1, '10.00', 'g'),
(35, 1, '10.00', 'g'),
(36, 1, '10.00', 'g'),
(37, 1, '10.00', 'g'),
(38, 1, '10.00', 'g'),
(39, 1, '10.00', 'g'),
(40, 1, '10.00', 'g'),
(41, 1, '10.00', 'g'),
(42, 1, '10.00', 'g'),
(43, 1, '10.00', 'g'),
(44, 1, '10.00', 'g'),
(45, 1, '10.00', 'g'),
(46, 1, '10.00', 'g'),
(47, 1, '10.00', 'g'),
(48, 1, '10.00', 'g'),
(49, 1, '10.00', 'g'),
(50, 1, '10.00', 'g'),
(51, 1, '10.00', 'g'),
(52, 1, '10.00', 'g'),
(53, 1, '10.00', 'g'),
(54, 1, '10.00', 'g'),
(55, 1, '10.00', 'g'),
(56, 1, '10.00', 'g'),
(57, 1, '10.00', 'g'),
(58, 1, '10.00', 'g'),
(59, 1, '10.00', 'g'),
(60, 1, '10.00', 'g'),
(61, 1, '10.00', 'g'),
(62, 1, '10.00', 'g'),
(63, 1, '10.00', 'g'),
(64, 1, '10.00', 'g'),
(65, 1, '10.00', 'g'),
(66, 1, '10.00', 'g'),
(67, 1, '10.00', 'g'),
(68, 1, '10.00', 'g'),
(69, 1, '10.00', 'g'),
(70, 1, '10.00', 'g'),
(71, 1, '10.00', 'g'),
(72, 1, '10.00', 'g'),
(73, 1, '10.00', 'g'),
(74, 1, '10.00', 'g'),
(75, 1, '10.00', 'g');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `po_id` int NOT NULL AUTO_INCREMENT,
  `po_number` varchar(30) NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `po_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','received','cancelled') NOT NULL DEFAULT 'received',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`po_id`),
  UNIQUE KEY `uniq_po_number` (`po_number`),
  KEY `idx_po_supplier` (`supplier_id`),
  KEY `idx_po_date` (`po_date`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`po_id`, `po_number`, `supplier_id`, `po_date`, `total_amount`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PO-20260418-0001', NULL, '2026-04-18', '2000.00', 'cancelled', '', 4, '2026-04-18 12:52:51', '2026-04-18 12:55:41'),
(2, 'PO-20260418-0002', 1, '2026-04-18', '2000.00', 'received', '', 4, '2026-04-18 12:55:56', '2026-04-18 12:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `po_item_id` int NOT NULL AUTO_INCREMENT,
  `po_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`po_item_id`),
  KEY `idx_po_items_po` (`po_id`),
  KEY `idx_po_items_ingredient` (`ingredient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`po_item_id`, `po_id`, `ingredient_id`, `quantity`, `unit`, `unit_cost`, `subtotal`) VALUES
(1, 1, 1, '10.00', 'kg', '200.00', '2000.00'),
(2, 2, 1, '10.00', 'kg', '200.00', '2000.00');

-- --------------------------------------------------------

--
-- Table structure for table `session_orders`
--

DROP TABLE IF EXISTS `session_orders`;
CREATE TABLE IF NOT EXISTS `session_orders` (
  `session_id` varchar(100) NOT NULL,
  `order_data` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `session_orders`
--

INSERT INTO `session_orders` (`session_id`, `order_data`, `created_at`, `updated_at`) VALUES
('77kfv7iun22bmbu5pjep5t326r', '{\"items\":[{\"product_id\":\"1\",\"product_name\":\"Baked Spaghetti\",\"price\":\"198.93\",\"image_path\":\"assets\\/images\\/products\\/69de61e62e1db.jpg\",\"quantity\":1}]}', '2026-04-14 15:51:05', '2026-04-14 15:51:05'),
('cfojs0geepnv44d3gg59e9tq40', '{\"items\":[{\"product_id\":\"1\",\"product_name\":\"Baked Spaghetti\",\"price\":\"198.93\",\"image_path\":\"assets\\/images\\/products\\/69de61e62e1db.jpg\",\"quantity\":1}]}', '2026-04-15 03:23:59', '2026-04-15 03:23:59'),
('48sflf72f1766858p646io80ic', '{\"items\":[{\"product_id\":\"2\",\"product_name\":\"Baked Macaroni\",\"price\":\"199.00\",\"tax_rate\":\"12.00\",\"image_path\":\"..\\/assets\\/images\\/products\\/69e0e4b05af40.jpg\",\"quantity\":1}]}', '2026-04-18 12:25:36', '2026-04-18 12:25:36');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `supplier_id` int NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(120) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Fresh Farms', '', '', '', '', 'active', '2026-04-18 12:55:32', '2026-04-18 12:55:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `profile_picture` varchar(255) DEFAULT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `account_status` enum('active','locked','suspended','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_attempts` int DEFAULT '0',
  `last_2fa_sent` datetime DEFAULT NULL,
  `twofa_code` varchar(6) DEFAULT NULL,
  `twofa_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `role`, `created_at`, `updated_at`, `profile_picture`, `verification_code`, `verification_expires`, `account_status`, `two_factor_enabled`, `two_factor_secret`, `two_factor_attempts`, `last_2fa_sent`, `twofa_code`, `twofa_expires`) VALUES
(1, 'zoryn', '$2y$10$xqoQQ.PpYwIMYUKl/sXQLuCF/ZLuYI2nUeTWrGgxRBHn3y8p3xVwy', 'zoryn cashier', 'zoryn-cashier@gmail.com', 'cashier', '2025-04-19 13:37:09', '2026-04-18 12:44:15', '69de4d7c3e4ef_zoryn_logo.jpg', NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL),
(12, 'cypher', '$2y$10$TienqtFHWNeLuhOCVYH66OCN6LlhYhro2IxkdfKvjNMViTz0xpp9q', 'cypher web', 'cyph3rcoding@gmail.com', 'admin', '2025-04-30 07:24:04', '2025-07-28 01:02:57', '68175fb68d8f6_Itachi uchiha anime icon !.jpg', '6186', '2025-04-30 15:54:04', 'active', 1, NULL, 0, '2025-07-28 09:02:38', NULL, NULL),
(3, 'Waiter', '$2y$10$TienqtFHWNeLuhOCVYH66OCN6LlhYhro2IxkdfKvjNMViTz0xpp9q', 'zoryn waiter', 'zoryn-waiter@gmail.com', 'waiter', '2025-04-24 20:03:20', '2026-04-18 12:24:43', NULL, NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL),
(4, 'admin', '$2y$10$9lJqX5R/J0WayGSvSMHIaOeJ2x/s.EndlXq2e99Wq4ywDAEBOH32a', 'admin', 'admin@gmail.com', 'admin', '2025-04-24 20:05:26', '2025-04-30 13:48:49', NULL, NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL),
(15, 'cashier anne', '$2y$10$p/UIQMOMPuat.BUcKAF4ae5oXVUlHsBQ88Dc2W4YHvq0sWj8QIzRq', 'anne hathaway', 'anne@gmail.com', 'cashier', '2025-05-01 03:39:34', '2025-05-13 13:49:02', '6812ed477abf9_312063087_644479490382883_5575475379441192405_n.jpg', NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
