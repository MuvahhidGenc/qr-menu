<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../vendor/autoload.php'; // TCPDF ve PhpSpreadsheet için
checkAuth();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

header('Content-Type: application/json');

try {
    $db = new Database();
    $format = $_GET['format'] ?? 'pdf';
    
    // Aktif kodu al
    $code = $db->query("SELECT code, expires_at FROM order_codes WHERE active = 1 LIMIT 1")->fetch();
    if (!$code) {
        throw new Exception('Aktif kod bulunamadı');
    }
    
    // Masa sayısını al
    $tableCount = $db->query("SELECT COUNT(*) as count FROM tables")->fetch()['count'];
    
    if ($format === 'pdf') {
        // PDF oluştur
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('QR Menü Sistemi');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Sipariş Kodları');
        
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        $pdf->AddPage();
        
        // Başlık
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, 'Sipariş Kodları', 0, 1, 'C');
        
        // Geçerlilik tarihi
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 10, 'Geçerlilik: ' . date('d.m.Y H:i', strtotime($code['expires_at'])), 0, 1, 'C');
        
        $pdf->Ln(10);
        
        // Kodları yazdır
        $pdf->SetFont('dejavusans', 'B', 24);
        for ($i = 1; $i <= $tableCount; $i++) {
            // Şık bir kutu içinde kod
            $pdf->RoundedRect(50, $pdf->GetY(), 110, 30, 3.50, '1111', 'DF', array(), array(240,240,240));
            $pdf->Cell(0, 30, $code['code'], 0, 1, 'C');
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->Cell(0, 10, 'Masa ' . $i, 0, 1, 'C');
            $pdf->SetFont('dejavusans', 'B', 24);
            $pdf->Ln(5);
            
            // Sayfa kontrolü
            if ($pdf->GetY() > 250 && $i < $tableCount) {
                $pdf->AddPage();
            }
        }
        
        // PDF'i indir
        $pdf->Output('siparis_kodlari.pdf', 'D');
        
    } else if ($format === 'excel') {
        // Excel oluştur
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Başlık
        $sheet->setCellValue('A1', 'Masa No');
        $sheet->setCellValue('B1', 'Sipariş Kodu');
        $sheet->setCellValue('C1', 'Geçerlilik');
        
        // Stil
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('CCCCCC');
            
        // Kodları ekle
        for ($i = 1; $i <= $tableCount; $i++) {
            $sheet->setCellValue('A'.($i+1), 'Masa '.$i);
            $sheet->setCellValue('B'.($i+1), $code['code']);
            $sheet->setCellValue('C'.($i+1), date('d.m.Y H:i', strtotime($code['expires_at'])));
        }
        
        // Sütun genişliklerini ayarla
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(25);
        
        // Excel'i indir
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="siparis_kodlari.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log('Print error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 