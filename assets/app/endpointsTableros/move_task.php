<?php
// assets/app/endpointsTableros/move_task.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Headers anti-cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// TEMPORAL: Mostrar errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $user_id = $_SESSION['user']['id'] ?? null;
    $is_admin = $_SESSION['user']['is_admin'] ?? 0;

    if (!$user_id) {
        throw new Exception('No autenticado');
    }

    // Leer datos del body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($input)) {
        throw new Exception('Payload inválido');
    }

    $task_id = intval($input['task_id'] ?? 0);
    $direction = $input['direction'] ?? '';

    if ($task_id <= 0) {
        throw new Exception('ID de tarea inválido');
    }

    if (!in_array($direction, ['left', 'right'])) {
        throw new Exception('Dirección inválida');
    }

    // Cargar modelos con manejo de errores
    $taskModelPath = __DIR__ . '/../../models/TaskModel.php';
    $activityModelPath = __DIR__ . '/../../models/ActivityModel.php';
    
    if (!file_exists($taskModelPath)) {
        throw new Exception("TaskModel.php no encontrado");
    }
    if (!file_exists($activityModelPath)) {
        throw new Exception("ActivityModel.php no encontrado");
    }
    
    require_once $taskModelPath;
    require_once $activityModelPath;

    $taskModel = new TaskModel();
    
    // Intentar sin ActivityModel para aislar el problema
    $useActivityLogging = false;
    $activityModel = null;
    
    try {
        $activityModel = new ActivityModel();
        $useActivityLogging = true;
    } catch (Exception $e) {
        error_log("ActivityModel no disponible: " . $e->getMessage());
        $useActivityLogging = false;
    }
    
    // Verificar que la tarea existe
    $task = $taskModel->getTaskById($task_id);
    
    if (!$task) {
        throw new Exception('Tarea no encontrada');
    }

    // Verificar permisos
    $esCreador = $task['created_by'] == $user_id;
    $estaAsignado = $taskModel->isUserAssignedToTask($task_id, $user_id);
    
    if (!$is_admin && !$esCreador && !$estaAsignado) {
        throw new Exception('No tienes permisos para mover esta tarea');
    }
    
    // Obtener status actual
    $currentStatus = $task['status'];
    $taskTitle = $task['title'];
    
    // Mapear status español a columna interna
    $statusToColumn = [
        'Pendiente' => 'pending',
        'En proceso' => 'in_progress', 
        'Completado' => 'done',
        'Completada' => 'done',
        'pending' => 'pending',
        'in_progress' => 'in_progress',
        'done' => 'done'
    ];
    
    $currentColumn = $statusToColumn[$currentStatus] ?? 'pending';
    
    // Calcular nueva columna según dirección
    $columnOrder = ['pending', 'in_progress', 'done'];
    $currentIndex = array_search($currentColumn, $columnOrder);
    
    if ($direction === 'left') {
        $newIndex = max(0, $currentIndex - 1);
    } else {
        $newIndex = min(count($columnOrder) - 1, $currentIndex + 1);
    }
    
    $newColumn = $columnOrder[$newIndex];
    
    // Mapear columna a status en español para la BD
    $columnToStatus = [
        'pending' => 'Pendiente',
        'in_progress' => 'En proceso', 
        'done' => 'Completado'
    ];
    
    $newStatus = $columnToStatus[$newColumn];
    
    // Si la columna no cambió
    if ($newColumn === $currentColumn) {
        echo json_encode([
            'ok' => true,
            'message' => 'La tarea ya está en esa columna',
            'task_id' => $task_id,
            'new_column' => $newColumn,
            'new_status' => $newStatus,
            'old_column' => $currentColumn,
            'old_status' => $currentStatus
        ]);
        exit;
    }
    
    // Intentar solo el movimiento básico (sin actividad)
    $updateData = [
        'task_id' => $task_id,
        'status' => $newStatus
    ];
    
    $success = $taskModel->updateTask($updateData, null);
    
    if ($success) {
        // Intentar registrar actividad si está disponible
        if ($useActivityLogging && $activityModel) {
            try {
                $activityModel->logTaskMovement(
                    $user_id,
                    $task_id,
                    $taskTitle,
                    $currentStatus, 
                    $newStatus,
                    $direction
                );
            } catch (Exception $e) {
                error_log("No se pudo registrar actividad: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'ok' => true,
            'message' => 'Tarea movida exitosamente',
            'task_id' => $task_id,
            'new_column' => $newColumn,
            'new_status' => $newStatus,
            'old_column' => $currentColumn,
            'old_status' => $currentStatus,
            'direction' => $direction,
            'activity_logged' => $useActivityLogging
        ]);
    } else {
        throw new Exception('No se pudo actualizar la tarea en la base de datos');
    }
    
} catch (Exception $e) {
    error_log("ERROR en move_task: " . $e->getMessage());
    http_response_code(500);
    
    echo json_encode([
        'ok' => false,
        'message' => 'Error al mover la tarea: ' . $e->getMessage()
    ]);
}
?>