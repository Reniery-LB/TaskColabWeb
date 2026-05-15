<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

api_require_method(['GET']);

$user = api_current_user();
$userId = (int)$user['id'];
$isAdmin = !empty($user['is_admin']) ? 1 : 0;

$sql = "
    SELECT
        b.id,
        b.owner_id,
        owner.name AS owner_name,
        b.title,
        b.description,
        b.visibility,
        b.color,
        b.created_at,
        b.updated_at,
        COUNT(DISTINCT CASE WHEN t.is_active = 1 THEN t.id END) AS total_tasks,
        COUNT(DISTINCT CASE WHEN t.is_active = 1 AND t.status = 'pending' THEN t.id END) AS pending_tasks,
        COUNT(DISTINCT CASE WHEN t.is_active = 1 AND t.status = 'in_progress' THEN t.id END) AS in_progress_tasks,
        COUNT(DISTINCT CASE WHEN t.is_active = 1 AND t.status = 'done' THEN t.id END) AS done_tasks,
        COUNT(DISTINCT bm_all.user_id) AS members_count
    FROM boards b
    LEFT JOIN users owner ON owner.id = b.owner_id
    LEFT JOIN tasks t ON t.board_id = b.id
    LEFT JOIN board_members bm_all ON bm_all.board_id = b.id
    WHERE
        ? = 1
        OR b.owner_id = ?
        OR EXISTS (SELECT 1 FROM board_members bm WHERE bm.board_id = b.id AND bm.user_id = ?)
    GROUP BY b.id, owner.name
    ORDER BY b.updated_at DESC, b.created_at DESC
";

$stmt = api_db()->prepare($sql);
$stmt->execute([$isAdmin, $userId, $userId]);
$boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = array_map(static function (array $board): array {
    return [
        'id' => (int)$board['id'],
        'owner_id' => $board['owner_id'] !== null ? (int)$board['owner_id'] : null,
        'owner_name' => $board['owner_name'],
        'title' => $board['title'],
        'description' => $board['description'],
        'visibility' => $board['visibility'],
        'color' => $board['color'],
        'total_tasks' => (int)$board['total_tasks'],
        'pending_tasks' => (int)$board['pending_tasks'],
        'in_progress_tasks' => (int)$board['in_progress_tasks'],
        'done_tasks' => (int)$board['done_tasks'],
        'members_count' => (int)$board['members_count'],
        'created_at' => $board['created_at'],
        'updated_at' => $board['updated_at'],
    ];
}, $boards);

api_json([
    'ok' => true,
    'boards' => $result,
    'count' => count($result),
]);
?>
