<?php
// assets/app/endpointsReportes/export_pdf.php
ob_start();
session_start();
if(empty($_SESSION['user']['id'])) die('No auth');

$dompdf_path = __DIR__ . '/../../../vendor/dompdf/autoload.inc.php';
$db_path = __DIR__ . '/../../../config/db.php';
$model_path = __DIR__ . '/../../models/TaskModel.php';

if(!file_exists($dompdf_path)) die('No DOMPDF');
if(!file_exists($db_path)) die('No DB');
if(!file_exists($model_path)) die('No Model');

require_once $dompdf_path;
require_once $db_path;
require_once $model_path;

use Dompdf\Dompdf;
use Dompdf\Options;

// ========== A PARTIR DE AQUÍ TODO FUNCIONA ==========

$taskModel = new TaskModel();
$user_id = $_SESSION['user']['id'];

// Obtener datos
$totalTasks = $taskModel->getUserTasksCount($user_id);
$pendingTasks = $taskModel->getUserTasksCountByStatus($user_id, 'pendiente');
$inProgressTasks = $taskModel->getUserTasksCountByStatus($user_id, 'en_proceso');
$completedTasks = $taskModel->getUserTasksCountByStatus($user_id, 'completado');
$userName = $_SESSION['user']['name'] ?? 'Usuario';
$userEmail = $_SESSION['user']['email'] ?? '';

// Obtener progreso y tareas atrasadas
$boardProgress = $taskModel->getBoardProgress($user_id);
$overdueTasks = $taskModel->getOverdueTasks($user_id);
$activeUsers = $taskModel->getActiveUsers($user_id);

$fecha = date('d/m/Y H:i:s');
$pdfFileName = 'reporte_tareas_' . date('Y-m-d_His') . '.pdf';

// HTML del PDF (versión simplificada para prueba)
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 20mm; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; color: #333; font-size: 12px; }
        h1 { color: #667eea; text-align: center; }
        .header { background: #667eea; color: white; padding: 15px; margin-bottom: 15px; }
        .stats { display: flex; gap: 10px; margin: 20px 0; }
        .stat { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; flex: 1; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #667eea; color: white; padding: 8px; }
        td { padding: 6px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Tareas - TaskColab</h1>
        <p>Generado: ' . $fecha . '</p>
    </div>
    
    <p><strong>Usuario:</strong> ' . $userName . '</p>
    <p><strong>Email:</strong> ' . $userEmail . '</p>
    
    <div class="stats">
        <div class="stat"><h3>' . $totalTasks . '</h3><p>Total Tareas</p></div>
        <div class="stat"><h3>' . $pendingTasks . '</h3><p>Pendientes</p></div>
        <div class="stat"><h3>' . $inProgressTasks . '</h3><p>En Proceso</p></div>
        <div class="stat"><h3>' . $completedTasks . '</h3><p>Completadas</p></div>
    </div>';

// Progreso por estado
if (!empty($boardProgress)) {
    $html .= '<h2>Progreso por Estado</h2><table>';
    foreach ($boardProgress as $progress) {
        $percentage = round($progress['percentage'], 1);
        $html .= '<tr>
            <td>' . $progress['status_display'] . '</td>
            <td>' . $progress['total_tasks'] . ' tareas</td>
            <td>' . $percentage . '%</td>
        </tr>';
    }
    $html .= '</table>';
}

// Tareas atrasadas
if (!empty($overdueTasks)) {
    $html .= '<h2>Tareas Atrasadas</h2><table>';
    foreach ($overdueTasks as $task) {
        $dueDate = date('d/m/Y', strtotime($task['due_date']));
        $html .= '<tr>
            <td><strong>' . $task['title'] . '</strong></td>
            <td>' . substr($task['description'] ?? 'Sin descripción', 0, 50) . '</td>
            <td style="color: red;">' . $dueDate . '</td>
        </tr>';
    }
    $html .= '</table>';
}

$html .= '
    <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 10px;">
        <p>Generado por TaskColab - ' . date('Y') . '</p>
    </div>
</body>
</html>';

// Limpiar buffer y generar PDF
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