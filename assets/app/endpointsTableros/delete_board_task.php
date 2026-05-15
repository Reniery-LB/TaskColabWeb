<?php
// assets/app/endpointsTableros/delete_board_task.php 
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
require_once __DIR__ . '/../../models/ActivityModel.php';

$user_id = $_SESSION['user']['id'] ?? null;
$is_admin = $_SESSION['user']['is_admin'] ?? 0;

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

$task_id = intval($input['task_id'] ?? 0);

if ($task_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'ID de tarea inválido']);
    exit;
}

try {
    $taskModel = new TaskModel();
    $activityModel = new ActivityModel();
    
    // Verificar que la tarea existe
    $task = $taskModel->getTaskById($task_id);
    
    if (!$task) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Tarea no encontrada']);
        exit;
    }
    
    // Verificar permisos (mismo que delete_tasks.php)
    $esCreador = $task['created_by'] == $user_id;
    $estaAsignado = $taskModel->isUserAssignedToTask($task_id, $user_id);
    
    if (!$is_admin && !$esCreador && !$estaAsignado) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'No tienes permisos para eliminar esta tarea']);
        exit;
    }
    
    // Eliminar actividades relacionadas
    $activities_deleted = $activityModel->deleteTaskActivities($task_id);
    error_log("Eliminando actividades de la tarea {$task_id}");
    
    // Eliminar tarea (soft delete)
    $success = $taskModel->deleteTask($task_id);
    
    if ($success) {
        error_log("Tarea {$task_id} eliminada. Actividades eliminadas: " . ($activities_deleted !== false ? $activities_deleted : '0'));
        
        echo json_encode([
            'ok' => true,
            'message' => 'Tarea eliminada exitosamente',
            'task_id' => $task_id,
            'activities_deleted' => $activities_deleted
        ]);
    } else {
        throw new Exception('No se pudo eliminar la tarea');
    }
    
} catch (Exception $e) {
    error_log("Error en delete_board_task: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error al eliminar la tarea',
        'error' => $e->getMessage()
    ]);
}