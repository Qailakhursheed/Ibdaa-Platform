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
    case 'get':
        getTemplate($conn);
        break;
    case 'save': // Handles both create and update
        saveTemplate($conn);
        break;
    case 'delete':
        deleteTemplate($conn);
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

function getTemplate($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM certificate_templates WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'template' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
    }
}

function saveTemplate($conn) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['name']) || empty($data['template_json'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Template name and content are required.']);
        return;
    }

    $name = $conn->real_escape_string($data['name']);
    $template_json = $conn->real_escape_string($data['template_json']);
    $id = isset($data['id']) ? intval($data['id']) : 0;

    if ($id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE certificate_templates SET name = ?, template_json = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $name, $template_json, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Template updated successfully.', 'id' => $id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update template.']);
        }
    } else {
        // Create
        $stmt = $conn->prepare("INSERT INTO certificate_templates (name, template_json) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $template_json);
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            echo json_encode(['success' => true, 'message' => 'Template created successfully.', 'id' => $new_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create template.']);
        }
    }
}

function deleteTemplate($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($data['id']) ? intval($data['id']) : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM certificate_templates WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Template deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete template']);
    }
}

$conn->close();
