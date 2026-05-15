<?php
// assets/app/endpoints/list_users.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config/db.php';

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $sql = "
        SELECT 
            u.id,
            u.name,
            u.email,
            COALESCE(u.notes, '') AS notes,
            u.is_active,
            u.last_login,
            COUNT(DISTINCT CASE 
                WHEN t.is_active = 1 THEN ta.task_id 
                ELSE NULL 
            END) AS assigned_count
        FROM users u
        LEFT JOIN task_assignments ta ON u.id = ta.user_id
        LEFT JOIN tasks t ON ta.task_id = t.id
        GROUP BY u.id, u.name, u.email, u.notes, u.is_active, u.last_login
        ORDER BY u.is_active DESC, u.name ASC
    ";
    
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'users' => $users,
        'count' => count($users)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Error en BD: ' . $e->getMessage()
    ]);
}
?>