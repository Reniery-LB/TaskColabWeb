<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

api_require_method(['GET']);

$user = api_current_user();

api_json([
    'ok' => true,
    'user' => api_public_user($user),
]);
?>
