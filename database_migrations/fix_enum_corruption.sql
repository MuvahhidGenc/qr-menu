-- =====================================================================
-- Enum bozulması onarımı
-- ---------------------------------------------------------------------
-- Neden oluştu?
--   MySQL session sql_mode = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'
--   (STRICT_TRANS_TABLES YOK). Bu modda ENUM sütununa yazılan geçersiz bir
--   değer hata vermek yerine sessizce boş string ('') olur.
--
--   - admin/ajax/update_table_status.php, frontend'den gelen 1/0 değerini
--     (int) cast edip doğrudan tables.status'a yazıyordu.
--     1/0, ENUM('active','inactive') içinde yok → TÜM masaların status'u
--     boş string'e çöktü ve 'status = active' sorguları 0 satır döndürdü.
--   - POS satışları 'card' / 'mixed' gibi payments ENUM('cash','pos')
--     dışındaki yöntemleri gönderiyordu → method boş string oldu.
--
-- Düzeltme: bozulan satırları iş kanıtına göre geri kazan.
--   - 'POS Satış ...' notu + table_id IS NULL  => 'pos'
--   - table_id DOLU (masa tahsilatı)           => 'cash'
--   - orders.status boş                       => 'pending'
--   - tables.status boş                       => 'active'
--
-- Bu dosya TEKRAR İDEMPOTENTTİR: güvenli şekilde birden fazla çalıştırılabilir.
-- =====================================================================

START TRANSACTION;

-- 1) Masa tahsilatı olan, bozulmuş ödeme yöntemi -> 'cash'
UPDATE payments
   SET payment_method = 'cash'
 WHERE payment_method IS NULL
    OR payment_method NOT IN ('cash', 'pos');

-- 2) Kasa/POS satışı olan, bozulmuş ödeme yöntemi -> 'pos'
--    (bu kural 1'den SONRA çalışır; 1. adım bunları 'cash' yapmış olsa bile
--     POS Satış notu + table_id IS NULL kanıtı 'pos' değerini geri getirir)
UPDATE payments
   SET payment_method = 'pos'
 WHERE table_id IS NULL
   AND payment_note LIKE 'POS Satış%'
   AND payment_method <> 'pos';

-- 3) Bozuk sipariş durumları -> 'pending'
UPDATE orders
   SET status = 'pending'
 WHERE status IS NULL
    OR status NOT IN ('pending','preparing','ready','delivered','completed',
                      'cancelled','partial_paid','confirmed','on_the_way');

-- 4) Bozuk masa durumları -> 'active'
--    Enum varsayılanı 'active' ve arayüzdeki anahtar "aktif = işaretli".
--    Boş değer yalnızca (int) cast hatasının ürettiği bir bozulma ürünüdür.
UPDATE tables
   SET status = 'active'
 WHERE status IS NULL
    OR status NOT IN ('active', 'inactive');

COMMIT;

-- ---------------------------------------------------------------------
-- Doğrulama (tümü 0 dönmeli)
-- ---------------------------------------------------------------------
SELECT 'payments.payment_method bozuk' AS kontrol, COUNT(*) AS bozuk
  FROM payments WHERE payment_method IS NULL OR payment_method NOT IN ('cash','pos')
UNION ALL
SELECT 'orders.status bozuk', COUNT(*)
  FROM orders WHERE status IS NULL OR status NOT IN
         ('pending','preparing','ready','delivered','completed','cancelled',
          'partial_paid','confirmed','on_the_way')
UNION ALL
SELECT 'tables.status bozuk', COUNT(*)
  FROM tables WHERE status IS NULL OR status NOT IN ('active','inactive');
