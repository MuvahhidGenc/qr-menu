<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once 'includes/print_helper.php';

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

// Yazıcı ayarlarını al
$printerSettings = [];
$settingsQuery = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'printer_%'");
$results = $settingsQuery->fetchAll();

foreach ($results as $row) {
    $printerSettings[$row['setting_key']] = $row['setting_value'];
}

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

// Restoran adını settings tablosundan al
$restaurantName = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'restaurant_name'")->fetch()['setting_value'];
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
        /* Modern Dashboard Styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            margin: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        /* Modern Header */
        .modern-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            margin: -30px -30px 30px -30px;
            position: relative;
            overflow: hidden;
        }

        .modern-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 20px,
                rgba(255,255,255,0.05) 20px,
                rgba(255,255,255,0.05) 40px
            );
            animation: movePattern 20s linear infinite;
        }

        @keyframes movePattern {
            0% { transform: translateX(-100px); }
            100% { transform: translateX(100px); }
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        /* Stats Cards */
        .stats-row {
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-icon.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.success { background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%); }
        .stat-icon.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Modern Buttons */
        .modern-btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .modern-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .modern-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
            color: white;
        }

        .modern-btn.success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            box-shadow: 0 4px 15px rgba(86, 171, 47, 0.4);
        }

        .modern-btn.success:hover {
            box-shadow: 0 8px 25px rgba(86, 171, 47, 0.6);
        }

        .modern-btn.danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);
        }

        .modern-btn.danger:hover {
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.6);
        }

        /* Modern Table */
        .modern-table-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 2rem;
            overflow: hidden;
        }

        .modern-table {
            width: 100%;
            margin: 0;
        }

        .modern-table thead th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #495057;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modern-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .modern-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .modern-table tbody td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border: none;
            font-size: 0.9rem;
        }

        /* Status Badges */
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-completed { 
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            box-shadow: 0 2px 8px rgba(21, 87, 36, 0.2);
        }

        .status-cancelled { 
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            box-shadow: 0 2px 8px rgba(114, 28, 36, 0.2);
        }

        /* Amount Badges */
        .amount-badge {
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .amount-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .discount-badge {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            color: #856404;
            box-shadow: 0 2px 8px rgba(133, 100, 4, 0.2);
        }

        .total-badge {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 184, 148, 0.3);
        }

        /* Action Buttons */
        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .action-btn.print {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(116, 185, 255, 0.4);
        }

        .action-btn.reorder {
            background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(253, 203, 110, 0.4);
        }

        .action-btn.cancel {
            background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(253, 121, 168, 0.4);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        /* Order Details */
        .order-details {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            line-height: 1.4;
            max-width: 300px;
            white-space: normal;
        }

        .order-item {
            margin-bottom: 4px;
            padding: 4px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .order-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        /* DataTables Customization */
        .dataTables_wrapper {
            padding: 0;
        }

        .dataTables_filter {
            margin-bottom: 1.5rem;
        }

        .dataTables_filter input {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 10px 15px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .dataTables_filter input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .dataTables_length select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .dataTables_paginate .paginate_button {
            border: none;
            background: none;
            color: #667eea;
            font-weight: 600;
            padding: 8px 12px;
            margin: 0 2px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .dataTables_paginate .paginate_button:hover {
            background: #667eea;
            color: white;
        }

        .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* Mobile Responsive */
        @media (max-width: 1200px) {
            .dashboard-container {
                margin: 10px;
                padding: 20px;
            }
            
            .modern-header {
                margin: -20px -20px 20px -20px;
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                margin: 5px;
                padding: 15px;
            }
            
            .modern-header {
                margin: -15px -15px 15px -15px;
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .page-subtitle {
                font-size: 1rem;
            }
            
            .modern-btn-group {
                justify-content: center;
                flex-direction: column;
                gap: 8px;
            }
            
            .modern-btn {
                padding: 10px 16px;
                font-size: 0.8rem;
                width: 100%;
                max-width: 200px;
            }
            
            .modern-table-container {
                padding: 0.5rem;
                overflow-x: auto;
            }
            
            /* Mobile Table - Card Layout */
            .modern-table {
                min-width: 100%;
            }
            
            .modern-table thead {
                display: none; /* Hide table headers on mobile */
            }
            
            .modern-table tbody tr {
                display: block;
                background: white;
                border: 1px solid #e9ecef;
                border-radius: 12px;
                margin-bottom: 1rem;
                padding: 1rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .modern-table tbody td {
                display: block;
                border: none;
                padding: 0.5rem 0;
                text-align: left;
                position: relative;
                padding-left: 50%;
                border-bottom: 1px solid #f8f9fa;
            }
            
            .modern-table tbody td:last-child {
                border-bottom: none;
            }
            
            .modern-table tbody td:before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
                color: #495057;
                font-size: 0.8rem;
            }
            
            .amount-badge {
                min-width: auto;
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .status-badge {
                padding: 6px 12px;
                font-size: 0.75rem;
            }
            
            .action-btn {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
                margin: 2px;
            }
            
            .order-details {
                max-width: none;
                font-size: 0.75rem;
                padding: 8px;
                margin-top: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .stats-row {
                margin-bottom: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
                text-align: center;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
                margin: 0 auto 0.75rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .modern-table tbody td {
                padding-left: 0;
                text-align: center;
            }
            
            .modern-table tbody td:before {
                position: static;
                display: block;
                width: 100%;
                text-align: center;
                margin-bottom: 0.25rem;
                font-size: 0.75rem;
                color: #6c757d;
            }
            
            .modern-btn {
                font-size: 0.75rem;
                padding: 8px 12px;
            }
        }

        /* Accordion Styles */
        /* Payment card styles - Bootstrap accordion yerine custom */
        .payment-card {
            border: none;
            margin-bottom: 1rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }

        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .payment-header {
            padding: 20px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .payment-details {
            padding: 0 20px 20px 20px;
            background: #f8f9fa;
        }
        
        .toggle-icon {
            transition: transform 0.3s ease;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .accordion-button {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: none;
            padding: 1.5rem;
            font-weight: 600;
            border-radius: 15px;
            box-shadow: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: none;
        }

        .accordion-button:focus {
            border: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
        }

        .accordion-button:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
        }

        .accordion-button:not(.collapsed):hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }

        .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23667eea'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
            transition: transform 0.2s ease;
        }

        .accordion-button:not(.collapsed)::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        }

        .accordion-body {
            background: #f8f9fa;
            padding: 2rem;
        }

        /* Payment Summary Styles */
        .payment-summary {
            pointer-events: auto;
        }

        .payment-summary .row > div {
            margin-bottom: 0.5rem;
        }

        .payment-date, .payment-table, .payment-amount, .payment-status {
            text-align: left;
        }

        .payment-actions {
            pointer-events: all;
            text-align: right;
        }

        /* Action buttons inside accordion should not interfere */
        .accordion-body .action-btn {
            pointer-events: all;
        }

        /* Detail Rows */
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row .label {
            font-weight: 600;
            color: #495057;
        }

        .detail-row .value {
            font-weight: 500;
        }

        /* Order Items Expanded */
        .order-item-expanded {
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border-left: 4px solid #667eea;
        }

        .order-item-expanded:last-child {
            margin-bottom: 0;
        }

        .item-name {
            color: #495057;
        }

        .item-price {
            color: #28a745;
        }

        /* Mobile Filter Buttons */
        .mobile-filter-btn {
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .mobile-filter-btn.active {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Custom Form Controls */
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        /* Mobile Responsive for Accordion */
        @media (max-width: 768px) {
            .accordion-button {
                padding: 1rem;
            }

            .accordion-body {
                padding: 1rem;
            }

            .payment-summary .row > div {
                margin-bottom: 0.75rem;
            }

            .payment-date, .payment-table, .payment-amount, .payment-status {
                text-align: center;
            }

            .payment-actions {
                text-align: center;
            }

            .action-btn {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
                margin: 0 2px;
            }

            .mobile-filter-btn {
                font-size: 0.85rem;
                padding: 8px 12px;
            }
        }

        @media (max-width: 576px) {
            .accordion-button {
                padding: 0.75rem;
                font-size: 0.9rem;
            }

            .payment-summary .col-md-2,
            .payment-summary .col-md-3 {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }

            .detail-row .value {
                margin-top: 0.25rem;
                font-size: 0.9rem;
            }
        }

        /* Animation Classes */
        .animate-slide-up {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="dashboard-container animate-slide-up">
        <!-- Modern Header -->
        <div class="modern-header">
            <div class="header-content">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="page-title">
                            <i class="fas fa-credit-card me-3"></i>
                            Alınmış Ödemeler
                        </h1>
                        <p class="page-subtitle">
                            Tamamlanmış ve iptal edilmiş ödemelerin detaylı listesi
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="modern-btn-group">
                            <button class="modern-btn success excel-btn">
                                <i class="fas fa-file-excel"></i>
                                <span>Excel</span>
                            </button>
                            <button class="modern-btn danger pdf-btn">
                                <i class="fas fa-file-pdf"></i>
                                <span>PDF</span>
                            </button>
                            <button class="modern-btn print-btn">
                                <i class="fas fa-print"></i>
                                <span>Yazdır</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <?php
        // İstatistikleri hesapla
        $totalPayments = count($payments);
        $completedPayments = count(array_filter($payments, function($p) { return $p['status'] == 'completed'; }));
        $cancelledPayments = count(array_filter($payments, function($p) { return $p['status'] == 'cancelled'; }));
        
        // Toplam geliri doğru hesapla - paid_amount kullan
        $totalRevenue = 0;
        foreach ($payments as $payment) {
            if ($payment['status'] == 'completed') {
                // paid_amount varsa onu kullan, yoksa subtotal - discount_amount hesapla
                if (!empty($payment['paid_amount']) && $payment['paid_amount'] > 0) {
                    $totalRevenue += floatval($payment['paid_amount']);
                } else {
                    $subtotal = floatval($payment['subtotal'] ?? 0);
                    $discount = floatval($payment['discount_amount'] ?? 0);
                    $totalRevenue += ($subtotal - $discount);
                }
            }
        }
        
        // İstatistikler hazır
        ?>
        <div class="row stats-row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card animate-slide-up">
                    <div class="stat-icon primary">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-number"><?= $totalPayments ?></div>
                    <div class="stat-label">Toplam Ödeme</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card animate-slide-up">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $completedPayments ?></div>
                    <div class="stat-label">Tamamlanan</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card animate-slide-up">
                    <div class="stat-icon warning">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-number"><?= $cancelledPayments ?></div>
                    <div class="stat-label">İptal Edilen</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card animate-slide-up">
                    <div class="stat-icon info">
                        <i class="fas fa-lira-sign"></i>
                    </div>
                    <div class="stat-number"><?= number_format($totalRevenue, 0, ',', '.') ?>₺</div>
                    <div class="stat-label">Toplam Gelir</div>
                </div>
            </div>
        </div>

        <!-- Desktop: Normal Table, Mobile: Accordion -->
        <div class="modern-table-container animate-slide-up">
            <!-- Desktop Table View -->
            <div class="desktop-table-view d-none d-md-block">
                <!-- Search and Filter Controls -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="dateFilter" class="form-label">Tarih Filtresi:</label>
                            <input type="date" id="dateFilter" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="statusFilter" class="form-label">Durum Filtresi:</label>
                            <select id="statusFilter" class="form-control">
                                <option value="">Tümü</option>
                                <option value="completed">Tamamlandı</option>
                                <option value="cancelled">İptal Edildi</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tableFilter" class="form-label">Masa Filtresi:</label>
                            <input type="text" id="tableFilter" class="form-control" placeholder="Masa numarası">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="paymentsTable" class="table table-hover modern-table">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Masa</th>
                                        <th>Toplam Tutar</th>
                                        <th>İskonto</th>
                                        <th>Ödenen</th>
                                        <th>Yöntem</th>
                                <th>Sipariş Detayı</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold"><?= date('d.m.Y', strtotime($payment['created_at'])) ?></span>
                                    <small class="text-muted"><?= date('H:i', strtotime($payment['created_at'])) ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon primary" style="width: 35px; height: 35px; font-size: 0.8rem; margin-right: 10px;">
                                        <i class="fas fa-chair"></i>
                                    </div>
                                    <span class="fw-bold"><?= $payment['table_no'] ?></span>
                                </div>
                            </td>
                                        <td>
                                            <span class="amount-badge total-badge">
                                    <?= number_format(floatval($payment['subtotal']), 2) ?> ₺
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($payment['discount_amount'] > 0): ?>
                                                <span class="amount-badge discount-badge">
                                        -<?= number_format(floatval($payment['discount_amount']), 2) ?> ₺
                                                </span>
                                    <br>
                                    <small class="text-muted">
                                        (<?= $payment['discount_type'] == 'percent' ? '%'.$payment['discount_value'] : number_format(floatval($payment['discount_value']), 2).' ₺' ?>)
                                    </small>
                                            <?php else: ?>
                                    <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                <?php 
                // Ödenen tutarı hesapla - önce paid_amount alanını kontrol et
                $paidAmount = 0;
                if (!empty($payment['paid_amount']) && $payment['paid_amount'] > 0) {
                    // Veritabanında paid_amount varsa onu kullan
                    $paidAmount = floatval($payment['paid_amount']);
                } else {
                    // Yoksa hesapla: subtotal - discount_amount
                    $subtotal = floatval($payment['subtotal'] ?? 0);
                    $discount = floatval($payment['discount_amount'] ?? 0);
                    $paidAmount = $subtotal - $discount;
                }
                                ?>
                                <span class="amount-badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                    <?= number_format($paidAmount, 2) ?> ₺
                                            </span>
                                        </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-<?= $payment['payment_method'] == 'cash' ? 'money-bill' : 'credit-card' ?> me-2 text-<?= $payment['payment_method'] == 'cash' ? 'success' : 'primary' ?>"></i>
                                    <span><?= $payment['payment_method'] == 'cash' ? 'Nakit' : 'POS' ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="order-details">
                                    <?php 
                                    if (!empty($payment['order_details'])):
                                        $orderItems = explode('||', $payment['order_details']);
                                        foreach($orderItems as $item):
                                            if (empty(trim($item))) continue;
                                            $itemParts = explode('|', $item);
                                            if (count($itemParts) >= 2):
                                                $quantityAndName = $itemParts[0];
                                                $price = floatval($itemParts[1]);
                                    ?>
                                    <div class="order-item">
                                        <div class="d-flex justify-content-between">
                                            <span><?= htmlspecialchars($quantityAndName) ?></span>
                                            <span class="fw-bold"><?= number_format($price, 2) ?> ₺</span>
                                        </div>
                                    </div>
                                    <?php 
                                            endif;
                                        endforeach; 
                                    else:
                                    ?>
                                        <span class="text-muted">Detay yok</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                                        <td>
                                            <span class="status-badge status-<?= $payment['status'] ?>">
                                    <i class="fas fa-<?= $payment['status'] == 'completed' ? 'check' : 'times' ?>"></i>
                                                <?= $payment['status'] == 'completed' ? 'Tamamlandı' : 'İptal Edildi' ?>
                                            </span>
                                            <?php if ($payment['status'] == 'cancelled' && $payment['payment_note']): ?>
                                                <br>
                                    <small class="text-danger mt-1">
                                                    <i class="fas fa-info-circle"></i> 
                                                    <?= htmlspecialchars($payment['payment_note']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                <div class="d-flex align-items-center gap-2">
                                    <button class="action-btn print" onclick="printReceipt(<?= htmlspecialchars(json_encode($payment)) ?>)" title="Fiş Yazdır">
                                        <i class="fas fa-print"></i>
                                    </button>
                                            <?php if ($payment['status'] == 'cancelled' && $canReorderToTable): ?>
                                        <button class="action-btn reorder" onclick="reorderToTable(<?= $payment['payment_id'] ?>, <?= $payment['table_id'] ?>, '<?= $payment['table_no'] ?>')" title="Masaya Tekrar Ekle">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($payment['status'] == 'completed' && $canCancelPayment): ?>
                                        <button class="action-btn cancel" onclick="cancelPayment(<?= $payment['payment_id'] ?>)" title="Ödemeyi İptal Et">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

            <!-- Mobile Accordion View -->
            <div class="mobile-accordion-view d-block d-md-none">
                <!-- Mobile Search -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="mobileSearch" class="form-label">Ara:</label>
                            <input type="text" id="mobileSearch" class="form-control" placeholder="Masa, tarih, tutar veya durum ile ara...">
                        </div>
                    </div>
                </div>

                <!-- Mobile Filter Buttons -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary mobile-filter-btn active" data-filter="all">Tümü</button>
                            <button type="button" class="btn btn-outline-success mobile-filter-btn" data-filter="completed">Tamamlandı</button>
                            <button type="button" class="btn btn-outline-danger mobile-filter-btn" data-filter="cancelled">İptal</button>
                        </div>
                    </div>
                </div>

                <div id="paymentsContainer">
                    <?php foreach ($payments as $index => $payment): ?>
                        <?php 
                // Ödenen tutarı hesapla - önce paid_amount alanını kontrol et
                $paidAmount = 0;
                if (!empty($payment['paid_amount']) && $payment['paid_amount'] > 0) {
                    // Veritabanında paid_amount varsa onu kullan
                    $paidAmount = floatval($payment['paid_amount']);
                } else {
                    // Yoksa hesapla: subtotal - discount_amount
                    $subtotal = floatval($payment['subtotal'] ?? 0);
                    $discount = floatval($payment['discount_amount'] ?? 0);
                    $paidAmount = $subtotal - $discount;
                }
                        ?>
                        <div class="payment-card payment-item" data-status="<?= $payment['status'] ?>" data-table="<?= $payment['table_no'] ?>" data-date="<?= date('Y-m-d', strtotime($payment['created_at'])) ?>" data-amount="<?= number_format($paidAmount, 2) ?>">
                            <div class="payment-header" onclick="togglePaymentDetails(<?= $index ?>)">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <div class="payment-date">
                                            <i class="fas fa-calendar me-2 text-primary"></i>
                                            <span class="fw-bold"><?= date('d.m.Y', strtotime($payment['created_at'])) ?></span>
                                            <small class="text-muted ms-2"><?= date('H:i', strtotime($payment['created_at'])) ?></small>
                                            <br>
                                            <i class="fas fa-chair me-2 text-success"></i>
                                            <small class="text-muted"><?= $payment['table_no'] ?></small>
                                            <span class="amount-badge ms-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 0.8rem;">
                                                <?= number_format($paidAmount, 2) ?> ₺
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="payment-status">
                                            <span class="status-badge status-<?= $payment['status'] ?>">
                                                <i class="fas fa-<?= $payment['status'] == 'completed' ? 'check' : 'times' ?>"></i>
                                                <?= $payment['status'] == 'completed' ? 'Tamamlandı' : 'İptal' ?>
                                            </span>
                                            <br>
                                            <i class="fas fa-chevron-down toggle-icon" id="toggle_<?= $index ?>"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="payment-details" id="details_<?= $index ?>" style="display: none;">
                                <div class="accordion-body">
                                    <div class="row">
                                        <!-- Ödeme Detayları -->
                                        <div class="col-12 mb-4">
                                            <h6 class="fw-bold text-primary mb-3">
                                                <i class="fas fa-receipt me-2"></i>Ödeme Detayları
                                            </h6>
                                            <div class="payment-details">
                                                <div class="detail-row">
                                                    <span class="label">Ara Toplam:</span>
                                                    <span class="value"><?= number_format(floatval($payment['subtotal']), 2) ?> ₺</span>
                                                </div>
                                                <?php if ($payment['discount_amount'] > 0): ?>
                                                <div class="detail-row text-warning">
                                                    <span class="label">İskonto:</span>
                                                    <span class="value">
                                                        -<?= number_format(floatval($payment['discount_amount']), 2) ?> ₺
                                                        (<?= $payment['discount_type'] == 'percent' ? '%'.$payment['discount_value'] : number_format(floatval($payment['discount_value']), 2).' ₺' ?>)
                                                    </span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="detail-row fw-bold text-success">
                                                    <span class="label">Toplam Ödenen:</span>
                                                    <span class="value"><?= number_format($paidAmount, 2) ?> ₺</span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="label">Ödeme Yöntemi:</span>
                                                    <span class="value">
                                                        <i class="fas fa-<?= $payment['payment_method'] == 'cash' ? 'money-bill' : 'credit-card' ?> me-2"></i>
                                                        <?= $payment['payment_method'] == 'cash' ? 'Nakit' : 'POS' ?>
                                                    </span>
                                                </div>
                                                <?php if ($payment['status'] == 'cancelled' && $payment['payment_note']): ?>
                                                <div class="detail-row text-danger">
                                                    <span class="label">İptal Nedeni:</span>
                                                    <span class="value"><?= htmlspecialchars($payment['payment_note']) ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Sipariş Detayları -->
                                        <div class="col-12 mb-3">
                                            <h6 class="fw-bold text-primary mb-3">
                                                <i class="fas fa-utensils me-2"></i>Sipariş İçeriği
                                            </h6>
                                            <div class="order-details-expanded">
                                                <?php 
                                                if (!empty($payment['order_details'])):
                                                    $orderItems = explode('||', $payment['order_details']);
                                                    foreach($orderItems as $item):
                                                        if (empty(trim($item))) continue;
                                                        $itemParts = explode('|', $item);
                                                        if (count($itemParts) >= 2):
                                                            $quantityAndName = $itemParts[0];
                                                            $price = floatval($itemParts[1]);
                                                ?>
                                                <div class="order-item-expanded">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="item-name"><?= htmlspecialchars($quantityAndName) ?></span>
                                                        <span class="item-price fw-bold"><?= number_format($price, 2) ?> ₺</span>
                                                    </div>
                                                </div>
                                                <?php 
                                                        endif;
                                                    endforeach; 
                                                else:
                                                ?>
                                                    <div class="text-muted text-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        Sipariş detayı bulunamadı
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- İşlemler -->
                                        <div class="col-12">
                                            <div class="d-flex justify-content-center gap-2">
                                                <button class="action-btn print" onclick="printReceipt(<?= htmlspecialchars(json_encode($payment)) ?>)" title="Fiş Yazdır">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                                <?php if ($payment['status'] == 'cancelled' && $canReorderToTable): ?>
                                                    <button class="action-btn reorder" onclick="reorderToTable(<?= $payment['payment_id'] ?>, <?= $payment['table_id'] ?>, '<?= $payment['table_no'] ?>')" title="Masaya Tekrar Ekle">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($payment['status'] == 'completed' && $canCancelPayment): ?>
                                                    <button class="action-btn cancel" onclick="cancelPayment(<?= $payment['payment_id'] ?>)" title="Ödemeyi İptal Et">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Mobile Pagination -->
                <div class="row mt-3">
                    <div class="col-12">
                        <nav aria-label="Mobile pagination">
                            <ul class="pagination justify-content-center" id="mobilePagination">
                                <!-- Pagination will be generated by JavaScript -->
                            </ul>
                        </nav>
                        <div class="text-center">
                            <small class="text-muted" id="mobileInfo">
                                <!-- Info will be generated by JavaScript -->
                            </small>
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
    // Wait for both DOM and all scripts to be loaded
    window.addEventListener('load', function() {
        // Double check that all required libraries are loaded
        if (typeof $ === 'undefined') {
            console.error('jQuery not loaded');
            return;
        }
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap not loaded');
            return;
        }
        
        // Initialize main application
        initializePaymentsPage();
    });

    // Yetki değişkenlerini GLOBAL olarak JavaScript'e aktar
    const userPermissions = {
        canCancelPayment: <?php echo $canCancelPayment ? 'true' : 'false' ?>,
        canReorderToTable: <?php echo $canReorderToTable ? 'true' : 'false' ?>
    };
    
    // Debug için yetkileri console'a yazdır
    console.log('User Permissions:', userPermissions);

    function initializePaymentsPage() {

        // Animasyon efektleri
        setTimeout(() => {
            $('.stat-card').each((index, element) => {
                setTimeout(() => {
                    $(element).addClass('animate-slide-up');
                }, index * 100);
            });
        }, 300);

        // Accordion items animation (sadece mobilde)
        if (window.innerWidth < 768) {
            setTimeout(() => {
                $('.payment-item').each((index, element) => {
                    setTimeout(() => {
                        $(element).addClass('animate-slide-up');
                    }, index * 100);
                });
            }, 500);
        }

        // Modern button loading states
        $('.modern-btn').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            btn.html('<div class="loading-spinner"></div>');
            
            setTimeout(() => {
                btn.html(originalHtml);
            }, 1500);
        });

        // DataTables sadece desktop'ta aktif
        if (window.innerWidth >= 768) {
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
            const table = $('#paymentsTable').DataTable({
            language: turkishLanguage,
            order: [[0, 'desc']],
                pageLength: 15,
                responsive: false,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                drawCallback: function() {
                    // Her sayfa çiziminde animasyon efekti
                    $('.modern-table tbody tr').each((index, element) => {
                        setTimeout(() => {
                            $(element).addClass('animate-slide-up');
                        }, index * 50);
                    });
                },
            buttons: [
                {
                    extend: 'excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
                    },
                        title: 'Ödemeler Raporu - ' + new Date().toLocaleDateString('tr-TR'),
                        className: 'btn-success'
                },
                {
                    extend: 'pdf',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
                    },
                        title: 'Ödemeler Raporu - ' + new Date().toLocaleDateString('tr-TR'),
                        className: 'btn-danger'
                },
                {
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7]
                    },
                        title: 'Ödemeler Raporu',
                        className: 'btn-primary'
                }
            ]
        });

            // Custom filtering
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const dateFilter = $('#dateFilter').val();
                const statusFilter = $('#statusFilter').val();
                const tableFilter = $('#tableFilter').val().toLowerCase();

                const rowDate = data[0]; // Date column
                const rowTable = data[1]; // Table column
                const rowStatus = data[7]; // Status column

                // Date filter
                if (dateFilter && !rowDate.includes(dateFilter.split('-').reverse().join('.'))) {
                    return false;
                }

                // Status filter
                if (statusFilter && !rowStatus.toLowerCase().includes(statusFilter === 'completed' ? 'tamamlandı' : 'iptal')) {
                    return false;
                }

                // Table filter
                if (tableFilter && !rowTable.toLowerCase().includes(tableFilter)) {
                    return false;
                }

                return true;
            });

            // Filter event handlers
            $('#dateFilter, #statusFilter, #tableFilter').on('keyup change', function() {
                table.draw();
            });

            // Desktop Table hover effects
            $('#paymentsTable tbody').on('mouseenter', 'tr', function() {
                $(this).find('.action-btn').each((index, btn) => {
                    setTimeout(() => {
                        $(btn).addClass('animate-slide-up');
                    }, index * 50);
                });
            });

            $('#paymentsTable tbody').on('mouseleave', 'tr', function() {
                $(this).find('.action-btn').removeClass('animate-slide-up');
            });

            // Excel/PDF/Print export functions - DataTables buttons
            $('.excel-btn').on('click', function() {
                $('#paymentsTable').DataTable().button('0').trigger();
            });

            $('.pdf-btn').on('click', function() {
                $('#paymentsTable').DataTable().button('1').trigger();
            });

            $('.print-btn').on('click', function() {
                $('#paymentsTable').DataTable().button('2').trigger();
            });
        } else {
            // Mobile functionality
            let currentPage = 1;
            const itemsPerPage = 10;
            let filteredItems = $('.payment-card');

            // Mobile search
            $('#mobileSearch').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                filterMobileItems();
            });

            // Mobile filter buttons
            $('.mobile-filter-btn').on('click', function() {
                $('.mobile-filter-btn').removeClass('active');
                $(this).addClass('active');
                filterMobileItems();
            });

            function filterMobileItems() {
                const searchTerm = $('#mobileSearch').val().toLowerCase();
                const statusFilter = $('.mobile-filter-btn.active').data('filter');

                filteredItems = $('.payment-card').filter(function() {
                    const item = $(this);
                    const searchableText = item.text().toLowerCase();
                    const itemStatus = item.data('status');

                    // Text search
                    const matchesSearch = searchTerm === '' || searchableText.includes(searchTerm);

                    // Status filter
                    const matchesStatus = statusFilter === 'all' || itemStatus === statusFilter;

                    return matchesSearch && matchesStatus;
                });

                currentPage = 1;
                showMobilePage();
                updateMobilePagination();
            }

            function showMobilePage() {
                $('.payment-card').hide();
                
                const startIndex = (currentPage - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;
                
                filteredItems.slice(startIndex, endIndex).show();
                
                updateMobileInfo();
            }

            function updateMobilePagination() {
                const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
                const pagination = $('#mobilePagination');
                pagination.empty();

                if (totalPages <= 1) return;

                // Previous button
                const prevDisabled = currentPage === 1 ? 'disabled' : '';
                pagination.append(`
                    <li class="page-item ${prevDisabled}">
                        <a class="page-link" href="#" data-page="${currentPage - 1}">Önceki</a>
                    </li>
                `);

                // Page numbers
                const startPage = Math.max(1, currentPage - 2);
                const endPage = Math.min(totalPages, currentPage + 2);

                for (let i = startPage; i <= endPage; i++) {
                    const active = i === currentPage ? 'active' : '';
                    pagination.append(`
                        <li class="page-item ${active}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `);
                }

                // Next button
                const nextDisabled = currentPage === totalPages ? 'disabled' : '';
                pagination.append(`
                    <li class="page-item ${nextDisabled}">
                        <a class="page-link" href="#" data-page="${currentPage + 1}">Sonraki</a>
                    </li>
                `);

                // Pagination click events
                pagination.find('.page-link').on('click', function(e) {
                    e.preventDefault();
                    const page = parseInt($(this).data('page'));
                    if (page >= 1 && page <= totalPages) {
                        currentPage = page;
                        showMobilePage();
                        updateMobilePagination();
                    }
                });
            }

            function updateMobileInfo() {
                const startIndex = (currentPage - 1) * itemsPerPage + 1;
                const endIndex = Math.min(currentPage * itemsPerPage, filteredItems.length);
                
                $('#mobileInfo').text(`${filteredItems.length} kayıttan ${startIndex} - ${endIndex} arası gösteriliyor`);
            }

            // Initialize mobile pagination
            filterMobileItems();

            // Action button click handling - prevent toggle
            $('.payment-details .action-btn').on('click', function(e) {
                e.stopPropagation();
            });
            
            // Artık Bootstrap accordion kullanmıyoruz - custom toggle

            // Mobile export functions
            $('.excel-btn').on('click', function() {
                exportToExcel();
            });

            $('.pdf-btn').on('click', function() {
                exportToPDF();
            });

            $('.print-btn').on('click', function() {
                printPayments();
            });
        }
    }
    // End of initializePaymentsPage function

    // Custom toggle function - Bootstrap accordion yerine
    function togglePaymentDetails(index) {
        const details = document.getElementById('details_' + index);
        const toggleIcon = document.getElementById('toggle_' + index);
        
        if (details.style.display === 'none' || details.style.display === '') {
            details.style.display = 'block';
            toggleIcon.classList.remove('fa-chevron-down');
            toggleIcon.classList.add('fa-chevron-up');
        } else {
            details.style.display = 'none';
            toggleIcon.classList.remove('fa-chevron-up');
            toggleIcon.classList.add('fa-chevron-down');
        }
    }

    // Mobile export functions
    function exportToExcel() {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Tarih,Masa,Ödenen Tutar,Durum,Ödeme Yöntemi\n";
        
        $('.payment-card').each(function() {
            const date = $(this).find('.payment-date span').first().text();
            const table = $(this).find('.payment-date small').text();
            const amount = $(this).find('.payment-amount .amount-badge').text().replace('₺', '').trim();
            const status = $(this).find('.status-badge').text().trim();
            
            csvContent += `"${date}","${table}","${amount}","${status}","-"\n`;
        });
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "odemeler_raporu_" + new Date().toLocaleDateString('tr-TR') + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportToPDF() {
        window.print();
    }

    function printPayments() {
        window.print();
    }

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
        // PHP'den gelen yazıcı ayarlarını al
        const printerSettings = <?php echo json_encode($printerSettings); ?>;
        const restaurantName = <?php echo json_encode($restaurantName); ?>;
        
        // Sipariş öğelerini hazırla
        const orderItems = payment.order_details.split('||').map(item => {
            const [quantityAndName, price] = item.split('|');
            const [quantity, name] = quantityAndName.split('x ');
            return {
                product_name: name.trim(),
                quantity: parseInt(quantity.trim()),
                price: parseFloat(price)
            };
        });
        
        // Ödeme verilerini hazırla
        const paymentData = {
            payment_id: payment.payment_id,
            table_no: payment.table_no,
            subtotal: parseFloat(payment.subtotal),
            discount_amount: parseFloat(payment.discount_amount || 0),
            discount_type: payment.discount_type,
            discount_value: payment.discount_value,
            total_amount: parseFloat(payment.total_amount),
            payment_method: payment.payment_method,
            status: payment.status,
            payment_note: payment.payment_note,
            created_at: payment.created_at
        };
        
        // Merkezi web receipt builder kullan
        const receiptContent = buildWebReceiptContent('payment', paymentData, orderItems, printerSettings, restaurantName);

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
    
    // Web-based receipt content builder (JavaScript sürümü)
    function buildWebReceiptContent(type, data, items, settings, restaurantName) {
        const paperWidth = settings['printer_paper_width'] || '80';
        
        // Responsive karakter genişliği hesapla
        const charWidth = getJSCharacterWidth(paperWidth);
        const fontSize = getJSFontSize(paperWidth);
        
        let content = `<div style='font-family: "Courier New", monospace; width: ${paperWidth}mm; margin: 0 auto; padding: 10px; font-size: ${fontSize}px; line-height: 1.2;'>`;
        
        // Başlık
        content += "<div style='text-align: center; margin-bottom: 20px;'>";
        
        if (settings['printer_header']) {
            const headerLines = wrapJSText(settings['printer_header'], charWidth);
            headerLines.forEach(line => {
                content += `<div style='margin-bottom: 5px;'>${line}</div>`;
            });
        }
        
        if (restaurantName) {
            const nameLines = wrapJSText(restaurantName, charWidth);
            nameLines.forEach(line => {
                content += `<h3 style='margin: 3px 0; font-size: ${fontSize + 2}px;'>${line}</h3>`;
            });
        }
        
        content += `<p style='margin: 5px 0;'>Fiş No: #${data.payment_id}</p>`;
        content += `<p style='margin: 5px 0;'>Tarih: ${new Date(data.created_at).toLocaleString('tr-TR')}</p>`;
        
        if (data.table_no) {
            content += `<p style='margin: 5px 0;'>Masa: ${data.table_no}</p>`;
        }
        
        content += "</div>";
        
        // Sipariş öğeleri
        if (items && items.length > 0) {
            content += "<div style='margin-bottom: 15px;'>";
            content += `<div style='margin-bottom: 5px; text-align: center; font-weight: bold;'>${'='.repeat(charWidth)}</div>`;
            content += `<div style='text-align: center; margin-bottom: 5px;'>SİPARİŞ DETAYLARI</div>`;
            content += `<div style='margin-bottom: 10px;'>${'-'.repeat(charWidth)}</div>`;
            
            items.forEach(item => {
                const total = item.quantity * item.price;
                const leftText = `${item.quantity}x ${item.product_name}`;
                const rightText = `${total.toFixed(2)} ₺`;
                const formattedLine = formatJSTwoColumns(leftText, rightText, charWidth);
                
                content += `<div style='margin: 2px 0; font-family: "Courier New", monospace;'>${formattedLine}</div>`;
            });
            
            content += "</div>";
        }
        
        // Ödeme detayları
        content += `<div style='margin-bottom: 10px;'>${'-'.repeat(charWidth)}</div>`;
        content += "<div style='margin-bottom: 10px;'>";
        
        if (data.subtotal && data.subtotal > 0) {
            const formattedLine = formatJSTwoColumns('Ara Toplam:', `${data.subtotal.toFixed(2)} ₺`, charWidth);
            content += `<div style='margin: 2px 0; font-family: "Courier New", monospace;'>${formattedLine}</div>`;
        }
        
        if (data.discount_amount && data.discount_amount > 0) {
            let discountText = 'İskonto';
            if (data.discount_type === 'percent' && data.discount_value) {
                discountText += ` (%${data.discount_value})`;
            }
            const formattedLine = formatJSTwoColumns(discountText + ':', `-${data.discount_amount.toFixed(2)} ₺`, charWidth);
            content += `<div style='margin: 2px 0; font-family: "Courier New", monospace;'>${formattedLine}</div>`;
        }
        
        const totalLine = formatJSTwoColumns('GENEL TOPLAM:', `${data.total_amount.toFixed(2)} ₺`, charWidth);
        content += `<div style='margin: 2px 0; font-family: "Courier New", monospace; font-weight: bold;'>${totalLine}</div>`;
        
        // Ödeme yöntemi
        const paymentMethodText = data.payment_method === 'cash' ? 'NAKİT' : 'KART';
        const paymentLine = formatJSTwoColumns('ÖDEME TİPİ:', paymentMethodText, charWidth);
        content += `<div style='margin: 2px 0; font-family: "Courier New", monospace;'>${paymentLine}</div>`;
        
        // İptal durumu
        if (data.status === 'cancelled') {
            content += `<div style='margin-top: 10px;'>${'='.repeat(charWidth)}</div>`;
            content += "<div style='text-align: center; margin: 5px 0; font-weight: bold;'>İPTAL EDİLDİ</div>";
            if (data.payment_note) {
                const noteLines = wrapJSText(`İptal Nedeni: ${data.payment_note}`, charWidth);
                noteLines.forEach(line => {
                    content += `<div style='margin: 2px 0; text-align: center;'>${line}</div>`;
                });
            }
        }
        
        content += "</div>";
        
        // Altlık
        if (settings['printer_footer']) {
            const footerLines = wrapJSText(settings['printer_footer'], charWidth);
            content += `<div style='margin-top: 10px;'>${'='.repeat(charWidth)}</div>`;
            content += "<div style='text-align: center; font-size: 0.9em;'>";
            footerLines.forEach(line => {
                content += `<div style='margin: 2px 0;'>${line}</div>`;
            });
            content += "</div>";
        }
        
        content += "</div>";
        
        return content;
    }

    // JavaScript responsive helper functions
    function getJSCharacterWidth(paperWidth) {
        const widthMap = {
            '58': 24,  // 58mm -> 24 karakter
            '80': 32,  // 80mm -> 32 karakter (varsayılan)
            '112': 44  // 112mm -> 44 karakter
        };
        
        if (widthMap[paperWidth]) {
            return widthMap[paperWidth];
        }
        
        // Hesaplanmış genişlik
        const calculatedWidth = Math.floor(paperWidth * 0.4);
        return Math.max(20, Math.min(60, calculatedWidth));
    }

    function getJSFontSize(paperWidth) {
        // Kağıt genişliğine göre font boyutu ayarla
        if (paperWidth <= 58) return 10;
        if (paperWidth <= 80) return 12;
        return 14;
    }

    function wrapJSText(text, width) {
        if (text.length <= width) {
            return [text];
        }
        
        const lines = [];
        const words = text.split(' ');
        let currentLine = '';
        
        words.forEach(word => {
            if ((currentLine + ' ' + word).length <= width) {
                currentLine += (currentLine ? ' ' : '') + word;
            } else {
                if (currentLine) {
                    lines.push(currentLine);
                    currentLine = word;
                } else {
                    // Tek kelime çok uzunsa, kes
                    lines.push(word.substring(0, width - 3) + '...');
                    currentLine = '';
                }
            }
        });
        
        if (currentLine) {
            lines.push(currentLine);
        }
        
        return lines;
    }

    function formatJSTwoColumns(leftText, rightText, totalWidth) {
        // Sol kolon için %65, sağ kolon için %35 alan ayır
        const leftWidth = Math.floor(totalWidth * 0.65);
        const rightWidth = totalWidth - leftWidth;
        
        // Sol metni kes gerekirse
        if (leftText.length > leftWidth) {
            leftText = leftText.substring(0, leftWidth - 3) + '...';
        }
        
        // Sağ metni kes gerekirse
        if (rightText.length > rightWidth) {
            rightText = rightText.substring(0, rightWidth - 1);
        }
        
        // HTML span'lerde fixed-width karakterler kullan
        const leftPadded = leftText.padEnd(leftWidth, ' ');
        const rightPadded = rightText.padStart(rightWidth, ' ');
        
        return `<span style='white-space: pre;'>${leftPadded}${rightPadded}</span>`;
    }
    </script>
</body>
</html>