<?php
// assets/app/endpointsReportes/get_dashboard_stats.php
// DESACTIVAR MOSTRAR ERRORES EN PANTALLA
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// HEADERS CORS - antes de cualquier output
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión
$user_id = $_SESSION['user']['id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

try {
    // CARGAR DB.PHP y TaskModel
    require_once __DIR__ . '/../../../config/db.php';
    require_once __DIR__ . '/../../models/TaskModel.php';
    
    $taskModel = new TaskModel();

    // Obtener estadísticas generales
    $totalTasks = $taskModel->getUserTasksCount($user_id);
    $pendingTasks = $taskModel->getUserTasksCountByStatus($user_id, 'pendiente');
    $inProgressTasks = $taskModel->getUserTasksCountByStatus($user_id, 'en_proceso');
    $completedTasks = $taskModel->getUserTasksCountByStatus($user_id, 'completado');
    
    // Obtener progreso por tablero
    $boardProgress = $taskModel->getBoardProgress($user_id);
    
    // Obtener usuarios activos
    $activeUsers = $taskModel->getActiveUsers($user_id);
    
    // Obtener tareas atrasadas - usar solo un método
    $overdueTasks = $taskModel->getOverdueTasks($user_id);
    
    // Preparar respuesta
    echo json_encode([
        'success' => true,
        'data' => [
            'general_stats' => [
                'total_tareas' => $totalTasks,
                'pendiente' => $pendingTasks,
                'en_proceso' => $inProgressTasks,
                'completado' => $completedTasks
            ],
            'board_progress' => $boardProgress,
            'active_users' => $activeUsers,
            'overdue_tasks' => $overdueTasks
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error del servidor: ' . $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
?>