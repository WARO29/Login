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

// Incluir las clases necesarias
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__.'/../../config/config.php';

// Obtener información del usuario (docente o administrativo)
if (isset($_SESSION['es_administrativo']) && $_SESSION['es_administrativo'] === true) {
    $nombre_usuario = $_SESSION['administrativo_nombre'];
    $tipo_usuario = 'Administrativo';
} else {
    $nombre_usuario = $_SESSION['docente_nombre'];
    $tipo_usuario = 'Docente';
}

// Los representantes ahora vienen del controlador, no se hace la consulta aquí
// La variable $representantes debe estar disponible desde el controlador
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarjeton de Votaciones - <?php echo $tipo_usuario; ?>s</title>
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
            max-width: 1140px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 30px;
        }
        .vote-instructions {
            background-color: #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .section-title {
            color: #343a40;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .section-title i {
            margin-right: 10px;
            color: #007bff;
        }
        .candidate-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            background-color: #fff;
        }
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .vote-button {
            width: 100%;
            border-radius: 4px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .vote-button i {
            margin-right: 5px;
        }
        .blank-vote {
            position: relative;
            overflow: hidden;
        }
        .voto-blanco-ribbon {
            position: absolute;
            top: 15px;
            right: -35px;
            background-color: #17a2b8;
            color: white;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-size: 12px;
            font-weight: bold;
            z-index: 1;
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
        
        <?php if($yaVoto): ?>
        <!-- Estado de votación cuando ya ha votado -->
        <div class="alert alert-success mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div>
                    <h4 class="mb-1">Representante: Voto registrado</h4>
                </div>
            </div>
        </div>
        
        <div class="alert alert-success">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <h4 class="mb-0">¡Votación completa!</h4>
            </div>
            <p>Has completado tu votación para representante docente. Tu voto ha sido registrado correctamente.</p>
            <button class="btn btn-success w-100" onclick="verConfirmacion()">
                <i class="fas fa-check-circle me-2"></i> Ver confirmación
            </button>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Resumen de tu votación</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <strong>Representante:</strong> Has ejercido tu voto <?= isset($_SESSION['nombre_representante']) ? 'por ' . htmlspecialchars($_SESSION['nombre_representante']) : '' ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Instrucciones de votación -->
        <div class="vote-instructions">
            <h4><i class="fas fa-info-circle me-2"></i>Instrucciones para Votar</h4>
            <p>Bienvenido al sistema de votación para representantes docentes. Como <?php echo strtolower($tipo_usuario); ?>, usted puede participar en la elección. Por favor, siga estos pasos:</p>
            <ol>
                <li>Revise la información de cada candidato y su propuesta.</li>
                <li>Seleccione al candidato de su preferencia haciendo clic en el botón "Votar".</li>
                <li>También puede elegir la opción de voto en blanco si así lo desea.</li>
                <li>Una vez emitido su voto, no podrá cambiarlo.</li>
            </ol>
        </div>
        <?php endif; ?>
        
        <?php if(!$yaVoto): ?>
        <!-- Sección de Candidatos a Representante Docente -->
        <h3 class="section-title"><i class="fas fa-user-tie"></i>Candidatos a Representante Docente</h3>
        
        <div class="row">
            <?php if(empty($representantes)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <h4><i class="fas fa-info-circle me-2"></i>Información</h4>
                        <p>Actualmente no hay candidatos registrados para representante docente.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach($representantes as $representante): ?>
                    <div class="col-md-4 mb-4">
                        <div class="candidate-card">
                            <div class="text-center p-4">
                                <img src="/Login/views/docente/img/foto_victor.jpg" alt="Foto de <?= htmlspecialchars($representante['nombre_repre_docente']) ?>" 
                                     class="rounded-circle img-fluid mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                <h4 class="mb-1"><?= htmlspecialchars($representante['nombre_repre_docente']) ?></h4>
                                <p class="text-muted mb-3"><?= htmlspecialchars($representante['cargo_repre_docente']) ?></p>
                                
                                <div class="d-flex justify-content-center mb-3">
                                    <div class="mx-2">
                                        <i class="fas fa-envelope text-muted"></i>
                                        <small><?= htmlspecialchars($representante['correo_repre_docente']) ?></small>
                                    </div>
                                </div>
                                
                                <?php if(!empty($representante['telefono_repre_docente'])): ?>
                                    <div class="mb-3">
                                        <i class="fas fa-phone text-muted"></i>
                                        <small><?= htmlspecialchars($representante['telefono_repre_docente']) ?></small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <a href="#" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file-alt me-1"></i> Ver Propuesta
                                    </a>
                                </div>
                                
                                <form action="/Login/docente/procesar_voto" method="POST">
                                    <input type="hidden" name="codigo_representante" value="<?= htmlspecialchars($representante['codigo_repres_docente']) ?>">
                                    <button type="submit" class="btn btn-primary vote-button">
                                        <i class="fas fa-vote-yea"></i> Votar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Opción de voto en blanco -->
                <div class="col-md-4 mb-4">
                    <div class="candidate-card blank-vote">
                        <div class="voto-blanco-ribbon">Voto Blanco</div>
                        <div class="text-center p-4">
                            <div class="p-4">
                                <i class="fas fa-check-square fa-5x text-secondary mb-3"></i>
                                <h4 class="mb-3">Voto en Blanco</h4>
                                <p class="mb-4">El voto en blanco es una expresión política de disentimiento, abstención o inconformidad.</p>
                            </div>
                        </div>
                        <form action="/Login/docente/procesar_voto" method="POST">
                            <input type="hidden" name="voto_blanco" value="1">
                            <button type="submit" class="btn btn-secondary vote-button">
                                <i class="fas fa-vote-yea"></i> Votar en Blanco
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Scripts de Bootstrap y jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Función para mostrar la confirmación de voto
        function verConfirmacion() {
            // Crear un modal de confirmación
            const modalHtml = `
                <div class="modal fade" id="confirmacionModal" tabindex="-1" aria-labelledby="confirmacionModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="confirmacionModalLabel"><i class="fas fa-check-circle me-2"></i>Confirmación de Voto</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center p-4">
                                <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                                <h4>¡Voto Registrado Exitosamente!</h4>
                                <p class="mb-0">Tu voto ha sido registrado correctamente en nuestro sistema.</p>
                                <p>Gracias por participar en este proceso democrático.</p>
                                <div class="alert alert-light border mt-3">
                                    <small class="text-muted">Fecha y hora: ${new Date().toLocaleString()}</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <a href="/Login/docente/cerrar-sesion" class="btn btn-primary">Cerrar Sesión</a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Añadir el modal al DOM si no existe
            if ($('#confirmacionModal').length === 0) {
                $('body').append(modalHtml);
            }
            
            // Mostrar el modal
            const confirmacionModal = new bootstrap.Modal(document.getElementById('confirmacionModal'));
            confirmacionModal.show();
        }
        
        // Función para confirmar el voto antes de enviarlo
        $(document).ready(function() {
            // Interceptar todos los formularios de votación
            $('form[action="/Login/docente/procesar_voto"]').on('submit', function(e) {
                e.preventDefault();
                
                // Determinar si es voto en blanco o por un candidato
                const esVotoBlanco = $(this).find('input[name="voto_blanco"]').length > 0;
                let mensaje = '';
                
                if (esVotoBlanco) {
                    mensaje = '¿Está seguro de que desea emitir un voto en blanco? Esta acción no se puede deshacer.';
                } else {
                    const nombreCandidato = $(this).closest('.candidate-card').find('.mb-1').text();
                    mensaje = `¿Está seguro de que desea votar por ${nombreCandidato}? Esta acción no se puede deshacer.`;
                }
                
                // Mostrar confirmación
                if (confirm(mensaje)) {
                    this.submit();
                }
            });
            
            // Mostrar alertas automáticamente y ocultarlas después de 5 segundos
            const alertas = $('.alert:not(.alert-success)');
            if (alertas.length > 0) {
                setTimeout(function() {
                    alertas.each(function() {
                        $(this).alert('close');
                    });
                }, 5000);
            }
            
            // Refrescar la página cada 30 segundos para verificar si la elección ya comenzó
            <?php if(!$yaVoto): ?>
            setInterval(function() {
                location.reload();
            }, 30000);
            <?php endif; ?>
        });
    </script>
</body>
</html>
