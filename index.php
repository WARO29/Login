<?php
session_start();
// Agrega esto al principio de tu archivo index.php o en un archivo autoload.php

require_once 'autoload.php';

require "config/config.php";

// Add the namespace for the AuthController
use controllers\AuthController;
use controllers\DocenteController;

require "controllers/AuthController.php";

// Obtener la ruta actual y limpiarla
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/Login'; // Directorio base de la aplicación
$path = str_replace($base_path, '', $request_uri);
$path = trim(parse_url($path, PHP_URL_PATH), '/');

// Manejo de rutas
switch ($path) {
    // Rutas de Estudiantes
    case '':
    case 'login':
        // Ruta por defecto - login de estudiantes
        require_once 'controllers/AuthController.php';
        $authController = new controllers\AuthController();
        $authController->login();
        break;
        
    /*case 'votacion':
        // Página de votación
        if (!isset($_SESSION['estudiante_id'])) {
            header('Location: /Login/');
            exit;
        }
        require_once 'controllers/VotacionController.php';
        $controller = new controllers\VotacionController();
        $controller->mostrarCandidatos();
        break;*/
    case 'procesar_voto':
        // Procesar el voto del estudiante
        require_once 'controllers/ProcesarVotosController.php';
        $controller = new controllers\ProcesarVotosController();
        $controller->procesarVoto();
        break;
        
    case 'voto_blanco':
        // Procesar voto en blanco
        require_once 'controllers/ProcesarVotosController.php';
        $controller = new controllers\ProcesarVotosController();
        $controller->procesarVotoEnBlanco();
        break;
        
    case 'cancelar_votacion':
        // Cancelar la votación y eliminar votos parciales
        require_once 'controllers/ProcesarVotosController.php';
        $controller = new controllers\ProcesarVotosController();
        $controller->cancelarVotacion();
        break;
        
    case 'finalizar_votacion':
        // Finalizar la votación verificando que se haya votado por ambos tipos
        require_once 'controllers/ProcesarVotosController.php';
        $controller = new controllers\ProcesarVotosController();
        $controller->finalizarVotacion($_SESSION['estudiante_id']);
        break;
        
    case 'estudiante/votos':
        // Página de votación para estudiantes
        if (!isset($_SESSION['estudiante_id']) || !isset($_SESSION['es_estudiante']) || $_SESSION['es_estudiante'] !== true) {
            header('Location: /Login/');
            exit;
        }
        // Cargar la vista de votación
        require_once 'views/estudiantes/votos.php';
        break;
        
    // Rutas de Docentes
    case 'docente/login':
        // Login de docentes
        require_once 'controllers/DocenteController.php';
        $controller = new controllers\DocenteController();
        $controller->login();
        break;

    case 'docente/autenticar':
        // Autenticación de docentes
        require_once 'controllers/DocenteController.php';
        $controller = new controllers\DocenteController();
        $controller->autenticar();
        break;

    case 'docente/cerrar-sesion':
        // Cerrar sesión de docentes
        require_once 'controllers/DocenteController.php';
        $controller = new controllers\DocenteController();
        $controller->cerrarSesion();
        break;

    case 'docente/panel':
        // Panel de docentes con representantes docentes
        require_once 'controllers/RepresentanteDocenteController.php';
        $controller = new controllers\RepresentanteDocenteController();
        $controller->mostrarPanel();
        break;
        
    // Rutas de Administradores
    case 'admin/login':
        // Login de administradores
        require_once 'controllers/AdminController.php';
        $controller = new controllers\AdminController();
        $controller->login();
        break;
        
    case 'admin/autenticar':
        // Autenticación de administradores
        require_once 'controllers/AdminController.php';
        $controller = new controllers\AdminController();
        $controller->autenticar();
        break;
        
    case 'admin/panel':
        // Panel de administración
        require_once 'controllers/AdminController.php';
        $controller = new controllers\AdminController();
        $controller->panel();
        break;
        
    case 'admin/cerrar-sesion':
        // Cerrar sesión de administradores
        require_once 'controllers/AdminController.php';
        $controller = new controllers\AdminController();
        $controller->cerrarSesion();
        break;

    // Nuevas rutas para gestión de estudiantes
    case 'admin/estudiantes':
        // Gestión de estudiantes
        require_once 'controllers/AdminEstudiantesController.php';
        $controller = new controllers\AdminEstudiantesController();
        $controller->index();
        break;
        
    case 'admin/estudiantes/agregar':
        // Agregar estudiante
        require_once 'controllers/AdminEstudiantesController.php';
        $controller = new controllers\AdminEstudiantesController();
        $controller->agregarEstudiante();
        break;
        
    case 'admin/estudiantes/editar':
        // Editar estudiante
        require_once 'controllers/AdminEstudiantesController.php';
        $controller = new controllers\AdminEstudiantesController();
        $controller->editarEstudiante();
        break;
        
    case 'admin/estudiantes/eliminar':
        // Eliminar estudiante
        require_once 'controllers/AdminEstudiantesController.php';
        $controller = new controllers\AdminEstudiantesController();
        $controller->eliminarEstudiante();
        break;
        
    // Nuevas rutas para gestión de docentes
    case 'admin/docentes':
        // Gestión de docentes
        require_once 'controllers/AdminDocentesController.php';
        $controller = new controllers\AdminDocentesController();
        $controller->index();
        break;
        
    case 'admin/docentes/agregar':
        // Agregar docente
        require_once 'controllers/AdminDocentesController.php';
        $controller = new controllers\AdminDocentesController();
        $controller->agregarDocente();
        break;
        
    case 'admin/docentes/editar':
        // Editar docente
        require_once 'controllers/AdminDocentesController.php';
        $controller = new controllers\AdminDocentesController();
        $controller->editarDocente();
        break;
        
    case 'admin/docentes/eliminar':
        // Eliminar docente
        require_once 'controllers/AdminDocentesController.php';
        $controller = new controllers\AdminDocentesController();
        $controller->eliminarDocente();
        break;

    default:
        // 404 - Página no encontrada
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 - Página no encontrada</h1>";
        echo "<p>La página que buscas no existe.</p>";
        echo "<a href='/Login/'>Volver al inicio</a>";
        break;
}

?>
