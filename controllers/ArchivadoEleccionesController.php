<?php
namespace controllers;

use models\ArchivadoEleccionesModel;
use models\LogsModel;
use utils\SessionManager;
use Exception;

class ArchivadoEleccionesController {
    private $archivadoModel;
    private $logsModel;

    public function __construct() {
        $this->archivadoModel = new ArchivadoEleccionesModel();
        $this->logsModel = new LogsModel();
    }

    /**
     * Procesar archivado automático de elecciones cerradas
     */
    public function procesarArchivadoAutomatico() {
        try {
            // 1. Cerrar elecciones que han pasado su fecha de cierre
            $resultadoCierre = $this->archivadoModel->procesarCierreAutomatico();
            
            // 2. Archivar elecciones cerradas
            $resultadoArchivado = $this->archivadoModel->archivarEleccionesCerradas();
            
            // 3. Limpiar dashboard
            $resultadoLimpieza = $this->archivadoModel->limpiarDashboardEleccionesArchivadas();
            
            // Registrar en logs
            $this->logsModel->registrarAccion(
                'sistema',
                'Proceso automático de archivado ejecutado',
                null,
                [
                    'elecciones_cerradas' => $resultadoCierre['elecciones_cerradas'] ?? 0,
                    'elecciones_archivadas' => $resultadoArchivado['elecciones_archivadas'] ?? 0,
                    'proceso' => 'automatico'
                ]
            );
            
            return [
                'success' => true,
                'cierre' => $resultadoCierre,
                'archivado' => $resultadoArchivado,
                'limpieza' => $resultadoLimpieza
            ];
            
        } catch (Exception $e) {
            error_log("Error en proceso automático de archivado: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error en proceso automático: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ejecutar archivado manual desde panel administrativo
     */
    public function ejecutarArchivadoManual() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resultado = $this->procesarArchivadoAutomatico();
            
            if ($resultado['success']) {
                $mensaje = "Archivado completado: ";
                $mensaje .= $resultado['cierre']['elecciones_cerradas'] . " elecciones cerradas, ";
                $mensaje .= $resultado['archivado']['elecciones_archivadas'] . " elecciones archivadas.";
                
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error en el proceso de archivado: " . $resultado['mensaje'];
                $_SESSION['tipo'] = "danger";
            }
        }

        header('Location: /Login/admin/panel');
        exit;
    }

    /**
     * API para verificar estado de elecciones
     */
    public function verificarEstadoElecciones() {
        header('Content-Type: application/json');
        
        try {
            $resultado = $this->procesarArchivadoAutomatico();
            echo json_encode($resultado);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error al verificar estado: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Archivar una elección específica
     */
    public function archivarEleccionEspecifica() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_eleccion = filter_input(INPUT_POST, 'id_eleccion', FILTER_VALIDATE_INT);
            
            if ($id_eleccion) {
                $resultado = $this->archivadoModel->archivarEleccion($id_eleccion);
                
                if ($resultado) {
                    // Registrar en logs
                    $this->logsModel->registrarAccion(
                        'elecciones',
                        "Elección ID $id_eleccion archivada manualmente",
                        $_SESSION['admin_id'] ?? null,
                        ['id_eleccion' => $id_eleccion, 'tipo' => 'manual']
                    );
                    
                    echo json_encode([
                        'success' => true,
                        'mensaje' => 'Elección archivada exitosamente'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'mensaje' => 'Error al archivar la elección'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'mensaje' => 'ID de elección inválido'
                ]);
            }
        }
    }
}
?>
