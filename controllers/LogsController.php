<?php
namespace controllers;

use models\LogsModel;
use utils\SessionManager;

class LogsController {
    private $logsModel;

    public function __construct() {
        $this->logsModel = new LogsModel();
    }

    /**
     * Mostrar logs del sistema
     */
    public function index() {
        SessionManager::iniciarSesion();
        
        // Verificar que sea administrador
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        // Obtener filtros
        $filtros = [
            'tipo' => filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'fecha_desde' => filter_input(INPUT_GET, 'fecha_desde', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'fecha_hasta' => filter_input(INPUT_GET, 'fecha_hasta', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'limite' => filter_input(INPUT_GET, 'limite', FILTER_VALIDATE_INT) ?: 50
        ];

        // Limpiar filtros vacíos
        $filtros = array_filter($filtros, function($value) {
            return !empty($value);
        });

        // Obtener logs
        $logs = $this->logsModel->obtenerLogs($filtros);
        
        // Obtener estadísticas
        $estadisticas = $this->logsModel->obtenerEstadisticasLogs(7); // Últimos 7 días

        // Cargar vista
        require_once 'views/admin/logs_sistema.php';
    }

    /**
     * Obtener logs de mesas virtuales específicamente
     */
    public function mesasVirtuales() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $id_eleccion = filter_input(INPUT_GET, 'eleccion', FILTER_VALIDATE_INT);
        $limite = filter_input(INPUT_GET, 'limite', FILTER_VALIDATE_INT) ?: 20;

        $logs = $this->logsModel->obtenerLogsMesasVirtuales($id_eleccion, $limite);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'total' => count($logs)
        ]);
    }

    /**
     * Limpiar logs antiguos
     */
    public function limpiar() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dias = filter_input(INPUT_POST, 'dias', FILTER_VALIDATE_INT) ?: 90;
            
            $eliminados = $this->logsModel->limpiarLogsAntiguos($dias);
            
            if ($eliminados !== false) {
                $_SESSION['mensaje'] = "Se eliminaron $eliminados logs antiguos (más de $dias días).";
                $_SESSION['tipo'] = "success";
                
                // Registrar la limpieza
                $this->logsModel->registrarAccion('sistema', "Limpieza de logs antiguos: $eliminados logs eliminados", $_SESSION['admin_id']);
            } else {
                $_SESSION['mensaje'] = "Error al limpiar logs antiguos.";
                $_SESSION['tipo'] = "danger";
            }
        }

        header('Location: /Login/admin/logs');
        exit;
    }
}
?>
