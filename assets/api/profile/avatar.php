<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../../config/app.php';

api_require_method(['POST']);

$user = api_current_user();
$userId = (int)$user['id'];

if (!isset($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
    api_json(['ok' => false, 'message' => 'No se recibio ningun archivo valido'], 400);
}

$file = $_FILES['avatar'];

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    api_json(['ok' => false, 'message' => 'Error al subir la imagen'], 400);
}

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];

$mimeType = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
if (!array_key_exists($mimeType, $allowedTypes)) {
    api_json(['ok' => false, 'message' => 'Tipo de archivo no permitido'], 400);
}

if (!getimagesize($file['tmp_name'])) {
    api_json(['ok' => false, 'message' => 'El archivo no es una imagen valida'], 400);
}

if ((int)$file['size'] > 2 * 1024 * 1024) {
    api_json(['ok' => false, 'message' => 'La imagen excede 2MB'], 400);
}

$avatarDir = project_path('assets/uploads/avatars');
if (!is_dir($avatarDir) && !mkdir($avatarDir, 0755, true)) {
    api_json(['ok' => false, 'message' => 'No se pudo crear el directorio de avatares'], 500);
}

if (!is_writable($avatarDir)) {
    api_json(['ok' => false, 'message' => 'El directorio no tiene permisos de escritura'], 500);
}

$fileName = 'avatar_' . $userId . '_' . time() . '.' . $allowedTypes[$mimeType];
$filePath = $avatarDir . '/' . $fileName;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    api_json(['ok' => false, 'message' => 'Error al guardar la imagen'], 500);
}

$relativePath = app_url('assets/uploads/avatars/' . $fileName);

$stmt = api_db()->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
$stmt->execute([$relativePath, $userId]);

$updated = api_current_user();

api_json([
    'ok' => true,
    'message' => 'Avatar actualizado correctamente',
    'avatar_url' => $relativePath,
    'user' => api_public_user($updated),
]);
?>
