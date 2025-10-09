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
    <title>Estado de Elecciones - Panel de Administración</title>
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
        .estado-badge {
            font-size: 1.2rem;
            padding: 0.5rem 1rem;
        }
        .countdown {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 1rem 0;
        }
        .status-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .timeline {
            position: relative;
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1.5rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: -1.5rem;
            width: 2px;
            background-color: #dee2e6;
        }
        .timeline-item:last-child::before {
            display: none;
        }
        .timeline-marker {
            position: absolute;
            left: 0;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background-color: #6c757d;
        }
        .timeline-marker.active {
            background-color: #28a745;
        }
        .timeline-marker.pending {
            background-color: #ffc107;
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
                        <h4 class="mb-0">Estado Actual de Elecciones</h4>
                        <small class="text-muted">Monitoreo en tiempo real del sistema electoral</small>
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
                    
                    <!-- Estado actual principal -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Estado Actual del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($eleccionActiva): ?>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="text-center mb-4">
                                            <div class="status-icon text-success">
                                                <i class="fas fa-vote-yea"></i>
                                            </div>
                                            <h3><?php echo htmlspecialchars($eleccionActiva['nombre_eleccion']); ?></h3>
                                            <span class="badge bg-success estado-badge">ELECCIONES ACTIVAS</span>
                                        </div>
                                        <p class="text-center"><?php echo htmlspecialchars($eleccionActiva['descripcion']); ?></p>
                                        
                                        <div class="row text-center">
                                            <div class="col-md-6">
                                                <h6>Fecha de Inicio</h6>
                                                <p><?php echo date('d/m/Y H:i', strtotime($eleccionActiva['fecha_inicio'])); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Fecha de Cierre</h6>
                                                <p><?php echo date('d/m/Y H:i', strtotime($eleccionActiva['fecha_cierre'])); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center mt-3">
                                            <h6>Tipos de Votación Habilitados</h6>
                                            <?php foreach ($eleccionActiva['tipos_votacion'] as $tipo): ?>
                                                <span class="badge bg-info me-2"><?php echo ucfirst($tipo); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-warning">
                                            <div class="card-header bg-warning text-dark">
                                                <h6 class="mb-0">Tiempo Restante</h6>
                                            </div>
                                            <div class="card-body text-center">
                                                <div class="countdown text-warning" id="countdown">
                                                    <?php echo $tiempoRestante['formato_humano']; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 d-grid gap-2">
                                            <a href="/Login/admin/cerrar-eleccion/<?php echo $eleccionActiva['id']; ?>" 
                                               class="btn btn-warning" 
                                               onclick="return confirm('¿Está seguro de cerrar esta elección?');">
                                                <i class="fas fa-lock"></i> Cerrar Elección
                                            </a>
                                            <a href="/Login/admin/configuracion-elecciones" class="btn btn-outline-primary">
                                                <i class="fas fa-cogs"></i> Gestionar Elecciones
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($proximaEleccion): ?>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="text-center mb-4">
                                            <div class="status-icon text-info">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <h3><?php echo htmlspecialchars($proximaEleccion['nombre_eleccion']); ?></h3>
                                            <span class="badge bg-info estado-badge">ELECCIONES PROGRAMADAS</span>
                                        </div>
                                        <p class="text-center"><?php echo htmlspecialchars($proximaEleccion['descripcion']); ?></p>
                                        
                                        <div class="row text-center">
                                            <div class="col-md-6">
                                                <h6>Fecha de Inicio</h6>
                                                <p><?php echo date('d/m/Y H:i', strtotime($proximaEleccion['fecha_inicio'])); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Fecha de Cierre</h6>
                                                <p><?php echo date('d/m/Y H:i', strtotime($proximaEleccion['fecha_cierre'])); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center mt-3">
                                            <h6>Tipos de Votación Programados</h6>
                                            <?php foreach ($proximaEleccion['tipos_votacion'] as $tipo): ?>
                                                <span class="badge bg-secondary me-2"><?php echo ucfirst($tipo); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-info">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="mb-0">Tiempo para Inicio</h6>
                                            </div>
                                            <div class="card-body text-center">
                                                <div class="countdown text-info" id="countdown">
                                                    <?php echo $tiempoRestante['formato_humano']; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 d-grid gap-2">
                                            <a href="/Login/admin/activar-eleccion/<?php echo $proximaEleccion['id']; ?>" 
                                               class="btn btn-success" 
                                               onclick="return confirm('¿Está seguro de activar esta elección ahora?');">
                                                <i class="fas fa-play"></i> Activar Ahora
                                            </a>
                                            <a href="/Login/admin/configuracion-elecciones" class="btn btn-outline-primary">
                                                <i class="fas fa-cogs"></i> Gestionar Elecciones
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="status-icon text-muted">
                                        <i class="fas fa-calendar-times"></i>
                                    </div>
                                    <h3>Sin Elecciones Programadas</h3>
                                    <p class="text-muted">No hay elecciones activas ni programadas en el sistema.</p>
                                    <a href="/Login/admin/nueva-eleccion" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Crear Nueva Elección
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Línea de tiempo de elecciones -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Cronología de Elecciones</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php if (!empty($todasElecciones)): ?>
                                    <?php foreach (array_slice($todasElecciones, 0, 5) as $eleccion): ?>
                                        <div class="timeline-item">
                                            <?php
                                            $markerClass = '';
                                            switch ($eleccion['estado']) {
                                                case 'activa':
                                                    $markerClass = 'active';
                                                    break;
                                                case 'programada':
                                                    $markerClass = 'pending';
                                                    break;
                                                default:
                                                    $markerClass = '';
                                            }
                                            ?>
                                            <div class="timeline-marker <?php echo $markerClass; ?>"></div>
                                            <div class="timeline-content">
                                                <h6><?php echo htmlspecialchars($eleccion['nombre_eleccion']); ?></h6>
                                                <p class="mb-1"><?php echo htmlspecialchars($eleccion['descripcion']); ?></p>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($eleccion['fecha_inicio'])); ?> - 
                                                    <?php echo date('d/m/Y H:i', strtotime($eleccion['fecha_cierre'])); ?>
                                                </small>
                                                <span class="badge bg-<?php 
                                                    echo $eleccion['estado'] === 'activa' ? 'success' : 
                                                        ($eleccion['estado'] === 'programada' ? 'info' : 'secondary'); 
                                                ?> ms-2">
                                                    <?php echo ucfirst($eleccion['estado']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <p class="text-muted">No hay elecciones registradas en el sistema.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enlaces rápidos -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-external-link-alt"></i> Acciones Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="/Login/admin/configuracion-elecciones" class="btn btn-outline-primary d-block">
                                        <i class="fas fa-calendar-alt"></i><br>
                                        <small>Gestionar Elecciones</small>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/Login/admin/logs-elecciones" class="btn btn-outline-info d-block">
                                        <i class="fas fa-list-alt"></i><br>
                                        <small>Ver Logs</small>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/Login/admin/nueva-eleccion" class="btn btn-outline-success d-block">
                                        <i class="fas fa-plus-circle"></i><br>
                                        <small>Nueva Elección</small>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/Login/admin/configuracion-sistema" class="btn btn-outline-secondary d-block">
                                        <i class="fas fa-sliders-h"></i><br>
                                        <small>Configuración</small>
                                    </a>
                                </div>
                            </div>
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
        // Actualizar contador en tiempo real cada 60 segundos
        setInterval(function() {
            location.reload();
        }, 60000);
        
        // Funcionalidad de carga de imagen manejada por profile-image-upload.js
        $(document).ready(function() {
            // Inicialización automática por el archivo externo
        });
    </script>
    
    <script src="/Login/assets/js/profile-image-upload.js"></script>
    
    <!-- Modal incluido desde sidebar.php -->
</body>
</html>
