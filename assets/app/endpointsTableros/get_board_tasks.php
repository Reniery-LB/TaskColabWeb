<?php
// assets/app/endpointsTableros/get_board_tasks.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

    if ($boardId <= 0) {
        $boardId = (int)($defaultProject['board_id'] ?? 1);
    }

    if (!$projectModel->userCanAccessBoard((int)$user_id, $boardId)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'No tienes acceso a este tablero']);
        exit;
    }
    
    // Obtener todas las tareas del usuario
    $allTasks = $taskModel->getUserTasks($user_id);
    $allTasks = array_values(array_filter($allTasks, function($task) use ($boardId) {
        return (int)($task['board_id'] ?? 0) === $boardId;
    }));
    
    // Agrupar por columna (status)
    $board = [
        'pending' => [],
        'in_progress' => [],
        'done' => []
    ];
    
    foreach ($allTasks as $task) {
        // Convertir status a clave de columna
        $status = strtolower($task['status']);
        
        // Mapear estados a columnas del tablero
        if ($status === 'pendiente' || $status === 'pending') {
            $board['pending'][] = $task;
        } elseif ($status === 'en proceso' || $status === 'in_progress') {
            $board['in_progress'][] = $task;
        } elseif ($status === 'completada' || $status === 'completado' || $status === 'done') {
            $board['done'][] = $task;
        }
    }
    
    // Ordenar por position dentro de cada columna
    foreach ($board as $column => &$tasks) {
        usort($tasks, function($a, $b) {
            return ($a['position'] ?? 0) - ($b['position'] ?? 0);
        });
    }
    
    echo json_encode([
        'ok' => true,
        'board' => $board,
        'total_tasks' => count($allTasks),
        'board_id' => $boardId,
        'debug_user_id' => $user_id
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_board_tasks: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error al cargar el tablero',
        'error' => $e->getMessage()
    ]);
}
?>
