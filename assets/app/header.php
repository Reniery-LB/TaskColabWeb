<?php
// assets/app/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

$user = $_SESSION['user'] ?? null;

// Cargar datos de la BD incluyendo avatar_url
if ($user && isset($user['id'])) {
    try {
        require_once __DIR__ . '/../../config/db.php';
        $stmt = $pdo->prepare("SELECT name, email, avatar_url, is_admin, is_active FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            // Actualizar sesión 
            $_SESSION['user']['name'] = $userData['name'];
            $_SESSION['user']['email'] = $userData['email'];
            $_SESSION['user']['avatar_url'] = $userData['avatar_url'];
            $_SESSION['user']['is_admin'] = $userData['is_admin'];
            $_SESSION['user']['is_active'] = $userData['is_active'];
            $user = $_SESSION['user']; 
        } else {
            // Usuario no encontrado o inactivo - cerrar sesión
            session_destroy();
            header('Location: ../../index.html');
            exit;
        }
    } catch (Exception $e) {
        error_log("Error cargando datos de usuario: " . $e->getMessage());
    }
}

$userName = $user['name'] ?? 'Invitado';
$userEmail = $user['email'] ?? 'No especificado';
$avatarUrl = $user['avatar_url'] ?? null;
$isAdmin = !empty($user['is_admin']);
$initial = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'));

$imgBase = '../../assets/img';
$homeLink = '../../index.html';

// DETECTAR SI ESTAMOS EN LOCAL O EN PRODUCCIÓN
$isLocal = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
            strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);

// CONFIGURAR RUTAS BASE SEGÚN EL ENTORNO
if ($isLocal) {
    $basePath = '/PROYECTO_GESTOR_TAREAS';
} else {
    $basePath = ''; // Para producción (InfinityFree)
}

// Configurar todas las APIs
$API_BASE = $basePath . '/assets/app/endpoints';
$API_BASE_PERFIL = $basePath . '/assets/app/endpointsPerfil';
$API_BASE_TAREAS = $basePath . '/assets/app/endpointsTareas';
$API_BASE_TABLEROS = $basePath . '/assets/app/endpointsTableros'; 
$UPLOADS_BASE = $basePath . '/assets/uploads';
?>
<header class="header">
  <div class="left-section">
    <div class="logo-box-todo">
      <a href="<?php echo $homeLink; ?>"><img src="<?php echo $imgBase; ?>/logo.png" width="30" alt="logo_tareas" class="logo"></a>
    </div>
    <div class="tab-container">
      <h2 class="tab">Pestaña 1</h2>
      <!-- <button class="add-tab">+</button> -->
    </div>
  </div>

  <div class="user-info">
    <h3><?php echo htmlspecialchars($userName, ENT_QUOTES); ?></h3>
    <div class="profile-circle">
        <?php if (!empty($avatarUrl)): ?>
            <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES); ?>?t=<?php echo time(); ?>" 
                 alt="Avatar" 
                 style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
        <?php else: ?>
            <?php echo htmlspecialchars($initial, ENT_QUOTES); ?>
        <?php endif; ?>
    </div>
    <a href="<?php echo $homeLink; ?>"><img src="<?php echo $imgBase; ?>/cerrarsesion.png" alt="cerrar sesión"></a>
  </div>

  <script>
    // CONFIGURACIÓN UNIFICADA DE TODAS LAS APIS
    window.API_BASE = "<?php echo $API_BASE; ?>";
    window.API_BASE_PERFIL = "<?php echo $API_BASE_PERFIL; ?>";
    window.API_BASE_TAREAS = "<?php echo $API_BASE_TAREAS; ?>";
    window.API_BASE_TABLEROS = "<?php echo $API_BASE_TABLEROS; ?>"; 
    window.UPLOADS_BASE = "<?php echo $UPLOADS_BASE; ?>";
    window.CURRENT_USER = <?php echo json_encode($user ?? null, JSON_UNESCAPED_UNICODE); ?>;
    
    console.log("=== CONFIGURACIÓN DE APIs ===");
    console.log("API_BASE:", window.API_BASE);
    console.log("API_BASE_TAREAS:", window.API_BASE_TAREAS);
    console.log("API_BASE_TABLEROS:", window.API_BASE_TABLEROS); 
    console.log("API_BASE_PERFIL:", window.API_BASE_PERFIL);
    console.log("=============================");
  </script>
</header>

<nav class="sidebar">
  <a href="#" data-section="tableros">Tableros <img src="<?php echo $imgBase; ?>/flecha.png"></a>
  <a href="#" data-section="tareas">Tareas <img src="<?php echo $imgBase; ?>/flecha.png"></a>
  <a href="#" data-section="reportes">Reportes <img src="<?php echo $imgBase; ?>/flecha.png"></a>
  <a href="#" data-section="usuarios">Usuarios <img src="<?php echo $imgBase; ?>/flecha.png"></a>
  <a href="#" data-section="perfil">Perfil <img src="<?php echo $imgBase; ?>/flecha.png"></a>
  <a href="#" data-section="inicio" style="display: none !important;"><img src="<?php echo $imgBase; ?>/casita.png" width="30px" height="30px"><img src="<?php echo $imgBase; ?>/flecha.png"></a>

  <?php if ($isAdmin): ?>
    <a href="#" data-section="admin">Admin <img src="<?php echo $imgBase; ?>/flecha.png"></a>
  <?php endif; ?>
</nav>