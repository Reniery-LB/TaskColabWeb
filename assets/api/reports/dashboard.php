<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

api_require_method(['GET']);

$user = api_current_user();
$userId = (int)$user['id'];
$isAdmin = !empty($user['is_admin']);
$pdo = api_db();

$scopeSql = $isAdmin ? '1 = 1' : "
    EXISTS (
        SELECT 1
        FROM task_assignments ta_scope
        WHERE ta_scope.task_id = t.id
          AND ta_scope.user_id = :scope_user_id
    )
";
$baseWhere = "t.is_active = 1 AND {$scopeSql}";
$params = $isAdmin ? [] : [':scope_user_id' => $userId];

$general = api_report_fetch_one($pdo, "
    SELECT
        COUNT(DISTINCT t.id) AS total_tareas,
        COUNT(DISTINCT CASE WHEN t.status = 'pending' THEN t.id END) AS pendiente,
        COUNT(DISTINCT CASE WHEN t.status = 'in_progress' THEN t.id END) AS en_proceso,
        COUNT(DISTINCT CASE WHEN t.status = 'done' THEN t.id END) AS completado,
        COUNT(DISTINCT CASE WHEN t.status <> 'done' AND t.due_date IS NOT NULL AND t.due_date < CURDATE() THEN t.id END) AS atrasadas,
        COUNT(DISTINCT CASE WHEN t.status <> 'done' AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN t.id END) AS proximas,
        COUNT(DISTINCT ta_users.user_id) AS usuarios_activos
    FROM tasks t
    LEFT JOIN task_assignments ta_users ON ta_users.task_id = t.id
    WHERE {$baseWhere}
", $params);

$total = (int)($general['total_tareas'] ?? 0);
$completed = (int)($general['completado'] ?? 0);
$general['productividad'] = $total > 0 ? (int)round(($completed / $total) * 100) : 0;

$stateDistribution = api_report_states(api_report_fetch_all($pdo, "
    SELECT t.status, COUNT(DISTINCT t.id) AS total_tasks
    FROM tasks t
    WHERE {$baseWhere}
    GROUP BY t.status
", $params));

$activeUsersScopeSql = $isAdmin ? '1 = 1' : 'ta.user_id = :active_scope_user_id';
$activeUsersParams = $isAdmin ? [] : [':active_scope_user_id' => $userId];
$activeUsers = api_report_fetch_all($pdo, "
    SELECT
        u.id,
        u.name AS usuario,
        u.email,
        COUNT(DISTINCT t.id) AS tareas_asignadas,
        COUNT(DISTINCT CASE WHEN t.status = 'done' THEN t.id END) AS tareas_completadas,
        COUNT(DISTINCT CASE WHEN t.status <> 'done' AND t.due_date IS NOT NULL AND t.due_date < CURDATE() THEN t.id END) AS tareas_atrasadas
    FROM users u
    INNER JOIN task_assignments ta ON ta.user_id = u.id
    INNER JOIN tasks t ON t.id = ta.task_id AND t.is_active = 1
    WHERE u.is_active = 1
      AND {$activeUsersScopeSql}
    GROUP BY u.id, u.name, u.email
    HAVING tareas_asignadas > 0
    ORDER BY tareas_asignadas DESC, tareas_completadas DESC
    LIMIT 8
", $activeUsersParams);

$alerts = api_report_alerts(api_report_fetch_all($pdo, "
    SELECT DISTINCT
        t.id,
        t.title,
        t.status,
        t.priority,
        t.due_date,
        b.title AS board_title,
        GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') AS assigned_users
    FROM tasks t
    LEFT JOIN boards b ON b.id = t.board_id
    LEFT JOIN task_assignments ta ON ta.task_id = t.id
    LEFT JOIN users u ON u.id = ta.user_id
    WHERE {$baseWhere}
      AND t.status <> 'done'
      AND (
        (t.due_date IS NOT NULL AND t.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))
        OR t.priority = 'high'
      )
    GROUP BY t.id, t.title, t.status, t.priority, t.due_date, b.title
    ORDER BY t.due_date ASC, t.priority ASC
    LIMIT 12
", $params));

api_json([
    'ok' => true,
    'data' => [
        'general_stats' => [
            'total_tareas' => (int)($general['total_tareas'] ?? 0),
            'pendiente' => (int)($general['pendiente'] ?? 0),
            'en_proceso' => (int)($general['en_proceso'] ?? 0),
            'completado' => (int)($general['completado'] ?? 0),
            'atrasadas' => (int)($general['atrasadas'] ?? 0),
            'proximas' => (int)($general['proximas'] ?? 0),
            'productividad' => (int)$general['productividad'],
            'usuarios_activos' => (int)($general['usuarios_activos'] ?? 0),
        ],
        'state_distribution' => $stateDistribution,
        'active_users' => $activeUsers,
        'alert_tasks' => $alerts,
    ],
]);

function api_report_fetch_one(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function api_report_fetch_all(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function api_report_states(array $rows): array
{
    $labels = ['pending' => 'Pendiente', 'in_progress' => 'En proceso', 'done' => 'Completado'];
    $counts = ['pending' => 0, 'in_progress' => 0, 'done' => 0];
    foreach ($rows as $row) {
        if (isset($counts[$row['status']])) $counts[$row['status']] = (int)$row['total_tasks'];
    }
    $total = array_sum($counts);
    $result = [];
    foreach ($counts as $status => $count) {
        $result[] = [
            'status' => $status,
            'status_display' => $labels[$status],
            'total_tasks' => $count,
            'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
        ];
    }
    return $result;
}

function api_report_alerts(array $tasks): array
{
    $today = new DateTimeImmutable('today');
    foreach ($tasks as &$task) {
        $dueDate = !empty($task['due_date']) ? new DateTimeImmutable($task['due_date']) : null;
        $task['alert_label'] = $dueDate && $dueDate < $today ? 'Atrasada' : ($dueDate && $dueDate == $today ? 'Vence hoy' : 'Próxima');
        $task['assigned_users'] = $task['assigned_users'] ?: 'Sin asignar';
    }
    unset($task);
    return $tasks;
}
?>
