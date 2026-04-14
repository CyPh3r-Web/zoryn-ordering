-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 19, 2025 at 11:56 AM
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
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `created_at`) VALUES
(1, 'Coffee', '2025-04-17 12:16:15'),
(2, 'Syrup', '2025-04-17 12:16:15'),
(3, 'Powder', '2025-04-17 12:16:15'),
(4, 'Dairy', '2025-04-17 12:16:15'),
(5, 'Topping', '2025-04-17 12:16:15'),
(6, 'Other', '2025-04-17 12:16:15');

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
  `unit` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ingredient_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`ingredient_id`, `ingredient_name`, `image_path`, `category_id`, `stock`, `unit`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Java Chip Powder', 'assets/images/ingredients/6800fb9467079.png', 3, '9.99', 'kg', 'active', '2025-04-17 13:01:08', '2025-05-01 02:17:42'),
(2, 'Dark Chocolate Powder', 'assets/images/ingredients/6800fbaf551e2.png', 3, '9.96', 'kg', 'active', '2025-04-17 13:01:35', '2025-05-04 14:42:55'),
(3, 'Caramel Syrup', 'assets/images/ingredients/6800fbc1d0fef.png', 2, '9.96', 'liters', 'active', '2025-04-17 13:01:53', '2025-05-13 15:16:55'),
(4, 'Chocolate Syrup', 'assets/images/ingredients/6800fc15838a1.png', 2, '9.96', 'liters', 'active', '2025-04-17 13:03:17', '2025-05-04 14:42:55'),
(5, 'Coffee', 'assets/images/ingredients/6800fc279c149.png', 1, '9.96', 'liters', 'active', '2025-04-17 13:03:35', '2025-05-01 04:01:48'),
(6, 'Matcha Powder', 'assets/images/ingredients/6800fc3faa339.png', 3, '9.91', 'kg', 'active', '2025-04-17 13:03:59', '2025-05-13 15:16:55'),
(7, 'Strawberry Syrup', 'assets/images/ingredients/6800fc7cae560.png', 2, '9.94', 'liters', 'active', '2025-04-17 13:05:00', '2025-05-13 15:16:55'),
(8, 'Ube Syrup', 'assets/images/ingredients/6800fc9887156.png', 2, '9.94', 'liters', 'active', '2025-04-17 13:05:28', '2025-05-13 15:16:55'),
(9, 'White Chocolate', 'assets/images/ingredients/6800fcb78e304.png', 2, '9.97', 'liters', 'active', '2025-04-17 13:05:59', '2025-05-13 15:16:55'),
(10, 'White Cup', 'assets/images/ingredients/680b88879eec9.png', 6, '69.00', 'pcs', 'active', '2025-04-25 13:05:11', '2025-05-13 15:16:55'),
(11, 'Straw', 'assets/images/ingredients/680b889b4f4c0.png', 6, '69.00', 'pcs', 'active', '2025-04-25 13:05:31', '2025-05-13 15:16:55'),
(12, 'Black Cup', 'assets/images/ingredients/680b88b55a5cf.png', 6, '99.00', 'pcs', 'active', '2025-04-25 13:05:57', '2025-05-01 02:17:42');

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
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `order_id`, `message`, `is_completed`, `is_read`, `created_at`) VALUES
(3, 3, 2, 'Your order #2 is now being prepared', 0, 0, '2025-05-01 02:17:50'),
(2, 2, 1, 'Your order #1 has been completed', 1, 1, '2025-04-28 10:23:48'),
(4, 3, 2, 'Your order #2 has been completed', 1, 0, '2025-05-01 02:18:01'),
(12, 15, 6, 'Hi Mr. Gavino Ang, your order of Ube Milky, Strawberry Milky, Dark Choco-ey (Total: ₱245.00) has been completed', 1, 1, '2025-05-01 03:55:09'),
(11, 15, 6, 'Hi Mr. Gavino Ang, your order of Ube Milky, Strawberry Milky, Dark Choco-ey (Total: ₱245.00) is now being prepared', 0, 1, '2025-05-01 03:54:43'),
(16, 1, 8, 'Hi javidec, your order of Iced Pure Black (Total: ₱49.00) has been completed', 1, 1, '2025-05-01 04:02:04'),
(15, 1, 8, 'Hi javidec, your order of Iced Pure Black (Total: ₱49.00) is now being prepared', 0, 1, '2025-05-01 04:01:54'),
(17, 16, 9, 'Hi kendi, your order of Dark Choco-ey (Total: ₱49.00) is now being prepared', 0, 0, '2025-05-13 15:05:44'),
(18, 15, 10, 'Hi Jember, your order of Matcha Milky, Ube Milky, Strawberry Milky, White Choco-ey (Total: ₱196.00) is now being prepared', 0, 1, '2025-05-13 15:17:04'),
(19, 15, 10, 'Hi Jember, your order of Matcha Milky, Ube Milky, Strawberry Milky, White Choco-ey (Total: ₱196.00) has been completed', 1, 1, '2025-05-13 15:17:08'),
(20, 16, 9, 'Hi kendi, your order of Dark Choco-ey (Total: ₱49.00) has been completed', 1, 0, '2025-05-13 15:17:10');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) DEFAULT NULL,
  `order_type` enum('walk-in','account-order') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'walk-in',
  `order_status` enum('pending','preparing','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('cash','online') DEFAULT NULL,
  `payment_status` enum('unpaid','pending','verified','failed') DEFAULT 'unpaid',
  `proof_of_payment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `feedback_comment` text,
  `feedback_ratings` json DEFAULT NULL,
  `feedback_date` datetime DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_name`, `order_type`, `order_status`, `total_amount`, `payment_type`, `payment_status`, `proof_of_payment`, `created_at`, `updated_at`, `feedback_comment`, `feedback_ratings`, `feedback_date`, `user_id`) VALUES
(1, 'cypher', 'account-order', 'completed', '343.00', 'cash', 'verified', 'cash_proof.jpg', '2025-04-28 10:20:50', '2025-04-28 10:24:43', 'Masarap naman sya', NULL, '2025-04-28 18:24:43', 2),
(2, 'kenken', 'account-order', 'completed', '196.00', 'online', 'verified', 'online_proof.jpg', '2025-05-01 02:17:42', '2025-05-01 02:18:19', 'Masarap', NULL, '2025-05-01 10:18:19', 3),
(3, 'javidec', 'account-order', 'completed', '245.00', 'cash', 'verified', 'cash_proof.jpg', '2025-05-01 02:21:52', '2025-05-01 02:22:50', 'mapait ang matcha at masyadong matamis', NULL, '2025-05-01 10:22:50', 1),
(4, 'javidec', 'account-order', 'completed', '147.00', 'cash', 'verified', 'cash_proof.jpg', '2025-05-01 02:44:47', '2025-05-01 02:45:49', 'namit gid', NULL, '2025-05-01 10:45:49', 1),
(5, 'Garlic', 'walk-in', 'completed', '49.00', 'cash', 'verified', 'cash_proof.jpg', '2025-05-01 03:44:34', '2025-05-01 03:45:07', 'Manamit gid', NULL, '2025-05-01 11:45:07', 15),
(6, 'Mr. Gavino Ang', 'walk-in', 'completed', '245.00', 'cash', 'verified', 'cash_proof.jpg', '2025-05-01 03:53:23', '2025-05-01 03:56:04', 'Nice service! will order again', NULL, '2025-05-01 11:56:04', 15),
(7, 'javidec', 'account-order', 'completed', '49.00', 'cash', 'verified', 'cash_proof.jpg', '2025-05-01 03:57:14', '2025-05-01 04:01:16', 'Okay lang', NULL, '2025-05-01 12:01:16', 1),
(8, 'javidec', 'account-order', 'completed', '49.00', 'cash', 'verified', 'cash_proof.jpg', '2025-05-01 04:01:48', '2025-05-01 04:04:11', 'pait masyado', NULL, '2025-05-01 12:04:11', 1),
(9, 'kendi', 'account-order', 'completed', '49.00', 'cash', 'verified', 'cash_proof.jpg', '2025-05-04 14:42:55', '2025-05-13 15:17:10', NULL, NULL, NULL, 16),
(10, 'Jember', 'walk-in', 'completed', '196.00', 'cash', 'verified', 'cash_proof.jpg', '2025-05-13 15:16:55', '2025-05-13 15:17:35', '', NULL, '2025-05-13 23:17:35', 15);

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
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 4, 2, '49.00'),
(2, 1, 5, 2, '49.00'),
(3, 1, 6, 3, '49.00'),
(4, 2, 1, 1, '49.00'),
(5, 2, 3, 1, '49.00'),
(6, 2, 4, 1, '49.00'),
(7, 2, 12, 1, '49.00'),
(8, 3, 4, 4, '49.00'),
(9, 3, 6, 1, '49.00'),
(10, 4, 8, 2, '49.00'),
(11, 4, 10, 1, '49.00'),
(12, 5, 4, 1, '49.00'),
(13, 6, 5, 3, '49.00'),
(14, 6, 6, 1, '49.00'),
(15, 6, 7, 1, '49.00'),
(16, 7, 9, 1, '49.00'),
(17, 8, 10, 1, '49.00'),
(18, 9, 7, 1, '49.00'),
(19, 10, 4, 1, '49.00'),
(20, 10, 5, 1, '49.00'),
(21, 10, 6, 1, '49.00'),
(22, 10, 8, 1, '49.00');

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
  `description` text,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `category_id`, `price`, `description`, `image_path`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Java Chip', 4, '49.00', '', 'assets/images/products/680b8c28de290.png', 'active', '2025-04-25 13:20:40', '2025-04-25 13:20:40'),
(2, 'Coffee Latte', 2, '49.00', '', 'assets/images/products/680b8c6eaf332.png', 'active', '2025-04-25 13:21:50', '2025-04-25 13:21:50'),
(3, 'Rocky Road', 4, '49.00', '', 'assets/images/products/680b8ca7df1ba.png', 'active', '2025-04-25 13:22:47', '2025-04-25 13:22:47'),
(4, 'Matcha Milky', 3, '49.00', '', 'assets/images/products/680b8cd9d8fa3.png', 'active', '2025-04-25 13:23:37', '2025-04-25 13:23:37'),
(5, 'Ube Milky', 3, '49.00', '', 'assets/images/products/680b8cf846534.png', 'active', '2025-04-25 13:24:08', '2025-04-25 13:24:08'),
(6, 'Strawberry Milky', 3, '49.00', '', 'assets/images/products/680b8d10b9538.png', 'active', '2025-04-25 13:24:32', '2025-04-25 13:24:32'),
(7, 'Dark Choco-ey', 5, '49.00', '', 'assets/images/products/680b8d3c96064.png', 'active', '2025-04-25 13:25:16', '2025-04-25 13:25:16'),
(8, 'White Choco-ey', 5, '49.00', '', 'assets/images/products/680b8d5fa5faf.png', 'active', '2025-04-25 13:25:51', '2025-04-25 13:25:51'),
(9, 'Caramel Macchiato', 2, '49.00', '', 'assets/images/products/680b8d9c75bab.png', 'active', '2025-04-25 13:26:52', '2025-04-25 13:26:52'),
(10, 'Iced Pure Black', 2, '49.00', '', 'assets/images/products/680b8dc514aec.png', 'active', '2025-04-25 13:27:33', '2025-04-25 13:27:33'),
(11, 'Spanish Latte', 2, '49.00', '', 'assets/images/products/680b8df433f3a.png', 'active', '2025-04-25 13:28:20', '2025-04-25 13:28:20'),
(12, 'Hot Black Gold Series', 6, '49.00', '', 'assets/images/products/680b8e4f6293d.png', 'active', '2025-04-25 13:29:51', '2025-04-28 03:23:28');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE IF NOT EXISTS `product_categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`category_id`, `category_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Hot Coffee', 'Traditional hot coffee beverages', 'active', '2025-04-17 12:41:28', '2025-04-17 12:41:28'),
(2, 'Cold Coffee', 'Iced and cold coffee drinks', 'active', '2025-04-17 12:41:28', '2025-04-17 12:41:28'),
(3, 'Milky Series', 'Coffee drinks with milk variations', 'active', '2025-04-17 12:41:28', '2025-04-17 12:41:28'),
(4, 'Rookie Series', 'Beginner-friendly coffee options', 'active', '2025-04-17 12:41:28', '2025-04-17 12:41:28'),
(5, 'Choco-ey Series', 'Chocolate-based coffee drinks', 'active', '2025-04-17 12:41:28', '2025-04-17 12:41:28'),
(6, 'Gold Series', 'Premium coffee selections', 'active', '2025-04-17 12:41:28', '2025-04-17 12:41:28');

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
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_feedback`
--

INSERT INTO `product_feedback` (`feedback_id`, `order_id`, `product_id`, `rating`) VALUES
(1, 1, 4, 4),
(2, 1, 5, 5),
(3, 1, 6, 4),
(4, 2, 1, 4),
(5, 2, 3, 5),
(6, 2, 4, 5),
(7, 2, 12, 5),
(8, 3, 4, 2),
(9, 3, 6, 3),
(10, 4, 8, 5),
(11, 4, 10, 5),
(12, 5, 4, 5),
(13, 6, 5, 4),
(14, 6, 6, 4),
(15, 6, 7, 5),
(16, 7, 9, 4),
(17, 8, 10, 1),
(18, 10, 4, 5),
(19, 10, 5, 2);

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
(1, 1, '10.00', 'g'),
(1, 4, '10.00', 'ml'),
(2, 11, '1.00', 'pcs'),
(2, 5, '10.00', 'ml'),
(2, 4, '10.00', 'ml'),
(3, 2, '10.00', 'g'),
(3, 4, '10.00', 'ml'),
(4, 11, '1.00', 'pcs'),
(5, 8, '10.00', 'ml'),
(5, 11, '1.00', 'pcs'),
(6, 7, '10.00', 'ml'),
(6, 11, '1.00', 'pcs'),
(7, 2, '10.00', 'g'),
(7, 4, '10.00', 'ml'),
(8, 11, '1.00', 'pcs'),
(8, 3, '10.00', 'ml'),
(9, 11, '1.00', 'pcs'),
(9, 5, '10.00', 'ml'),
(9, 3, '10.00', 'ml'),
(10, 11, '1.00', 'pcs'),
(10, 5, '10.00', 'ml'),
(11, 11, '1.00', 'pcs'),
(11, 5, '10.00', 'ml'),
(11, 3, '10.00', 'ml'),
(12, 12, '1.00', 'pcs'),
(12, 5, '10.00', 'ml'),
(12, 2, '10.00', 'g'),
(4, 10, '1.00', 'pcs'),
(4, 6, '10.00', 'g'),
(6, 10, '1.00', 'pcs'),
(5, 10, '1.00', 'pcs'),
(8, 9, '10.00', 'ml'),
(8, 10, '1.00', 'pcs'),
(2, 10, '1.00', 'pcs'),
(9, 10, '1.00', 'pcs'),
(7, 11, '1.00', 'pcs'),
(7, 10, '1.00', 'pcs'),
(3, 11, '1.00', 'pcs'),
(3, 10, '1.00', 'pcs'),
(10, 10, '1.00', 'pcs'),
(1, 11, '1.00', 'pcs'),
(1, 10, '1.00', 'pcs'),
(11, 10, '1.00', 'pcs');

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
('8mh5qu0pgfb2jnbb75g425jtdd', '{\"items\":[{\"product_id\":\"5\",\"product_name\":\"Ube Milky\",\"price\":\"49.00\",\"image_path\":\"assets\\/images\\/products\\/680b8cf846534.png\",\"quantity\":3}]}', '2025-04-26 04:06:44', '2025-04-26 04:33:12');

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
(1, 'javidec', '$2y$10$p/UIQMOMPuat.BUcKAF4ae5oXVUlHsBQ88Dc2W4YHvq0sWj8QIzRq', 'javidec', 'zoryn@gmail.com', 'user', '2025-04-19 13:37:09', '2025-05-04 23:36:32', '6812f640d1dfd_476494835_1148181520012675_4414561697490981302_n.jpg', NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL),
(12, 'cypher', '$2y$10$TienqtFHWNeLuhOCVYH66OCN6LlhYhro2IxkdfKvjNMViTz0xpp9q', 'cypher web', 'cyph3rcoding@gmail.com', 'admin', '2025-04-30 07:24:04', '2025-05-18 01:19:15', '68175fb68d8f6_Itachi uchiha anime icon !.jpg', '6186', '2025-04-30 15:54:04', 'active', 1, NULL, 0, '2025-05-18 09:19:15', '933162', '2025-05-18 09:29:15'),
(3, 'kenken', '$2y$10$TienqtFHWNeLuhOCVYH66OCN6LlhYhro2IxkdfKvjNMViTz0xpp9q', 'ken ', 'zorynss@gmail.com', 'user', '2025-04-24 20:03:20', '2025-05-04 14:08:36', NULL, NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL),
(4, 'admin', '$2y$10$9lJqX5R/J0WayGSvSMHIaOeJ2x/s.EndlXq2e99Wq4ywDAEBOH32a', 'admin', 'admin@gmail.com', 'admin', '2025-04-24 20:05:26', '2025-04-30 13:48:49', NULL, NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL),
(15, 'cashier anne', '$2y$10$p/UIQMOMPuat.BUcKAF4ae5oXVUlHsBQ88Dc2W4YHvq0sWj8QIzRq', 'anne hathaway', 'anne@gmail.com', 'cashier', '2025-05-01 03:39:34', '2025-05-13 13:49:02', '6812ed477abf9_312063087_644479490382883_5575475379441192405_n.jpg', NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
