<?php
// Verificar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el docente está autenticado
if (!isset($_SESSION['es_docente']) || $_SESSION['es_docente'] !== true) {
    header("Location: /Login/docente/login");
    exit();
}

// Incluir las clases necesarias
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__.'/../../config/config.php';

// Obtener información del docente
$nombre_docente = $_SESSION['docente_nombre'];

// Los representantes ahora vienen del controlador, no se hace la consulta aquí
// La variable $representantes debe estar disponible desde el controlador
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarjeton de Votaciones - Docentes.</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #343a40;
        }
        .content-container {
            margin-top: 30px;
            padding: 20px;
        }
        .btn-cerrar-sesion {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-cerrar-sesion:hover {
            background-color: #dc3545;
            color: white;
            text-decoration: none;
        }
        .welcome-text {
            display: flex;
            align-items: center;
        }
        .candidate-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            transition: transform 0.2s;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .candidate-card:hover {
            transform: translateY(-5px);
        }
        .candidate-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: block;
            border: 3px solid #0d6efd;
        }
        .candidate-info {
            text-align: center;
        }
        .candidate-name {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        .candidate-position {
            color: #6c757d;
            font-style: italic;
            margin-bottom: 10px;
        }
        .vote-button {
            width: 100%;
            margin-top: 15px;
        }
        .section-title {
            color: #2c3e50;
            margin: 30px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        .blank-vote {
            background-color: #f8f9fa;
            text-align: center;
            padding: 20px;
            border: 2px solid #6c757d !important;
            position: relative;
            overflow: hidden;
        }
        .blank-vote::before {
            content: "Opción Democrática";
            position: absolute;
            top: 10px;
            right: -35px;
            background-color: #17a2b8;
            color: white;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-size: 12px;
            font-weight: bold;
            z-index: 1;
        }
        .blank-vote-info {
            background-color: rgba(108, 117, 125, 0.1);
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        .candidate-proposal {
            margin-top: 15px;
            text-align: center;
        }
        .candidate-proposal a {
            color: #0d6efd;
            text-decoration: none;
        }
        .candidate-proposal a:hover {
            text-decoration: underline;
        }
        .vote-instructions {
            background-color: #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .vote-summary {
            background-color: rgba(0, 0, 0, 0.03);
            border-radius: 5px;
            padding: 20px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Votación</a>
            <div class="d-flex text-white align-items-center">
                <span class="welcome-text">Bienvenido: <?php echo htmlspecialchars($nombre_docente); ?></span>
                <a class="btn-cerrar-sesion ms-3" href="/Login/docente/cerrar-sesion"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container content-container">
        <!-- Instrucciones de votación -->
        <div class="vote-instructions">
            <h4><i class="fas fa-info-circle me-2"></i>Instrucciones para Votar</h4>
            <p>Bienvenido al sistema de votación para representantes docentes. Por favor, siga estos pasos:</p>
            <ol>
                <li>Revise la información de cada candidato y su propuesta.</li>
                <li>Seleccione al candidato de su preferencia haciendo clic en el botón "Votar".</li>
                <li>También puede elegir la opción de voto en blanco si así lo desea.</li>
                <li>Una vez emitido su voto, no podrá cambiarlo.</li>
            </ol>
        </div>

        <!-- Sección de Representantes Docentes -->
        <h3 class="section-title"><i class="fas fa-user-tie me-2"></i>Candidatos a Representante Docente</h3>
        
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
                            <?php if(!empty($representante['propuesta_repre_docente'])): ?>
                                <img src="<?= htmlspecialchars($representante['propuesta_repre_docente']) ?>" alt="Foto de <?= htmlspecialchars($representante['nombre_repre_docente']) ?>" class="candidate-image">
                            <?php else: ?>
                                <img src="/Login/assets/default-profile.jpg" alt="Imagen por defecto" class="candidate-image">
                            <?php endif; ?>
                            
                            <div class="candidate-info">
                                <div class="candidate-name"><?= htmlspecialchars($representante['nombre_repre_docente']) ?></div>
                                <div class="candidate-position"><?= htmlspecialchars($representante['cargo_repre_docente']) ?></div>
                                
                                <div class="candidate-details">
                                    <p><small><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($representante['correo_repre_docente']) ?></small></p>
                                    <?php if(!empty($representante['telefono_repre_docente'])): ?>
                                        <p><small><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($representante['telefono_repre_docente']) ?></small></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if(!empty($representante['propuesta_repre_docente'])): ?>
                                    <div class="candidate-proposal">
                                        <a href="<?= htmlspecialchars($representante['propuesta_repre_docente']) ?>" target="_blank">
                                            <i class="fas fa-file-alt me-1"></i> Ver Propuesta
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <button class="btn btn-primary vote-button" onclick="votar('<?= $representante['codigo_repres_docente'] ?>')">
                                    <i class="fas fa-vote-yea me-1"></i> Votar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Opción de voto en blanco -->
                <div class="col-md-4 mb-4">
                    <div class="candidate-card blank-vote">
                        <div class="text-center mb-3">
                            <i class="fas fa-vote-yea fa-4x text-secondary"></i>
                        </div>
                        <div class="blank-vote-info">
                            <p>El voto en blanco es una expresión política de disentimiento, abstención o inconformidad.</p>
                        </div>
                        <button class="btn btn-secondary vote-button" onclick="votarEnBlanco()">
                            <i class="fas fa-vote-yea me-1"></i> Votar en Blanco
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para manejar los votos -->
    <script>
        function votar(codigoRepresentante) {
            if(confirm('¿Está seguro de votar por este candidato? Esta acción no se puede deshacer.')) {
                // Aquí iría el código para registrar el voto
                alert('Su voto ha sido registrado. Gracias por participar.');
                // Redireccionar o mostrar confirmación
            }
        }
        
        function votarEnBlanco() {
            if(confirm('¿Está seguro de emitir un voto en blanco? Esta acción no se puede deshacer.')) {
                // Aquí iría el código para registrar el voto en blanco
                alert('Su voto en blanco ha sido registrado. Gracias por participar.');
                // Redireccionar o mostrar confirmación
            }
        }
    </script>
</body>
</html>
