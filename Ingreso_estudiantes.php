<?php

require "Conexion.php";

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identificacion = $_POST['identificacion'];
    $nombre_usuario = $_POST['nombre_usuario'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    $grado = $_POST['grado'];
    
    $sql = "INSERT INTO estudiante (identificacion, nombre_usuario, contraseña, grado) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $identificacion, $nombre_usuario, $contrasena, $grado);
    
    if ($stmt->execute()) {
        echo "<div class='alert alert-success text-center'>Estudiante registrado exitosamente</div>";
    } else {
        echo "<div class='alert alert-danger text-center'>Error al registrar estudiante</div>";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Estudiante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">Registro de Estudiante</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Identificación</label>
                                <input type="number" name="identificacion" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nombre de Usuario</label>
                                <input type="text" name="nombre_usuario" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="contrasena" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Grado</label>
                                <input type="text" name="grado" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Registrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>