<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session_security.php';

SessionSecurity::startSecureSession();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['manager', 'technical'])) {
    die('Unauthorized');
}

$template_id = $_GET['template_id'] ?? 0;
$student_id = $_GET['student_id'] ?? 0;
$course_id = $_GET['course_id'] ?? 0;

if (empty($template_id) || empty($student_id) || empty($course_id)) {
    die('Missing parameters.');
}

// 1. Fetch the template
$stmt = $conn->prepare("SELECT template_json FROM certificate_templates WHERE id = ?");
$stmt->bind_param("i", $template_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Template not found.');
}
$template_row = $result->fetch_assoc();
$template_data = json_decode($template_row['template_json'], true);
$stmt->close();

// 2. Fetch student and course data (using the API for consistency)
$api_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/get_certificate_data.php';
$api_url .= "?student_id=$student_id&course_id=$course_id";

// Use a simple file_get_contents, assuming allow_url_fopen is on.
// A more robust solution would use cURL.
$certificate_data_json = file_get_contents($api_url);
$certificate_data = json_decode($certificate_data_json, true);

if (!$certificate_data || !$certificate_data['success']) {
    die('Could not fetch certificate data.');
}
$data = $certificate_data['data'];

// 3. Create PDF using TCPDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Ibdaa Taiz Platform');
$pdf->SetTitle('Certificate');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);

$pdf->AddPage();

// Set background
if (!empty($template_data['backgroundImage'])) {
    $bgImage = __DIR__ . '/../' . $template_data['backgroundImage'];
    if (file_exists($bgImage)) {
        // Get page dimensions
        $w = $pdf->getPageWidth();
        $h = $pdf->getPageHeight();
        // Add image covering the full page
        $pdf->Image($bgImage, 0, 0, $w, $h, '', '', '', false, 300, '', false, false, 0);
    }
} else {
    // Fallback to background color
    $bgColor = $template_data['backgroundColor'] ?? '#ffffff';
    list($r, $g, $b) = sscanf($bgColor, "#%02x%02x%02x");
    $pdf->SetFillColor($r, $g, $b);
    $pdf->Rect(0, 0, $pdf->getPageWidth(), $pdf->getPageHeight(), 'F');
}


// 4. Add elements to the PDF
foreach ($template_data['elements'] as $element) {
    $html = $element['html'];
    // Replace placeholders
    $html = str_replace('[student_name]', $data['student_name'], $html);
    $html = str_replace('[course_name]', $data['course_name'], $html);
    $html = str_replace('[completion_date]', $data['completion_date'], $html);
    $html = str_replace('[certificate_id]', $data['certificate_id'], $html);

    // This is a simplified conversion of styles.
    // A more complete solution would parse the CSS more thoroughly.
    $style = $element['style'];
    
    // Extract font size
    preg_match('/font-size:\s*(\d+)px/', $style, $matches);
    $fontSize = $matches[1] ?? 12;
    $pdf->SetFont('dejavusans', '', $fontSize);

    // Extract color
    preg_match('/color:\s*(#[0-9a-fA-F]{6})/', $style, $matches);
    if (isset($matches[1])) {
        list($r, $g, $b) = sscanf($matches[1], "#%02x%02x%02x");
        $pdf->SetTextColor($r, $g, $b);
    } else {
        $pdf->SetTextColor(0, 0, 0);
    }
    
    // Extract position
    preg_match('/top:\s*(\d+)px/', $style, $topMatches);
    $y = $topMatches[1] ?? 10;
    preg_match('/left:\s*(\d+)px/', $style, $leftMatches);
    $x = $leftMatches[1] ?? 10;

    // Convert px to mm (approximate)
    $x_mm = $x * 0.264583;
    $y_mm = $y * 0.264583;

    $pdf->writeHTMLCell(0, 0, $x_mm, $y_mm, $html, 0, 1, 0, true, '', true);
}


// 5. Output the PDF
$pdf->Output('certificate.pdf', 'I');

$conn->close();
