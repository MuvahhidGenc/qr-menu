-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 31 Oca 2025, 07:41:52
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
(4, 'tmutfak', 'mutfak', '', '$2y$10$8Fl0Ru482zYVTeoD72OMkeE6QNFbp8N1rSN9zlhl6HDoV5RFhhBby', '', '2025-01-30 07:36:44', 3, 1),
(5, 'tgarson', 'garson', '', '$2y$10$pdV/iMQuZ4AqitDpErrtkeFBw1GkM6a3ySqO68IXJtnTtQK3PoHVC', '', '2025-01-30 07:37:08', 4, 1),
(6, 'tyonetici', 'yönetici', '', '$2y$10$UvR8pL1D2Cnwle6QQ7BOxuwAjoVe.q9ATN0AosJhe9MNopTcGoeku', '', '2025-01-30 07:37:39', 2, 1),
(8, 'test', 'test', '', '$2y$10$X8HVN5CRQ7ltUlGf7C5zd.rg.6P7tqhEA3o/QKF7kqpoQEEdKEmea', '', '2025-01-30 08:01:18', 4, 1);

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
(6, 'ANA YEMEK', 1, 0, '2025-01-01 12:16:22', '676a97b066ef6.jpg'),
(8, 'ÇORBA', 1, 1, '2025-01-01 12:19:28', '676a6742c6cac.png'),
(9, 'İÇEÇEK', 1, 2, '2025-01-01 12:20:01', '6769785d70e7b.jpeg'),
(18, 'TATLI', 1, 3, '2025-01-28 12:51:16', '676a97b066ef6.jpg');

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
(169, 78, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 0, '2025-01-31 06:34:49'),
(170, 78, 'order_status', 'Sipariş #78 iptal edildi', 0, '2025-01-31 06:35:11'),
(171, 78, 'order_status', 'Sipariş #78 geri alındı', 0, '2025-01-31 06:35:27'),
(172, 78, 'order_status', 'Sipariş #78 hazırlanıyor', 0, '2025-01-31 06:35:32'),
(173, 78, 'order_status', 'Sipariş #78 hazırlanıyor', 0, '2025-01-31 06:35:32'),
(174, 78, 'order_status', 'Sipariş #78 hazır', 0, '2025-01-31 06:35:49'),
(175, 78, 'order_status', 'Sipariş #78 teslim edildi', 0, '2025-01-31 06:36:13'),
(176, 78, 'order_status', 'Sipariş #78 teslim edildi', 0, '2025-01-31 06:36:13');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','preparing','ready','delivered','completed','cancelled') DEFAULT 'pending',
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
(78, NULL, 1, 'completed', NULL, 300.00, NULL, NULL, '2025-01-31 06:34:49', '2025-01-31 06:36:41', 48, '2025-01-31 06:36:41');

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
(287, 78, 1, 1, 30.00, '2025-01-31 06:34:49'),
(288, 78, 3, 1, 270.00, '2025-01-31 06:34:49');

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
(1, 0, '6', '2025-01-03 07:04:53', '2025-01-28 18:25:05');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `payment_method` enum('cash','pos') NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `payment_note` text DEFAULT NULL,
  `status` enum('completed','cancelled') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `discount_type` enum('percent','amount') DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `payments`
--

INSERT INTO `payments` (`id`, `table_id`, `payment_method`, `total_amount`, `subtotal`, `paid_amount`, `payment_note`, `status`, `created_at`, `discount_type`, `discount_value`, `discount_amount`) VALUES
(48, 1, 'cash', 240.00, 300.00, 0.00, NULL, 'completed', '2025-01-31 06:36:41', 'percent', 20.00, 60.00);

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
  `view_count` int(11) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `status`, `created_at`, `special`, `view_count`, `sort_order`) VALUES
(1, 6, 'Adana Kebapp', 'asdasd asasdasdasdasdasdsd', 30.00, '6769785d70e7b.jpeg', 1, '2025-01-01 12:23:44', 0, 0, 3),
(3, 6, 'Kuş Başı Kebap', 'asdasd', 270.00, '677021225bf50.jpg', 1, '2025-01-01 12:24:49', 0, 0, 2),
(5, 9, 'KOLA', 'ASDAS DASD ASD AS', 60.00, '676a97b066ef6.jpg', 1, '2025-01-01 12:28:02', 0, 0, 1),
(6, 8, 'Çorba', 'asdasdasdas', 100.00, '6769785d70e7b.jpeg', 1, '2025-01-01 12:28:45', 0, 0, 1),
(19, 6, 'Adana Şiş', 'test ürün', 20.00, '676a6742c6cac.png', 0, '2025-01-28 10:35:19', 0, 0, 4),
(20, 6, 'Adana Şiş', 'asdasdasdasd', 20.00, '676a6742c6cac.png', 0, '2025-01-28 10:35:51', 0, 0, 5),
(24, 6, 'test hızlı ürün 2', '', 2.00, '6769785d70e7b.jpeg', 1, '2025-01-28 11:43:36', 0, 0, 1),
(25, 6, 'Tavuk Şiş', 'test', 240.00, '6769785d70e7b.jpeg', 1, '2025-01-28 12:23:03', 0, 0, 0),
(30, 8, 'test', 'asdasdasd', 20.00, '6769785d70e7b.jpeg', 1, '2025-01-28 12:57:50', 0, 0, 0),
(31, 18, 'test22', 'asdasdasdasd', 20.00, '676a97b066ef6.jpg', 1, '2025-01-28 12:58:17', 0, 0, 0);

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
(3, 'Mutfak', 'kitchen', 'Mutfak personeli', '{\"dashboard\":{\"view\":true},\"categories\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false,\"kitchen_only\":false},\"products\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"orders\":{\"view\":true},\"tables\":{\"view\":false,\"manage\":false,\"payment\":false,\"sales\":false,\"add_order\":false,\"edit_order\":false,\"delete_order\":false,\"save_order\":false},\"kitchen\":{\"view\":true,\"manage\":true},\"users\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"roles\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"settings\":{\"view\":false,\"edit\":false},\"reports\":{\"view\":false},\"payments\":{\"view\":false,\"create\":false,\"cancel\":false},\"reservations\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false,\"approve\":false,\"reject\":false,\"settings\":false},\"order_settings\":{\"view\":false,\"edit\":false,\"payment_methods\":false,\"discount_rules\":false,\"tax_settings\":false,\"printer_settings\":false}}', 1, '2025-01-04 07:46:12', '2025-01-30 08:06:18'),
(4, 'Garson', 'waiter', 'Servis personeli', '{\"dashboard\":{\"view\":true},\"categories\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true,\"kitchen_only\":false},\"products\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true},\"orders\":{\"view\":true,\"status\":true,\"view_payments\":true},\"tables\":{\"view\":true,\"manage\":false,\"payment\":true,\"sales\":true,\"add_order\":true,\"edit_order\":true,\"delete_order\":true,\"save_order\":true,\"view_payments\":false},\"kitchen\":{\"view\":true,\"manage\":true},\"users\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true},\"roles\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true},\"settings\":{\"view\":true,\"edit\":true},\"reports\":{\"view\":true},\"payments\":{\"view\":true,\"manage\":false,\"cancel\":true,\"reorder\":true,\"view_completed\":false},\"reservations\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true,\"approve\":true,\"reject\":true,\"settings\":false},\"order_settings\":{\"view\":true,\"edit\":true,\"payment_methods\":false,\"discount_rules\":false,\"tax_settings\":false,\"printer_settings\":false}}', 1, '2025-01-04 07:46:12', '2025-01-30 13:06:11');

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
(1, 'Masa 1', 4, NULL, '', '2025-01-01 12:14:45', NULL),
(2, 'Masa 2', 4, NULL, '', '2025-01-01 12:14:50', NULL),
(3, 'Masa 3', 4, NULL, '', '2025-01-01 12:14:55', NULL),
(4, 'Masa 4', 4, NULL, '', '2025-01-01 12:15:00', NULL),
(5, 'Masa 5', 4, NULL, 'active', '2025-01-01 12:15:06', NULL),
(6, 'Masa 6', 4, NULL, '', '2025-01-02 12:45:45', NULL),
(8, '7', 4, NULL, '', '2025-01-04 15:42:05', NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- Tablo için AUTO_INCREMENT değeri `order_codes`
--
ALTER TABLE `order_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=289;

--
-- Tablo için AUTO_INCREMENT değeri `order_settings`
--
ALTER TABLE `order_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- Tablo için AUTO_INCREMENT değeri `pre_orders`
--
ALTER TABLE `pre_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Tablo için AUTO_INCREMENT değeri `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `reservation_orders`
--
ALTER TABLE `reservation_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
