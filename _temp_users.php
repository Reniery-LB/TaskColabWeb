<?php
// Temporary helper to get a user email for login
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

$stmt = $pdo->query('SELECT id, email, name, is_admin FROM users WHERE is_active = 1 LIMIT 5');
$users = $stmt->fetchAll();

echo json_encode($users, JSON_PRETTY_PRINT);
?>
