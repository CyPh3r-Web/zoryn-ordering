-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 14, 2026 at 01:34 PM
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(111, 12, 28, 'Hi cypher, your Matcha Milky (₱49.00) has been completed. ', 0, 0, '2025-11-13 07:42:53');

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
  `payment_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `proof_of_payment` varchar(255) DEFAULT NULL,
  `payment_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `feedback_comment` text,
  `feedback_ratings` json DEFAULT NULL,
  `feedback_date` datetime DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_name`, `order_type`, `order_status`, `total_amount`, `payment_type`, `proof_of_payment`, `payment_status`, `created_at`, `updated_at`, `feedback_comment`, `feedback_ratings`, `feedback_date`, `user_id`) VALUES
(1, 'cypher', 'account-order', 'completed', '343.00', '', NULL, 'verified', '2025-04-28 02:20:50', '2025-05-19 15:50:15', 'Masarap naman sya', NULL, '2025-04-28 18:24:43', 2),
(2, 'kenkenji', 'account-order', 'completed', '196.00', '', NULL, 'verified', '2025-04-30 18:17:42', '2025-05-21 00:55:25', 'Masarap', NULL, '2025-05-01 10:18:19', 3),
(3, 'Johnny', 'account-order', 'completed', '245.00', '', NULL, 'verified', '2025-04-30 18:21:52', '2025-05-21 00:55:12', 'mapait ang matcha at masyadong matamis', NULL, '2025-05-01 10:22:50', 1),
(4, 'Johnny', 'account-order', 'completed', '147.00', '', NULL, 'verified', '2025-04-30 18:44:47', '2025-05-21 00:55:12', 'namit gid', NULL, '2025-05-01 10:45:49', 1),
(5, 'Garlic', 'walk-in', 'completed', '49.00', '', NULL, 'verified', '2025-04-30 19:44:34', '2025-05-19 15:48:09', 'Manamit gid', NULL, '2025-05-01 11:45:07', 15),
(6, 'Mr. Gavino Ang', 'walk-in', 'completed', '245.00', '', NULL, 'verified', '2025-04-30 19:53:23', '2025-05-19 15:48:10', 'Nice service! will order again', NULL, '2025-05-01 11:56:04', 15),
(7, 'Johnny', 'account-order', 'completed', '49.00', '', NULL, 'verified', '2025-04-30 19:57:14', '2025-05-21 00:55:12', 'Okay lang', NULL, '2025-05-01 12:01:16', 1),
(8, 'Johnny', 'account-order', 'completed', '49.00', '', NULL, 'verified', '2025-04-30 20:01:48', '2025-05-21 00:55:12', 'pait masyado', NULL, '2025-05-01 12:04:11', 1),
(9, 'kendi', 'account-order', 'completed', '49.00', '', NULL, 'verified', '2025-05-04 06:42:55', '2025-05-19 15:48:13', NULL, NULL, NULL, 16),
(11, 'Johnny', 'account-order', 'completed', '441.00', '', NULL, 'verified', '2025-05-19 14:24:22', '2025-11-13 07:43:56', 'kalain inyo matcha\n', NULL, '2025-11-13 15:43:56', 1),
(12, 'cashier anne', 'walk-in', 'completed', '49.00', 'cash', NULL, 'verified', '2025-05-19 15:31:50', '2025-05-21 00:51:52', NULL, NULL, NULL, 15),
(14, 'jj reddick', 'walk-in', 'completed', '98.00', 'online', 'uploads/payment_proofs/payment_14_1747670767_product-qr.png', 'verified', '2025-05-19 15:55:32', '2025-05-21 00:50:23', NULL, NULL, NULL, 15),
(13, 'Jh', 'walk-in', 'completed', '196.00', 'cash', NULL, 'verified', '2025-05-19 15:52:51', '2025-05-21 00:50:22', NULL, NULL, NULL, 15),
(15, 'cashier anne', 'walk-in', 'completed', '49.00', 'cash', NULL, 'verified', '2025-05-19 15:57:45', '2025-05-21 00:50:25', NULL, NULL, NULL, 15),
(16, 'cashier anne', 'walk-in', 'completed', '49.00', 'cash', NULL, 'verified', '2025-05-19 16:08:12', '2025-05-21 00:51:50', NULL, NULL, NULL, 15),
(17, 'angel', 'walk-in', 'completed', '147.00', 'cash', NULL, 'verified', '2025-05-19 16:13:17', '2025-05-19 16:15:15', '', NULL, '2025-05-20 00:15:15', 15),
(18, 'Johnny', 'account-order', 'completed', '294.00', 'online', 'uploads/payment_proofs/payment_18_1747672081_c11b5071e000445781ddb08546ff3a8c.jpg', 'verified', '2025-05-19 16:27:20', '2025-05-21 00:55:12', NULL, NULL, NULL, 1),
(19, 'Johnny', 'account-order', 'preparing', '147.00', 'cash', NULL, 'verified', '2025-05-21 00:33:57', '2025-05-21 00:55:12', NULL, NULL, NULL, 1),
(29, 'cypher', 'account-order', 'completed', '245.00', 'online', 'uploads/payment_proofs/payment_6886cc7d21c77.jpg', 'pending', '2025-07-28 01:03:57', '2025-11-13 07:42:49', NULL, NULL, NULL, 12),
(20, 'Johnny', 'account-order', 'completed', '147.00', 'online', 'uploads/payment_proofs/payment_682d2004d5950.jpg', 'verified', '2025-05-21 00:36:20', '2025-05-21 00:55:12', '', NULL, '2025-05-21 08:37:48', 1),
(21, 'Johnny', 'account-order', 'completed', '49.00', 'online', 'uploads/payment_proofs/payment_682d207965170.png', 'verified', '2025-05-21 00:38:17', '2025-05-21 00:55:12', NULL, NULL, NULL, 1),
(22, 'cashier anne', 'walk-in', 'completed', '49.00', 'cash', NULL, 'verified', '2025-05-21 00:39:58', '2025-05-21 00:51:46', NULL, NULL, NULL, 15),
(23, 'Johnny', 'account-order', 'completed', '98.00', 'cash', NULL, 'verified', '2025-05-21 00:42:18', '2025-05-21 00:55:12', NULL, NULL, NULL, 1),
(24, 'Johnny', 'account-order', 'completed', '98.00', 'cash', NULL, 'verified', '2025-05-21 00:45:06', '2025-11-13 07:44:20', 'namit inyo kape\n', NULL, '2025-11-13 15:44:20', 1),
(25, 'Johnny', 'account-order', 'completed', '49.00', 'cash', NULL, 'verified', '2025-05-21 00:46:35', '2025-05-21 00:55:12', NULL, NULL, NULL, 1),
(26, 'Johnny', 'account-order', 'completed', '147.00', 'cash', NULL, 'verified', '2025-05-21 00:47:49', '2025-05-21 00:55:12', NULL, NULL, NULL, 1),
(27, 'Johnny', 'account-order', 'preparing', '49.00', 'cash', NULL, 'verified', '2025-05-21 00:52:08', '2025-05-21 00:55:12', NULL, NULL, NULL, 1),
(28, 'cypher', 'account-order', 'completed', '49.00', 'cash', NULL, 'unpaid', '2025-07-27 23:39:26', '2025-11-13 07:42:53', NULL, NULL, NULL, 12),
(30, 'javidec', 'account-order', 'completed', '196.00', 'online', 'uploads/payment_proofs/payment_691589b343515.jpg', 'verified', '2025-11-13 07:33:07', '2025-11-13 07:43:03', NULL, NULL, NULL, 1);

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
) ENGINE=MyISAM AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(22, 10, 8, 1, '49.00'),
(23, 11, 6, 1, '49.00'),
(24, 11, 1, 2, '49.00'),
(25, 11, 3, 1, '49.00'),
(26, 11, 4, 1, '49.00'),
(27, 11, 5, 1, '49.00'),
(28, 11, 6, 1, '49.00'),
(29, 11, 10, 1, '49.00'),
(30, 11, 11, 1, '49.00'),
(31, 11, 12, 1, '49.00'),
(32, 12, 1, 1, '49.00'),
(33, 13, 11, 4, '49.00'),
(34, 14, 11, 1, '49.00'),
(35, 14, 12, 1, '49.00'),
(36, 15, 4, 1, '49.00'),
(37, 16, 5, 1, '49.00'),
(38, 17, 4, 1, '49.00'),
(39, 17, 5, 1, '49.00'),
(40, 17, 6, 1, '49.00'),
(41, 18, 10, 1, '49.00'),
(42, 18, 11, 1, '49.00'),
(43, 18, 9, 1, '49.00'),
(44, 18, 3, 1, '49.00'),
(45, 18, 4, 2, '49.00'),
(46, 19, 1, 1, '49.00'),
(47, 19, 3, 1, '49.00'),
(48, 19, 5, 1, '49.00'),
(49, 20, 7, 1, '49.00'),
(50, 20, 11, 2, '49.00'),
(51, 21, 12, 1, '49.00'),
(52, 22, 4, 1, '49.00'),
(53, 23, 6, 1, '49.00'),
(54, 23, 9, 1, '49.00'),
(55, 24, 3, 1, '49.00'),
(56, 24, 8, 1, '49.00'),
(57, 25, 5, 1, '49.00'),
(58, 26, 10, 3, '49.00'),
(59, 27, 10, 1, '49.00'),
(60, 28, 4, 1, '49.00'),
(61, 29, 5, 1, '49.00'),
(62, 29, 6, 1, '49.00'),
(63, 29, 4, 1, '49.00'),
(64, 29, 10, 1, '49.00'),
(65, 29, 12, 1, '49.00'),
(66, 30, 4, 1, '49.00'),
(67, 30, 5, 1, '49.00'),
(68, 30, 6, 1, '49.00'),
(69, 30, 9, 1, '49.00');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(1, 'Baked Meals', 'Oven-baked dishes such as lasagna, baked macaroni, and casseroles.', 'assets/zoryn/products/baked-meals.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(2, 'Barkada Meryenda', 'Snack platters ideal for sharing with friends or groups.', 'assets/zoryn/products/barkada-meryenda.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(3, 'Best Choice', 'Chef-recommended or best-selling menu items.', 'assets/zoryn/products/best-choice.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(4, 'Chicken & Beef', 'Main dishes featuring chicken and beef selections.', 'assets/zoryn/products/chicken & beef.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(5, 'Desserts', 'Sweet treats including cakes, pastries, and traditional desserts.', 'assets/zoryn/products/dessert.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(6, 'Drinks', 'Beverages such as soft drinks, juices, and specialty drinks.', 'assets/zoryn/products/drinks.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(7, 'Family Set', 'Meal bundles designed for families or large groups.', 'assets/zoryn/products/family-set.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(8, 'Halo Halo', 'Traditional Filipino shaved ice dessert with mixed ingredients.', 'assets/zoryn/products/halo-halo.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(9, 'Iced Coffee', 'Cold coffee beverages including lattes and flavored iced coffee.', 'assets/zoryn/products/iced-coffee.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(10, 'Platter', 'Large servings of assorted dishes suitable for sharing.', 'assets/zoryn/products/platter.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(11, 'Rice Platter', 'Rice-based meals served with various viands.', 'assets/zoryn/products/rice_platter.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(12, 'Salad', 'Fresh vegetable or fruit-based salad dishes.', 'assets/zoryn/products/salad.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(13, 'Seafood', 'Dishes made from fish, shrimp, squid, and other seafood.', 'assets/zoryn/products/seafood.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(14, 'Solo Meals', 'Individual meal portions perfect for single diners.', 'assets/zoryn/products/solo-meals.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27'),
(15, 'Soup', 'Hot and comforting soup-based dishes.', 'assets/zoryn/products/soup.jpg', 'active', '2026-04-14 13:31:27', '2026-04-14 13:31:27');

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
('8mh5qu0pgfb2jnbb75g425jtdd', '{\"items\":[{\"product_id\":\"5\",\"product_name\":\"Ube Milky\",\"price\":\"49.00\",\"image_path\":\"assets\\/images\\/products\\/680b8cf846534.png\",\"quantity\":3}]}', '2025-04-26 04:06:44', '2025-04-26 04:33:12'),
('1apevds9l75rres6pjbsqr12td', '{\"items\":[{\"product_id\":\"5\",\"product_name\":\"Ube Milky\",\"price\":\"49.00\",\"image_path\":\"assets\\/images\\/products\\/680b8cf846534.png\",\"quantity\":1},{\"product_id\":\"2\",\"product_name\":\"Coffee Latte\",\"price\":\"49.00\",\"image_path\":\"assets\\/images\\/products\\/680b8c6eaf332.png\",\"quantity\":1},{\"product_id\":\"9\",\"product_name\":\"Caramel Macchiato\",\"price\":\"49.00\",\"image_path\":\"assets\\/images\\/products\\/680b8d9c75bab.png\",\"quantity\":1}]}', '2026-04-13 08:25:44', '2026-04-13 08:25:44');

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
(1, 'javidec', '$2y$10$p/UIQMOMPuat.BUcKAF4ae5oXVUlHsBQ88Dc2W4YHvq0sWj8QIzRq', 'javidec', 'kupikuys@gmail.com', 'user', '2025-04-19 13:37:09', '2025-05-04 23:36:32', '6812f640d1dfd_476494835_1148181520012675_4414561697490981302_n.jpg', NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL),
(12, 'cypher', '$2y$10$TienqtFHWNeLuhOCVYH66OCN6LlhYhro2IxkdfKvjNMViTz0xpp9q', 'cypher web', 'cyph3rcoding@gmail.com', 'admin', '2025-04-30 07:24:04', '2025-07-28 01:02:57', '68175fb68d8f6_Itachi uchiha anime icon !.jpg', '6186', '2025-04-30 15:54:04', 'active', 1, NULL, 0, '2025-07-28 09:02:38', NULL, NULL),
(3, 'kenken', '$2y$10$TienqtFHWNeLuhOCVYH66OCN6LlhYhro2IxkdfKvjNMViTz0xpp9q', 'ken ', 'kupikuysss@gmail.com', 'user', '2025-04-24 20:03:20', '2025-05-04 14:08:36', NULL, NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL),
(4, 'admin', '$2y$10$9lJqX5R/J0WayGSvSMHIaOeJ2x/s.EndlXq2e99Wq4ywDAEBOH32a', 'admin', 'admin@gmail.com', 'admin', '2025-04-24 20:05:26', '2025-04-30 13:48:49', NULL, NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL),
(15, 'cashier anne', '$2y$10$p/UIQMOMPuat.BUcKAF4ae5oXVUlHsBQ88Dc2W4YHvq0sWj8QIzRq', 'anne hathaway', 'anne@gmail.com', 'cashier', '2025-05-01 03:39:34', '2025-05-13 13:49:02', '6812ed477abf9_312063087_644479490382883_5575475379441192405_n.jpg', NULL, NULL, 'active', 0, NULL, 0, NULL, NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
