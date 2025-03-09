<?php
// votos.php
// Verificar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['documento'])) {
    header("Location: /Login/index.php");
    exit();
}

// Incluir las clases necesarias

require_once __DIR__ . '/../autoload.php';

//require_once '../models/Candidatos.php';
require_once '../config/config.php';

use models\Candidatos;
use config\Database;

// Obtener los candidatos
$database = new Database();
$db = $database->getConnection();
$candidatosModel = new Candidatos($db);

// Obtener candidatos a Personero y Representante
$personeros = $candidatosModel->getCandidatosPorTipo('PERSONERO');
$representantes = $candidatosModel->getCandidatosPorTipo('REPRESENTANTE');

// Generar token CSRF para protección contra ataques CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarjetón de Votación</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .candidate-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .candidate-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        .vote-button {
            width: 100%;
            margin-top: 10px;
        }
        .section-title {
            color: #2c3e50;
            margin: 30px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        .navbar {
            margin-bottom: 30px;
        }
        .blank-vote {
            background-color: #f8f9fa;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Votación</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Bienvenido: <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>
                </span>
                <a href="/Login/index.php?controller=auth&action=logout" class="btn btn-danger">
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Sección de Personero -->
        <h2 class="section-title">Candidatos a Personero</h2>
        <div class="row">
            <?php if (empty($personeros)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No hay candidatos a personero disponibles en este momento.</div>
                </div>
            <?php else: ?>
                <?php foreach ($personeros as $personero): ?>
                <div class="col-md-4">
                    <div class="candidate-card text-center">
                        <img src="<?php echo htmlspecialchars($personero['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($personero['nombre']); ?>" 
                            class="candidate-image">
                        <h4><?php echo htmlspecialchars($personero['nombre'] . ' ' . $personero['apellido']); ?></h4>
                        <p class="text-muted">Número: <?php echo htmlspecialchars($personero['numero']); ?></p>
                        <form action="/Login/index.php?controller=votos&action=votar" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="candidato_id" value="<?php echo (int)$personero['id_candidato']; ?>">
                            <input type="hidden" name="tipo_voto" value="PERSONERO">
                            <button type="submit" class="btn btn-primary vote-button">
                                Votar
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Voto en Blanco - Personero -->
            <div class="col-md-4">
                <div class="candidate-card blank-vote">
                    <h4>Voto en Blanco</h4>
                    <p>Personero</p>
                    <form action="/Login/index.php?controller=votos&action=votarEnBlanco" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="tipo_voto" value="PERSONERO">
                        <button type="submit" class="btn btn-secondary vote-button">
                            Votar en Blanco
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sección de Representante -->
        <h2 class="section-title">Candidatos a Representante</h2>
        <div class="row">
            <?php if (empty($representantes)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No hay candidatos a representante disponibles en este momento.</div>
                </div>
            <?php else: ?>
                <?php foreach ($representantes as $representante): ?>
                <div class="col-md-4">
                    <div class="candidate-card text-center">
                        <img src="<?php echo htmlspecialchars($representante['foto']); ?>" 
                             alt="Foto de <?php echo htmlspecialchars($representante['nombre']); ?>" 
                             class="candidate-image">
                        <h4><?php echo htmlspecialchars($representante['nombre'] . ' ' . $representante['apellido']); ?></h4>
                        <p class="text-muted">Número: <?php echo htmlspecialchars($representante['numero']); ?></p>
                        <form action="/Login/index.php?controller=votos&action=votar" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="candidato_id" value="<?php echo (int)$representante['id_candidato']; ?>">
                            <input type="hidden" name="tipo_voto" value="REPRESENTANTE">
                            <button type="submit" class="btn btn-primary vote-button">
                                Votar
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Voto en Blanco - Representante -->
            <div class="col-md-4">
                <div class="candidate-card blank-vote">
                    <h4>Voto en Blanco</h4>
                    <p>Representante</p>
                    <form action="/Login/index.php?controller=votos&action=votarEnBlanco" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="tipo_voto" value="REPRESENTANTE">
                        <button type="submit" class="btn btn-secondary vote-button">
                            Votar en Blanco
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Confirmación de voto -->
    <script>
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (confirm('¿Está seguro de su voto? Esta acción no se puede deshacer.')) {
                this.submit();
            }
        });
    });
    </script>
</body>
</html>