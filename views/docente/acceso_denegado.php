<?php
// Verificar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado (docente o administrativo)
if ((!isset($_SESSION['es_docente']) || $_SESSION['es_docente'] !== true) &&
    (!isset($_SESSION['es_administrativo']) || $_SESSION['es_administrativo'] !== true)) {
    header("Location: /Login/docente/login");
    exit();
}

// Obtener información del usuario (docente o administrativo)
if (isset($_SESSION['es_administrativo']) && $_SESSION['es_administrativo'] === true) {
    $nombre_usuario = $_SESSION['administrativo_nombre'];
    $tipo_usuario = 'Administrativo';
} else {
    $nombre_usuario = $_SESSION['docente_nombre'];
    $tipo_usuario = 'Docente';
}

// Obtener información sobre el estado de las elecciones
use utils\EleccionMiddleware;
$estadoElecciones = EleccionMiddleware::obtenerMensajeEstado();
$informacionEleccion = EleccionMiddleware::obtenerInformacionEleccion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a Votación - <?php echo $tipo_usuario; ?>s</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: #343a40;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .welcome-text {
            color: #fff;
            font-weight: 500;
        }
        .btn-cerrar-sesion {
            color: #fff;
            text-decoration: none;
            background-color: #dc3545;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-cerrar-sesion:hover {
            background-color: #c82333;
            color: #fff;
        }
        .content-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 30px;
        }
        .status-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .status-warning {
            color: #ffc107;
        }
        .status-info {
            color: #0dcaf0;
        }
        .status-danger {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Votación</a>
            <div class="d-flex text-white align-items-center">
                <span class="welcome-text">Bienvenido: <?php echo htmlspecialchars($nombre_usuario); ?> (<?php echo $tipo_usuario; ?>)</span>
                <a class="btn-cerrar-sesion ms-3" href="/Login/docente/cerrar-sesion"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container content-container">
        <!-- Alertas y Mensajes -->
        <?php if(isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['mensaje'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
            // Limpiar mensajes después de mostrarlos
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo']);
            ?>
        <?php endif; ?>

        <div class="text-center">
            <?php if($estadoElecciones['estado'] === 'sin_elecciones'): ?>
                <i class="fas fa-info-circle status-icon status-info"></i>
                <h2 class="mb-3">No hay elecciones programadas</h2>
                <p class="lead">Actualmente no hay elecciones programadas en el sistema.</p>
                <p>Contacte al administrador para más información sobre próximas elecciones.</p>
            <?php elseif($estadoElecciones['estado'] === 'programada'): ?>
                <i class="fas fa-clock status-icon status-warning"></i>
                <h2 class="mb-3">Elecciones no iniciadas</h2>
                <p class="lead"><?= $estadoElecciones['mensaje'] ?></p>
                <?php if($informacionEleccion): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Información de la Próxima Elección</h5>
                        </div>
                        <div class="card-body">
                            <h6><?= htmlspecialchars($informacionEleccion['nombre']) ?></h6>
                            <p><?= htmlspecialchars($informacionEleccion['descripcion']) ?></p>
                            <p><strong>Inicio:</strong> <?= date('d/m/Y H:i', strtotime($informacionEleccion['fecha_inicio'])) ?></p>
                            <p><strong>Cierre:</strong> <?= date('d/m/Y H:i', strtotime($informacionEleccion['fecha_cierre'])) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php elseif($estadoElecciones['estado'] === 'finalizada'): ?>
                <i class="fas fa-check-circle status-icon status-info"></i>
                <h2 class="mb-3">Elecciones finalizadas</h2>
                <p class="lead"><?= $estadoElecciones['mensaje'] ?></p>
                <?php if($informacionEleccion): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Información de la Elección Finalizada</h5>
                        </div>
                        <div class="card-body">
                            <h6><?= htmlspecialchars($informacionEleccion['nombre']) ?></h6>
                            <p><?= htmlspecialchars($informacionEleccion['descripcion']) ?></p>
                            <p><strong>Inicio:</strong> <?= date('d/m/Y H:i', strtotime($informacionEleccion['fecha_inicio'])) ?></p>
                            <p><strong>Cierre:</strong> <?= date('d/m/Y H:i', strtotime($informacionEleccion['fecha_cierre'])) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <i class="fas fa-ban status-icon status-danger"></i>
                <h2 class="mb-3">Acceso no permitido</h2>
                <p class="lead">Su tipo de usuario no está habilitado para participar en la elección actual.</p>
                <?php if(isset($_SESSION['motivo_acceso_denegado'])): ?>
                    <div class="alert alert-warning">
                        <strong>Motivo:</strong> <?= htmlspecialchars($_SESSION['motivo_acceso_denegado']) ?>
                    </div>
                    <?php unset($_SESSION['motivo_acceso_denegado']); ?>
                <?php endif; ?>
                
                <?php if($informacionEleccion): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información de la Elección Actual</h5>
                        </div>
                        <div class="card-body">
                            <h6><?= htmlspecialchars($informacionEleccion['nombre']) ?></h6>
                            <p><?= htmlspecialchars($informacionEleccion['descripcion']) ?></p>
                            <p><strong>Tipos de usuario habilitados:</strong> 
                                <?php 
                                $tipos = $informacionEleccion['tipos_votacion'] ?? [];
                                echo !empty($tipos) ? implode(', ', array_map('ucfirst', $tipos)) : 'Ninguno';
                                ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <a href="/Login/docente/cerrar-sesion" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
            </a>
        </div>
    </div>

    <!-- Scripts de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-ocultar alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alertas = document.querySelectorAll('.alert');
            alertas.forEach(function(alerta) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alerta);
                    bsAlert.close();
                }, 5000);
            });
            
            // Refrescar la página cada 30 segundos para verificar si la elección ya comenzó
            setInterval(function() {
                location.reload();
            }, 30000);
        });
    </script>
</body>
</html>