<?php
// assets/app/endpointsTareas/create_task.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
require_once __DIR__ . '/../../models/ProjectModel.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar campo REQUERIDO (solo título)
    if (empty($input['title'])) {
        throw new Exception("El título es obligatorio");
    }
    
    $userId = (int)$_SESSION['user']['id'];
    $isAdmin = !empty($_SESSION['user']['is_admin']);

    // Validar assigned_to. Admin puede asignar a varios; usuario normal solo a sí mismo.
    $assigned_to = [];
    if ($isAdmin) {
        $rawAssigned = $input['assigned_to'] ?? [];
        if (!is_array($rawAssigned)) {
            $rawAssigned = $rawAssigned ? [$rawAssigned] : [];
        }
        foreach ($rawAssigned as $assignedId) {
            $assignedId = (int)$assignedId;
            if ($assignedId > 0) {
                $assigned_to[] = $assignedId;
            }
        }
        $assigned_to = array_values(array_unique($assigned_to));
    } else {
        $assigned_to = [$userId];
    }
    
    // Validar due_date (puede ser null o vacío)
    $due_date = null;
    if (isset($input['due_date']) && !empty($input['due_date'])) {
        $due_date = $input['due_date'];
        // Verificar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
            $due_date = null;
        } elseif ($due_date < date('Y-m-d')) {
            throw new Exception('La fecha límite no puede ser anterior al día de hoy');
        }
    }
    
    $taskModel = new TaskModel();
    $projectModel = new ProjectModel();
    $defaultProject = $projectModel->getOrCreateDefaultProject((int)$userId);
    $boardId = isset($input['board_id']) ? (int)$input['board_id'] : (int)($defaultProject['board_id'] ?? 1);

    if ($boardId <= 0) {
        $boardId = (int)($defaultProject['board_id'] ?? 1);
    }

    if (!$projectModel->userCanAccessBoard((int)$userId, $boardId)) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'message' => 'No tienes acceso a este tablero'
        ]);
        exit;
    }
    
    error_log("Datos recibidos en create_task: " . print_r($input, true));
    
    $taskData = [
        'title' => trim($input['title']),
        'description' => isset($input['description']) ? trim($input['description']) : '',
        'assigned_to' => $assigned_to,
        'status' => isset($input['status']) ? $input['status'] : 'Pendiente',
        'priority' => isset($input['priority']) ? $input['priority'] : 'Media',
        'due_date' => $due_date, // Puede ser null
        'board_id' => $boardId,
        'created_by' => $userId
    ];
    
    error_log("Datos a guardar: " . print_r($taskData, true));
    
    // Crear la tarea
    $result = $taskModel->createTask($taskData);
    
    if ($result) {
        echo json_encode([
            'ok' => true,
            'message' => 'Tarea creada exitosamente',
            'task_id' => $result
        ]);
    } else {
        throw new Exception('Error al crear la tarea en la base de datos');
    }
    
} catch (Exception $e) {
    error_log("Error en create_task: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
}
?>
