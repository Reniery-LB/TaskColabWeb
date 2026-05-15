<?php
// assets/app/endpoints/get_user.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../../../controllers/UserController.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['ok' => false, 'message' => 'Missing id']);
    exit;
}

$ctrl = new UserController();
$user = $ctrl->get($id);
if ($user) {
    echo json_encode(['ok' => true, 'user' => $user]);
} else {
    echo json_encode(['ok' => false, 'message' => 'Usuario no encontrado']);
}
