// Sepet sayacını güncelle
let updateInProgress = false;
// Sayfa yüklendiğinde
$(document).ready(function() {
    updateCartCount(); // Sepet sayısını güncelle
    
    // Sepet butonuna tıklama
    $('#cartButton').off('click').on('click', function() {
        loadCart();
        $('#cartModal').modal('show');
    });
 });

// Modal footer'a sipariş notu ekle
let modalFooter = document.querySelector('#cartModal .modal-footer');
if (modalFooter) {
    let noteDiv = document.createElement('div');
    noteDiv.className = 'w-100 mb-3';
    noteDiv.innerHTML = `
        <textarea id="orderNote" class="form-control" 
                  placeholder="Sipariş notunuz (opsiyonel)" 
                  rows="2"
                  style="resize: none;"></textarea>
    `;
    modalFooter.insertBefore(noteDiv, modalFooter.firstChild);
}

// Miktar arttırma/azaltma
function decreaseAmount(productId) {
    let input = document.getElementById('qty_' + productId);
    let value = parseInt(input.value);
    if(value > 1) {
        input.value = value - 1;
    }
}

function increaseAmount(productId) {
    let input = document.getElementById('qty_' + productId);
    let value = parseInt(input.value);
    if(value < 99) {
        input.value = value + 1;
    }
}
 
// Sepete ekleme fonksiyonu güncellemesi

// Sepete ekleme
function addToCart(productId) {
    let quantity = parseInt($('#qty_' + productId).val()) || 1;
    
    $.ajax({
        url: 'ajax/add_to_cart.php',
        type: 'POST',
        data: {
            product_id: productId,
            quantity: quantity
        },
        dataType: 'json',
        success: function(response) {
            console.log('Server Response:', response); // Debug için
            if(response.success) {
                updateCartCount();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Ürün sepete eklendi',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                console.error('Error:', response.message); // Debug için
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: response.message || 'Bir hata oluştu',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        }
    });
}
 
 // Miktar güncelleme fonksiyonu
function updateQuantity(productId, change) {
    $.ajax({
        url: 'ajax/update_cart.php',
        type: 'POST',
        data: {
            product_id: productId,
            change: change
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                loadCart(); // Sepeti yeniden yükle
                updateCartCount(); // Sepet sayacını güncelle
            } else {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: response.message || 'Bir hata oluştu',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        }
    });
}
 
 // Sepet içeriğini yükleme fonksiyonu
 function loadCart() {
    $.ajax({
        url: 'ajax/get_cart.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#cartItems').html(response.html);
                $('#cartTotal').text(response.total);
            }
        }
    });
 }
 

function updateCartCount() {
    // Eğer güncelleme devam ediyorsa yeni istek yapma
    if(updateInProgress) return;
    
    updateInProgress = true;
    $.ajax({
        url: 'ajax/cart_count.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('.cart-count').text(response.count);
            }
        },
        complete: function() {
            updateInProgress = false;
        }
    });
}
 
 

//siparişi Tamammla Butonu
async function completeOrder() {
    try {
        // Önce sepet kontrolü yap
        const cartResponse = await fetch('ajax/get_cart.php');
        const cartData = await cartResponse.json();
        
        if (!cartData.success || !cartData.items || cartData.items.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sepet Boş!',
                text: 'Lütfen sipariş vermek için sepetinize ürün ekleyin.',
                confirmButtonText: 'Tamam'
            });
            return; // Fonksiyonu burada sonlandır
        }

        // Sepet doluysa devam et
        const settingsResponse = await fetch('ajax/check_order_settings.php');
        if (!settingsResponse.ok) {
            throw new Error('Sipariş ayarları alınamadı');
        }
        
        const settings = await settingsResponse.json();
        let orderCode = null;
        
        // Eğer kod gerekli ise
        if (settings.code_required) {
            // Özel modal HTML'i
            const modalHtml = `
                <div class="modal fade" id="orderCodeModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Sipariş Kodu</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="codeInput" class="form-label">Lütfen ${settings.code_length} haneli sipariş kodunu giriniz</label>
                                    <input type="text" 
                                           class="form-control text-center" 
                                           id="codeInput" 
                                           maxlength="${settings.code_length}" 
                                           placeholder="Örn: 1234"
                                           style="font-size: 1.2em; letter-spacing: 2px;">
                                    <div class="invalid-feedback">Lütfen geçerli bir kod giriniz</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                <button type="button" class="btn btn-primary" id="confirmCode">Onayla</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Modal'ı sayfaya ekle
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Modal nesnesini oluştur
            const modal = new bootstrap.Modal(document.getElementById('orderCodeModal'));
            
            // Kod girişini bekle
            orderCode = await new Promise((resolve) => {
                const modalElement = document.getElementById('orderCodeModal');
                const input = document.getElementById('codeInput');
                const confirmBtn = document.getElementById('confirmCode');
                
                // Input'a fokus ver
                modalElement.addEventListener('shown.bs.modal', () => {
                    input.focus();
                });

                // Enter tuşu ile onaylama
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        confirmBtn.click();
                    }
                });

                // Onay butonu tıklaması
                confirmBtn.addEventListener('click', async () => {
                    const value = input.value;
                    if (!value || value.length !== parseInt(settings.code_length) || !/^\d+$/.test(value)) {
                        input.classList.add('is-invalid');
                        return;
                    }

                    // Kodu doğrula
                    try {
                        console.log('Gönderilen kod:', value); // Debug log

                        const verifyResponse = await fetch('ajax/verify_order_code.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ code: value })
                        });

                        const verifyResult = await verifyResponse.json();
                        console.log('Doğrulama sonucu:', verifyResult); // Debug log

                        if (!verifyResult.success) {
                            input.classList.add('is-invalid');
                            input.nextElementSibling.textContent = verifyResult.message || 'Geçersiz sipariş kodu';
                            // Hata detaylarını konsola yazdır
                            if (verifyResult.debug) {
                                console.log('Hata detayları:', verifyResult.debug);
                            }
                            return;
                        }

                        modal.hide();
                        resolve(value);
                    } catch (error) {
                        console.error('Doğrulama hatası:', error); // Debug log
                        input.classList.add('is-invalid');
                        input.nextElementSibling.textContent = 'Kod doğrulama hatası';
                    }
                });

                // Modal kapatıldığında
                modalElement.addEventListener('hidden.bs.modal', () => {
                    resolve(null);
                    modalElement.remove(); // Modal'ı DOM'dan kaldır
                });

                // Input değiştiğinde hata durumunu temizle
                input.addEventListener('input', () => {
                    input.classList.remove('is-invalid');
                });

                // Modal'ı göster
                modal.show();
            });

            // Eğer kod girilmediyse veya iptal edildiyse
            if (!orderCode) {
                return;
            }
        }

        // Siparişi kaydet
        const urlParams = new URLSearchParams(window.location.search);
        const tableId = urlParams.get('table');

        if (!tableId) {
            throw new Error('Masa bilgisi bulunamadı!');
        }

        const response = await fetch('ajax/complete_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                table_id: tableId,
                note: document.getElementById('orderNote')?.value || ''
            })
        });

        if (!response.ok) {
            throw new Error('Sipariş tamamlanamadı');
        }

        const result = await response.json();

        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: 'Siparişiniz alındı.',
                showConfirmButton: false,
                timer: 1500
            });
            window.location.href = 'orders.php';
        } else {
            throw new Error(result.error || 'Sipariş tamamlanamadı');
        }

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: error.message
        });
    }
}

function verifyOrderCode(code) {
    fetch('ajax/verify_order_code.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            table_id: currentTableId,
            code: code
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            submitOrder(); // Kod doğru, siparişi gönder
        } else {
            Swal.fire('Hata!', 'Geçersiz sipariş kodu', 'error');
        }
    })
    .catch(error => {
        Swal.fire('Hata!', 'Kod doğrulama hatası', 'error');
    });
}