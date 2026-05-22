<?php
require_once __DIR__ . '/bootstrap.php';

chat_require_method(['POST']);

$input = chat_input();
$projectId = (int)($input['project_id'] ?? 0);

if ($projectId <= 0) {
    chat_json(['ok' => false, 'message' => 'Proyecto inválido'], 422);
}

try {
    $model = new ChatModel();
    $conversation = $model->createProjectConversation($projectId, chat_current_user_id());

    chat_json([
        'ok' => true,
        'conversation' => $conversation
    ]);
} catch (RuntimeException $e) {
    chat_json(['ok' => false, 'message' => $e->getMessage()], 403);
} catch (Throwable $e) {
    error_log('Error en create_project: ' . $e->getMessage());
    chat_json(['ok' => false, 'message' => 'Error al abrir chat de proyecto'], 500);
}
