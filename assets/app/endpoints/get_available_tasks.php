<?php
// assets/app/endpoints/get_available_tasks.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';

// Verificar autenticación
$user_id = $_SESSION['user']['id'] ?? null;
$is_admin = $_SESSION['user']['is_admin'] ?? 0;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    // Obtener todas las tareas activas
    $sql = "
        SELECT 
            id,
            title,
            description,
            status,
            priority,
            due_date
        FROM tasks
        WHERE is_active = 1
        ORDER BY title ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'tasks' => $tasks,
        'count' => count($tasks)
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get_available_tasks: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>