/* ==========================================================================
   Web Adrese Sipariş - Müşteri tarafı JavaScript
   Bağımlılık: jQuery + SweetAlert2 (customer-header.php ile gelir)
   Backend fiyatları HER ZAMAN doğrulayan create_order.php üzerinden gelir.
   ========================================================================== */

const DV = {
    baseUrl: window.DV_BASE_URL || '',
    cartActionUrl: window.DV_CART_ACTION_URL || 'ajax/delivery/cart_action.php',
    busy: false,

    /** Bildirim balonu */
    toast: function (message, type) {
        let el = document.getElementById('dvToast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'dvToast';
            el.className = 'dv-toast';
            document.body.appendChild(el);
        }
        el.className = 'dv-toast show ' + (type || '');
        el.textContent = message;
        clearTimeout(this._timer);
        this._timer = setTimeout(function () {
            el.className = 'dv-toast ' + (type || '');
        }, 2200);
    },

    /** Sepet sayacını (herhangi bir sayfada) güncelle */
    refreshCartBar: function () {
        const self = this;
        fetch(this.cartActionUrl + '?action=summary', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || !res.success) return;

                const countEl = document.querySelector('[data-dv-count]');
                const sumEl = document.querySelector('[data-dv-sum]');
                const btn = document.querySelector('[data-dv-go]');

                if (countEl) countEl.textContent = res.count + ' ürün';
                if (sumEl) sumEl.textContent = res.total_formatted;

                if (btn) {
                    if (res.count > 0) {
                        btn.disabled = false;
                    } else {
                        btn.disabled = true;
                    }
                }
            })
            .catch(function () { /* sessiz */ });
    },

    /** Ürün miktarını (karta özel) değiştirir */
    setQty: function (productId, delta, qtySelector) {
        const self = this;
        if (self.busy) return;

        const span = document.querySelector(qtySelector);
        if (!span) return;

        self.busy = true;
        fetch(self.cartActionUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'action=set_qty&product_id=' + encodeURIComponent(productId) + '&qty=' + encodeURIComponent(delta)
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                self.busy = false;
                if (!res.success) {
                    self.toast(res.message || 'İşlem yapılamadı', 'err');
                    return;
                }
                if (res.quantity > 0) {
                    span.textContent = res.quantity;
                }
                self.refreshCartBar();
                if (res.count === 0) {
                    setTimeout(function () { location.reload(); }, 500);
                }
            })
            .catch(function () {
                self.busy = false;
                self.toast('Sunucuya ulaşılamadı', 'err');
            });
    },

    /** Ürünü sepete ekle */
    add: function (productId, qty) {
        const self = this;
        if (self.busy) return;
        self.busy = true;

        self.toast('Sepete ekleniyor...');

        fetch(self.cartActionUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'action=add&product_id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(qty || 1)
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                self.busy = false;
                if (!res.success) {
                    self.toast(res.message || 'Ürün eklenemedi', 'err');
                    return;
                }
                self.toast(res.message || 'Sepete eklendi', 'ok');
                self.refreshCartBar();
                const btn = document.querySelector('[data-dv-add="' + productId + '"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-check"></i> Eklendi';
                    setTimeout(function () {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-cart-plus"></i> Ekle';
                    }, 1200);
                }
            })
            .catch(function () {
                self.busy = false;
                self.toast('Sunucuya ulaşılamadı', 'err');
            });
    },

    /** Sepetten ürün çıkar */
    remove: function (productId, callback) {
        const self = this;
        fetch(self.cartActionUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'action=remove&product_id=' + encodeURIComponent(productId)
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    self.toast('Ürün sepetten çıkarıldı', 'ok');
                    if (callback) callback(res);
                } else {
                    self.toast(res.message || 'İşlem yapılamadı', 'err');
                }
            })
            .catch(function () { self.toast('Sunucuya ulaşılamadı', 'err'); });
    },

    /** Ödeme yöntemi seçimi */
    selectPayment: function (value) {
        document.querySelectorAll('.dv-pay-option').forEach(function (el) {
            el.classList.toggle('active', el.dataset.value === value);
        });
        const radio = document.getElementBy('input[name="payment_method"][value="' + value + '"]');
        if (radio) radio.checked = true;
    }
};

// --------------------------------------------------------------------------
// AKTİF KATEGORİ (initSearch ve initCategories arasında PAYLAŞILIR)
// --------------------------------------------------------------------------
// Neden paylaşılıyor? Kategori AJAX ile değiştirilince (sayfa yenilenmeden)
// arama da YENİ kategoriye göre yapılmalıdır. Arama kendi içinde ayrı bir
// kopyasını tutuyor olsaydı, kategori değiştikten sonra yapılan arama eski
// kategorinin ürünlerini getirirdi.
DV._category = '0';
try {
    DV._category = (new URLSearchParams(window.location.search || '')).get('category') || '0';
} catch (e) { /* URLSearchParams yoksa ana menü varsayılanı */ }

/**
 * Kategori sekmeleri çubuğunu gösterir/gizler.
 * Arama sırasında gizlenir; arama temizlenince geri gelir.
 * Sunucu ilk yüklemede doğru durumu basar, sonrası JS'in işidir.
 */
DV.setCategoryBar = function (visible) {
    const bar = document.getElementById('dvCatBar');
    if (bar) bar.style.display = visible ? '' : 'none';
};

document.addEventListener('DOMContentLoaded', function () {
    DV.refreshCartBar();

    // ---------------------------------------------------------------
    // ÜRÜN KARTI BUTONLARI: EVENT DELEGASYONU (document seviyesinde)
    // ---------------------------------------------------------------
    // Neden delegasyon? Canlı arama sonuçları AJAX ile sonradan DOM'a
    // ekleniyor. querySelectorAll ile bağlansaydı yeni gelen kartların
    // butonları HİÇBİR ŞEKİLDE bağlanmaz ve ölü kalırdı. Tek dinleyici
    // document'e bağlandığı için sonradan eklenen her kart otomatik çalışır.
    document.addEventListener('click', function (e) {
        const target = e.target;
        if (!target || !target.closest) return;

        // Miktar artır
        const inc = target.closest('[data-dv-inc]');
        if (inc) {
            e.preventDefault();
            const id = inc.dataset.dvInc;
            const span = document.querySelector('[data-dv-qty="' + id + '"]');
            const current = span ? (parseInt(span.textContent, 10) || 1) : 1;
            if (current >= 99) return;
            DV.setQty(id, 1, '[data-dv-qty="' + id + '"]');
            return;
        }

        // Miktar azalt
        const dec = target.closest('[data-dv-dec]');
        if (dec) {
            e.preventDefault();
            const id = dec.dataset.dvDec;
            const span = document.querySelector('[data-dv-qty="' + id + '"]');
            const current = span ? (parseInt(span.textContent, 10) || 1) : 1;
            if (current <= 0) return;
            DV.setQty(id, -1, '[data-dv-qty="' + id + '"]');
            return;
        }

        // Sepete ekle
        const add = target.closest('[data-dv-add]');
        if (add) {
            e.preventDefault();
            const id = add.dataset.dvAdd;
            const span = document.querySelector('[data-dv-qty="' + id + '"]');
            const qty = span ? (parseInt(span.textContent, 10) || 1) : 1;
            DV.add(id, qty);
            return;
        }

        // Sepetten çıkar
        const rem = target.closest('[data-dv-remove]');
        if (rem) {
            e.preventDefault();
            DV.remove(rem.dataset.dvRemove, function (res) {
                if (typeof window.dvCartRowRemoved === 'function') {
                    window.dvCartRowRemoved(rem.dataset.dvRemove, res);
                } else {
                    location.reload();
                }
            });
            return;
        }

        // Ödeme seçeneği
        const pay = target.closest('.dv-pay-option');
        if (pay) {
            DV.selectPayment(pay.dataset.value);
        }
    });

    DV.initSearch();
    DV.initCategories();
});

/**
 * CANLI ürün araması (sayfa yenilenmeden)
 *
 *  - Yazmaya başlandığında 280 ms debounce uygulanır, sonra sunucuya
 *    POST ile sorulur (ajax/delivery/search.php). SAYFA YENİLENMEZ.
 *  - Sunucu, siparis.php ile BİREBİR aynı HTML'yi döner
 *    (includes/delivery-search.php) ve yanıt doğrudan #dvSearchResults
 *    konteyzerine yazılır. Böylece "N sonuç" yazısı ile ekrandaki kart
 *    sayısı yapısal olarak eşitlenemez.
 *  - Yazarken ayrıca anlık yerel filtre uygulanır (sunucu yanıtını
 *    beklemeden anında geri bildirim).
 *  - Enter'a basılırsa debounce beklemeden anında gönderilir.
 *  - fetch desteklenmiyorsa veya istek başarısız olursa klasik forma
 *    düşülür (progressive enhancement: arama yine de çalışır).
 *  - Arama kutusu yalnızca menü sayfasında vardır; yoksa sessizce çıkar.
 */
DV.initSearch = function () {
    const form = document.getElementById('dvSearchForm');
    const input = document.getElementById('dvSearchInput');
    if (!form || !input) return;

    // PHP dvSearchFold() ile BİREBİR aynı normalize etme.
    // 'I'/'ı'/'İ'/'i' -> 'i' ... 'Ş'/'s' -> 's' vb. Böylece "IRMAK" yazan
    // müşteri "irmak" aradığında da ürünü bulur.
    const norm = function (s) {
        return (s || '')
            .replace(/[ıİIi]/g, 'i')
            .replace(/[şŞSs]/g, 's')
            .replace(/[ğĞGg]/g, 'g')
            .replace(/[üÜUu]/g, 'u')
            .replace(/[öÖOo]/g, 'o')
            .replace(/[çÇCc]/g, 'c')
            .replace(/[àáâä]/g, 'a')
            .replace(/[èéêë]/g, 'e')
            .replace(/[ñ]/g, 'n')
            .replace(/[ř]/g, 'r')
            .toLowerCase()
            .trim();
    };

    // Çoklu kelime = VE mantığı (sunucuyla aynı)
    const termsOf = function (s) {
        return norm(s)
            .replace(/[%_]/g, ' ')
            .split(/\s+/)
            .filter(function (t) { return t.length > 0; })
            .slice(0, 6);
    };

    // data-name + data-desc + data-cat: sunucu bu ÜÇ alanda arar,
    // istemci de aynı üç alanda arar. (Eskiden yalnızca adda aranıyordu;
    // açıklama/kategoride geçen ürünler sunucuda bulunup ekranda gizleniyordu.)
    const haystackOf = function (card) {
        return norm(
            (card.dataset.name || '') + ' ' +
            (card.dataset.desc || '') + ' ' +
            (card.dataset.cat  || '')
        );
    };

    const results = document.getElementById('dvSearchResults');
    const endpoint = (window.DV_SEARCH_URL || 'ajax/delivery/search.php');
    const csrf = window.DV_CSRF_TOKEN || '';
    const canFetch = !!(window.fetch && results);
    // Kutu boşaltılıp menüye dönüldüğünde hangi kategoride olduğumuzu
    // korumak için gerekir (aksi halde kategori ekranından ana menüye atlanır).
    // Kategori AJAX ile değiştirilebildiği için SABİT BİR KOPYADA TUTULMAZ;
    // ortak durumdan (DV._category) okunur.
    const getCategory = function () { return DV._category || '0'; };
    let debounce = null;
    let inflight = null;        // AbortController: eski istek iptal edilir
    let seq = 0;                // Yanıt sırası: yarış koşulunda eski yanıt ezilir
    let lastTerm = null;

    // ---- Yerel filtre: anında geri bildirim, sunucu gelene kadar ----
    // Ekrandaki kartlara dokunmak SADECE sunucu yanıtı beklerken anlık
    // geri bildirim içindir. Yanıt geldiğinde tüm liste sunucunun HTML'i
    // ile değiştirilir; "N sonuç" ile kart sayısı daima eşittir.
    const localFilter = function (terms) {
        const cards = Array.prototype.slice.call(
            results.querySelectorAll('.dv-product[data-name]')
        );
        if (!terms.length) {
            cards.forEach(function (c) { c.style.display = ''; });
            return cards.length;
        }
        let shown = 0;
        cards.forEach(function (c) {
            const hay = haystackOf(c);
            // Tüm terimler bulunmalı (VE mantığı)
            let hit = true;
            for (let i = 0; i < terms.length; i++) {
                if (hay.indexOf(terms[i]) === -1) { hit = false; break; }
            }
            c.style.display = hit ? '' : 'none';
            if (hit) shown++;
        });
        return shown;
    };

    // ---- URL'yi arama terimiyle eşitle (sayfa YENİLENMEZ) ----
    // replaceState: geri tuşu arama geçmişinde gezinmez, ama URL
    // paylaşılabilir kalır ve sayfa yenilense de arama korunur.
    // NOT: Kategori bilgisi düşürülmez. Yalnızca "Tümü" (0) seçiliyken
    // category parametresi hiç yazılmaz, aksi halde URL şişerdi.
    const syncUrl = function (term) {
        if (!window.history || !window.history.replaceState) return;
        const cat = getCategory();
        let url = 'siparis.php';
        const qs = [];
        if (term) { qs.push('q=' + encodeURIComponent(term)); }
        if (cat) { qs.push('category=' + encodeURIComponent(cat)); }
        if (qs.length) { url += '?' + qs.join('&'); }
        try { window.history.replaceState({ dvSearch: term }, '', url); }
        catch (e) { /* bazı tarayıcılar file://'de reddeder */ }
    };

    // ---- Canlı arama isteği ----
    const runSearch = function (term) {
        if (!canFetch) { form.submit(); return; }   // fetch yoksa klasik form

        const mySeq = ++seq;
        if (inflight) { try { inflight.abort(); } catch (e) {} }
        inflight = (window.AbortController ? new AbortController() : null);

        form.classList.add('dv-searching');

        const body = new URLSearchParams();
        body.append('q', term);
        // Kutu boşaltıldığında sunucu menü listesini döndürsün; böylece
        // "tüm ürünler"e dönüş de sayfa yenilemeden olur.
        if (!term) { body.append('reset', '1'); }
        // Kategori her zaman gönderilir: kutu bir kategorinin içinde
        // boşaltılırsa sunucu O KATEGORİNİN listesini döndürsün, ana menüyü
        // değil. Kategori AJAX ile değişmiş olabileceği için istek anında
        // ortak durumdan okunur.
        const cat = getCategory();
        if (cat && cat !== '0') { body.append('category_id', cat); }
        if (csrf) body.append('csrf_token', csrf);

        fetch(endpoint, {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: inflight ? inflight.signal : undefined
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (res) {
                if (mySeq !== seq) return;   // daha yeni bir istek geldi
                if (!res || !res.success) throw new Error((res && res.message) || 'Arama başarısız');

                // Sunucunun ürettiği HTML doğrudan yerleştirilir.
                results.innerHTML = res.html;
                syncUrl(res.term);
                lastTerm = res.term;
                form.classList.remove('dv-searching');
                // Arama sırasında sekmeler gizlenir; arama temizlenince
                // (res.term boş) tekrar görünür olmalı.
                DV.setCategoryBar(!res.term);
                if (typeof DV.refreshCartBar === 'function') DV.refreshCartBar();
            })
            .catch(function (err) {
                if (mySeq !== seq) return;             // iptal edilmiş istek
                if (err && err.name === 'AbortError') return;
                form.classList.remove('dv-searching');
                // Sunucuya ulaşılamadıysa klasik forma düş.
                // NOT: fetch desteklenmiyorsa form zaten POST'a gider; burada
                // yalnızca istek BAŞARISIZ olduğunda devreye girer.
                if (canFetch) { form.submit(); }
            });
    };

    // ---- Yazma olayı: debounce ile canlı arama ----
    input.addEventListener('input', function () {
        const raw = input.value;
        const terms = termsOf(raw);
        clearTimeout(debounce);

        if (terms.length === 0) {
            // Boş kutu: menüye dön. fetch varsa AJAX (sayfa yenilenmez),
            // yoksa klasik form.
            if (lastTerm !== null) {
                lastTerm = null;
                runSearch('');
            }
            return;
        }

        // 1) Anında yerel geri bildirim (sunucuyu beklemeden)
        localFilter(terms);

        // 2) 280 ms sonra sunucuya sor (yazma duraklayınca)
        debounce = setTimeout(function () { runSearch(raw.trim()); }, 280);
    });

    // Enter: anında sunucuya gönder (debounce beklemeden)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(debounce);
        if (input.value.trim() === '') {
            runSearch('');
            return;
        }
        runSearch(input.value.trim());
    });

    // Sayfa ?q= ile açıldıysa canlı arama zaten etkin; yalnızca
    // "sunucu sonucu" modunda olduğumuzu not ederiz.
    if (document.querySelector('[data-server-search="1"]')) {
        input.setAttribute('data-server-results', '1');
        lastTerm = input.value.trim();
    }
};

/**
 * KATEGORİ GEÇİŞİ (sayfa yenilenmeden)
 *
 *  - Kategori sekmeleri (.dv-cat-chip), kategori kartları (.dv-cat-card) ve
 *    "Kategoriler" geri bağlantısı (data-dv-category="0") normal <a> elemanlarıdır.
 *    Bu fonksiyon onların tıklamasını yakalar, preventDefault() ile sayfa
 *    yenilemesini engeller ve aynı endpoint'i (ajax/delivery/search.php)
 *    "reset=1&category_id=N" ile sorgular.
 *  - Sunucu, siparis.php ile BİREBİR aynı HTML'yi döner
 *    (dvRenderMenuListing) ve yanıt doğrudan #dvSearchResults'a yazılır.
 *    Böylece kategori ekranı ile kategori kartı ekranı arasındaki geçiş
 *    de tutarlıdır.
 *  - ÖNBELLEK: kategori HTML'i sayfa ömründe değişmez. Bir kez alınan
 *    kategori ikinci kez tıklandığında sunucuya GİDİLMEZ, anında gösterilir.
 *    (ajax/delivery/search.php hız sınırı 60 istek/120 sn; hızlı kategori
 *    tıklamalarında sunucuyu yormamak için gereklidir.)
 *  - fetch desteklenmiyorsa veya istek başarısız olursa klasik bağlantıya
 *    düşülür: kategori yine de görünür, sadece sayfa yenilenir.
 *  - Olay DELEGASYONU kullanılır (document seviyesinde): sonuçlar innerHTML
 *    ile değiştiği için doğrudan bağlanan dinleyiciler yeni gelen kategori
 *    kartlarına ulaşamazdı.
 */
DV.initCategories = function () {
    const results = document.getElementById('dvSearchResults');
    if (!results) return;

    const endpoint = (window.DV_SEARCH_URL || 'ajax/delivery/search.php');
    const csrf = window.DV_CSRF_TOKEN || '';
    const canFetch = !!(window.fetch && results);

    const searchForm = document.getElementById('dvSearchForm');
    const searchInput = document.getElementById('dvSearchInput');

    const cache = {};               // kategoriId -> HTML
    let inflight = null;            // AbortController
    let seq = 0;                    // yanıt sırası

    const chips = function () {
        return Array.prototype.slice.call(
            document.querySelectorAll('.dv-cat-chip[data-dv-category]')
        );
    };

    const markActive = function (id) {
        chips().forEach(function (c) {
            c.classList.toggle(
                'active',
                c.getAttribute('data-dv-category') === String(id)
            );
        });
    };

    const syncCategoryUrl = function (id) {
        if (!window.history || !window.history.replaceState) return;
        const url = (id && id !== '0')
            ? 'siparis.php?category=' + encodeURIComponent(id)
            : 'siparis.php';
        try { window.history.replaceState({ dvCategory: id }, '', url); }
        catch (e) { /* file://'de reddedilebilir */ }
    };

    const render = function (id, html) {
        results.innerHTML = html;
        markActive(id);
        syncCategoryUrl(id);
        // Arama kutusu boşaltılır: arama modundan çıkıyoruz. (Kategori
        // sekmeleri arama sırasında gizli olduğu için bu normalde zaten
        // boştur; yine de sunucu modundan AJAX'a geçişte tutarlılık için.)
        if (searchInput && searchInput.value !== '') {
            searchInput.value = '';
            searchInput.removeAttribute('data-server-results');
        }
        DV.setCategoryBar(true);
        if (typeof DV.refreshCartBar === 'function') DV.refreshCartBar();
    };

    /**
     * @param {string} id   kategori id'si ('0' = ana menü)
     * @param {HTMLAnchorElement|null} link tıklanan bağlantı (fallback için)
     * @returns {boolean} true ise gezinme JS ile ele alındı (preventDefault),
     *                    false ise klasik gezinme yapılmalı.
     */
    const load = function (id, link) {
        if (!canFetch) { return false; }
        const key = String(id);
        if (Object.prototype.hasOwnProperty.call(cache, key)) {
            render(key, cache[key]);
            return true;
        }

        // Aktif kategoriyi TIKLAMA ANINDA guncelle, yanit gelmesini bekleme.
        // Arama kutusuna yanit gelmeden yazilirsa arama da yeni kategoriye
        // gore calissin; aksi halde eski kategoriye giderdi (yaris durumu).
        DV._category = key;

        const mySeq = ++seq;
        if (inflight) { try { inflight.abort(); } catch (e) {} }
        inflight = (window.AbortController ? new AbortController() : null);
        if (searchForm) searchForm.classList.add('dv-searching');

        const body = new URLSearchParams();
        body.append('reset', '1');
        body.append('category_id', key);
        if (csrf) body.append('csrf_token', csrf);

        fetch(endpoint, {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: inflight ? inflight.signal : undefined
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (res) {
                if (mySeq !== seq) return;    // daha yeni tıklama var
                if (!res || !res.success) {
                    throw new Error((res && res.message) || 'Kategori yüklenemedi');
                }
                cache[key] = res.html;
                render(key, res.html);
                if (searchForm) searchForm.classList.remove('dv-searching');
            })
            .catch(function (err) {
                if (mySeq !== seq) return;
                if (err && err.name === 'AbortError') return;
                if (searchForm) searchForm.classList.remove('dv-searching');
                // Sunucuya ulaşılamadıysa klasik bağlantıya düş: kategori
                // yine de açılır, yalnızca sayfa yenilenir.
                if (link && link.href) { window.location.href = link.href; }
            });

        return true;
    };

    document.addEventListener('click', function (e) {
        const t = e.target;
        const link = (t && t.closest) ? t.closest('[data-dv-category]') : null;
        if (!link) return;
        // Ctrl/Cmd/Shift tıklaması ve orta tuş -> tarayıcı normal davranır
        // (yeni sekme / yeni pencere), dokunulmaz.
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (typeof e.button === 'number' && e.button !== 0) return;
        const id = link.getAttribute('data-dv-category');
        if (id === null || id === '') return;
        if (load(id, link)) { e.preventDefault(); }
        // canFetch yoksa load false döner: preventDefault ÇAĞRILMAZ ve
        // tarayıcı href'e kendisi gider.
    });

    // İlk yüklemede kategori 0'ın HTML'i zaten sunucu tarafından basıldığı
    // için ön belleğe alınmaz; ilk tıklamada sunucudan gelir.
};
