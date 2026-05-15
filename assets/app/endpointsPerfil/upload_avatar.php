<?php
// assets/app/endpointsPerfil/upload_avatar.php
// HEADERS CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['ok' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../../config/db.php';

$userId = $_SESSION['user']['id'];
$response = ['ok' => false, 'message' => 'Error desconocido'];

try {
    if (!isset($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
        throw new Exception('No se recibió ningún archivo válido');
    }

    $file = $_FILES['avatar'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error en subida: ' . $file['error']);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido: ' . $file['type']);
    }

    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        throw new Exception('El archivo no es una imagen válida');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('La imagen excede 2MB');
    }

    // RUTA CORREGIDA - Desde la raíz del servidor web
    $avatarDir = $_SERVER['DOCUMENT_ROOT'] . '/PROYECTO_GESTOR_TAREAS/assets/uploads/avatars';
    
    if (!is_dir($avatarDir)) {
        if (!mkdir($avatarDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de avatares');
        }
    }

    if (!is_writable($avatarDir)) {
        throw new Exception('El directorio no tiene permisos de escritura');
    }

    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;
    $filePath = $avatarDir . '/' . $fileName;

    // Mover el archivo subido
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Error al guardar la imagen en el servidor');
    }

    if (!file_exists($filePath)) {
        throw new Exception('El archivo no se creó en la ruta esperada');
    }

    // Ruta relativa para la base de datos 
    $relativePath = '/PROYECTO_GESTOR_TAREAS/assets/uploads/avatars/' . $fileName;

    // Actualizar en la base de datos
    $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
    $result = $stmt->execute([$relativePath, $userId]);

    if (!$result) {
        throw new Exception('Error ejecutando consulta en BD');
    }

    // Actualizar sesión
    $_SESSION['user']['avatar_url'] = $relativePath;

    $response = [
        'ok' => true,
        'message' => 'Avatar actualizado correctamente',
        'avatar_url' => $relativePath
    ];

} catch (Exception $e) {
    $response = ['ok' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit;
?>