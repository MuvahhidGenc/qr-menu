<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Media Modal -->
<div class="modal fade" id="mediaModal" tabindex="-1" data-bs-focus="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Medya Seçici</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
            
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#existingMedia">
                        <i class="fas fa-images me-2"></i>Mevcut Medya
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#uploadNew">
                        <i class="fas fa-upload me-2"></i>Yeni Yükle
                    </a>
                </li>
            </ul>
                
                <div class="tab-content">
                    <!-- Mevcut Medya -->
                    <div class="tab-pane fade show active" id="existingMedia">
                        <div class="row" id="mediaGrid">
                            <?php
                            $files = glob("../uploads/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
                            foreach($files as $file):
                                $filename = basename($file);
                            ?>
                            <div class="col-md-3 mb-3">
                                <div class="card media-item" onclick="selectMediaItem(this)" data-filename="<?= $filename ?>">
                                    <div class="position-relative">
                                        <img src="/qr-menu/uploads/<?= $filename ?>" class="card-img-top" 
                                            style="height:120px;object-fit:cover;">
                                        <!-- Seçim overlay -->
                                        <div class="position-absolute top-0 start-0 w-100 h-100 selected-overlay" style="display: none; background: rgba(13,110,253,0.3);">
                                            <div class="d-flex align-items-center justify-content-center h-100">
                                                <i class="fas fa-check-circle text-white fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-2 d-flex justify-content-between align-items-center">
                                        <small class="text-muted text-truncate" style="max-width: 120px;" title="<?= $filename ?>">
                                            <?= $filename ?>
                                        </small>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="deleteMedia('<?= $filename ?>', this, event)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Yeni Yükleme Tab'ı -->
                    <div class="tab-pane fade" id="uploadNew">
                        <form id="uploadForm">
                            <div class="mb-3">
                                <label>Dosya Seç</label>
                                <input type="file" class="form-control" name="files[]" accept="image/*" multiple required>
                            <div class="form-text">Birden fazla dosya seçebilirsiniz (Ctrl+tıklama ile)</div>
                                <small class="text-muted">İzin verilen formatlar: JPG, PNG, GIF</small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Yükle
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="selectMediaBtn" onclick="useSelectedMedia()" disabled>
                    <i class="fas fa-check me-1"></i>Seçileni Kullan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.media-item {
   cursor: pointer;
   transition: all 0.3s;
   border: 2px solid transparent;
}
.media-item:hover {
   transform: translateY(-3px);
   box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.media-item.selected {
   border: 2px solid #007bff;
   background-color: rgba(0, 123, 255, 0.1);
}

/* Tab stilleri */
.nav-tabs .nav-link {
   color: #333;
   font-weight: 500;
   padding: 10px 20px;
}

.nav-tabs .nav-link.active {
   color: #007bff;
   font-weight: 600;
}

/* Upload form stilleri */
#uploadForm {
   padding: 20px;
   background: #f8f9fa;
   border-radius: 5px;
}

#uploadForm label {
   font-weight: 500;
   margin-bottom: 8px;
   color: #333;
}

#uploadForm .form-control {
   border: 2px dashed #ddd;
   padding: 12px;
   background: white;
}

#uploadForm .btn-primary {
   margin-top: 10px;
   padding: 10px 25px;
}

/* Modal başlık ve footer stilleri */
.modal-header {
   background: #f8f9fa;
   border-bottom: 1px solid #dee2e6;
}

.modal-footer {
   background: #f8f9fa;
   border-top: 1px solid #dee2e6;
}

.modal-title {
   font-weight: 600;
   color: #333;
}
.media-item .btn-danger {
    padding: 2px 6px;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.3s;
}

.media-item:hover .btn-danger {
    opacity: 1;
}

.media-item .card-body {
    padding: 5px 8px !important;
}
</style>

<script>
let selectedMediaItem = null;

// Medya seçme fonksiyonu - tek tıkla otomatik seçim
function selectMediaItem(element) {
    const filename = element.dataset.filename;
    
    // Önceki seçimleri temizle
    document.querySelectorAll('.media-item').forEach(item => {
        item.style.border = 'none';
        const overlay = item.querySelector('.selected-overlay');
        if (overlay) overlay.style.display = 'none';
    });
    
    // Yeni seçimi işaretle
    element.style.border = '3px solid #0d6efd';
    const overlay = element.querySelector('.selected-overlay');
    if (overlay) overlay.style.display = 'block';
    
    selectedMediaItem = filename;
    
    // Otomatik kullan
    setTimeout(() => {
        useSelectedMedia();
    }, 200);
}

// Seçilen medyayı kullan
function useSelectedMedia() {
    if (selectedMediaItem) {
        // Ana sayfadaki selectMedia fonksiyonunu çağır
        if (typeof window.selectMedia === 'function') {
            window.selectMedia(selectedMediaItem);
        }
        
        // Modal'ı kapat
        const mediaModal = document.getElementById('mediaModal');
        const bsMediaModal = bootstrap.Modal.getInstance(mediaModal);
        if (bsMediaModal) {
            bsMediaModal.hide();
        }
        
        // Seçimi temizle
        selectedMediaItem = null;
    }
}

// Yeni dosya yükleme (çoklu dosya desteği)
$('#uploadForm').on('submit', function(e) {
    e.preventDefault();
    
    let formData = new FormData();
    let fileInput = $(this).find('input[type="file"]');
    
    if(fileInput[0].files.length > 0) {
        // Çoklu dosya desteği
        for (let i = 0; i < fileInput[0].files.length; i++) {
            formData.append('files[]', fileInput[0].files[i]);
        }
        
        $.ajax({
            url: 'upload_media.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                try {
                    if(response.success) {
                        // Çoklu dosya desteği
                        if(response.files && response.files.length > 0) {
                            // Çoklu dosya yüklendi
                            response.files.forEach(filename => {
                                let html = `
                                <div class="col-md-3 mb-3">
                                    <div class="card media-item" onclick="selectMediaItem(this)" data-filename="${filename}">
                                        <div class="position-relative">
                                            <img src="/qr-menu/uploads/${filename}" class="card-img-top" style="height:120px;object-fit:cover;">
                                            <!-- Seçim overlay -->
                                            <div class="position-absolute top-0 start-0 w-100 h-100 selected-overlay" style="display: none; background: rgba(13,110,253,0.3);">
                                                <div class="d-flex align-items-center justify-content-center h-100">
                                                    <i class="fas fa-check-circle text-white fs-1"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body p-2 d-flex justify-content-between align-items-center">
                                            <small class="text-muted text-truncate" style="max-width: 120px;" title="${filename}">
                                                ${filename}
                                            </small>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteMedia('${filename}', this, event)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>`;
                                $('#mediaGrid').prepend(html);
                            });
                            
                            // Başarı mesajı
                            alert(`${response.files.length} dosya başarıyla yüklendi!`);
                            
                            // Hata varsa göster
                            if(response.errors && response.errors.length > 0) {
                                alert('Bazı dosyalar yüklenemedi:\n' + response.errors.join('\n'));
                            }
                        }
                        // Tek dosya desteği (geriye uyumluluk)
                        else if(response.filename) {
                            let html = `
                            <div class="col-md-3 mb-3">
                                <div class="card media-item" onclick="selectMediaItem(this)" data-filename="${response.filename}">
                                    <div class="position-relative">
                                        <img src="/qr-menu/uploads/${response.filename}" class="card-img-top" style="height:120px;object-fit:cover;">
                                        <!-- Seçim overlay -->
                                        <div class="position-absolute top-0 start-0 w-100 h-100 selected-overlay" style="display: none; background: rgba(13,110,253,0.3);">
                                            <div class="d-flex align-items-center justify-content-center h-100">
                                                <i class="fas fa-check-circle text-white fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-2 d-flex justify-content-between align-items-center">
                                        <small class="text-muted text-truncate" style="max-width: 120px;" title="${response.filename}">
                                            ${response.filename}
                                        </small>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteMedia('${response.filename}', this, event)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>`;
                            $('#mediaGrid').prepend(html);
                            
                            alert('Dosya başarıyla yüklendi!');
                        }
                        
                        // Form'u temizle
                        $('#uploadForm')[0].reset();
                        
                        // Mevcut medya tab'ına geç
                        $('.nav-tabs a[href="#existingMedia"]').tab('show');
                        
                    } else {
                        alert('Hata: ' + response.message);
                    }
                } catch (error) {
                    alert('İşlem sırasında bir hata oluştu');
                    console.error(error);
                }
            },
            error: function(xhr, status, error) {
                alert('Sunucu hatası: ' + error);
            }
        });
    } else {
        alert('Lütfen bir dosya seçin');
    }
});

function deleteMedia(filename, element, event) {
    // Tıklama olayının yayılmasını engelle
    event.preventDefault();
    event.stopPropagation();

    if(confirm('Bu dosyayı silmek istediğinize emin misiniz?')) {
        $.ajax({
            url: 'delete_media.php',
            type: 'POST',
            data: { filename: filename },
            dataType: 'json', // JSON yanıt beklediğimizi belirt
            success: function(response) {
                if(response.success) {
                    // Medya öğesini grid'den kaldır
                    $(element).closest('.col-md-3').fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    // Eğer silinen dosya seçili dosya ise input'u temizle
                    if(selectedFile === filename) {
                        selectedFile = '';
                        if(targetInput) {
                            $(`#${targetInput}`).val('');
                            $(`#${targetInput}Preview`).attr('src', '').hide();
                        }
                    }
                } else {
                    alert(response.message || 'Silme işlemi başarısız oldu');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax Error:', error);
                console.log('Response:', xhr.responseText);
                alert('Silme işlemi sırasında bir hata oluştu: ' + error);
            }
        });
    }
    return false;
}


</script>