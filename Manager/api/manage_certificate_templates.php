<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../includes/session_security.php';

SessionSecurity::startSecureSession();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['manager', 'technical'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAll':
        getAllTemplates($conn);
        break;
    case 'create':
        createTemplate($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getAllTemplates($conn) {
    $result = $conn->query("SELECT id, name, updated_at FROM certificate_templates ORDER BY updated_at DESC");
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    echo json_encode(['success' => true, 'templates' => $templates]);
}

function createTemplate($conn) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['name']) || empty($data['template_json'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Template name and content are required.']);
        return;
    }

    $name = $conn->real_escape_string($data['name']);
    $template_json = $conn->real_escape_string($data['template_json']);

    $stmt = $conn->prepare("INSERT INTO certificate_templates (name, template_json) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $template_json);

    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        echo json_encode(['success' => true, 'message' => 'Template created successfully.', 'id' => $new_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create template.']);
    }
    $stmt->close();
}

$conn->close();
