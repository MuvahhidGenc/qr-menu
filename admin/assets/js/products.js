// Hızlı ürün ekleme modal işlemleri
const quickAddModal = new bootstrap.Modal(document.getElementById('quickAddProductModal'));

document.querySelectorAll('.quick-add-product').forEach(button => {
    button.addEventListener('click', function() {
        const categoryId = this.dataset.categoryId;
        const categoryName = this.dataset.categoryName;
        
        document.getElementById('quick_category_id').value = categoryId;
        document.getElementById('categoryNameSpan').textContent = categoryName;
        
        quickAddModal.show();
    });
});

document.getElementById('quickAddProductForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('status', document.getElementById('quick_status').checked ? '1' : '0');
    
    try {
        const response = await fetch('ajax/add_product.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire('Başarılı!', 'Ürün başarıyla eklendi', 'success').then(() => {
                location.reload();
            });
        } else {
            throw new Error(result.message || 'Bir hata oluştu');
        }
    } catch (error) {
        Swal.fire('Hata!', error.message, 'error');
    }
}); 