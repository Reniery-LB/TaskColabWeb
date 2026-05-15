<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function api_db(): PDO
{
    $pdo = getDBConnection();
    if (!$pdo) {
        api_json(['ok' => false, 'message' => 'No se pudo conectar a la base de datos'], 500);
    }

    return $pdo;
}

function api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $input = json_decode($raw, true);
    if (!is_array($input)) {
        api_json(['ok' => false, 'message' => 'Payload JSON inválido'], 400);
    }

    return $input;
}

function api_require_method(array $methods): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        api_json(['ok' => false, 'message' => 'Método no permitido'], 405);
    }
}

function api_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

    if (!$header && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    }

    if (!$header || !preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
        return null;
    }

    return trim($matches[1]);
}

function api_hash_token(string $token): string
{
    return hash('sha256', $token);
}

function api_public_user(array $user): array
{
    return [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'is_admin' => (bool)$user['is_admin'],
        'avatar_url' => $user['avatar_url'] ?? null,
        'notes' => $user['notes'] ?? null,
        'is_active' => (bool)($user['is_active'] ?? 1),
        'created_at' => $user['created_at'] ?? null,
        'last_login' => $user['last_login'] ?? null,
    ];
}

function api_create_session(int $userId, ?string $deviceName = null): array
{
    $pdo = api_db();
    $token = bin2hex(random_bytes(32));
    $tokenHash = api_hash_token($token);
    $expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, token_hash, device_name, ip_address, user_agent, expires_at, last_seen_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $tokenHash, $deviceName, $ipAddress, $userAgent, $expiresAt]);

    return [
        'token' => $token,
        'expires_at' => $expiresAt,
    ];
}

function api_current_user(bool $required = true): ?array
{
    $token = api_bearer_token();
    if (!$token) {
        if ($required) {
            api_json(['ok' => false, 'message' => 'Token no enviado'], 401);
        }
        return null;
    }

    $pdo = api_db();
    $stmt = $pdo->prepare("
        SELECT
            u.id, u.name, u.email, u.is_admin, u.avatar_url, u.notes, u.created_at, u.last_login, u.is_active,
            s.id AS session_id, s.expires_at
        FROM user_sessions s
        INNER JOIN users u ON u.id = s.user_id
        WHERE s.token_hash = ?
          AND s.revoked_at IS NULL
          AND s.expires_at > NOW()
          AND u.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([api_hash_token($token)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        if ($required) {
            api_json(['ok' => false, 'message' => 'Sesión inválida o expirada'], 401);
        }
        return null;
    }

    $update = $pdo->prepare('UPDATE user_sessions SET last_seen_at = NOW() WHERE id = ?');
    $update->execute([(int)$user['session_id']]);

    return $user;
}

function api_revoke_current_session(): void
{
    $token = api_bearer_token();
    if (!$token) {
        return;
    }

    $stmt = api_db()->prepare('UPDATE user_sessions SET revoked_at = NOW() WHERE token_hash = ? AND revoked_at IS NULL');
    $stmt->execute([api_hash_token($token)]);
}
?>
