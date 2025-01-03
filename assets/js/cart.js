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
        
        // Debug için sepet verilerini konsola yazdır
        console.log('Sepet Verileri:', cartData);
        
        // Sepet kontrolünü düzelt
        if (!cartData.success || cartData.total <= 0) {  // items yerine total kontrolü
            Swal.fire({
                icon: 'warning',
                title: 'Sepet Boş!',
                text: 'Lütfen sipariş vermek için sepetinize ürün ekleyin.',
                confirmButtonText: 'Tamam'
            });
            return;
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
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header border-0 bg-primary text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-key me-2"></i>Sipariş Kodu
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="text-center mb-4">
                                    <div class="mb-3">
                                        <i class="fas fa-lock fa-3x text-primary"></i>
                                    </div>
                                    <p class="lead mb-1">Lütfen ${settings.code_length} haneli sipariş kodunu giriniz</p>
                                    <small class="text-muted">Sipariş kodunuz masa üzerindeki QR ile birlikte verilmiştir</small>
                                </div>
                                <div class="form-group">
                                    <div class="position-relative">
                                        <input type="text" 
                                            class="form-control form-control-lg text-center border-2" 
                                            id="codeInput" 
                                            maxlength="${settings.code_length}" 
                                            placeholder="● ● ● ●"
                                            style="font-size: 1.5em; letter-spacing: 8px; font-weight: bold;">
                                        <div class="invalid-feedback text-center"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 justify-content-center">
                                <button type="button" 
                                        class="btn btn-secondary px-4" 
                                        data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>İptal
                                </button>
                                <button type="button" 
                                        class="btn btn-primary px-4" 
                                        id="confirmCode">
                                    <i class="fas fa-check me-2"></i>Onayla
                                </button>
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
                timer: 15000
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

// Input stil ve davranışları için
document.addEventListener('DOMContentLoaded', function() {
    // Input'a fokus olduğunda stil değişimi
    $(document).on('focus', '#codeInput', function() {
        $(this).addClass('border-primary');
    });

    // Input'tan çıkıldığında stil değişimi
    $(document).on('blur', '#codeInput', function() {
        $(this).removeClass('border-primary');
    });

    // Sadece rakam girişine izin ver
    $(document).on('keypress', '#codeInput', function(e) {
        if (e.which < 48 || e.which > 57) {
            e.preventDefault();
        }
    });

    // Input değeri değiştiğinde animasyon
    $(document).on('input', '#codeInput', function() {
        $(this).removeClass('is-invalid');
        if ($(this).val().length === parseInt(settings.code_length)) {
            $('#confirmCode').addClass('btn-pulse');
        } else {
            $('#confirmCode').removeClass('btn-pulse');
        }
    });
});

// Pulse animasyonu için CSS
const style = document.createElement('style');
style.textContent = `
    .btn-pulse {
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(var(--bs-primary-rgb), 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0);
        }
    }
    #orderCodeModal .modal-content {
        border-radius: 15px;
    }
    #orderCodeModal .modal-header {
        border-radius: 15px 15px 0 0;
    }
    #codeInput {
        border-radius: 10px;
        height: 60px;
        transition: all 0.3s ease;
    }
    #codeInput:focus {
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    }
    .invalid-feedback {
        font-size: 0.9em;
        margin-top: 0.5rem;
    }
`;
document.head.appendChild(style);