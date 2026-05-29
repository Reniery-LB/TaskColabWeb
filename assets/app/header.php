<?php
// assets/app/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

$user = $_SESSION['user'] ?? null;
require_once __DIR__ . '/../../config/app.php';
$basePath = app_base_path();
$imgBase = app_url('assets/img');
$homeLink = app_url('index.html');
$logoutLink = app_url('assets/app/logout.php');

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
            header('Location: ' . $homeLink);
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

// Configurar todas las APIs
$API_BASE = app_url('assets/app/endpoints');
$API_BASE_PERFIL = app_url('assets/app/endpointsPerfil');
$API_BASE_TAREAS = app_url('assets/app/endpointsTareas');
$API_BASE_TABLEROS = app_url('assets/app/endpointsTableros'); 
$API_BASE_PROJECTS = app_url('assets/app/endpointsProjects');
$API_BASE_CHAT = app_url('assets/app/endpointsChat');
$API_BASE_REPORTES = app_url('assets/app/endpointsReportes');
$UPLOADS_BASE = app_url('assets/uploads');
?>
<header class="header">
  <div class="left-section">
    <div class="logo-box-todo">
      <a href="<?php echo $logoutLink; ?>" class="header-logo-logout-link" aria-label="Cerrar sesión"><img src="<?php echo $imgBase; ?>/logo.png" width="30" alt="logo_tareas" class="logo"></a>
    </div>
    <div class="tab-container">
      <h2 class="tab">Espacio de trabajo</h2>
      <!-- <button class="add-tab">+</button> -->
    </div>
  </div>

  <div class="user-info">
    <button type="button" class="header-chat-button" data-go-section="chat" aria-label="Abrir chats">
      <span class="header-chat-icon" aria-hidden="true"></span>
      <span>Chats</span>
    </button>
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
    <a class="header-logout-link" href="<?php echo $logoutLink; ?>"><img src="<?php echo $imgBase; ?>/cerrarsesion.png" alt="cerrar sesión"></a>
  </div>

  <script>
    // CONFIGURACIÓN UNIFICADA DE TODAS LAS APIS
    window.APP_BASE = "<?php echo htmlspecialchars($basePath, ENT_QUOTES); ?>";
    window.IMG_BASE = "<?php echo htmlspecialchars($imgBase, ENT_QUOTES); ?>";
    window.HOME_URL = "<?php echo htmlspecialchars($homeLink, ENT_QUOTES); ?>";
    window.LOGOUT_URL = "<?php echo htmlspecialchars($logoutLink, ENT_QUOTES); ?>";
    window.API_BASE = "<?php echo $API_BASE; ?>";
    window.API_BASE_PERFIL = "<?php echo $API_BASE_PERFIL; ?>";
    window.API_BASE_TAREAS = "<?php echo $API_BASE_TAREAS; ?>";
    window.API_BASE_TABLEROS = "<?php echo $API_BASE_TABLEROS; ?>"; 
    window.API_BASE_PROJECTS = "<?php echo $API_BASE_PROJECTS; ?>";
    window.API_BASE_CHAT = "<?php echo $API_BASE_CHAT; ?>";
    window.API_BASE_REPORTES = "<?php echo $API_BASE_REPORTES; ?>";
    window.UPLOADS_BASE = "<?php echo $UPLOADS_BASE; ?>";
    window.CURRENT_USER = <?php echo json_encode($user ?? null, JSON_UNESCAPED_UNICODE); ?>;
    
    console.log("=== CONFIGURACIÓN DE APIs ===");
    console.log("API_BASE:", window.API_BASE);
    console.log("API_BASE_TAREAS:", window.API_BASE_TAREAS);
    console.log("API_BASE_TABLEROS:", window.API_BASE_TABLEROS); 
    console.log("API_BASE_PROJECTS:", window.API_BASE_PROJECTS);
    console.log("API_BASE_CHAT:", window.API_BASE_CHAT);
    console.log("API_BASE_PERFIL:", window.API_BASE_PERFIL);
    console.log("=============================");
  </script>
</header>

<nav class="sidebar">
  <a href="#" data-section="proyectos">Proyectos <img class="nav-arrow" src="<?php echo $imgBase; ?>/flecha.png" alt=""></a>
  <a href="#" data-section="tableros">Tableros <img class="nav-arrow" src="<?php echo $imgBase; ?>/flecha.png" alt=""></a>
  <a href="#" data-section="tareas">Tareas <img class="nav-arrow" src="<?php echo $imgBase; ?>/flecha.png" alt=""></a>
  <a href="#" data-section="reportes">Reportes <img class="nav-arrow" src="<?php echo $imgBase; ?>/flecha.png" alt=""></a>
  <a href="#" data-section="usuarios">Usuarios <img class="nav-arrow" src="<?php echo $imgBase; ?>/flecha.png" alt=""></a>
  <?php if ($isAdmin): ?>
    <a href="#" data-section="admin">Admin <img class="nav-arrow" src="<?php echo $imgBase; ?>/flecha.png" alt=""></a>
  <?php endif; ?>
  <a href="#" data-section="perfil">Perfil <img class="nav-arrow" src="<?php echo $imgBase; ?>/flecha.png" alt=""></a>
  <a href="#" data-section="inicio" class="sidebar-home-link">
    <span class="sidebar-home-icon-wrap">
      <img class="sidebar-home-icon" src="<?php echo $imgBase; ?>/casita.png" alt="">
    </span>
    <span>Home</span>
    <img class="nav-arrow" src="<?php echo $imgBase; ?>/flecha.png" alt="">
  </a>
</nav>
