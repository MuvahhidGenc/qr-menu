-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 26 Ara 2024, 19:31:46
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `status`, `created_at`, `image`) VALUES
(1, 'Yemek', 1, '2024-12-24 07:06:30', '676a6e1cc23fc.jpg'),
(2, 'İçeçek', 1, '2024-12-24 07:43:52', '676a6e27408af.jpg'),
(3, 'Tatlı', 1, '2024-12-24 07:44:07', '676a6e82563e2.jpg');

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

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','preparing','ready','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `orders`
--

INSERT INTO `orders` (`id`, `table_id`, `status`, `total_amount`, `note`, `created_at`, `updated_at`) VALUES
(3, 1, 'pending', 2249.97, NULL, '2024-12-26 11:19:04', '2024-12-26 11:19:04'),
(4, 1, 'pending', 2249.97, NULL, '2024-12-26 11:19:15', '2024-12-26 11:19:15'),
(5, 1, 'pending', 2249.97, NULL, '2024-12-26 11:20:45', '2024-12-26 11:20:45'),
(6, 1, 'pending', 2249.97, NULL, '2024-12-26 11:23:06', '2024-12-26 11:23:06'),
(7, 1, 'pending', 1499.98, NULL, '2024-12-26 11:32:43', '2024-12-26 11:32:43'),
(8, 1, '', 3199.96, NULL, '2024-12-26 11:32:59', '2024-12-26 11:48:45'),
(9, 1, 'pending', 5549.93, NULL, '2024-12-26 15:34:41', '2024-12-26 15:34:41'),
(10, 1, '', 749.99, NULL, '2024-12-26 15:35:24', '2024-12-26 15:35:31'),
(11, 1, 'pending', 3199.96, NULL, '2024-12-26 15:37:35', '2024-12-26 15:37:35'),
(12, 1, '', 1599.98, NULL, '2024-12-26 16:29:27', '2024-12-26 16:29:57'),
(13, 1, 'pending', 3749.95, NULL, '2024-12-26 16:41:30', '2024-12-26 16:41:30'),
(14, 1, 'pending', 6799.92, NULL, '2024-12-26 16:42:29', '2024-12-26 16:42:29'),
(15, 1, 'cancelled', 4799.94, NULL, '2024-12-26 16:50:08', '2024-12-26 16:50:23'),
(16, 1, 'pending', 99.98, NULL, '2024-12-26 16:51:22', '2024-12-26 16:51:22'),
(17, 1, 'pending', 3199.96, NULL, '2024-12-26 16:57:23', '2024-12-26 16:57:23'),
(18, 5, 'pending', 49.99, NULL, '2024-12-26 17:03:47', '2024-12-26 17:03:47'),
(19, 5, 'delivered', 49.99, NULL, '2024-12-26 17:04:47', '2024-12-26 18:09:17'),
(20, 5, 'pending', 349.93, NULL, '2024-12-26 18:11:52', '2024-12-26 18:11:52');

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
(23, 20, 4, 3, 49.99, '2024-12-26 18:11:52');

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
  `qr_code` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `tables`
--

INSERT INTO `tables` (`id`, `table_no`, `qr_code`, `status`, `created_at`) VALUES
(1, 'Masa 1', NULL, 1, '2024-12-26 11:16:43'),
(2, 'Masa 2', NULL, 1, '2024-12-26 11:16:43'),
(4, 'Masa 4', NULL, 1, '2024-12-26 11:16:43'),
(5, 'Masa 5', NULL, 1, '2024-12-26 11:16:43'),
(6, 'Masa 3', NULL, 1, '2024-12-26 16:27:52');

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
  ADD KEY `table_id` (`table_id`);

--
-- Tablo için indeksler `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Tablo için indeksler `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_status` (`category_id`,`status`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`);

--
-- Tablo kısıtlamaları `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Tablo kısıtlamaları `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
