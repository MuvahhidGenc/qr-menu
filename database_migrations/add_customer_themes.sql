-- SQL Migration: Customer Themes Table
-- Bu dosyayı veritabanında çalıştırarak tema yönetimi tablosunu oluşturun

CREATE TABLE IF NOT EXISTS `customer_themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `description` text,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Varsayılan tema örnekleri ekle
INSERT INTO `customer_themes` (`name`, `concept`, `primary_color`, `secondary_color`, `accent_color`, `background_color`, `text_color`, `font_family`, `category_style`, `product_layout`, `header_style`, `description`, `is_active`) VALUES
('Modern Kırmızı', 'modern', '#e74c3c', '#c0392b', '#f39c12', '#f8f9fa', '#2c3e50', 'Poppins', 'grid', 'grid', 'modern', 'Modern ve dinamik kırmızı tema', 1),
('Zarif Mavi', 'elegant', '#3498db', '#2980b9', '#1abc9c', '#ecf0f1', '#2c3e50', 'Playfair Display', 'grid', 'grid-2col', 'classic', 'Zarif ve profesyonel mavi tema', 0),
('Lüks Altın', 'luxury', '#f1c40f', '#f39c12', '#e67e22', '#2c3e50', '#ecf0f1', 'Montserrat', 'masonry', 'list', 'full', 'Lüks altın rengi tema', 0),
('Minimal Gri', 'minimal', '#95a5a6', '#7f8c8d', '#34495e', '#ffffff', '#2c3e50', 'Roboto', 'list', 'list-2col', 'minimal', 'Temiz ve minimal gri tema', 0),
('Vintage Kahve', 'vintage', '#8d6e63', '#6d4c41', '#d7ccc8', '#efebe9', '#3e2723', 'Open Sans', 'grid', 'grid', 'classic', 'Nostaljik kahve rengi tema', 0),
('Kurumsal Lacivert', 'corporate', '#2c3e50', '#34495e', '#3498db', '#ecf0f1', '#2c3e50', 'Lato', 'grid', 'grid-2col', 'modern', 'Profesyonel kurumsal tema', 0);

-- Settings tablosuna aktif tema ID'si için alan ekle
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('active_theme_id', '1') ON DUPLICATE KEY UPDATE `setting_value` = '1';