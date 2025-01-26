-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 26 Oca 2025, 12:24:37
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

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
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `admins`
--

INSERT INTO `admins` (`id`, `username`, `name`, `phone`, `password`, `email`, `created_at`, `role_id`, `status`) VALUES
(1, 'admin', 'Sistem Yöneticisi', '', '$2y$10$spSmwLUlzZuvmDBv1lUr6OhBwIFl6uK8haA919W2N9Uj3ZqT0sUfu', 'admin@example.com', '2024-12-24 06:40:16', 1, 1),
(2, 'test', 'Deneme deneme', '', '$argon2id$v=19$m=65536,t=4,p=3$Z0ExTFFyMjhMYzQ0R2dHVw$cifv/NHZid8ZU5Xhju6HsHQ0yzh/QXDa2mFe/iOD+Oo', '', '2025-01-04 07:36:37', 4, 1);

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
(6, 'ANA YEMEK', 1, 0, '2025-01-01 12:16:22', '676a6e1cc23fc.jpg'),
(7, 'TATLI', 1, 0, '2025-01-01 12:19:12', '676a664757925.jpg'),
(8, 'ÇORBA', 1, 0, '2025-01-01 12:19:28', '6774e8b62a116.jpg'),
(9, 'İÇEÇEK', 1, 0, '2025-01-01 12:20:01', '676a6e27408af.jpg');

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
(26, 14, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-02 12:51:02'),
(27, 14, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-02 12:51:17'),
(28, 14, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-02 12:51:36'),
(29, 15, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-02 12:52:47'),
(30, 16, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-02 13:00:04'),
(31, 16, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-02 13:00:27'),
(32, 17, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-02 13:17:14'),
(33, 17, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-02 13:22:12'),
(34, 17, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-02 13:24:15'),
(35, 17, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-02 13:24:30'),
(36, 18, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-02 13:25:19'),
(37, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-02 13:27:06'),
(38, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-02 13:28:22'),
(39, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 10:05:13'),
(40, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 10:16:57'),
(41, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 10:17:09'),
(42, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 10:19:04'),
(43, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 10:19:40'),
(44, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 10:33:33'),
(45, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 10:45:45'),
(46, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 10:53:14'),
(47, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:02:22'),
(48, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:10:41'),
(49, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:11:51'),
(50, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:15:46'),
(51, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:19:12'),
(52, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:23:43'),
(53, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:27:39'),
(54, 19, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-03 13:29:36'),
(55, 20, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-03 13:30:07'),
(56, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:30:44'),
(57, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:31:47'),
(58, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:32:05'),
(59, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:33:52'),
(60, 18, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:36:24'),
(61, 21, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-03 13:37:47'),
(62, 21, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 13:38:04'),
(63, 22, 'new_order', 'Masa Masa 3\'dan yeni sipariş geldi!', 1, '2025-01-03 13:38:49'),
(64, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 13:39:20'),
(65, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 13:40:18'),
(66, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 13:40:50'),
(67, 21, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 18:09:15'),
(68, 21, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 18:09:41'),
(69, 21, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 18:10:01'),
(70, 23, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-03 18:10:20'),
(71, 23, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-03 18:11:03'),
(72, 24, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-03 18:11:27'),
(73, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:15:35'),
(74, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:16:52'),
(75, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:16:57'),
(76, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:17:03'),
(77, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:17:09'),
(78, 25, 'new_order', 'Masa Masa 3\'dan yeni sipariş geldi!', 1, '2025-01-03 18:19:32'),
(79, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:21:50'),
(80, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:28:44'),
(81, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:29:17'),
(82, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:30:56'),
(83, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:32:14'),
(84, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:33:13'),
(85, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 18:35:47'),
(86, 24, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 13:09:36'),
(87, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-04 13:09:57'),
(88, 24, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 13:16:21'),
(89, 23, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 13:22:54'),
(90, 23, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 13:23:02'),
(91, 23, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 13:33:33'),
(92, 23, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 13:57:35'),
(93, 23, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 13:57:46'),
(94, 23, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 13:57:53'),
(95, 23, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 14:05:37'),
(96, 23, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 14:11:20'),
(97, 21, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 15:23:51'),
(98, 21, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 15:24:08'),
(99, 21, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 15:24:14'),
(100, 21, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 15:24:28'),
(101, 21, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-04 15:25:03'),
(102, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-04 15:53:02'),
(103, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-04 15:53:13'),
(104, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-04 15:53:24'),
(105, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-04 15:53:31'),
(106, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-04 17:29:05'),
(107, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-04 17:29:11'),
(108, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-04 17:29:14'),
(109, 25, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-04 17:29:17'),
(110, 27, 'new_order', 'Masa Masa 2\'dan yeni sipariş geldi!', 1, '2025-01-05 10:44:25'),
(111, 27, 'order_updated', 'Masa Masa 2\'a yeni ürünler eklendi', 0, '2025-01-05 10:45:05'),
(112, 27, 'order_updated', 'Masa Masa 2\'a yeni ürünler eklendi', 0, '2025-01-05 10:45:29'),
(113, 28, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-25 12:56:14'),
(114, 29, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-25 12:57:20'),
(115, 41, 'new_order', 'Masa Masa 5 için onaylanan rezervasyondan yeni sipariş', 0, '2025-01-25 16:10:27'),
(116, NULL, 'payment_cancelled', 'Masa Masa 2\'ın ödemesi iptal edildi. Neden: deneme', 0, '2025-01-26 10:59:41'),
(117, 46, 'new_order', 'Masa Masa 2\'a iptal edilmiş siparişler yeniden eklendi!', 0, '2025-01-26 11:06:11'),
(118, NULL, 'payment_cancelled', 'Masa Masa 3\'ın ödemesi iptal edildi. Neden: asdadasd', 0, '2025-01-26 11:12:31'),
(119, 47, 'new_order', 'Masa Masa 3\'a iptal edilmiş siparişler yeniden eklendi!', 0, '2025-01-26 11:12:37');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','preparing','ready','delivered','completed') DEFAULT 'pending',
  `order_code` varchar(6) DEFAULT NULL,
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

INSERT INTO `orders` (`id`, `reservation_id`, `table_id`, `status`, `order_code`, `total_amount`, `notes`, `note`, `created_at`, `updated_at`, `payment_id`, `completed_at`) VALUES
(14, NULL, 1, 'completed', NULL, 950.00, NULL, NULL, '2025-01-02 12:51:02', '2025-01-02 12:55:58', 3, '2025-01-02 12:55:58'),
(15, NULL, 1, 'completed', NULL, 60.00, NULL, NULL, '2025-01-02 12:52:47', '2025-01-02 12:55:58', 3, '2025-01-02 12:55:58'),
(16, NULL, 1, 'completed', NULL, 310.00, NULL, NULL, '2025-01-02 13:00:04', '2025-01-02 13:00:38', 4, '2025-01-02 13:00:38'),
(17, NULL, 1, 'completed', NULL, 240.00, NULL, NULL, '2025-01-02 13:17:14', '2025-01-02 13:25:13', 5, '2025-01-02 13:25:13'),
(18, NULL, 1, 'completed', NULL, 6210.00, NULL, 'asdasd asdas das\n---\n16:28: karışık adana', '2025-01-02 13:25:19', '2025-01-03 13:37:37', 6, '2025-01-03 13:37:37'),
(19, NULL, 1, 'completed', NULL, 250.00, NULL, NULL, '2025-01-03 13:29:36', '2025-01-03 13:37:37', 6, '2025-01-03 13:37:37'),
(20, NULL, 1, 'completed', NULL, 250.00, NULL, NULL, '2025-01-03 13:30:07', '2025-01-03 13:37:37', 6, '2025-01-03 13:37:37'),
(21, NULL, 1, 'completed', NULL, 3750.00, NULL, NULL, '2025-01-03 13:37:47', '2025-01-04 15:51:28', 8, '2025-01-04 15:51:28'),
(22, NULL, 3, 'completed', NULL, 3960.00, NULL, '16:38: Test\n---\n16:39: fındığı bol\n---\n16:40: test2\n---\n21:15: soğansız', '2025-01-03 13:38:49', '2025-01-03 18:17:27', 7, '2025-01-03 18:17:27'),
(25, NULL, 3, '', NULL, 250.00, NULL, NULL, '2025-01-03 18:19:32', '2025-01-26 11:12:31', 12, '2025-01-26 11:12:23'),
(27, NULL, 2, '', NULL, 400.00, NULL, '13:44: Test', '2025-01-05 10:44:25', '2025-01-26 10:59:41', 11, '2025-01-26 10:59:26'),
(28, NULL, 1, 'completed', NULL, 500.00, '', NULL, '2025-01-05 11:53:45', '2025-01-25 12:57:08', 9, '2025-01-25 12:57:08'),
(29, NULL, 3, '', NULL, 250.00, NULL, NULL, '2025-01-25 12:57:20', '2025-01-26 11:12:31', 12, '2025-01-26 11:12:23'),
(30, NULL, 1, 'pending', NULL, 250.00, '', NULL, '2025-01-25 12:58:40', '2025-01-26 10:23:15', NULL, NULL),
(41, 18, 3, '', '375D61', 250.00, NULL, NULL, '2025-01-25 16:10:27', '2025-01-26 11:12:31', 12, '2025-01-26 11:12:23'),
(42, NULL, 1, 'ready', NULL, 500.00, NULL, NULL, '2025-01-26 10:07:04', '2025-01-26 10:14:53', NULL, NULL),
(43, NULL, 2, '', NULL, 250.00, NULL, NULL, '2025-01-26 10:11:54', '2025-01-26 10:59:41', 11, '2025-01-26 10:59:26'),
(44, NULL, 1, 'ready', NULL, 2250.00, NULL, NULL, '2025-01-26 10:11:57', '2025-01-26 10:14:49', NULL, NULL),
(45, NULL, 1, 'ready', NULL, 2500.00, NULL, NULL, '2025-01-26 10:12:15', '2025-01-26 10:14:45', NULL, NULL),
(46, NULL, 2, 'pending', NULL, NULL, NULL, NULL, '2025-01-26 11:06:11', '2025-01-26 11:06:11', NULL, NULL),
(47, NULL, 3, 'completed', NULL, 750.00, NULL, NULL, '2025-01-26 11:12:37', '2025-01-26 11:14:25', 13, '2025-01-26 11:14:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_codes`
--

CREATE TABLE `order_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `order_codes`
--

INSERT INTO `order_codes` (`id`, `code`, `active`, `created_at`, `expires_at`) VALUES
(1, '0344', 0, '2025-01-03 06:47:04', '2025-01-04 06:47:04'),
(2, '5245', 0, '2025-01-03 06:47:19', '2025-01-04 06:47:19'),
(3, '897267', 0, '2025-01-03 06:47:26', '2025-01-04 06:47:26'),
(4, '516992', 0, '2025-01-03 07:04:59', '2025-01-04 07:04:59'),
(5, '647739', 0, '2025-01-05 10:04:11', '2025-01-06 10:04:11'),
(6, '620764', 1, '2025-01-25 12:56:06', '2025-01-26 12:56:06');

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
(174, 14, 1, 1, 250.00, '2025-01-02 12:51:02'),
(175, 14, 8, 3, 150.00, '2025-01-02 12:51:02'),
(176, 14, 3, 1, 250.00, '2025-01-02 12:51:36'),
(177, 15, 5, 1, 60.00, '2025-01-02 12:52:47'),
(178, 16, 5, 1, 60.00, '2025-01-02 13:00:04'),
(179, 16, 7, 1, 250.00, '2025-01-02 13:00:27'),
(180, 17, 5, 4, 60.00, '2025-01-02 13:17:14'),
(181, 18, 5, 1, 60.00, '2025-01-02 13:25:19'),
(182, 18, 1, 21, 250.00, '2025-01-02 13:27:06'),
(183, 18, 8, 1, 150.00, '2025-01-03 10:05:13'),
(184, 18, 3, 3, 250.00, '2025-01-03 13:02:22'),
(185, 19, 3, 1, 250.00, '2025-01-03 13:29:36'),
(186, 20, 1, 1, 250.00, '2025-01-03 13:30:07'),
(188, 21, 3, 8, 250.00, '2025-01-03 13:38:04'),
(189, 22, 5, 1, 60.00, '2025-01-03 13:38:49'),
(190, 22, 1, 5, 250.00, '2025-01-03 13:38:49'),
(191, 22, 8, 1, 150.00, '2025-01-03 13:39:20'),
(192, 22, 3, 7, 250.00, '2025-01-03 13:40:50'),
(193, 22, 7, 3, 250.00, '2025-01-03 13:40:50'),
(197, 44, 1, 9, 250.00, '2025-01-03 18:19:32'),
(198, 45, 3, 10, 250.00, '2025-01-04 13:09:57'),
(200, 21, 1, 3, 250.00, '2025-01-04 15:24:08'),
(201, 21, 7, 4, 250.00, '2025-01-04 15:24:14'),
(203, 25, 7, 1, 250.00, '2025-01-04 17:29:11'),
(206, 43, 1, 1, 250.00, '2025-01-05 10:44:25'),
(207, 42, 3, 2, 250.00, '2025-01-05 10:44:25'),
(208, 27, 6, 4, 100.00, '2025-01-05 10:45:29'),
(209, 28, 3, 1, 250.00, '2025-01-05 11:53:45'),
(210, 28, 1, 1, 250.00, '2025-01-25 12:56:14'),
(211, 29, 1, 1, 250.00, '2025-01-25 12:57:20'),
(212, 30, 3, 1, 250.00, '2025-01-25 12:58:40'),
(213, 36, 1, 1, 250.00, '2025-01-25 15:36:16'),
(214, 36, 7, 1, 250.00, '2025-01-25 15:36:16'),
(215, 37, 6, 1, 100.00, '2025-01-25 15:58:59'),
(216, 38, 1, 1, 250.00, '2025-01-25 15:59:49'),
(217, 39, 1, 1, 250.00, '2025-01-25 16:03:02'),
(218, 40, 1, 1, 250.00, '2025-01-25 16:06:59'),
(219, 41, 1, 1, 250.00, '2025-01-25 16:10:27'),
(220, 46, 6, 4, 100.00, '2025-01-26 11:06:11'),
(221, 46, 1, 1, 250.00, '2025-01-26 11:06:11'),
(222, 47, 7, 1, 250.00, '2025-01-26 11:12:37'),
(223, 47, 1, 1, 250.00, '2025-01-26 11:12:37'),
(224, 47, 1, 1, 250.00, '2025-01-26 11:12:37');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_settings`
--

CREATE TABLE `order_settings` (
  `id` int(11) NOT NULL,
  `code_required` tinyint(1) DEFAULT 0,
  `code_length` enum('4','6') DEFAULT '4',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `order_settings`
--

INSERT INTO `order_settings` (`id`, `code_required`, `code_length`, `created_at`, `updated_at`) VALUES
(1, 1, '6', '2025-01-03 07:04:53', '2025-01-03 07:04:53');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `payment_method` enum('cash','pos') NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `payment_note` text DEFAULT NULL,
  `status` enum('completed','cancelled') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `payments`
--

INSERT INTO `payments` (`id`, `table_id`, `payment_method`, `total_amount`, `paid_amount`, `payment_note`, `status`, `created_at`) VALUES
(3, 1, 'pos', 60.00, 60.00, NULL, 'completed', '2025-01-02 12:55:58'),
(4, 1, 'pos', 310.00, 310.00, NULL, 'completed', '2025-01-02 13:00:38'),
(5, 1, 'pos', 240.00, 240.00, NULL, 'completed', '2025-01-02 13:25:13'),
(6, 1, 'cash', 6710.00, 6710.00, NULL, 'completed', '2025-01-03 13:37:37'),
(7, 3, 'pos', 3960.00, 3960.00, NULL, 'completed', '2025-01-03 18:17:27'),
(8, 1, 'pos', 3750.00, 3750.00, NULL, 'completed', '2025-01-04 15:51:28'),
(9, 1, 'cash', 500.00, 500.00, NULL, 'completed', '2025-01-25 12:57:08'),
(10, 2, 'pos', 650.00, 650.00, NULL, 'cancelled', '2025-01-26 10:55:16'),
(11, 2, 'cash', 650.00, 650.00, '\nİptal Nedeni: deneme', 'cancelled', '2025-01-26 10:59:26'),
(12, 3, 'cash', 750.00, 750.00, '\nİptal Nedeni: asdadasd', 'cancelled', '2025-01-26 11:12:23'),
(13, 3, 'pos', 750.00, 750.00, NULL, 'completed', '2025-01-26 11:14:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pre_orders`
--

CREATE TABLE `pre_orders` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
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
(1, 6, 'Adana Kebap', 'asdasd as', 250.00, '676aa5cbe6cf5.jpg', 1, '2025-01-01 12:23:44', 0, 0),
(3, 6, 'Kuş Başı Kebap', 'asdasd', 250.00, '6774e519e3e6f.jpg', 1, '2025-01-01 12:24:49', 0, 0),
(5, 9, 'KOLA', 'ASDAS DASD ASD AS', 60.00, '676a97b066ef6.jpg', 1, '2025-01-01 12:28:02', 0, 0),
(6, 8, 'Çorba', 'asdasdasdas', 100.00, '6774e8b62a116.jpg', 1, '2025-01-01 12:28:45', 0, 0),
(7, 6, 'Karışık Kebap', 'asdas dasd asd asd as', 250.00, '676a6e1cc23fc.jpg', 1, '2025-01-01 12:30:20', 0, 0);

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
(18, 'ömer', '05392833498', NULL, 5, 4, '2025-01-25', '19:10:00', '', 'confirmed', '2025-01-25 16:08:50');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `reservation_orders`
--

CREATE TABLE `reservation_orders` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `reservation_orders`
--

INSERT INTO `reservation_orders` (`id`, `reservation_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(1, 18, 1, 1, 250.00, '2025-01-25 16:08:50');

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
-- Tablo için tablo yapısı `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `permissions`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'Süper Admin', 'super-admin', 'Tam yetkili sistem yöneticisi', '{\"dashboard\": {\"view\": true}, \"categories\": {\"view\": true, \"add\": true, \"edit\": true, \"delete\": true, \"kitchen_only\": true}, \"products\": {\"view\": true, \"add\": true, \"edit\": true, \"delete\": true}, \"orders\": {\"view\": true, \"add\": true, \"update\": true, \"delete\": true, \"payment\": true}, \"tables\": {\"view\": true, \"manage\": true}, \"kitchen\": {\"view\": true, \"manage\": true}, \"users\": {\"view\": true, \"add\": true, \"edit\": true, \"delete\": true}, \"roles\": {\"view\": true, \"add\": true, \"edit\": true, \"delete\": true}, \"settings\": {\"view\": true, \"edit\": true}, \"reports\": {\"view\": true}}', 1, '2025-01-04 07:46:12', '2025-01-26 10:48:46'),
(2, 'Yönetici', 'admin', 'Genel sistem yöneticisi', '{\"dashboard\": true, \"categories\": {\"view\": true, \"add\": true, \"edit\": true, \"delete\": true}, \"products\": {\"view\": true, \"add\": true, \"edit\": true, \"delete\": true}, \"orders\": {\"view\": true, \"add\": true, \"edit\": true, \"delete\": true, \"payment\": true}, \"tables\": {\"view\": true, \"manage\": true}, \"users\": true, \"roles\": true, \"settings\": true, \"reports\": true}', 1, '2025-01-04 07:46:12', '2025-01-26 10:48:46'),
(3, 'Mutfak', 'kitchen', 'Mutfak personeli', '{\"dashboard\": true, \"categories\": {\"view\": true, \"kitchen_only\": true}, \"products\": {\"view\": true}, \"orders\": {\"view\": true, \"update\": true}, \"kitchen\": {\"view\": true, \"manage\": true}}', 1, '2025-01-04 07:46:12', '2025-01-26 10:48:47'),
(4, 'Garson', 'waiter', 'Servis personeli', '{\"dashboard\": {\"view\": true}, \"categories\": {\"view\": true, \"add\": true, \"edit\": true, \"delete\": true, \"kitchen_only\": false}, \"products\": {\"view\": true, \"add\": true, \"edit\": true, \"delete\": true}, \"orders\": {\"view\": true, \"add\": true, \"update\": true, \"delete\": true}, \"tables\": {\"view\": true, \"manage\": false, \"payment\": false, \"sales\": true, \"add_order\": true, \"edit_order\": true, \"delete_order\": true, \"save_order\": true}, \"kitchen\": {\"view\": false, \"manage\": false}, \"users\": {\"view\": false, \"add\": false, \"edit\": false, \"delete\": false}, \"roles\": {\"view\": false, \"add\": false, \"edit\": false, \"delete\": false}, \"settings\": {\"view\": true, \"edit\": true}, \"reports\": {\"view\": false}, \"payments\": {\"view\": false, \"create\": false, \"cancel\": false, \"manage\": false}, \"reservations\": {\"view\": true, \"add\": true, \"edit\": true, \"delete\": true, \"approve\": true, \"reject\": true, \"settings\": false}, \"order_settings\": {\"view\": true, \"edit\": true, \"payment_methods\": false, \"discount_rules\": false, \"tax_settings\": false, \"printer_settings\": false}}', 1, '2025-01-04 07:46:12', '2025-01-26 10:48:47');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `header_bg` varchar(255) DEFAULT NULL,
  `order_code_required` tinyint(1) DEFAULT 0,
  `order_code_length` enum('4','6') DEFAULT '4'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`, `header_bg`, `order_code_required`, `order_code_length`) VALUES
('currency', 'TL', '2025-01-05 10:18:47', NULL, 0, '4'),
('header_bg', '676a6e27408af.jpg', '2025-01-05 10:38:55', NULL, 0, '4'),
('logo', '676a6b29d3f6a.png', '2025-01-05 10:38:55', NULL, 0, '4'),
('restaurant_name', 'Test Restuarantt', '2025-01-05 11:32:59', NULL, 0, '4'),
('theme_color', '#fe5039', '2025-01-05 11:32:53', NULL, 0, '4');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_code` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `tables`
--

INSERT INTO `tables` (`id`, `table_no`, `capacity`, `qr_code`, `status`, `created_at`, `order_code`) VALUES
(1, 'Masa 1', 4, NULL, 'active', '2025-01-01 12:14:45', NULL),
(2, 'Masa 2', 4, NULL, '', '2025-01-01 12:14:50', NULL),
(3, 'Masa 3', 4, NULL, '', '2025-01-01 12:14:55', NULL),
(4, 'Masa 4', 4, NULL, 'active', '2025-01-01 12:15:00', NULL),
(5, 'Masa 5', 4, NULL, 'active', '2025-01-01 12:15:06', NULL),
(6, 'Masa 6', 4, NULL, 'active', '2025-01-02 12:45:45', NULL),
(8, '7', 4, NULL, 'active', '2025-01-04 15:42:05', NULL);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`);

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
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Tablo için indeksler `order_codes`
--
ALTER TABLE `order_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Tablo için indeksler `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Tablo için indeksler `order_settings`
--
ALTER TABLE `order_settings`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`);

--
-- Tablo için indeksler `pre_orders`
--
ALTER TABLE `pre_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `item_id` (`item_id`);

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
-- Tablo için indeksler `reservation_orders`
--
ALTER TABLE `reservation_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Tablo için indeksler `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Tablo için indeksler `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Tablo için AUTO_INCREMENT değeri `order_codes`
--
ALTER TABLE `order_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

--
-- Tablo için AUTO_INCREMENT değeri `order_settings`
--
ALTER TABLE `order_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tablo için AUTO_INCREMENT değeri `pre_orders`
--
ALTER TABLE `pre_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tablo için AUTO_INCREMENT değeri `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `reservation_orders`
--
ALTER TABLE `reservation_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `admins_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `admins_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Tablo kısıtlamaları `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `pre_orders`
--
ALTER TABLE `pre_orders`
  ADD CONSTRAINT `pre_orders_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`),
  ADD CONSTRAINT `pre_orders_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `products` (`id`);

--
-- Tablo kısıtlamaları `reservation_orders`
--
ALTER TABLE `reservation_orders`
  ADD CONSTRAINT `reservation_orders_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
