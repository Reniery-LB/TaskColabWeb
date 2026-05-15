<?php
// assets/app/endpoints/update_user.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config/db.php';

// Leer JSON del body
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Payload inválido']);
    exit;
}

$id = intval($input['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'ID inválido']);
    exit;
}

// Verificar autenticación
$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'No autenticado']);
    exit;
}

// RESTRICCIÓN: SOLO PERMITIR EDITAR SU PROPIO PERFIL
if (intval($currentUser['id']) !== $id) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Solo puedes editar tu propio perfil']);
    exit;
}

// Campos permitidos
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$notes = isset($input['notes']) ? trim($input['notes']) : null; 
$is_active = isset($input['is_active']) ? (int)$input['is_active'] : null;

try {
    $fields = [];
    $params = [];

    if ($name !== '') { 
        $fields[] = 'name = ?'; 
        $params[] = $name; 
    }
    
    if ($email !== '') { 
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmtCheck->execute([$email, $id]);
        if ($stmtCheck->fetch()) {
            echo json_encode(['ok' => false, 'message' => 'El correo ya está en uso']);
            exit;
        }
        $fields[] = 'email = ?'; 
        $params[] = $email; 
    }

    if ($notes !== null) { 
        $fields[] = 'notes = ?'; 
        $params[] = $notes; 
    }
    
    if ($is_active !== null) { 
        $fields[] = 'is_active = ?'; 
        $params[] = $is_active; 
    }

    // ACTUALIZAR FECHA SIEMPRE AL EDITAR
    $fields[] = 'last_login = NOW()';

    if (empty($fields)) {
        echo json_encode(['ok' => false, 'message' => 'No hay campos para actualizar']);
        exit;
    }

    $params[] = $id;
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Actualizar sesión
    if (intval($currentUser['id']) === $id) {
        $_SESSION['user']['name'] = $name !== '' ? $name : $currentUser['name'];
        $_SESSION['user']['email'] = $email !== '' ? $email : $currentUser['email'];
        if ($is_active !== null) {
            $_SESSION['user']['is_active'] = $is_active;
        }
    }

    // OBTENER USUARIO ACTUALIZADO
    $stmt2 = $pdo->prepare("
        SELECT 
            u.id, 
            u.name, 
            u.email, 
            u.notes, 
            u.is_active, 
            u.last_login,
            u.is_admin,
            COUNT(DISTINCT CASE 
                WHEN t.is_active = 1 THEN ta.task_id 
                ELSE NULL 
            END) as assigned_count
        FROM users u
        LEFT JOIN task_assignments ta ON u.id = ta.user_id
        LEFT JOIN tasks t ON ta.task_id = t.id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt2->execute([$id]);
    $user = $stmt2->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true, 
        'user' => $user, 
        'message' => 'Usuario actualizado correctamente'
    ]);

} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error en BD: ' . $ex->getMessage()]);
}
?>