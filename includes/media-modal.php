<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Media Modal -->
<div class="modal fade" id="mediaModal" tabindex="-1">
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
                                <div class="card media-item">
                                    <img src="/qr-menu/uploads/<?= $filename ?>" class="card-img-top" 
                                        style="height:120px;object-fit:cover;"
                                        onclick="selectMedia('<?= $filename ?>', this)">
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
                                <input type="file" class="form-control" name="file" accept="image/*" required>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" id="selectMediaBtn" onclick="useSelectedMedia()" disabled>Seç</button>
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

// Medya seçme fonksiyonu
function selectMediaItem(img) {
    const allImages = document.querySelectorAll('.media-item');
    allImages.forEach(img => img.style.border = 'none');
    
    img.style.border = '3px solid #0d6efd';
    selectedMediaItem = img.dataset.filename;
    
    document.getElementById('selectMediaBtn').disabled = false;
}

// Seçilen medyayı kullanma fonksiyonu
function useSelectedMedia() {
    if (selectedMediaItem) {
        // Ana sayfadaki selectMedia fonksiyonunu çağır
        window.parent.selectMedia(selectedMediaItem);
        
        // Modal'ı kapat
        const mediaModal = document.getElementById('mediaModal');
        const bsMediaModal = bootstrap.Modal.getInstance(mediaModal);
        if (bsMediaModal) {
            bsMediaModal.hide();
        }
        
        // Seçimi temizle
        selectedMediaItem = null;
        document.getElementById('selectMediaBtn').disabled = true;
    }
}

// Yeni dosya yükleme
$('#uploadForm').on('submit', function(e) {
    e.preventDefault();
    
    let formData = new FormData();
    let fileInput = $(this).find('input[type="file"]');
    
    if(fileInput[0].files.length > 0) {
        formData.append('file', fileInput[0].files[0]);
        
        $.ajax({
            url: 'upload_media.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                try {
                    if(response.success) {
                        // Medya grid'i güncelle
                        let html = `
                        <div class="col-md-3 mb-3">
                            <div class="card media-item" onclick="selectMedia('${response.filename}', this)">
                                <img src="/qr-menu/uploads/${response.filename}" class="card-img-top" style="height:120px;object-fit:cover;">
                                <div class="card-body p-2">
                                    <small class="text-muted">${response.filename}</small>
                                </div>
                            </div>
                        </div>`;
                        $('#mediaGrid').prepend(html);
                        
                        // Otomatik olarak yeni yüklenen dosyayı seç
                        selectMedia(response.filename, $('#mediaGrid').find('.media-item').first());
                        
                        // Form'u temizle
                        $('#uploadForm')[0].reset();
                        
                        // Başarı mesajı
                        alert('Dosya başarıyla yüklendi!');
                        
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