<?php
// HEADERS CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
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

// Verificar autenticación y admin
$user_id = $_SESSION['user']['id'] ?? null;
$isAdmin = $_SESSION['user']['is_admin'] ?? 0;

if (!$user_id || !$isAdmin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'No tienes permisos de administrador']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

// Validación
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'ID de usuario inválido']);
    exit;
}

// No permitir que el admin se elimine a sí mismo
if ($id == $user_id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'No puedes eliminar tu propia cuenta']);
    exit;
}

try {
    // Verificar que el usuario exista
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userToDelete) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    // Eliminar usuario 
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'ok' => true,
        'message' => 'Usuario eliminado exitosamente',
        'deleted_user' => $userToDelete
    ]);
    
} catch (PDOException $e) {
    error_log("Error en delete_user_admin: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error al eliminar usuario']);
}
?>