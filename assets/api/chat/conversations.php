<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../models/ChatModel.php';

api_require_method(['GET', 'POST', 'DELETE']);

$user = api_current_user();
$userId = (int)$user['id'];
$model = new ChatModel();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $conversations = $model->listConversations($userId);
        api_json(['ok' => true, 'conversations' => $conversations, 'count' => count($conversations)]);
    }

    $input = api_input();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type = (string)($input['type'] ?? 'direct');
        if ($type === 'project') {
            $conversation = $model->createProjectConversation((int)($input['project_id'] ?? 0), $userId);
        } else {
            $conversation = $model->createDirectConversation($userId, (int)($input['user_id'] ?? 0));
        }

        api_json(['ok' => true, 'conversation' => $conversation], 201);
    }

    $conversationId = (int)($input['conversation_id'] ?? $input['id'] ?? 0);
    if ($conversationId <= 0) {
        api_json(['ok' => false, 'message' => 'Conversación inválida'], 422);
    }

    api_json(['ok' => true, 'result' => $model->deleteConversation($conversationId, $userId)]);
} catch (Throwable $e) {
    $status = $e instanceof InvalidArgumentException ? 422 : 403;
    api_json(['ok' => false, 'message' => $e->getMessage()], $status);
}
?>
