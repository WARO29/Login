<?php
// Verificar si la sesión no está iniciada antes de iniciarla
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*require_once __DIR__ . '/../autoload.php';*/



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
    <title>Login Docente - Sistema de Votación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border: none;
            border-radius: 10px;
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            font-weight: bold;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 0.8rem;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
    <div class="container">
        <?php if(isset($status) && $status == "mensaje"): ?>
            <div class="alert alert-<?= $tipo ?> alert-dismissible fade show" role="alert">
                <?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">Acceso Docente</h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="/Login/docente/autenticar">
                            <div class="mb-4">
                                <label class="form-label">Número de Cédula</label>
                                <input type="text" 
                                       name="documento" 
                                       class="form-control form-control-lg" 
                                       placeholder="Ingrese su número de cédula" 
                                       required 
                                       pattern="[0-9]+"
                                       title="Por favor ingrese solo números"
                                       maxlength="10">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                Ingresar al Sistema
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 