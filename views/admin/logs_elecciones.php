<?php
// Verificar que el usuario sea administrador
if (!isset($_SESSION['admin_id'])) {
    header('Location: /Login/admin/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Elecciones - Panel de Administración</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .log-entry {
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #dee2e6;
        }
        .log-intento {
            border-left-color: #ffc107;
            background-color: #fff8e1;
        }
        .log-exitoso {
            border-left-color: #28a745;
            background-color: #f0f8f4;
        }
        .log-bloqueado {
            border-left-color: #dc3545;
            background-color: #fdf2f2;
        }
        .log-voto {
            border-left-color: #17a2b8;
            background-color: #f0f9ff;
        }
        .badge-action {
            font-size: 0.8rem;
        }
        .stats-card {
            text-align: center;
            padding: 1.5rem;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <?php include 'views/admin/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content p-0">
                <!-- Header -->
                <div class="content-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Logs de Elecciones</h4>
                        <small class="text-muted">Registro de accesos y eventos del sistema de elecciones</small>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>
                
                <div class="container-fluid px-4 py-3">
                    <!-- Alertas y mensajes -->
                    <?php if (isset($_SESSION['mensaje'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['tipo'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['mensaje']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
                    <?php endif; ?>
                    
                    <!-- Estadísticas de logs -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body stats-card">
                                    <div class="stats-number text-primary">
                                        <?php echo $estadisticasAcceso['intento_login'] ?? 0; ?>
                                    </div>
                                    <div class="text-muted">Intentos de Acceso</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body stats-card">
                                    <div class="stats-number text-success">
                                        <?php echo $estadisticasAcceso['login_exitoso'] ?? 0; ?>
                                    </div>
                                    <div class="text-muted">Accesos Exitosos</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body stats-card">
                                    <div class="stats-number text-danger">
                                        <?php echo $estadisticasAcceso['login_bloqueado'] ?? 0; ?>
                                    </div>
                                    <div class="text-muted">Accesos Bloqueados</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body stats-card">
                                    <div class="stats-number text-info">
                                        <?php echo $estadisticasAcceso['voto_registrado'] ?? 0; ?>
                                    </div>
                                    <div class="text-muted">Votos Registrados</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estadísticas por tipo de usuario -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Estadísticas por Tipo de Usuario</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($estadisticasPorTipo as $tipo => $total): ?>
                                    <div class="col-md-4">
                                        <div class="text-center p-3">
                                            <div class="h4 mb-1"><?php echo $total; ?></div>
                                            <div class="text-muted"><?php echo ucfirst($tipo); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de logs -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Registro de Eventos Recientes</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($logs)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No hay logs registrados.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php 
                                    $logClass = '';
                                    $iconClass = '';
                                    $badgeClass = '';
                                    
                                    switch ($log['accion']) {
                                        case 'intento_login':
                                            $logClass = 'log-intento';
                                            $iconClass = 'fas fa-sign-in-alt text-warning';
                                            $badgeClass = 'bg-warning';
                                            break;
                                        case 'login_exitoso':
                                            $logClass = 'log-exitoso';
                                            $iconClass = 'fas fa-check-circle text-success';
                                            $badgeClass = 'bg-success';
                                            break;
                                        case 'login_bloqueado':
                                            $logClass = 'log-bloqueado';
                                            $iconClass = 'fas fa-ban text-danger';
                                            $badgeClass = 'bg-danger';
                                            break;
                                        case 'voto_registrado':
                                            $logClass = 'log-voto';
                                            $iconClass = 'fas fa-vote-yea text-info';
                                            $badgeClass = 'bg-info';
                                            break;
                                        default:
                                            $iconClass = 'fas fa-info-circle text-secondary';
                                            $badgeClass = 'bg-secondary';
                                    }
                                    ?>
                                    <div class="log-entry <?php echo $logClass; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="d-flex align-items-start">
                                                <div class="me-3 mt-1">
                                                    <i class="<?php echo $iconClass; ?>"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($log['nombre_usuario'] ?? $log['id_usuario']); ?>
                                                        <span class="badge <?php echo $badgeClass; ?> badge-action ms-2">
                                                            <?php 
                                                            switch ($log['accion']) {
                                                                case 'intento_login': echo 'Intento de acceso'; break;
                                                                case 'login_exitoso': echo 'Acceso exitoso'; break;
                                                                case 'login_bloqueado': echo 'Acceso bloqueado'; break;
                                                                case 'voto_registrado': echo 'Voto registrado'; break;
                                                                default: echo ucfirst($log['accion']);
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div class="text-muted small">
                                                        Tipo: <?php echo ucfirst($log['tipo_usuario']); ?>
                                                        <?php if (!empty($log['motivo'])): ?>
                                                            | Motivo: <?php echo htmlspecialchars($log['motivo'] ?? ''); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        IP: <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                                                        <?php if (!empty($log['nombre_eleccion'])): ?>
                                                            | Elección: <?php echo htmlspecialchars($log['nombre_eleccion'] ?? ''); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo date('d/m/Y H:i:s', strtotime($log['fecha_evento'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Funcionalidad de carga de imagen manejada por profile-image-upload.js
        $(document).ready(function() {
            // Inicialización automática por el archivo externo
        });
    </script>
    
    <script src="/Login/assets/js/profile-image-upload.js"></script>
    
    <!-- Modal incluido desde sidebar.php -->
</body>
</html>
