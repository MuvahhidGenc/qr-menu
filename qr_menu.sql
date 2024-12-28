-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 28 Ara 2024, 08:20:17
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `qr_menu`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'admin', '$argon2id$v=19$m=65536,t=4,p=3$dFdyYnNzd1BlaEZNWEpBMA$mHfkCjKRFwJ0OU3g7CBDAih+84cmWNV4dxzkJUf2Kwk', 'admin@example.com', '2024-12-24 06:40:16');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `status`, `sort_order`, `created_at`, `image`) VALUES
(1, 'Yemek', 1, 0, '2024-12-24 07:06:30', '676a6e1cc23fc.jpg'),
(2, 'İçeçek', 1, 0, '2024-12-24 07:43:52', '676a6e27408af.jpg'),
(3, 'Tatlı', 1, 0, '2024-12-24 07:44:07', '676a6e82563e2.jpg');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `notifications`
--

INSERT INTO `notifications` (`id`, `order_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 24, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:21:47'),
(2, 25, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:23:10'),
(3, 26, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:24:17'),
(4, 27, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:26:46'),
(5, 28, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:26:54'),
(6, 29, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:27:54'),
(7, 30, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:35:39'),
(8, 31, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:39:28'),
(9, 32, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:40:14'),
(10, 33, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2024-12-27 06:41:22'),
(11, 34, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2024-12-27 06:43:33'),
(12, 35, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:46:30'),
(13, 36, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:50:12'),
(14, 37, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:50:48'),
(15, 38, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:51:08'),
(16, 39, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:51:20'),
(17, 40, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:51:27'),
(18, 41, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:55:43'),
(19, 42, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:56:16'),
(20, 43, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:57:19'),
(21, 44, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 06:59:42'),
(22, 45, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 07:02:45'),
(23, 46, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 07:03:49'),
(24, 47, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 07:08:47'),
(25, 48, 'new_order', 'Masa Masa 4\'dan yeni sipariş geldi!', 1, '2024-12-27 07:09:27'),
(26, 49, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 07:17:17'),
(27, 50, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2024-12-27 08:22:54'),
(28, 51, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-27 11:35:25'),
(29, 52, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 0, '2024-12-27 15:58:57'),
(30, 59, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2024-12-28 07:16:58');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','preparing','ready','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_id` int(11) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `orders`
--

INSERT INTO `orders` (`id`, `table_id`, `status`, `total_amount`, `notes`, `note`, `created_at`, `updated_at`, `payment_id`, `completed_at`) VALUES
(3, 1, 'pending', 2249.97, NULL, NULL, '2024-12-26 11:19:04', '2024-12-26 11:19:04', NULL, NULL),
(4, 1, 'pending', 2249.97, NULL, NULL, '2024-12-26 11:19:15', '2024-12-26 11:19:15', NULL, NULL),
(5, 1, 'pending', 2249.97, NULL, NULL, '2024-12-26 11:20:45', '2024-12-26 11:20:45', NULL, NULL),
(6, 1, 'pending', 2249.97, NULL, NULL, '2024-12-26 11:23:06', '2024-12-26 11:23:06', NULL, NULL),
(7, 1, 'pending', 1499.98, NULL, NULL, '2024-12-26 11:32:43', '2024-12-26 11:32:43', NULL, NULL),
(8, 1, '', 3199.96, NULL, NULL, '2024-12-26 11:32:59', '2024-12-26 11:48:45', NULL, NULL),
(9, 1, 'pending', 5549.93, NULL, NULL, '2024-12-26 15:34:41', '2024-12-26 15:34:41', NULL, NULL),
(10, 1, '', 749.99, NULL, NULL, '2024-12-26 15:35:24', '2024-12-26 15:35:31', NULL, NULL),
(11, 1, 'pending', 3199.96, NULL, NULL, '2024-12-26 15:37:35', '2024-12-26 15:37:35', NULL, NULL),
(12, 1, '', 1599.98, NULL, NULL, '2024-12-26 16:29:27', '2024-12-26 16:29:57', NULL, NULL),
(13, 1, 'pending', 3749.95, NULL, NULL, '2024-12-26 16:41:30', '2024-12-26 16:41:30', NULL, NULL),
(14, 1, 'pending', 6799.92, NULL, NULL, '2024-12-26 16:42:29', '2024-12-26 16:42:29', NULL, NULL),
(15, 1, 'cancelled', 4799.94, NULL, NULL, '2024-12-26 16:50:08', '2024-12-26 16:50:23', NULL, NULL),
(16, 1, 'pending', 99.98, NULL, NULL, '2024-12-26 16:51:22', '2024-12-26 16:51:22', NULL, NULL),
(17, 1, 'pending', 3199.96, NULL, NULL, '2024-12-26 16:57:23', '2024-12-26 16:57:23', NULL, NULL),
(18, 5, 'pending', 49.99, NULL, NULL, '2024-12-26 17:03:47', '2024-12-26 17:03:47', NULL, NULL),
(19, 5, 'delivered', 49.99, NULL, NULL, '2024-12-26 17:04:47', '2024-12-26 18:09:17', NULL, NULL),
(20, 5, 'pending', 349.93, NULL, NULL, '2024-12-26 18:11:52', '2024-12-26 18:11:52', NULL, NULL),
(21, 1, 'pending', 849.97, NULL, NULL, '2024-12-27 06:15:49', '2024-12-27 06:15:49', NULL, NULL),
(22, 2, 'ready', 199.96, NULL, NULL, '2024-12-27 06:16:49', '2024-12-27 11:12:47', NULL, NULL),
(23, 2, 'pending', 199.96, NULL, NULL, '2024-12-27 06:17:52', '2024-12-27 06:17:52', NULL, NULL),
(24, 2, 'pending', 99.98, NULL, NULL, '2024-12-27 06:21:47', '2024-12-27 06:21:47', NULL, NULL),
(25, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:23:10', '2024-12-27 06:23:10', NULL, NULL),
(26, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:24:17', '2024-12-27 06:24:17', NULL, NULL),
(27, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:26:46', '2024-12-27 06:26:46', NULL, NULL),
(28, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:26:54', '2024-12-27 06:26:54', NULL, NULL),
(29, 2, 'pending', 749.99, NULL, NULL, '2024-12-27 06:27:54', '2024-12-27 06:27:54', NULL, NULL),
(30, 2, 'pending', 1599.98, NULL, NULL, '2024-12-27 06:35:39', '2024-12-27 06:35:39', NULL, NULL),
(31, 2, 'pending', 749.99, NULL, NULL, '2024-12-27 06:39:28', '2024-12-27 06:39:28', NULL, NULL),
(32, 2, 'pending', 1599.98, NULL, NULL, '2024-12-27 06:40:14', '2024-12-27 06:40:14', NULL, NULL),
(33, 1, 'pending', 749.99, NULL, NULL, '2024-12-27 06:41:22', '2024-12-27 06:41:22', NULL, NULL),
(34, 1, 'pending', 749.99, NULL, NULL, '2024-12-27 06:43:33', '2024-12-27 06:43:33', NULL, NULL),
(35, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:46:30', '2024-12-27 06:46:30', NULL, NULL),
(36, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:50:12', '2024-12-27 06:50:12', NULL, NULL),
(37, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:50:48', '2024-12-27 06:50:48', NULL, NULL),
(38, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:51:08', '2024-12-27 06:51:08', NULL, NULL),
(39, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:51:20', '2024-12-27 06:51:20', NULL, NULL),
(40, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:51:27', '2024-12-27 06:51:27', NULL, NULL),
(41, 2, 'pending', 749.99, NULL, NULL, '2024-12-27 06:55:43', '2024-12-27 06:55:43', NULL, NULL),
(42, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:56:16', '2024-12-27 06:56:16', NULL, NULL),
(43, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:57:19', '2024-12-27 06:57:19', NULL, NULL),
(44, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 06:59:42', '2024-12-27 06:59:42', NULL, NULL),
(45, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 07:02:45', '2024-12-27 07:02:45', NULL, NULL),
(46, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 07:03:49', '2024-12-27 07:03:49', NULL, NULL),
(47, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 07:08:47', '2024-12-27 07:08:47', NULL, NULL),
(48, 4, 'pending', 49.99, NULL, NULL, '2024-12-27 07:09:27', '2024-12-27 07:09:27', NULL, NULL),
(49, 2, 'ready', 49.99, NULL, NULL, '2024-12-27 07:17:17', '2024-12-27 11:35:54', NULL, NULL),
(50, 1, 'delivered', 49.99, NULL, NULL, '2024-12-27 08:22:54', '2024-12-27 15:49:48', NULL, NULL),
(51, 2, 'delivered', 49.99, NULL, NULL, '2024-12-27 11:35:25', '2024-12-27 15:57:46', NULL, NULL),
(52, 2, 'pending', 49.99, NULL, NULL, '2024-12-27 15:58:57', '2024-12-27 15:58:57', NULL, NULL),
(59, 2, 'pending', 99.98, NULL, NULL, '2024-12-28 07:16:58', '2024-12-28 07:16:58', NULL, NULL),
(60, 2, '', NULL, NULL, NULL, '2024-12-28 07:17:29', '2024-12-28 07:17:29', NULL, NULL),
(61, 2, '', NULL, NULL, NULL, '2024-12-28 07:18:33', '2024-12-28 07:18:33', NULL, NULL),
(62, 1, '', NULL, NULL, NULL, '2024-12-28 07:19:51', '2024-12-28 07:19:51', NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(1, 6, 1, 3, 749.99, '2024-12-26 11:23:06'),
(2, 7, 1, 2, 749.99, '2024-12-26 11:32:43'),
(3, 8, 2, 2, 849.99, '2024-12-26 11:32:59'),
(4, 8, 1, 2, 749.99, '2024-12-26 11:32:59'),
(5, 9, 1, 4, 749.99, '2024-12-26 15:34:41'),
(6, 9, 2, 3, 849.99, '2024-12-26 15:34:41'),
(7, 10, 1, 1, 749.99, '2024-12-26 15:35:24'),
(8, 11, 1, 2, 749.99, '2024-12-26 15:37:35'),
(9, 11, 2, 2, 849.99, '2024-12-26 15:37:35'),
(10, 12, 1, 1, 749.99, '2024-12-26 16:29:27'),
(11, 12, 2, 1, 849.99, '2024-12-26 16:29:27'),
(12, 13, 1, 5, 749.99, '2024-12-26 16:41:30'),
(13, 14, 2, 8, 849.99, '2024-12-26 16:42:29'),
(14, 15, 1, 3, 749.99, '2024-12-26 16:50:08'),
(15, 15, 2, 3, 849.99, '2024-12-26 16:50:08'),
(16, 16, 3, 1, 49.99, '2024-12-26 16:51:22'),
(17, 16, 4, 1, 49.99, '2024-12-26 16:51:22'),
(18, 17, 1, 2, 749.99, '2024-12-26 16:57:23'),
(19, 17, 2, 2, 849.99, '2024-12-26 16:57:23'),
(20, 18, 3, 1, 49.99, '2024-12-26 17:03:47'),
(21, 19, 3, 1, 49.99, '2024-12-26 17:04:47'),
(22, 20, 3, 4, 49.99, '2024-12-26 18:11:52'),
(23, 20, 4, 3, 49.99, '2024-12-26 18:11:52'),
(24, 21, 1, 1, 749.99, '2024-12-27 06:15:49'),
(25, 21, 3, 2, 49.99, '2024-12-27 06:15:49'),
(26, 22, 3, 2, 49.99, '2024-12-27 06:16:49'),
(27, 22, 4, 2, 49.99, '2024-12-27 06:16:49'),
(28, 23, 3, 2, 49.99, '2024-12-27 06:17:52'),
(29, 23, 4, 2, 49.99, '2024-12-27 06:17:52'),
(30, 24, 3, 1, 49.99, '2024-12-27 06:21:47'),
(31, 24, 4, 1, 49.99, '2024-12-27 06:21:47'),
(32, 25, 3, 1, 49.99, '2024-12-27 06:23:10'),
(33, 26, 3, 1, 49.99, '2024-12-27 06:24:17'),
(34, 27, 3, 1, 49.99, '2024-12-27 06:26:46'),
(35, 28, 3, 1, 49.99, '2024-12-27 06:26:54'),
(36, 29, 1, 1, 749.99, '2024-12-27 06:27:54'),
(37, 30, 1, 1, 749.99, '2024-12-27 06:35:39'),
(38, 30, 2, 1, 849.99, '2024-12-27 06:35:39'),
(39, 31, 1, 1, 749.99, '2024-12-27 06:39:28'),
(40, 32, 1, 1, 749.99, '2024-12-27 06:40:14'),
(41, 32, 2, 1, 849.99, '2024-12-27 06:40:14'),
(42, 33, 1, 1, 749.99, '2024-12-27 06:41:22'),
(43, 34, 1, 1, 749.99, '2024-12-27 06:43:33'),
(44, 35, 3, 1, 49.99, '2024-12-27 06:46:30'),
(45, 36, 3, 1, 49.99, '2024-12-27 06:50:12'),
(46, 37, 3, 1, 49.99, '2024-12-27 06:50:48'),
(47, 38, 4, 1, 49.99, '2024-12-27 06:51:08'),
(48, 39, 4, 1, 49.99, '2024-12-27 06:51:20'),
(49, 40, 4, 1, 49.99, '2024-12-27 06:51:27'),
(50, 41, 1, 1, 749.99, '2024-12-27 06:55:43'),
(51, 42, 4, 1, 49.99, '2024-12-27 06:56:16'),
(52, 43, 3, 1, 49.99, '2024-12-27 06:57:19'),
(53, 44, 3, 1, 49.99, '2024-12-27 06:59:42'),
(54, 45, 3, 1, 49.99, '2024-12-27 07:02:45'),
(55, 46, 3, 1, 49.99, '2024-12-27 07:03:49'),
(56, 47, 3, 1, 49.99, '2024-12-27 07:08:47'),
(57, 48, 3, 1, 49.99, '2024-12-27 07:09:27'),
(58, 49, 3, 1, 49.99, '2024-12-27 07:17:17'),
(59, 50, 3, 1, 49.99, '2024-12-27 08:22:54'),
(60, 51, 3, 1, 49.99, '2024-12-27 11:35:25'),
(61, 52, 3, 1, 49.99, '2024-12-27 15:58:57'),
(62, 59, 3, 1, 49.99, '2024-12-28 07:16:58'),
(63, 59, 4, 1, 49.99, '2024-12-28 07:16:58');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `payment_method` enum('cash','credit_card','debit_card') NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `payment_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `special` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `status`, `created_at`, `special`, `view_count`) VALUES
(1, 1, 'Tavuk Şiş', 'asdasdasdasda asfdasdas asdasda sdasd', 749.99, '6769785d70e7b.jpeg', 1, '2024-12-24 07:44:58', 0, 0),
(2, 1, 'Adana Şiş', 'asdasdasd asdasdasd asdasdasd', 849.99, '676aa5cbe6cf5.jpg', 1, '2024-12-24 07:45:38', 0, 0),
(3, 2, 'Kola', 'asdasdasd asdasdas dsa dasd', 49.99, '676a97b066ef6.jpg', 1, '2024-12-24 07:46:14', 0, 0),
(4, 2, 'meyve Suyu', 'asdasdas dasdasdas asdasd', 49.99, '676a97b066ef6.jpg', 1, '2024-12-24 07:46:47', 0, 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `review_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `guest_count` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `reservations`
--

INSERT INTO `reservations` (`id`, `customer_name`, `customer_phone`, `customer_email`, `table_id`, `guest_count`, `reservation_date`, `reservation_time`, `special_requests`, `status`, `created_at`) VALUES
(1, 'Test', '5365564544', 'omer.yyildirim@gmail.com', 6, 4, '2024-12-27', '16:24:00', 'sdasd', 'confirmed', '2024-12-27 11:22:07');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `header_bg` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`, `header_bg`) VALUES
('currency', 'TL', '2024-12-24 06:48:18', NULL),
('header_bg', '676a6e27408af.jpg', '2024-12-24 12:50:53', NULL),
('logo', '676a6b29d3f6a.png', '2024-12-24 08:04:57', NULL),
('restaurant_name', 'Test Restaurant', '2024-12-24 07:48:18', NULL),
('theme_color', '#e84a4a', '2024-12-24 11:08:58', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_no` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 4,
  `qr_code` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `tables`
--

INSERT INTO `tables` (`id`, `table_no`, `capacity`, `qr_code`, `status`, `created_at`) VALUES
(1, 'Masa 1', 4, NULL, 'active', '2024-12-26 11:16:43'),
(2, 'Masa 2', 4, NULL, 'active', '2024-12-26 11:16:43'),
(4, 'Masa 4', 4, NULL, 'active', '2024-12-26 11:16:43'),
(5, 'Masa 5', 4, NULL, 'active', '2024-12-26 11:16:43'),
(6, 'Masa 3', 4, NULL, 'active', '2024-12-26 16:27:52');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Tablo için indeksler `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Tablo için indeksler `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Tablo için indeksler `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`);

--
-- Tablo için indeksler `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_status` (`category_id`,`status`);

--
-- Tablo için indeksler `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Tablo için indeksler `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`);

--
-- Tablo için indeksler `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Tablo için indeksler `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Tablo kısıtlamaları `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`);

--
-- Tablo kısıtlamaları `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Tablo kısıtlamaları `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`);

--
-- Tablo kısıtlamaları `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Tablo kısıtlamaları `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`),
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Tablo kısıtlamaları `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`);

--
-- Tablo kısıtlamaları `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
