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

try {
    $projectModel = new ProjectModel();
    $projects = $projectModel->listProjects((int)$userId);

    echo json_encode([
        'ok' => true,
        'projects' => $projects,
        'count' => count($projects)
    ]);
} catch (Exception $e) {
    error_log('Error en list_projects: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error al cargar proyectos',
        'error' => $e->getMessage()
    ]);
}
