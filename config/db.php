<?php
// config/db.php 

// ZONA HORARIA ESPECÍFICA PARA LA PAZ, BAJA CALIFORNIA SUR
date_default_timezone_set('America/Mazatlan'); 

function load_dotenv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = preg_replace('/^"(.*)"$/', '$1', $value);
        $value = preg_replace("/^'(.*)'$/", '$1', $value);
        $_ENV[$name] = $value;
    }
}

load_dotenv(__DIR__ . '/../.env');

$DB_HOST = $_ENV['DB_HOST'] ?? '127.0.0.1';
$DB_PORT = $_ENV['DB_PORT'] ?? '3307';
$DB_NAME = $_ENV['DB_NAME'] ?? 'taskcolab';
$DB_USER = $_ENV['DB_USER'] ?? 'root';
$DB_PASS = $_ENV['DB_PASS'] ?? '';
$DB_CHARSET = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset={$DB_CHARSET}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // CREAR $pdo EN ÁMBITO GLOBAL
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    
    // HACERLA GLOBAL EXPLÍCITAMENTE
    $GLOBALS['pdo'] = $pdo;
    
} catch (PDOException $e) {
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());

    if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
        die('Error de conexión a la base de datos: ' . $e->getMessage());
    } else {
        die('Error de conexión a la base de datos');
    }
}

// FUNCIÓN PARA OBTENER LA CONEXIÓN DESDE CUALQUIER LUGAR
function getDBConnection() {
    if (isset($GLOBALS['pdo'])) {
        return $GLOBALS['pdo'];
    }
    
    global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHARSET;
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset={$DB_CHARSET}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    try {
        $GLOBALS['pdo'] = new PDO($dsn, $DB_USER, $DB_PASS, $options);
        return $GLOBALS['pdo'];
    } catch (PDOException $e) {
        error_log('Error recreando conexión BD: ' . $e->getMessage());
        return null;
    }
}

if (!isset($pdo) && isset($GLOBALS['pdo'])) {
    $pdo = $GLOBALS['pdo'];
}