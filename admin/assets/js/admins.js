$(document).ready(function() {
    // jQuery'nin yüklendiğini kontrol et
    console.log('jQuery version:', $.fn.jquery);
    
    // Butonun varlığını kontrol et
    console.log('Kaydet butonu var mı:', $('#saveEditButton').length);

    // Event listener'ı document üzerinden dinleyelim
    $(document).on('click', '#saveEditButton', function(e) {
        e.preventDefault();
        console.log('Kaydet butonuna tıklandı');
        
        var formData = $('#editAdminForm').serialize();
        console.log('Gönderilecek veriler:', formData);

        $.ajax({
            url: 'ajax/edit_admin.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Yönetici başarıyla güncellendi.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#editAdminModal').modal('hide');
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
                console.error('AJAX Error:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Sistem Hatası!',
                    text: 'İstek gönderilirken bir hata oluştu: ' + error
                });
            }
        });
    });

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

    // Düzenleme modalını açma
    $('.edit-admin').on('click', function() {
        var id = $(this).data('id');
        var username = $(this).data('username');
        var name = $(this).data('name');
        var email = $(this).data('email');
        var role = $(this).data('role');
        var salary = $(this).data('salary');
        var bonus = $(this).data('bonus');
        
        $('#edit_id').val(id);
        $('#edit_username').val(username);
        $('#edit_name').val(name);
        $('#edit_email').val(email);
        $('#edit_role_id').val(role);
        
        // Maaş için formatlanmış değer
        if (salary) {
            salary = parseFloat(salary).toLocaleString('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).replace(/\s/g, '.');
        }
        $('#edit_salary').val(salary);
        
        // Prim için düz değer
        $('#edit_bonus_percentage').val(bonus ? parseInt(bonus) : '');
        
        $('#editAdminModal').modal('show');
    });

    // Para birimi formatı için mask tanımlama
    function initializeMasks() {
        // Maaş alanı için mask
        $('#salary, #edit_salary').mask('###.###.###.##0,00', {
            reverse: true,
            placeholder: "0,00"
        });
        
        // Prim yüzdesi için sadece sayı kontrolü (0-100 arası)
        $('#bonus_percentage, #edit_bonus_percentage').on('input', function() {
            let value = $(this).val().replace(/[^\d]/g, '');
            if (value > 100) value = 100;
            $(this).val(value);
        });
    }

    // Form gönderiminde değerleri temizle
    function cleanNumber(value) {
        if (!value) return null;
        
        // Maaş için
        if (value.includes('.') || value.includes(',')) {
            return parseFloat(value.replace(/\./g, '').replace(',', '.'));
        }
        
        // Prim için (direkt sayı)
        return parseFloat(value);
    }

    // Sayfa yüklendiğinde ve modal açıldığında maskları başlat
    initializeMasks();
    
    $('#addAdminModal, #editAdminModal').on('shown.bs.modal', function () {
        initializeMasks();
        
        // Input alanlarına tıklandığında içeriği seç
        $('#salary, #edit_salary, #bonus_percentage, #edit_bonus_percentage').on('click', function() {
            $(this).select();
        });
    });
}); 