$(document).ready(function() {
    // Yeni masa ekleme
    $('#addTableForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'ajax/add_table.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Masa başarıyla eklendi',
                        confirmButtonText: 'Tamam'
                    }).then((result) => {
                        location.reload();
                    });
                }
            }
        });
    });

    // Masa durumu değiştirme
    $('.table-status').change(function() {
        let tableId = $(this).data('id');
        let status = $(this).is(':checked') ? 1 : 0;
        
        $.ajax({
            url: 'ajax/update_table_status.php',
            type: 'POST',
            data: {
                table_id: tableId,
                status: status
            },
            success: function(response) {
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

    // QR kod oluşturma
    $('.generate-qr').click(function() {
        let tableId = $(this).data('id');
        let tableName = $(this).data('table');
        
        // QR kodun içereceği URL
        let url = window.location.origin + '/qr-menu?table=' + tableId;
        
        // Önceki QR kodu temizle
        $('#qrCode').empty();
        
        // Yeni QR kod oluştur
        new QRCode(document.getElementById("qrCode"), {
            text: url,
            width: 256,
            height: 256
        });
        /*var modal = new bootstrap.Modal(document.getElementById('qrModal'));
modal.show();*/
       $('#qrModal').modal('show');
    });

    // QR kod indirme
    $('.download-qr').click(function() {
        let canvas = document.querySelector("#qrCode canvas");
        let image = canvas.toDataURL("image/png");
        let link = document.createElement('a');
        link.download = 'masa-qr.png';
        link.href = image;
        link.click();
    });

    // Masa silme
    $('.delete-table').click(function() {
        let tableId = $(this).data('id');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu masa kalıcı olarak silinecek!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/delete_table.php',
                    type: 'POST',
                    data: { table_id: tableId },
                    success: function(response) {
                        if(response.success) {
                            Swal.fire(
                                'Silindi!',
                                'Masa başarıyla silindi.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        }
                    }
                });
            }
        });
    });
});

// Edit modal açma
$('.edit-table').click(function() {
    let tableId = $(this).data('id');
    let tableName = $(this).data('table');
    
    $('#editTableId').val(tableId);
    $('#editTableNo').val(tableName);
    $('#editTableModal').modal('show');
});

// Edit form submit
$('#editTableForm').submit(function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'ajax/edit_table.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if(response.success) {
                $('#editTableModal').modal('hide');
                location.reload();
            }
        }
    });
});