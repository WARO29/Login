<?php
// Verificar que el usuario sea administrador
if (!isset($_SESSION['admin_id'])) {
    header('Location: /Login/admin/login');
    exit;
}

// Verificar que la elección exista
if (!isset($eleccion) || !$eleccion) {
    header('Location: /Login/admin/configuracion-elecciones');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Elección - Panel de Administración</title>
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
        .profile-img-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .badge-estado {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        .timeline {
            position: relative;
            margin: 0 auto;
        }
        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background-color: #dee2e6;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
        }
        .timeline-container {
            padding: 10px 40px;
            position: relative;
            background-color: inherit;
            width: 50%;
        }
        .timeline-container::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            right: -12px;
            background-color: white;
            border: 4px solid #6c757d;
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }
        .left {
            left: 0;
        }
        .right {
            left: 50%;
        }
        .left::before {
            content: " ";
            height: 0;
            position: absolute;
            top: 22px;
            width: 0;
            z-index: 1;
            right: 30px;
            border: medium solid #f8f9fa;
            border-width: 10px 0 10px 10px;
            border-color: transparent transparent transparent #f8f9fa;
        }
        .right::before {
            content: " ";
            height: 0;
            position: absolute;
            top: 22px;
            width: 0;
            z-index: 1;
            left: 30px;
            border: medium solid #f8f9fa;
            border-width: 10px 10px 10px 0;
            border-color: transparent #f8f9fa transparent transparent;
        }
        .right::after {
            left: -13px;
        }
        .timeline-content {
            padding: 20px 30px;
            background-color: #f8f9fa;
            position: relative;
            border-radius: 6px;
        }
        .log-entry {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        .log-entry:nth-child(odd) {
            background-color: #f8f9fa;
        }
        .log-entry:nth-child(even) {
            background-color: #e9ecef;
        }
        .log-time {
            font-weight: bold;
            color: #6c757d;
        }
        .log-user {
            font-weight: bold;
        }
        .log-action {
            color: #495057;
        }
        .log-intento {
            border-left: 4px solid #ffc107;
        }
        .log-exitoso {
            border-left: 4px solid #28a745;
        }
        .log-bloqueado {
            border-left: 4px solid #dc3545;
        }
        .log-voto {
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
                        <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <?php include 'views/admin/sidebar.php'; ?>
            </div><!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalle de Elección</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="/Login/admin/configuracion-elecciones" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
                <?php endif; ?>
                
                <!-- Información general de la elección -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-info-circle"></i> Información General
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h3><?php echo htmlspecialchars($eleccion['nombre_eleccion']); ?></h3>
                                <p><?php echo htmlspecialchars($eleccion['descripcion']); ?></p>
                                
                                <div class="mt-3">
                                    <?php 
                                    $estadoBadgeClass = '';
                                    switch ($eleccion['estado']) {
                                        case 'programada':
                                            $estadoBadgeClass = 'bg-info';
                                            break;
                                        case 'activa':
                                            $estadoBadgeClass = 'bg-success';
                                            break;
                                        case 'cerrada':
                                            $estadoBadgeClass = 'bg-secondary';
                                            break;
                                        case 'cancelada':
                                            $estadoBadgeClass = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $estadoBadgeClass; ?> badge-estado">
                                        <?php echo strtoupper($eleccion['estado']); ?>
                                    </span>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <h5>Fecha de Inicio</h5>
                                        <p><?php echo date('d/m/Y H:i', strtotime($eleccion['fecha_inicio'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Fecha de Cierre</h5>
                                        <p><?php echo date('d/m/Y H:i', strtotime($eleccion['fecha_cierre'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <h5>Tipos de Votación Habilitados</h5>
                                    <div>
                                        <?php foreach ($eleccion['tipos_votacion'] as $tipo): ?>
                                            <span class="badge bg-secondary me-2"><?php echo ucfirst($tipo); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <h5>Configuraciones Adicionales</h5>
                                    <ul>
                                        <li>Mostrar resultados parciales: 
                                            <?php echo isset($eleccion['configuracion_adicional']['mostrar_resultados_parciales']) && $eleccion['configuracion_adicional']['mostrar_resultados_parciales'] ? 'Sí' : 'No'; ?>
                                        </li>
                                        <li>Permitir voto en blanco: 
                                            <?php echo isset($eleccion['configuracion_adicional']['permitir_voto_blanco']) && $eleccion['configuracion_adicional']['permitir_voto_blanco'] ? 'Sí' : 'No'; ?>
                                        </li>
                                        <li>Tiempo máximo de votación: 
                                            <?php 
                                            $tiempoMaximo = isset($eleccion['configuracion_adicional']['tiempo_maximo_votacion']) ? $eleccion['configuracion_adicional']['tiempo_maximo_votacion'] : 0;
                                            echo $tiempoMaximo > 0 ? $tiempoMaximo . ' minutos' : 'Sin límite'; 
                                            ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <i class="fas fa-cogs"></i> Acciones
                                    </div>
                                    <div class="card-body">
                                        <?php if ($eleccion['estado'] === 'programada'): ?>
                                            <a href="/Login/admin/activar-eleccion/<?php echo $eleccion['id']; ?>" class="btn btn-success btn-block mb-2 w-100" onclick="return confirm('¿Está seguro de activar esta elección?');">
                                                <i class="fas fa-play"></i> Activar Elección
                                            </a>
                                            <a href="/Login/admin/editar-eleccion/<?php echo $eleccion['id']; ?>" class="btn btn-primary btn-block mb-2 w-100">
                                                <i class="fas fa-edit"></i> Editar Elección
                                            </a>
                                            <a href="/Login/admin/eliminar-eleccion/<?php echo $eleccion['id']; ?>" class="btn btn-danger btn-block mb-2 w-100" onclick="return confirm('¿Está seguro de eliminar esta elección? Esta acción no se puede deshacer.');">
                                                <i class="fas fa-trash"></i> Eliminar Elección
                                            </a>
                                        <?php elseif ($eleccion['estado'] === 'activa'): ?>
                                            <a href="/Login/admin/cerrar-eleccion/<?php echo $eleccion['id']; ?>" class="btn btn-warning btn-block mb-2 w-100" onclick="return confirm('¿Está seguro de cerrar esta elección?');">
                                                <i class="fas fa-lock"></i> Cerrar Elección
                                            </a>
                                            <a href="/Login/admin/cancelar-eleccion/<?php echo $eleccion['id']; ?>" class="btn btn-danger btn-block mb-2 w-100" onclick="return confirm('¿Está seguro de cancelar esta elección? Esta acción no se puede deshacer.');">
                                                <i class="fas fa-ban"></i> Cancelar Elección
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="/Login/admin/resultados-eleccion/<?php echo $eleccion['id']; ?>" class="btn btn-info btn-block mb-2 w-100">
                                            <i class="fas fa-chart-bar"></i> Ver Resultados
                                        </a>
                                        <a href="/Login/admin/exportar-logs/<?php echo $eleccion['id']; ?>" class="btn btn-secondary btn-block mb-2 w-100">
                                            <i class="fas fa-download"></i> Exportar Logs
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="card border-primary mt-3">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-chart-pie"></i> Estadísticas
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <h6>Participación</h6>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 65%;" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">65%</div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <h6>Votos Registrados</h6>
                                            <p class="mb-0">Total: 120 votos</p>
                                            <small class="text-muted">Estudiantes: 100</small><br>
                                            <small class="text-muted">Docentes: 20</small>
                                        </div>
                                        <div>
                                            <h6>Actividad</h6>
                                            <p class="mb-0">Accesos: 150</p>
                                            <p class="mb-0">Intentos bloqueados: 5</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Línea de tiempo de la elección -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-history"></i> Línea de Tiempo
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-container left">
                                <div class="timeline-content">
                                    <h4>Creación</h4>
                                    <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($eleccion['fecha_creacion'])); ?></p>
                                    <p>La elección fue creada y programada.</p>
                                </div>
                            </div>
                            
                            <?php if ($eleccion['estado'] !== 'programada'): ?>
                                <div class="timeline-container right">
                                    <div class="timeline-content">
                                        <h4>Activación</h4>
                                        <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($eleccion['fecha_inicio'])); ?></p>
                                        <p>La elección fue activada y comenzó el periodo de votación.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($eleccion['estado'] === 'cerrada' || $eleccion['estado'] === 'cancelada'): ?>
                                <div class="timeline-container left">
                                    <div class="timeline-content">
                                        <h4><?php echo $eleccion['estado'] === 'cerrada' ? 'Cierre' : 'Cancelación'; ?></h4>
                                        <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($eleccion['fecha_actualizacion'])); ?></p>
                                        <p>La elección fue <?php echo $eleccion['estado'] === 'cerrada' ? 'cerrada' : 'cancelada'; ?>.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Logs de acceso -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-list"></i> Logs de Acceso
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Usuario</th>
                                        <th>Tipo</th>
                                        <th>Acción</th>
                                        <th>Motivo</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No hay logs registrados para esta elección.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $log): ?>
                                            <?php 
                                            $logClass = '';
                                            switch ($log['accion']) {
                                                case 'intento_login':
                                                    $logClass = 'log-intento';
                                                    break;
                                                case 'login_exitoso':
                                                    $logClass = 'log-exitoso';
                                                    break;
                                                case 'login_bloqueado':
                                                    $logClass = 'log-bloqueado';
                                                    break;
                                                case 'voto_registrado':
                                                    $logClass = 'log-voto';
                                                    break;
                                            }
                                            ?>
                                            <tr class="<?php echo $logClass; ?>">
                                                <td class="log-time"><?php echo date('d/m/Y H:i:s', strtotime($log['fecha_evento'])); ?></td>
                                                <td class="log-user">
                                                    <?php echo htmlspecialchars($log['nombre_usuario'] ?? $log['id_usuario']); ?>
                                                    <small class="text-muted d-block"><?php echo ucfirst($log['tipo_usuario']); ?></small>
                                                </td>
                                                <td><?php echo ucfirst($log['tipo_usuario']); ?></td>
                                                <td class="log-action">
                                                    <?php 
                                                    switch ($log['accion']) {
                                                        case 'intento_login':
                                                            echo '<span class="badge bg-warning">Intento de acceso</span>';
                                                            break;
                                                        case 'login_exitoso':
                                                            echo '<span class="badge bg-success">Acceso exitoso</span>';
                                                            break;
                                                        case 'login_bloqueado':
                                                            echo '<span class="badge bg-danger">Acceso bloqueado</span>';
                                                            break;
                                                        case 'voto_registrado':
                                                            echo '<span class="badge bg-info">Voto registrado</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge bg-secondary">' . ucfirst($log['accion']) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['motivo'] ?? '-'); ?></td>
                                                <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
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
