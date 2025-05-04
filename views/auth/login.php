<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$status = "";
if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo'])) {
    $status = "mensaje";
    $mensaje = $_SESSION['mensaje'];
    $tipo = $_SESSION['tipo'];
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Administrativo - Sistema de Votación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .card {
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border: none;
            border-radius: 10px;
        }
        .card-header {
            background-color: #343a40;
            color: white;
            font-weight: bold;
            padding: 1.2rem;
            border-radius: 10px 10px 0 0;
        }
        .btn-admin {
            background-color: #343a40;
            color: white;
            border: none;
            padding: 0.8rem;
            font-weight: 500;
            border-radius: 5px;
        }
        .btn-admin:hover {
            background-color: #212529;
            color: white;
        }
        .form-control:focus {
            border-color: #343a40;
            box-shadow: 0 0 0 0.25rem rgba(52, 58, 64, 0.25);
        }
        .admin-icon {
            font-size: 3rem;
            color: #343a40;
            margin-bottom: 1rem;
        }
    </style>
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
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">Panel Administrativo</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-shield admin-icon"></i>
                            <h5>Ingreso Exclusivo para Administradores</h5>
                            <p class="text-muted">Acceso al panel de control del sistema de votación</p>
                        </div>
                        <form method="POST" action="/Login/admin/autenticar">
                            <div class="mb-3">
                                <label class="form-label">Usuario</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" 
                                           name="usuario" 
                                           class="form-control form-control-lg" 
                                           placeholder="Nombre de usuario" 
                                           required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" 
                                           name="password" 
                                           class="form-control form-control-lg" 
                                           placeholder="Contraseña" 
                                           required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-admin w-100 mb-3">
                                Acceder al Sistema
                            </button>
                            <div class="d-flex justify-content-between">
                                <a href="/Login/" class="text-decoration-none text-muted">
                                    <i class="fas fa-arrow-left me-1"></i> Volver al Inicio
                                </a>
                                <a href="#" class="text-decoration-none text-muted">
                                    ¿Olvidó su contraseña?
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>