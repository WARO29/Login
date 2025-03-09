<?php
session_start();

use config\Database;
use models\Votos;

if (!isset($_SESSION['documento'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidato_id = $_POST['candidato_id'];
    $tipo_voto = $_POST['tipo_voto'];
    $documento = $_SESSION['documento'];

    $database = new Database();
    $db = $database->getConnection();
    $votosModel = new Votos($db);

    if ($votosModel->registrarVoto($documento, $candidato_id, $tipo_voto)) {
        $_SESSION['mensaje'] = "Voto registrado exitosamente";
        $_SESSION['tipo'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar el voto";
        $_SESSION['tipo'] = "danger";
    }

    header("Location: ../views/confirmacion.php");
    exit();
}
?>