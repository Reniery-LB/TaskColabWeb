<?php
// assets/app/endpointsReportes/get_dashboard_stats.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

try {
    require_once __DIR__ . '/../../../config/db.php';
    require_once __DIR__ . '/../../models/ProjectModel.php';

    $isAdminStmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ? AND is_active = 1");
    $isAdminStmt->execute([$userId]);
    $isAdmin = (int)$isAdminStmt->fetchColumn() === 1;
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $projectName = 'General';

    if ($projectId > 0) {
        $projectModel = new ProjectModel();
        $allowedProjects = $projectModel->listProjects($userId);
        $selectedProject = null;

        foreach ($allowedProjects as $project) {
            if ((int)$project['id'] === $projectId) {
                $selectedProject = $project;
                break;
            }
        }

        if (!$selectedProject) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes acceso a este proyecto']);
            exit;
        }

        $projectName = $selectedProject['name'] ?? 'Proyecto';
    }

    $scopeSql = $isAdmin ? "1 = 1" : "
        EXISTS (
            SELECT 1
            FROM task_assignments ta_scope
            WHERE ta_scope.task_id = t.id
              AND ta_scope.user_id = :scope_user_id
        )
    ";

    $baseWhere = "t.is_active = 1 AND {$scopeSql}";

    $params = [];
    if (!$isAdmin) {
        $params[':scope_user_id'] = $userId;
    }
    if ($projectId > 0) {
        $baseWhere .= " AND EXISTS (
            SELECT 1
            FROM boards b_scope
            WHERE b_scope.id = t.board_id
              AND b_scope.project_id = :project_id
        )";
        $params[':project_id'] = $projectId;
    }

    $general = fetchOne($pdo, "
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
    $general['productividad'] = $total > 0 ? round(($completed / $total) * 100) : 0;

    $stateDistribution = fetchAll($pdo, "
        SELECT t.status, COUNT(DISTINCT t.id) AS total_tasks
        FROM tasks t
        WHERE {$baseWhere}
        GROUP BY t.status
    ", $params);
    $stateDistribution = normalizeStates($stateDistribution);

    $activeUsersScopeSql = $isAdmin ? "1 = 1" : "ta.user_id = :active_scope_user_id";
    $activeUsersParams = $isAdmin ? [] : [':active_scope_user_id' => $userId];
    $activeUsersProjectSql = "1 = 1";
    if ($projectId > 0) {
        $activeUsersProjectSql = "
            EXISTS (
                SELECT 1
                FROM boards b_active_scope
                WHERE b_active_scope.id = t.board_id
                  AND b_active_scope.project_id = :active_project_id
            )
        ";
        $activeUsersParams[':active_project_id'] = $projectId;
    }
    $activeUsers = fetchAll($pdo, "
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
          AND {$activeUsersProjectSql}
        GROUP BY u.id, u.name, u.email
        HAVING tareas_asignadas > 0
        ORDER BY tareas_asignadas DESC, tareas_completadas DESC
        LIMIT 8
    ", $activeUsersParams);
    $totalAssigned = array_sum(array_map(fn($item) => (int)$item['tareas_asignadas'], $activeUsers));
    foreach ($activeUsers as &$user) {
        $assigned = (int)$user['tareas_asignadas'];
        $user['tareas_asignadas'] = $assigned;
        $user['tareas_completadas'] = (int)$user['tareas_completadas'];
        $user['tareas_atrasadas'] = (int)$user['tareas_atrasadas'];
        $user['porcentaje'] = $totalAssigned > 0 ? round(($assigned / $totalAssigned) * 100) : 0;
    }
    unset($user);

    $completedByWeek = fetchAll($pdo, "
        SELECT
            YEARWEEK(t.updated_at, 3) AS week_key,
            DATE(DATE_SUB(t.updated_at, INTERVAL WEEKDAY(t.updated_at) DAY)) AS week_start,
            COUNT(DISTINCT t.id) AS total
        FROM tasks t
        WHERE {$baseWhere}
          AND t.status = 'done'
          AND t.updated_at >= DATE_SUB(CURDATE(), INTERVAL 6 WEEK)
        GROUP BY week_key, week_start
        ORDER BY week_start ASC
    ", $params);

    $priorityRows = fetchAll($pdo, "
        SELECT
            t.priority,
            SUM(CASE WHEN t.status <> 'done' AND t.due_date IS NOT NULL AND t.due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue,
            SUM(CASE WHEN t.status <> 'done' AND t.due_date = CURDATE() THEN 1 ELSE 0 END) AS today,
            SUM(CASE WHEN t.status <> 'done' AND t.due_date > CURDATE() AND t.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS week,
            SUM(CASE WHEN t.status <> 'done' AND t.due_date > DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS later,
            SUM(CASE WHEN t.status <> 'done' AND t.due_date IS NULL THEN 1 ELSE 0 END) AS no_date
        FROM tasks t
        WHERE {$baseWhere}
        GROUP BY t.priority
    ", $params);
    $priorityHeatmap = normalizePriorityHeatmap($priorityRows);

    $alerts = fetchAll($pdo, "
        SELECT DISTINCT
            t.id,
            t.title,
            t.description,
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
        GROUP BY t.id, t.title, t.description, t.status, t.priority, t.due_date, b.title
        ORDER BY
            CASE
                WHEN t.due_date IS NOT NULL AND t.due_date < CURDATE() THEN 0
                WHEN t.priority = 'high' THEN 1
                WHEN t.due_date IS NOT NULL THEN 2
                ELSE 3
            END,
            t.due_date ASC,
            t.priority ASC
        LIMIT 12
    ", $params);
    $alerts = enrichAlerts($alerts);

    echo json_encode([
        'success' => true,
        'data' => [
            'scope' => [
                'type' => $projectId > 0 ? 'project' : 'general',
                'project_id' => $projectId > 0 ? $projectId : null,
                'project_name' => $projectName,
                'is_admin' => $isAdmin,
            ],
            'general_stats' => [
                'total_tareas' => (int)$general['total_tareas'],
                'pendiente' => (int)$general['pendiente'],
                'en_proceso' => (int)$general['en_proceso'],
                'completado' => (int)$general['completado'],
                'atrasadas' => (int)$general['atrasadas'],
                'proximas' => (int)$general['proximas'],
                'productividad' => (int)$general['productividad'],
                'usuarios_activos' => (int)$general['usuarios_activos'],
            ],
            'board_progress' => $stateDistribution,
            'state_distribution' => $stateDistribution,
            'active_users' => $activeUsers,
            'completed_by_week' => normalizeWeeks($completedByWeek),
            'priority_heatmap' => $priorityHeatmap,
            'overdue_tasks' => array_values(array_filter($alerts, fn($task) => $task['alert_type'] === 'overdue')),
            'alert_tasks' => $alerts,
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('Error en get_dashboard_stats: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function fetchOne(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function fetchAll(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function normalizeStates(array $rows): array {
    $labels = [
        'pending' => 'Pendiente',
        'in_progress' => 'En proceso',
        'done' => 'Completado',
    ];
    $ordered = [
        'pending' => 0,
        'in_progress' => 0,
        'done' => 0,
    ];

    foreach ($rows as $row) {
        if (array_key_exists($row['status'], $ordered)) {
            $ordered[$row['status']] = (int)$row['total_tasks'];
        }
    }

    $total = array_sum($ordered);
    $result = [];
    foreach ($ordered as $status => $count) {
        $result[] = [
            'status' => $status,
            'status_display' => $labels[$status],
            'total_tasks' => $count,
            'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
        ];
    }

    return $result;
}

function normalizeWeeks(array $rows): array {
    $byWeek = [];
    foreach ($rows as $row) {
        $byWeek[$row['week_start']] = (int)$row['total'];
    }

    $weeks = [];
    $start = new DateTimeImmutable('monday this week');
    $start = $start->modify('-5 weeks');

    for ($i = 0; $i < 6; $i++) {
        $week = $start->modify("+{$i} weeks");
        $key = $week->format('Y-m-d');
        $weeks[] = [
            'week_start' => $key,
            'label' => $week->format('d/m/Y'),
            'total' => $byWeek[$key] ?? 0,
        ];
    }

    return $weeks;
}

function normalizePriorityHeatmap(array $rows): array {
    $labels = [
        'high' => 'Alta',
        'medium' => 'Media',
        'low' => 'Baja',
    ];
    $buckets = [
        'overdue' => 'Atrasadas',
        'today' => 'Hoy',
        'week' => '7 días',
        'later' => 'Después',
        'no_date' => 'Sin fecha',
    ];

    $indexed = [];
    foreach ($rows as $row) {
        $indexed[$row['priority']] = $row;
    }

    $result = [];
    foreach ($labels as $priority => $label) {
        $row = $indexed[$priority] ?? [];
        $cells = [];
        foreach ($buckets as $bucket => $bucketLabel) {
            $cells[] = [
                'bucket' => $bucket,
                'label' => $bucketLabel,
                'value' => (int)($row[$bucket] ?? 0),
            ];
        }
        $result[] = [
            'priority' => $priority,
            'label' => $label,
            'cells' => $cells,
        ];
    }

    return [
        'buckets' => array_values($buckets),
        'rows' => $result,
    ];
}

function enrichAlerts(array $tasks): array {
    $priorityLabels = ['high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja'];
    $statusLabels = ['pending' => 'Pendiente', 'in_progress' => 'En proceso', 'done' => 'Completado'];
    $today = new DateTimeImmutable('today');

    foreach ($tasks as &$task) {
        $dueDate = !empty($task['due_date']) ? new DateTimeImmutable($task['due_date']) : null;
        $alertType = 'priority';
        $alertLabel = 'Prioridad alta';

        if ($dueDate && $dueDate < $today) {
            $alertType = 'overdue';
            $alertLabel = 'Atrasada';
        } elseif ($dueDate && $dueDate == $today) {
            $alertType = 'today';
            $alertLabel = 'Vence hoy';
        } elseif ($dueDate) {
            $alertType = 'soon';
            $alertLabel = 'Próxima';
        }

        $task['priority_display'] = $priorityLabels[$task['priority']] ?? $task['priority'];
        $task['status_display'] = $statusLabels[$task['status']] ?? $task['status'];
        $task['due_date_display'] = $dueDate ? $dueDate->format('d/m/Y') : 'Sin fecha';
        $task['alert_type'] = $alertType;
        $task['alert_label'] = $alertLabel;
        $task['assigned_users'] = $task['assigned_users'] ?: 'Sin asignar';
    }
    unset($task);

    return $tasks;
}
