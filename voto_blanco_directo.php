<?php
// Verificar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['documento'])) {
    header("Location: /Login/index.php");
    exit();
}

require_once 'autoload.php';
require_once 'config/config.php';

use models\Votos;
use config\Database;

$votosModel = new Votos();
$id_estudiante = $_SESSION['estudiante_id'];
$votoPersonero = $votosModel->haVotadoPorTipo($id_estudiante, 'PERSONERO');
$votoRepresentante = $votosModel->haVotadoPorTipo($id_estudiante, 'REPRESENTANTE');

// Procesar el formulario de voto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_voto = $_POST['tipo_voto'] ?? '';
    
    if (in_array($tipo_voto, ['PERSONERO', 'REPRESENTANTE'])) {
        try {
            // Verificar que no haya votado ya
            if (($tipo_voto === 'PERSONERO' && $votoPersonero) || 
                ($tipo_voto === 'REPRESENTANTE' && $votoRepresentante)) {
                $mensaje = "Ya has votado por este tipo de candidato.";
                $tipo = "warning";
            } else {
                // Registrar voto directo
                $database = new Database();
                $conn = $database->getConnection();
                
                $query = "INSERT INTO votos (id_estudiante, id_candidato, tipo_voto) VALUES (?, NULL, ?)";
                $stmt = $conn->prepare($query);
                
                if (!$stmt) {
                    throw new Exception("Error en la preparación de la consulta: " . $conn->error);
                }
                
                $stmt->bind_param("is", $id_estudiante, $tipo_voto);
                $result = $stmt->execute();
                
                if ($result) {
                    $mensaje = "Voto en blanco registrado correctamente para " . strtolower($tipo_voto);
                    $tipo = "success";
                    
                    // Actualizar el estado de los votos
                    if ($tipo_voto === 'PERSONERO') {
                        $votoPersonero = true;
                        $_SESSION['nombre_personero'] = 'Voto en Blanco';
                    } else {
                        $votoRepresentante = true;
                        $_SESSION['nombre_representante'] = 'Voto en Blanco';
                    }
                    
                    // Redireccionar si ya votó por ambos tipos
                    if ($votoPersonero && $votoRepresentante) {
                        header("Location: /Login/views/confirmacion.php");
                        exit();
                    }
                } else {
                    throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
                }
            }
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo = "danger";
        }
    } else {
        $mensaje = "Tipo de voto inválido.";
        $tipo = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Voto en Blanco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Prueba de Voto en Blanco</h1>
        
        <?php if (isset($mensaje)): ?>
        <div class="alert alert-<?= $tipo ?> alert-dismissible fade show" role="alert">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Voto en Blanco para Personero
                    </div>
                    <div class="card-body">
                        <p>Estado: <?= $votoPersonero ? '<span class="badge bg-success">Votado</span>' : '<span class="badge bg-warning">Pendiente</span>' ?></p>
                        <?php if (!$votoPersonero): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="tipo_voto" value="PERSONERO">
                            <button type="submit" class="btn btn-primary">Votar en Blanco</button>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-info">Ya has votado para personero.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Voto en Blanco para Representante
                    </div>
                    <div class="card-body">
                        <p>Estado: <?= $votoRepresentante ? '<span class="badge bg-success">Votado</span>' : '<span class="badge bg-warning">Pendiente</span>' ?></p>
                        <?php if (!$votoRepresentante): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="tipo_voto" value="REPRESENTANTE">
                            <button type="submit" class="btn btn-primary">Votar en Blanco</button>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-info">Ya has votado para representante.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="/Login/views/estudiantes/votos.php" class="btn btn-secondary">Volver a la página de votación</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 