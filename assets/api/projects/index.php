<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../models/ProjectModel.php';

api_require_method(['GET', 'POST', 'PATCH', 'DELETE']);

$user = api_current_user();
$userId = (int)$user['id'];
$model = new ProjectModel();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $projects = array_map('api_project_payload', $model->listProjects($userId));
        api_json(['ok' => true, 'projects' => $projects, 'count' => count($projects)]);
    }

    $input = api_input();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim((string)($input['name'] ?? ''));
        if ($name === '') {
            api_json(['ok' => false, 'message' => 'El nombre del proyecto es obligatorio'], 422);
        }

        $project = $model->createProject([
            'name' => $name,
            'description' => trim((string)($input['description'] ?? '')),
            'color' => api_project_color((string)($input['color'] ?? '#1B5CFF')),
            'status' => 'active',
            'due_date' => api_project_due_date($input['due_date'] ?? null),
        ], $userId);

        api_json(['ok' => true, 'message' => 'Proyecto creado', 'project' => api_project_payload($project)], 201);
    }

    $projectId = (int)($input['project_id'] ?? $input['id'] ?? 0);
    if ($projectId <= 0) {
        api_json(['ok' => false, 'message' => 'Proyecto inválido'], 422);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        $updates = [];
        if (array_key_exists('name', $input)) $updates['name'] = $input['name'];
        if (array_key_exists('description', $input)) $updates['description'] = $input['description'];
        if (array_key_exists('color', $input)) $updates['color'] = api_project_color((string)$input['color']);
        if (array_key_exists('due_date', $input)) $updates['due_date'] = api_project_due_date($input['due_date']);
        if (array_key_exists('status', $input)) $updates['status'] = $input['status'];

        $project = $model->updateProject($projectId, $updates, $userId);

        api_json(['ok' => true, 'message' => 'Proyecto actualizado', 'project' => api_project_payload($project)]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $model->archiveProject($projectId, $userId);
        api_json(['ok' => true, 'message' => 'Proyecto archivado', 'project_id' => $projectId]);
    }
} catch (Throwable $e) {
    $status = $e instanceof InvalidArgumentException ? 422 : 500;
    api_json(['ok' => false, 'message' => $e->getMessage()], $status);
}

function api_project_payload(array $project): array
{
    $total = (int)($project['total_tasks'] ?? 0);
    $done = (int)($project['done_tasks'] ?? 0);

    return [
        'id' => (int)$project['id'],
        'name' => $project['name'],
        'description' => $project['description'] ?? '',
        'owner_id' => isset($project['owner_id']) ? (int)$project['owner_id'] : null,
        'status' => $project['status'] ?? 'active',
        'color' => $project['color'] ?? '#1B5CFF',
        'due_date' => $project['due_date'] ?? null,
        'board_id' => (int)($project['board_id'] ?? 0),
        'total_tasks' => $total,
        'pending_tasks' => (int)($project['pending_tasks'] ?? 0),
        'in_progress_tasks' => (int)($project['in_progress_tasks'] ?? 0),
        'done_tasks' => $done,
        'members_count' => (int)($project['members_count'] ?? 0),
        'progress' => $total > 0 ? (int)round(($done / $total) * 100) : 0,
        'created_at' => $project['created_at'] ?? null,
        'updated_at' => $project['updated_at'] ?? null,
    ];
}

function api_project_color(string $color): string
{
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#1B5CFF';
}

function api_project_due_date(mixed $value): ?string
{
    $date = trim((string)($value ?? ''));
    if ($date === '') {
        return null;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new InvalidArgumentException('Fecha objetivo inválida');
    }
    return $date;
}
?>
