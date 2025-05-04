<?php
namespace controllers;

use models\DocenteModel;

class DocenteController {
    private $docenteModel;

    public function __construct() {
        $this->docenteModel = new DocenteModel();
    }

    public function login() {
        // Mostrar la vista de login
        require_once 'views/docente/Login.php';
    }

    public function autenticar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documento = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_STRING);
            
            if (!$documento) {
                $_SESSION['mensaje'] = "Por favor ingrese un número de documento válido.";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/docente/Login');
                exit();
            }

            // Verificar el docente en la base de datos
            $docente = $this->docenteModel->getDocentePorDocumento($documento);

            if ($docente) {
                // Iniciar sesión
                $_SESSION['docente_id'] = $docente['id'];
                $_SESSION['docente_documento'] = $docente['documento'];
                $_SESSION['docente_nombre'] = $docente['nombre'];
                $_SESSION['es_docente'] = true;
                
                // Redirigir al panel de docente
                header('Location: /Login/docente/panel');
                exit;
            } else {
                $_SESSION['mensaje'] = "Documento no encontrado en el sistema.";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/docente/login');
                exit;
            }
        } else {
            header('Location: /Login/docente/login');
            exit;
        }
    }

    public function cerrarSesion() {
        // Destruir todas las variables de sesión relacionadas con el docente
        unset($_SESSION['docente_id']);
        unset($_SESSION['docente_documento']);
        unset($_SESSION['docente_nombre']);
        unset($_SESSION['es_docente']);
        
        session_destroy();
        header('Location: /Login/docente/login');
        exit;
    }
    
    public function panel() {
        // Verificar si el docente está autenticado
        if (!isset($_SESSION['es_docente']) || $_SESSION['es_docente'] !== true) {
            header('Location: /Login/docente/login');
            exit;
        }
        
        // Cargar la vista del panel de docente
        require_once 'views/docente/panel.php';
    }
}