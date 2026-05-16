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

$name = trim($input['name'] ?? '');
if ($name === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'El nombre del proyecto es obligatorio']);
    exit;
}

$color = trim($input['color'] ?? '#1B5CFF');
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
    $color = '#1B5CFF';
}

$dueDate = trim($input['due_date'] ?? '');
if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
    $dueDate = '';
}

try {
    $projectModel = new ProjectModel();
    $project = $projectModel->createProject([
        'name' => $name,
        'description' => trim($input['description'] ?? ''),
        'color' => $color,
        'due_date' => $dueDate ?: null,
        'status' => 'active'
    ], (int)$userId);

    echo json_encode([
        'ok' => true,
        'message' => 'Proyecto creado correctamente',
        'project' => $project
    ]);
} catch (Exception $e) {
    error_log('Error en create_project: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error al crear el proyecto',
        'error' => $e->getMessage()
    ]);
}
