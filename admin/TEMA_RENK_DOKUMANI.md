# 🎨 TEMA RENK DOKÜMANI

## Renk Tanımlamaları

### 1. **Primary Color (Ana Renk)**
**Kullanım Alanları:**
- Kategori kartı arka planı (gradient)
- Butonlar (Sepete Ekle, Ödeme, vb.)
- Fiyat metinleri
- Hover efektleri
- Border vurguları
- Kategori başlıkları (list modda)
- Ürün fiyatları

**CSS Değişkeni:** `--theme-primary`
**Örnek:** `#e74c3c` (Kırmızı)

### 2. **Secondary Color (İkincil Renk)**
**Kullanım Alanları:**
- Buton hover durumları
- Gradient'lerin ikinci rengi
- Vurgu efektleri

**CSS Değişkeni:** `--theme-secondary`
**Örnek:** `#c0392b` (Koyu Kırmızı)

### 3. **Accent Color (Vurgu Rengi)**
**Kullanım Alanları:**
- Özel badge'ler
- İkonlar
- Lüks tema border'ları
- Dikkat çekici elementler

**CSS Değişkeni:** `--theme-accent`
**Örnek:** `#f39c12` (Turuncu)

### 4. **Background Color (Arka Plan Rengi)**
**Kullanım Alanları:**
- Sayfa arka planı
- Minimal tema kategori kartları

**CSS Değişkeni:** `--theme-background`
**Örnek:** `#f8f9fa` (Açık Gri)

### 5. **Text Color (Metin Rengi)**
**Kullanım Alanları:**
- Tüm metinler
- Başlıklar
- Açıklamalar

**CSS Değişkeni:** `--theme-text`
**Örnek:** `#2c3e50` (Koyu Gri)

---

## Kategori Listeleme Türleri

### 1. **Grid (Varsayılan)**
- **Görünüm:** Otomatik sütun sayısı (280px minimum)
- **Primary Color:** Kategori kartı gradient arka planı
- **Hover:** Scale efekti + gölge

### 2. **Grid-2col (2 Sütunlu Izgara)**
- **Görünüm:** 2 sütun sabit
- **Primary Color:** Kategori kartı gradient arka planı
- **Hover:** Yukarı kayma + gölge
- **Mobile:** 1 sütun

### 3. **List (Liste)**
- **Görünüm:** Yatay liste (resim sol, metin sağ)
- **Primary Color:** Kategori başlık metni
- **Background:** Beyaz
- **Hover:** Sağa kayma + border rengi

### 4. **List-2col (2 Sütunlu Liste)**
- **Görünüm:** 2 sütun yatay liste
- **Primary Color:** Kategori başlık metni
- **Background:** Beyaz
- **Hover:** Yukarı kayma + border rengi
- **Mobile:** 1 sütun

### 5. **Masonry (Duvar Taşı)**
- **Görünüm:** 3 sütun columns düzeni
- **Primary Color:** Kategori kartı gradient arka planı
- **Hover:** Scale efekti

---

## Ürün Listeleme Türleri

### 1. **Grid (Varsayılan)**
- **Görünüm:** Otomatik sütun sayısı (250px minimum)
- **Primary Color:** Ürün fiyatı, hover border
- **Background:** Beyaz kartlar

### 2. **Grid-2col (2 Sütunlu Izgara)**
- **Görünüm:** 2 sütun sabit
- **Primary Color:** Ürün fiyatı, hover border
- **Mobile:** 1 sütun

### 3. **List (Liste)**
- **Görünüm:** Yatay liste (resim sol, bilgi sağ)
- **Primary Color:** Ürün fiyatı, hover border
- **Background:** Beyaz kartlar

### 4. **List-2col (2 Sütunlu Liste)**
- **Görünüm:** 2 sütun yatay liste
- **Primary Color:** Ürün fiyatı, hover border
- **Mobile:** Yatay liste (1 sütun)

---

## Konsept Özel Stilleri

### Modern
- **Primary:** Skew efekti ile kategori kartları
- **Hover:** Scale + parlama

### Elegant
- **Primary:** Blur efekti
- **Hover:** Yukarı kayma + gölge

### Luxury
- **Accent:** Border rengi
- **Hover:** Parlama animasyonu

### Minimal
- **Primary:** Border rengi
- **Background:** Kategori kartı arka planı
- **Hover:** Arka plan primary renge döner

### Vintage
- **Primary:** Gradient arka plan
- **Filter:** Sepia efekti

### Corporate
- **Primary:** Gradient arka plan
- **Accent:** Sol border vurgusu

---

## Test Senaryoları

1. **Tema Seç:** Modern Kırmızı
2. **Kategori Stili:** Grid-2col
3. **Ürün Düzeni:** List-2col
4. **Kontrol Et:**
   - ✅ Kategoriler 2 sütunda mı?
   - ✅ Ürünler 2 sütunda mı?
   - ✅ Kırmızı renk görünüyor mu?
   - ✅ Hover efektleri çalışıyor mu?
   - ✅ Mobile'da 1 sütuna geçiyor mu?

