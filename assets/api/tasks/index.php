<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

api_require_method(['GET']);

$user = api_current_user();
$userId = (int)$user['id'];
$isAdmin = !empty($user['is_admin']) ? 1 : 0;
$boardId = isset($_GET['board_id']) ? (int)$_GET['board_id'] : null;
$status = isset($_GET['status']) ? trim((string)$_GET['status']) : null;

$where = ['t.is_active = 1'];
$params = [$isAdmin, $userId, $userId, $userId, $userId];

$permissionSql = "
    (
        ? = 1
        OR t.created_by = ?
        OR EXISTS (SELECT 1 FROM task_assignments pta WHERE pta.task_id = t.id AND pta.user_id = ?)
        OR b.owner_id = ?
        OR EXISTS (SELECT 1 FROM board_members bm WHERE bm.board_id = t.board_id AND bm.user_id = ?)
    )
";
$where[] = $permissionSql;

if ($boardId !== null && $boardId > 0) {
    $where[] = 't.board_id = ?';
    $params[] = $boardId;
}

if ($status !== null && $status !== '') {
    $allowedStatuses = ['pending', 'in_progress', 'done'];
    if (!in_array($status, $allowedStatuses, true)) {
        api_json(['ok' => false, 'message' => 'Estado inválido'], 422);
    }
    $where[] = 't.status = ?';
    $params[] = $status;
}

$sql = "
    SELECT
        t.id,
        t.board_id,
        b.title AS board_title,
        t.title,
        t.description,
        t.status,
        t.priority,
        t.due_date,
        t.created_by,
        creator.name AS created_by_name,
        t.position,
        t.column_created,
        t.created_at,
        t.updated_at,
        GROUP_CONCAT(DISTINCT assignee.id ORDER BY assignee.name SEPARATOR ',') AS assigned_user_ids,
        GROUP_CONCAT(DISTINCT assignee.name ORDER BY assignee.name SEPARATOR '||') AS assigned_user_names
    FROM tasks t
    LEFT JOIN boards b ON b.id = t.board_id
    LEFT JOIN users creator ON creator.id = t.created_by
    LEFT JOIN task_assignments ta ON ta.task_id = t.id
    LEFT JOIN users assignee ON assignee.id = ta.user_id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY t.id, b.title, creator.name
    ORDER BY t.status ASC, t.position ASC, t.updated_at DESC
";

$stmt = api_db()->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = array_map(static function (array $task): array {
    $assignedIds = array_values(array_filter(explode(',', (string)($task['assigned_user_ids'] ?? ''))));
    $assignedNames = array_values(array_filter(explode('||', (string)($task['assigned_user_names'] ?? ''))));
    $assignedUsers = [];

    foreach ($assignedIds as $index => $id) {
        $assignedUsers[] = [
            'id' => (int)$id,
            'name' => $assignedNames[$index] ?? '',
        ];
    }

    return [
        'id' => (int)$task['id'],
        'board_id' => (int)$task['board_id'],
        'board_title' => $task['board_title'],
        'title' => $task['title'],
        'description' => $task['description'] ?? '',
        'status' => $task['status'],
        'priority' => $task['priority'],
        'due_date' => $task['due_date'],
        'created_by' => $task['created_by'] !== null ? (int)$task['created_by'] : null,
        'created_by_name' => $task['created_by_name'],
        'assigned_users' => $assignedUsers,
        'position' => (int)($task['position'] ?? 0),
        'column_created' => $task['column_created'],
        'created_at' => $task['created_at'],
        'updated_at' => $task['updated_at'],
    ];
}, $tasks);

api_json([
    'ok' => true,
    'tasks' => $result,
    'count' => count($result),
]);
?>
