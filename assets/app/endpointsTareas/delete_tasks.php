<?php
// assets/app/endpointsTareas/delete_tasks.php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

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
require_once __DIR__ . '/../../models/ActivityModel.php';

$user_id = $_SESSION['user']['id'] ?? null;
$is_admin = $_SESSION['user']['is_admin'] ?? 0;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'No autenticado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$task_ids = $input['task_ids'] ?? [];

if (empty($task_ids) || !is_array($task_ids)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'IDs de tareas inválidos']);
    exit;
}

try {
    $taskModel = new TaskModel();
    $activityModel = new ActivityModel();
    
    // VERIFICAR PERMISOS PARA CADA TAREA
    foreach ($task_ids as $task_id) {
        $task = $taskModel->getTaskById($task_id);
        
        if (!$task) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => "Tarea ID $task_id no encontrada"]);
            exit;
        }
        
        // PERMITIR SI:
        // - Es admin (puede eliminar cualquier tarea)
        // - Es el creador de la tarea
        // - Está asignado a la tarea
        $esCreador = $task['created_by'] == $user_id;
        $estaAsignado = $taskModel->isUserAssignedToTask($task_id, $user_id);
        
        if (!$is_admin && !$esCreador && !$estaAsignado) {
            http_response_code(403);
            echo json_encode([
                'ok' => false, 
                'message' => "No tienes permisos para eliminar la tarea ID $task_id"
            ]);
            exit;
        }
    }
    
    // TIENE PERMISOS PARA TODAS LAS TAREAS
    $deleted_count = 0;
    $total_activities_deleted = 0;
    
    foreach ($task_ids as $task_id) {
        // Eliminar actividades relacionadas con esta tarea
        $activities_deleted = $activityModel->deleteTaskActivities($task_id);
        if ($activities_deleted !== false) {
            $total_activities_deleted += $activities_deleted;
            error_log("Eliminadas $activities_deleted actividades de la tarea $task_id");
        }
        
        // Eliminar la tarea
        if ($taskModel->deleteTask($task_id)) {
            $deleted_count++;
            error_log("Tarea $task_id eliminada correctamente");
        }
    }
    
    echo json_encode([
        'ok' => true,
        'message' => "$deleted_count tarea(s) eliminada(s) correctamente",
        'deleted_count' => $deleted_count,
        'activities_deleted' => $total_activities_deleted
    ]);
    
} catch (Exception $e) {
    error_log("Error en delete_tasks: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}