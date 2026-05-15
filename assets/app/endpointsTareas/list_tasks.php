<?php
// assets/app/endpointsTareas/list_tasks.php
session_start();
header('Content-Type: application/json');

// Verificar sesión usando la estructura correcta
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false, 
        'message' => 'No autorizado: sesión no iniciada',
        'debug' => 'Falta user[id] en sesión'
    ]);
    exit;
}

require_once __DIR__ . '/../../models/TaskModel.php';

try {
    $taskModel = new TaskModel();
    $userId = $_SESSION['user']['id'];
    
    error_log("User ID en list_tasks: " . $userId);
    
    $tasks = $taskModel->getUserTasks($userId);
    
    echo json_encode([
        'ok' => true,
        'tasks' => $tasks,
        'count' => count($tasks),
        'debug_user_id' => $userId
    ]);
    
} catch (Exception $e) {
    error_log("Error en list_tasks: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'message' => $e->getMessage()
    ]);
}
?>