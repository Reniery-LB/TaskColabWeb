<?php

class ChatModel {
    private PDO $conn;

    public function __construct() {
        require_once __DIR__ . '/../../config/db.php';
        $this->conn = getDBConnection();

        if (!$this->conn) {
            throw new Exception('No se pudo establecer conexión a la base de datos');
        }

        $this->ensureSchema();
    }

    private function ensureSchema(): void {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS conversations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type ENUM('direct','group','project','task') NOT NULL,
                title VARCHAR(180) NULL,
                project_id INT NULL,
                task_id INT NULL,
                created_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_message_at DATETIME NULL,
                is_active TINYINT(1) DEFAULT 1,
                UNIQUE KEY uq_project_conversation (type, project_id),
                UNIQUE KEY uq_task_conversation (type, task_id),
                INDEX idx_conversations_type (type),
                INDEX idx_conversations_updated (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS conversation_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                conversation_id INT NOT NULL,
                user_id INT NOT NULL,
                role_in_conversation VARCHAR(32) DEFAULT 'member',
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_read_message_id INT NULL,
                muted TINYINT(1) DEFAULT 0,
                deleted_at DATETIME NULL,
                UNIQUE KEY uq_conversation_user (conversation_id, user_id),
                INDEX idx_conversation_members_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        if (!$this->columnExists('conversation_members', 'deleted_at')) {
            $this->conn->exec("ALTER TABLE conversation_members ADD COLUMN deleted_at DATETIME NULL AFTER muted");
        }

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                conversation_id INT NOT NULL,
                user_id INT NULL,
                body TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                INDEX idx_messages_conversation (conversation_id, id),
                INDEX idx_messages_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS message_reads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message_id INT NOT NULL,
                user_id INT NOT NULL,
                read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_message_user_read (message_id, user_id),
                INDEX idx_message_reads_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS message_attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                url TEXT NOT NULL,
                uploaded_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_message_attachments_message (message_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function listConversations(int $userId): array {
        $this->ensureProjectConversationsForUser($userId);

        $stmt = $this->conn->prepare("
            SELECT
                c.id,
                c.type,
                c.title,
                c.project_id,
                c.task_id,
                c.created_by,
                c.created_at,
                c.updated_at,
                c.last_message_at,
                cm.role_in_conversation,
                cm.last_read_message_id,
                active_user.is_admin AS current_user_is_admin,
                p.name AS project_name,
                t.title AS task_title,
                (
                    SELECT m.body
                    FROM messages m
                    WHERE m.conversation_id = c.id
                      AND m.deleted_at IS NULL
                    ORDER BY m.id DESC
                    LIMIT 1
                ) AS last_message_body,
                (
                    SELECT MAX(m.id)
                    FROM messages m
                    WHERE m.conversation_id = c.id
                      AND m.deleted_at IS NULL
                ) AS last_message_id,
                (
                    SELECT COUNT(*)
                    FROM messages unread
                    WHERE unread.conversation_id = c.id
                      AND unread.deleted_at IS NULL
                      AND unread.user_id <> :unread_user_id
                      AND unread.id > COALESCE(cm.last_read_message_id, 0)
                ) AS unread_count
            FROM conversations c
            INNER JOIN conversation_members cm
                ON cm.conversation_id = c.id
               AND cm.user_id = :member_user_id
               AND cm.deleted_at IS NULL
            INNER JOIN users active_user ON active_user.id = :active_user_id
            LEFT JOIN projects p ON p.id = c.project_id
            LEFT JOIN tasks t ON t.id = c.task_id
            WHERE c.is_active = 1
            ORDER BY COALESCE(c.last_message_at, c.updated_at, c.created_at) DESC, c.id DESC
        ");
        $stmt->execute([
            ':unread_user_id' => $userId,
            ':member_user_id' => $userId,
            ':active_user_id' => $userId
        ]);

        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($conversations as &$conversation) {
            $conversation['id'] = (int)$conversation['id'];
            $conversation['project_id'] = $conversation['project_id'] !== null ? (int)$conversation['project_id'] : null;
            $conversation['task_id'] = $conversation['task_id'] !== null ? (int)$conversation['task_id'] : null;
            $conversation['last_message_id'] = $conversation['last_message_id'] !== null ? (int)$conversation['last_message_id'] : null;
            $conversation['unread_count'] = (int)$conversation['unread_count'];
            $conversation['title'] = $this->conversationTitle($conversation, $userId);
            $conversation['can_delete'] = $conversation['type'] === 'direct'
                || ($conversation['type'] === 'project' && (int)$conversation['current_user_is_admin'] === 1);
            unset($conversation['current_user_is_admin']);
        }

        return $conversations;
    }

    public function getMessages(int $conversationId, int $userId, int $afterId = 0): array {
        $this->requireConversationMember($conversationId, $userId);

        $stmt = $this->conn->prepare("
            SELECT
                m.id,
                m.conversation_id,
                m.user_id,
                COALESCE(u.name, 'Usuario eliminado') AS user_name,
                u.avatar_url,
                m.body,
                m.created_at,
                m.updated_at
            FROM messages m
            LEFT JOIN users u ON u.id = m.user_id
            WHERE m.conversation_id = :conversation_id
              AND m.deleted_at IS NULL
              AND m.id > :after_id
            ORDER BY m.id ASC
            LIMIT 150
        ");
        $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
        $stmt->execute();

        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($messages as &$message) {
            $message['id'] = (int)$message['id'];
            $message['conversation_id'] = (int)$message['conversation_id'];
            $message['user_id'] = $message['user_id'] !== null ? (int)$message['user_id'] : null;
            $message['is_mine'] = (int)$message['user_id'] === $userId;
        }

        if (!empty($messages)) {
            $lastId = (int)end($messages)['id'];
            $this->markRead($conversationId, $userId, $lastId);
        }

        return $messages;
    }

    public function sendMessage(int $conversationId, int $userId, string $body): array {
        $this->requireConversationMember($conversationId, $userId);

        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException('El mensaje no puede estar vacío');
        }

        if (mb_strlen($body, 'UTF-8') > 4000) {
            throw new InvalidArgumentException('El mensaje no puede superar 4000 caracteres');
        }

        $this->conn->beginTransaction();

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO messages (conversation_id, user_id, body, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$conversationId, $userId, $body]);
            $messageId = (int)$this->conn->lastInsertId();

            $update = $this->conn->prepare("
                UPDATE conversations
                SET last_message_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([$conversationId]);

            $restoreMembers = $this->conn->prepare("
                UPDATE conversation_members
                SET deleted_at = NULL
                WHERE conversation_id = ?
            ");
            $restoreMembers->execute([$conversationId]);

            $this->markRead($conversationId, $userId, $messageId);
            $this->conn->commit();

            $messages = $this->getMessages($conversationId, $userId, $messageId - 1);
            return $messages[0] ?? [];
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function createDirectConversation(int $userId, int $otherUserId): array {
        if ($userId === $otherUserId) {
            throw new InvalidArgumentException('Selecciona otro usuario para iniciar chat privado');
        }

        $other = $this->getActiveUser($otherUserId);
        if (!$other) {
            throw new InvalidArgumentException('Usuario no encontrado o inactivo');
        }

        $existing = $this->findDirectConversation($userId, $otherUserId);
        if ($existing) {
            return $existing;
        }

        $this->conn->beginTransaction();

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO conversations (type, created_by, created_at, updated_at)
                VALUES ('direct', ?, NOW(), NOW())
            ");
            $stmt->execute([$userId]);
            $conversationId = (int)$this->conn->lastInsertId();

            $memberStmt = $this->conn->prepare("
                INSERT INTO conversation_members (conversation_id, user_id, role_in_conversation)
                VALUES (?, ?, 'member')
            ");
            $memberStmt->execute([$conversationId, $userId]);
            $memberStmt->execute([$conversationId, $otherUserId]);

            $this->conn->commit();
            return $this->getConversation($conversationId, $userId);
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function createProjectConversation(int $projectId, int $userId): array {
        if (!$this->userCanAccessProject($projectId, $userId)) {
            throw new RuntimeException('No tienes acceso a este proyecto');
        }

        $conversationId = $this->ensureProjectConversation($projectId, $userId);
        return $this->getConversation($conversationId, $userId);
    }

    public function deleteConversation(int $conversationId, int $userId): array {
        $conversation = $this->getConversationRecord($conversationId);
        if (!$conversation) {
            throw new RuntimeException('Conversación no encontrada');
        }

        if ($conversation['type'] === 'direct') {
            $this->requireConversationMember($conversationId, $userId);

            $stmt = $this->conn->prepare("
                UPDATE conversation_members
                SET deleted_at = NOW()
                WHERE conversation_id = ?
                  AND user_id = ?
            ");
            $stmt->execute([$conversationId, $userId]);

            $activeMembers = $this->conn->prepare("
                SELECT COUNT(*)
                FROM conversation_members
                WHERE conversation_id = ?
                  AND deleted_at IS NULL
            ");
            $activeMembers->execute([$conversationId]);

            if ((int)$activeMembers->fetchColumn() === 0) {
                $close = $this->conn->prepare("UPDATE conversations SET is_active = 0, updated_at = NOW() WHERE id = ?");
                $close->execute([$conversationId]);
            }

            return ['deleted' => true, 'scope' => 'member'];
        }

        if ($conversation['type'] === 'project') {
            if (!$this->isAdmin($userId)) {
                throw new RuntimeException('Solo un administrador puede borrar chats de proyecto');
            }

            $stmt = $this->conn->prepare("UPDATE conversations SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$conversationId]);

            return ['deleted' => true, 'scope' => 'conversation'];
        }

        throw new RuntimeException('Este tipo de conversación todavía no se puede eliminar');
    }

    private function getConversation(int $conversationId, int $userId): array {
        $conversations = $this->listConversations($userId);
        foreach ($conversations as $conversation) {
            if ((int)$conversation['id'] === $conversationId) {
                return $conversation;
            }
        }

        throw new RuntimeException('Conversación no encontrada');
    }

    private function findDirectConversation(int $userId, int $otherUserId): ?array {
        $stmt = $this->conn->prepare("
            SELECT c.id
            FROM conversations c
            INNER JOIN conversation_members cm1 ON cm1.conversation_id = c.id AND cm1.user_id = :user_id
            INNER JOIN conversation_members cm2 ON cm2.conversation_id = c.id AND cm2.user_id = :other_user_id
            WHERE c.type = 'direct'
              AND c.is_active = 1
              AND (
                SELECT COUNT(*)
                FROM conversation_members cm_count
                WHERE cm_count.conversation_id = c.id
              ) = 2
            LIMIT 1
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':other_user_id' => $otherUserId
        ]);

        $conversationId = $stmt->fetchColumn();
        if (!$conversationId) {
            return null;
        }

        $restore = $this->conn->prepare("
            UPDATE conversation_members
            SET deleted_at = NULL
            WHERE conversation_id = ?
              AND user_id = ?
        ");
        $restore->execute([(int)$conversationId, $userId]);

        return $this->getConversation((int)$conversationId, $userId);
    }

    private function ensureProjectConversationsForUser(int $userId): void {
        $isAdmin = $this->isAdmin($userId);

        if ($isAdmin) {
            $stmt = $this->conn->query("
                SELECT id
                FROM projects
                WHERE status <> 'archived'
                ORDER BY updated_at DESC
            ");
        } else {
            $stmt = $this->conn->prepare("
                SELECT DISTINCT p.id
                FROM projects p
                INNER JOIN project_members pm ON pm.project_id = p.id
                WHERE pm.user_id = ?
                  AND p.status <> 'archived'
                ORDER BY p.updated_at DESC
            ");
            $stmt->execute([$userId]);
        }

        $projectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($projectIds as $projectId) {
            $this->ensureProjectConversation((int)$projectId, $userId);
        }
    }

    private function ensureProjectConversation(int $projectId, int $userId): int {
        $stmt = $this->conn->prepare("
            SELECT id, is_active
            FROM conversations
            WHERE type = 'project'
              AND project_id = ?
            LIMIT 1
        ");
        $stmt->execute([$projectId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        $conversationId = $existing['id'] ?? null;

        if (!$conversationId) {
            $project = $this->getProject($projectId);
            if (!$project) {
                throw new RuntimeException('Proyecto no encontrado');
            }

            $insert = $this->conn->prepare("
                INSERT INTO conversations (type, title, project_id, created_by, created_at, updated_at)
                VALUES ('project', ?, ?, ?, NOW(), NOW())
            ");
            $insert->execute(['General - ' . $project['name'], $projectId, $userId]);
            $conversationId = (int)$this->conn->lastInsertId();
        }

        if ($existing && (int)$existing['is_active'] !== 1) {
            return (int)$conversationId;
        }

        $this->syncProjectMembers((int)$conversationId, $projectId, $userId);
        return (int)$conversationId;
    }

    private function syncProjectMembers(int $conversationId, int $projectId, int $currentUserId): void {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO conversation_members (conversation_id, user_id, role_in_conversation)
            SELECT
                :conversation_id,
                u.id,
                CASE
                    WHEN p.owner_id = u.id THEN 'owner'
                    WHEN pm.role_in_project IN ('owner', 'admin') THEN 'admin'
                    ELSE 'member'
                END
            FROM projects p
            INNER JOIN project_members pm ON pm.project_id = p.id
            INNER JOIN users u ON u.id = pm.user_id AND u.is_active = 1
            WHERE p.id = :project_id
        ");
        $stmt->execute([
            ':conversation_id' => $conversationId,
            ':project_id' => $projectId
        ]);

        $project = $this->getProject($projectId);
        if ($project && !empty($project['owner_id'])) {
            $ownerStmt = $this->conn->prepare("
                INSERT IGNORE INTO conversation_members (conversation_id, user_id, role_in_conversation)
                SELECT ?, id, 'owner'
                FROM users
                WHERE id = ? AND is_active = 1
            ");
            $ownerStmt->execute([$conversationId, (int)$project['owner_id']]);
        }

        if ($this->isAdmin($currentUserId)) {
            $adminStmt = $this->conn->prepare("
                INSERT IGNORE INTO conversation_members (conversation_id, user_id, role_in_conversation)
                VALUES (?, ?, 'admin')
            ");
            $adminStmt->execute([$conversationId, $currentUserId]);
        }
    }

    private function conversationTitle(array $conversation, int $userId): string {
        if ($conversation['type'] === 'direct') {
            $stmt = $this->conn->prepare("
                SELECT u.name
                FROM conversation_members cm
                INNER JOIN users u ON u.id = cm.user_id
                WHERE cm.conversation_id = ?
                  AND cm.user_id <> ?
                ORDER BY u.name ASC
                LIMIT 1
            ");
            $stmt->execute([(int)$conversation['id'], $userId]);
            return (string)($stmt->fetchColumn() ?: 'Chat privado');
        }

        if ($conversation['type'] === 'project') {
            return $conversation['title'] ?: ('General - ' . ($conversation['project_name'] ?: 'Proyecto'));
        }

        if ($conversation['type'] === 'task') {
            return $conversation['title'] ?: ('Tarea - ' . ($conversation['task_title'] ?: 'Sin título'));
        }

        return $conversation['title'] ?: 'Chat grupal';
    }

    private function markRead(int $conversationId, int $userId, int $messageId): void {
        if ($messageId <= 0) {
            return;
        }

        $stmt = $this->conn->prepare("
            UPDATE conversation_members
            SET last_read_message_id = GREATEST(COALESCE(last_read_message_id, 0), ?)
            WHERE conversation_id = ?
              AND user_id = ?
        ");
        $stmt->execute([$messageId, $conversationId, $userId]);

        $reads = $this->conn->prepare("
            INSERT IGNORE INTO message_reads (message_id, user_id, read_at)
            SELECT id, ?, NOW()
            FROM messages
            WHERE conversation_id = ?
              AND id <= ?
              AND deleted_at IS NULL
              AND (user_id IS NULL OR user_id <> ?)
        ");
        $reads->execute([$userId, $conversationId, $messageId, $userId]);
    }

    private function requireConversationMember(int $conversationId, int $userId): void {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM conversation_members cm
            INNER JOIN conversations c ON c.id = cm.conversation_id
            WHERE cm.conversation_id = ?
              AND cm.user_id = ?
              AND cm.deleted_at IS NULL
              AND c.is_active = 1
        ");
        $stmt->execute([$conversationId, $userId]);

        if ((int)$stmt->fetchColumn() === 0) {
            throw new RuntimeException('No tienes acceso a esta conversación');
        }
    }

    private function userCanAccessProject(int $projectId, int $userId): bool {
        if ($this->isAdmin($userId)) {
            return true;
        }

        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM project_members pm
            INNER JOIN projects p ON p.id = pm.project_id
            WHERE pm.project_id = ?
              AND pm.user_id = ?
              AND p.status <> 'archived'
        ");
        $stmt->execute([$projectId, $userId]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function isAdmin(int $userId): bool {
        $stmt = $this->conn->prepare("SELECT is_admin FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn() === 1;
    }

    private function getProject(int $projectId): ?array {
        $stmt = $this->conn->prepare("SELECT id, name, owner_id FROM projects WHERE id = ? LIMIT 1");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        return $project ?: null;
    }

    private function getActiveUser(int $userId): ?array {
        $stmt = $this->conn->prepare("SELECT id, name, email FROM users WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    private function getConversationRecord(int $conversationId): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM conversations WHERE id = ? LIMIT 1");
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        return $conversation ?: null;
    }

    private function columnExists(string $table, string $column): bool {
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
}
