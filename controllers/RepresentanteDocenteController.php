<?php
namespace controllers;

use models\RepresentanteDocenteModel;
use models\Votos;
use utils\EleccionMiddleware;
use utils\SessionManager;

class RepresentanteDocenteController {
    private $representanteDocenteModel;
    private $votosModel;

    public function __construct() {
        $this->representanteDocenteModel = new RepresentanteDocenteModel();
        $this->votosModel = new Votos();
    }

    /**
     * Obtiene todos los representantes docentes
     * @return array Arreglo con todos los representantes docentes
     */
    public function getAllRepresentantes() {
        return $this->representanteDocenteModel->getAll();
    }
    
    /**
     * Obtiene un representante docente por su código
     * @param string $codigo Código del representante docente
     * @return array|null Datos del representante docente o null si no existe
     */
    public function getRepresentanteByCodigo($codigo) {
        return $this->representanteDocenteModel->getByCodigo($codigo);
    }

    /**
     * Carga la vista del panel con los representantes docentes
     */
    public function mostrarPanel() {
        // Verificar sesión
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar si el docente está autenticado
        if (!isset($_SESSION['es_docente']) || $_SESSION['es_docente'] !== true) {
            header("Location: /Login/docente/login");
            exit();
        }

        // Determinar el tipo de usuario
        $tipoUsuario = isset($_SESSION['es_administrativo']) && $_SESSION['es_administrativo'] === true ? 'administrativo' : 'docente';
        
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
        if (SessionManager::esAdministrativoAutenticado()) {
            // Es administrativo, verificar en tabla de votos administrativos
            $infoAdministrativo = SessionManager::obtenerInfoUsuario('administrativo');
            $yaVoto = $this->votosModel->haVotadoAdministrativo($infoAdministrativo['cedula']);
        } else {
            // Es docente, verificar en tabla de votos docentes
            $yaVoto = $this->votosModel->haVotadoDocente($id_docente);
        }
        
        // Obtener todos los representantes docentes
        $representantes = $this->getAllRepresentantes();
        
        // Cargar la vista
        require_once 'views/docente/panel.php';
    }
    
    /**
     * Procesa el voto de un docente
     */
    public function procesarVoto() {
        // Usar SessionManager para manejo seguro de sesiones
        require_once __DIR__ . '/../utils/SessionManager.php';
        \utils\SessionManager::iniciarSesion();
        
        // Verificar si el docente está autenticado
        if (!\utils\SessionManager::esDocenteAutenticado()) {
            header("Location: /Login/docente/login");
            exit();
        }
        
        // Determinar el tipo de usuario usando SessionManager
        $tipoUsuario = SessionManager::esAdministrativoAutenticado() ? 'administrativo' : 'docente';
        $id_usuario = $_SESSION['docente_id'];
        
        // Verificar si puede votar según la configuración de elecciones
        $verificacionVoto = EleccionMiddleware::puedeVotar($tipoUsuario, $id_usuario);
        
        if (!$verificacionVoto['puede_votar']) {
            $_SESSION['mensaje'] = $verificacionVoto['motivo'];
            $_SESSION['tipo'] = "warning";
            header("Location: /Login/docente/panel");
            exit();
        }
        
        // Verificar si el docente/administrativo ya ha votado
        if (SessionManager::esAdministrativoAutenticado()) {
            // Es administrativo, verificar en tabla de votos administrativos
            $infoAdministrativo = SessionManager::obtenerInfoUsuario('administrativo');
            $yaVoto = $this->votosModel->haVotadoAdministrativo($infoAdministrativo['cedula']);
        } else {
            // Es docente, verificar en tabla de votos docentes
            $yaVoto = $this->votosModel->haVotadoDocente($id_usuario);
        }
        
        if ($yaVoto) {
            $_SESSION['mensaje'] = "Ya has ejercido tu voto. No puedes votar nuevamente.";
            $_SESSION['tipo'] = "warning";
            header("Location: /Login/docente/panel");
            exit();
        }
        
        // Procesar el voto
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $codigo_representante = isset($_POST['codigo_representante']) ? $_POST['codigo_representante'] : null;
            $voto_blanco = isset($_POST['voto_blanco']) && $_POST['voto_blanco'] === '1';
            
            // Registrar el voto según el tipo de usuario
            if (SessionManager::esAdministrativoAutenticado()) {
                // Registrar voto de administrativo
                $infoAdministrativo = SessionManager::obtenerInfoUsuario('administrativo');
                $resultado = $this->votosModel->registrarVotoAdministrativo($infoAdministrativo['cedula'], $codigo_representante, $voto_blanco);
            } else {
                // Registrar voto de docente
                $resultado = $this->votosModel->registrarVotoDocente($id_usuario, $codigo_representante, $voto_blanco);
            }
            
            if ($resultado) {
                // Guardar información del voto en la sesión
                if ($voto_blanco) {
                    $_SESSION['nombre_representante'] = 'Voto en Blanco';
                } else {
                    $representante = $this->getRepresentanteByCodigo($codigo_representante);
                    $_SESSION['nombre_representante'] = $representante['nombre_repre_docente'];
                }
                
                $_SESSION['voto_completado'] = true;
                $_SESSION['mensaje'] = "¡Tu voto ha sido registrado exitosamente!";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Ha ocurrido un error al registrar tu voto. Por favor, intenta nuevamente.";
                $_SESSION['tipo'] = "danger";
            }
            
            header("Location: /Login/docente/panel");
            exit();
        } else {
            header("Location: /Login/docente/panel");
            exit();
        }
    }
}
