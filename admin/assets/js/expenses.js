$(document).ready(function() {
    // Para birimi maskesi
    function initializeMasks() {
        $('#expense_amount, #edit_amount').mask('###.###.###.##0,00', {
            reverse: true,
            placeholder: "0,00"
        });
    }

    // Sayfa yüklendiğinde bugünün tarihini set et
    function setTodayDate() {
        const today = new Date().toISOString().split('T')[0];
        $('#expense_date').val(today);
    }

    // Başlangıç işlemleri
    initializeMasks();
    setTodayDate();

    // Kategori değiştiğinde
    $('#expense_category').on('change', function() {
        const selectedCategory = $(this).find('option:selected');
        const categoryName = selectedCategory.text().trim();
        const staffSelection = $('#staff_selection');
        const amountInput = $('#expense_amount');
        const descriptionInput = $('#expense_description');

        console.log('Seçilen kategori:', categoryName); // Debug için

        if (categoryName === 'Personel Maaşları') {
            staffSelection.slideDown();
            // Alanları düzenlenebilir bırak
            amountInput.prop('readonly', false);
            descriptionInput.prop('readonly', false);
        } else {
            staffSelection.slideUp();
            amountInput.val('');
            descriptionInput.val('');
        }
    });

    // Personel seçildiğinde
    $('#staff_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const salary = selectedOption.data('salary');
        
        if (salary) {
            // Maaşı formatlı şekilde göster
            const formattedSalary = parseFloat(salary).toLocaleString('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).replace(/\s/g, '.');
            
            // Varsayılan değerleri doldur ama düzenlenebilir bırak
            $('#expense_amount').val(formattedSalary);
            
            // Personel adını açıklamaya ekle
            const staffName = selectedOption.text().trim();
            $('#expense_description').val(staffName + ' - Maaş Ödemesi');
            
            // Mask'ı yeniden uygula
            initializeMasks();
        } else {
            $('#expense_amount').val('');
            $('#expense_description').val('');
        }
    });

    // Form gönderiminde değerleri temizle
    function cleanNumber(value) {
        if (!value) return null;
        return parseFloat(value.replace(/\./g, '').replace(',', '.'));
    }

    // Form gönderimi
    $('#addExpenseForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            category_id: $('#expense_category').val(),
            amount: cleanNumber($('#expense_amount').val()),
            expense_date: $('#expense_date').val(),
            description: $('#expense_description').val(),
            staff_id: $('#staff_id').val() // Personel seçilmişse
        };

        $.ajax({
            type: 'POST',
            url: 'ajax/add_expense.php',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Gider başarıyla eklendi.',
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
            },
            error: function(xhr, status, error) {
                console.error('AJAX Hatası:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Sistem Hatası!',
                    text: 'İşlem sırasında bir hata oluştu.'
                });
            }
        });
    });

    // DataTables başlat
    $('#expenseTable').DataTable({
        responsive: true,
        dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: 'Gider Listesi',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                title: 'Gider Listesi',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Yazdır',
                className: 'btn btn-info btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            }
        ],
        language: {
            "emptyTable": "Tabloda herhangi bir veri mevcut değil",
            "info": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
            "infoEmpty": "Kayıt yok",
            "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
            "infoThousands": ".",
            "lengthMenu": "Sayfada _MENU_ kayıt göster",
            "loadingRecords": "Yükleniyor...",
            "processing": "İşleniyor...",
            "search": "Ara:",
            "zeroRecords": "Eşleşen kayıt bulunamadı",
            "paginate": {
                "first": "İlk",
                "last": "Son",
                "next": "Sonraki",
                "previous": "Önceki"
            },
            "aria": {
                "sortAscending": ": artan sütun sıralamasını aktifleştir",
                "sortDescending": ": azalan sütun sıralamasını aktifleştir"
            },
            "select": {
                "rows": {
                    "_": "%d kayıt seçildi",
                    "1": "1 kayıt seçildi"
                }
            },
            "buttons": {
                "copySuccess": {
                    "1": "1 kayıt panoya kopyalandı",
                    "_": "%d kayıt panoya kopyalandı"
                },
                "copy": "Kopyala",
                "print": "Yazdır"
            }
        },
        order: [[0, 'desc']], // Tarihe göre sırala
        pageLength: 25 // Sayfa başına gösterilecek kayıt sayısı
    });

    // Düzenleme modalını aç
    $('.edit-expense').on('click', function() {
        const id = $(this).data('id');
        const amount = $(this).data('amount');
        const category = $(this).data('category');
        const description = $(this).data('description');
        const date = $(this).data('date');

        $('#edit_id').val(id);
        $('#edit_category_id').val(category);
        $('#edit_description').val(description);
        $('#edit_date').val(date);

        // Tutarı formatla
        const formattedAmount = parseFloat(amount).toLocaleString('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).replace(/\s/g, '.');
        $('#edit_amount').val(formattedAmount);

        // Mask'ı yeniden uygula
        initializeMasks();

        $('#editExpenseModal').modal('show');
    });

    // Düzenleme formunu gönder
    $('#editExpenseForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'edit',
            id: $('#edit_id').val(),
            category_id: $('#edit_category_id').val(),
            amount: cleanNumber($('#edit_amount').val()),
            expense_date: $('#edit_date').val(),
            description: $('#edit_description').val()
        };

        $.ajax({
            type: 'POST',
            url: 'ajax/edit_expense.php',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Gider başarıyla güncellendi.',
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
            },
            error: function(xhr, status, error) {
                console.error('AJAX Hatası:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Sistem Hatası!',
                    text: 'İşlem sırasında bir hata oluştu.'
                });
            }
        });
    });

    // Silme işlemi
    $('.delete-expense').on('click', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu gider kaydı kalıcı olarak silinecek!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: 'ajax/delete_expense.php',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı!',
                                text: 'Gider başarıyla silindi.',
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
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Hatası:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Sistem Hatası!',
                            text: 'İşlem sırasında bir hata oluştu.'
                        });
                    }
                });
            }
        });
    });

    // İstatistikleri güncelle
    function updateStats() {
        $.ajax({
            url: 'ajax/get_expense_stats.php',
            type: 'GET',
            success: function(response) {
                $('#monthlyTotal').text(response.monthlyTotal + ' ₺');
                $('#yearlyTotal').text(response.yearlyTotal + ' ₺');
                $('#topCategory').text(response.topCategory);
                $('#dailyAverage').text(response.dailyAverage + ' ₺');
            }
        });
    }

    // Grafikleri çiz
    function initCharts() {
        // Aylık gider grafiği
        $.ajax({
            url: 'ajax/get_monthly_expenses.php',
            type: 'GET',
            success: function(data) {
                if (data.labels && data.labels.length > 0) {
                    const ctx = document.getElementById('expenseChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Aylık Giderler (₺)',
                                data: data.values,
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return value.toLocaleString('tr-TR') + ' ₺';
                                        }
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.parsed.y.toLocaleString('tr-TR') + ' ₺';
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    $('#expenseChart').parent().html('<div class="text-center text-muted">Henüz gider kaydı bulunmuyor</div>');
                }
            }
        });

        // Kategori dağılımı grafiği
        $.ajax({
            url: 'ajax/get_category_distribution.php',
            type: 'GET',
            success: function(data) {
                if (data.labels && data.labels.length > 0) {
                    const ctx = document.getElementById('categoryChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.values,
                                backgroundColor: data.colors || [
                                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                                    '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.parsed;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value * 100) / total).toFixed(1);
                                            return `${context.label}: ${value.toLocaleString('tr-TR')} ₺ (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    $('#categoryChart').parent().html('<div class="text-center text-muted">Henüz kategori verisi bulunmuyor</div>');
                }
            }
        });
    }

    // Filtreleme işlemi
    $('#filterButton').on('click', function() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        const categoryId = $('#categoryFilter').val();

        const table = $('#expenseTable').DataTable();
        
        // Önceki filtreleri temizle
        $.fn.dataTable.ext.search.pop();
        
        // Custom filtreleme fonksiyonu
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const row = table.row(dataIndex).node();
            let valid = true;
            
            // Tarih kontrolü
            if (startDate && endDate) {
                const rowDate = new Date(data[0].split('.').reverse().join('-'));
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                // Saat farkını sıfırla
                rowDate.setHours(0, 0, 0, 0);
                start.setHours(0, 0, 0, 0);
                end.setHours(0, 0, 0, 0);
                
                valid = valid && (rowDate >= start && rowDate <= end);
            }
            
            // Kategori kontrolü
            if (categoryId) {
                const rowCategoryId = $(row).find('td:eq(1) .category-badge').data('category-id');
                valid = valid && (rowCategoryId == categoryId);
            }
            
            return valid;
        });
        
        table.draw(); // Tabloyu yenile
    });

    // Filtreleri temizle butonu
    $('#clearFilter').on('click', function() {
        $('#startDate').val('');
        $('#endDate').val('');
        $('#categoryFilter').val('');
        
        // Filtreleri temizle
        $.fn.dataTable.ext.search.pop();
        $('#expenseTable').DataTable().draw();
    });

    // Tarih kontrolü
    $('#startDate, #endDate').on('change', function() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (startDate && endDate) {
            if (new Date(startDate) > new Date(endDate)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Uyarı',
                    text: 'Başlangıç tarihi bitiş tarihinden büyük olamaz!'
                });
                $(this).val('');
            }
        }
    });

    // Başlangıçta çalıştır
    updateStats();
    initCharts();
}); 