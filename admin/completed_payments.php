<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolleri - Görüntüleme yetkisi için payments.view_completed VEYA payments.view yetkisi yeterli olsun
$canViewCompletedPayments = hasPermission('payments.view_completed') || hasPermission('payments.view');
$canCancelPayment = hasPermission('payments.cancel');
$canReorderToTable = hasPermission('payments.reorder');

// Sadece görüntüleme yetkisi kontrolü
if (!$canViewCompletedPayments) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();

// Tamamlanmış ödemeleri çek - düzeltilmiş sorgu
$payments = $db->query("
    SELECT 
        p.id as payment_id,
        p.table_id,
        p.payment_method,
        p.total_amount,
        p.subtotal,
        p.paid_amount,
        p.payment_note,
        p.status,
        p.created_at,
        p.discount_type,
        p.discount_value,
        p.discount_amount,
        t.table_no,
        GROUP_CONCAT(
            CONCAT(
                oi.quantity, 'x ',
                pr.name,
                '|',
                oi.price
            ) SEPARATOR '||'
        ) as order_details
    FROM payments p
    LEFT JOIN tables t ON p.table_id = t.id
    LEFT JOIN orders o ON p.id = o.payment_id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products pr ON oi.product_id = pr.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alınmış Ödemeler - QR Menü Admin</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS'lerini güvenli CDN'lere taşıyalım -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-buttons-bs5/2.2.2/buttons.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.95em;
        }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .amount-detail {
            font-weight: bold;
            color: #495057;
        }
        .payment-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .payment-table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 12px;
            font-size: 1em;
        }
        .payment-table tbody td {
            padding: 8px 12px;
            vertical-align: middle;
            font-size: 0.95em;
        }
        .amount-badge {
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.95em;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            min-width: 80px;
        }
        .currency-symbol {
            margin-left: 2px;
            display: inline-block;
            vertical-align: middle;
        }
        .discount-badge {
            background: #ffeeba;
            color: #856404;
        }
        .total-badge {
            background: #d4edda;
            color: #155724;
        }
        .print-receipt {
            cursor: pointer;
            color: #0d6efd;
        }
        .print-receipt:hover {
            color: #0a58ca;
        }
        .dataTables_wrapper {
            font-size: 0.95em;
        }
        @media screen and (max-width: 768px) {
            .payment-table tbody td {
                padding: 6px 8px;
            }
            
            .amount-badge {
                min-width: 70px;
                padding: 3px 6px;
            }
            
            .status-badge {
                padding: 3px 8px;
            }
        }
        .payment-table td {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        td.order-details {
            white-space: normal;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Alınmış Ödemeler</h3>
                        <div class="btn-group">
                            <button class="btn btn-success btn-sm excel-btn">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button class="btn btn-danger btn-sm pdf-btn ms-2">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button class="btn btn-primary btn-sm print-btn ms-2">
                                <i class="fas fa-print"></i> Yazdır
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive payment-table">
                            <table id="paymentsTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Masa</th>
                                        <th>Toplam Tutar</th>
                                        <th>İskonto</th>
                                        <th>Ödenen</th>
                                        <th>Yöntem</th>
                                        <th class="order-details">Sipariş Detayı</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= date('d.m.Y H:i', strtotime($payment['created_at'])) ?></td>
                                        <td>Masa <?= $payment['table_no'] ?></td>
                                        <td>
                                            <span class="amount-badge total-badge">
                                                <?= number_format($payment['subtotal'], 2) ?> ₺
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($payment['discount_amount'] > 0): ?>
                                                <span class="amount-badge discount-badge">
                                                    <?= number_format($payment['discount_amount'], 2) ?> ₺
                                                    (<?= $payment['discount_type'] == 'percent' ? '%'.$payment['discount_value'] : number_format($payment['discount_value'], 2).' ₺' ?>)
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="amount-badge">
                                                <?= number_format($payment['total_amount'], 2) ?><span class="currency-symbol">₺</span>
                                            </span>
                                        </td>
                                        <td><?= $payment['payment_method'] == 'cash' ? 'Nakit' : 'POS' ?></td>
                                        <td class="order-details"><?= $payment['order_details'] ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $payment['status'] ?>">
                                                <?= $payment['status'] == 'completed' ? 'Tamamlandı' : 'İptal Edildi' ?>
                                            </span>
                                            <?php if ($payment['status'] == 'cancelled' && $payment['payment_note']): ?>
                                                <br>
                                                <small class="text-danger">
                                                    <i class="fas fa-info-circle"></i> 
                                                    <?= htmlspecialchars($payment['payment_note']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-print print-receipt" onclick="printReceipt(<?= htmlspecialchars(json_encode($payment)) ?>)" title="Fiş Yazdır"></i>
                                            <?php if ($payment['status'] == 'cancelled' && $canReorderToTable): ?>
                                                <button class="btn btn-sm btn-warning ms-2" onclick="reorderToTable(<?= $payment['payment_id'] ?>, <?= $payment['table_id'] ?>, '<?= $payment['table_no'] ?>')">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($payment['status'] == 'completed' && $canCancelPayment): ?>
                                                <button class="btn btn-sm btn-danger ms-2" onclick="cancelPayment(<?= $payment['payment_id'] ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- DataTables JS'lerini güvenli CDN'lere taşıyalım -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.2.2/js/buttons.print.min.js"></script>

    <script>
    // Yetki değişkenlerini JavaScript'e aktar
    const userPermissions = {
        canCancelPayment: <?php echo $canCancelPayment ? 'true' : 'false' ?>,
        canReorderToTable: <?php echo $canReorderToTable ? 'true' : 'false' ?>
    };

    $(document).ready(function() {
        // Excel butonlarını aktif et
        $('.excel-btn').on('click', function() {
            $('#paymentsTable').DataTable().button('0').trigger();
        });

        $('.pdf-btn').on('click', function() {
            $('#paymentsTable').DataTable().button('1').trigger();
        });

        $('.print-btn').on('click', function() {
            $('#paymentsTable').DataTable().button('2').trigger();
        });

        // DataTables Türkçe dil tanımlaması
        const turkishLanguage = {
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
            }
        };

        // DataTables başlatma
        $('#paymentsTable').DataTable({
            language: turkishLanguage,
            order: [[0, 'desc']],
            pageLength: 25,
            buttons: [
                {
                    extend: 'excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
                    },
                    title: 'Ödemeler Raporu - ' + new Date().toLocaleDateString('tr-TR')
                },
                {
                    extend: 'pdf',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
                    },
                    title: 'Ödemeler Raporu - ' + new Date().toLocaleDateString('tr-TR')
                },
                {
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
                    },
                    title: 'Ödemeler Raporu'
                }
            ]
        });
    });

    // Ödeme İptali
    function cancelPayment(paymentId) {
        if (!userPermissions.canCancelPayment) {
            Swal.fire('Yetkisiz İşlem', 'Ödeme iptal etme yetkiniz bulunmuyor!', 'error');
            return;
        }

        Swal.fire({
            title: 'Ödeme İptal',
            html: `
                <div class="mb-3">
                    <label for="cancelNote" class="form-label">İptal Nedeni</label>
                    <textarea id="cancelNote" class="form-control" rows="3" placeholder="İptal nedenini açıklayın"></textarea>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, İptal Et',
            cancelButtonText: 'Vazgeç',
            preConfirm: () => {
                const note = document.getElementById('cancelNote').value;
                if (!note.trim()) {
                    Swal.showValidationMessage('İptal nedeni girmelisiniz');
                    return false;
                }
                return note;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/cancel_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_id: paymentId,
                        cancel_note: result.value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Başarılı!', 'Ödeme iptal edildi.', 'success')
                        .then(() => location.reload());
                    } else {
                        throw new Error(data.message || 'Bir hata oluştu');
                    }
                })
                .catch(error => {
                    Swal.fire('Hata!', error.message, 'error');
                });
            }
        });
    }

    // Masaya Tekrar Ekleme
    function reorderToTable(paymentId, tableId, tableNo) {
        if (!userPermissions.canReorderToTable) {
            Swal.fire('Yetkisiz İşlem', 'Siparişleri masaya aktarma yetkiniz bulunmuyor!', 'error');
            return;
        }

        Swal.fire({
            title: 'Emin misiniz?',
            text: `Masa ${tableNo}'e iptal edilmiş siparişler tekrar eklenecek. Onaylıyor musunuz?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Ekle',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/reorder_to_table.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_id: paymentId,
                        table_id: tableId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Başarılı!', 'Siparişler masaya eklendi.', 'success')
                        .then(() => location.reload());
                    } else {
                        throw new Error(data.message || 'Bir hata oluştu');
                    }
                })
                .catch(error => {
                    Swal.fire('Hata!', error.message, 'error');
                });
            }
        });
    }

    // Fiş yazdırma fonksiyonu
    function printReceipt(payment) {
        // Sipariş detaylarını düzenli formata çevir
        const orderItems = payment.order_details.split('||').map(item => {
            const [quantityAndName, price] = item.split('|');
            const [quantity, name] = quantityAndName.split('x ');
            return {
                quantity: parseInt(quantity.trim()),
                name: name.trim(),
                price: parseFloat(price),
                total: parseInt(quantity.trim()) * parseFloat(price)
            };
        });

        const receiptContent = `
            <div style="font-family: 'Courier New', monospace; max-width: 300px; margin: 0 auto; padding: 10px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;">RESTORAN ADI</h3>
                    <p style="margin: 5px 0;">Fiş No: #${payment.payment_id}</p>
                    <p style="margin: 5px 0;">Tarih: ${new Date(payment.created_at).toLocaleString('tr-TR')}</p>
                    <p style="margin: 5px 0;">Masa: ${payment.table_no}</p>
                </div>

                <div style="margin-bottom: 20px;">
                    <div style="border-bottom: 1px dashed #000; margin-bottom: 10px;">
                        <div style="display: grid; grid-template-columns: 30px auto 70px; gap: 10px; margin-bottom: 5px;">
                            <div style="font-weight: bold;">Adet</div>
                            <div style="font-weight: bold;">Ürün</div>
                            <div style="font-weight: bold; text-align: right;">Tutar</div>
                        </div>
                    </div>
                    ${orderItems.map(item => `
                        <div style="display: grid; grid-template-columns: 30px auto 70px; gap: 10px; margin: 5px 0;">
                            <div>${item.quantity}</div>
                            <div>${item.name}</div>
                            <div style="text-align: right;">${item.total.toFixed(2)} ₺</div>
                        </div>
                    `).join('')}
                </div>

                <div style="border-top: 1px dashed #000; padding-top: 10px;">
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Ara Toplam:</span>
                        <span>${parseFloat(payment.subtotal).toFixed(2)} ₺</span>
                    </div>

                    ${payment.discount_amount > 0 ? `
                        <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                            <span>İskonto ${payment.discount_type === 'percent' ? '(%' + payment.discount_value + ')' : ''}:</span>
                            <span>-${parseFloat(payment.discount_amount).toFixed(2)} ₺</span>
                        </div>
                    ` : ''}

                    <div style="display: flex; justify-content: space-between; margin: 5px 0; font-weight: bold; font-size: 1.1em;">
                        <span>Genel Toplam:</span>
                        <span>${parseFloat(payment.total_amount).toFixed(2)} ₺</span>
                    </div>

                    <div style="margin-top: 10px; font-size: 0.9em;">
                        <p style="margin: 5px 0;">Ödeme Yöntemi: ${payment.payment_method === 'cash' ? 'Nakit' : 'Kredi Kartı'}</p>
                        ${payment.status === 'cancelled' ? `
                            <div style="margin-top: 10px; padding: 10px; background: #f8d7da; color: #721c24; border-radius: 5px;">
                                <strong>İPTAL EDİLDİ</strong><br>
                                İptal Nedeni: ${payment.payment_note || 'Belirtilmedi'}
                            </div>
                        ` : ''}
                    </div>

                    <div style="margin-top: 20px; text-align: center; font-size: 0.8em;">
                        <p style="margin: 5px 0;">Bizi tercih ettiğiniz için teşekkür ederiz!</p>
                        <p style="margin: 5px 0;">İyi günler dileriz.</p>
                    </div>
                </div>
            </div>
        `;

        const printWindow = window.open('', '', 'width=300,height=600');
        printWindow.document.write(`
            <html>
            <head>
                <title>Fiş #${payment.payment_id}</title>
                <meta charset="UTF-8">
                <style>
                    @media print {
                        body { margin: 0; padding: 10px; }
                        @page { margin: 0; }
                    }
                </style>
            </head>
            <body>
                ${receiptContent}
                <script>
                    window.onload = function() {
                        window.print();
                        window.onafterprint = function() {
                            window.close();
                        }
                    }
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
    </script>
</body>
</html>