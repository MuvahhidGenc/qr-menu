-- Masa Kategorileri için Migration
-- Mevcut yapıyı bozmadan kategori sistemi ekleniyor

-- 1. Masa kategorileri tablosunu oluştur
CREATE TABLE IF NOT EXISTS `table_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Varsayılan kategorileri ekle
INSERT IGNORE INTO `table_categories` (`id`, `name`, `description`, `sort_order`) VALUES
(1, 'Salon', 'Ana salon masaları', 1),

-- 3. Tables tablosuna category_id ekle (eğer yoksa)
ALTER TABLE `tables` 
ADD COLUMN IF NOT EXISTS `category_id` int(11) DEFAULT 1,
ADD INDEX IF NOT EXISTS `idx_category_id` (`category_id`);

-- 4. Foreign key constraint ekle (eğer yoksa)
ALTER TABLE `tables` 
ADD CONSTRAINT `fk_tables_category` 
FOREIGN KEY (`category_id`) REFERENCES `table_categories`(`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- 5. Mevcut masaları varsayılan kategoriye ata (Salon)
UPDATE `tables` SET `category_id` = 1 WHERE `category_id` IS NULL;
