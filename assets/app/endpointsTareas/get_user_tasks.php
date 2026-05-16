<?php
// assets/app/endpointsTareas/get_user_tasks.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../models/TaskModel.php';
require_once __DIR__ . '/../../models/ProjectModel.php';

$user_id = $_SESSION['user']['id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $taskModel = new TaskModel();
    $projectModel = new ProjectModel();
    $defaultProject = $projectModel->getOrCreateDefaultProject((int)$user_id);
    $boardId = isset($_GET['board_id']) ? (int)$_GET['board_id'] : (int)($defaultProject['board_id'] ?? 1);
    $tasks = $taskModel->getUserTasks($user_id);
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
        'debug_user_id' => $user_id
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_user_tasks: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error al obtener tareas',
        'error' => $e->getMessage()
    ]);
}
?>
