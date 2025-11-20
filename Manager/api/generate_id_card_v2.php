<?php
// 🎴 AI-Powered ID Card Generator v2.0
// Advanced Student ID Card System with Watermark & Professional Design

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../database/db.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Security Check
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
$student_id = intval($_GET['id'] ?? 0);

if (!$user_id) die('Access denied');

$allowed = in_array($user_role, ['manager', 'technical']) || ($user_id == $student_id);
if (!$allowed) die('Not authorized');
if (!$student_id) die('Student ID required');

try {
    // Fetch student data with course info
    $stmt = $conn->prepare("
        SELECT 
            u.id, u.full_name, u.email, u.phone, 
            u.governorate, u.district, u.created_at, u.photo,
            c.title as course_title,
            c.duration as course_duration,
            e.enrollment_date,
            e.status as enrollment_status
        FROM users u
        LEFT JOIN enrollments e ON u.id = e.user_id
        LEFT JOIN courses c ON e.course_id = c.course_id
        WHERE u.id = ? AND u.role = 'student'
        LIMIT 1
    ");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) die('Student not found');
    $student = $result->fetch_assoc();

    // Determine Base URL dynamically
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = "$protocol://$host/Ibdaa-Taiz";

    // Generate QR Code
    $verifyUrl = "$baseUrl/platform/verify_student.php?id=" . $student_id;
    $qrOptions = new QROptions([
        'version' => 5,
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel' => QRCode::ECC_H,
        'scale' => 8,
        'imageBase64' => true
    ]);
    $qrcode = new QRCode($qrOptions);
    $qrCodeImage = $qrcode->render($verifyUrl);
    $qrTempPath = __DIR__ . '/../../uploads/temp/qr_' . $student_id . '_' . time() . '.png';
    @mkdir(__DIR__ . '/../../uploads/temp/', 0777, true);
    file_put_contents($qrTempPath, base64_decode(substr($qrCodeImage, strpos($qrCodeImage, ',') + 1)));

    // Create PDF with Advanced Design
    $pdf = new FPDF('L', 'mm', [85.6, 53.98]);
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);

    // ===== BACKGROUND GRADIENT =====
    // Simulate gradient with multiple rectangles
    $pdf->SetFillColor(240, 245, 255);
    $pdf->Rect(0, 0, 85.6, 53.98, 'F');

    // ===== WATERMARK LOGO (LARGE, TRANSPARENT EFFECT) =====
    $logoPath = __DIR__ . '/../../platform/photos/Sh.jpg';
    if (file_exists($logoPath)) {
        // Large centered watermark (FPDF doesn't support alpha transparency)
        // We simulate it by placing it centered
        $watermarkSize = 40;
        $watermarkX = (85.6 - $watermarkSize) / 2;
        $watermarkY = (53.98 - $watermarkSize) / 2;
        $pdf->Image($logoPath, $watermarkX, $watermarkY, $watermarkSize, $watermarkSize);
    }

    // ===== BORDER WITH GRADIENT COLORS =====
    $pdf->SetDrawColor(99, 102, 241); // Indigo
    $pdf->SetLineWidth(0.8);
    $pdf->Rect(2, 2, 81.6, 49.98, 'D');
    
    $pdf->SetDrawColor(139, 92, 246); // Purple
    $pdf->SetLineWidth(0.4);
    $pdf->Rect(3, 3, 79.6, 47.98, 'D');

    // ===== TOP LOGO (SMALL) =====
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 68, 5, 14, 11);
    }

    // ===== TOP HEADER =====
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(67, 56, 202); // Indigo
    $pdf->SetXY(5, 5);
    $pdf->MultiCell(60, 5, iconv('UTF-8', 'windows-1256', 'منصة إبداع للتدريب والتأهيل'), 0, 'R');
    
    $pdf->SetFont('Arial', 'I', 7);
    $pdf->SetTextColor(100, 116, 139); // Slate
    $pdf->SetXY(5, 10);
    $pdf->Cell(60, 4, 'Ibdaa Training Platform', 0, 0, 'R');

    // ===== CARD TITLE =====
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(239, 68, 68); // Red accent
    $pdf->SetXY(5, 15);
    $pdf->Cell(60, 4, iconv('UTF-8', 'windows-1256', 'بطاقة طالب'), 0, 1, 'R');
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->SetX(5);
    $pdf->Cell(60, 3, 'STUDENT ID CARD', 0, 0, 'R');

    // ===== STUDENT PHOTO PLACEHOLDER =====
    $photoX = 7;
    $photoY = 22;
    $photoW = 18;
    $photoH = 24;
    
    // White border around photo
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect($photoX - 1, $photoY - 1, $photoW + 2, $photoH + 2, 'F');

    // If student has uploaded a photo, draw it; otherwise draw a placeholder
    $studentPhotoPath = null;
    if (!empty($student['photo'])) {
        $candidate = __DIR__ . '/../../' . ltrim($student['photo'], '/\\');
        if (file_exists($candidate)) {
            $studentPhotoPath = $candidate;
        }
    }

    if ($studentPhotoPath) {
        try {
            $pdf->Image($studentPhotoPath, $photoX, $photoY, $photoW, $photoH);
        } catch (Exception $e) {
            // No placeholder - keep natural/empty
        }
    }
    // No placeholder at all - if no photo, area stays empty/natural

    // ===== STUDENT INFO BOX =====
    $infoX = 28;
    $infoY = 22;
    $infoW = 48;
    $infoH = 24;
    
    // Info box background
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect($infoX, $infoY, $infoW, $infoH, 'F');
    
    // Student Name
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(15, 23, 42); // Slate-900
    $pdf->SetXY($infoX + 2, $infoY + 2);
    $pdf->MultiCell($infoW - 4, 4, iconv('UTF-8', 'windows-1256', mb_substr($student['full_name'], 0, 35)), 0, 'R');
    
    // Student ID
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(99, 102, 241);
    $pdf->SetXY($infoX + 2, $infoY + 8);
    $studentIdFormatted = 'ID: ' . str_pad($student['id'], 6, '0', STR_PAD_LEFT);
    $pdf->Cell($infoW - 4, 4, $studentIdFormatted, 0, 0, 'R');
    
    // Course Title
    if (!empty($student['course_title'])) {
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetXY($infoX + 2, $infoY + 13);
        $courseText = iconv('UTF-8', 'windows-1256', 'الدورة: ' . mb_substr($student['course_title'], 0, 30));
        $pdf->MultiCell($infoW - 4, 3, $courseText, 0, 'R');
    }
    
    // Location
    if (!empty($student['district']) || !empty($student['governorate'])) {
        $pdf->SetFont('Arial', '', 6);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY($infoX + 2, $infoY + 18);
        $location = ($student['district'] ?? '') . ' - ' . ($student['governorate'] ?? '');
        $locationText = iconv('UTF-8', 'windows-1256', trim($location, ' -'));
        $pdf->Cell($infoW - 4, 3, $locationText, 0, 0, 'R');
    }

    // ===== QR CODE =====
    if (file_exists($qrTempPath)) {
        $qrX = 8;
        $qrY = 47;
        $qrSize = 16;
        
        // QR background
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($qrX - 1, $qrY - 1, $qrSize + 2, $qrSize + 2, 'F');
        
        $pdf->Image($qrTempPath, $qrX, $qrY, $qrSize, $qrSize);
        
        // QR label
        $pdf->SetFont('Arial', '', 5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY($qrX - 2, $qrY + $qrSize + 1);
        $pdf->Cell($qrSize + 4, 2, 'Scan to Verify', 0, 0, 'C');
    }

    // ===== ISSUE DATE & EXPIRY =====
    $pdf->SetFont('Arial', '', 6);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->SetXY(28, 47);
    $issueDate = iconv('UTF-8', 'windows-1256', 'تاريخ الإصدار: ') . date('Y/m/d', strtotime($student['created_at']));
    $pdf->Cell(48, 3, $issueDate, 0, 0, 'R');
    
    $pdf->SetXY(28, 50);
    $expiryDate = iconv('UTF-8', 'windows-1256', 'صالحة حتى: ') . date('Y/m/d', strtotime('+2 years'));
    $pdf->Cell(48, 3, $expiryDate, 0, 0, 'R');

    // ===== BOTTOM STRIP =====
    $pdf->SetFillColor(67, 56, 202);
    $pdf->Rect(0, 51, 85.6, 2.98, 'F');
    
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(5, 51.5);
    $pdf->Cell(75.6, 2, iconv('UTF-8', 'windows-1256', 'البطاقة الذكية AI-Powered Smart Card'), 0, 0, 'C');

    // ===== SECURITY FEATURES TEXT =====
    $pdf->SetFont('Arial', '', 5);
    $pdf->SetTextColor(148, 163, 184);
    $pdf->SetXY(26, 44);
    $securityText = 'Unique ID: ' . hash('crc32', $student_id . $student['created_at']);
    $pdf->Cell(50, 2, strtoupper($securityText), 0, 0, 'R');

    // Clean up
    if (file_exists($qrTempPath)) @unlink($qrTempPath);

    // Output
    $filename = 'ibdaa_id_card_' . str_pad($student_id, 6, '0', STR_PAD_LEFT) . '.pdf';
    $pdf->Output('I', $filename);

} catch (Exception $e) {
    die('Error generating card: ' . $e->getMessage());
}
