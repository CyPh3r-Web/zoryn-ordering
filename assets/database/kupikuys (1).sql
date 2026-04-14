-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 26, 2025 at 02:20 AM
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
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`ingredient_id`, `ingredient_name`, `image_path`, `category_id`, `stock`, `unit`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Java Chip Powder', 'assets/images/ingredients/6800fb9467079.png', 3, '9.99', 'kg', 'active', '2025-04-17 13:01:08', '2025-04-25 14:13:59'),
(2, 'Dark Chocolate Powder', 'assets/images/ingredients/6800fbaf551e2.png', 3, '9.94', 'kg', 'active', '2025-04-17 13:01:35', '2025-04-25 14:21:33'),
(3, 'Caramel Syrup', 'assets/images/ingredients/6800fbc1d0fef.png', 2, '1.00', 'liters', 'active', '2025-04-17 13:01:53', '2025-04-17 13:01:53'),
(4, 'Chocolate Syrup', 'assets/images/ingredients/6800fc15838a1.png', 2, '0.97', 'liters', 'active', '2025-04-17 13:03:17', '2025-04-25 14:13:59'),
(5, 'Coffee', 'assets/images/ingredients/6800fc279c149.png', 1, '0.94', 'liters', 'active', '2025-04-17 13:03:35', '2025-04-25 14:21:33'),
(6, 'Matcha Powder', 'assets/images/ingredients/6800fc3faa339.png', 3, '0.99', 'kg', 'active', '2025-04-17 13:03:59', '2025-04-25 14:12:38'),
(7, 'Strawberry Syrup', 'assets/images/ingredients/6800fc7cae560.png', 2, '0.98', 'liters', 'active', '2025-04-17 13:05:00', '2025-04-25 14:12:38'),
(8, 'Ube Syrup', 'assets/images/ingredients/6800fc9887156.png', 2, '0.99', 'liters', 'active', '2025-04-17 13:05:28', '2025-04-25 14:12:38'),
(9, 'White Chocolate', 'assets/images/ingredients/6800fcb78e304.png', 2, '1.00', 'liters', 'active', '2025-04-17 13:05:59', '2025-04-17 13:05:59'),
(10, 'White Cup', 'assets/images/ingredients/680b88879eec9.png', 6, '92.00', 'pcs', 'active', '2025-04-25 13:05:11', '2025-04-25 14:13:59'),
(11, 'Straw', 'assets/images/ingredients/680b889b4f4c0.png', 6, '100.00', 'pcs', 'active', '2025-04-25 13:05:31', '2025-04-25 13:05:31'),
(12, 'Black Cup', 'assets/images/ingredients/680b88b55a5cf.png', 6, '95.00', 'pcs', 'active', '2025-04-25 13:05:57', '2025-04-25 14:21:33');

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
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `order_id`, `message`, `is_completed`, `is_read`, `created_at`) VALUES
(1, 1, 1, 'Your order #1 is now being prepared', 0, 1, '2025-04-25 05:57:31'),
(2, 1, 1, 'Your order #1 has been completed', 1, 1, '2025-04-25 05:57:46'),
(3, 1, 2, 'Your order #2 is now being prepared', 0, 1, '2025-04-25 11:41:33'),
(4, 1, 2, 'Your order #2 has been completed', 1, 1, '2025-04-25 11:41:51'),
(5, 1, 3, 'Your order #3 is now being prepared', 0, 1, '2025-04-25 12:44:33'),
(6, 1, 3, 'Your order #3 has been completed', 1, 1, '2025-04-25 12:44:40'),
(7, 1, 1, 'Your order #1 is now being prepared', 0, 1, '2025-04-25 13:45:50'),
(8, 1, 1, 'Your order #1 has been completed', 1, 0, '2025-04-25 13:46:06'),
(9, 1, 3, 'Your order #3 is now being prepared', 0, 0, '2025-04-25 14:22:03'),
(10, 1, 2, 'Your order #2 is now being prepared', 0, 0, '2025-04-25 14:22:04'),
(11, 1, 1, 'Your order #1 is now being prepared', 0, 0, '2025-04-25 14:22:07');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) DEFAULT NULL,
  `order_type` enum('walk-in','account-order','') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'walk-in',
  `order_status` enum('pending','preparing','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `feedback_comment` text,
  `feedback_ratings` json DEFAULT NULL,
  `feedback_date` datetime DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_name`, `order_type`, `order_status`, `total_amount`, `created_at`, `updated_at`, `feedback_comment`, `feedback_ratings`, `feedback_date`, `user_id`) VALUES
(1, 'javidec', 'account-order', 'preparing', '147.00', '2025-04-25 14:12:38', '2025-04-25 14:22:07', NULL, NULL, NULL, 1),
(2, 'javidec', 'account-order', 'preparing', '147.00', '2025-04-25 14:13:59', '2025-04-25 14:22:04', NULL, NULL, NULL, 1),
(3, 'javidec', 'account-order', 'preparing', '245.00', '2025-04-25 14:21:33', '2025-04-25 14:22:03', NULL, NULL, NULL, 1);

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
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 4, 1, '49.00'),
(2, 1, 5, 1, '49.00'),
(3, 1, 6, 1, '49.00'),
(4, 2, 1, 1, '49.00'),
(5, 2, 2, 1, '49.00'),
(6, 2, 3, 1, '49.00'),
(7, 3, 12, 5, '49.00');

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
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(12, 'Hot Black Gold Series', 6, '49.00', '', 'assets/images/products/680b8e4f6293d.png', 'active', '2025-04-25 13:29:51', '2025-04-25 13:31:07');

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
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_feedback`
--

INSERT INTO `product_feedback` (`feedback_id`, `order_id`, `product_id`, `rating`) VALUES
(1, 2, 4, 1),
(2, 2, 5, 2),
(3, 2, 6, 3),
(4, 2, 9, 5),
(5, 3, 4, 5),
(6, 3, 7, 4),
(7, 1, 1, 4),
(8, 1, 3, 4);

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
(1, 4, '10.00', 'ml'),
(1, 1, '10.00', 'g'),
(1, 10, '1.00', 'pcs'),
(2, 4, '10.00', 'ml'),
(2, 5, '10.00', 'ml'),
(2, 10, '1.00', 'pcs'),
(3, 4, '10.00', 'ml'),
(3, 2, '10.00', 'g'),
(3, 10, '1.00', 'pcs'),
(4, 6, '10.00', 'g'),
(4, 10, '1.00', 'pcs'),
(5, 8, '10.00', 'ml'),
(5, 10, '1.00', 'pcs'),
(6, 7, '10.00', 'ml'),
(6, 10, '1.00', 'pcs'),
(7, 4, '10.00', 'ml'),
(7, 2, '10.00', 'g'),
(7, 10, '1.00', 'pcs'),
(8, 3, '10.00', 'ml'),
(8, 9, '10.00', 'ml'),
(8, 10, '1.00', 'pcs'),
(9, 3, '10.00', 'ml'),
(9, 5, '10.00', 'ml'),
(9, 10, '1.00', 'pcs'),
(10, 5, '10.00', 'ml'),
(10, 10, '1.00', 'pcs'),
(11, 3, '10.00', 'ml'),
(11, 5, '10.00', 'ml'),
(11, 10, '1.00', 'pcs'),
(12, 12, '1.00', 'pcs'),
(12, 5, '10.00', 'ml'),
(12, 2, '10.00', 'g');

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
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `role`, `created_at`, `updated_at`) VALUES
(1, 'javidec', '$2y$10$p/UIQMOMPuat.BUcKAF4ae5oXVUlHsBQ88Dc2W4YHvq0sWj8QIzRq', 'javidec monsion', 'jav@gmail.com', 'user', '2025-04-19 13:37:09', '2025-04-25 03:58:22'),
(2, 'cypher', '$2y$10$CHsirelq59O3YlBG8GkUIOwYtn8LiEvtpA8J.GeMpZdHpy0UdVvm2', 'cypher web', 'cypher@gmail.com', 'user', '2025-04-24 18:59:54', '2025-04-25 03:58:36'),
(3, 'kenken', '$2y$10$63tzUtnZxekh3xiWONyS2uiPFDg1zDK8zeaa3kxv7rEi3HVlcsnWq', 'ken urbayo', 'ken@gmail.com', 'user', '2025-04-24 20:03:20', '2025-04-24 20:03:20'),
(4, 'admin', '$2y$10$9lJqX5R/J0WayGSvSMHIaOeJ2x/s.EndlXq2e99Wq4ywDAEBOH32a', 'admin', 'admin@gmail.com', 'admin', '2025-04-24 20:05:26', '2025-04-24 20:05:26');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
