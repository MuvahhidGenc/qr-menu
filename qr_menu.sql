-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 29 Eki 2025, 21:19:07
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
  `salary` decimal(10,2) DEFAULT NULL,
  `bonus_percentage` decimal(5,2) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `admins`
--

INSERT INTO `admins` (`id`, `username`, `name`, `phone`, `password`, `email`, `created_at`, `role_id`, `salary`, `bonus_percentage`, `status`) VALUES
(1, 'admin', 'Sistem Yöneticisi', '', '$2y$10$be4HiFcfX83QINQojTUAoOghTD3jQi/3zElTzAOX/0pYfnt/we8xW', 'admin@example.com', '2024-12-24 03:40:16', 1, NULL, NULL, 1),
(4, 'tmutfak', 'mutfak', '', '$2y$10$8Fl0Ru482zYVTeoD72OMkeE6QNFbp8N1rSN9zlhl6HDoV5RFhhBby', '', '2025-01-30 04:36:44', 3, NULL, NULL, 1),
(5, 'garson', 'garson', '', '$2y$10$1hMc/UNVOE0QNXuiV38mm.pOcmULNHMUskRSF1eIqv7sU/mOCUVeW', '', '2025-01-30 04:37:08', 4, 12000.00, 2.00, 1),
(6, 'yonetici', 'Queen yönetici', '', '$2y$10$KqkFOv4L.iWB/fcwCSVupOqUYKATG8bC7GrBqVmtzxzBotAzJO8Ny', '', '2025-01-30 04:37:39', 2, NULL, NULL, 1),
(8, 'testr', 'test', '', '$2y$10$ud8F/SdfpRzh/Z7UpbAu1.sJhRaF3Q1arHTWfAV6F78t9kQGb6eVK', '', '2025-01-30 05:01:18', 5, 25000.00, 3.00, 1);

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
(6, 'DÜNYA KAHVELERİ', 1, 3, '2025-01-01 09:16:22', '68a9f8d9a173a.jpg'),
(8, 'SICAK İÇECEKLER', 1, 1, '2025-01-01 09:19:28', '68aa0270c90a9.jpg'),
(9, 'SOĞUK KAHVELER', 1, 0, '2025-01-01 09:20:01', '68a9f7f65e1cb.jpg'),
(18, 'SOĞUK İÇECEKLER', 1, 2, '2025-01-28 09:51:16', '68a9f85b3ec8e.jpg');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `customer_themes`
--

CREATE TABLE `customer_themes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `concept` enum('modern','classic','elegant','casual','luxury','minimal','vintage','corporate') DEFAULT 'modern',
  `primary_color` varchar(7) NOT NULL DEFAULT '#e74c3c',
  `secondary_color` varchar(7) NOT NULL DEFAULT '#c0392b',
  `accent_color` varchar(7) NOT NULL DEFAULT '#f39c12',
  `background_color` varchar(7) NOT NULL DEFAULT '#f8f9fa',
  `text_color` varchar(7) NOT NULL DEFAULT '#2c3e50',
  `font_family` varchar(50) DEFAULT 'Poppins',
  `category_style` enum('grid','masonry','carousel','list','grid-2col','list-2col') DEFAULT 'grid',
  `product_layout` enum('grid','list','grid-2col','list-2col') DEFAULT 'grid',
  `header_style` enum('modern','classic','minimal','full') DEFAULT 'modern',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `customer_themes`
--

INSERT INTO `customer_themes` (`id`, `name`, `concept`, `primary_color`, `secondary_color`, `accent_color`, `background_color`, `text_color`, `font_family`, `category_style`, `product_layout`, `header_style`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Modern Kırmızı', 'modern', '#e74c3c', '#c0392b', '#ff5252', '#f8f9fa', '#2c3e50', 'Poppins', 'list', 'grid-2col', 'modern', 'Modern ve dinamik kırmızı tema', 1, '2025-08-24 14:09:12', '2025-10-29 18:59:26'),
(2, 'Zarif Mavi', 'elegant', '#3498db', '#2980b9', '#1abc9c', '#ecf0f1', '#2c3e50', 'Playfair Display', 'grid', 'grid-2col', 'classic', 'Zarif ve profesyonel mavi tema', 0, '2025-08-24 14:09:12', '2025-08-24 14:09:12'),
(3, 'Lüks Altın', 'luxury', '#f1c40f', '#f39c12', '#e67e22', '#2c3e50', '#f1c40f', 'Montserrat', 'masonry', 'list', 'full', 'Lüks altın rengi tema', 0, '2025-08-24 14:09:12', '2025-10-29 06:36:28'),
(4, 'Minimal Gri', 'minimal', '#2c3e50', '#7f8c8d', '#34495e', '#ffffff', '#2c3e50', 'Roboto', 'list', 'list-2col', 'minimal', 'Temiz ve minimal gri tema', 0, '2025-08-24 14:09:12', '2025-10-29 06:37:13'),
(5, 'Vintage Kahve', 'vintage', '#8d6e63', '#6d4c41', '#d7ccc8', '#efebe9', '#3e2723', 'Open Sans', 'grid-2col', 'list', 'classic', 'Nostaljik kahve rengi tema', 0, '2025-08-24 14:09:12', '2025-10-29 07:34:50'),
(6, 'Kurumsal Lacivert', 'modern', '#3a94ee', '#4da6ff', '#3498db', '#ffffff', '#5ba9b3', 'Playfair Display', 'grid-2col', 'list', 'modern', 'Profesyonel kurumsal tema', 0, '2025-08-24 14:09:12', '2025-10-29 06:35:14'),
(7, 'mor tema', 'modern', '#ff7ac1', '#feb4db', '#fbcbf2', '#f8f9fa', '#ffffff', 'Poppins', 'list', 'list', 'modern', '', 0, '2025-10-29 06:54:01', '2025-10-29 07:37:20');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `staff_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT '#3498db',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `color`, `created_at`, `updated_at`) VALUES
(1, 'Kira', '#3498db', '2025-02-04 09:58:31', '2025-02-04 09:58:31'),
(2, 'Elektrik', '#e74c3c', '2025-02-04 09:58:31', '2025-02-04 09:58:31'),
(3, 'Su', '#2ecc71', '2025-02-04 09:58:31', '2025-02-04 09:58:31'),
(4, 'Doğalgaz', '#f1c40f', '2025-02-04 09:58:31', '2025-02-04 09:58:31'),
(5, 'İnternet', '#9b59b6', '2025-02-04 09:58:31', '2025-02-04 09:58:31'),
(6, 'Personel Maaşları', '#1abc9c', '2025-02-04 09:58:31', '2025-02-04 09:58:31'),
(7, 'Malzeme Alımı', '#e67e22', '2025-02-04 09:58:31', '2025-02-04 09:58:31'),
(8, 'Bakım Onarım', '#34495e', '2025-02-04 09:58:31', '2025-02-04 09:58:31'),
(9, 'Vergi', '#c0392b', '2025-02-04 09:58:31', '2025-02-04 09:58:31'),
(10, 'Diğer', '#7f8c8d', '2025-02-04 09:58:31', '2025-02-04 09:58:31');

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
(24, NULL, 'payment_cancelled', 'Masa Masa 1\'ın ödemesi iptal edildi. Neden: test', 0, '2025-10-26 13:05:37'),
(25, NULL, 'payment_cancelled', 'Masa Masa 1\'ın ödemesi iptal edildi. Neden: test', 0, '2025-10-26 13:06:04'),
(26, 36, 'new_order', 'Masa Masa 1\'a iptal edilmiş siparişler yeniden eklendi!', 0, '2025-10-26 13:06:30'),
(27, NULL, 'payment_cancelled', 'Masa Masa 1\'ın ödemesi iptal edildi. Neden: test', 0, '2025-10-26 13:07:12'),
(28, 38, 'new_order', 'Masa Masa 1\'dan yeni sipariş geldi!', 0, '2025-10-29 08:32:02'),
(29, 39, 'order_status', 'Sipariş #39 hazır', 0, '2025-10-29 18:16:10'),
(30, 39, 'order_status', 'Sipariş #39 hazırlanıyor', 0, '2025-10-29 18:16:13'),
(31, 38, 'order_status', 'Sipariş #38 teslim edildi', 0, '2025-10-29 18:16:18'),
(32, 39, 'order_status', 'Sipariş #39 hazır', 0, '2025-10-29 18:17:01');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','preparing','ready','delivered','completed','cancelled','partial_paid') DEFAULT 'pending',
  `order_code` varchar(6) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_id` int(11) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `orders`
--

INSERT INTO `orders` (`id`, `reservation_id`, `table_id`, `status`, `order_code`, `total_amount`, `notes`, `note`, `created_at`, `updated_at`, `payment_id`, `completed_at`, `cancelled_at`) VALUES
(35, NULL, 1, 'cancelled', NULL, 260.00, '', NULL, '2025-10-26 13:02:44', '2025-10-26 13:06:04', 62, '2025-10-26 13:04:03', NULL),
(37, NULL, 2, 'completed', NULL, 260.00, '', NULL, '2025-10-26 13:30:16', '2025-10-26 13:31:04', 65, '2025-10-26 13:31:04', NULL),
(38, NULL, 1, 'completed', NULL, 120.00, NULL, NULL, '2025-10-29 08:32:02', '2025-10-29 18:16:31', 74, '2025-10-29 18:16:31', NULL),
(39, NULL, 13, 'completed', NULL, 160.00, '', NULL, '2025-10-29 08:53:24', '2025-10-29 18:22:43', 77, '2025-10-29 18:22:43', NULL),
(40, NULL, 14, 'completed', NULL, 160.00, '', NULL, '2025-10-29 18:08:33', '2025-10-29 18:08:36', 73, '2025-10-29 18:08:36', NULL);

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
(1, '794027', 1, '2025-10-29 08:31:56', '2025-10-30 08:31:56');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL COMMENT 'Ödeme ID (kısmi ödemeler için)',
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `payment_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(90, 35, 62, 1, 1, 93.75, '2025-10-26 13:02:44'),
(91, 35, 62, 3, 1, 56.25, '2025-10-26 13:02:44'),
(92, 35, 60, 1, 1, 100.00, '2025-10-26 13:03:02'),
(96, 37, 65, 1, 1, 62.50, '2025-10-26 13:30:16'),
(97, 37, 65, 3, 1, 37.50, '2025-10-26 13:30:16'),
(98, 37, 63, 1, 1, 100.00, '2025-10-26 13:30:34'),
(99, 38, 74, 42, 1, 60.00, '2025-10-29 08:32:02'),
(101, 39, 77, 3, 1, 30.00, '2025-10-29 08:53:24'),
(102, 0, 66, 37, 1, 90.00, '2025-10-29 11:37:19'),
(103, 0, 67, 37, 1, 90.00, '2025-10-29 11:37:40'),
(104, 0, 68, 38, 1, 20.00, '2025-10-29 11:39:59'),
(105, 0, 69, 37, 1, 90.00, '2025-10-29 11:47:24'),
(106, 0, 70, 38, 1, 20.00, '2025-10-29 12:08:17'),
(107, 0, 70, 25, 1, 90.00, '2025-10-29 12:08:17'),
(108, 0, 71, 43, 4, 120.00, '2025-10-29 16:45:36'),
(109, 0, 71, 25, 1, 90.00, '2025-10-29 16:45:36'),
(110, 0, 71, 38, 1, 20.00, '2025-10-29 16:45:36'),
(111, 0, 71, 6, 1, 30.00, '2025-10-29 16:45:36'),
(112, 40, 73, 1, 1, 100.00, '2025-10-29 18:08:33'),
(113, 40, 73, 3, 1, 60.00, '2025-10-29 18:08:33'),
(114, 39, 75, 1, 1, 100.00, '2025-10-29 18:22:18'),
(115, 0, 78, 45, 8, 110.00, '2025-10-29 18:48:41'),
(116, 0, 79, 43, 1, 120.00, '2025-10-29 18:51:20'),
(117, 0, 79, 41, 1, 115.00, '2025-10-29 18:51:20'),
(118, 0, 79, 42, 2, 120.00, '2025-10-29 18:51:20');

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
(1, 1, '6', '2025-01-03 04:04:53', '2025-09-27 04:44:54');

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
  `is_partial` tinyint(1) DEFAULT 0 COMMENT 'Kısmi ödeme mi?',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `discount_type` enum('percent','amount') DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `payments`
--

INSERT INTO `payments` (`id`, `table_id`, `payment_method`, `total_amount`, `subtotal`, `paid_amount`, `payment_note`, `status`, `is_partial`, `created_at`, `discount_type`, `discount_value`, `discount_amount`) VALUES
(60, 1, 'cash', 100.00, 260.00, 100.00, '{\"type\":\"product\",\"items\":[{\"id\":\"90\",\"quantity\":1,\"price\":100}],\"amount\":100}\nİptal Nedeni: test', 'cancelled', 0, '2025-10-26 13:03:02', 'percent', 0.00, 0.00),
(61, 1, 'cash', 10.00, 160.00, 10.00, '{\"type\":\"amount\",\"items\":[],\"amount\":10}\nİptal Nedeni: test', 'cancelled', 0, '2025-10-26 13:03:45', 'percent', 0.00, 0.00),
(62, 1, 'pos', 150.00, 150.00, 150.00, '\nİptal Nedeni: test', 'cancelled', 0, '2025-10-26 13:04:03', 'percent', 0.00, 0.00),
(63, 2, 'cash', 100.00, 260.00, 100.00, '{\"type\":\"product\",\"items\":[{\"id\":\"96\",\"quantity\":1,\"price\":100}],\"amount\":100}', 'completed', 0, '2025-10-26 13:30:34', 'percent', 0.00, 0.00),
(64, 2, 'cash', 60.00, 160.00, 60.00, '{\"type\":\"amount\",\"items\":[],\"amount\":60}', 'completed', 0, '2025-10-26 13:30:55', 'percent', 0.00, 0.00),
(65, 2, 'cash', 100.00, 100.00, 100.00, NULL, 'completed', 0, '2025-10-26 13:31:04', 'percent', 0.00, 0.00),
(66, NULL, 'cash', 90.00, 90.00, 90.00, 'POS Satış', 'completed', 0, '2025-10-29 11:37:19', NULL, NULL, 0.00),
(67, NULL, 'cash', 90.00, 90.00, 90.00, 'POS Satış', 'completed', 0, '2025-10-29 11:37:40', NULL, NULL, 0.00),
(68, NULL, 'cash', 20.00, 20.00, 20.00, 'POS Satış', 'completed', 0, '2025-10-29 11:39:59', NULL, NULL, 0.00),
(69, NULL, 'cash', 90.00, 90.00, 90.00, 'POS Satış', 'completed', 0, '2025-10-29 11:47:24', NULL, NULL, 0.00),
(70, NULL, 'cash', 110.00, 110.00, 110.00, 'POS Satış - Kasa 2', 'completed', 0, '2025-10-29 12:08:17', NULL, NULL, 0.00),
(71, NULL, '', 620.00, 620.00, 620.00, 'POS Satış - Kasa 1', 'completed', 0, '2025-10-29 16:45:36', NULL, NULL, 0.00),
(72, 1, 'cash', 60.00, 120.00, 60.00, '{\"type\":\"amount\",\"items\":[],\"amount\":60}', 'completed', 0, '2025-10-29 18:08:21', 'percent', 0.00, 0.00),
(73, 14, 'cash', 160.00, 160.00, 160.00, NULL, 'completed', 0, '2025-10-29 18:08:36', 'percent', 0.00, 0.00),
(74, 1, 'cash', 60.00, 60.00, 60.00, NULL, 'completed', 0, '2025-10-29 18:16:31', 'percent', 0.00, 0.00),
(75, 13, 'cash', 100.00, 160.00, 100.00, '{\"type\":\"product\",\"items\":[{\"id\":\"100\",\"quantity\":1,\"price\":100}],\"amount\":100}', 'completed', 0, '2025-10-29 18:22:18', 'percent', 0.00, 0.00),
(76, 13, 'cash', 30.00, 60.00, 30.00, '{\"type\":\"amount\",\"items\":[],\"amount\":30}', 'completed', 0, '2025-10-29 18:22:37', 'percent', 0.00, 0.00),
(77, 13, 'cash', 30.00, 30.00, 30.00, NULL, 'completed', 0, '2025-10-29 18:22:43', 'percent', 0.00, 0.00),
(78, NULL, 'cash', 880.00, 880.00, 880.00, 'POS Satış - Kasa 1', 'completed', 0, '2025-10-29 18:48:41', NULL, NULL, 0.00),
(79, NULL, 'cash', 475.00, 475.00, 475.00, 'POS Satış - Kasa 1', 'completed', 0, '2025-10-29 18:51:20', NULL, NULL, 0.00);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pos_favorites`
--

CREATE TABLE `pos_favorites` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 1,
  `last_used` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `pos_favorites`
--

INSERT INTO `pos_favorites` (`id`, `product_id`, `user_id`, `usage_count`, `last_used`, `created_at`) VALUES
(6, 38, 1, 1, '2025-10-29 18:48:13', '2025-10-29 18:48:13'),
(7, 40, 1, 1, '2025-10-29 18:48:24', '2025-10-29 18:48:24'),
(8, 45, 1, 1, '2025-10-29 18:48:28', '2025-10-29 18:48:28');

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
  `sort_order` int(11) DEFAULT 0,
  `barcode` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 9999
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `status`, `created_at`, `special`, `view_count`, `sort_order`, `barcode`, `stock`) VALUES
(1, 6, 'Sahlep', '', 100.00, '68a9fb1785d63.jpg', 1, '2025-01-01 09:23:44', 0, 0, 3, NULL, 9999),
(3, 6, 'Espresso Machiato', '', 60.00, '68a9f8d9a173a.jpg', 1, '2025-01-01 09:24:49', 0, 0, 1, NULL, 9999),
(6, 8, 'FİNCAN ÇAY', '', 30.00, '68aa02537e47f.jpg', 1, '2025-01-01 09:28:45', 0, 0, 1, NULL, 9998),
(24, 6, 'Espresso', '', 70.00, '68a9fad0bd7fa.jpg', 1, '2025-01-28 08:43:36', 0, 0, 0, NULL, 9999),
(25, 6, 'Double Espresso', '', 90.00, '68a9f9bb62df0.jpg', 1, '2025-01-28 09:23:03', 0, 0, 2, NULL, 9997),
(30, 8, 'SICAK ÇİKOLATA', '', 100.00, '68aa0232692d8.jpg', 1, '2025-01-28 09:57:50', 0, 0, 0, NULL, 9999),
(31, 18, 'SARIYER SİYAH COLA', '', 70.00, '68a9fe276dfea.jpg', 1, '2025-01-28 09:58:17', 0, 0, 0, NULL, 9999),
(34, 8, 'DİBEK KAHVESİ', '', 80.00, '68a9fad0bd7fa.jpg', 1, '2025-04-27 11:17:34', 0, 0, 0, NULL, 9999),
(37, 8, 'Ballı süt', '', 90.00, '68aa02bbea2ea.jpg', 1, '2025-04-27 11:19:41', 0, 0, 0, NULL, 9996),
(38, 8, 'Çay', '', 20.00, '68aa0270c90a9.jpg', 1, '2025-04-27 11:20:10', 0, 0, 0, NULL, 9996),
(40, 9, 'İce Filtre Kahve', '', 110.00, '68a9ffafa9509.jpg', 1, '2025-04-27 11:24:08', 0, 0, 0, NULL, 9999),
(41, 9, 'İce Latte', '', 115.00, '68aa008829f4b.jpg', 1, '2025-04-27 11:24:22', 0, 0, 0, NULL, 9998),
(42, 9, 'İce Mocha', '', 120.00, '68aa00cac957b.jpg', 1, '2025-04-27 11:24:39', 0, 0, 0, NULL, 9997),
(43, 9, 'İce American', '', 120.00, '68a9f85b3ec8e.jpg', 1, '2025-04-27 11:25:22', 0, 0, 0, '3498', 45),
(44, 9, 'İce Wine Chocolate Mocha', '', 120.00, '68aa008829f4b.jpg', 1, '2025-04-27 11:25:58', 0, 0, 0, NULL, 9999),
(45, 9, 'İce Flat White', '', 110.00, '68aa0009e84d5.jpg', 1, '2025-04-27 11:26:18', 0, 0, 0, NULL, 9991),
(46, 18, 'SARIYER SARI COLA', '', 70.00, '68a9fe188a6c6.jpg', 1, '2025-04-27 11:59:59', 0, 0, 0, NULL, 9999),
(47, 18, 'SARIYER MANDALİNA', '', 70.00, '68a9fdf9adf37.jpg', 1, '2025-04-27 12:00:27', 0, 0, 0, NULL, 9999),
(48, 18, 'SARIYER GAZOZ', '', 70.00, '68a9fe0648599.jpg', 1, '2025-04-27 12:00:54', 0, 0, 0, NULL, 9999);

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
-- Tablo için tablo yapısı `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp(),
  `blocked_until` timestamp NULL DEFAULT NULL
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
(1, 'Süper Admin', 'super-admin', 'Tam yetkili sistem yöneticisi', '{\"dashboard\":{\"view\":true},\"categories\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true,\"kitchen_only\":true},\"products\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true},\"orders\":{\"view\":true,\"status\":true,\"view_payments\":true},\"tables\":{\"view\":true,\"manage\":true,\"payment\":true,\"sales\":true,\"add_order\":true,\"edit_order\":true,\"delete_order\":true,\"save_order\":true,\"view_payments\":true},\"kitchen\":{\"view\":true,\"manage\":true},\"users\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true},\"roles\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true},\"settings\":{\"view\":true,\"edit\":true},\"reports\":{\"view\":true},\"payments\":{\"view\":true,\"manage\":true,\"cancel\":true,\"reorder\":true,\"view_completed\":true},\"reservations\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true,\"approve\":true,\"reject\":true,\"settings\":true},\"order_settings\":{\"view\":true,\"edit\":true,\"payment_methods\":true,\"discount_rules\":true,\"tax_settings\":true,\"printer_settings\":true}}', 1, '2025-01-04 04:46:12', '2025-09-27 19:55:51'),
(2, 'Yönetici', 'admin', 'Genel sistem yöneticisi', '{\"dashboard\":{\"view\":true},\"categories\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true,\"kitchen_only\":true},\"products\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true},\"orders\":{\"view\":false,\"status\":false,\"view_payments\":false},\"tables\":{\"view\":true,\"manage\":true,\"payment\":true,\"sales\":true,\"add_order\":true,\"edit_order\":true,\"delete_order\":true,\"save_order\":true,\"view_payments\":true},\"kitchen\":{\"view\":false,\"manage\":false},\"users\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true},\"roles\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"settings\":{\"view\":true,\"edit\":true},\"reports\":{\"view\":true},\"payments\":{\"view\":true,\"manage\":true,\"cancel\":true,\"reorder\":true,\"view_completed\":true},\"reservations\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true,\"approve\":true,\"reject\":true,\"settings\":true},\"order_settings\":{\"view\":false,\"edit\":false,\"payment_methods\":false,\"discount_rules\":false,\"tax_settings\":false,\"printer_settings\":false}}', 1, '2025-01-04 04:46:12', '2025-09-27 07:40:10'),
(3, 'Mutfak', 'kitchen', 'Mutfak personeli', '{\"dashboard\":{\"view\":true},\"categories\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false,\"kitchen_only\":false},\"products\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"orders\":{\"view\":true},\"tables\":{\"view\":false,\"manage\":false,\"payment\":false,\"sales\":false,\"add_order\":false,\"edit_order\":false,\"delete_order\":false,\"save_order\":false},\"kitchen\":{\"view\":true,\"manage\":true},\"users\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"roles\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"settings\":{\"view\":false,\"edit\":false},\"reports\":{\"view\":false},\"payments\":{\"view\":false,\"create\":false,\"cancel\":false},\"reservations\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false,\"approve\":false,\"reject\":false,\"settings\":false},\"order_settings\":{\"view\":false,\"edit\":false,\"payment_methods\":false,\"discount_rules\":false,\"tax_settings\":false,\"printer_settings\":false}}', 1, '2025-01-04 04:46:12', '2025-01-30 05:06:18'),
(4, 'Garson', 'waiter', 'Servis personeli', '{\"dashboard\":{\"view\":true},\"categories\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true,\"kitchen_only\":false},\"products\":{\"view\":true,\"add\":true,\"edit\":true,\"delete\":true},\"orders\":{\"view\":false,\"status\":false,\"view_payments\":false},\"tables\":{\"view\":true,\"manage\":false,\"payment\":false,\"sales\":true,\"add_order\":true,\"edit_order\":true,\"delete_order\":true,\"save_order\":true,\"view_payments\":false},\"kitchen\":{\"view\":false,\"manage\":false},\"users\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"roles\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"settings\":{\"view\":false,\"edit\":false},\"reports\":{\"view\":false},\"payments\":{\"view\":false,\"manage\":false,\"cancel\":false,\"reorder\":true,\"view_completed\":false},\"reservations\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false,\"approve\":false,\"reject\":false,\"settings\":false},\"order_settings\":{\"view\":false,\"edit\":false,\"payment_methods\":false,\"discount_rules\":false,\"tax_settings\":false,\"printer_settings\":false}}', 1, '2025-01-04 04:46:12', '2025-09-28 12:33:49'),
(5, 'Kasa', 'kasa', '', '{\"dashboard\":{\"view\":true},\"categories\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false,\"kitchen_only\":false},\"products\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"orders\":{\"view\":false,\"status\":false,\"view_payments\":false},\"tables\":{\"view\":true,\"manage\":true,\"payment\":true,\"sales\":true,\"add_order\":true,\"edit_order\":true,\"delete_order\":true,\"save_order\":true,\"view_payments\":true},\"kitchen\":{\"view\":false,\"manage\":false},\"users\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"roles\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false},\"settings\":{\"view\":false,\"edit\":false},\"reports\":{\"view\":false},\"payments\":{\"view\":false,\"manage\":false,\"cancel\":false,\"reorder\":false,\"view_completed\":true},\"reservations\":{\"view\":false,\"add\":false,\"edit\":false,\"delete\":false,\"approve\":false,\"reject\":false,\"settings\":false},\"order_settings\":{\"view\":false,\"edit\":false,\"payment_methods\":false,\"discount_rules\":false,\"tax_settings\":false,\"printer_settings\":false}}', 0, '2025-09-28 12:42:16', '2025-09-28 12:48:05'),
(6, 'test', 'test', '', '{\"categories\":{\"view\":\"on\",\"add\":\"on\",\"edit\":\"on\",\"delete\":\"on\",\"kitchen_only\":\"on\"},\"order_settings\":{\"view\":\"on\",\"edit\":\"on\",\"payment_methods\":\"on\",\"discount_rules\":\"on\",\"tax_settings\":\"on\",\"printer_settings\":\"on\"}}', 0, '2025-09-28 12:53:13', '2025-09-28 12:53:13'),
(7, 'testt', 'testt', '', '{\"dashboard\":{\"view\":\"false\"},\"categories\":{\"view\":\"true\",\"add\":\"true\",\"edit\":\"true\",\"delete\":\"true\",\"kitchen_only\":\"true\"},\"products\":{\"view\":\"false\",\"add\":\"false\",\"edit\":\"false\",\"delete\":\"false\"},\"orders\":{\"view\":\"true\",\"status\":\"true\",\"view_payments\":\"true\"},\"tables\":{\"view\":\"false\",\"manage\":\"false\",\"payment\":\"false\",\"sales\":\"false\",\"add_order\":\"false\",\"edit_order\":\"false\",\"delete_order\":\"false\",\"save_order\":\"false\",\"view_payments\":\"false\"},\"kitchen\":{\"view\":\"false\",\"manage\":\"false\"},\"users\":{\"view\":\"false\",\"add\":\"false\",\"edit\":\"false\",\"delete\":\"false\"},\"roles\":{\"view\":\"false\",\"add\":\"false\",\"edit\":\"false\",\"delete\":\"false\"},\"settings\":{\"view\":\"false\",\"edit\":\"false\"},\"reports\":{\"view\":\"false\"},\"payments\":{\"view\":\"false\",\"manage\":\"false\",\"cancel\":\"false\",\"reorder\":\"false\",\"view_completed\":\"false\"},\"reservations\":{\"view\":\"false\",\"add\":\"false\",\"edit\":\"false\",\"delete\":\"false\",\"approve\":\"false\",\"reject\":\"false\",\"settings\":\"false\"},\"order_settings\":{\"view\":\"false\",\"edit\":\"false\",\"payment_methods\":\"false\",\"discount_rules\":\"false\",\"tax_settings\":\"false\",\"printer_settings\":\"false\"}}', 0, '2025-09-28 12:53:50', '2025-09-28 12:53:50'),
(8, 'testtttt', 'testtttt', '', '{\"dashboard\":{\"view\":\"true\"},\"categories\":{\"view\":\"true\",\"add\":\"true\",\"edit\":\"true\",\"delete\":\"true\",\"kitchen_only\":\"true\"},\"products\":{\"view\":\"true\",\"add\":\"true\",\"edit\":\"true\",\"delete\":\"true\"},\"orders\":{\"view\":\"false\",\"status\":\"false\",\"view_payments\":\"false\"},\"tables\":{\"view\":\"false\",\"manage\":\"false\",\"payment\":\"false\",\"sales\":\"false\",\"add_order\":\"false\",\"edit_order\":\"false\",\"delete_order\":\"false\",\"save_order\":\"false\",\"view_payments\":\"false\"},\"kitchen\":{\"view\":\"false\",\"manage\":\"false\"},\"users\":{\"view\":\"false\",\"add\":\"false\",\"edit\":\"false\",\"delete\":\"false\"},\"roles\":{\"view\":\"false\",\"add\":\"false\",\"edit\":\"false\",\"delete\":\"false\"},\"settings\":{\"view\":\"false\",\"edit\":\"false\"},\"reports\":{\"view\":\"false\"},\"payments\":{\"view\":\"false\",\"manage\":\"false\",\"cancel\":\"false\",\"reorder\":\"false\",\"view_completed\":\"false\"},\"reservations\":{\"view\":\"false\",\"add\":\"false\",\"edit\":\"false\",\"delete\":\"false\",\"approve\":\"false\",\"reject\":\"false\",\"settings\":\"false\"},\"order_settings\":{\"view\":\"false\",\"edit\":\"false\",\"payment_methods\":\"false\",\"discount_rules\":\"false\",\"tax_settings\":\"false\",\"printer_settings\":\"false\"}}', 0, '2025-09-28 12:57:23', '2025-09-28 12:57:23');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `severity` enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'MEDIUM',
  `message` text NOT NULL,
  `user_agent` text DEFAULT NULL,
  `referer` varchar(500) DEFAULT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `security_logs`
--

INSERT INTO `security_logs` (`id`, `ip_address`, `event_type`, `severity`, `message`, `user_agent`, `referer`, `request_uri`, `request_method`, `additional_data`, `created_at`) VALUES
(36, '::1', 'LOGIN_ATTEMPT', 'MEDIUM', 'Başarısız giriş: admin - Geçersiz kullanıcı adı veya şifre', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'http://localhost/qr-menu/admin/login.php', '/qr-menu/admin/login.php', 'POST', '{\"username\":\"admin\",\"success\":false,\"reason\":\"Ge\\u00e7ersiz kullan\\u0131c\\u0131 ad\\u0131 veya \\u015fifre\"}', '2025-10-26 09:08:04'),
(37, '::1', 'LOGIN_ATTEMPT', 'LOW', 'Başarılı giriş: admin', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'http://localhost/qr-menu/admin/login.php', '/qr-menu/admin/login.php', 'POST', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '2025-10-26 09:08:33'),
(38, '::1', 'LOGIN_ATTEMPT', 'MEDIUM', 'Başarısız giriş: admin@qrmenu.com - Geçersiz kullanıcı adı veya şifre', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'http://localhost/qr-menu/admin/login.php', '/qr-menu/admin/login.php', 'POST', '{\"username\":\"admin@qrmenu.com\",\"success\":false,\"reason\":\"Ge\\u00e7ersiz kullan\\u0131c\\u0131 ad\\u0131 veya \\u015fifre\"}', '2025-10-28 11:02:08'),
(39, '::1', 'LOGIN_ATTEMPT', 'LOW', 'Başarılı giriş: admin', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'http://localhost/qr-menu/admin/login.php', '/qr-menu/admin/login.php', 'POST', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '2025-10-28 11:02:20'),
(40, '::1', 'LOGIN_ATTEMPT', 'LOW', 'Başarılı giriş: admin', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'http://localhost/qr-menu/admin/login.php', '/qr-menu/admin/login.php', 'POST', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '2025-10-29 11:19:06');

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
('active_theme_id', '1', '2025-10-29 07:37:20', NULL, 0, '4'),
('currency', 'TL', '2025-02-04 05:05:53', NULL, 0, '4'),
('header_bg', '68a9f8d9a173a.jpg', '2025-09-27 10:20:46', NULL, 0, '4'),
('logo', '68ab080601fc1.png', '2025-08-24 12:39:38', NULL, 0, '4'),
('printer_auto_cut', '1', '2025-02-05 04:16:14', NULL, 0, '4'),
('printer_default', 'POS-58', '2025-08-23 14:30:08', NULL, 0, '4'),
('printer_footer', 'Bizi Tercih Ettiğiniz İçin Teşekkür Ederiz.', '2025-08-23 14:28:32', NULL, 0, '4'),
('printer_header', 'Test Firma', '2025-08-23 14:28:32', NULL, 0, '4'),
('printer_logo_enabled', '1', '2025-02-05 04:13:15', NULL, 0, '4'),
('printer_open_drawer', '1', '2025-02-05 04:16:50', NULL, 0, '4'),
('printer_paper_width', '80', '2025-02-05 04:13:15', NULL, 0, '4'),
('restaurant_name', 'QRMENÜ DEMO', '2025-09-27 10:20:46', NULL, 0, '4'),
('system_accept_qr_orders', '0', '2025-10-29 19:00:52', NULL, 0, '4'),
('system_barcode_sales_enabled', '1', '2025-10-29 19:00:52', NULL, 0, '4'),
('system_customer_access', '0', '2025-10-29 19:00:52', NULL, 0, '4'),
('system_multi_payment', '1', '2025-10-29 16:47:30', NULL, 0, '4'),
('system_qr_kitchen_visible', '0', '2025-10-29 19:00:52', NULL, 0, '4'),
('system_qr_menu_enabled', '0', '2025-10-29 19:00:52', NULL, 0, '4'),
('system_qr_orders_visible', '0', '2025-10-29 19:00:52', NULL, 0, '4'),
('system_qr_reservations_visible', '0', '2025-10-29 19:00:52', NULL, 0, '4'),
('system_qr_tables_visible', '0', '2025-10-29 19:00:52', NULL, 0, '4'),
('system_reservation_enabled', '0', '2025-10-29 18:55:24', NULL, 0, '4'),
('system_stock_management_visible', '1', '2025-10-29 19:00:57', NULL, 0, '4'),
('system_stock_tracking', '1', '2025-10-29 19:00:52', NULL, 0, '4'),
('system_table_management', '0', '2025-10-29 18:55:24', NULL, 0, '4'),
('theme_color', '#fafafa', '2025-10-29 18:58:35', NULL, 0, '4');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `movement_type` enum('in','out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `old_stock` int(11) NOT NULL DEFAULT 0,
  `new_stock` int(11) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `movement_type`, `quantity`, `old_stock`, `new_stock`, `note`, `created_by`, `created_at`) VALUES
(1, 37, 'out', 1, 9999, 9998, 'POS Satış - Fiş #66', NULL, '2025-10-29 14:37:19'),
(2, 37, 'out', 1, 9998, 9997, 'POS Satış - Fiş #67', NULL, '2025-10-29 14:37:40'),
(3, 38, 'out', 1, 9999, 9998, 'POS Satış - Fiş #68', 1, '2025-10-29 14:39:59'),
(4, 37, 'out', 1, 9997, 9996, 'POS Satış - Fiş #69', 1, '2025-10-29 14:47:24'),
(5, 38, 'out', 1, 9998, 9997, 'POS Satış - Fiş #70', 1, '2025-10-29 15:08:17'),
(6, 25, 'out', 1, 9999, 9998, 'POS Satış - Fiş #70', 1, '2025-10-29 15:08:17'),
(7, 43, 'out', 4, 50, 46, 'POS Satış - Fiş #71', 1, '2025-10-29 19:45:36'),
(8, 25, 'out', 1, 9998, 9997, 'POS Satış - Fiş #71', 1, '2025-10-29 19:45:36'),
(9, 38, 'out', 1, 9997, 9996, 'POS Satış - Fiş #71', 1, '2025-10-29 19:45:36'),
(10, 6, 'out', 1, 9999, 9998, 'POS Satış - Fiş #71', 1, '2025-10-29 19:45:36'),
(11, 54, 'in', 1, 10, 11, 'Stok girişi: +1 adet', 1, '2025-10-29 21:05:37'),
(12, 45, 'out', 8, 9999, 9991, 'POS Satış - Fiş #78', 1, '2025-10-29 21:48:41'),
(13, 43, 'out', 1, 46, 45, 'POS Satış - Fiş #79', 1, '2025-10-29 21:51:20'),
(14, 41, 'out', 1, 9999, 9998, 'POS Satış - Fiş #79', 1, '2025-10-29 21:51:20'),
(15, 42, 'out', 2, 9999, 9997, 'POS Satış - Fiş #79', 1, '2025-10-29 21:51:20');

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
  `order_code` varchar(6) DEFAULT NULL,
  `category_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `tables`
--

INSERT INTO `tables` (`id`, `table_no`, `capacity`, `qr_code`, `status`, `created_at`, `order_code`, `category_id`) VALUES
(1, 'Masa 1', 4, NULL, '', '2025-01-01 09:14:45', NULL, 1),
(2, 'Masa 2', 4, NULL, '', '2025-01-01 09:14:50', NULL, 1),
(3, 'Masa 3', 4, NULL, '', '2025-01-01 09:14:55', NULL, 1),
(4, 'Masa 4', 4, NULL, '', '2025-01-01 09:15:00', NULL, 1),
(5, 'Masa 5', 4, NULL, '', '2025-01-01 09:15:06', NULL, 1),
(6, 'Masa 6', 4, NULL, '', '2025-01-02 09:45:45', NULL, 1),
(8, 'Masa 7', 4, NULL, '', '2025-01-04 12:42:05', NULL, 1),
(11, 'Masa 18', 4, NULL, '', '2025-09-27 11:11:26', NULL, 1),
(12, 'Salon 2 Masa 1', 4, NULL, '', '2025-09-27 15:41:37', NULL, 3),
(13, 'test', 4, NULL, '', '2025-09-28 10:15:32', NULL, 5),
(14, 'Masa bahçe1', 4, NULL, '', '2025-09-28 13:00:47', NULL, 2);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `table_categories`
--

CREATE TABLE `table_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `table_categories`
--

INSERT INTO `table_categories` (`id`, `name`, `description`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Salon', 'Ana salon masaları', 1, 1, '2025-09-27 10:47:49', '2025-09-27 10:47:49'),
(2, 'Bahçe', 'Bahçe alanı masaları', 2, 1, '2025-09-27 10:47:49', '2025-09-27 10:47:49'),
(3, 'Salon 2', 'İkinci salon masaları', 3, 1, '2025-09-27 10:47:49', '2025-09-27 10:47:49'),
(4, 'test1', '', 1, 1, '2025-09-27 15:32:15', '2025-09-27 15:32:15'),
(5, 'testtt', '', 0, 1, '2025-09-28 10:15:57', '2025-09-28 10:15:57');

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
-- Tablo için indeksler `customer_themes`
--
ALTER TABLE `customer_themes`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Tablo için indeksler `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_payment_id` (`payment_id`);

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
-- Tablo için indeksler `pos_favorites`
--
ALTER TABLE `pos_favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_last_used` (`last_used`);

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
-- Tablo için indeksler `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_endpoint` (`ip_address`,`endpoint`);

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
-- Tablo için indeksler `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`created_at`),
  ADD KEY `idx_event_severity` (`event_type`,`severity`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Tablo için indeksler `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `created_at` (`created_at`);

--
-- Tablo için indeksler `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Tablo için indeksler `table_categories`
--
ALTER TABLE `table_categories`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `customer_themes`
--
ALTER TABLE `customer_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Tablo için AUTO_INCREMENT değeri `order_codes`
--
ALTER TABLE `order_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- Tablo için AUTO_INCREMENT değeri `order_settings`
--
ALTER TABLE `order_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- Tablo için AUTO_INCREMENT değeri `pos_favorites`
--
ALTER TABLE `pos_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `pre_orders`
--
ALTER TABLE `pre_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- Tablo için AUTO_INCREMENT değeri `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `reservation_orders`
--
ALTER TABLE `reservation_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Tablo için AUTO_INCREMENT değeri `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Tablo için AUTO_INCREMENT değeri `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Tablo için AUTO_INCREMENT değeri `table_categories`
--
ALTER TABLE `table_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Tablo kısıtlamaları `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`),
  ADD CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `admins` (`id`);

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

--
-- Tablo kısıtlamaları `tables`
--
ALTER TABLE `tables`
  ADD CONSTRAINT `fk_tables_category` FOREIGN KEY (`category_id`) REFERENCES `table_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
