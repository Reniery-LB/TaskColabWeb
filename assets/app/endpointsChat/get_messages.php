<?php
require_once __DIR__ . '/bootstrap.php';

chat_require_method(['GET']);

$conversationId = (int)($_GET['conversation_id'] ?? 0);
$afterId = (int)($_GET['after_id'] ?? 0);

if ($conversationId <= 0) {
    chat_json(['ok' => false, 'message' => 'Conversación inválida'], 422);
}

try {
    $model = new ChatModel();
    $messages = $model->getMessages($conversationId, chat_current_user_id(), max(0, $afterId));

    chat_json([
        'ok' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
} catch (RuntimeException $e) {
    chat_json(['ok' => false, 'message' => $e->getMessage()], 403);
} catch (Throwable $e) {
    error_log('Error en get_messages: ' . $e->getMessage());
    chat_json(['ok' => false, 'message' => 'Error al cargar mensajes'], 500);
}
