<?php
// assets/app/endpointsPerfil/get_user_activity.php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Headers anti-cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) session_start();

// RUTAS ABSOLUTAS
$basePath = $_SERVER['DOCUMENT_ROOT'] . '/PROYECTO_GESTOR_TAREAS';
$dbPath = $basePath . '/config/db.php';
$taskModelPath = $basePath . '/assets/models/TaskModel.php';
$activityModelPath = $basePath . '/assets/models/ActivityModel.php';

try {
    // Cargar archivos
    require_once $dbPath;
    require_once $taskModelPath;
    require_once $activityModelPath;

    $user_id = $_SESSION['user']['id'] ?? null;
    if (!$user_id) {
        throw new Exception('No autenticado');
    }

    $taskModel = new TaskModel();
    $activityModel = new ActivityModel();
    
    // Usar SOLO getUserActivity (tareas asignadas)
    // NO usar getUserTasks porque devuelve TODAS las tareas para admins
    $tareasAsignadas = $taskModel->getUserActivity($user_id, 10);
    
    // Obtener también actividad de movimientos
    $actividadReciente = $activityModel->getUserActivity($user_id, 10);
    
    // Combinar ambas fuentes de datos
    $actividadCompleta = [];
    
    // Agregar tareas asignadas
    foreach ($tareasAsignadas as $tarea) {
        $actividadCompleta[] = [
            'tipo' => 'tarea_activa',
            'tarea' => $tarea['description'] ?? ($tarea['title'] ?? 'Sin descripción'),
            'estado' => $tarea['status'] ?? '-',
            'fecha_limite' => $tarea['due_date'] ?? null,
            'prioridad' => $tarea['priority'] ?? '-',
            'tablero' => $tarea['board_title'] ?? 'General',
            'fecha_actividad' => $tarea['updated_at'] ?? $tarea['created_at'] ?? null
        ];
    }
    
    // Agregar actividad reciente (movimientos)
    foreach ($actividadReciente as $actividad) {
        $detalles = json_decode($actividad['details'] ?? '{}', true);
        
        $actividadCompleta[] = [
            'tipo' => 'actividad',
            'tarea' => $detalles['task_title'] ?? $actividad['task_title'] ?? 'Tarea',
            'estado' => $actividad['action'] ?? 'Acción realizada',
            'fecha_limite' => $actividad['task_due_date'] ?? null,
            'prioridad' => $actividad['task_priority'] ?? '-',
            'tablero' => $actividad['board_title'] ?? 'General',
            'fecha_actividad' => $actividad['created_at'] ?? null
        ];
    }
    
    // Ordenar por fecha más reciente
    usort($actividadCompleta, function($a, $b) {
        $timeA = strtotime($a['fecha_actividad'] ?? '');
        $timeB = strtotime($b['fecha_actividad'] ?? '');
        return $timeB - $timeA;
    });
    
    // Limitar a 10 elementos
    $actividadCompleta = array_slice($actividadCompleta, 0, 10);
    
    error_log("get_user_activity " . count($actividadCompleta) . " elementos para usuario $user_id (SOLO ASIGNADAS)");
    
    echo json_encode([
        'ok' => true,
        'tareas' => $actividadCompleta,
        'count' => count($actividadCompleta),
        'debug_user_id' => $user_id,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_user_activity: " . $e->getMessage());
    
    echo json_encode([
        'ok' => false,
        'tareas' => [],
        'count' => 0,
        'message' => 'Error al cargar actividad',
        'error' => $e->getMessage()
    ]);
}
