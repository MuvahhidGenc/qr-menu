-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 02 Oca 2025, 11:33:20
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
(1, 1, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 1, '2025-01-01 13:02:20'),
(2, 1, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 1, '2025-01-01 13:02:47'),
(3, 2, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 0, '2025-01-01 13:21:10'),
(4, 3, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 0, '2025-01-01 13:34:59'),
(5, 3, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 0, '2025-01-01 13:35:52'),
(6, 3, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 0, '2025-01-01 13:36:17'),
(7, 4, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 0, '2025-01-01 13:37:01'),
(8, 5, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 0, '2025-01-01 13:39:40'),
(9, 5, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 0, '2025-01-01 13:40:49'),
(10, 6, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 0, '2025-01-01 13:41:24'),
(11, 6, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 0, '2025-01-01 13:41:44'),
(12, 6, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 0, '2025-01-01 13:41:56'),
(13, 7, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 0, '2025-01-01 13:43:18'),
(14, 6, 'order_updated', 'Masa Masa 1\'a yeni ürünler eklendi', 0, '2025-01-01 13:46:40'),
(15, 8, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 0, '2025-01-01 15:06:40');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','preparing','ready','delivered','completed') DEFAULT 'pending',
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
(1, 1, 'completed', 500.00, NULL, NULL, '2025-01-01 13:02:20', '2025-01-01 13:19:27', 1, '2025-01-01 13:19:27'),
(2, 1, 'completed', 250.00, NULL, NULL, '2025-01-01 13:21:10', '2025-01-01 13:41:04', 2, '2025-01-01 13:41:04'),
(3, 1, 'completed', 500.00, NULL, NULL, '2025-01-01 13:34:59', '2025-01-01 13:41:04', 2, '2025-01-01 13:41:04'),
(4, 1, 'completed', 100.00, NULL, NULL, '2025-01-01 13:37:01', '2025-01-01 13:41:04', 2, '2025-01-01 13:41:04'),
(5, 1, 'completed', 650.00, NULL, NULL, '2025-01-01 13:39:40', '2025-01-01 13:41:04', 2, '2025-01-01 13:41:04'),
(6, 1, 'delivered', 710.00, NULL, NULL, '2025-01-01 13:41:24', '2025-01-01 14:17:28', NULL, NULL),
(7, 1, 'preparing', 100.00, NULL, NULL, '2025-01-01 13:43:18', '2025-01-01 14:17:37', NULL, NULL),
(8, 1, 'pending', 750.00, NULL, NULL, '2025-01-01 15:06:40', '2025-01-01 15:06:40', NULL, NULL);

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
(157, 1, 1, 1, 250.00, '2025-01-01 13:02:20'),
(158, 1, 7, 1, 250.00, '2025-01-01 13:02:46'),
(159, 2, 1, 1, 250.00, '2025-01-01 13:21:10'),
(160, 3, 8, 1, 150.00, '2025-01-01 13:34:59'),
(161, 3, 1, 1, 250.00, '2025-01-01 13:35:52'),
(162, 3, 6, 1, 100.00, '2025-01-01 13:36:17'),
(163, 4, 6, 1, 100.00, '2025-01-01 13:37:01'),
(164, 5, 1, 1, 250.00, '2025-01-01 13:39:40'),
(165, 5, 6, 4, 100.00, '2025-01-01 13:40:49'),
(166, 6, 1, 1, 250.00, '2025-01-01 13:41:24'),
(167, 6, 8, 1, 150.00, '2025-01-01 13:41:44'),
(168, 6, 5, 1, 60.00, '2025-01-01 13:41:56'),
(169, 7, 6, 1, 100.00, '2025-01-01 13:43:18'),
(170, 6, 3, 1, 250.00, '2025-01-01 13:46:40'),
(171, 8, 3, 1, 250.00, '2025-01-01 15:06:40'),
(172, 8, 1, 1, 250.00, '2025-01-01 15:06:40'),
(173, 8, 7, 1, 250.00, '2025-01-01 15:06:40');

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
(1, 1, 'pos', 500.00, 500.00, NULL, '2025-01-01 13:19:27'),
(2, 1, 'pos', 1500.00, 1500.00, NULL, '2025-01-01 13:41:04');

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
('theme_color', '#ff4747', '2025-01-01 15:05:36', NULL);

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
(1, 'Masa 1', 4, NULL, 'active', '2025-01-01 12:14:45'),
(2, 'Masa 2', 4, NULL, 'active', '2025-01-01 12:14:50'),
(3, 'Masa 3', 4, NULL, 'active', '2025-01-01 12:14:55'),
(4, 'Masa 4', 4, NULL, 'active', '2025-01-01 12:15:00'),
(5, 'Masa 5', 4, NULL, 'active', '2025-01-01 12:15:06');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
