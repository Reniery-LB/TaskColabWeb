<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

api_require_method(['GET', 'POST', 'PATCH', 'DELETE']);

$user = api_current_user();
$userId = (int)$user['id'];
$isAdmin = !empty($user['is_admin']) ? 1 : 0;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    api_list_tasks($userId, $isAdmin);
}

$input = api_input();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    api_create_task($input, $userId, $isAdmin);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    api_update_task($input, $userId, $isAdmin);
}

api_delete_tasks($input, $userId, $isAdmin);

function api_list_tasks(int $userId, int $isAdmin): void
{
    $boardId = isset($_GET['board_id']) ? (int)$_GET['board_id'] : null;
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
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

    if ($projectId !== null && $projectId > 0) {
        $where[] = 'b.project_id = ?';
        $params[] = $projectId;
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

    $result = array_map('api_task_payload', $tasks);

    api_json([
        'ok' => true,
        'tasks' => $result,
        'count' => count($result),
    ]);
}

function api_create_task(array $input, int $userId, int $isAdmin): void
{
    require_once __DIR__ . '/../../models/TaskModel.php';
    require_once __DIR__ . '/../../models/ProjectModel.php';

    $title = trim((string)($input['title'] ?? ''));
    if ($title === '') {
        api_json(['ok' => false, 'message' => 'El título es obligatorio'], 422);
    }

    $projectModel = new ProjectModel();
    $boardId = (int)($input['board_id'] ?? 0);
    if ($boardId <= 0 && !empty($input['project_id'])) {
        $project = $projectModel->getProjectForUser((int)$input['project_id'], $userId);
        $boardId = (int)($project['board_id'] ?? 0);
    }
    if ($boardId <= 0) {
        $project = $projectModel->getOrCreateDefaultProject($userId);
        $boardId = (int)($project['board_id'] ?? 0);
    }
    if ($boardId <= 0 || !$projectModel->userCanAccessBoard($userId, $boardId)) {
        api_json(['ok' => false, 'message' => 'No tienes acceso a este tablero'], 403);
    }

    $dueDate = trim((string)($input['due_date'] ?? ''));
    if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
        api_json(['ok' => false, 'message' => 'Fecha límite inválida'], 422);
    }

    $assignedTo = [];
    if ($isAdmin) {
        $rawAssigned = $input['assigned_to'] ?? $input['assigned_user_ids'] ?? [];
        if (!is_array($rawAssigned)) {
            $rawAssigned = $rawAssigned ? [$rawAssigned] : [];
        }
        foreach ($rawAssigned as $assignedId) {
            $assignedId = (int)$assignedId;
            if ($assignedId > 0) $assignedTo[] = $assignedId;
        }
        $assignedTo = array_values(array_unique($assignedTo));
    } else {
        $assignedTo = [$userId];
    }

    $taskModel = new TaskModel();
    $taskId = $taskModel->createTask([
        'title' => $title,
        'description' => trim((string)($input['description'] ?? '')),
        'status' => api_status_to_model((string)($input['status'] ?? 'pending')),
        'priority' => api_priority_to_model((string)($input['priority'] ?? 'medium')),
        'due_date' => $dueDate !== '' ? $dueDate : null,
        'created_by' => $userId,
        'assigned_to' => $assignedTo,
        'board_id' => $boardId,
        'column_created' => (string)($input['status'] ?? 'pending'),
    ]);

    api_json(['ok' => true, 'message' => 'Tarea creada', 'task_id' => (int)$taskId], 201);
}

function api_update_task(array $input, int $userId, int $isAdmin): void
{
    $taskId = (int)($input['task_id'] ?? $input['id'] ?? 0);
    if ($taskId <= 0) {
        api_json(['ok' => false, 'message' => 'Tarea inválida'], 422);
    }

    if (!api_can_mutate_task($taskId, $userId, $isAdmin)) {
        api_json(['ok' => false, 'message' => 'No tienes permisos para editar esta tarea'], 403);
    }

    $fields = [];
    $params = [':task_id' => $taskId];
    if (array_key_exists('title', $input)) {
        $title = trim((string)$input['title']);
        if ($title === '') api_json(['ok' => false, 'message' => 'El título es obligatorio'], 422);
        $fields[] = 'title = :title';
        $params[':title'] = $title;
    }
    if (array_key_exists('description', $input)) {
        $fields[] = 'description = :description';
        $params[':description'] = trim((string)$input['description']);
    }
    if (array_key_exists('status', $input)) {
        $status = api_status_to_db((string)$input['status']);
        $fields[] = 'status = :status';
        $params[':status'] = $status;
    }
    if (array_key_exists('priority', $input)) {
        $priority = api_priority_to_db((string)$input['priority']);
        $fields[] = 'priority = :priority';
        $params[':priority'] = $priority;
    }
    if (array_key_exists('due_date', $input)) {
        $dueDate = trim((string)($input['due_date'] ?? ''));
        if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            api_json(['ok' => false, 'message' => 'Fecha límite inválida'], 422);
        }
        $fields[] = 'due_date = :due_date';
        $params[':due_date'] = $dueDate !== '' ? $dueDate : null;
    }

    if (!$fields) {
        api_json(['ok' => false, 'message' => 'No hay cambios para aplicar'], 422);
    }

    $fields[] = 'updated_at = NOW()';
    $stmt = api_db()->prepare('UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :task_id');
    $stmt->execute($params);

    api_json(['ok' => true, 'message' => 'Tarea actualizada', 'task_id' => $taskId]);
}

function api_delete_tasks(array $input, int $userId, int $isAdmin): void
{
    $ids = $input['task_ids'] ?? $input['ids'] ?? null;
    if (!is_array($ids)) {
        $ids = [(int)($input['task_id'] ?? $input['id'] ?? 0)];
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) {
        api_json(['ok' => false, 'message' => 'No hay tareas para eliminar'], 422);
    }

    foreach ($ids as $taskId) {
        if (!api_can_mutate_task($taskId, $userId, $isAdmin)) {
            api_json(['ok' => false, 'message' => 'No tienes permisos para eliminar una o más tareas'], 403);
        }
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = api_db()->prepare("UPDATE tasks SET is_active = 0, updated_at = NOW() WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    api_json(['ok' => true, 'message' => 'Tareas eliminadas', 'task_ids' => $ids]);
}

function api_can_mutate_task(int $taskId, int $userId, int $isAdmin): bool
{
    $stmt = api_db()->prepare("
        SELECT COUNT(*)
        FROM tasks t
        LEFT JOIN boards b ON b.id = t.board_id
        WHERE t.id = ?
          AND t.is_active = 1
          AND (
            ? = 1
            OR t.created_by = ?
            OR EXISTS (SELECT 1 FROM task_assignments ta WHERE ta.task_id = t.id AND ta.user_id = ?)
            OR b.owner_id = ?
            OR EXISTS (SELECT 1 FROM board_members bm WHERE bm.board_id = t.board_id AND bm.user_id = ?)
          )
    ");
    $stmt->execute([$taskId, $isAdmin, $userId, $userId, $userId, $userId]);
    return (int)$stmt->fetchColumn() > 0;
}

function api_task_payload(array $task): array
{
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
}

function api_status_to_db(string $status): string
{
    return match (strtolower($status)) {
        'in_progress', 'en proceso' => 'in_progress',
        'done', 'completed', 'completado' => 'done',
        default => 'pending',
    };
}

function api_status_to_model(string $status): string
{
    return match (api_status_to_db($status)) {
        'in_progress' => 'En proceso',
        'done' => 'Completado',
        default => 'Pendiente',
    };
}

function api_priority_to_db(string $priority): string
{
    return match (strtolower($priority)) {
        'low', 'baja' => 'low',
        'high', 'alta' => 'high',
        default => 'medium',
    };
}

function api_priority_to_model(string $priority): string
{
    return match (api_priority_to_db($priority)) {
        'high' => 'Alta',
        'low' => 'Baja',
        default => 'Media',
    };
}
?>
