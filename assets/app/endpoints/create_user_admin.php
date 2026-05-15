<?php
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

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$notes = trim($input['notes'] ?? '');
$task_ids = $input['task_ids'] ?? []; 

// Validaciones
if (empty($name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Nombre y correo son obligatorios']);
    exit;
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Formato de correo inválido']);
    exit;
}

try {
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'El correo ya está registrado']);
        exit;
    }
    
    // Generar contraseña temporal
    $temporaryPassword = bin2hex(random_bytes(4)); // Genera contraseña de 8 caracteres
    $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
    
    // Insertar usuario
    $sql = "INSERT INTO users (name, email, password_hash, notes, is_active, is_admin) 
            VALUES (?, ?, ?, ?, 1, 0)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $email, $passwordHash, $notes]);
    
    $newUserId = $pdo->lastInsertId();
    
    // Asignar tareas si se proporcionaron
    if (!empty($task_ids) && is_array($task_ids)) {
        $sqlTask = "INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)";
        $stmtTask = $pdo->prepare($sqlTask);
        
        foreach ($task_ids as $task_id) {
            try {
                $stmtTask->execute([$task_id, $newUserId]);
            } catch (PDOException $e) {
                // Ignorar duplicados
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
    }
    
    // Obtener el usuario recién creado con el conteo de tareas
    $stmt = $pdo->prepare("
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
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$newUserId]);
    $newUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'message' => 'Usuario creado exitosamente',
        'user' => $newUser,
        'temporary_password' => $temporaryPassword // Enviar la contraseña temporal
    ]);
    
} catch (PDOException $e) {
    error_log("Error en create_user_admin: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error al crear usuario']);
}
?>