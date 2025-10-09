<?php
// Obtener la página actual para marcar el elemento activo
$current_page = $_SERVER['REQUEST_URI'];
$base_path = '/Login';
$current_path = str_replace($base_path, '', $current_page);
$current_path = trim(parse_url($current_path, PHP_URL_PATH), '/');

// Función para determinar si un enlace está activo
function isActive($path, $current) {
    return strpos($current, $path) !== false ? 'active' : '';
}

// Función para determinar si el menú de configuración debe estar expandido
function isConfigMenuExpanded($current) {
    $config_paths = ['admin/configuracion-elecciones', 'admin/logs-elecciones', 'admin/estado-elecciones', 'admin/configuracion-sistema', 'admin/nueva-eleccion', 'admin/editar-eleccion'];
    foreach ($config_paths as $path) {
        if (strpos($current, $path) !== false) {
            return 'show';
        }
    }
    return '';
}
?>

<div class="d-flex justify-content-center align-items-center py-3">
    <h5 class="mb-0 text-white">Panel Administrativo</h5>
</div>
<hr class="text-white-50">

<div class="px-3 mb-4">
    <div class="text-center mb-3">
        <div class="position-relative d-inline-block">
            <?php
            // Verificar si hay una imagen en la sesión
            if (isset($_SESSION['admin_imagen']) && !empty($_SESSION['admin_imagen'])): ?>
                <img id="sidebar-profile-image" src="<?= $_SESSION['admin_imagen'] ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($_SESSION['admin_nombre'] ?? 'Administrador') ?>" 
                     class="rounded-circle img-fluid mb-2 profile-img-main" style="width: 80px; height: 80px; object-fit: cover;">
            <?php else: ?>
                <i id="profile-icon" class="fas fa-user-circle fa-3x text-white-50"></i>
            <?php endif; ?>
            
            <!-- Botón para cambiar imagen -->
            <?php 
            $show_button = (strpos($current_path, 'admin/panel') !== false ||
                           strpos($current_path, 'admin/configuracion') !== false ||
                           strpos($current_path, 'admin/logs-elecciones') !== false ||
                           strpos($current_path, 'admin/estado-elecciones') !== false ||
                           strpos($current_path, 'admin/estudiantes') !== false ||
                           strpos($current_path, 'admin/docentes') !== false ||
                           strpos($current_path, 'admin/administrativos') !== false ||
                           strpos($current_path, 'admin/candidatos') !== false ||
                           strpos($current_path, 'admin/mesas-virtuales') !== false);
            if ($show_button): 
            ?>
                <button type="button" id="change-profile-image" class="btn btn-sm btn-primary position-absolute" 
                        style="bottom: 10px; right: -5px; border-radius: 50%; width: 25px; height: 25px; padding: 0;" 
                        data-bs-toggle="modal" data-bs-target="#profileModal">
                    <i class="fas fa-camera" style="font-size: 12px;"></i>
                </button>
            <?php endif; ?>
        </div>
        <div class="text-white-50 small"><?= htmlspecialchars($_SESSION['admin_nombre'] ?? 'Administrador') ?></div>
        <small class="text-white-50">Administrador</small>
    </div>
</div>
<hr class="text-white-50">

<!-- Menú de navegación -->
<ul class="nav flex-column px-2">
    <!-- Dashboard -->
    <li class="nav-item">
        <a href="/Login/admin/panel" class="sidebar-link <?= isActive('admin/panel', $current_path) ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
    </li>
    
    <!-- Estudiantes -->
    <li class="nav-item">
        <a href="/Login/admin/estudiantes" class="sidebar-link <?= isActive('admin/estudiantes', $current_path) ?>">
            <i class="fas fa-users"></i> Estudiantes
        </a>
    </li>
    
    <!-- Docentes -->
    <li class="nav-item">
        <a href="/Login/admin/docentes" class="sidebar-link <?= isActive('admin/docentes', $current_path) ?>">
            <i class="fas fa-user-tie"></i> Docentes
        </a>
    </li>
    
    <!-- Administrativos -->
    <li class="nav-item">
        <a href="/Login/admin/administrativos" class="sidebar-link <?= isActive('admin/administrativos', $current_path) ?>">
            <i class="fas fa-user-cog"></i> Administrativos
        </a>
    </li>
    
    <!-- Candidatos -->
    <li class="nav-item">
        <a href="/Login/admin/candidatos" class="sidebar-link <?= isActive('admin/candidatos', $current_path) ?>">
            <i class="fas fa-user-graduate"></i> Candidatos
        </a>
    </li>
    
    <!-- Mesas Virtuales -->
    <li class="nav-item">
        <a href="/Login/admin/mesas-virtuales" class="sidebar-link <?= isActive('admin/mesas-virtuales', $current_path) ?>">
            <i class="fas fa-table"></i> Mesas Virtuales
        </a>
    </li>
    
    <!-- Configuración (Menú desplegable) -->
    <li class="nav-item">
        <a href="#" class="sidebar-link" data-bs-toggle="collapse" data-bs-target="#configuracionSubmenu" 
           aria-expanded="<?= !empty(isConfigMenuExpanded($current_path)) ? 'true' : 'false' ?>">
            <i class="fas fa-cogs"></i> Configuración <i class="fas fa-chevron-down ms-auto"></i>
        </a>
        <div class="collapse <?= isConfigMenuExpanded($current_path) ?>" id="configuracionSubmenu">
            <ul class="nav flex-column ms-3">
                <li class="nav-item">
                    <a href="/Login/admin/configuracion-elecciones" 
                       class="sidebar-link py-1 <?= isActive('admin/configuracion-elecciones', $current_path) ?>">
                        <i class="fas fa-calendar-alt"></i> Elecciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/Login/admin/logs-elecciones" 
                       class="sidebar-link py-1 <?= isActive('admin/logs-elecciones', $current_path) ?>">
                        <i class="fas fa-list-alt"></i> Logs de Elecciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/Login/admin/estado-elecciones" 
                       class="sidebar-link py-1 <?= isActive('admin/estado-elecciones', $current_path) ?>">
                        <i class="fas fa-info-circle"></i> Estado Actual
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/Login/admin/logs" 
                       class="sidebar-link py-1 <?= isActive('admin/logs', $current_path) ?>">
                        <i class="fas fa-file-alt"></i> Logs del Sistema
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/Login/admin/configuracion-sistema" 
                       class="sidebar-link py-1 <?= isActive('admin/configuracion-sistema', $current_path) ?>">
                        <i class="fas fa-sliders-h"></i> Sistema
                    </a>
                </li>
            </ul>
        </div>
    </li>
    
    <!-- Resultados -->
    <li class="nav-item">
        <a href="#" class="sidebar-link">
            <i class="fas fa-chart-bar"></i> Resultados
        </a>
    </li>
    
    <!-- Estadísticas -->
    <li class="nav-item">
        <a href="#" class="sidebar-link">
            <i class="fas fa-chart-pie"></i> Estadísticas
        </a>
    </li>
</ul>

<hr class="text-white-50">

<!-- Cerrar sesión -->
<ul class="nav flex-column px-2">
    <li class="nav-item">
        <a href="/Login/admin/cerrar-sesion" class="sidebar-link text-danger">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </li>
</ul>

<!-- Estilos CSS para el sidebar -->
<style>
.sidebar-link {
    color: #adb5bd;
    text-decoration: none;
    display: block;
    padding: 0.75rem 1rem;
    border-radius: 0.25rem;
    transition: all 0.2s;
}
.sidebar-link:hover, .sidebar-link.active {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
    text-decoration: none;
}
.sidebar-link i {
    margin-right: 0.5rem;
    width: 20px;
    text-align: center;
}
.sidebar-link.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.2);
}
</style>

<!-- Incluir modal de imagen de perfil -->
<?php include __DIR__ . '/includes/profile-modal.php'; ?>
