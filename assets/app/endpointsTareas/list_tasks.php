<?php
// assets/app/endpointsTareas/list_tasks.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
require_once __DIR__ . '/../../models/ProjectModel.php';

try {
    $taskModel = new TaskModel();
    $projectModel = new ProjectModel();
    $userId = $_SESSION['user']['id'];
    $defaultProject = $projectModel->getOrCreateDefaultProject((int)$userId);
    $boardId = isset($_GET['board_id']) ? (int)$_GET['board_id'] : (int)($defaultProject['board_id'] ?? 1);
    
    error_log("User ID en list_tasks: " . $userId);
    
    $tasks = $taskModel->getUserTasks($userId);
    if ($boardId > 0) {
        $tasks = array_values(array_filter($tasks, function($task) use ($boardId) {
            return (int)($task['board_id'] ?? 0) === $boardId;
        }));
    }
    
    echo json_encode([
        'ok' => true,
        'tasks' => $tasks,
        'count' => count($tasks),
        'board_id' => $boardId,
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
