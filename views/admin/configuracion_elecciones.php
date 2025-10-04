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
    <title>Configuración de Elecciones - Panel de Administración</title>
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
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            line-height: 36px;
            text-align: center;
            border-radius: 50%;
        }
        .btn-icon i {
            font-size: 0.9rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state i {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 1rem;
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
                        <h4 class="mb-0">Configuración de Elecciones</h4>
                        <small class="text-muted">Gestión de elecciones y votaciones</small>
                    </div>
                    <div>
                        <a href="/Login/admin/nueva-eleccion" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Elección
                        </a>
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
                    
                    <!-- Resumen del estado actual -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-vote-yea"></i> Estado Actual</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($eleccionActiva): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <span class="badge bg-success p-2 rounded-circle">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="mb-0">Elección Activa</h5>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($eleccionActiva['nombre_eleccion']); ?></p>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted d-block">Inicio: <?php echo date('d/m/Y H:i', strtotime($eleccionActiva['fecha_inicio'])); ?></small>
                                            <small class="text-muted d-block">Cierre: <?php echo date('d/m/Y H:i', strtotime($eleccionActiva['fecha_cierre'])); ?></small>
                                        </div>
                                        <div class="d-grid gap-2 d-md-flex">
                                            <a href="/Login/admin/detalle-eleccion/<?php echo $eleccionActiva['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Ver Detalles
                                            </a>
                                            <a href="/Login/admin/cerrar-eleccion/<?php echo $eleccionActiva['id']; ?>" 
                                               class="btn btn-sm btn-warning" 
                                               onclick="return confirm('¿Está seguro de cerrar esta elección?');">
                                                <i class="fas fa-lock"></i> Cerrar Elección
                                            </a>
                                        </div>
                                    <?php elseif ($proximaEleccion): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <span class="badge bg-info p-2 rounded-circle">
                                                    <i class="fas fa-clock"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="mb-0">Próxima Elección Programada</h5>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($proximaEleccion['nombre_eleccion']); ?></p>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted d-block">Inicio: <?php echo date('d/m/Y H:i', strtotime($proximaEleccion['fecha_inicio'])); ?></small>
                                            <small class="text-muted d-block">Cierre: <?php echo date('d/m/Y H:i', strtotime($proximaEleccion['fecha_cierre'])); ?></small>
                                        </div>
                                        <div class="d-grid gap-2 d-md-flex">
                                            <a href="/Login/admin/detalle-eleccion/<?php echo $proximaEleccion['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Ver Detalles
                                            </a>
                                            <a href="/Login/admin/activar-eleccion/<?php echo $proximaEleccion['id']; ?>" 
                                               class="btn btn-sm btn-success" 
                                               onclick="return confirm('¿Está seguro de activar esta elección ahora?');">
                                                <i class="fas fa-play"></i> Activar Ahora
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-calendar-times mb-3 text-muted" style="font-size: 2rem;"></i>
                                            <h5>Sin Elecciones Programadas</h5>
                                            <p class="text-muted">No hay elecciones activas ni programadas en el sistema.</p>
                                            <a href="/Login/admin/nueva-eleccion" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Crear Nueva Elección
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Estado del Sistema</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <span class="badge bg-<?php echo $estadoElecciones['estado'] === 'activo' ? 'success' : 'secondary'; ?> p-2 rounded-circle">
                                                <i class="fas fa-<?php echo $estadoElecciones['estado'] === 'activo' ? 'check' : 'times'; ?>"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="mb-0">Sistema de Votación</h5>
                                            <p class="text-muted mb-0"><?php echo $estadoElecciones['mensaje']; ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($eleccionActiva || $proximaEleccion): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block">Tiempo restante: <?php echo $tiempoRestante['formato_humano']; ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2 d-md-flex">
                                        <a href="/Login/admin/estado-elecciones" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-chart-line"></i> Ver Estado Detallado
                                        </a>
                                        <a href="/Login/admin/logs-elecciones" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-list-alt"></i> Ver Logs
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de todas las elecciones -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Todas las Elecciones</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($todasElecciones)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Fecha Inicio</th>
                                                <th>Fecha Cierre</th>
                                                <th>Estado</th>
                                                <th>Tipos de Votación</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($todasElecciones as $eleccion): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($eleccion['nombre_eleccion']); ?></strong>
                                                        <?php if (!empty($eleccion['descripcion'])): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($eleccion['descripcion'], 0, 50)) . (strlen($eleccion['descripcion']) > 50 ? '...' : ''); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($eleccion['fecha_inicio'])); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($eleccion['fecha_cierre'])); ?></td>
                                                    <td>
                                                        <?php
                                                        $badgeClass = '';
                                                        switch ($eleccion['estado']) {
                                                            case 'programada':
                                                                $badgeClass = 'bg-info';
                                                                break;
                                                            case 'activa':
                                                                $badgeClass = 'bg-success';
                                                                break;
                                                            case 'cerrada':
                                                                $badgeClass = 'bg-secondary';
                                                                break;
                                                            case 'cancelada':
                                                                $badgeClass = 'bg-danger';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $badgeClass; ?> estado-badge">
                                                            <?php echo ucfirst($eleccion['estado']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($eleccion['tipos_votacion'])): ?>
                                                            <?php foreach ($eleccion['tipos_votacion'] as $tipo): ?>
                                                                <span class="badge bg-light text-dark me-1"><?php echo ucfirst($tipo); ?></span>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-dark">Ninguno</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="/Login/admin/detalle-eleccion/<?php echo $eleccion['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            
                                                            <?php if ($eleccion['estado'] === 'programada'): ?>
                                                                <a href="/Login/admin/editar-eleccion/<?php echo $eleccion['id']; ?>" class="btn btn-sm btn-outline-info" title="Editar">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="/Login/admin/activar-eleccion/<?php echo $eleccion['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-success" 
                                                                   title="Activar"
                                                                   onclick="return confirm('¿Está seguro de activar esta elección?');">
                                                                    <i class="fas fa-play"></i>
                                                                </a>
                                                                <a href="/Login/admin/cancelar-eleccion/<?php echo $eleccion['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-danger" 
                                                                   title="Cancelar"
                                                                   onclick="return confirm('¿Está seguro de cancelar esta elección?');">
                                                                    <i class="fas fa-times"></i>
                                                                </a>
                                                            <?php elseif ($eleccion['estado'] === 'activa'): ?>
                                                                <a href="/Login/admin/cerrar-eleccion/<?php echo $eleccion['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-warning" 
                                                                   title="Cerrar"
                                                                   onclick="return confirm('¿Está seguro de cerrar esta elección?');">
                                                                    <i class="fas fa-lock"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($eleccion['estado'] !== 'activa'): ?>
                                                                <a href="/Login/admin/eliminar-eleccion/<?php echo $eleccion['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-danger" 
                                                                   title="Eliminar"
                                                                   onclick="return confirm('¿Está seguro de eliminar esta elección? Esta acción no se puede deshacer.');">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h5>No hay elecciones registradas</h5>
                                    <p class="text-muted">No se han encontrado elecciones en el sistema.</p>
                                    <a href="/Login/admin/nueva-eleccion" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Crear Nueva Elección
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Elecciones históricas -->
                    <?php if (!empty($eleccionesPasadas)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Historial de Elecciones</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Fecha</th>
                                            <th>Duración</th>
                                            <th>Tipos de Votación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eleccionesPasadas as $eleccion): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($eleccion['nombre_eleccion']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($eleccion['fecha_inicio'])); ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $inicio = new DateTime($eleccion['fecha_inicio']);
                                                    $fin = new DateTime($eleccion['fecha_cierre']);
                                                    $duracion = $inicio->diff($fin);
                                                    
                                                    if ($duracion->days > 0) {
                                                        echo $duracion->days . ' días, ' . $duracion->h . ' horas';
                                                    } else {
                                                        echo $duracion->h . ' horas, ' . $duracion->i . ' minutos';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($eleccion['tipos_votacion'])): ?>
                                                        <?php foreach ($eleccion['tipos_votacion'] as $tipo): ?>
                                                            <span class="badge bg-light text-dark me-1"><?php echo ucfirst($tipo); ?></span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark">Ninguno</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="/Login/admin/detalle-eleccion/<?php echo $eleccion['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                        <i class="fas fa-eye"></i> Ver Detalles
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Refrescar la página cada 30 segundos para actualizar el estado de las elecciones
            setInterval(function() {
                location.reload();
            }, 30000);
        });
    </script>
</body>
</html>
