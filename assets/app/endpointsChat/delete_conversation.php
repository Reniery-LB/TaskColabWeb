<?php
require_once __DIR__ . '/bootstrap.php';

chat_require_method(['POST']);

$input = chat_input();
$conversationId = (int)($input['conversation_id'] ?? 0);

if ($conversationId <= 0) {
    chat_json(['ok' => false, 'message' => 'Conversación inválida'], 422);
}

try {
    $model = new ChatModel();
    $result = $model->deleteConversation($conversationId, chat_current_user_id());

    chat_json([
        'ok' => true,
        'message' => 'Chat eliminado',
        'result' => $result
    ]);
} catch (RuntimeException $e) {
    chat_json(['ok' => false, 'message' => $e->getMessage()], 403);
} catch (Throwable $e) {
    error_log('Error en delete_conversation: ' . $e->getMessage());
    chat_json(['ok' => false, 'message' => 'Error al eliminar chat'], 500);
}
