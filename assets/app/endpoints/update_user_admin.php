<?php
// assets/app/endpoints/update_user_admin.php
// HEADERS CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$notes = trim($input['notes'] ?? '');
$task_ids = $input['task_ids'] ?? [];

// Validaciones
if ($id <= 0 || empty($name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    // Verificar que el usuario exista
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    // Verificar si el email ya existe en otro usuario
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'El correo ya está en uso por otro usuario']);
        exit;
    }
    
    // Actualizar usuario
    $sql = "UPDATE users SET name = ?, email = ?, notes = ?, last_login = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $email, $notes, $id]);
    
    // ACTUALIZAR SESIÓN SI ES EL USUARIO ACTUAL
    $esUsuarioActual = ($id == $user_id);
    
    if ($esUsuarioActual) {
        error_log("Actualizando sesión del usuario actual (ID: $id)");
        
        // Actualizar datos en la sesión
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        
        // También actualizar avatar_url si ya existe
        if (isset($_SESSION['user']['avatar_url'])) {
            // Mantener el avatar_url actual
            $avatar_url = $_SESSION['user']['avatar_url'];
        } else {
            // Obtener avatar_url de la base de datos
            $stmtAvatar = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $stmtAvatar->execute([$id]);
            $avatarData = $stmtAvatar->fetch(PDO::FETCH_ASSOC);
            $avatar_url = $avatarData['avatar_url'] ?? null;
            $_SESSION['user']['avatar_url'] = $avatar_url;
        }
        
        error_log("Sesión actualizada: name=$name, email=$email, avatar_url=" . ($avatar_url ?? 'null'));
    }
    
    // Actualizar asignaciones de tareas
    // 1. Eliminar todas las asignaciones actuales
    $pdo->prepare("DELETE FROM task_assignments WHERE user_id = ?")->execute([$id]);
    
    // 2. Insertar las nuevas asignaciones
    if (!empty($task_ids) && is_array($task_ids)) {
        $sqlTask = "INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)";
        $stmtTask = $pdo->prepare($sqlTask);
        
        foreach ($task_ids as $task_id) {
            try {
                $stmtTask->execute([$task_id, $id]);
            } catch (PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
    }
    
    // Obtener el usuario actualizado CON TODOS LOS DATOS INCLUYENDO AVATAR
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.avatar_url,
            u.notes,
            u.is_active,
            u.is_admin,
            u.last_login,
            COUNT(ta.task_id) as assigned_count
        FROM users u
        LEFT JOIN task_assignments ta ON u.id = ta.user_id
        LEFT JOIN tasks t ON ta.task_id = t.id AND t.is_active = 1
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$id]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($esUsuarioActual) {
        // Asegurar que la sesión tenga avatar_url
        if (!isset($_SESSION['user']['avatar_url']) && isset($updatedUser['avatar_url'])) {
            $_SESSION['user']['avatar_url'] = $updatedUser['avatar_url'];
        }
        
        // Incluir avatar_url en la respuesta
        $updatedUser['avatar_url'] = $_SESSION['user']['avatar_url'] ?? $updatedUser['avatar_url'] ?? null;
    }
    
    // Respuesta mejorada con más datos
    $response = [
        'ok' => true,
        'message' => 'Usuario actualizado exitosamente',
        'user' => $updatedUser,
        'is_current_user' => $esUsuarioActual
    ];
    
    if ($esUsuarioActual) {
        $response['session_updated'] = true;
        $response['session_data'] = [
            'name' => $_SESSION['user']['name'],
            'email' => $_SESSION['user']['email'],
            'avatar_url' => $_SESSION['user']['avatar_url'] ?? null
        ];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Error en update_user_admin: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error al actualizar usuario: ' . $e->getMessage()]);
}
?>