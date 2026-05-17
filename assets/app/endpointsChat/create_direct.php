<?php
require_once __DIR__ . '/bootstrap.php';

chat_require_method(['POST']);

$input = chat_input();
$otherUserId = (int)($input['user_id'] ?? 0);

if ($otherUserId <= 0) {
    chat_json(['ok' => false, 'message' => 'Usuario inválido'], 422);
}

try {
    $model = new ChatModel();
    $conversation = $model->createDirectConversation(chat_current_user_id(), $otherUserId);

    chat_json([
        'ok' => true,
        'conversation' => $conversation
    ]);
} catch (InvalidArgumentException $e) {
    chat_json(['ok' => false, 'message' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('Error en create_direct: ' . $e->getMessage());
    chat_json(['ok' => false, 'message' => 'Error al crear chat privado'], 500);
}
