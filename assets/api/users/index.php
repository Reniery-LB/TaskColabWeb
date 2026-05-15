<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

api_require_method(['GET']);

$user = api_current_user();
$isAdmin = !empty($user['is_admin']);

if ($isAdmin) {
    $sql = "
        SELECT
            u.id, u.name, u.email, u.notes, u.is_admin, u.avatar_url, u.is_active, u.created_at, u.last_login,
            COUNT(DISTINCT CASE WHEN t.is_active = 1 THEN ta.task_id END) AS assigned_count
        FROM users u
        LEFT JOIN task_assignments ta ON ta.user_id = u.id
        LEFT JOIN tasks t ON t.id = ta.task_id
        GROUP BY u.id
        ORDER BY u.is_active DESC, u.name ASC
    ";
    $stmt = api_db()->query($sql);
} else {
    $sql = "
        SELECT
            u.id, u.name, u.email, u.notes, u.is_admin, u.avatar_url, u.is_active, u.created_at, u.last_login,
            COUNT(DISTINCT CASE WHEN t.is_active = 1 THEN ta.task_id END) AS assigned_count
        FROM users u
        LEFT JOIN task_assignments ta ON ta.user_id = u.id
        LEFT JOIN tasks t ON t.id = ta.task_id
        WHERE u.is_active = 1
        GROUP BY u.id
        ORDER BY u.name ASC
    ";
    $stmt = api_db()->query($sql);
}

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = array_map(static function (array $item): array {
    $public = api_public_user($item);
    $public['assigned_count'] = (int)$item['assigned_count'];
    return $public;
}, $users);

api_json([
    'ok' => true,
    'users' => $result,
    'count' => count($result),
]);
?>
