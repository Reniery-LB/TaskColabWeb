<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../models/UserModel.php';

api_require_method(['POST']);

$input = api_input();
$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');
$confirm = (string)($input['confirm_password'] ?? $input['password_confirmation'] ?? '');
$isAdmin = !empty($input['is_admin']) ? 1 : 0;
$deviceName = isset($input['device_name']) ? trim((string)$input['device_name']) : null;

$controller = new AuthController();
$result = $controller->register($name, $email, $password, $confirm, $isAdmin);

if (!$result['ok']) {
    api_json([
        'ok' => false,
        'message' => 'No se pudo crear la cuenta',
        'errors' => $result['errors'] ?? ['Error desconocido'],
    ], 422);
}

$userModel = new UserModel();
$user = $userModel->findById((int)$result['user_id']);

if (!$user) {
    api_json(['ok' => false, 'message' => 'Cuenta creada, pero no se pudo iniciar sesión'], 500);
}

unset($user['password_hash']);
$session = api_create_session((int)$user['id'], $deviceName);

api_json([
    'ok' => true,
    'message' => 'Cuenta creada',
    'token_type' => 'Bearer',
    'access_token' => $session['token'],
    'expires_at' => $session['expires_at'],
    'user' => api_public_user($user),
], 201);
?>
