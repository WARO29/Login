<?php

namespace controllers;

use models\EstudianteModel;
use config\Database;
use utils\SessionManager;
use utils\EleccionMiddleware;
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
                SessionManager::iniciarSesion();
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = $tipo;
                header("Location: /Login/");
                exit();
            }

            // Validar que el ID de estudiante contenga solo números
            if (!preg_match('/^[0-9]+$/', $id_estudiante)) {
                $mensaje = "El ID de estudiante debe contener solo números";
                $tipo = "danger";
                SessionManager::iniciarSesion();
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = $tipo;
                header("Location: /Login/");
                exit();
            }

            // Verificar si hay elecciones activas y si los estudiantes pueden votar
            $verificacionElecciones = EleccionMiddleware::verificarAccesoVotante('estudiante');
            if (!$verificacionElecciones['puede_acceder']) {
                // Registrar intento de acceso bloqueado
                EleccionMiddleware::registrarIntentoAcceso([
                    'tipo_usuario' => 'estudiante',
                    'id_usuario' => $id_estudiante,
                    'motivo' => $verificacionElecciones['motivo'],
                    'id_eleccion' => $verificacionElecciones['eleccion_activa']['id'] ?? null
                ]);
                
                $mensaje = $verificacionElecciones['mensaje'];
                $tipo = "warning";
                SessionManager::iniciarSesion();
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = $tipo;
                header("Location: /Login/estado-elecciones");
                exit();
            }
            
            // Intentar autenticar al estudiante (verificando id_estudiante y estado activo)
            $estudiante = $this->estudianteModel->autenticarEstudiante($id_estudiante);

            // Verificar si el estudiante existe pero no está activo
            if (is_array($estudiante) && isset($estudiante['error']) && $estudiante['error'] === 'inactive') {
                $mensaje = "Tu cuenta no está activa. Por favor contacta al administrador.";
                $tipo = "danger";
                SessionManager::iniciarSesion();
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = $tipo;
                header("Location: /Login/");
                exit();
            }

            if ($estudiante) {
                // Verificar si el estudiante ya votó
                if ($this->estudianteModel->haVotado($id_estudiante)) {
                    $mensaje = "Ya has ejercido tu voto";
                    $tipo = "warning";
                    SessionManager::iniciarSesion();
                    $_SESSION['mensaje'] = $mensaje;
                    $_SESSION['tipo'] = $tipo;
                    header("Location: /Login/");
                    exit();
                }

                // Establecer sesión de estudiante usando SessionManager
                SessionManager::establecerSesionEstudiante($estudiante);
                
                // Registrar acceso exitoso
                EleccionMiddleware::registrarAccesoExitoso([
                    'tipo_usuario' => 'estudiante',
                    'id_usuario' => $id_estudiante,
                    'nombre_usuario' => $estudiante['nombre'] . ' ' . $estudiante['apellido'],
                    'id_eleccion' => $verificacionElecciones['eleccion_activa']['id'] ?? null
                ]);
                
                // Redirigir a la página de votación
                header("Location: /Login/estudiante/votos");
                exit();
            } else {
                $mensaje = "ID de estudiante no encontrado en el sistema";
                $tipo = "danger";
                SessionManager::iniciarSesion();
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = $tipo;
                header("Location: /Login/");
                exit();
            }
        } else {
            // Si no es POST, mostrar la vista de login para estudiantes
            require "views/estudiantes/login.php";
        }
    }

    public function logout() {
        // Cerrar sesión de estudiante usando SessionManager
        SessionManager::cerrarSesionEstudiante();
        
        // Redirigir al login
        header("Location: /Login/");
        exit();
    }
}

?>