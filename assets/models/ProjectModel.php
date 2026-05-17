<?php

class ProjectModel {
    private $conn;

    public function __construct() {
        require_once __DIR__ . '/../../config/db.php';
        $this->conn = getDBConnection();

        if (!$this->conn) {
            throw new Exception('No se pudo establecer conexión a la base de datos');
        }

        $this->ensureSchema();
    }

    private function ensureSchema() {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(180) NOT NULL,
                description TEXT NULL,
                owner_id INT NULL,
                status ENUM('active','paused','archived') NOT NULL DEFAULT 'active',
                color VARCHAR(7) DEFAULT '#1B5CFF',
                start_date DATE NULL,
                due_date DATE NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_projects_owner (owner_id),
                INDEX idx_projects_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS project_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT NOT NULL,
                user_id INT NOT NULL,
                role_in_project VARCHAR(32) DEFAULT 'member',
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_project_user (project_id, user_id),
                INDEX idx_project_members_project (project_id),
                INDEX idx_project_members_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        if (!$this->columnExists('boards', 'project_id')) {
            $this->conn->exec("ALTER TABLE boards ADD COLUMN project_id INT NULL AFTER id");
        }

        if (!$this->indexExists('boards', 'idx_boards_project_id')) {
            $this->conn->exec("ALTER TABLE boards ADD INDEX idx_boards_project_id (project_id)");
        }
    }

    private function columnExists($table, $column) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function indexExists($table, $index) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getOrCreateDefaultProject($userId) {
        $stmt = $this->conn->prepare("
            SELECT p.id, p.name, p.description, p.owner_id, p.status, p.color, p.due_date, b.id AS board_id
            FROM projects p
            INNER JOIN project_members pm ON pm.project_id = p.id
            LEFT JOIN boards b ON b.project_id = p.id
            WHERE pm.user_id = ?
              AND p.status <> 'archived'
            ORDER BY p.created_at ASC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($project) {
            return $project;
        }

        return $this->createProject([
            'name' => 'Proyecto general',
            'description' => 'Espacio inicial para organizar tareas y tablero.',
            'color' => '#1B5CFF',
            'status' => 'active',
            'due_date' => null
        ], $userId, true);
    }

    public function listProjects($userId) {
        $this->getOrCreateDefaultProject($userId);

        $stmt = $this->conn->prepare("
            SELECT 
                p.id,
                p.name,
                p.description,
                p.owner_id,
                p.status,
                p.color,
                p.due_date,
                p.created_at,
                p.updated_at,
                COALESCE(b.id, 0) AS board_id,
                COUNT(DISTINCT t.id) AS total_tasks,
                SUM(CASE WHEN t.status = 'pending' AND t.is_active = 1 THEN 1 ELSE 0 END) AS pending_tasks,
                SUM(CASE WHEN t.status = 'in_progress' AND t.is_active = 1 THEN 1 ELSE 0 END) AS in_progress_tasks,
                SUM(CASE WHEN t.status = 'done' AND t.is_active = 1 THEN 1 ELSE 0 END) AS done_tasks,
                COUNT(DISTINCT pm_all.user_id) AS members_count
            FROM projects p
            INNER JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = :user_id
            LEFT JOIN boards b ON b.project_id = p.id
            LEFT JOIN tasks t ON t.board_id = b.id AND t.is_active = 1
            LEFT JOIN project_members pm_all ON pm_all.project_id = p.id
            WHERE p.status <> 'archived'
            GROUP BY p.id, p.name, p.description, p.owner_id, p.status, p.color, p.due_date, p.created_at, p.updated_at, b.id
            ORDER BY p.updated_at DESC, p.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createProject(array $data, $userId, $reuseExistingBoard = false) {
        $this->conn->beginTransaction();

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO projects (name, description, owner_id, status, color, due_date)
                VALUES (:name, :description, :owner_id, :status, :color, :due_date)
            ");
            $stmt->execute([
                ':name' => trim($data['name']),
                ':description' => trim($data['description'] ?? ''),
                ':owner_id' => $userId,
                ':status' => $data['status'] ?? 'active',
                ':color' => $data['color'] ?? '#1B5CFF',
                ':due_date' => !empty($data['due_date']) ? $data['due_date'] : null
            ]);

            $projectId = (int)$this->conn->lastInsertId();
            $boardId = null;

            if ($reuseExistingBoard) {
                $boardStmt = $this->conn->prepare("
                    SELECT id FROM boards 
                    WHERE (owner_id = ? OR owner_id IS NULL)
                    ORDER BY id ASC
                    LIMIT 1
                ");
                $boardStmt->execute([$userId]);
                $boardId = $boardStmt->fetchColumn();
            }

            if ($boardId) {
                $updateBoard = $this->conn->prepare("UPDATE boards SET project_id = ?, updated_at = NOW() WHERE id = ?");
                $updateBoard->execute([$projectId, $boardId]);
            } else {
                $boardStmt = $this->conn->prepare("
                    INSERT INTO boards (project_id, owner_id, title, description, visibility, color)
                    VALUES (:project_id, :owner_id, :title, :description, 'private', :color)
                ");
                $boardStmt->execute([
                    ':project_id' => $projectId,
                    ':owner_id' => $userId,
                    ':title' => trim($data['name']),
                    ':description' => trim($data['description'] ?? ''),
                    ':color' => $data['color'] ?? '#1B5CFF'
                ]);
                $boardId = (int)$this->conn->lastInsertId();
            }

            $memberStmt = $this->conn->prepare("
                INSERT IGNORE INTO project_members (project_id, user_id, role_in_project)
                VALUES (?, ?, 'owner')
            ");
            $memberStmt->execute([$projectId, $userId]);

            $boardMemberStmt = $this->conn->prepare("
                INSERT IGNORE INTO board_members (board_id, user_id, invited_by, role_in_board)
                VALUES (?, ?, ?, 'owner')
            ");
            $boardMemberStmt->execute([$boardId, $userId, $userId]);

            $this->conn->commit();

            return [
                'id' => $projectId,
                'name' => trim($data['name']),
                'description' => trim($data['description'] ?? ''),
                'owner_id' => $userId,
                'status' => $data['status'] ?? 'active',
                'color' => $data['color'] ?? '#1B5CFF',
                'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
                'board_id' => (int)$boardId,
                'total_tasks' => 0,
                'pending_tasks' => 0,
                'in_progress_tasks' => 0,
                'done_tasks' => 0,
                'members_count' => 1
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getProjectForUser($projectId, $userId) {
        $stmt = $this->conn->prepare("
            SELECT p.*, b.id AS board_id, pm.role_in_project
            FROM projects p
            INNER JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = :user_id
            LEFT JOIN boards b ON b.project_id = p.id
            WHERE p.id = :project_id
              AND p.status <> 'archived'
            LIMIT 1
        ");
        $stmt->execute([
            ':project_id' => $projectId,
            ':user_id' => $userId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function userCanManageProject($projectId, $userId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM projects p
            LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = :member_user_id
            WHERE p.id = :project_id
              AND p.status <> 'archived'
              AND (
                p.owner_id = :owner_user_id
                OR pm.role_in_project IN ('owner', 'admin')
                OR EXISTS (
                    SELECT 1
                    FROM users admin_user
                    WHERE admin_user.id = :admin_user_id
                      AND admin_user.is_admin = 1
                      AND admin_user.is_active = 1
                )
              )
        ");
        $stmt->execute([
            ':project_id' => $projectId,
            ':member_user_id' => $userId,
            ':owner_user_id' => $userId,
            ':admin_user_id' => $userId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function updateProject($projectId, array $data, $userId) {
        if (!$this->userCanManageProject($projectId, $userId)) {
            throw new Exception('No tienes permisos para editar este proyecto');
        }

        $fields = [];
        $params = [
            ':project_id' => $projectId
        ];

        if (array_key_exists('name', $data)) {
            $name = trim($data['name']);
            if ($name === '') {
                throw new Exception('El nombre del proyecto es obligatorio');
            }
            $fields[] = 'name = :name';
            $params[':name'] = $name;
        }

        if (array_key_exists('description', $data)) {
            $fields[] = 'description = :description';
            $params[':description'] = trim($data['description'] ?? '');
        }

        if (array_key_exists('status', $data)) {
            $status = $data['status'];
            if (!in_array($status, ['active', 'paused'], true)) {
                throw new Exception('Estado de proyecto inválido');
            }
            $fields[] = 'status = :status';
            $params[':status'] = $status;
        }

        if (array_key_exists('color', $data)) {
            $color = trim($data['color'] ?? '#1B5CFF');
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $color = '#1B5CFF';
            }
            $fields[] = 'color = :color';
            $params[':color'] = $color;
        }

        if (array_key_exists('due_date', $data)) {
            $dueDate = trim($data['due_date'] ?? '');
            if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
                throw new Exception('Fecha objetivo inválida');
            }
            $fields[] = 'due_date = :due_date';
            $params[':due_date'] = $dueDate ?: null;
        }

        if (!$fields) {
            throw new Exception('No hay campos para actualizar');
        }

        $fields[] = 'updated_at = NOW()';

        $this->conn->beginTransaction();

        try {
            $stmt = $this->conn->prepare("
                UPDATE projects
                SET " . implode(', ', $fields) . "
                WHERE id = :project_id
            ");
            $stmt->execute($params);

            if (isset($params[':name']) || isset($params[':description']) || isset($params[':color'])) {
                $boardFields = [];
                $boardParams = [':project_id' => $projectId];

                if (isset($params[':name'])) {
                    $boardFields[] = 'title = :title';
                    $boardParams[':title'] = $params[':name'];
                }
                if (isset($params[':description'])) {
                    $boardFields[] = 'description = :description';
                    $boardParams[':description'] = $params[':description'];
                }
                if (isset($params[':color'])) {
                    $boardFields[] = 'color = :color';
                    $boardParams[':color'] = $params[':color'];
                }

                if ($boardFields) {
                    $boardFields[] = 'updated_at = NOW()';
                    $boardStmt = $this->conn->prepare("
                        UPDATE boards
                        SET " . implode(', ', $boardFields) . "
                        WHERE project_id = :project_id
                    ");
                    $boardStmt->execute($boardParams);
                }
            }

            $this->conn->commit();

            return $this->getProjectForUser($projectId, $userId);
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function archiveProject($projectId, $userId) {
        if (!$this->userCanManageProject($projectId, $userId)) {
            throw new Exception('No tienes permisos para archivar este proyecto');
        }

        $stmt = $this->conn->prepare("
            UPDATE projects
            SET status = 'archived', updated_at = NOW()
            WHERE id = :project_id
        ");
        $stmt->execute([':project_id' => $projectId]);

        return $stmt->rowCount() > 0;
    }

    public function userCanAccessBoard($userId, $boardId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM boards b
            LEFT JOIN project_members pm ON pm.project_id = b.project_id AND pm.user_id = :project_member_user_id
            LEFT JOIN board_members bm ON bm.board_id = b.id AND bm.user_id = :board_member_user_id
            WHERE b.id = :board_id
              AND (
                b.owner_id = :owner_user_id
                OR pm.user_id IS NOT NULL
                OR bm.user_id IS NOT NULL
                OR EXISTS (
                    SELECT 1
                    FROM users admin_user
                    WHERE admin_user.id = :admin_user_id
                      AND admin_user.is_admin = 1
                      AND admin_user.is_active = 1
                )
              )
        ");
        $stmt->execute([
            ':project_member_user_id' => $userId,
            ':board_member_user_id' => $userId,
            ':board_id' => $boardId,
            ':owner_user_id' => $userId,
            ':admin_user_id' => $userId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }
}
