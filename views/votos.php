<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistemas de Votacion - 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php
// Verificar autenticación
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['documento'])) {
    header("Location: /Login/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sistema de Votación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar {
            margin-bottom: 20px;
        }
        .user-info {
            color: white;
            padding: 8px 15px;
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Votación</a>
            <div class="navbar-nav ms-auto d-flex align-items-center">
                <div class="user-info me-3">
                    <i class="fas fa-user me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                </div>
                <button onclick="confirmarCerrarSesion()" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </button>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2>Sistema de Votación - COSAFA</h2>
                <!-- Contenido de la página -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-code.js"></script>
    <script>
    function confirmarCerrarSesion() {
        if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
            window.location.href = '/Login/index.php?controller=auth&action=logout';
        }
    }
    </script>
</body>
</html>
</body>
</html>