<?php
namespace controllers;

use models\DocenteModel;
use models\AdministrativoModel;
use controllers\RepresentanteDocenteController;
use utils\EleccionMiddleware;
use utils\SessionManager;

class DocenteController {
    private $docenteModel;
    private $administrativoModel;

    public function __construct() {
        $this->docenteModel = new DocenteModel();
        $this->administrativoModel = new AdministrativoModel();
    }

    public function login() {
        // Mostrar la vista de login
        require_once 'views/docente/Login.php';
    }

    public function autenticar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documento = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_STRING);
            
            if (!$documento) {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['mensaje'] = "Por favor ingrese un número de documento válido.";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/docente/login');
                exit();
            }

            // Primero verificar si es un docente
            $docente = $this->docenteModel->getDocentePorDocumento($documento);

            if ($docente) {
                // Establecer sesión de docente usando SessionManager
                $docenteData = [
                    'id' => $docente['codigo_docente'],
                    'nombre' => $docente['nombre'],
                    'email' => $docente['email'] ?? ''
                ];
                SessionManager::establecerSesionDocente($docenteData);
                
                // Establecer variables adicionales para compatibilidad
                SessionManager::iniciarSesion();
                $_SESSION['docente_documento'] = $docente['codigo_docente'];
                $_SESSION['tipo_usuario'] = 'docente';
                
                // Redirigir al panel de docente
                header('Location: /Login/docente/panel');
                exit;
            }

            // Si no es docente, verificar si es administrativo
            $administrativo = $this->administrativoModel->getAdministrativoPorCedula($documento);

            if ($administrativo) {
                // Establecer sesión de administrativo usando SessionManager
                SessionManager::establecerSesionAdministrativo($administrativo);
                
                // Redirigir al panel de docente (mismo panel)
                header('Location: /Login/docente/panel');
                exit;
            }

            // Si no es ni docente ni administrativo
            SessionManager::iniciarSesion();
            $_SESSION['mensaje'] = "Documento no encontrado en el sistema.";
            $_SESSION['tipo'] = "danger";
            header('Location: /Login/docente/login');
            exit;
        } else {
            header('Location: /Login/docente/login');
            exit;
        }
    }

    public function cerrarSesion() {
        // Determinar si es docente o administrativo y cerrar la sesión correspondiente
        SessionManager::iniciarSesion();
        
        if (SessionManager::esAdministrativoAutenticado()) {
            SessionManager::cerrarSesionAdministrativo();
        } else {
            SessionManager::cerrarSesionDocente();
        }
        
        // Redirigir al login de docentes
        header('Location: /Login/docente/login');
        exit;
    }
    
    public function panel() {
        // Usar SessionManager para manejo seguro de sesiones
        SessionManager::iniciarSesion();
        
        // Verificar si el docente o administrativo está autenticado
        if (!SessionManager::esDocenteAutenticado() && !SessionManager::esAdministrativoAutenticado()) {
            header('Location: /Login/docente/login');
            exit;
        }
        
        // Determinar el tipo de usuario usando SessionManager
        $tipoUsuario = SessionManager::esAdministrativoAutenticado() ? 'administrativo' : 'docente';
        
        // Verificar si puede acceder a votar según la configuración de elecciones
        $verificacionAcceso = EleccionMiddleware::verificarAccesoVotante($tipoUsuario);
        
        if (!$verificacionAcceso['puede_acceder']) {
            // No puede acceder, mostrar mensaje y redirigir o mostrar vista de información
            $_SESSION['mensaje'] = $verificacionAcceso['mensaje'];
            $_SESSION['tipo'] = 'warning';
            $_SESSION['motivo_acceso_denegado'] = $verificacionAcceso['motivo'];
            
            // Cargar vista con información de por qué no puede votar
            require_once 'views/docente/acceso_denegado.php';
            return;
        }
        
        // Obtener información del docente
        $id_docente = $_SESSION['docente_id'];
        $nombre_docente = $_SESSION['docente_nombre'];
        
        // Verificar si el usuario ya ha votado (docente o administrativo)
        $votosModel = new \models\Votos();
        if (SessionManager::esAdministrativoAutenticado()) {
            // Es administrativo, verificar en tabla de votos administrativos
            $infoAdministrativo = SessionManager::obtenerInfoUsuario('administrativo');
            $yaVoto = $votosModel->haVotadoAdministrativo($infoAdministrativo['cedula']);
        } else {
            // Es docente, verificar en tabla de votos docentes
            $yaVoto = $votosModel->haVotadoDocente($id_docente);
        }
        
        // Obtener todos los representantes docentes
        $representanteController = new RepresentanteDocenteController();
        $representantes = $representanteController->getAllRepresentantes();
        
        // Cargar la vista del panel de docente
        require_once 'views/docente/panel.php';
    }
}