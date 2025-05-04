<?php

namespace controllers;

use models\EstudianteModel;
use config\Database;
//require_once '../config/config.php'; // Asegúrate de incluir el archivo de la clase Database

class AuthController {
    private $estudianteModel;

    public function __construct() {
        $this->estudianteModel = new EstudianteModel();
    }

    public function login() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Sanitizar el ID de estudiante ingresado
            $id_estudiante = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_STRING);
            
            // Registrar el ID que se está intentando autenticar
            error_log("Formulario enviado con ID: " . $id_estudiante);

            // Validar que el ID de estudiante no esté vacío
            if (empty($id_estudiante)) {
                $mensaje = "Por favor ingrese su ID de estudiante";
                $tipo = "danger";
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = $tipo;
                header("Location: /Login/");
                exit();
            }

            // Validar que el ID de estudiante contenga solo números
            if (!preg_match('/^[0-9]+$/', $id_estudiante)) {
                $mensaje = "El ID de estudiante debe contener solo números";
                $tipo = "danger";
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = $tipo;
                header("Location: /Login/");
                exit();
            }

            // Intentar autenticar al estudiante (verificando id_estudiante)
            $estudiante = $this->estudianteModel->autenticarEstudiante($id_estudiante);

            if ($estudiante) {
                // Verificar si el estudiante ya votó
                if ($this->estudianteModel->haVotado($id_estudiante)) {
                    $mensaje = "Ya has ejercido tu voto";
                    $tipo = "warning";
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['mensaje'] = $mensaje;
                    $_SESSION['tipo'] = $tipo;
                    header("Location: /Login/");
                    exit();
                }

                // Iniciar sesión y guardar datos del estudiante
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                // Guardar datos completos del estudiante en la sesión
                $_SESSION['estudiante_id'] = $estudiante['id_estudiante'];
                $_SESSION['documento'] = $estudiante['documento'];
                $_SESSION['nombre'] = $estudiante['nombre'];
                $_SESSION['apellido'] = $estudiante['apellido'];
                $_SESSION['nombre_completo'] = $estudiante['nombre'] . ' ' . $estudiante['apellido'];
                $_SESSION['grado'] = $estudiante['grado'];
                $_SESSION['es_estudiante'] = true;
                
                // Redirigir a la página de votación
                header("Location: /Login/views/estudiantes/votos.php");
                exit();
            } else {
                $mensaje = "ID de estudiante no encontrado en el sistema";
                $tipo = "danger";
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = $tipo;
                header("Location: /Login/");
                exit();
            }
        } else {
            // Si no es POST, mostrar la vista de login
            require "views/auth/login.php";
        }
    }

    public function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Destruir todas las variables de sesión
        $_SESSION = array();
        session_destroy();
        
        // Redirigir al login
        header("Location: /Login/");
        exit();
    }
}

?>