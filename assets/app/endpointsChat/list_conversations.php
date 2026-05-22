<?php
require_once __DIR__ . '/bootstrap.php';

chat_require_method(['GET']);

try {
    $model = new ChatModel();
    $conversations = $model->listConversations(chat_current_user_id());

    chat_json([
        'ok' => true,
        'conversations' => $conversations,
        'count' => count($conversations)
    ]);
} catch (Throwable $e) {
    error_log('Error en list_conversations: ' . $e->getMessage());
    chat_json(['ok' => false, 'message' => 'Error al cargar conversaciones'], 500);
}
