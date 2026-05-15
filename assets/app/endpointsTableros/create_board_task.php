<?php
// assets/app/endpointsTableros/create_board_task.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Headers anti-cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Deshabilitar display de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../models/TaskModel.php';

$user_id = $_SESSION['user']['id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'No autenticado']);
    exit;
}

// Leer datos del body
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Payload inválido']);
    exit;
}

// Validar campos obligatorios
$title = trim($input['title'] ?? '');
$column = $input['column'] ?? 'pending'; // pending, in_progress, done

if (empty($title)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'El título es obligatorio']);
    exit;
}

try {
    $taskModel = new TaskModel();
    
    // Mapear columna a status (en español para TaskModel)
    $statusMap = [
        'pending' => 'Pendiente',
        'in_progress' => 'En proceso',
        'done' => 'Completado'
    ];
    
    $status = $statusMap[$column] ?? 'Pendiente';
    
    // Preparar datos de la tarea
    $taskData = [
        'title' => $title,
        'description' => trim($input['description'] ?? ''),
        'status' => $status,
        'priority' => $input['priority'] ?? 'Media', // Alta, Media, Baja
        'due_date' => $input['due_date'] ?? null,
        'created_by' => $user_id,
        'assigned_to' => $input['assigned_to'] ?? null,
        'board_id' => $input['board_id'] ?? 1, // Por defecto, tablero 1
        'column_created' => $column
    ];
    
    // Crear tarea
    $task_id = $taskModel->createTask($taskData);
    
    if ($task_id) {
        echo json_encode([
            'ok' => true,
            'message' => 'Tarea creada exitosamente',
            'task_id' => $task_id,
            'column' => $column
        ]);
    } else {
        throw new Exception('No se pudo crear la tarea');
    }
    
} catch (Exception $e) {
    error_log("Error en create_board_task: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error al crear la tarea',
        'error' => $e->getMessage()
    ]);
}
?>