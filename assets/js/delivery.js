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

document.addEventListener('DOMContentLoaded', function () {
    DV.refreshCartBar();

    // Ürün kartlarındaki miktar butonları
    document.querySelectorAll('[data-dv-inc]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = btn.dataset.dvInc;
            const span = document.querySelector('[data-dv-qty="' + id + '"]');
            const current = parseInt(span.textContent, 10) || 1;
            if (current >= 99) return;
            DV.setQty(id, 1, '[data-dv-qty="' + id + '"]');
        });
    });

    document.querySelectorAll('[data-dv-dec]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = btn.dataset.dvDec;
            const span = document.querySelector('[data-dv-qty="' + id + '"]');
            const current = parseInt(span.textContent, 10) || 1;
            if (current <= 0) return;
            DV.setQty(id, -1, '[data-dv-qty="' + id + '"]');
        });
    });

    // Sepete ekle butonları
    document.querySelectorAll('[data-dv-add]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = btn.dataset.dvAdd;
            const span = document.querySelector('[data-dv-qty="' + id + '"]');
            const qty = span ? (parseInt(span.textContent, 10) || 1) : 1;
            DV.add(id, qty);
        });
    });

    // Sepetten çıkar
    document.querySelectorAll('[data-dv-remove]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            DV.remove(btn.dataset.dvRemove, function (res) {
                if (typeof window.dvCartRowRemoved === 'function') {
                    window.dvCartRowRemoved(btn.dataset.dvRemove, res);
                } else {
                    location.reload();
                }
            });
        });
    });

    // Ödeme seçenekleri
    document.querySelectorAll('.dv-pay-option').forEach(function (el) {
        el.addEventListener('click', function () { DV.selectPayment(el.dataset.value); });
    });
});
