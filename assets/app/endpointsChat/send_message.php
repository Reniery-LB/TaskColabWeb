<?php
require_once __DIR__ . '/bootstrap.php';

chat_require_method(['POST']);

$input = chat_input();
$conversationId = (int)($input['conversation_id'] ?? 0);
$body = (string)($input['body'] ?? '');

if ($conversationId <= 0) {
    chat_json(['ok' => false, 'message' => 'Conversación inválida'], 422);
}

try {
    $model = new ChatModel();
    $message = $model->sendMessage($conversationId, chat_current_user_id(), $body);

    chat_json([
        'ok' => true,
        'message' => 'Mensaje enviado',
        'data' => $message
    ]);
} catch (InvalidArgumentException $e) {
    chat_json(['ok' => false, 'message' => $e->getMessage()], 422);
} catch (RuntimeException $e) {
    chat_json(['ok' => false, 'message' => $e->getMessage()], 403);
} catch (Throwable $e) {
    error_log('Error en send_message: ' . $e->getMessage());
    chat_json(['ok' => false, 'message' => 'Error al enviar mensaje'], 500);
}
