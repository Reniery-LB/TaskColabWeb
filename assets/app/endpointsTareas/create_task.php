<?php
// assets/app/endpointsTareas/create_task.php
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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar campo REQUERIDO (solo título)
    if (empty($input['title'])) {
        throw new Exception("El título es obligatorio");
    }
    
    // Validar assigned_to (puede ser null o vacío)
    $assigned_to = null;
    if (isset($input['assigned_to']) && !empty($input['assigned_to'])) {
        $assigned_to = (int)$input['assigned_to'];
        if ($assigned_to <= 0) {
            $assigned_to = null;
        }
    }
    
    // Validar due_date (puede ser null o vacío)
    $due_date = null;
    if (isset($input['due_date']) && !empty($input['due_date'])) {
        $due_date = $input['due_date'];
        // Verificar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
            $due_date = null;
        }
    }
    
    $taskModel = new TaskModel();
    $userId = $_SESSION['user']['id'];
    
    error_log("Datos recibidos en create_task: " . print_r($input, true));
    
    $taskData = [
        'title' => trim($input['title']),
        'description' => isset($input['description']) ? trim($input['description']) : '',
        'assigned_to' => $assigned_to, // Puede ser null
        'status' => isset($input['status']) ? $input['status'] : 'Pendiente',
        'priority' => isset($input['priority']) ? $input['priority'] : 'Media',
        'due_date' => $due_date, // Puede ser null
        'board_id' => isset($input['board_id']) ? (int)$input['board_id'] : 1,
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