<?php
// assets/app/endpointsReportes/export_pdf.php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']['id'])) {
    http_response_code(401);
    die('No auth');
}

$dompdfPath = __DIR__ . '/../../../vendor/dompdf/autoload.inc.php';
$dbPath = __DIR__ . '/../../../config/db.php';

if (!file_exists($dompdfPath)) die('No DOMPDF');
if (!file_exists($dbPath)) die('No DB');

require_once $dompdfPath;
require_once $dbPath;

use Dompdf\Dompdf;
use Dompdf\Options;

$userId = (int)$_SESSION['user']['id'];
$userName = $_SESSION['user']['name'] ?? 'Usuario';
$userEmail = $_SESSION['user']['email'] ?? '';

$isAdminStmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ? AND is_active = 1");
$isAdminStmt->execute([$userId]);
$isAdmin = (int)$isAdminStmt->fetchColumn() === 1;

$scopeSql = $isAdmin ? "1 = 1" : "
    (
        t.created_by = :scope_user_id
        OR EXISTS (
            SELECT 1
            FROM task_assignments ta_scope
            WHERE ta_scope.task_id = t.id
              AND ta_scope.user_id = :scope_user_id_exists
        )
    )
";
$baseWhere = "t.is_active = 1 AND {$scopeSql}";
$params = $isAdmin ? [] : [':scope_user_id' => $userId, ':scope_user_id_exists' => $userId];

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
$productivity = $total > 0 ? round(($completed / $total) * 100) : 0;

$states = normalizeStates(fetchAll($pdo, "
    SELECT t.status, COUNT(DISTINCT t.id) AS total_tasks
    FROM tasks t
    WHERE {$baseWhere}
    GROUP BY t.status
", $params));

$users = fetchAll($pdo, "
    SELECT
        u.name AS usuario,
        COUNT(DISTINCT t.id) AS tareas_asignadas,
        COUNT(DISTINCT CASE WHEN t.status = 'done' THEN t.id END) AS tareas_completadas
    FROM users u
    INNER JOIN task_assignments ta ON ta.user_id = u.id
    INNER JOIN tasks t ON t.id = ta.task_id AND t.is_active = 1
    WHERE u.is_active = 1
    GROUP BY u.id, u.name
    HAVING tareas_asignadas > 0
    ORDER BY tareas_asignadas DESC
    LIMIT 7
");

$weeks = normalizeWeeks(fetchAll($pdo, "
    SELECT
        DATE(DATE_SUB(t.updated_at, INTERVAL WEEKDAY(t.updated_at) DAY)) AS week_start,
        COUNT(DISTINCT t.id) AS total
    FROM tasks t
    WHERE {$baseWhere}
      AND t.status = 'done'
      AND t.updated_at >= DATE_SUB(CURDATE(), INTERVAL 6 WEEK)
    GROUP BY week_start
    ORDER BY week_start ASC
", $params));

$heatmap = normalizePriorityHeatmap(fetchAll($pdo, "
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
", $params));

$alerts = enrichAlerts(fetchAll($pdo, "
    SELECT DISTINCT
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
    ORDER BY t.due_date ASC
    LIMIT 10
", $params));

$fecha = date('d/m/Y H:i');
$pdfFileName = 'reporte_taskcolab_' . date('Y-m-d_His') . '.pdf';

$html = buildPdfHtml([
    'userName' => $userName,
    'userEmail' => $userEmail,
    'fecha' => $fecha,
    'general' => $general,
    'productivity' => $productivity,
    'states' => $states,
    'users' => $users,
    'weeks' => $weeks,
    'heatmap' => $heatmap,
    'alerts' => $alerts,
]);

ob_end_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream($pdfFileName, ['Attachment' => true, 'compress' => true]);
exit;

function buildPdfHtml(array $data): string {
    $g = $data['general'];
    $states = $data['states'];
    $users = $data['users'];
    $weeks = $data['weeks'];
    $heatmap = $data['heatmap'];
    $alerts = $data['alerts'];

    $stateBars = '';
    foreach ($states as $state) {
        $color = ['pending' => '#f59e0b', 'in_progress' => '#1b5cff', 'done' => '#16a34a'][$state['status']] ?? '#64748b';
        $stateBars .= barRow(e($state['status_display']), (int)$state['total_tasks'], (float)$state['percentage'], $color);
    }

    $userRows = '';
    $maxUserTasks = max(1, ...array_map(fn($u) => (int)$u['tareas_asignadas'], $users ?: [['tareas_asignadas' => 0]]));
    foreach ($users as $user) {
        $percent = round(((int)$user['tareas_asignadas'] / $maxUserTasks) * 100);
        $userRows .= barRow(e($user['usuario']), (int)$user['tareas_asignadas'], $percent, '#1b5cff', (int)$user['tareas_completadas'] . ' completadas');
    }
    if ($userRows === '') {
        $userRows = '<p class="empty">Sin usuarios con tareas asignadas.</p>';
    }

    $weekRows = '';
    $maxWeek = max(1, ...array_map(fn($w) => (int)$w['total'], $weeks ?: [['total' => 0]]));
    foreach ($weeks as $week) {
        $percent = round(((int)$week['total'] / $maxWeek) * 100);
        $weekRows .= barRow(e($week['label']), (int)$week['total'], $percent, '#16a34a');
    }

    $heatRows = '';
    foreach ($heatmap['rows'] as $row) {
        $heatRows .= '<tr><th>' . e($row['label']) . '</th>';
        foreach ($row['cells'] as $cell) {
            $value = (int)$cell['value'];
            $bg = $value > 0 ? '#dbeafe' : '#f8fafc';
            $heatRows .= '<td style="background:' . $bg . ';">' . $value . '</td>';
        }
        $heatRows .= '</tr>';
    }

    $alertRows = '';
    foreach ($alerts as $task) {
        $alertRows .= '
            <tr>
                <td><span class="pill pill-' . e($task['alert_type']) . '">' . e($task['alert_label']) . '</span></td>
                <td><strong>' . e($task['title']) . '</strong><br><small>' . e($task['board_title'] ?: 'Sin tablero') . '</small></td>
                <td>' . e($task['assigned_users'] ?: 'Sin asignar') . '</td>
                <td>' . e($task['due_date_display']) . '</td>
                <td>' . e($task['priority_display']) . '</td>
            </tr>';
    }
    if ($alertRows === '') {
        $alertRows = '<tr><td colspan="5" class="empty-cell">Sin alertas críticas por ahora.</td></tr>';
    }

    return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page { margin: 14mm; }
body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 11px; margin: 0; background: #ffffff; }
.cover { background: #102a66; color: #ffffff; padding: 22px 24px; border-radius: 16px; margin-bottom: 16px; }
.eyebrow { color: #a9d8ff; font-size: 10px; text-transform: uppercase; letter-spacing: .8px; font-weight: bold; }
h1 { font-size: 30px; margin: 6px 0 6px; }
.cover p { margin: 2px 0; color: #dbeafe; }
.kpis { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 0 12px; }
.kpis td { width: 20%; background: #f8fbff; border: 1px solid #dbe6f7; border-top: 5px solid #1b5cff; border-radius: 10px; padding: 12px; }
.kpis .green { border-top-color: #16a34a; }
.kpis .red { border-top-color: #dc2626; }
.kpis .amber { border-top-color: #f59e0b; }
.kpis span { display: block; color: #64748b; font-size: 10px; font-weight: bold; }
.kpis strong { display: block; color: #172033; font-size: 24px; margin: 7px 0 4px; }
.section { margin-top: 14px; page-break-inside: avoid; }
.section h2 { margin: 0 0 8px; color: #172033; font-size: 16px; }
.panel { border: 1px solid #dbe6f7; border-radius: 12px; padding: 14px; background: #ffffff; }
.grid { width: 100%; border-collapse: separate; border-spacing: 10px; }
.grid td { width: 50%; vertical-align: top; }
.bar-row { margin: 9px 0; }
.bar-top { width: 100%; font-size: 10px; color: #475569; }
.bar-top strong { color: #172033; }
.track { height: 9px; border-radius: 99px; background: #eaf1ff; overflow: hidden; margin-top: 5px; }
.fill { height: 9px; border-radius: 99px; }
.heatmap { width: 100%; border-collapse: collapse; }
.heatmap th, .heatmap td { border: 1px solid #dbe6f7; padding: 8px; text-align: center; }
.heatmap th { background: #f8fbff; color: #475569; }
.alerts { width: 100%; border-collapse: collapse; }
.alerts th, .alerts td { border-bottom: 1px solid #dbe6f7; padding: 8px; text-align: left; vertical-align: top; }
.alerts th { background: #f8fbff; color: #64748b; text-transform: uppercase; font-size: 9px; }
.alerts small { color: #64748b; }
.pill { display: inline-block; padding: 4px 8px; border-radius: 99px; font-size: 9px; font-weight: bold; }
.pill-overdue { color: #991b1b; background: #fee2e2; }
.pill-today, .pill-soon { color: #92400e; background: #fef3c7; }
.pill-priority { color: #1e40af; background: #dbeafe; }
.empty, .empty-cell { color: #64748b; text-align: center; padding: 16px; }
.footer { margin-top: 18px; padding-top: 10px; border-top: 1px solid #dbe6f7; color: #64748b; text-align: center; font-size: 9px; }
</style>
</head>
<body>
<div class="cover">
    <div class="eyebrow">TaskColab · Reporte ejecutivo</div>
    <h1>Reporte moderno de productividad</h1>
    <p>Generado: ' . e($data['fecha']) . '</p>
    <p>Usuario: ' . e($data['userName']) . ' · ' . e($data['userEmail']) . '</p>
</div>
<table class="kpis">
    <tr>
        <td><span>Total</span><strong>' . (int)$g['total_tareas'] . '</strong><span>Tareas activas</span></td>
        <td class="green"><span>Completadas</span><strong>' . (int)$g['completado'] . '</strong><span>' . (int)$data['productivity'] . '% productividad</span></td>
        <td class="red"><span>Atrasadas</span><strong>' . (int)$g['atrasadas'] . '</strong><span>Requieren atención</span></td>
        <td class="amber"><span>Próximas</span><strong>' . (int)$g['proximas'] . '</strong><span>Vencen en 7 días</span></td>
        <td><span>Usuarios</span><strong>' . (int)$g['usuarios_activos'] . '</strong><span>Activos</span></td>
    </tr>
</table>
<table class="grid"><tr>
    <td><div class="panel"><h2>Distribución por estado</h2>' . $stateBars . '</div></td>
    <td><div class="panel"><h2>Tareas por usuario</h2>' . $userRows . '</div></td>
</tr></table>
<div class="section panel"><h2>Completadas por semana</h2>' . $weekRows . '</div>
<div class="section panel"><h2>Carga por prioridad y vencimiento</h2>
    <table class="heatmap">
        <tr><th>Prioridad</th><th>Atrasadas</th><th>Hoy</th><th>7 días</th><th>Después</th><th>Sin fecha</th></tr>
        ' . $heatRows . '
    </table>
</div>
<div class="section panel"><h2>Alertas de tareas</h2>
    <table class="alerts">
        <tr><th>Alerta</th><th>Tarea</th><th>Responsable</th><th>Fecha</th><th>Prioridad</th></tr>
        ' . $alertRows . '
    </table>
</div>
<div class="footer">Generado por TaskColab · ' . date('Y') . '</div>
</body>
</html>';
}

function barRow(string $label, int $value, float $percent, string $color, string $extra = ''): string {
    $percent = max(0, min(100, $percent));
    return '<div class="bar-row">
        <div class="bar-top"><strong>' . $label . '</strong> <span style="float:right;">' . $value . ($extra ? ' · ' . e($extra) : '') . '</span></div>
        <div class="track"><div class="fill" style="width:' . $percent . '%; background:' . $color . ';"></div></div>
    </div>';
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
    $labels = ['pending' => 'Pendiente', 'in_progress' => 'En proceso', 'done' => 'Completado'];
    $ordered = ['pending' => 0, 'in_progress' => 0, 'done' => 0];
    foreach ($rows as $row) {
        if (isset($ordered[$row['status']])) $ordered[$row['status']] = (int)$row['total_tasks'];
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
    foreach ($rows as $row) $byWeek[$row['week_start']] = (int)$row['total'];
    $weeks = [];
    $start = (new DateTimeImmutable('monday this week'))->modify('-5 weeks');
    for ($i = 0; $i < 6; $i++) {
        $week = $start->modify("+{$i} weeks");
        $key = $week->format('Y-m-d');
        $weeks[] = ['label' => $week->format('d M'), 'total' => $byWeek[$key] ?? 0];
    }
    return $weeks;
}

function normalizePriorityHeatmap(array $rows): array {
    $labels = ['high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja'];
    $keys = ['overdue', 'today', 'week', 'later', 'no_date'];
    $indexed = [];
    foreach ($rows as $row) $indexed[$row['priority']] = $row;
    $result = [];
    foreach ($labels as $priority => $label) {
        $cells = [];
        foreach ($keys as $key) {
            $cells[] = ['value' => (int)($indexed[$priority][$key] ?? 0)];
        }
        $result[] = ['label' => $label, 'cells' => $cells];
    }
    return ['rows' => $result];
}

function enrichAlerts(array $tasks): array {
    $priorityLabels = ['high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja'];
    $today = new DateTimeImmutable('today');
    foreach ($tasks as &$task) {
        $due = !empty($task['due_date']) ? new DateTimeImmutable($task['due_date']) : null;
        $type = 'priority';
        $label = 'Prioridad alta';
        if ($due && $due < $today) {
            $type = 'overdue';
            $label = 'Atrasada';
        } elseif ($due && $due == $today) {
            $type = 'today';
            $label = 'Vence hoy';
        } elseif ($due) {
            $type = 'soon';
            $label = 'Próxima';
        }
        $task['priority_display'] = $priorityLabels[$task['priority']] ?? $task['priority'];
        $task['due_date_display'] = $due ? $due->format('d/m/Y') : 'Sin fecha';
        $task['alert_type'] = $type;
        $task['alert_label'] = $label;
    }
    return $tasks;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
