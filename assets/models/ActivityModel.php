<?php
// assets/models/ActivityModel.php
class ActivityModel {
    private $conn;
    private $table = 'activity_logs';

    public function __construct() {
        // USAR LA FUNCIÓN GLOBAL PARA OBTENER LA CONEXIÓN
        $dbPath = __DIR__ . '/../../config/db.php';
        if (!file_exists($dbPath)) {
            throw new Exception("Archivo db.php no encontrado en: $dbPath");
        }
        
        // Incluir db.php
        require_once $dbPath;
        
        // Obtener conexión usando la función global
        $this->conn = getDBConnection();
        
        if (!$this->conn) {
            throw new Exception("No se pudo establecer conexión a la base de datos");
        }
    }

    // Registrar movimiento de tarea entre columnas
    public function logTaskMovement($user_id, $task_id, $task_title, $from_status, $to_status, $direction) {
        try {
            $action = $this->getMovementAction($from_status, $to_status, $direction);
            $details = json_encode([
                'task_id' => $task_id,
                'task_title' => $task_title,
                'from_status' => $from_status,
                'to_status' => $to_status,
                'direction' => $direction
            ], JSON_UNESCAPED_UNICODE);

            $query = "INSERT INTO {$this->table} (user_id, entity_type, entity_id, action, details) 
                     VALUES (:user_id, 'task', :entity_id, :action, :details)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':user_id' => $user_id,
                ':entity_id' => $task_id,
                ':action' => $action,
                ':details' => $details
            ]);

            if ($result) {
                error_log("Actividad registrada: $action para tarea $task_id");
                return true;
            } else {
                error_log("Error al ejecutar INSERT en actividad");
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error PDO registrando movimiento de tarea: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general registrando movimiento: " . $e->getMessage());
            return false;
        }
    }

    // Generar texto descriptivo para el movimiento
    private function getMovementAction($from_status, $to_status, $direction) {
        $statusMap = [
            'Pendiente' => 'pendiente',
            'En proceso' => 'en proceso',
            'Completado' => 'completado',
            'Completada' => 'completada'
        ];

        $from = $statusMap[$from_status] ?? strtolower($from_status);
        $to = $statusMap[$to_status] ?? strtolower($to_status);

        return "Movió tarea de {$from} a {$to}";
    }

    //  Obtener actividad reciente del usuario
    public function getUserActivity($user_id, $limit = 10) {
        try {
            $query = "SELECT al.*, t.title as task_title, t.status as task_status, 
                             t.due_date as task_due_date, t.priority as task_priority,
                             b.title as board_title
                      FROM {$this->table} al
                      LEFT JOIN tasks t ON al.entity_id = t.id AND al.entity_type = 'task'
                      LEFT JOIN boards b ON t.board_id = b.id
                      WHERE al.user_id = :user_id 
                      ORDER BY al.created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo actividad del usuario: " . $e->getMessage());
            return [];
        }
    }

    // Eliminar todas las actividades relacionadas con una tarea
    public function deleteTaskActivities($task_id) {
        try {
            $query = "DELETE FROM {$this->table} WHERE entity_type = 'task' AND entity_id = :task_id";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':task_id' => $task_id
            ]);

            if ($result) {
                $deletedRows = $stmt->rowCount();
                error_log("Actividades eliminadas: {$deletedRows} actividades de la tarea {$task_id}");
                return $deletedRows;
            } else {
                error_log("Error al eliminar actividades de la tarea {$task_id}");
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error PDO eliminando actividades: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general eliminando actividades: " . $e->getMessage());
            return false;
        }
    }
}
?>