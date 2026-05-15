<?php
// assets/app/endpoints/refresh_session.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

try {
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        throw new Exception('No hay sesión activa');
    }
    
    $userId = $_SESSION['user']['id'];
    
    // Obtener datos actualizados del usuario
    $stmt = $pdo->prepare("SELECT id, name, email, avatar_url, is_admin, is_active FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        throw new Exception('Usuario no encontrado o inactivo');
    }
    
    // Actualizar sesión
    $_SESSION['user'] = array_merge($_SESSION['user'], $userData);
    
    echo json_encode([
        'ok' => true,
        'message' => 'Sesión actualizada',
        'user' => $_SESSION['user']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
}
?>