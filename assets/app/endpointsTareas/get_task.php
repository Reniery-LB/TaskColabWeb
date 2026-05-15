<?php
// assets/app/endpointsTareas/get_task.php
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false, 
        'message' => 'No autorizado: sesión no iniciada'
    ]);
    exit;
}

require_once __DIR__ . '/../../models/TaskModel.php';

try {
    // Obtener ID de la tarea desde GET parameter
    $taskId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$taskId) {
        throw new Exception('ID de tarea no proporcionado');
    }

    $taskModel = new TaskModel();
    $userId = $_SESSION['user']['id'];
    
    // Obtener la tarea específica
    $task = $taskModel->getTaskById($taskId, $userId);
    
    if ($task) {
        echo json_encode([
            'ok' => true,
            'task' => $task
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'message' => 'Tarea no encontrada o no tienes permisos'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en get_task: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
}
?>