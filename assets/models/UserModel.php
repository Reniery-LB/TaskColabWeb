<?php
// assets/models/UserModel.php
require_once __DIR__ . '/../../config/db.php';

class UserModel {
    private $pdo;
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT id, name, email, notes, is_admin, avatar_url, is_active, last_login FROM users ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Devuelve usuarios con la cantidad de tareas asignadas (LEFT JOIN con task_assignments/tasks)
    public function getAllWithTaskCount() {
        $sql = "
            SELECT u.id, u.name, u.email, u.notes, u.is_admin, u.avatar_url, u.is_active, u.last_login,
                COALESCE(t.cnt, 0) AS tasks_assigned
            FROM users u
            LEFT JOIN (
                SELECT ta.user_id, COUNT(*) AS cnt
                FROM task_assignments ta
                JOIN tasks t ON t.id = ta.task_id AND t.is_active = 1  
                GROUP BY ta.user_id
            ) t ON t.user_id = u.id
            ORDER BY u.name ASC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($name, $email, $password_hash, $is_admin = 0) {
        $sql = "INSERT INTO users (name, email, password_hash, is_admin, created_at, is_active) VALUES (?, ?, ?, ?, NOW(), 1)";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([$name, $email, $password_hash, $is_admin]);
        if ($ok) return $this->pdo->lastInsertId();
        return false;
    }

    public function update($id, $data) {
        // $data = ['name'=>..., 'email'=>..., 'notes'=>..., 'is_active'=>0/1, 'avatar_url'=>...]
        $fields = [];
        $params = [];

        foreach (['name','email','notes','is_active','avatar_url'] as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $params[] = $data[$k];
            }
        }

        if (empty($fields)) return false;

        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function setLastLogin($id) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function setAvatar($id, $avatarUrl) {
        $stmt = $this->pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        return $stmt->execute([$avatarUrl, $id]);
    }

    public function countAssignedTasks($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM task_assignments ta
            JOIN tasks t ON t.id = ta.task_id AND t.is_active = 1
            WHERE ta.user_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
}
