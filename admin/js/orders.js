function transferOrder(orderId) {
    const newTableId = document.getElementById('transferTableSelect').value;
    
    if (!newTableId) {
        showAlert('Lütfen bir masa seçin', 'error');
        return;
    }

    $.ajax({
        url: 'ajax/transfer_order.php',
        type: 'POST',
        data: {
            order_id: orderId,
            new_table_id: newTableId
        },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    showAlert('Sipariş başarıyla aktarıldı', 'success');
                    // Sayfayı yenile
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (e) {
                showAlert('Bir hata oluştu', 'error');
            }
        },
        error: function() {
            showAlert('Bir hata oluştu', 'error');
        }
    });
} 