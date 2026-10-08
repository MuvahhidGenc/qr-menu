<?php
/**
 * Veritabanı hata sınıfı.
 *
 * ÖNEMLİ: Bu sınıf bilerek PDOException DEĞİLDİR. Çünkü PDO'nun hata
 * mesajı SQLSTATE + tablo/kolon/constraint adlarını içerir
 * ("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry ...
 * for key 'uq_username'"). Bu mesaj 25+ admin/ajax ucunun catch bloğunda
 * doğrudan istemciye basildigi icin sema siziyordu.
 *
 * Artik Database::query() gercek hatayi error_log'a yazip istemciye guvenli
 * bir mesajla DvDbException firlatir. Uygulama seviyesindeki
 * "throw new Exception('Bu masa numarasi zaten kullanimda!')" gibi mesajlar
 * degismez; onlar kullaniciya gosterilecek bilincli mesajlardir.
 */
class DvDbException extends RuntimeException {}

class Database {
    private $host = "localhost";
    private $db_name = "qr_menu";
    private $username = "root";
    private $password = "";
    private $conn = null;

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Önceden: echo "Bağlantı hatası: " . $e->getMessage();
            // Ham hata mesajı ekrana basılıyordu ve $this->conn null kalıyordu
            // (sonraki satırda fatal "call to member function on null").
            error_log('[qr_menu] DB baglanti hatasi: ' . $e->getMessage());
            throw new DvDbException('Veritabani baglantisi kurulamadi.');
        }
    }

    // PDO bağlantısını döndüren metod
    public function getConnection() {
        return $this->conn;
    }

    public function query($sql, $params = array()) {
        if ($this->conn === null) {
            throw new DvDbException('Veritabani baglantisi kurulamadi.');
        }
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Gerçek detay (SQLSTATE, kolon adları, constraint) sadece log'a.
            // Parametreler LOGLANMAZ: parola/token içerebilir.
            error_log(sprintf(
                '[qr_menu] SQL hatasi: %s | SQL: %s | param_sayisi: %d',
                $e->getMessage(),
                preg_replace('/\s+/', ' ', (string)$sql),
                is_array($params) ? count($params) : 0
            ));
            // İstemciye güvenli mesaj.
            throw new DvDbException('Sorgu calistirilamadi.');
        }
    }

    public function lastInsertId() {
        if ($this->conn === null) {
            throw new DvDbException('Veritabani baglantisi kurulamadi.');
        }
        return $this->conn->lastInsertId();
    }

    public function beginTransaction() {
        if ($this->conn === null) {
            throw new DvDbException('Veritabani baglantisi kurulamadi.');
        }
        return $this->conn->beginTransaction();
    }

    public function commit() {
        if ($this->conn === null) {
            throw new DvDbException('Veritabani baglantisi kurulamadi.');
        }
        return $this->conn->commit();
    }

    // PDO, aktif transaction yoksa hata firlatir. Hata durumunda sessizce
    // yutulur: zaten rollback edilecek bir sey olmayabilir.
    public function rollback() {
        if ($this->conn === null) return false;
        try {
            return $this->conn->rollBack();
        } catch (PDOException $e) {
            return false;
        }
    }
}