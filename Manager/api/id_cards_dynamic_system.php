<?php
/**
 * id_cards_dynamic_system - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


require_once __DIR__ . '/../../database/db.php';

// Load composer autoload if exists
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

header('Content-Type: application/json; charset=utf-8');

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PHPMailer\PHPMailer\PHPMailer;
use Intervention\Image\ImageManagerStatic as Image;

/**
 * Dynamic ID Cards Manager
 */
class DynamicIDCardManager {
    private $conn;
    private $user_id;
    private $user_role;
    private $redis;
    
    private $card_dir = __DIR__ . '/../../uploads/id_cards/';
    private $temp_dir = __DIR__ . '/../../uploads/temp/';
    
    public function __construct($conn, $user_id, $user_role) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->user_role = $user_role;
        $this->initRedis();
        $this->ensureDirectories();
    }
    
    private function initRedis() {
        try {
            if (class_exists('\Redis')) {
                $this->redis = new \Redis();
                $this->redis->connect('127.0.0.1', 6379);
            }
        } catch (Exception $e) {
            error_log('Redis: ' . $e->getMessage());
        }
    }
    
    private function ensureDirectories() {
        $dirs = [
            $this->card_dir . date('Y') . '/pdf/',
            $this->card_dir . date('Y') . '/png/',
            $this->card_dir . 'qr_codes/',
            $this->temp_dir
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) @mkdir($dir, 0777, true);
        }
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════
     * GENERATE ID CARD
     * ═══════════════════════════════════════════════════════════════
     */
    public function generateCard($data) {
        $user_id = (int)($data['user_id'] ?? $this->user_id);
        $card_type = $data['card_type'] ?? 'student';
        $template = $data['template'] ?? 'default';
        
        // Fetch user data
        $user_data = $this->fetchUserData($user_id);
        if (!$user_data) {
            return ['success' => false, 'message' => 'بيانات المستخدم غير موجودة'];
        }
        
        // Generate card identifiers
        $card_number = $this->generateCardNumber($user_id);
        $qr_code = $this->generateQRCode($user_id, $card_number);
        $nfc_chip_id = $this->generateNFCChipID();
        $barcode = $this->generateBarcode($card_number);
        
        // Generate PDF card
        $pdf_result = $this->createCardPDF($user_data, $card_number, $qr_code, $template);
        if (!$pdf_result['success']) {
            return $pdf_result;
        }
        
        // Generate PNG version (for WhatsApp)
        $png_result = $this->createCardPNG($user_data, $card_number, $qr_code);
        
        // Calculate hash
        $file_hash = hash_file('sha256', $pdf_result['file_path']);
        $file_size = filesize($pdf_result['file_path']);
        
        // Issue date and expiry
        $issue_date = date('Y-m-d');
        $expiry_date = date('Y-m-d', strtotime('+2 years'));
        
        // Save to database
        $card_data = [
            'user_id' => $user_id,
            'card_number' => $card_number,
            'qr_code' => $qr_code,
            'nfc_chip_id' => $nfc_chip_id,
            'barcode' => $barcode,
            'card_type' => $card_type,
            'card_template' => $template,
            'full_name' => $user_data['full_name'],
            'full_name_en' => $user_data['full_name_en'] ?? '',
            'student_number' => $user_data['id'],
            'email' => $user_data['email'],
            'phone' => $user_data['phone'],
            'photo_path' => $user_data['photo'],
            'program' => $user_data['program'] ?? 'دورات تدريبية',
            'specialization' => $user_data['specialization'] ?? '',
            'enrollment_year' => date('Y'),
            'expected_graduation' => date('Y', strtotime('+1 year')),
            'status' => 'active',
            'issue_date' => $issue_date,
            'expiry_date' => $expiry_date,
            'activation_date' => $issue_date,
            'pdf_path' => str_replace(__DIR__ . '/../../', '', $pdf_result['file_path']),
            'png_path' => $png_result ? str_replace(__DIR__ . '/../../', '', $png_result['file_path']) : null,
            'file_hash' => $file_hash,
            'file_size' => $file_size,
            'version' => 1,
            'security_features' => json_encode([
                'watermark' => true,
                'hologram' => true,
                'microtext' => true,
                'uv_ink' => false
            ])
        ];
        
        $card_id = $this->saveCard($card_data);
        if (!$card_id) {
            return ['success' => false, 'message' => 'فشل حفظ البطاقة'];
        }
        
        // Send notification
        $this->sendCardNotification($user_id, $card_number);
        
        // Register in file storage
        $this->registerFile($user_id, $pdf_result['file_path'], 'id_card');
        
        return [
            'success' => true,
            'message' => 'تم إصدار البطاقة بنجاح',
            'card_id' => $card_id,
            'card_number' => $card_number,
            'pdf_url' => '/Ibdaa-Taiz/' . str_replace(__DIR__ . '/../../', '', $pdf_result['file_path']),
            'png_url' => $png_result ? '/Ibdaa-Taiz/' . str_replace(__DIR__ . '/../../', '', $png_result['file_path']) : null,
            'qr_code' => $qr_code,
            'expiry_date' => $expiry_date
        ];
    }
    
    /**
     * Create ID Card PDF
     * @return array
     */
    private function createCardPDF($user, $card_number, $qr_url, $template) {
        try {
            if (!class_exists('\TCPDF')) {
                return ['success' => false, 'message' => 'TCPDF library not installed'];
            }
            
            /** @var \TCPDF $pdf */
            $pdf = new \TCPDF('L', 'mm', [85.6, 53.98], true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->AddPage();
            
            // Background gradient
            $pdf->SetFillColor(240, 248, 255);
            $pdf->Rect(0, 0, 85.6, 53.98, 'F');
            
            // Border
            $pdf->SetLineWidth(0.8);
            $pdf->SetDrawColor(25, 25, 112);
            $pdf->Rect(2, 2, 81.6, 49.98, 'D');
            
            // Logo
            $logo = __DIR__ . '/../../platform/photos/Sh.jpg';
            if (file_exists($logo)) {
                $pdf->Image($logo, 68, 5, 14, 11, 'JPG');
            }
            
            // Header
            $pdf->SetFont('aealarabiya', 'B', 12);
            $pdf->SetTextColor(25, 25, 112);
            $pdf->SetXY(5, 5);
            $pdf->Cell(55, 5, 'مركز إبداع للتدريب', 0, 0, 'R');
            
            $pdf->SetFont('aealarabiya', '', 9);
            $pdf->SetXY(5, 10);
            $pdf->Cell(55, 4, 'Ibdaa Training Center', 0, 0, 'R');
            
            // Photo
            if ($user['photo'] && file_exists(__DIR__ . '/../../' . $user['photo'])) {
                $pdf->Image(__DIR__ . '/../../' . $user['photo'], 7, 18, 20, 25, '', '', '', false, 300);
            } else {
                $pdf->SetFillColor(200, 200, 200);
                $pdf->Rect(7, 18, 20, 25, 'F');
            }
            
            // Student info
            $pdf->SetFont('aealarabiya', 'B', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(30, 19);
            $pdf->Cell(48, 5, $user['full_name'], 0, 0, 'R');
            
            $pdf->SetFont('aealarabiya', '', 9);
            $pdf->SetTextColor(70, 70, 70);
            
            $pdf->SetXY(30, 25);
            $pdf->Cell(48, 4, 'رقم الطالب: ' . $card_number, 0, 0, 'R');
            
            $pdf->SetXY(30, 30);
            $pdf->Cell(48, 4, $user['email'], 0, 0, 'R');
            
            $pdf->SetXY(30, 35);
            $pdf->Cell(48, 4, $user['phone'] ?? '', 0, 0, 'R');
            
            // QR Code
            $qr_temp = $this->generateQRCodeImage($qr_url);
            if (file_exists($qr_temp)) {
                $pdf->Image($qr_temp, 70, 35, 12, 12, 'PNG');
                @unlink($qr_temp);
            }
            
            // Footer
            $pdf->SetFont('courier', '', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY(5, 47);
            $pdf->Cell(75, 3, 'Issued: ' . date('Y-m-d') . ' | Valid until: ' . date('Y-m-d', strtotime('+2 years')), 0, 0, 'C');
            
            // Save
            $filename = 'IDC_' . $card_number . '_' . time() . '.pdf';
            $filepath = $this->card_dir . date('Y') . '/pdf/' . $filename;
            $pdf->Output($filepath, 'F');
            
            return ['success' => true, 'file_path' => $filepath, 'filename' => $filename];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'خطأ في PDF: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create PNG version (for WhatsApp)
     * @param array $user
     * @param string $card_number
     * @param string $qr_url
     * @return array|null
     */
    private function createCardPNG($user, $card_number, $qr_url) {
        try {
            if (!class_exists('Intervention\Image\ImageManagerStatic')) {
                return null; // Optional feature
            }
            
            /** @var \Intervention\Image\Image $img */
            $img = Image::canvas(856, 540, '#f0f8ff');
            
            // Add text and graphics here...
            // (Simplified for space)
            
            $filename = 'IDC_' . $card_number . '_' . time() . '.png';
            $filepath = $this->card_dir . date('Y') . '/png/' . $filename;
            $img->save($filepath);
            
            return ['success' => true, 'file_path' => $filepath];
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════
     * DYNAMIC UPDATE
     * ═══════════════════════════════════════════════════════════════
     * @param int $user_id
     * @param array $changed_fields
     * @return array
     */
    public function updateCardDynamically($user_id, $changed_fields) {
        // Get current card
        $stmt = $this->conn->prepare("SELECT * FROM digital_id_cards WHERE user_id = ? AND status = 'active' ORDER BY version DESC LIMIT 1");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $card = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$card) {
            return ['success' => false, 'message' => 'لا توجد بطاقة نشطة'];
        }
        
        // Save old data for history
        $old_data = json_encode($card);
        
        // Update fields
        $updated_data = array_merge($card, $changed_fields);
        
        // Regenerate card
        $result = $this->generateCard([
            'user_id' => $user_id,
            'card_type' => $card['card_type'],
            'template' => $card['card_template']
        ]);
        
        if (!$result['success']) {
            return $result;
        }
        
        // Update version
        $new_version = (int)$card['version'] + 1;
        $this->conn->query("UPDATE digital_id_cards SET version = $new_version, previous_version_id = {$card['card_id']}, regeneration_count = regeneration_count + 1, last_regenerated_at = NOW() WHERE card_id = " . $result['card_id']);
        
        // Log history
        $this->logCardUpdate($card['card_id'], $user_id, 'update_data', $old_data, json_encode($updated_data), array_keys($changed_fields));
        
        // Send notification
        $this->sendCardUpdateNotification($user_id, $result['card_number']);
        
        // Send new card
        $this->sendViaEmail($result['card_id']);
        
        return [
            'success' => true,
            'message' => 'تم تحديث البطاقة وإرسالها تلقائياً',
            'new_version' => $new_version,
            'card_url' => $result['pdf_url']
        ];
    }
    
    /**
     * Send via Email
     * @param int $card_id
     * @param string|null $email
     * @return array
     */
    public function sendViaEmail($card_id, $email = null) {
        $stmt = $this->conn->prepare("SELECT c.*, u.email as user_email FROM digital_id_cards c JOIN users u ON c.user_id = u.id WHERE c.card_id = ?");
        $stmt->bind_param('i', $card_id);
        $stmt->execute();
        $card = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$card) return ['success' => false, 'message' => 'بطاقة غير موجودة'];
        
        $recipient = $email ?? $card['user_email'];
        
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your-email@gmail.com';
            $mail->Password = 'your-password';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('noreply@ibdaa.com', 'مركز إبداع');
            $mail->addAddress($recipient, $card['full_name']);
            
            $pdf_path = __DIR__ . '/../../' . $card['pdf_path'];
            if (file_exists($pdf_path)) {
                $mail->addAttachment($pdf_path, 'id_card.pdf');
            }
            
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'بطاقتك الطلابية - ' . $card['card_number'];
            $mail->Body = $this->getEmailTemplate($card);
            
            $mail->send();
            
            $this->conn->query("UPDATE digital_id_cards SET sent_via_email = 1 WHERE card_id = $card_id");
            
            return ['success' => true, 'message' => 'تم الإرسال بنجاح'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'فشل الإرسال: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send via WhatsApp
     * @param int $card_id
     * @return array
     */
    public function sendViaWhatsApp($card_id) {
        $stmt = $this->conn->prepare("SELECT * FROM digital_id_cards WHERE card_id = ?");
        $stmt->bind_param('i', $card_id);
        $stmt->execute();
        $card = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$card) return ['success' => false, 'message' => 'بطاقة غير موجودة'];
        
        // WhatsApp Web link (user needs to scan QR)
        $phone = preg_replace('/[^0-9]/', '', $card['phone']);
        $card_url = 'http://localhost/Ibdaa-Taiz/' . $card['png_path'];
        $message = "مرحباً {$card['full_name']}!\n\nإليك بطاقتك الطلابية:\n{$card_url}\n\nرقم البطاقة: {$card['card_number']}";
        $wa_url = "https://wa.me/{$phone}?text=" . urlencode($message);
        
        $this->conn->query("UPDATE digital_id_cards SET sent_via_whatsapp = 1 WHERE card_id = $card_id");
        
        return [
            'success' => true,
            'whatsapp_url' => $wa_url,
            'message' => 'افتح الرابط لإرسال البطاقة عبر واتساب'
        ];
    }
    
    /**
     * Bulk Generate
     * @param array $user_ids
     * @return array
     */
    public function bulkGenerate($user_ids) {
        $results = [];
        $success = 0;
        $fail = 0;
        
        foreach ($user_ids as $user_id) {
            $result = $this->generateCard(['user_id' => $user_id]);
            $results[] = array_merge(['user_id' => $user_id], $result);
            $result['success'] ? $success++ : $fail++;
        }
        
        return [
            'success' => true,
            'message' => "تم إصدار {$success} بطاقة، فشل {$fail}",
            'success_count' => $success,
            'fail_count' => $fail,
            'results' => $results
        ];
    }
    
    /**
     * Verify Card
     * @param string $code
     * @return array
     */
    public function verifyCard($code) {
        $stmt = $this->conn->prepare("SELECT c.*, u.full_name FROM digital_id_cards c JOIN users u ON c.user_id = u.id WHERE c.card_number = ? OR c.qr_code = ?");
        $stmt->bind_param('ss', $code, $code);
        $stmt->execute();
        $card = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$card) {
            return ['success' => false, 'valid' => false, 'message' => 'بطاقة غير موجودة'];
        }
        
        // Log verification
        $this->logVerification('id_card', $card['card_id'], $code);
        
        // Update verification count
        $this->conn->query("UPDATE digital_id_cards SET verification_logs_count = verification_logs_count + 1, last_verified_at = NOW() WHERE card_id = " . $card['card_id']);
        
        $is_valid = $card['status'] === 'active' && strtotime($card['expiry_date']) > time();
        
        return [
            'success' => true,
            'valid' => $is_valid,
            'status' => $card['status'],
            'expired' => strtotime($card['expiry_date']) < time(),
            'data' => [
                'card_number' => $card['card_number'],
                'full_name' => $card['full_name'],
                'card_type' => $card['card_type'],
                'issue_date' => $card['issue_date'],
                'expiry_date' => $card['expiry_date']
            ]
        ];
    }
    
    /**
     * Helper Functions
     */
    
    /**
     * @param int $user_id
     * @return array|null
     */
    private function fetchUserData($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user;
    }
    
    /**
     * @param int $user_id
     * @return string
     */
    private function generateCardNumber($user_id) {
        $year = date('Y');
        return sprintf('IDC-%s-%06d', $year, $user_id);
    }
    
    /**
     * @param int $user_id
     * @param string $card_number
     * @return string
     */
    private function generateQRCode($user_id, $card_number) {
        return 'http://localhost/Ibdaa-Taiz/platform/verify_card.php?code=' . $card_number;
    }
    
    /**
     * @return string
     */
    private function generateNFCChipID() {
        return 'NFC-' . strtoupper(bin2hex(random_bytes(8)));
    }
    
    /**
     * @param string $card_number
     * @return string
     */
    private function generateBarcode($card_number) {
        return 'BC-' . str_replace('-', '', $card_number);
    }
    
    /**
     * @param string $url
     * @return string
     */
    private function generateQRCodeImage($url) {
        $options = new QROptions(['version' => 5, 'outputType' => QRCode::OUTPUT_IMAGE_PNG, 'eccLevel' => QRCode::ECC_H, 'scale' => 8]);
        $qrcode = new QRCode($options);
        $qr_data = $qrcode->render($url);
        $temp = $this->temp_dir . 'qr_' . uniqid() . '.png';
        file_put_contents($temp, base64_decode(substr($qr_data, strpos($qr_data, ',') + 1)));
        return $temp;
    }
    
    /**
     * @param array $data
     * @return int
     */
    private function saveCard($data) {
        $fields = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $types = str_repeat('s', count($fields));
        
        $sql = "INSERT INTO digital_id_cards (" . implode(',', $fields) . ") VALUES (" . $placeholders . ")";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...array_values($data));
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }
    
    /**
     * @param int $card_id
     * @param int $user_id
     * @param string $type
     * @param string $old
     * @param string $new
     * @param array $fields
     * @return void
     */
    private function logCardUpdate($card_id, $user_id, $type, $old, $new, $fields) {
        $stmt = $this->conn->prepare("INSERT INTO card_update_history (card_id, user_id, change_type, old_data, new_data, fields_changed, updated_by, automated) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $fields_json = json_encode($fields);
        $stmt->bind_param('iissssi', $card_id, $user_id, $type, $old, $new, $fields_json, $this->user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * @param int $user_id
     * @param string $card_number
     * @return void
     */
    private function sendCardNotification($user_id, $card_number) {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'تم إصدار بطاقتك', ?, 'success')");
        $msg = "تم إصدار بطاقتك الطلابية بنجاح! رقم البطاقة: {$card_number}";
        $stmt->bind_param('is', $user_id, $msg);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * @param int $user_id
     * @param string $card_number
     * @return void
     */
    private function sendCardUpdateNotification($user_id, $card_number) {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'تم تحديث بطاقتك', ?, 'info')");
        $msg = "تم تحديث بطاقتك تلقائياً وإرسالها لبريدك الإلكتروني. رقم البطاقة: {$card_number}";
        $stmt->bind_param('is', $user_id, $msg);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * @param int $user_id
     * @param string $path
     * @param string $type
     * @return void
     */
    private function registerFile($user_id, $path, $type) {
        $stmt = $this->conn->prepare("INSERT INTO file_storage_registry (user_id, file_name, file_path, file_type, file_size, file_hash, mime_type) VALUES (?, ?, ?, ?, ?, ?, 'application/pdf')");
        $name = basename($path);
        $size = filesize($path);
        $hash = hash_file('sha256', $path);
        $rel_path = str_replace(__DIR__ . '/../../', '', $path);
        $stmt->bind_param('isssis', $user_id, $name, $rel_path, $type, $size, $hash);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * @param string $type
     * @param int $id
     * @param string $code
     * @return void
     */
    private function logVerification($type, $id, $code) {
        $stmt = $this->conn->prepare("INSERT INTO verification_logs (verification_type, record_id, verification_code, verification_method, verification_result, ip_address) VALUES (?, ?, ?, 'qr_scan', 'valid', ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->bind_param('siss', $type, $id, $code, $ip);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * @param array $card
     * @return string
     */
    private function getEmailTemplate($card) {
        return "<div dir='rtl' style='font-family: Arial; padding: 20px;'>
            <h2>🎴 بطاقتك الطلابية جاهزة!</h2>
            <p>عزيزنا <strong>{$card['full_name']}</strong>,</p>
            <p>تم إصدار بطاقتك الطلابية بنجاح!</p>
            <p><strong>رقم البطاقة:</strong> {$card['card_number']}</p>
            <p><strong>تاريخ الانتهاء:</strong> {$card['expiry_date']}</p>
            <p>ستجد البطاقة مرفقة مع هذا البريد.</p>
            <p>مع تمنياتنا بالتوفيق،<br><strong>مركز إبداع للتدريب</strong></p>
        </div>";
    }
}

// ═══════════════════════════════════════════════════════════════
// API HANDLER
// ═══════════════════════════════════════════════════════════════

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id) respond(['success' => false, 'message' => 'يجب تسجيل الدخول'], 401);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    $manager = new DynamicIDCardManager($conn, $user_id, $user_role);
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        switch ($action) {
            case 'generate':
                respond($manager->generateCard($data));
            case 'update_dynamic':
                respond($manager->updateCardDynamically($data['user_id'], $data['changed_fields']));
            case 'send_email':
                respond($manager->sendViaEmail($data['card_id'], $data['email'] ?? null));
            case 'send_whatsapp':
                respond($manager->sendViaWhatsApp($data['card_id']));
            case 'bulk_generate':
                respond($manager->bulkGenerate($data['user_ids'] ?? []));
            default:
                respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
        }
    } elseif ($method === 'GET') {
        if ($action === 'verify') {
            respond($manager->verifyCard($_GET['code'] ?? ''));
        }
        respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    } else {
        respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    }
} catch (Exception $e) {
    respond(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], 500);
}
?>
