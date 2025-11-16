<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $payload = [
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            echo '{"success": false, "message": "Fatal Error & JSON encoding failed."}';
        } else {
            echo $encoded;
        }
    }
});

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../database/db.php';

// استخدام فئة FPDF من الحزمة setasign/fpdf (ليست ذات مساحة أسماء)
// يتم تحميلها عبر autoload، ويمكن استدعاؤها مباشرةً باسم FPDF
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
$student_id = intval($_GET['id'] ?? 0);

if (!$user_id) die('Access denied');

$allowed = in_array($user_role, ['manager', 'technical']) || ($user_id == $student_id);
if (!$allowed) die('Not authorized');
if (!$student_id) die('Student ID required');

try {
    $stmt = $conn->prepare("SELECT u.id, u.full_name, u.email, u.phone, u.governorate, u.district, u.created_at, c.title as course_title FROM users u LEFT JOIN enrollments e ON u.id = e.user_id LEFT JOIN courses c ON e.course_id = c.course_id WHERE u.id = ? AND u.role = 'student' LIMIT 1");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) die('Student not found');
    $student = $result->fetch_assoc();
    
    $verifyUrl = 'http://localhost/Ibdaa-Taiz/platform/verify_student.php?id=' . $student_id;
    $qrOptions = new QROptions(['version' => 5, 'outputType' => QRCode::OUTPUT_IMAGE_PNG, 'eccLevel' => QRCode::ECC_L, 'scale' => 6, 'imageBase64' => true]);
    $qrcode = new QRCode($qrOptions);
    $qrCodeImage = $qrcode->render($verifyUrl);
    $qrTempPath = __DIR__ . '/../../uploads/temp/qr_' . $student_id . '_' . time() . '.png';
    file_put_contents($qrTempPath, base64_decode(substr($qrCodeImage, strpos($qrCodeImage, ',') + 1)));
    
    $pdf = new FPDF('L', 'mm', [85.6, 53.98]);
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);
    $pdf->SetFillColor(240, 248, 255);
    $pdf->Rect(0, 0, 85.6, 53.98, 'F');
    $pdf->SetDrawColor(41, 128, 185);
    $pdf->SetLineWidth(0.5);
    $pdf->Rect(2, 2, 81.6, 49.98, 'D');
    
    $logoPath = __DIR__ . '/../../platform/photos/Sh.jpg';
    if (file_exists($logoPath)) {
        // FPDF القياسي لا يدعم الشفافية مباشرةً؛ نضع الشعار بحجم كبير في الوسط
        $pdf->Image($logoPath, 25, 12, 35, 30);
        // شعار صغير علوي
        $pdf->Image($logoPath, 65, 5, 15, 12);
    }
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(41, 128, 185);
    $pdf->SetXY(5, 6);
    $pdf->Cell(55, 5, iconv('UTF-8', 'windows-1256', 'بطاقة طالب'), 0, 1, 'R');
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetTextColor(52, 73, 94);
    $pdf->SetX(5);
    $pdf->Cell(55, 4, 'Student ID Card', 0, 1, 'R');
    
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(25, 20);
    $pdf->Cell(45, 5, iconv('UTF-8', 'windows-1256', mb_substr($student['full_name'], 0, 30)), 0, 1, 'R');
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(25, 26);
    $pdf->Cell(45, 4, iconv('UTF-8', 'windows-1256', 'الرقم: ') . str_pad($student['id'], 6, '0', STR_PAD_LEFT), 0, 1, 'R');
    
    if (!empty($student['course_title'])) {
        $pdf->SetXY(25, 31);
        $pdf->Cell(45, 4, iconv('UTF-8', 'windows-1256', mb_substr($student['course_title'], 0, 30)), 0, 1, 'R');
    }
    
    if (!empty($student['district']) || !empty($student['governorate'])) {
        $pdf->SetXY(25, 36);
        $location = trim(($student['district'] ?? '') . ' - ' . ($student['governorate'] ?? ''));
        $pdf->Cell(45, 4, iconv('UTF-8', 'windows-1256', $location), 0, 1, 'R');
    }
    
    $pdf->SetFont('Arial', '', 6);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetXY(25, 42);
    $pdf->Cell(45, 3, iconv('UTF-8', 'windows-1256', 'تاريخ الإصدار: ') . date('Y/m/d', strtotime($student['created_at'])), 0, 1, 'R');
    
    if (file_exists($qrTempPath)) {
        $pdf->Image($qrTempPath, 5, 22, 18, 18);
        $pdf->SetFont('Arial', '', 5);
        $pdf->SetXY(3, 41);
        $pdf->Cell(22, 2, 'Scan to Verify', 0, 0, 'C');
    }
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(5, 47, 80.6, 47);
    $pdf->SetFont('Arial', 'I', 6);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetXY(5, 48.5);
    $pdf->Cell(75.6, 3, iconv('UTF-8', 'windows-1256', 'منصة إبداع للتدريب والتأهيل - Ibdaa Training Platform'), 0, 0, 'C');
    
    if (file_exists($qrTempPath)) unlink($qrTempPath);
    $pdf->Output('I', 'student_card_' . $student_id . '.pdf');
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
