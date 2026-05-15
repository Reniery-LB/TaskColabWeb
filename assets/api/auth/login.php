<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

api_require_method(['POST']);

$input = api_input();
$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');
$deviceName = isset($input['device_name']) ? trim((string)$input['device_name']) : null;

if ($email === '' || $password === '') {
    api_json(['ok' => false, 'message' => 'Correo y contraseña son obligatorios'], 422);
}

$controller = new AuthController();
$result = $controller->login($email, $password);

if (!$result['ok']) {
    api_json(['ok' => false, 'message' => $result['message'] ?? 'Credenciales inválidas'], 401);
}

$user = $result['user'];
$pdo = api_db();
$stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
$stmt->execute([(int)$user['id']]);

$session = api_create_session((int)$user['id'], $deviceName);

api_json([
    'ok' => true,
    'message' => 'Sesión iniciada',
    'token_type' => 'Bearer',
    'access_token' => $session['token'],
    'expires_at' => $session['expires_at'],
    'user' => api_public_user($user),
]);
?>
