<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../models/ProjectModel.php';

$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'No autenticado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Payload inválido']);
    exit;
}

$projectId = (int)($input['project_id'] ?? 0);
if ($projectId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Proyecto inválido']);
    exit;
}

try {
    $projectModel = new ProjectModel();
    $archived = $projectModel->archiveProject($projectId, (int)$userId);

    echo json_encode([
        'ok' => true,
        'message' => $archived ? 'Proyecto archivado correctamente' : 'El proyecto ya estaba archivado'
    ]);
} catch (Exception $e) {
    error_log('Error en archive_project: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
}
