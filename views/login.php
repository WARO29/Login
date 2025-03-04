<?php
// Verificar si la sesión no está iniciada antes de iniciarla
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$status = "";
// Resto del código para mostrar mensajes
if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo'])) {
    $status = "mensaje";
    $mensaje = $_SESSION['mensaje'];
    $tipo = $_SESSION['tipo'];
    // Limpiar mensajes después de mostrarlos
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
    <div class="container">
    <?php if($status == "mensaje"): ?>
        <div class="alert alert-<?= $tipo ?> alert-dismissible fade show" role="alert">
                <?= $mensaje ?> 
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header text-center">Iniciar Sesión</div>
                    <div class="card-body">
                        <form method="POST" action="index.php">
                            <div class="mb-3">
                                <label class="form-label">Documento</label>
                                <input type="text" name="documento" class="form-control" placeholder="Documento" required>
                            </div>
                            <!--<a href="/Ingreso_estudiantes.php">Registrar Estudiante</a>-->
                            <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 