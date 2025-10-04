<?php
// Página de estado de elecciones para votantes
require_once 'utils/EleccionMiddleware.php';

// Obtener información de estado
$estadoElecciones = utils\EleccionMiddleware::obtenerMensajeEstado();
$tiempoRestante = utils\EleccionMiddleware::obtenerTiempoRestante();
$informacionEleccion = utils\EleccionMiddleware::obtenerInformacionEleccion();

// Determinar el tipo de usuario si está autenticado
$tipoUsuario = null;
$idUsuario = null;

if (isset($_SESSION['estudiante_id'])) {
    $tipoUsuario = 'estudiante';
    $idUsuario = $_SESSION['estudiante_id'];
} elseif (isset($_SESSION['docente_id'])) {
    $tipoUsuario = 'docente';
    $idUsuario = $_SESSION['docente_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $tipoUsuario = 'administrador';
    $idUsuario = $_SESSION['admin_id'];
}

// Verificar si puede votar (solo si es un votante autenticado)
$puedeVotar = null;
if ($tipoUsuario && $tipoUsuario !== 'administrador') {
    $puedeVotar = utils\EleccionMiddleware::puedeVotar($tipoUsuario, $idUsuario);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Elecciones - Sistema de Votación</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .countdown {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 1rem 0;
        }
        .card-status {
            margin-top: 2rem;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-status .card-header {
            font-weight: bold;
            font-size: 1.2rem;
        }
        .status-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .status-active {
            color: #28a745;
        }
        .status-scheduled {
            color: #17a2b8;
        }
        .status-closed {
            color: #6c757d;
        }
        .btn-vote {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="text-center mb-4">Estado de Elecciones</h1>
                
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
                <?php endif; ?>
                
                <?php if ($estadoElecciones['estado'] === 'activa'): ?>
                    <!-- Elecciones activas -->
                    <div class="card card-status">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-vote-yea"></i> Elecciones Activas
                        </div>
                        <div class="card-body text-center">
                            <div class="status-icon status-active">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($informacionEleccion['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars($informacionEleccion['descripcion']); ?></p>
                            <div class="countdown" id="countdown">
                                Tiempo restante: <?php echo $tiempoRestante['formato_humano']; ?>
                            </div>
                            <p class="text-muted">
                                Las elecciones finalizarán el 
                                <?php echo date('d/m/Y', strtotime($informacionEleccion['fecha_cierre'])); ?> 
                                a las 
                                <?php echo date('H:i', strtotime($informacionEleccion['fecha_cierre'])); ?>
                            </p>
                            
                            <?php if ($tipoUsuario && $tipoUsuario !== 'administrador'): ?>
                                <?php if ($puedeVotar['puede_votar']): ?>
                                    <a href="/Login/<?php echo $tipoUsuario; ?>/panel" class="btn btn-success btn-lg btn-vote">
                                        <i class="fas fa-vote-yea"></i> Votar Ahora
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <?php echo $puedeVotar['motivo']; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Debe iniciar sesión para poder votar.
                                </div>
                                <div class="d-flex justify-content-center">
                                    <a href="/Login/" class="btn btn-primary me-2">
                                        <i class="fas fa-user-graduate"></i> Estudiantes
                                    </a>
                                    <a href="/Login/docente/login" class="btn btn-secondary me-2">
                                        <i class="fas fa-chalkboard-teacher"></i> Docentes
                                    </a>
                                    <a href="/Login/admin/login" class="btn btn-dark">
                                        <i class="fas fa-user-shield"></i> Administradores
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($estadoElecciones['estado'] === 'programada'): ?>
                    <!-- Elecciones programadas -->
                    <div class="card card-status">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-calendar-alt"></i> Elecciones Programadas
                        </div>
                        <div class="card-body text-center">
                            <div class="status-icon status-scheduled">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($informacionEleccion['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars($informacionEleccion['descripcion']); ?></p>
                            <div class="countdown" id="countdown">
                                Tiempo para inicio: <?php echo $tiempoRestante['formato_humano']; ?>
                            </div>
                            <p class="text-muted">
                                Las elecciones comenzarán el 
                                <?php echo date('d/m/Y', strtotime($informacionEleccion['fecha_inicio'])); ?> 
                                a las 
                                <?php echo date('H:i', strtotime($informacionEleccion['fecha_inicio'])); ?>
                            </p>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Las elecciones aún no han comenzado. Vuelva en la fecha y hora indicadas.
                            </div>
                            
                            <div class="d-flex justify-content-center">
                                <a href="/Login/" class="btn btn-primary me-2">
                                    <i class="fas fa-home"></i> Volver al Inicio
                                </a>
                                <button class="btn btn-secondary" id="btnNotificarme">
                                    <i class="fas fa-bell"></i> Notificarme cuando comience
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Sin elecciones -->
                    <div class="card card-status">
                        <div class="card-header bg-secondary text-white">
                            <i class="fas fa-info-circle"></i> Sin Elecciones Programadas
                        </div>
                        <div class="card-body text-center">
                            <div class="status-icon status-closed">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>No hay elecciones activas</h3>
                            <p>En este momento no hay elecciones programadas en el sistema.</p>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Consulte con el administrador para más información sobre las próximas elecciones.
                            </div>
                            
                            <a href="/Login/" class="btn btn-primary">
                                <i class="fas fa-home"></i> Volver al Inicio
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Enlaces rápidos -->
                <div class="card mt-4">
                    <div class="card-header">
                        Enlaces Rápidos
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <a href="/Login/" class="btn btn-outline-primary btn-sm d-block mb-2">
                                    <i class="fas fa-home"></i> Inicio
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="/Login/resultados" class="btn btn-outline-info btn-sm d-block mb-2">
                                    <i class="fas fa-chart-bar"></i> Resultados
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="/Login/contacto" class="btn btn-outline-secondary btn-sm d-block mb-2">
                                    <i class="fas fa-envelope"></i> Contacto
                                </a>
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
        // Actualizar contador en tiempo real
        function actualizarContador() {
            $.ajax({
                url: '/Login/api/verificar-disponibilidad-votacion',
                type: 'GET',
                dataType: 'json',
                data: {
                    tipo_usuario: '<?php echo $tipoUsuario ?? ""; ?>',
                    id_usuario: '<?php echo $idUsuario ?? ""; ?>'
                },
                success: function(data) {
                    if (data && data.tiempo_restante) {
                        $('#countdown').text(data.tiempo_restante.formato_humano);
                    }
                    
                    // Si el estado cambió, recargar la página
                    if (data && data.estado !== '<?php echo $estadoElecciones['estado']; ?>') {
                        location.reload();
                    }
                }
            });
        }
        
        // Actualizar cada 60 segundos
        $(document).ready(function() {
            setInterval(actualizarContador, 60000);
            
            // Botón de notificación (solo para demostración)
            $('#btnNotificarme').click(function() {
                alert('Esta función estará disponible próximamente. Recibirá una notificación cuando comiencen las elecciones.');
            });
        });
    </script>
</body>
</html>


