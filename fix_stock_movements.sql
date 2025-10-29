-- user_id kolonu ekle
ALTER TABLE stock_movements 
ADD COLUMN user_id INT(11) NULL AFTER product_id;

-- Index ekle
ALTER TABLE stock_movements 
ADD INDEX idx_user_id (user_id);

