<?php
// autoload.php se encarga de cargar todas las clases necesarias
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';

// Verificar si la sesión no está iniciada antes de iniciarla
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté autenticado como estudiante
if (!isset($_SESSION['estudiante_id']) || !isset($_SESSION['es_estudiante'])) {
    header("Location: /Login/");
    exit();
}

// Obtener datos del estudiante
$estudiante_id = $_SESSION['estudiante_id'];
$nombre = $_SESSION['nombre'];
$apellido = isset($_SESSION['apellido']) ? $_SESSION['apellido'] : '';
$nombre_completo = isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : "$nombre $apellido";
$grado = isset($_SESSION['grado']) ? $_SESSION['grado'] : '';

// Obtener información de los votos
$nombre_personero = isset($_SESSION['nombre_personero']) ? $_SESSION['nombre_personero'] : 'Desconocido';
$nombre_representante = isset($_SESSION['nombre_representante']) ? $_SESSION['nombre_representante'] : 'Desconocido';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmación de Voto - Sistema de Votación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .confirmation-card {
            max-width: 600px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        .confirmation-header {
            background-color: #28a745;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        .confirmation-body {
            padding: 2rem;
            text-align: center;
        }
        .confirmation-footer {
            background-color: #f8f9fa;
            padding: 1.5rem;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        @media print {
            body {
                background-color: #fff;
                display: block;
            }
            .confirmation-card {
                box-shadow: none;
                border: 1px solid #dee2e6;
                max-width: 100%;
                margin: 0 auto;
            }
            .confirmation-footer {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-card">
            <div class="confirmation-header">
                <i class="fas fa-check-circle success-icon"></i>
                <h2>¡Voto Registrado!</h2>
            </div>
            <div class="confirmation-body">
                <?php if(isset($_SESSION['mensaje_correo'])): ?>
                    <div class="alert alert-<?= $_SESSION['tipo_correo'] ?> alert-dismissible fade show" role="alert">
                        <?= $_SESSION['mensaje_correo'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje_correo']); unset($_SESSION['tipo_correo']); ?>
                <?php endif; ?>
                <h4>Gracias por participar, <?= htmlspecialchars($nombre_completo) ?></h4>
                <p class="lead">Tu voto ha sido registrado correctamente en nuestro sistema.</p>
                <p>El proceso de votación ha sido completado exitosamente. Tu participación es importante para nuestra democracia escolar.</p>
                
                <!-- Resumen de votos -->
                <div class="card my-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Resumen de tu votación</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6><i class="fas fa-user-tie me-2"></i>Personero</h6>
                                    <p class="mb-0 fw-bold text-primary"><?= htmlspecialchars($nombre_personero) ?></p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6><i class="fas fa-user-graduate me-2"></i>Representante</h6>
                                    <p class="mb-0 fw-bold text-primary"><?= htmlspecialchars($nombre_representante) ?></p>
                                    <p class="mb-0 small text-muted mt-1">Para Grado <?= htmlspecialchars($grado) ?></p>
                                    <?php if($nombre_representante == 'Voto en Blanco'): ?>
                                    <div class="mt-2 small alert alert-warning py-1 px-2 mb-0">
                                        <?php 
                                        // Consultar si hay candidatos para el grado del estudiante
                                        $database = new \config\Database();
                                        $db = $database->getConnection();
                                        $candidatosModel = new \models\Candidatos($db);
                                        $representantes = $candidatosModel->getCandidatosPorTipoYGrado('REPRESENTANTE', $grado);
                                        
                                        if(empty($representantes)): 
                                        ?>
                                            <i class="fas fa-info-circle"></i> No había candidatos disponibles para tu grado
                                        <?php else: ?>
                                            <i class="fas fa-check-circle"></i> Voto en blanco registrado correctamente
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="small text-muted text-center mt-2">
                            Los representantes son específicos para cada grado. Solo puedes votar por un representante de tu mismo grado.
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Este comprobante confirma que has ejercido tu derecho al voto. Recuerda que el voto es secreto.
                </div>
                
                <!-- ID único de verificación -->
                <div class="mt-4 p-3 bg-light rounded">
                    <small class="text-muted">ID de Verificación</small>
                    <div class="border p-2 bg-white rounded mt-1">
                        <?= strtoupper(substr(md5($estudiante_id . time()), 0, 12)) ?>
                    </div>
                    <small class="text-muted d-block mt-2">Fecha y hora: <?= date('d/m/Y H:i:s') ?></small>
                </div>
            </div>
            <div class="confirmation-footer">
                <a href="/Login/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print me-2"></i>Imprimir o Guardar PDF
                </button>
                <form action="/Login/enviar-confirmacion" method="POST" class="d-inline">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-envelope me-2"></i>Enviar por Correo
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>