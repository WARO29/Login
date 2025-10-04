<?php
namespace controllers;

use models\Admin;
use models\Estadisticas;
use utils\SessionManager;

class AdminController {
    private $adminModel;

    public function __construct() {
        $this->adminModel = new Admin();
    }

    public function login() {
        // Mostrar la vista de login para administradores
        require_once 'views/auth/login.php';
    }

    public function autenticar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);
            $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
            
            if (empty($usuario) || empty($password)) {
                SessionManager::iniciarSesion();
                $_SESSION['mensaje'] = "Por favor complete todos los campos";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/admin/login');
                exit;
            }

            // Verificar credenciales del administrador
            $admin = $this->adminModel->authenticate($usuario, $password);

            if ($admin) {
                // Establecer sesión de administrador usando SessionManager
                SessionManager::establecerSesionAdmin($admin);
                
                // Registrar en el log la información del administrador para depuración
                error_log("Administrador autenticado - ID: {$admin['id']}, Nombre: {$admin['nombre']}, Imagen: " . (isset($admin['imagen_url']) ? $admin['imagen_url'] : 'No asignada'));
                
                // Redirigir al panel de administración
                header('Location: /Login/admin/panel');
                exit;
            } else {
                SessionManager::iniciarSesion();
                $_SESSION['mensaje'] = "Usuario o contraseña incorrectos";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/admin/login');
                exit;
            }
        } else {
            header('Location: /Login/admin/login');
            exit;
        }
    }

    public function panel() {
        // Verificar que el usuario esté autenticado como administrador usando SessionManager
        if (!SessionManager::esAdminAutenticado()) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Obtener estadísticas para el dashboard
        $estadisticasModel = new Estadisticas();
        $totalEstudiantes = $estadisticasModel->getTotalEstudiantes();
        $totalVotos = $estadisticasModel->getTotalVotos();
        $totalCandidatos = $estadisticasModel->getTotalCandidatos();
        $porcentajeParticipacion = $estadisticasModel->getPorcentajeParticipacion();
        $votosRecientes = $estadisticasModel->getVotosRecientes(5);
        
        // Mostrar el panel de administración con las estadísticas
        require_once 'views/auth/panel.php';
    }

    public function cerrarSesion() {
        // Cerrar sesión de administrador usando SessionManager
        SessionManager::cerrarSesionAdmin();
        
        // Redirigir al login de administrador
        header('Location: /Login/admin/login');
        exit;
    }
}