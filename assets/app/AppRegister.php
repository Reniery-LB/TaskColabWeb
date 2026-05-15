<?php
// assets/app/AppRegister.php
session_start();
require_once __DIR__ . '/../controllers/AuthController.php';

$controller = new AuthController();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../view/registro.html');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] == '1' ? 1 : 0;

$result = $controller->register($name, $email, $password, $confirm, $is_admin);

if ($result['ok']) {
    header('Location: ../../view/login.html?success=1');
    exit;
} else {
    $errors = $result['errors'] ?? ['Error desconocido.'];
    $enc = urlencode(implode('||', array_map('trim', $errors)));
    header("Location: ../../view/registro.html?errors={$enc}");
    exit;
}
