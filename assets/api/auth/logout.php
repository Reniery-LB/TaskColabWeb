<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

api_require_method(['POST']);

api_current_user();
api_revoke_current_session();

api_json([
    'ok' => true,
    'message' => 'Sesión cerrada',
]);
?>
