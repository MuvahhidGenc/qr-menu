-- ============================================================================
-- Web Adrese Sipariş (Online Delivery Order) Migration
-- ----------------------------------------------------------------------------
-- Hedef: Mevcut QR Menü / Peşin Satış akışlarına dokunmadan, internet üzerinden
--        adrese sipariş alabilmek için gerekli alanları eklemek.
--
-- NOTLAR:
--   * Yeni tablo OLUŞTURULMAZ. Mevcut `orders` tablosu genişletilir.
--   * `table_id` NULL'a izin verir; adres siparişinde masa kullanılmaz.
--   * Yeni durumlar enum'un SONUNA eklenir; mevcut enum sırası/değerleri bozulmaz.
--   * Tüm sorgular IF NOT EXISTS / INFORMATION_SCHEMA kontrollüdür; tekrar
--     çalıştırılsa da hata vermez (idempotent).
-- ============================================================================

-- 1) Sipariş tipi ayırıcısı ----------------------------------------------------
SET @has_order_type := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'order_type'
);
SET @sql := IF(@has_order_type = 0,
    "ALTER TABLE `orders` ADD COLUMN `order_type` enum('table','delivery') NOT NULL DEFAULT 'table' AFTER `table_id`",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Müşteri bilgileri (sipariş anında snapshot) ------------------------------
SET @has_customer_name := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'customer_name'
);
SET @sql := IF(@has_customer_name = 0,
    "ALTER TABLE `orders`
     ADD COLUMN `customer_name` varchar(100) DEFAULT NULL AFTER `order_type`,
     ADD COLUMN `customer_surname` varchar(100) DEFAULT NULL AFTER `customer_name`,
     ADD COLUMN `customer_phone` varchar(20) DEFAULT NULL AFTER `customer_surname`",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Teslimat adresi ----------------------------------------------------------
SET @has_delivery_address := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivery_address'
);
SET @sql := IF(@has_delivery_address = 0,
    "ALTER TABLE `orders`
     ADD COLUMN `delivery_city` varchar(50) DEFAULT NULL AFTER `customer_phone`,
     ADD COLUMN `delivery_district` varchar(50) DEFAULT NULL AFTER `delivery_city`,
     ADD COLUMN `delivery_neighborhood` varchar(100) DEFAULT NULL AFTER `delivery_district`,
     ADD COLUMN `delivery_address` varchar(500) DEFAULT NULL AFTER `delivery_neighborhood`,
     ADD COLUMN `delivery_building_no` varchar(20) DEFAULT NULL AFTER `delivery_address`,
     ADD COLUMN `delivery_apartment_no` varchar(20) DEFAULT NULL AFTER `delivery_building_no`,
     ADD COLUMN `delivery_note` text DEFAULT NULL AFTER `delivery_apartment_no`",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Sipariş tutarları --------------------------------------------------------
SET @has_subtotal := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'subtotal'
);
SET @sql := IF(@has_subtotal = 0,
    "ALTER TABLE `orders`
     ADD COLUMN `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`,
     ADD COLUMN `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`,
     ADD COLUMN `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `discount_amount`",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5) Ödeme yöntemi (kapıda nakit / kapıda kart) -------------------------------
SET @has_payment_method := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_method'
);
SET @sql := IF(@has_payment_method = 0,
    "ALTER TABLE `orders` ADD COLUMN `payment_method` varchar(20) DEFAULT NULL AFTER `delivery_fee`",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6) Müşteri takip token'ı (KVKK: kalıcı müşteri verisi tutulmaz) ------------
SET @has_delivery_token := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivery_token'
);
SET @sql := IF(@has_delivery_token = 0,
    "ALTER TABLE `orders` ADD COLUMN `delivery_token` varchar(64) DEFAULT NULL AFTER `payment_method`",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7) Adres siparişleri için durumlar (enum sonuna eklenir) -------------------
SET @has_confirmed := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'status' AND COLUMN_TYPE LIKE '%confirmed%'
);
SET @sql := IF(@has_confirmed = 0,
    "ALTER TABLE `orders` MODIFY COLUMN `status`
     enum('pending','preparing','ready','delivered','completed','cancelled','partial_paid','confirmed','on_the_way')
     NOT NULL DEFAULT 'pending'",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 8) İndeksler ----------------------------------------------------------------
SET @has_idx_order_type := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_order_type'
);
SET @sql := IF(@has_idx_order_type = 0,
    "ALTER TABLE `orders` ADD INDEX `idx_order_type` (`order_type`, `status`)",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_idx_token := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_delivery_token'
);
SET @sql := IF(@has_idx_token = 0,
    "ALTER TABLE `orders` ADD INDEX `idx_delivery_token` (`delivery_token`)",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 9) Sistem parametreleri -----------------------------------------------------
-- Anahtar uzunluğu max 50 karakter olmalıdır (settings.setting_key VARCHAR(50)).
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('system_delivery_order_enabled', '0'),
    ('delivery_fee', '0.00'),
    ('delivery_free_over', '0.00'),
    ('delivery_min_order', '0.00'),
    ('delivery_payment_methods', 'cash,card'),
    ('delivery_payment_cash_enabled', '1'),
    ('delivery_payment_card_enabled', '1'),
    ('delivery_required_fields', 'name,surname,phone,city,district,neighborhood,address'),
    ('delivery_prepare_minutes', '45'),
    ('delivery_active_days', '1,2,3,4,5,6,7'),
    ('delivery_open_time', '10:00'),
    ('delivery_close_time', '23:00'),
    ('delivery_hour_check', '0')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- 10) QR ile masa siparisi anahtari -------------------------------------------
-- QR menusu (index.php?table=N) ve Web Adrese Siparis (siparis.php) artik
-- BIRBIRINDEN BAGIMSIZ iki ayri anahtarla kontrol edilir.
-- Eski `system_customer_access` degeri yeni anahtara tasinir; boylece mevcut
-- kurulumlarda yoneticinin tercihi korunur.
-- INSERT IGNORE: anahtar zaten varsa UYGULANMAZ (idempotent ve yikici degil).
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`)
    SELECT 'system_table_qr_order_enabled', `setting_value`
    FROM `settings`
    WHERE `setting_key` = 'system_customer_access';

-- Eski anahtar artik kullanilmaz; yanlislikla acik kalmasin diye silinir.
-- NOT: Bu anahtari tutan kod kalmadigi icin veri kaybi riski yoktur.
DELETE FROM `settings` WHERE `setting_key` = 'system_customer_access';
