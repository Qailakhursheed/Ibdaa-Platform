<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $payload = [
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        echo $encoded === false
            ? '{"success":false,"message":"Fatal Error & JSON encoding failed."}'
            : $encoded;
    }
});

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../platform/db.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use FPDF;

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

$method = $_SERVER['REQUEST_METHOD'];

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً'], JSON_UNESCAPED_UNICODE);
    exit;
}

$allowedIssuers = ['manager', 'technical', 'trainer'];

if ($method === 'GET') {
    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
    if ($courseId <= 0) {
        echo json_encode(['success' => false, 'message' => 'يرجى تحديد الدورة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($userRole === 'student') {
        $studentId = (int) $userId;
    } elseif (in_array($userRole, $allowedIssuers, true)) {
        $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : (int) ($_GET['user_id'] ?? 0);
        if ($studentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'يرجى تحديد الطالب'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare('SELECT certificate_code, verification_code, file_path, issued_at FROM certificates WHERE user_id = ? AND course_id = ? LIMIT 1');
    $stmt->bind_param('ii', $studentId, $courseId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'لم يتم إصدار شهادة بعد لهذا المسار'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fileUrl = $result['file_path'] ?: '';
    echo json_encode([
        'success' => true,
        'data' => [
            'certificate_code' => $result['certificate_code'],
            'verification_code' => $result['verification_code'],
            'issued_at' => $result['issued_at'],
            'file_url' => $fileUrl
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($userRole, $allowedIssuers, true)) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بإصدار الشهادات'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

$studentId = isset($data['student_id']) ? (int) $data['student_id'] : (int) ($data['user_id'] ?? 0);
$courseId = isset($data['course_id']) ? (int) $data['course_id'] : 0;
$enrollmentId = isset($data['enrollment_id']) ? (int) $data['enrollment_id'] : 0;
$force = !empty($data['regenerate']);

if ($enrollmentId > 0 && ($studentId === 0 || $courseId === 0)) {
    $stmt = $conn->prepare('SELECT user_id, course_id FROM enrollments WHERE enrollment_id = ? LIMIT 1');
    $stmt->bind_param('i', $enrollmentId);
    $stmt->execute();
    $enrollmentRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($enrollmentRow) {
        $studentId = (int) $enrollmentRow['user_id'];
        $courseId = (int) $enrollmentRow['course_id'];
    }
}

if ($studentId <= 0 || $courseId <= 0) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة لإصدار الشهادة'], JSON_UNESCAPED_UNICODE);
    exit;
}

// جلب بيانات الطالب والدورة والتحقق من حالة الإكمال
$infoStmt = $conn->prepare(
    'SELECT e.status, u.full_name, u.full_name_en, u.email, u.photo as photo, c.title AS course_title, c.start_date, c.end_date
     FROM enrollments e
     JOIN users u ON u.id = e.user_id
     JOIN courses c ON c.course_id = e.course_id
     WHERE e.user_id = ? AND e.course_id = ?
     LIMIT 1'
);
$infoStmt->bind_param('ii', $studentId, $courseId);
$infoStmt->execute();
$enrollment = $infoStmt->get_result()->fetch_assoc();
$infoStmt->close();

if (!$enrollment) {
    echo json_encode(['success' => false, 'message' => 'لم يتم العثور على تسجيل للطالب في هذه الدورة'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($enrollment['status'] !== 'completed') {
    echo json_encode(['success' => false, 'message' => 'لا يمكن إصدار شهادة قبل إكمال الدورة'], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentCertificateStmt = $conn->prepare('SELECT certificate_id, certificate_code, verification_code, file_path FROM certificates WHERE user_id = ? AND course_id = ? LIMIT 1');
$currentCertificateStmt->bind_param('ii', $studentId, $courseId);
$currentCertificateStmt->execute();
$currentCertificate = $currentCertificateStmt->get_result()->fetch_assoc();
$currentCertificateStmt->close();

if ($currentCertificate && !$force) {
    echo json_encode([
        'success' => true,
        'message' => 'تم إصدار الشهادة مسبقاً'. ($currentCertificate['file_path'] ? '، تم توفير رابط التحميل.' : ''),
        'data' => [
            'certificate_code' => $currentCertificate['certificate_code'],
            'verification_code' => $currentCertificate['verification_code'],
            'file_url' => $currentCertificate['file_path']
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function makeCode(mysqli $conn, string $column, string $prefix = 'IB'): string {
    do {
        $random = strtoupper(bin2hex(random_bytes(4)));
        $code = $prefix . '-' . date('ymd') . '-' . $random;
        $check = $conn->prepare("SELECT 1 FROM certificates WHERE {$column} = ? LIMIT 1");
        $check->bind_param('s', $code);
        $check->execute();
        $exists = $check->get_result()->fetch_row();
        $check->close();
    } while ($exists);
    return $code;
}

$certificateCode = makeCode($conn, 'certificate_code', 'IBDAA');
$verificationCode = makeCode($conn, 'verification_code', 'VER');

$uploadsDir = __DIR__ . '/../../uploads/certificates';
if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
    echo json_encode(['success' => false, 'message' => 'تعذر إنشاء مجلد الشهادات'], JSON_UNESCAPED_UNICODE);
    exit;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$verifyUrl = $protocol . '://' . $host . '/platform/verify_certificate.php?code=' . urlencode($verificationCode);

$options = new QROptions([
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel' => QRCode::ECC_L,
    'scale' => 6,
]);
$qrcode = (new QRCode($options))->render($verifyUrl);
$qrTempPath = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
file_put_contents($qrTempPath, $qrcode);

$pdfFileName = 'certificate_' . $certificateCode . '.pdf';
$pdfPath = $uploadsDir . '/' . $pdfFileName;

function ar_text(string $text): string {
    $converted = iconv('UTF-8', 'windows-1256//IGNORE', $text);
    return $converted !== false ? $converted : $text;
}

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetDrawColor(2, 132, 199);
$pdf->SetLineWidth(1.2);
$pdf->Rect(10, 10, 277, 190, 'D');

$pdf->SetTextColor(2, 6, 23);
$pdf->SetFont('Arial', 'B', 36);
$pdf->Cell(0, 30, ar_text('شهادة إتمام دورة'), 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('Arial', '', 18);
$pdf->Cell(0, 12, ar_text('تُمنح هذه الشهادة إلى'), 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 28);
$pdf->SetTextColor(8, 47, 73);
$pdf->Cell(0, 18, ar_text($enrollment['full_name']), 0, 1, 'C');

$pdf->Ln(4);
$pdf->SetFont('Arial', '', 16);
$pdf->SetTextColor(2, 6, 23);
$pdf->Cell(0, 12, ar_text('وذلك لاجتيازه متطلبات دورة: ' . $enrollment['course_title']), 0, 1, 'C');

$pdf->Ln(3);
$pdf->SetFont('Arial', '', 12);
$durationText = ar_text('الفترة التدريبية: ' . ($enrollment['start_date'] ?: '-') . ' إلى ' . ($enrollment['end_date'] ?: '-'));
$pdf->Cell(0, 10, $durationText, 0, 1, 'C');

$pdf->Ln(12);
$pdf->SetFont('Arial', '', 14);
$pdf->Cell(0, 10, ar_text('رمز الشهادة: ' . $certificateCode), 0, 1, 'C');
$pdf->Cell(0, 10, ar_text('رمز التحقق: ' . $verificationCode), 0, 1, 'C');

$pdf->Image($qrTempPath, 130, 135, 40, 40);
$pdf->Ln(28);
$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 10, ar_text('قم بمسح رمز QR للتحقق من صحة الشهادة عبر المنصة.'), 0, 1, 'C');

// ===== Add visible logo and watermark (if available) =====
$logoPath = __DIR__ . '/../../platform/photos/Sh.jpg';
$watermarkTemp = null;
if (file_exists($logoPath)) {
    // Top-left visible logo
    try {
        $pdf->Image($logoPath, 12, 12, 28, 28);
    } catch (Exception $e) {
        // ignore image errors, continue
    }

    // Create a semi-transparent watermark PNG from the logo (GD)
    $watermarkTemp = tempnam(sys_get_temp_dir(), 'wm_') . '.png';
    if (function_exists('imagecreatefromstring')) {
        $imgData = @file_get_contents($logoPath);
        if ($imgData !== false) {
            $srcImg = @imagecreatefromstring($imgData);
            if ($srcImg !== false) {
                $w = imagesx($srcImg);
                $h = imagesy($srcImg);
                // target size in mm -> convert to px roughly (FPDF uses mm; we'll pick 450px wide for a large watermark)
                $targetW = 800;
                $targetH = intval($h * ($targetW / $w));
                $wm = imagecreatetruecolor($targetW, $targetH);
                imagesavealpha($wm, true);
                $trans_colour = imagecolorallocatealpha($wm, 0, 0, 0, 127);
                imagefill($wm, 0, 0, $trans_colour);
                // resample
                imagecopyresampled($wm, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $w, $h);
                // apply transparency by merging with a transparent canvas using imagecopymerge (percent 40)
                $overlay = imagecreatetruecolor($targetW, $targetH);
                imagesavealpha($overlay, true);
                $trans_colour2 = imagecolorallocatealpha($overlay, 0, 0, 0, 127);
                imagefill($overlay, 0, 0, $trans_colour2);
                // copy resampled into overlay with low opacity
                imagecopymerge($overlay, $wm, 0, 0, 0, 0, $targetW, $targetH, 40);
                // save as PNG
                imagepng($overlay, $watermarkTemp);
                imagedestroy($overlay);
                imagedestroy($wm);
            }
            imagedestroy($srcImg);
        }
    }

    // Place watermark centered and large if created
    if ($watermarkTemp && file_exists($watermarkTemp)) {
        // estimate placement and size (mm) on A4 landscape
        $wmWidthMM = 180; // wide watermark
        $wmHeightMM = 120;
        $wmX = (210 - $wmWidthMM) / 2 + 10; // A4 landscape width 297mm, but page margins used earlier; adjust conservatively
        $wmY = 40;
        try {
            $pdf->Image($watermarkTemp, $wmX, $wmY, $wmWidthMM, $wmHeightMM);
        } catch (Exception $e) {
            // ignore
        }
    }
}

// ===== Place student photo if available (only when uploaded) =====
$studentPhotoPath = null;
if (!empty($enrollment['photo'])) {
    $candidate = __DIR__ . '/../../' . ltrim($enrollment['photo'], '/\\');
    if (file_exists($candidate)) {
        $studentPhotoPath = $candidate;
    }
}

if ($studentPhotoPath) {
    // place on right side, inside a white framed box
    $photoW = 30;
    $photoH = 40;
    $photoX = 250; // near right edge for A4 landscape
    $photoY = 40;
    // white background box
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect($photoX - 1, $photoY - 1, $photoW + 2, $photoH + 2, 'F');
    // draw photo
    try {
        $pdf->Image($studentPhotoPath, $photoX, $photoY, $photoW, $photoH);
    } catch (Exception $e) {
        // ignore
    }
}

$pdf->Output('F', $pdfPath);

if (is_file($qrTempPath)) {
    unlink($qrTempPath);
}
if (!empty($watermarkTemp) && is_file($watermarkTemp)) {
    @unlink($watermarkTemp);
}

$fileUrl = 'uploads/certificates/' . $pdfFileName;

if ($currentCertificate) {
    $update = $conn->prepare('UPDATE certificates SET certificate_code = ?, verification_code = ?, issued_at = NOW(), issued_by = ?, file_path = ?, status = "issued" WHERE certificate_id = ?');
    $update->bind_param('ssisi', $certificateCode, $verificationCode, $userId, $fileUrl, $currentCertificate['certificate_id']);
    $update->execute();
    $update->close();
} else {
    $insert = $conn->prepare('INSERT INTO certificates (user_id, course_id, certificate_code, verification_code, issued_at, issued_by, file_path, status) VALUES (?, ?, ?, ?, NOW(), ?, ?, "issued")');
    $insert->bind_param('iissis', $studentId, $courseId, $certificateCode, $verificationCode, $userId, $fileUrl);
    $insert->execute();
    $insert->close();
}

echo json_encode([
    'success' => true,
    'message' => 'تم إصدار الشهادة بنجاح',
    'data' => [
        'certificate_code' => $certificateCode,
        'verification_code' => $verificationCode,
        'file_url' => $fileUrl
    ]
], JSON_UNESCAPED_UNICODE);
?>
