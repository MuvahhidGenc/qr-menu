-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 03 Oca 2025, 14:45:16
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
(6, 'ANA YEMEK', 1, 0, '2025-01-01 12:16:22', '676a6e1cc23fc.jpg'),
(7, 'TATLI', 1, 0, '2025-01-01 12:19:12', '677532bcbcc11.jpg'),
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
(61, 21, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 0, '2025-01-03 13:37:47'),
(62, 21, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 0, '2025-01-03 13:38:04'),
(63, 22, 'new_order', 'Masa Masa 3\'dan yeni sipariş geldi!', 0, '2025-01-03 13:38:49'),
(64, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 0, '2025-01-03 13:39:20'),
(65, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 1, '2025-01-03 13:40:18'),
(66, 22, 'order_updated', 'Masa Masa 3\'a yeni ürünler eklendi', 0, '2025-01-03 13:40:50');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
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

INSERT INTO `orders` (`id`, `table_id`, `status`, `order_code`, `total_amount`, `notes`, `note`, `created_at`, `updated_at`, `payment_id`, `completed_at`) VALUES
(14, 1, 'completed', NULL, 950.00, NULL, NULL, '2025-01-02 12:51:02', '2025-01-02 12:55:58', 3, '2025-01-02 12:55:58'),
(15, 1, 'completed', NULL, 60.00, NULL, NULL, '2025-01-02 12:52:47', '2025-01-02 12:55:58', 3, '2025-01-02 12:55:58'),
(16, 1, 'completed', NULL, 310.00, NULL, NULL, '2025-01-02 13:00:04', '2025-01-02 13:00:38', 4, '2025-01-02 13:00:38'),
(17, 1, 'completed', NULL, 240.00, NULL, NULL, '2025-01-02 13:17:14', '2025-01-02 13:25:13', 5, '2025-01-02 13:25:13'),
(18, 1, 'completed', NULL, 6210.00, NULL, 'asdasd asdas das\n---\n16:28: karışık adana', '2025-01-02 13:25:19', '2025-01-03 13:37:37', 6, '2025-01-03 13:37:37'),
(19, 1, 'completed', NULL, 250.00, NULL, NULL, '2025-01-03 13:29:36', '2025-01-03 13:37:37', 6, '2025-01-03 13:37:37'),
(20, 1, 'completed', NULL, 250.00, NULL, NULL, '2025-01-03 13:30:07', '2025-01-03 13:37:37', 6, '2025-01-03 13:37:37'),
(21, 1, 'pending', NULL, 500.00, NULL, NULL, '2025-01-03 13:37:47', '2025-01-03 13:38:04', NULL, NULL),
(22, 3, 'pending', NULL, 1710.00, NULL, '16:38: Test\n---\n16:39: fındığı bol\n---\n16:40: test2', '2025-01-03 13:38:49', '2025-01-03 13:40:50', NULL, NULL);

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
(4, '516992', 1, '2025-01-03 07:04:59', '2025-01-04 07:04:59');

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
(187, 21, 1, 1, 250.00, '2025-01-03 13:37:47'),
(188, 21, 3, 1, 250.00, '2025-01-03 13:38:04'),
(189, 22, 5, 1, 60.00, '2025-01-03 13:38:49'),
(190, 22, 1, 2, 250.00, '2025-01-03 13:38:49'),
(191, 22, 8, 1, 150.00, '2025-01-03 13:39:20'),
(192, 22, 3, 2, 250.00, '2025-01-03 13:40:50'),
(193, 22, 7, 2, 250.00, '2025-01-03 13:40:50');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `payments`
--

INSERT INTO `payments` (`id`, `table_id`, `payment_method`, `total_amount`, `paid_amount`, `payment_note`, `created_at`) VALUES
(3, 1, 'pos', 60.00, 60.00, NULL, '2025-01-02 12:55:58'),
(4, 1, 'pos', 310.00, 310.00, NULL, '2025-01-02 13:00:38'),
(5, 1, 'pos', 240.00, 240.00, NULL, '2025-01-02 13:25:13'),
(6, 1, 'cash', 6710.00, 6710.00, NULL, '2025-01-03 13:37:37');

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
(7, 6, 'Karışık Kebap', 'asdas dasd asd asd as', 250.00, '676a6e1cc23fc.jpg', 1, '2025-01-01 12:30:20', 0, 0),
(8, 7, 'TATLI', 'asdas dasd asd asd as', 150.00, '677532bcbcc11.jpg', 1, '2025-01-01 12:49:30', 0, 0);

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
  `header_bg` varchar(255) DEFAULT NULL,
  `order_code_required` tinyint(1) DEFAULT 0,
  `order_code_length` enum('4','6') DEFAULT '4'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`, `header_bg`, `order_code_required`, `order_code_length`) VALUES
('currency', 'TL', '2024-12-24 06:48:18', NULL, 0, '4'),
('header_bg', '', '2025-01-02 13:41:13', NULL, 0, '4'),
('logo', '', '2025-01-02 13:41:13', NULL, 0, '4'),
('restaurant_name', 'Test Restaurant', '2024-12-24 07:48:18', NULL, 0, '4'),
('theme_color', '#e74c3c', '2025-01-02 13:41:13', NULL, 0, '4');

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
(2, 'Masa 2', 4, NULL, 'active', '2025-01-01 12:14:50', NULL),
(3, 'Masa 3', 4, NULL, 'active', '2025-01-01 12:14:55', NULL),
(4, 'Masa 4', 4, NULL, 'active', '2025-01-01 12:15:00', NULL),
(5, 'Masa 5', 4, NULL, 'active', '2025-01-01 12:15:06', NULL),
(6, 'Masa 6', 4, NULL, 'active', '2025-01-02 12:45:45', NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Tablo için AUTO_INCREMENT değeri `order_codes`
--
ALTER TABLE `order_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- Tablo için AUTO_INCREMENT değeri `order_settings`
--
ALTER TABLE `order_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Tablo kısıtlamaları `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
