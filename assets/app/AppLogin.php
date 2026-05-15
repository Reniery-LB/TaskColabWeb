<?php
// assets/app/AppLogin.php
session_start();
require_once __DIR__ . '/../controllers/AuthController.php';

$controller = new AuthController();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../view/login.html');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$result = $controller->login($email, $password);

if ($result['ok']) {
    $user = $result['user'];

    // GUARDAR TODOS LOS DATOS DEL USUARIO EN LA SESIÓN
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'], 
        'is_admin' => !empty($user['is_admin']) ? 1 : 0,
        'avatar_path' => $user['avatar_path'] ?? null, 
        'is_active' => $user['is_active'] ?? 1 
    ];

    // Actualizar last_login
    require_once __DIR__ . '/../../config/db.php';
    if (isset($pdo)) {
        $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $stmt->execute([$user['id']]);
    }

    // Log para debugging 
    error_log("Usuario logueado: " . json_encode($_SESSION['user']));

    header('Location: ../../view/pantalla_principal/gestor_task.php');
    exit;
} else {
    $msg = urlencode($result['message'] ?? 'Credenciales inválidas.');
    header("Location: ../../view/login.html?error={$msg}");
    exit;
}
?>