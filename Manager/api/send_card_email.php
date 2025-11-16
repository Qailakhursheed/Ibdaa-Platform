<?php
// 📧 Send ID Card via Email
// AI-Powered Email System for Student ID Cards

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../database/db.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Security Check
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
$student_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

if (!in_array($user_role, ['manager', 'technical'])) {
    echo json_encode(['success' => false, 'message' => 'صلاحيات غير كافية']);
    exit;
}

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'رقم الطالب مطلوب']);
    exit;
}

try {
    // Fetch student data
    $stmt = $conn->prepare("
        SELECT 
            u.id, u.full_name, u.email, u.phone,
            c.title as course_title
        FROM users u
        LEFT JOIN enrollments e ON u.id = e.user_id
        LEFT JOIN courses c ON e.course_id = c.course_id
        WHERE u.id = ? AND u.role = 'student'
        LIMIT 1
    ");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'الطالب غير موجود']);
        exit;
    }
    
    $student = $result->fetch_assoc();
    
    if (empty($student['email'])) {
        echo json_encode(['success' => false, 'message' => 'لا يوجد بريد إلكتروني للطالب']);
        exit;
    }

    // Generate Card URL
    $cardUrl = 'http://localhost/Ibdaa-Taiz/Manager/api/generate_id_card_v2.php?id=' . $student_id;
    
    // Generate QR Code as base64
    $verifyUrl = 'http://localhost/Ibdaa-Taiz/platform/verify_student.php?id=' . $student_id;
    $qrOptions = new QROptions([
        'version' => 5,
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel' => QRCode::ECC_H,
        'scale' => 8,
        'imageBase64' => false
    ]);
    $qrcode = new QRCode($qrOptions);
    $qrCodeBase64 = $qrcode->render($verifyUrl);

    // Email HTML Template
    $emailHTML = "
<!DOCTYPE html>
<html dir='rtl' lang='ar'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>بطاقتك الطلابية</title>
</head>
<body style='font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px;'>
    <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3);'>
        
        <!-- Header -->
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center;'>
            <h1 style='color: white; margin: 0; font-size: 32px; font-weight: bold;'>🎴 بطاقتك الطلابية</h1>
            <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;'>منصة إبداع للتدريب والتأهيل</p>
        </div>

        <!-- Content -->
        <div style='padding: 40px 30px;'>
            <p style='font-size: 18px; color: #333; margin-bottom: 20px;'>
                مرحباً <strong>" . htmlspecialchars($student['full_name']) . "</strong>،
            </p>
            
            <p style='font-size: 16px; color: #555; line-height: 1.8; margin-bottom: 30px;'>
                يسعدنا إرسال بطاقتك الطلابية الرقمية من منصة إبداع. يمكنك تنزيلها ومسح رمز QR للتحقق من البيانات.
            </p>

            <!-- Card Info Box -->
            <div style='background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 15px; padding: 25px; margin-bottom: 30px;'>
                <div style='color: white; font-size: 16px; margin-bottom: 10px;'>
                    <strong>📋 معلومات البطاقة:</strong>
                </div>
                <div style='color: rgba(255,255,255,0.95); font-size: 14px; line-height: 2;'>
                    • <strong>رقم الطالب:</strong> " . str_pad($student['id'], 6, '0', STR_PAD_LEFT) . "<br>
                    • <strong>الدورة:</strong> " . htmlspecialchars($student['course_title'] ?? 'غير محدد') . "<br>
                    • <strong>تاريخ الإصدار:</strong> " . date('Y/m/d') . "
                </div>
            </div>

            <!-- Download Button -->
            <div style='text-align: center; margin-bottom: 30px;'>
                <a href='" . $cardUrl . "' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 50px; font-size: 18px; font-weight: bold; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);'>
                    📥 تنزيل البطاقة (PDF)
                </a>
            </div>

            <!-- QR Code -->
            <div style='text-align: center; margin-bottom: 30px;'>
                <div style='background: #f8fafc; border-radius: 15px; padding: 20px; display: inline-block;'>
                    <img src='" . $qrCodeBase64 . "' alt='QR Code' style='width: 200px; height: 200px; border-radius: 10px;'>
                    <p style='color: #64748b; font-size: 12px; margin: 10px 0 0 0;'>امسح الكود للتحقق من البيانات</p>
                </div>
            </div>

            <!-- Features -->
            <div style='background: #f1f5f9; border-radius: 10px; padding: 20px; margin-bottom: 20px;'>
                <div style='color: #334155; font-size: 14px; line-height: 1.8;'>
                    <strong>✨ مميزات البطاقة الذكية:</strong><br>
                    🔐 رمز QR آمن للتحقق الفوري<br>
                    🎨 تصميم احترافي عالي الجودة<br>
                    📱 متوافقة مع جميع الأجهزة<br>
                    🆔 رقم تعريف فريد ومشفر<br>
                    ✅ معتمدة رسمياً من المنصة
                </div>
            </div>

            <p style='font-size: 14px; color: #666; text-align: center; margin-top: 30px;'>
                إذا كان لديك أي استفسار، لا تتردد في التواصل معنا
            </p>
        </div>

        <!-- Footer -->
        <div style='background: #1e293b; padding: 25px; text-align: center;'>
            <p style='color: #94a3b8; font-size: 14px; margin: 0;'>
                © " . date('Y') . " منصة إبداع للتدريب والتأهيل
            </p>
            <p style='color: #64748b; font-size: 12px; margin: 10px 0 0 0;'>
                AI-Powered Smart ID Card System v2.0
            </p>
        </div>
    </div>
</body>
</html>
";

    // Send Email using PHPMailer
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ha717781053@gmail.com';
        $mail->Password = 'YOUR_APP_PASSWORD'; // Update with actual password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom('ha717781053@gmail.com', 'منصة إبداع');
        $mail->addAddress($student['email'], $student['full_name']);
        $mail->isHTML(true);
        $mail->Subject = 'بطاقتك الطلابية من منصة إبداع 🎴';
        $mail->Body = $emailHTML;
        
        $mail->send();
        
        // Log the action
        $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, 'id_card_sent', ?, NOW())");
        $details = json_encode(['student_id' => $student_id, 'email' => $student['email']]);
        $logStmt->bind_param('is', $user_id, $details);
        $logStmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'تم إرسال البطاقة بنجاح إلى ' . $student['email'],
            'email' => $student['email']
        ]);
        
    } catch (PHPMailerException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'فشل إرسال البريد: ' . $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ]);
}
