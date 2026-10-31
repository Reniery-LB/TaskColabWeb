<?php
// Temporary auto-login script - creates a session for user id 1 (admin)
// and redirects to the dashboard
require_once __DIR__ . '/config/db.php';

session_start();

$stmt = $pdo->prepare('SELECT id, name, email, is_admin, avatar_path, is_active FROM users WHERE id = 1');
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'is_admin' => !empty($user['is_admin']) ? 1 : 0,
        'avatar_path' => $user['avatar_path'] ?? null,
        'is_active' => $user['is_active'] ?? 1
    ];
    
    header('Location: view/pantalla_principal/gestor_task.php');
    exit;
} else {
    echo "User not found";
}
?>
