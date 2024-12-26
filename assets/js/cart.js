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
 
 // Siparişi tamamlama fonksiyonu
 function completeOrder() {
    $.ajax({
        url: 'ajax/complete_order.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#cartModal').modal('hide');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Sipariş Alındı!',
                    text: 'Siparişiniz başarıyla alındı. Sipariş numaranız: ' + response.order_id,
                    confirmButtonText: 'Tamam'
                }).then((result) => {
                    updateCartCount();
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: response.message || 'Bir hata oluştu'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'Bir hata oluştu, lütfen tekrar deneyin.'
            });
        }
    });
 }

//siparişi Tamammla Butonu
 function completeOrder() {
    $.ajax({
        url: 'ajax/complete_order.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#cartModal').modal('hide');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Sipariş Alındı!',
                    text: 'Siparişiniz başarıyla alındı. Sipariş numaranız: ' + response.order_id,
                    confirmButtonText: 'Tamam'
                }).then((result) => {
                    // Sepet sayacını güncelle
                    updateCartCount();
                    // Sayfayı yenile
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: response.message || 'Bir hata oluştu'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'Bir hata oluştu, lütfen tekrar deneyin.'
            });
        }
    });
}