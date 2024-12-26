$(document).ready(function() {
    // Filtre butonuna tıklanınca
    $('#applyFilters').click(function() {
        let status = $('#statusFilter').val();
        let table = $('#tableFilter').val();
        let date = $('#dateFilter').val();
        
        window.location.href = `orders.php?status=${status}&table=${table}&date=${date}`;
    });

    // Sipariş durumu değiştiğinde
    $('.status-select').change(function() {
        let orderId = $(this).data('order-id');
        let newStatus = $(this).val();
        
        console.log('Updating status:', orderId, newStatus); // Debug
    
        $.ajax({
            url: 'ajax/update_order_status.php',
            type: 'POST',
            data: {
                order_id: orderId,
                status: newStatus
            },
            success: function(response) {
                console.log('Response:', response); // Debug
                if(response.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Durum güncellendi',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            }
        });
    });

    // Sipariş detayını görüntüleme
    $('.view-order').click(function() {
        let orderId = $(this).data('order-id');
        
        $.ajax({
            url: 'ajax/get_order_details.php',
            type: 'POST',
            data: { order_id: orderId },
            success: function(response) {
                if(response.success) {
                    $('#orderModal .modal-body').html(response.html);
                    $('#orderModal').modal('show');
                }
            }
        });
    });
});