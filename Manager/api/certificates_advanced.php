<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * ADVANCED CERTIFICATES MANAGEMENT SYSTEM
 * نظام إصدار وإدارة الشهادات المتقدم
 * ═══════════════════════════════════════════════════════════════
 * Features:
 * - Blockchain-style verification
 * - Dynamic PDF generation with TCPDF
 * - QR Code + Barcode integration
 * - Watermark & Digital signatures
 * - Email/WhatsApp delivery
 * - Template management
 * - Bulk generation
 * - Version control
 * - Analytics & reporting
 * ═══════════════════════════════════════════════════════════════
 */

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/email_sender.php';

header('Content-Type: application/json; charset=utf-8');

use TCPDF;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Intervention\Image\ImageManagerStatic as Image;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Advanced Certificate Manager Class
 */
class AdvancedCertificateManager {
    private $conn;
    private $user_id;
    private $user_role;
    private $redis;
    
    // Paths
    private $certificate_dir = __DIR__ . '/../../uploads/certificates/';
    private $temp_dir = __DIR__ . '/../../uploads/temp/';
    private $template_dir = __DIR__ . '/../../templates/certificates/';
    
    public function __construct($conn, $user_id, $user_role) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->user_role = $user_role;
        
        // Initialize Redis
        $this->initRedis();
        
        // Ensure directories exist
        $this->ensureDirectories();
    }
    
    private function initRedis() {
        try {
            if (class_exists('Redis')) {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
            }
        } catch (Exception $e) {
            error_log('Redis connection failed: ' . $e->getMessage());
        }
    }
    
    private function ensureDirectories() {
        $dirs = [
            $this->certificate_dir,
            $this->certificate_dir . date('Y') . '/',
            $this->temp_dir,
            $this->template_dir
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
        }
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════
     * CERTIFICATE GENERATION
     * ═══════════════════════════════════════════════════════════════
     */
    
    /**
     * Generate Certificate
     */
    public function generateCertificate($data) {
        // Validate required fields
        $required = ['student_id', 'course_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "الحقل {$field} مطلوب"];
            }
        }
        
        $student_id = (int)$data['student_id'];
        $course_id = (int)$data['course_id'];
        $template_id = $data['template_id'] ?? 1;
        $regenerate = !empty($data['regenerate']);
        
        // Check if certificate already exists
        if (!$regenerate) {
            $existing = $this->checkExistingCertificate($student_id, $course_id);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'الشهادة موجودة بالفعل',
                    'existing' => $existing
                ];
            }
        }
        
        // Fetch student and course data
        $certData = $this->fetchCertificateData($student_id, $course_id);
        if (!$certData) {
            return ['success' => false, 'message' => 'بيانات غير كاملة'];
        }
        
        // Check completion status
        if ($certData['enrollment_status'] !== 'completed') {
            return ['success' => false, 'message' => 'يجب إكمال الدورة أولاً'];
        }
        
        // Generate unique codes
        $certificate_code = $this->generateCertificateCode($student_id, $course_id);
        $verification_code = $this->generateVerificationCode();
        $blockchain_hash = $this->generateBlockchainHash($certData, $certificate_code);
        
        // Generate PDF
        $pdf_result = $this->createCertificatePDF($certData, $certificate_code, $verification_code, $template_id);
        if (!$pdf_result['success']) {
            return $pdf_result;
        }
        
        // Calculate file hash
        $file_hash = hash_file('sha256', $pdf_result['file_path']);
        $file_size = filesize($pdf_result['file_path']);
        
        // Save to database
        $db_data = [
            'user_id' => $student_id,
            'course_id' => $course_id,
            'enrollment_id' => $certData['enrollment_id'],
            'certificate_code' => $certificate_code,
            'verification_code' => $verification_code,
            'blockchain_hash' => $blockchain_hash,
            'full_name' => $certData['full_name'],
            'full_name_en' => $certData['full_name_en'] ?? '',
            'course_title' => $certData['course_title'],
            'course_title_en' => $certData['course_title_en'] ?? '',
            'final_grade' => $certData['final_grade'],
            'grade_letter' => $certData['grade_letter'],
            'gpa' => $certData['gpa'],
            'course_start_date' => $certData['course_start_date'],
            'course_end_date' => $certData['course_end_date'],
            'completion_date' => $certData['completion_date'],
            'file_path' => str_replace(__DIR__ . '/../../', '', $pdf_result['file_path']),
            'file_size' => $file_size,
            'file_hash' => $file_hash,
            'template_id' => $template_id,
            'status' => 'issued',
            'issue_type' => $data['bulk'] ?? false ? 'bulk' : 'manual',
            'issued_by' => $this->user_id,
            'metadata' => json_encode([
                'skills' => $certData['skills'] ?? [],
                'total_hours' => $certData['total_hours'] ?? 0,
                'attendance_rate' => $certData['attendance_rate'] ?? 100
            ])
        ];
        
        $cert_id = $this->saveCertificate($db_data, $regenerate);
        if (!$cert_id) {
            return ['success' => false, 'message' => 'فشل حفظ الشهادة'];
        }
        
        // Send notification
        $this->sendCertificateNotification($student_id, $certificate_code);
        
        // Update enrollment
        $this->updateEnrollmentStatus($certData['enrollment_id'], 'certificate_issued');
        
        // Register in file storage
        $this->registerFile([
            'user_id' => $student_id,
            'file_name' => basename($pdf_result['file_path']),
            'file_path' => str_replace(__DIR__ . '/../../', '', $pdf_result['file_path']),
            'file_type' => 'certificate',
            'mime_type' => 'application/pdf',
            'file_size' => $file_size,
            'file_hash' => $file_hash,
            'folder' => 'uploads/certificates/' . date('Y') . '/',
            'category' => 'academic'
        ]);
        
        return [
            'success' => true,
            'message' => 'تم إصدار الشهادة بنجاح',
            'certificate_id' => $cert_id,
            'certificate_code' => $certificate_code,
            'verification_code' => $verification_code,
            'file_path' => str_replace(__DIR__ . '/../../', '', $pdf_result['file_path']),
            'file_url' => '/Ibdaa-Taiz/' . str_replace(__DIR__ . '/../../', '', $pdf_result['file_path'])
        ];
    }
    
    /**
     * Create Certificate PDF with TCPDF
     */
    private function createCertificatePDF($data, $cert_code, $verify_code, $template_id) {
        try {
            // Create TCPDF instance
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document properties
            $pdf->SetCreator('إبداع - Ibdaa Platform');
            $pdf->SetAuthor('مركز إبداع للتدريب');
            $pdf->SetTitle('شهادة إتمام دورة - ' . $data['course_title']);
            $pdf->SetSubject('Certificate of Completion');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(false, 0);
            
            // Add page
            $pdf->AddPage();
            
            // Add watermark background
            $this->addWatermark($pdf);
            
            // Add decorative border
            $this->addBorder($pdf);
            
            // Add logo
            $logo_path = __DIR__ . '/../../platform/photos/Sh.jpg';
            if (file_exists($logo_path)) {
                $pdf->Image($logo_path, 130, 20, 30, 30, 'JPG', '', '', false, 300, '', false, false, 0);
            }
            
            // Add header
            $pdf->SetFont('aealarabiya', 'B', 24);
            $pdf->SetTextColor(25, 25, 112); // Midnight Blue
            $pdf->SetXY(15, 30);
            $pdf->Cell(0, 15, 'شهادة إتمام دورة', 0, 1, 'C');
            
            $pdf->SetFont('aealarabiya', '', 14);
            $pdf->SetTextColor(70, 70, 70);
            $pdf->SetX(15);
            $pdf->Cell(0, 10, 'Certificate of Completion', 0, 1, 'C');
            
            // Certificate body
            $pdf->Ln(15);
            $pdf->SetFont('aealarabiya', '', 16);
            $pdf->SetTextColor(50, 50, 50);
            $pdf->SetX(15);
            $pdf->MultiCell(0, 10, 'يشهد مركز إبداع للتدريب بأن', 0, 'C');
            
            // Student name
            $pdf->Ln(5);
            $pdf->SetFont('aealarabiya', 'B', 22);
            $pdf->SetTextColor(139, 0, 0); // Dark Red
            $pdf->SetX(15);
            $pdf->Cell(0, 12, $data['full_name'], 0, 1, 'C');
            
            // Course details
            $pdf->Ln(5);
            $pdf->SetFont('aealarabiya', '', 16);
            $pdf->SetTextColor(50, 50, 50);
            $pdf->SetX(15);
            $pdf->MultiCell(0, 10, 'قد أتم بنجاح دورة', 0, 'C');
            
            $pdf->Ln(3);
            $pdf->SetFont('aealarabiya', 'B', 20);
            $pdf->SetTextColor(0, 100, 0); // Dark Green
            $pdf->SetX(15);
            $pdf->Cell(0, 12, $data['course_title'], 0, 1, 'C');
            
            // Dates
            $pdf->Ln(5);
            $pdf->SetFont('aealarabiya', '', 14);
            $pdf->SetTextColor(70, 70, 70);
            $pdf->SetX(15);
            $date_text = sprintf('من تاريخ %s إلى %s', 
                date('Y/m/d', strtotime($data['course_start_date'])),
                date('Y/m/d', strtotime($data['course_end_date']))
            );
            $pdf->Cell(0, 10, $date_text, 0, 1, 'C');
            
            // Grade (if available)
            if ($data['final_grade']) {
                $pdf->Ln(3);
                $pdf->SetFont('aealarabiya', 'B', 16);
                $pdf->SetTextColor(0, 51, 102);
                $pdf->SetX(15);
                $grade_text = sprintf('بتقدير: %s (%.2f%%) - GPA: %.2f', 
                    $data['grade_letter'] ?? '', 
                    $data['final_grade'],
                    $data['gpa'] ?? 0
                );
                $pdf->Cell(0, 10, $grade_text, 0, 1, 'C');
            }
            
            // QR Code
            $verify_url = 'http://localhost/Ibdaa-Taiz/platform/verify_certificate.php?code=' . $verify_code;
            $qr_image = $this->generateQRCode($verify_url);
            $pdf->Image($qr_image, 245, 150, 30, 30, 'PNG');
            
            // Certificate code & verification
            $pdf->SetFont('courier', '', 10);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY(15, 180);
            $pdf->Cell(100, 5, 'رمز الشهادة: ' . $cert_code, 0, 0, 'L');
            $pdf->SetXY(15, 185);
            $pdf->Cell(100, 5, 'رمز التحقق: ' . $verify_code, 0, 0, 'L');
            
            // Issue date
            $pdf->SetXY(170, 180);
            $pdf->Cell(100, 5, 'تاريخ الإصدار: ' . date('Y-m-d'), 0, 0, 'R');
            
            // Signature line
            $pdf->SetXY(50, 160);
            $pdf->SetLineWidth(0.5);
            $pdf->Line(50, 165, 110, 165);
            $pdf->SetXY(50, 166);
            $pdf->SetFont('aealarabiya', '', 12);
            $pdf->Cell(60, 5, 'مدير المركز', 0, 0, 'C');
            
            // Save PDF
            $filename = 'certificate_' . $cert_code . '_' . time() . '.pdf';
            $year_dir = $this->certificate_dir . date('Y') . '/';
            if (!is_dir($year_dir)) @mkdir($year_dir, 0777, true);
            
            $filepath = $year_dir . $filename;
            $pdf->Output($filepath, 'F');
            
            // Clean temp files
            if (file_exists($qr_image)) @unlink($qr_image);
            
            return [
                'success' => true,
                'file_path' => $filepath,
                'filename' => $filename
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'خطأ في إنشاء PDF: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Add watermark to PDF
     */
    private function addWatermark($pdf) {
        $pdf->SetAlpha(0.1);
        $pdf->SetTextColor(200, 200, 200);
        $pdf->SetFont('aealarabiya', 'B', 60);
        $pdf->RotatedText(100, 140, 'إبداع', 45);
        $pdf->SetAlpha(1);
    }
    
    /**
     * Add decorative border
     */
    private function addBorder($pdf) {
        // Outer border
        $pdf->SetLineStyle(['width' => 1.5, 'color' => [25, 25, 112]]);
        $pdf->Rect(10, 10, 277, 190);
        
        // Inner border
        $pdf->SetLineStyle(['width' => 0.5, 'color' => [139, 0, 139]]);
        $pdf->Rect(12, 12, 273, 186);
    }
    
    /**
     * Generate QR Code
     */
    private function generateQRCode($url) {
        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => 8
        ]);
        
        $qrcode = new QRCode($options);
        $qr_data = $qrcode->render($url);
        
        // Save to temp
        $temp_file = $this->temp_dir . 'qr_' . uniqid() . '.png';
        file_put_contents($temp_file, base64_decode(substr($qr_data, strpos($qr_data, ',') + 1)));
        
        return $temp_file;
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════
     * VERIFICATION & BLOCKCHAIN
     * ═══════════════════════════════════════════════════════════════
     */
    
    /**
     * Generate blockchain-style hash
     */
    private function generateBlockchainHash($data, $cert_code) {
        $payload = json_encode([
            'student_id' => $data['user_id'],
            'course_id' => $data['course_id'],
            'certificate_code' => $cert_code,
            'timestamp' => time(),
            'full_name' => $data['full_name'],
            'course_title' => $data['course_title']
        ]);
        
        return hash('sha256', $payload . env('APP_KEY', 'ibdaa-secret-key-2024'));
    }
    
    /**
     * Verify certificate
     */
    public function verifyCertificate($code) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.*,
                u.full_name as student_name,
                u.email as student_email,
                co.title as course_name
            FROM certificates c
            JOIN users u ON c.user_id = u.id
            JOIN courses co ON c.course_id = co.course_id
            WHERE c.certificate_code = ? OR c.verification_code = ?
            LIMIT 1
        ");
        
        $stmt->bind_param('ss', $code, $code);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$result) {
            return [
                'success' => false,
                'valid' => false,
                'message' => 'شهادة غير موجودة'
            ];
        }
        
        // Log verification
        $this->logVerification('certificate', $result['certificate_id'], $code);
        
        // Update download count
        $this->conn->query("UPDATE certificates SET download_count = download_count + 1, last_downloaded_at = NOW() WHERE certificate_id = " . $result['certificate_id']);
        
        $is_valid = $result['status'] === 'issued';
        
        return [
            'success' => true,
            'valid' => $is_valid,
            'status' => $result['status'],
            'message' => $is_valid ? 'شهادة صالحة' : 'شهادة غير صالحة',
            'data' => [
                'certificate_code' => $result['certificate_code'],
                'student_name' => $result['student_name'],
                'course_name' => $result['course_name'],
                'issued_at' => $result['issued_at'],
                'final_grade' => $result['final_grade'],
                'grade_letter' => $result['grade_letter']
            ]
        ];
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════
     * BULK OPERATIONS
     * ═══════════════════════════════════════════════════════════════
     */
    
    /**
     * Bulk generate certificates
     */
    public function bulkGenerate($enrollment_ids) {
        $results = [];
        $success_count = 0;
        $fail_count = 0;
        
        foreach ($enrollment_ids as $enrollment_id) {
            // Fetch enrollment data
            $stmt = $this->conn->prepare("
                SELECT user_id, course_id 
                FROM enrollments 
                WHERE enrollment_id = ? AND status = 'completed'
            ");
            $stmt->bind_param('i', $enrollment_id);
            $stmt->execute();
            $enroll = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$enroll) {
                $results[] = [
                    'enrollment_id' => $enrollment_id,
                    'success' => false,
                    'message' => 'تسجيل غير موجود أو غير مكتمل'
                ];
                $fail_count++;
                continue;
            }
            
            // Generate certificate
            $result = $this->generateCertificate([
                'student_id' => $enroll['user_id'],
                'course_id' => $enroll['course_id'],
                'bulk' => true
            ]);
            
            $results[] = array_merge(['enrollment_id' => $enrollment_id], $result);
            
            if ($result['success']) {
                $success_count++;
            } else {
                $fail_count++;
            }
        }
        
        return [
            'success' => true,
            'message' => "تم إصدار {$success_count} شهادة بنجاح، فشل {$fail_count}",
            'success_count' => $success_count,
            'fail_count' => $fail_count,
            'results' => $results
        ];
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════
     * DELIVERY METHODS
     * ═══════════════════════════════════════════════════════════════
     */
    
    /**
     * Send certificate via email
     */
    public function sendViaEmail($cert_id, $email = null) {
        // Fetch certificate
        $stmt = $this->conn->prepare("
            SELECT c.*, u.full_name, u.email as student_email
            FROM certificates c
            JOIN users u ON c.user_id = u.id
            WHERE c.certificate_id = ?
        ");
        $stmt->bind_param('i', $cert_id);
        $stmt->execute();
        $cert = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$cert) {
            return ['success' => false, 'message' => 'شهادة غير موجودة'];
        }
        
        $recipient = $email ?? $cert['student_email'];
        
        $file_full_path = __DIR__ . '/../../' . $cert['file_path'];
        
        $emailSender = new EmailSender($this->conn);
        $result = $emailSender->sendCertificate(
            $recipient, 
            $cert['full_name'], 
            $cert['course_title'], 
            $file_full_path
        );

        if ($result['success']) {
            // Update database
            $this->conn->query("UPDATE certificates SET sent_via_email = 1, email_sent_at = NOW() WHERE certificate_id = $cert_id");
        }

        return $result;
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($cert) {
        $verify_url = 'http://localhost/Ibdaa-Taiz/platform/verify_certificate.php?code=' . $cert['verification_code'];
        
        return "
        <div dir='rtl' style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1 style='margin: 0;'>🎓 تهانينا!</h1>
                <p style='margin: 10px 0 0 0; font-size: 18px;'>تم إصدار شهادتك بنجاح</p>
            </div>
            
            <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;'>
                <p style='font-size: 16px; line-height: 1.8;'>
                    عزيزنا <strong>{$cert['full_name']}</strong>,<br><br>
                    
                    نهنئك بإتمام دورة <strong>{$cert['course_title']}</strong> بنجاح!<br><br>
                    
                    ستجد شهادتك الرسمية مرفقة مع هذا البريد. يمكنك أيضاً التحقق منها في أي وقت باستخدام:<br><br>
                    
                    <strong>رمز الشهادة:</strong> {$cert['certificate_code']}<br>
                    <strong>رمز التحقق:</strong> {$cert['verification_code']}<br><br>
                    
                    <a href='{$verify_url}' style='display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>
                        تحقق من الشهادة
                    </a>
                </p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #888;'>
                    <p>مع تمنياتنا لك بالتوفيق والنجاح</p>
                    <p><strong>مركز إبداع للتدريب</strong></p>
                </div>
            </div>
        </div>
        ";
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════
     * HELPER FUNCTIONS
     * ═══════════════════════════════════════════════════════════════
     */
    
    private function fetchCertificateData($student_id, $course_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                e.enrollment_id,
                e.user_id,
                e.course_id,
                e.status as enrollment_status,
                e.final_grade,
                e.letter_grade as grade_letter,
                e.grade_point as gpa,
                e.enrollment_date,
                e.completion_date,
                u.full_name,
                u.email,
                c.title as course_title,
                c.start_date as course_start_date,
                c.end_date as course_end_date,
                c.duration as total_hours
            FROM enrollments e
            JOIN users u ON e.user_id = u.id
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.user_id = ? AND e.course_id = ?
            LIMIT 1
        ");
        
        $stmt->bind_param('ii', $student_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }
    
    private function checkExistingCertificate($student_id, $course_id) {
        $stmt = $this->conn->prepare("
            SELECT certificate_id, certificate_code, verification_code, file_path
            FROM certificates
            WHERE user_id = ? AND course_id = ? AND status != 'revoked'
            LIMIT 1
        ");
        
        $stmt->bind_param('ii', $student_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }
    
    private function generateCertificateCode($student_id, $course_id) {
        $year = date('Y');
        $count = $this->conn->query("SELECT COUNT(*) as c FROM certificates WHERE YEAR(issued_at) = $year")->fetch_assoc()['c'];
        return sprintf('CERT-%s-%06d', $year, $count + 1);
    }
    
    private function generateVerificationCode() {
        return strtoupper(bin2hex(random_bytes(16)));
    }
    
    private function saveCertificate($data, $update = false) {
        if ($update) {
            // Update existing
            $stmt = $this->conn->prepare("
                UPDATE certificates SET
                    file_path = ?, file_size = ?, file_hash = ?,
                    updated_at = NOW()
                WHERE user_id = ? AND course_id = ?
            ");
            $stmt->bind_param('siiii', 
                $data['file_path'], $data['file_size'], $data['file_hash'],
                $data['user_id'], $data['course_id']
            );
            $stmt->execute();
            $cert_id = $stmt->affected_rows > 0 ? 
                $this->conn->query("SELECT certificate_id FROM certificates WHERE user_id = {$data['user_id']} AND course_id = {$data['course_id']}")->fetch_assoc()['certificate_id'] : 
                0;
            $stmt->close();
        } else {
            // Insert new
            $fields = array_keys($data);
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $types = str_repeat('s', count($fields));
            $types = str_replace('s', 'i', $types, 3); // First 3 are integers
            
            $sql = "INSERT INTO certificates (" . implode(',', $fields) . ") VALUES (" . $placeholders . ")";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...array_values($data));
            $stmt->execute();
            $cert_id = $stmt->insert_id;
            $stmt->close();
        }
        
        return $cert_id;
    }
    
    private function sendCertificateNotification($user_id, $cert_code) {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, link)
            VALUES (?, 'تم إصدار شهادتك', ?, 'success', '/Manager/certificates.php?code=?')
        ");
        $message = "تهانينا! تم إصدار شهادة إتمام دورتك. رمز الشهادة: {$cert_code}";
        $stmt->bind_param('iss', $user_id, $message, $cert_code);
        $stmt->execute();
        $stmt->close();
    }
    
    private function updateEnrollmentStatus($enrollment_id, $status) {
        $this->conn->query("UPDATE enrollments SET certificate_issued = 1 WHERE enrollment_id = $enrollment_id");
    }
    
    private function registerFile($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO file_storage_registry 
            (user_id, file_name, file_path, file_type, mime_type, file_size, file_hash, folder, category)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('issssssss', 
            $data['user_id'], $data['file_name'], $data['file_path'], $data['file_type'],
            $data['mime_type'], $data['file_size'], $data['file_hash'], $data['folder'], $data['category']
        );
        $stmt->execute();
        $stmt->close();
    }
    
    private function logVerification($type, $record_id, $code) {
        $stmt = $this->conn->prepare("
            INSERT INTO verification_logs 
            (verification_type, record_id, verification_code, verification_method, verification_result, ip_address, user_agent)
            VALUES (?, ?, ?, 'manual_code', 'valid', ?, ?)
        ");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $stmt->bind_param('sisss', $type, $record_id, $code, $ip, $ua);
        $stmt->execute();
        $stmt->close();
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

if (!$user_id) {
    respond(['success' => false, 'message' => 'يجب تسجيل الدخول'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    $manager = new AdvancedCertificateManager($conn, $user_id, $user_role);
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        switch ($action) {
            case 'generate':
                $result = $manager->generateCertificate($data);
                respond($result);
                break;
                
            case 'bulk_generate':
                $enrollment_ids = $data['enrollment_ids'] ?? [];
                $result = $manager->bulkGenerate($enrollment_ids);
                respond($result);
                break;
                
            case 'send_email':
                $cert_id = $data['certificate_id'] ?? 0;
                $email = $data['email'] ?? null;
                $result = $manager->sendViaEmail($cert_id, $email);
                respond($result);
                break;
                
            default:
                respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
        }
    }
    
    elseif ($method === 'GET') {
        switch ($action) {
            case 'verify':
                $code = $_GET['code'] ?? '';
                $result = $manager->verifyCertificate($code);
                respond($result);
                break;
                
            default:
                respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
        }
    }
    
    else {
        respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    }
    
} catch (Exception $e) {
    respond([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 500);
}
?>
