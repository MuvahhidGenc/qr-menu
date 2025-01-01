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
function completeOrder() {
    let note = document.getElementById('orderNote').value;
    
    fetch('process-order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'complete_order',
            note: note
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Başarılı!',
                text: 'Siparişiniz alındı.',
                icon: 'success'
            }).then(() => {
                window.location.href = 'orders.php';
            });
        } else {
            Swal.fire({
                title: 'Hata!',
                text: data.message || 'Bir hata oluştu.',
                icon: 'error'
            });
        }
    });
}