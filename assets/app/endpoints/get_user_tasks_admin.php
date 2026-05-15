<?php
// HEADERS CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

require_once __DIR__ . '/../../../config/db.php';

// Verificar autenticación
$current_user_id = $_SESSION['user']['id'] ?? null;
$isAdmin = $_SESSION['user']['is_admin'] ?? 0;

if (!$current_user_id || !$isAdmin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'No tienes permisos de administrador']);
    exit;
}

$user_id = intval($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'ID de usuario inválido']);
    exit;
}

try {
    // Obtener tareas asignadas al usuario
    $sql = "
        SELECT 
            t.id,
            t.title,
            t.description,
            t.status,
            t.priority
        FROM tasks t
        INNER JOIN task_assignments ta ON t.id = ta.task_id
        WHERE ta.user_id = ? AND t.is_active = 1
        ORDER BY t.title ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'tasks' => $tasks,
        'count' => count($tasks)
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get_user_tasks_admin: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error en el servidor']);
}
?>