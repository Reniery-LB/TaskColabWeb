<?php
// assets/app/endpointsPerfil/delete_account.php
// HEADERS CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['ok' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../../config/db.php';

$userId = $_SESSION['user']['id'];
$response = ['ok' => false, 'message' => 'Error desconocido'];

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    $password = $input['password'] ?? '';

    if (empty($password)) {
        throw new Exception('La contraseña es requerida');
    }

    // CORRECCIÓN
    $sql = "SELECT id, password_hash FROM users WHERE id = ? AND is_active = 1";
    
    // Verificar contraseña
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Usuario no encontrado o cuenta inactiva');
    }

    // Verificar contraseña
    if (!password_verify($password, $user['password_hash'])) {
        throw new Exception('Contraseña incorrecta');
    }

    // ELIMINACIÓN SEGURA - Marcar como inactivo en lugar de borrar
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0, notes = CONCAT(IFNULL(notes, ''), ' - Cuenta eliminada el ', NOW()) WHERE id = ?");
    $result = $stmt->execute([$userId]);

    if ($result && $stmt->rowCount() > 0) {
        session_destroy();
        
        $response = [
            'ok' => true,
            'message' => 'Cuenta eliminada correctamente'
        ];
        
        error_log("Cuenta marcada como inactiva para usuario: $userId");
    } else {
        throw new Exception('Error al eliminar la cuenta');
    }

} catch (Exception $e) {
    error_log("Error en delete_account.php: " . $e->getMessage());
    $response = ['ok' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit;
?>