<?php
namespace controllers;

use models\Admin;

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
                $_SESSION['mensaje'] = "Por favor complete todos los campos";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/admin/login');
                exit;
            }

            // Verificar credenciales del administrador
            $admin = $this->adminModel->authenticate($usuario, $password);

            if ($admin) {
                // Iniciar sesión de administrador
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_usuario'] = $admin['usuario'];
                $_SESSION['admin_nombre'] = $admin['nombre'];
                $_SESSION['es_admin'] = true;
                
                // Redirigir al panel de administración
                header('Location: /Login/admin/panel');
                exit;
            } else {
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
        // Verificar que el usuario esté autenticado como administrador
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Mostrar el panel de administración
        require_once 'views/auth/panel.php';
    }

    public function cerrarSesion() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Destruir todas las variables de sesión relacionadas con el administrador
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_usuario']);
        unset($_SESSION['admin_nombre']);
        unset($_SESSION['es_admin']);
        
        session_destroy();
        header('Location: /Login/admin/login');
        exit;
    }
}