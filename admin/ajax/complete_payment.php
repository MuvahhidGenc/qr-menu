<?php
require_once '../../includes/config.php';

$db = new Database();

try {
    // JSON verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz veri formatı');
    }

    $tableId = $input['table_id'] ?? null;
    $paymentMethod = $input['payment_method'] ?? null;
    $totalAmount = $input['total_amount'] ?? 0;
    $subtotal = $input['subtotal'] ?? $totalAmount;
    $discountType = $input['discount_type'] ?? null;
    $discountValue = $input['discount_value'] ?? 0;
    $discountAmount = $input['discount_amount'] ?? 0;
    $isPartial = $input['is_partial'] ?? false;
    $partialData = $input['partial_data'] ?? null;
    
    $paidAmount = $totalAmount;
    
    if (!$tableId || !$paymentMethod) {
        throw new Exception('Gerekli alanlar eksik');
    }
    
    // Kısmi ödeme validasyonu
    if ($isPartial && !$partialData) {
        throw new Exception('Kısmi ödeme bilgileri eksik');
    }

    // Masanın durumunu kontrol et
    $tableCheck = $db->query(
        "SELECT status FROM tables WHERE id = ?",
        [$tableId]
    )->fetch();

    $db->beginTransaction();

    try {
        // Masadaki aktif siparişleri bul
        $orders = $db->query(
            "SELECT id, total_amount FROM orders 
             WHERE table_id = ? 
             AND status NOT IN ('completed', 'cancelled')
             AND payment_id IS NULL",
            [$tableId]
        )->fetchAll();

        // Kısmi ödeme notunu hazırla
        $paymentNote = $isPartial ? json_encode($partialData, JSON_UNESCAPED_UNICODE) : null;

        // Ödeme kaydı oluştur
        $db->query(
            "INSERT INTO payments (
                table_id, 
                payment_method, 
                total_amount,
                subtotal,
                paid_amount,
                discount_type,
                discount_value, 
                discount_amount,
                payment_note,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())",
            [
                $tableId,
                $paymentMethod,
                $totalAmount,
                $subtotal,
                $paidAmount,
                $discountType,
                $discountValue,
                $discountAmount,
                $paymentNote
            ]
        );
        
        $payment_id = $db->lastInsertId();

        // Kısmi ödeme işlemleri
        if ($isPartial) {
            if ($partialData['type'] === 'product') {
                // Ürün bazlı: Önce ödenen ürünleri payment_id ile işaretle
                $items = $partialData['items'];
                
                error_log("Partial payment - product type. Items: " . json_encode($items));
                
                if (!empty($items)) {
                    // Önce tüm ödenen ürünleri payment_id ile işaretle
                    foreach ($items as $item) {
                        $itemId = is_array($item) ? $item['id'] : $item;
                        $paidQty = is_array($item) ? ($item['quantity'] ?? 1) : 1;
                        
                        error_log("Processing item: ID=$itemId, PaidQty=$paidQty");
                        
                        // Mevcut ürün bilgisini al
                        $currentItem = $db->query(
                            "SELECT quantity, order_id, product_id, price FROM order_items WHERE id = ?",
                            [$itemId]
                        )->fetch();
                        
                        if ($currentItem) {
                            $remainingQty = $currentItem['quantity'] - $paidQty;
                            
                            error_log("Current qty: {$currentItem['quantity']}, Remaining: $remainingQty");
                            
                            // Ödenen kısmı yeni satır olarak kaydet (ödeme kaydı için)
                            $db->query(
                                "INSERT INTO order_items (order_id, product_id, quantity, price, payment_id, created_at)
                                 VALUES (?, ?, ?, ?, ?, NOW())",
                                [$currentItem['order_id'], $currentItem['product_id'], $paidQty, $currentItem['price'], $payment_id]
                            );
                            error_log("Created paid item record: Qty=$paidQty");
                            
                            if ($remainingQty <= 0) {
                                // Tümü ödendi - masadan kaldır
                                $db->query("DELETE FROM order_items WHERE id = ?", [$itemId]);
                                error_log("Deleted item $itemId from table (fully paid)");
                            } else {
                                // Kısmi ödeme - miktarı azalt
                                $db->query(
                                    "UPDATE order_items SET quantity = ? WHERE id = ?",
                                    [$remainingQty, $itemId]
                                );
                                error_log("Reduced item $itemId quantity to $remainingQty");
                            }
                        } else {
                            error_log("Item $itemId not found!");
                        }
                    }
                    
                    // Boş kalan siparişleri temizle
                    $emptyOrders = $db->query(
                        "SELECT o.id FROM orders o
                         LEFT JOIN order_items oi ON o.id = oi.order_id AND oi.payment_id IS NULL
                         WHERE o.table_id = ? 
                         AND o.payment_id IS NULL
                         GROUP BY o.id
                         HAVING COUNT(oi.id) = 0",
                        [$tableId]
                    )->fetchAll();
                    
                    foreach ($emptyOrders as $order) {
                        $db->query(
                            "UPDATE orders SET status = 'completed', payment_id = ?, completed_at = NOW() WHERE id = ?",
                            [$payment_id, $order['id']]
                        );
                    }
                    
                    error_log("Cleaned up empty orders for table $tableId");
                }
            } else {
                // Tutar bazlı: Ödenen tutarı ürünlere orantılı dağıt
                $paidAmount = $partialData['amount'];
                
                // Masadaki tüm ödenmemiş ürünleri ve toplam tutarı al
                $allItems = $db->query(
                    "SELECT oi.id, oi.quantity, oi.price, (oi.quantity * oi.price) as item_total
                     FROM order_items oi
                     INNER JOIN orders o ON oi.order_id = o.id
                     WHERE o.table_id = ? 
                     AND o.payment_id IS NULL
                     AND oi.payment_id IS NULL
                     AND o.status NOT IN ('completed', 'cancelled')",
                    [$tableId]
                )->fetchAll();
                
                if (!empty($allItems)) {
                    // Toplam tutarı hesapla
                    $totalAmount = 0;
                    foreach ($allItems as $item) {
                        $totalAmount += $item['item_total'];
                    }
                    
                    error_log("Amount-based partial payment: Paid=$paidAmount, Total=$totalAmount");
                    
                    // Her ürüne orantılı indirim uygula
                    foreach ($allItems as $item) {
                        // Bu ürünün payı = (ürün tutarı / toplam tutar) * ödenen tutar
                        $itemShare = ($item['item_total'] / $totalAmount) * $paidAmount;
                        
                        // Yeni birim fiyat = eski fiyat - (pay / miktar)
                        $priceReduction = $itemShare / $item['quantity'];
                        $newPrice = $item['price'] - $priceReduction;
                        
                        // Negatif olmasın
                        if ($newPrice < 0) $newPrice = 0;
                        
                        error_log("Item {$item['id']}: OldPrice={$item['price']}, Share=$itemShare, Reduction=$priceReduction, NewPrice=$newPrice");
                        
                        // Fiyatı güncelle
                        $db->query(
                            "UPDATE order_items SET price = ? WHERE id = ?",
                            [$newPrice, $item['id']]
                        );
                    }
                }
            }
            
            // Masada hala ürün var mı kontrol et
            $remainingItems = $db->query(
                "SELECT COUNT(*) as count FROM order_items oi
                 INNER JOIN orders o ON oi.order_id = o.id
                 WHERE o.table_id = ? AND o.payment_id IS NULL",
                [$tableId]
            )->fetch();
            
            // Kalan toplam tutarı hesapla - sadece ödenmemiş ürünler
            $orderTotal = $db->query(
                "SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total
                 FROM order_items oi
                 INNER JOIN orders o ON oi.order_id = o.id
                 WHERE o.table_id = ? 
                 AND o.payment_id IS NULL 
                 AND oi.payment_id IS NULL",
                [$tableId]
            )->fetch();
            
            $remainingTotal = [
                'total' => max(0, $orderTotal['total'])
            ];
            
            if ($remainingItems['count'] == 0) {
                // Hiç ürün kalmadı, masayı boşalt
                $db->query("UPDATE tables SET status = 'empty' WHERE id = ?", [$tableId]);
            } else {
                // Ürün var, masa dolu kalsın
                $db->query("UPDATE tables SET status = 'occupied' WHERE id = ?", [$tableId]);
            }
            
            $db->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Kısmi ödeme alındı',
                'remaining' => $remainingTotal['total']
            ]);
        } else {
            // Tam ödeme: Tüm siparişleri tamamla
        foreach($orders as $order) {
            $db->query(
                "UPDATE orders SET 
                    status = 'completed',
                    payment_id = ?,
                    completed_at = NOW()
                WHERE id = ?",
                [$payment_id, $order['id']]
            );
        }

            // Tam ödeme için tüm order_items'a payment_id ata
        $db->query(
                "UPDATE order_items oi
                 INNER JOIN orders o ON oi.order_id = o.id
                 SET oi.payment_id = ?
                 WHERE o.table_id = ? 
                 AND o.payment_id = ?
                 AND oi.payment_id IS NULL",
                [$payment_id, $tableId, $payment_id]
            );

            // Masayı boşalt
            $db->query("UPDATE tables SET status = 'empty' WHERE id = ?", [$tableId]);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Ödeme başarıyla tamamlandı']);
        }

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    if (isset($db)) {
        try {
            $db->rollBack();
        } catch (Exception $rollbackError) {
            // Rollback hatası görmezden gelinebilir
        }
    }
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}