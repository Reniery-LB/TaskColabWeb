<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../models/ChatModel.php';

api_require_method(['GET', 'POST']);

$user = api_current_user();
$userId = (int)$user['id'];
$model = new ChatModel();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $conversationId = (int)($_GET['conversation_id'] ?? 0);
        $afterId = max(0, (int)($_GET['after_id'] ?? 0));
        if ($conversationId <= 0) {
            api_json(['ok' => false, 'message' => 'Conversación inválida'], 422);
        }

        $messages = $model->getMessages($conversationId, $userId, $afterId);
        api_json(['ok' => true, 'messages' => $messages, 'count' => count($messages)]);
    }

    $input = api_input();
    $conversationId = (int)($input['conversation_id'] ?? 0);
    $body = (string)($input['body'] ?? '');
    if ($conversationId <= 0) {
        api_json(['ok' => false, 'message' => 'Conversación inválida'], 422);
    }

    $message = $model->sendMessage($conversationId, $userId, $body);
    api_json(['ok' => true, 'message' => 'Mensaje enviado', 'data' => $message], 201);
} catch (Throwable $e) {
    $status = $e instanceof InvalidArgumentException ? 422 : 403;
    api_json(['ok' => false, 'message' => $e->getMessage()], $status);
}
?>
