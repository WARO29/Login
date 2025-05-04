<?php
// Verificar si la sesión está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    // Redirigir al login si no está autenticado
    header("Location: /Login/admin/login");
    exit();
}

// Datos del administrador autenticado
$admin_id = $_SESSION['admin_id'];
$admin_usuario = $_SESSION['admin_usuario'];
$admin_nombre = $_SESSION['admin_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Administrativo - Sistema de Votación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #212529;
            color: white;
            padding-top: 1rem;
        }
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
        }
        .sidebar-link i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        .main-content {
            min-height: 100vh;
        }
        .content-header {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        .stat-card {
            padding: 1.5rem;
        }
        .stat-icon {
            font-size: 2rem;
            background-color: rgba(52, 58, 64, 0.1);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="d-flex justify-content-center align-items-center py-3">
                    <h5 class="mb-0">Panel Administrativo</h5>
                </div>
                <hr class="text-white-50">
                <div class="px-3 mb-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-circle fa-3x text-white-50"></i>
                        <p class="mt-2 mb-0"><?= htmlspecialchars($admin_nombre) ?></p>
                        <small class="text-white-50">Administrador</small>
                    </div>
                </div>
                <ul class="nav flex-column px-2">
                    <li class="nav-item">
                        <a href="/Login/admin/panel" class="sidebar-link active">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/Login/admin/estudiantes" class="sidebar-link">
                            <i class="fas fa-users"></i> Estudiantes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/Login/admin/docentes" class="sidebar-link">
                            <i class="fas fa-user-tie"></i> Docentes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-user-graduate"></i> Candidatos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-vote-yea"></i> Resultados
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-chart-pie"></i> Estadísticas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a href="/Login/admin/cerrar-sesion" class="sidebar-link text-danger">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content p-0">
                <!-- Header -->
                <div class="content-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Dashboard</h4>
                        <small class="text-muted">Bienvenido al panel administrativo</small>
                    </div>
                    <div>
                        <span class="me-3"><?= date('d/m/Y H:i') ?></span>
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($admin_usuario) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-1"></i> Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/Login/admin/cerrar-sesion"><i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Content Area -->
                <div class="container-fluid px-4 py-3">
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="stat-icon text-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="mb-1">1,252</h3>
                                <p class="text-muted mb-0">Total Estudiantes</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="stat-icon text-success">
                                    <i class="fas fa-vote-yea"></i>
                                </div>
                                <h3 class="mb-1">845</h3>
                                <p class="text-muted mb-0">Votos Registrados</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="stat-icon text-warning">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <h3 class="mb-1">12</h3>
                                <p class="text-muted mb-0">Candidatos</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="stat-icon text-danger">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <h3 class="mb-1">68%</h3>
                                <p class="text-muted mb-0">Participación</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main Cards -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Resultados de Votación</h5>
                                </div>
                                <div class="card-body">
                                    <p>Aquí se mostrarán los gráficos de resultados.</p>
                                    <div class="alert alert-info">
                                        Para ver datos reales, debes implementar la lógica de consulta de resultados.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Actividad Reciente</h5>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <small class="text-muted">10:45 AM</small>
                                                <p class="mb-0">Juan Pérez registró su voto</p>
                                            </div>
                                            <span class="badge bg-primary rounded-pill">Personero</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <small class="text-muted">10:30 AM</small>
                                                <p class="mb-0">María García registró su voto</p>
                                            </div>
                                            <span class="badge bg-success rounded-pill">Representante</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <small class="text-muted">10:15 AM</small>
                                                <p class="mb-0">Carlos López registró su voto</p>
                                            </div>
                                            <span class="badge bg-primary rounded-pill">Personero</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <small class="text-muted">10:00 AM</small>
                                                <p class="mb-0">Laura Rodríguez registró su voto</p>
                                            </div>
                                            <span class="badge bg-success rounded-pill">Representante</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>