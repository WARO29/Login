<?php
session_start();
// Agrega esto al principio de tu archivo index.php o en un archivo autoload.php

require_once 'autoload.php';

require "config/config.php";

// Add the namespace for the AuthController
use controllers\AuthController;

require "controllers/AuthController.php";

$authController = new AuthController();
$authController->login();

require "views/login.php"; // Cargar la vista de los estudiantes o profesor 

?>


