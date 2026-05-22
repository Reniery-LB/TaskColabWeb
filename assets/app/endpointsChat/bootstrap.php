<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../models/ChatModel.php';

function chat_json(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function chat_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $input = json_decode($raw, true);
    if (!is_array($input)) {
        chat_json(['ok' => false, 'message' => 'Payload JSON inválido'], 400);
    }

    return $input;
}

function chat_current_user_id(): int {
    $userId = $_SESSION['user']['id'] ?? null;
    if (!$userId) {
        chat_json(['ok' => false, 'message' => 'No autenticado'], 401);
    }

    return (int)$userId;
}

function chat_require_method(array $methods): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        chat_json(['ok' => false, 'message' => 'Método no permitido'], 405);
    }
}
