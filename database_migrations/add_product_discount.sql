-- Ürün indirimi (%): müşteri menüsünde "İndirimli Ürünler" kategorisi ve
-- tüm satış kanallarında indirimli fiyat için kullanılır.
-- Güvenli tekrar çalıştırma: sütun varsa ALTER atlanır.
SET @__has_discount := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'products'
      AND COLUMN_NAME = 'discount_percent'
);

SET @__alter_discount := IF(
    @__has_discount = 0,
    'ALTER TABLE products ADD COLUMN discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER price',
    'SELECT 1'
);

PREPARE __stmt_discount FROM @__alter_discount;
EXECUTE __stmt_discount;
DEALLOCATE PREPARE __stmt_discount;
