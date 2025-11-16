<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ]);
    }
});

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../platform/db.php';

// استخدام فئة FPDF غير مسماة المساحة من الحزمة setasign/fpdf
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager', 'technical'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$student_id = intval($_POST['student_id'] ?? 0);

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'رقم الطالب مطلوب']);
    exit;
}

try {
    // Get student data
    $stmt = $conn->prepare("SELECT u.id, u.full_name, u.email, u.phone, u.governorate, u.district, u.created_at, c.title as course_title FROM users u LEFT JOIN enrollments e ON u.id = e.user_id LEFT JOIN courses c ON e.course_id = c.course_id WHERE u.id = ? AND u.role = 'student' LIMIT 1");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على الطالب']);
        exit;
    }
    
    $student = $result->fetch_assoc();
    
    // Generate QR code
    $verifyUrl = 'http://localhost/Ibdaa-Taiz/platform/verify_student.php?id=' . $student_id;
    $qrOptions = new QROptions([
        'version' => 5,
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel' => QRCode::ECC_L,
        'scale' => 6,
        'imageBase64' => true
    ]);
    $qrcode = new QRCode($qrOptions);
    $qrCodeImage = $qrcode->render($verifyUrl);
    $tempDir = __DIR__ . '/../../uploads/temp';
    if (!is_dir($tempDir)) { @mkdir($tempDir, 0775, true); }
    $qrTempPath = $tempDir . '/qr_' . $student_id . '_' . time() . '.png';
    file_put_contents($qrTempPath, base64_decode(substr($qrCodeImage, strpos($qrCodeImage, ',') + 1)));
    
    // Generate PDF
    $pdf = new FPDF('L', 'mm', [85.6, 53.98]);
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);
    
    // Background
    $pdf->SetFillColor(240, 248, 255);
    $pdf->Rect(0, 0, 85.6, 53.98, 'F');
    
    // Border
    $pdf->SetDrawColor(41, 128, 185);
    $pdf->SetLineWidth(0.5);
    $pdf->Rect(2, 2, 81.6, 49.98, 'D');
    
    // Logo (watermark + small)
    $logoPath = __DIR__ . '/../../platform/photos/Sh.jpg';
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 25, 12, 35, 30);
        $pdf->Image($logoPath, 65, 5, 15, 12);
    }
    
    // Title
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(41, 128, 185);
    $pdf->SetXY(5, 6);
    $pdf->Cell(55, 5, iconv('UTF-8', 'windows-1256', 'بطاقة طالب'), 0, 1, 'R');
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetTextColor(52, 73, 94);
    $pdf->SetX(5);
    $pdf->Cell(55, 4, 'Student ID Card', 0, 1, 'R');
    
    // Student name
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(25, 20);
    $pdf->Cell(45, 5, iconv('UTF-8', 'windows-1256', mb_substr($student['full_name'], 0, 30)), 0, 1, 'R');
    
    // Student ID
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(25, 26);
    $pdf->Cell(45, 4, iconv('UTF-8', 'windows-1256', 'الرقم: ') . str_pad($student['id'], 6, '0', STR_PAD_LEFT), 0, 1, 'R');
    
    // Course
    if (!empty($student['course_title'])) {
        $pdf->SetXY(25, 31);
        $pdf->Cell(45, 4, iconv('UTF-8', 'windows-1256', mb_substr($student['course_title'], 0, 30)), 0, 1, 'R');
    }
    
    // Location
    if (!empty($student['district']) || !empty($student['governorate'])) {
        $pdf->SetXY(25, 36);
        $location = trim(($student['district'] ?? '') . ' - ' . ($student['governorate'] ?? ''));
        $pdf->Cell(45, 4, iconv('UTF-8', 'windows-1256', $location), 0, 1, 'R');
    }
    
    // Issue date
    $pdf->SetFont('Arial', '', 6);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetXY(25, 42);
    $pdf->Cell(45, 3, iconv('UTF-8', 'windows-1256', 'تاريخ الإصدار: ') . date('Y/m/d', strtotime($student['created_at'])), 0, 1, 'R');
    
    // QR code
    if (file_exists($qrTempPath)) {
        $pdf->Image($qrTempPath, 5, 22, 18, 18);
        $pdf->SetFont('Arial', '', 5);
        $pdf->SetXY(3, 41);
        $pdf->Cell(22, 2, 'Scan to Verify', 0, 0, 'C');
    }
    
    // Footer line and text
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(5, 47, 80.6, 47);
    $pdf->SetFont('Arial', 'I', 6);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetXY(5, 48.5);
    $pdf->Cell(75.6, 3, iconv('UTF-8', 'windows-1256', 'منصة إبداع للتدريب والتأهيل - Ibdaa Training Platform'), 0, 0, 'C');
    
    // Save PDF to temp file
    $pdfPath = $tempDir . '/card_' . $student_id . '_' . time() . '.pdf';
    $pdf->Output('F', $pdfPath);
    
    // Clean up QR code
    if (file_exists($qrTempPath)) unlink($qrTempPath);
    
    // إعدادات SMTP من قاعدة البيانات
    $smtp = [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'user' => '',
        'pass' => '',
        'from_name' => 'منصة إبداع'
    ];
    if ($result = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'")) {
        while ($row = $result->fetch_assoc()) {
            $key = $row['setting_key'];
            $val = $row['setting_value'];
            if ($key === 'smtp_host') $smtp['host'] = $val;
            elseif ($key === 'smtp_port') $smtp['port'] = (int)$val;
            elseif ($key === 'smtp_user') $smtp['user'] = $val;
            elseif ($key === 'smtp_pass') $smtp['pass'] = $val;
            elseif ($key === 'smtp_from_name') $smtp['from_name'] = $val;
        }
    }

    if (empty($smtp['user']) || empty($smtp['pass'])) {
        // Clean up files
        if (file_exists($qrTempPath)) unlink($qrTempPath);
        if (file_exists($pdfPath)) unlink($pdfPath);
        echo json_encode(['success' => false, 'message' => 'إعدادات SMTP غير مكتملة. الرجاء ضبطها من الإعدادات.']);
        exit;
    }

    // إرسال البريد باستخدام PHPMailer مع المرفق
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['user'];
        $mail->Password = $smtp['pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtp['port'];
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($smtp['user'], $smtp['from_name']);
        $mail->addAddress($student['email'], $student['full_name']);
        $mail->addAttachment($pdfPath, 'student_card_' . $student_id . '.pdf');

        $mail->isHTML(true);
        $mail->Subject = 'بطاقة الطالب - منصة إبداع';
        $mail->Body = "<div dir='rtl' style='font-family: Arial, sans-serif;'>
            <h2 style='color:#2980b9'>عزيزنا/عزيزتنا {$student['full_name']}</h2>
            <p>نرفق لك بطاقة الطالب الخاصة بك من منصة إبداع للتدريب والتأهيل.</p>
            <p><strong>معلومات البطاقة:</strong></p>
            <ul>
                <li>رقم الطالب: #" . str_pad($student['id'], 6, '0', STR_PAD_LEFT) . "</li>
                " . (!empty($student['course_title']) ? "<li>الدورة: {$student['course_title']}</li>" : "") . "
            </ul>
            <p>يمكنك استخدام رمز QR في البطاقة للتحقق من صحتها عبر مسحه ضوئياً.</p>
        </div>";
        $mail->AltBody = 'مرفق بطاقة الطالب الخاصة بك.';

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'تم إرسال البطاقة بنجاح إلى ' . $student['email']]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'فشل إرسال البريد: ' . $mail->ErrorInfo]);
    } finally {
        // تنظيف الملفات المؤقتة
        if (file_exists($pdfPath)) unlink($pdfPath);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
}
?>
