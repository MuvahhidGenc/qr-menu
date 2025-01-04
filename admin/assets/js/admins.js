$(document).ready(function() {
    // DataTable başlat
    $('#adminsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json'
        },
        order: [[0, 'desc']]
    });

    // Personel düzenleme
    $('.edit-admin').click(function() {
        const adminId = $(this).data('id');
        console.log('Tıklanan admin ID:', adminId);

        $.get('ajax/get_admin.php', {id: adminId})
            .done(function(response) {
                console.log('AJAX başarılı, gelen veri:', response);
                
                if (response.success) {
                    // Form elemanlarını doldur
                    $('#edit_id').val(response.id);
                    $('#edit_username').val(response.username);
                    $('#edit_name').val(response.name);
                    $('#edit_email').val(response.email);
                    $('#edit_role_id').val(response.role_id);
                    
                    // Form değerlerini kontrol et
                    console.log('Form değerleri dolduruldu:', {
                        id: $('#edit_id').val(),
                        username: $('#edit_username').val(),
                        name: $('#edit_name').val(),
                        email: $('#edit_email').val(),
                        role: $('#edit_role_id').val()
                    });

                    // Modal'ı aç
                    $('#editAdminModal').modal('show');
                } else {
                    alert('Hata: ' + (response.error || 'Bilinmeyen bir hata oluştu'));
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX hatası:', {
                    status: textStatus,
                    error: errorThrown,
                    response: jqXHR.responseText
                });
            });
    });

    // Silme işlemi
    $('.delete-admin').on('click', function() {
        var id = $(this).data('id');
        var isSuperAdmin = $(this).data('super') === 1;
        
        if(isSuperAdmin) {
            Swal.fire({
                icon: 'error',
                title: 'İşlem Engellendi!',
                text: 'Süper Admin kullanıcısı silinemez.',
                confirmButtonText: 'Tamam'
            });
            return;
        }

        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu işlem geri alınamaz!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('ajax/delete_admin.php', {id: id}, function(response) {
                    if(response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı!',
                            text: 'Kullanıcı başarıyla silindi.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata!',
                            text: response.error || 'Bir hata oluştu!'
                        });
                    }
                }, 'json');
            }
        });
    });

    // Yeni admin ekleme
    $('#addAdminForm').on('submit', function(e) {
        e.preventDefault();
        
        // Form verilerini logla
        console.log('Form Data:', $(this).serialize());
        
        $.ajax({
            url: 'ajax/add_admin.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('Success Response:', response);
                if(response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Yeni kullanıcı başarıyla eklendi.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#addAdminModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: response.error || 'Bir hata oluştu!'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error});
                Swal.fire({
                    icon: 'error',
                    title: 'Sistem Hatası!',
                    text: 'Detaylı hata: ' + error
                });
            }
        });
    });

    // Form resetleme
    $('#addAdminModal').on('hidden.bs.modal', function () {
        $('#addAdminForm')[0].reset();
    });
}); 