<?php
// assets/app/endpointsTareas/update_task.php
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
    
    // Validar campos requeridos
    if (empty($input['task_id'])) {
        throw new Exception('ID de tarea requerido');
    }

    $taskModel = new TaskModel();
    $userId = $_SESSION['user']['id'];
    
    // Preparar datos para actualizar
    $updateData = [
        'task_id' => (int)$input['task_id']
    ];
    
    // Campos opcionales para actualizar
    if (isset($input['title'])) $updateData['title'] = $input['title'];
    if (isset($input['description'])) $updateData['description'] = $input['description'];
    if (isset($input['status'])) $updateData['status'] = $input['status'];
    if (isset($input['priority'])) $updateData['priority'] = $input['priority'];
    if (isset($input['due_date'])) $updateData['due_date'] = $input['due_date'];
    if (isset($input['assigned_to'])) $updateData['assigned_to'] = (int)$input['assigned_to'];
    if (isset($input['board_id'])) $updateData['board_id'] = (int)$input['board_id'];
    
    error_log("Actualizando tarea con datos: " . print_r($updateData, true));
    
    // Actualizar la tarea
    $result = $taskModel->updateTask($updateData, $userId);
    
    if ($result) {
        echo json_encode([
            'ok' => true,
            'message' => 'Tarea actualizada exitosamente',
            'task_id' => $input['task_id']
        ]);
    } else {
        throw new Exception('Error al actualizar la tarea');
    }
    
} catch (Exception $e) {
    error_log("Error en update_task: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
}
?>