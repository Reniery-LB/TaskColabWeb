<?php
// HEADERS CORS
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

require_once __DIR__ . '/../../../config/db.php';

// Verificar autenticación
$user_id = $_SESSION['user']['id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'No autenticado']);
    exit;
}

// Verificar si el usuario es administrador
$isAdmin = $_SESSION['user']['is_admin'] ?? 0;
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'No tienes permisos de administrador']);
    exit;
}

try {
    // Obtener todos los usuarios con su conteo de tareas
    $sql = "
        SELECT 
            u.id,
            u.name,
            u.email,
            u.notes,
            u.is_active,
            u.is_admin,
            u.last_login,
            COUNT(ta.task_id) as assigned_count
        FROM users u
        LEFT JOIN task_assignments ta ON u.id = ta.user_id
        LEFT JOIN tasks t ON ta.task_id = t.id AND t.is_active = 1
        GROUP BY u.id, u.name, u.email, u.notes, u.is_active, u.is_admin, u.last_login
        ORDER BY u.id DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'users' => $users,
        'count' => count($users)
    ]);
    
} catch (PDOException $e) {
    error_log("Error en list_users_admin: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error en el servidor']);
}
?>