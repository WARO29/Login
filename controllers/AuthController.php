<?php

namespace controllers;

use models\Estudiantes;
use config\Database;
//require_once '../config/config.php'; // Asegúrate de incluir el archivo de la clase Database

class AuthController {
    private $EstudianteModel;
    private $connection;


    public function __construct() {
        $database = new Database(); // Crear una instancia de la clase Database
        $this->connection = $database->getConnection(); // Obtiene la conexión
        $this->EstudianteModel = new Estudiantes($this->connection);
    }

    public function login() {
        //$EstudianteModel = new Estudiantes($this->connection);
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $documento = $_POST['documento'];

            // Validar que el documento no esté vacío
            if (empty($documento)) {
                $mensaje = "Por favor ingrese un número de documento";
                $tipo = "danger";
                // Guardar mensaje en sesión
                session_start();
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = $tipo;
                header("Location: /Login/index.php");
                exit();
            }

            // Intentar autenticar al estudiante
            $estudiante = $this->EstudianteModel->authenticate($documento);

            if ($estudiante) {
                // Verificar si el estudiante ya votó
                if ($this->EstudianteModel->hasVoted($documento)) {
                    $mensaje = "Ya has ejercido tu voto";
                    $tipo = "warning";
                    session_start();
                    $_SESSION['mensaje'] = $mensaje;
                    $_SESSION['tipo'] = $tipo;
                    header("Location: /Login/index.php");
                    exit();
                }

                // Iniciar sesión y guardar datos del estudiante
                session_start();
                $_SESSION['documento'] = $estudiante['documento'];
                $_SESSION['nombre'] = $estudiante['nombre'];
                
                // Redirigir a la página de votación
                //header("Location: index.php?controller=votacion&action=mostrarCandidatos");
                header("Location: /Login/views/votos.php");
                exit();
            } else {
                $mensaje = "Documento no encontrado";
                $tipo = "danger";
                session_start();
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = $tipo;
                header("Location: /Login/index.php");
                exit();
            }
        }    
    }


    public function logout() {
        // Iniciar la sesión si no está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la sesión
        session_destroy();
        
        // Redirigir al login
        header("Location: /Login/index.php");
        exit();
    }


}

?>