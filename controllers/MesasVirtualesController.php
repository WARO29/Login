<?php
namespace controllers;

use models\MesasVirtualesModel;
use models\EleccionConfigModel;
use models\GeneradorPersonalModel;
use models\LogsModel;
use utils\SessionManager;

class MesasVirtualesController {
    private $mesasModel;
    private $eleccionModel;
    private $generadorModel;
    private $logsModel;

    public function __construct() {
        $this->mesasModel = new MesasVirtualesModel();
        $this->eleccionModel = new EleccionConfigModel();
        $this->generadorModel = new GeneradorPersonalModel();
        $this->logsModel = new LogsModel();
    }

    /**
     * Mostrar panel de gestión de mesas virtuales
     */
    public function panel() {
        SessionManager::iniciarSesion();
        
        // Verificar que sea administrador
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        // Obtener elección activa
        $eleccionActiva = $this->eleccionModel->getConfiguracionActiva();
        
        // Verificar si se seleccionó una elección específica por GET
        $eleccionSeleccionada = filter_input(INPUT_GET, 'eleccion', FILTER_VALIDATE_INT);
        
        // Determinar qué elección usar
        if ($eleccionSeleccionada) {
            $id_eleccion = $eleccionSeleccionada;
            // Obtener datos de la elección seleccionada
            $eleccionActual = $this->eleccionModel->getEleccionPorId($id_eleccion);
        } else {
            $id_eleccion = $eleccionActiva ? $eleccionActiva['id'] : null;
            $eleccionActual = $eleccionActiva;
        }

        // Obtener todas las elecciones para el selector
        $todasElecciones = $this->eleccionModel->getTodasElecciones();

        // Si hay elección seleccionada, obtener datos de mesas
        $estadisticasMesas = [];
        $resumenNiveles = [];
        
        if ($id_eleccion) {
            $estadisticasMesas = $this->mesasModel->getEstadisticasMesas($id_eleccion);
            $resumenNiveles = $this->mesasModel->getResumenPorNiveles($id_eleccion);
        }

        // Cargar vista
        require_once 'views/admin/mesas_virtuales.php';
    }

    /**
     * Crear mesas para una elección específica
     */
    public function crearMesas() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_eleccion = filter_input(INPUT_POST, 'id_eleccion', FILTER_VALIDATE_INT);
            
            if ($id_eleccion) {
                if ($this->mesasModel->crearMesasParaEleccion($id_eleccion)) {
                    $_SESSION['mensaje'] = "Mesas virtuales creadas exitosamente para la elección.";
                    $_SESSION['tipo'] = "success";
                    
                    // Registrar en logs
                    $this->logsModel->registrarAccionMesas('crear_mesas', $id_eleccion, null, '12 mesas creadas automáticamente');
                    
                    // Asignar estudiantes automáticamente
                    $estudiantesAsignados = $this->mesasModel->asignarEstudiantesAMesas($id_eleccion);
                    $_SESSION['mensaje'] .= " Se asignaron $estudiantesAsignados estudiantes.";
                    
                    // Registrar asignación de estudiantes
                    $this->logsModel->registrarAccionMesas('asignar_estudiantes', $id_eleccion, null, "$estudiantesAsignados estudiantes asignados");
                } else {
                    $_SESSION['mensaje'] = "Error al crear las mesas virtuales.";
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar error en logs
                    $this->logsModel->registrarAccionMesas('crear_mesas', $id_eleccion, null, 'Error al crear mesas virtuales');
                }
            } else {
                $_SESSION['mensaje'] = "ID de elección inválido.";
                $_SESSION['tipo'] = "danger";
            }
        }

        header('Location: /Login/admin/mesas-virtuales');
        exit;
    }

    /**
     * Gestionar personal de una mesa específica
     */
    public function gestionarPersonal() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        $id_mesa = filter_input(INPUT_GET, 'mesa', FILTER_VALIDATE_INT);
        
        if (!$id_mesa) {
            $_SESSION['mensaje'] = "ID de mesa inválido.";
            $_SESSION['tipo'] = "danger";
            header('Location: /Login/admin/mesas-virtuales');
            exit;
        }

        // Obtener información de la mesa
        $mesas = $this->mesasModel->getMesasPorEleccion(null);
        $mesa = null;
        foreach ($mesas as $m) {
            if ($m['id_mesa'] == $id_mesa) {
                $mesa = $m;
                break;
            }
        }

        if (!$mesa) {
            $_SESSION['mensaje'] = "Mesa no encontrada.";
            $_SESSION['tipo'] = "danger";
            header('Location: /Login/admin/mesas-virtuales');
            exit;
        }

        // Obtener personal actual
        $personalMesa = $this->mesasModel->getPersonalMesa($id_mesa);
        $validacionPersonal = $this->mesasModel->validarPersonalCompleto($id_mesa);

        // Cargar vista
        require_once 'views/admin/gestionar_personal_mesa.php';
    }

    /**
     * Agregar personal a una mesa
     */
    public function agregarPersonal() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_mesa = filter_input(INPUT_POST, 'id_mesa', FILTER_VALIDATE_INT);
            $tipo_personal = filter_input(INPUT_POST, 'tipo_personal', FILTER_SANITIZE_STRING);
            
            $datos = [
                'nombre_completo' => filter_input(INPUT_POST, 'nombre_completo', FILTER_SANITIZE_STRING),
                'documento_identidad' => filter_input(INPUT_POST, 'documento_identidad', FILTER_SANITIZE_STRING),
                'telefono' => filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING),
                'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
                'observaciones' => filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING)
            ];

            if ($id_mesa && $tipo_personal && $datos['nombre_completo'] && $datos['documento_identidad']) {
                if ($this->mesasModel->agregarPersonalMesa($id_mesa, $tipo_personal, $datos)) {
                    $_SESSION['mensaje'] = "Personal agregado exitosamente.";
                    $_SESSION['tipo'] = "success";
                } else {
                    $_SESSION['mensaje'] = "Error al agregar personal.";
                    $_SESSION['tipo'] = "danger";
                }
            } else {
                $_SESSION['mensaje'] = "Todos los campos obligatorios deben ser completados.";
                $_SESSION['tipo'] = "danger";
            }

            header("Location: /Login/admin/gestionar-personal?mesa=$id_mesa");
            exit;
        }

        header('Location: /Login/admin/mesas-virtuales');
        exit;
    }

    /**
     * Eliminar personal de una mesa
     */
    public function eliminarPersonal() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        $id_personal = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $id_mesa = filter_input(INPUT_GET, 'mesa', FILTER_VALIDATE_INT);

        if ($id_personal) {
            if ($this->mesasModel->eliminarPersonalMesa($id_personal)) {
                $_SESSION['mensaje'] = "Personal eliminado exitosamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar personal.";
                $_SESSION['tipo'] = "danger";
            }
        }

        header("Location: /Login/admin/gestionar-personal?mesa=$id_mesa");
        exit;
    }

    /**
     * Obtener estadísticas de mesas en formato JSON
     */
    public function estadisticasJson() {
        SessionManager::iniciarSesion();
        
        if (!SessionManager::esAdministradorAutenticado()) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        $id_eleccion = filter_input(INPUT_GET, 'eleccion', FILTER_VALIDATE_INT);
        
        if (!$id_eleccion) {
            // Obtener elección activa
            $eleccionActiva = $this->eleccionModel->getConfiguracionActiva();
            $id_eleccion = $eleccionActiva ? $eleccionActiva['id'] : null;
        }

        if ($id_eleccion) {
            $estadisticas = $this->mesasModel->getEstadisticasMesas($id_eleccion);
            $resumen = $this->mesasModel->getResumenPorNiveles($id_eleccion);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'estadisticas' => $estadisticas,
                'resumen' => $resumen
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'No hay elección activa'
            ]);
        }
        exit;
    }

    /**
     * Reasignar estudiantes a mesas
     */
    public function reasignarEstudiantes() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_eleccion = filter_input(INPUT_POST, 'id_eleccion', FILTER_VALIDATE_INT);
            
            if ($id_eleccion) {
                $estudiantesAsignados = $this->mesasModel->asignarEstudiantesAMesas($id_eleccion);
                
                $_SESSION['mensaje'] = "Estudiantes reasignados exitosamente. Total: $estudiantesAsignados";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "ID de elección inválido.";
                $_SESSION['tipo'] = "danger";
            }
        }

        header('Location: /Login/admin/mesas-virtuales');
        exit;
    }

    /**
     * Ver detalles de una mesa específica
     */
    public function verMesa() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        $id_mesa = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$id_mesa) {
            $_SESSION['mensaje'] = "ID de mesa inválido.";
            $_SESSION['tipo'] = "danger";
            header('Location: /Login/admin/mesas-virtuales');
            exit;
        }

        // Obtener información detallada de la mesa
        $personalMesa = $this->mesasModel->getPersonalMesa($id_mesa);
        $validacionPersonal = $this->mesasModel->validarPersonalCompleto($id_mesa);
        
        // Obtener estadísticas específicas de la mesa
        $estadisticasMesas = $this->mesasModel->getEstadisticasMesas(null);
        $estadisticaMesa = null;
        
        foreach ($estadisticasMesas as $est) {
            if ($est['id_mesa'] == $id_mesa) {
                $estadisticaMesa = $est;
                break;
            }
        }

        // Cargar vista
        require_once 'views/admin/detalle_mesa.php';
    }

    /**
     * Generar personal automáticamente para todas las mesas
     */
    public function generarPersonal() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_eleccion = filter_input(INPUT_POST, 'id_eleccion', FILTER_VALIDATE_INT);
            
            if ($id_eleccion) {
                $resultado = $this->generadorModel->generarPersonalCompleto($id_eleccion);
                
                if ($resultado['success']) {
                    $_SESSION['mensaje'] = $resultado['mensaje'];
                    $_SESSION['tipo'] = "success";
                    
                    // Registrar en logs
                    $this->logsModel->registrarAccionMesas('generar_personal', $id_eleccion, null, $resultado['mensaje']);
                } else {
                    $_SESSION['mensaje'] = $resultado['mensaje'];
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar error en logs
                    $this->logsModel->registrarAccionMesas('generar_personal', $id_eleccion, null, 'Error: ' . $resultado['mensaje']);
                }
            } else {
                $_SESSION['mensaje'] = "ID de elección inválido.";
                $_SESSION['tipo'] = "danger";
            }
        }

        header('Location: /Login/admin/mesas-virtuales');
        exit;
    }

    /**
     * Limpiar todo el personal de una elección
     */
    public function limpiarPersonal() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_eleccion = filter_input(INPUT_POST, 'id_eleccion', FILTER_VALIDATE_INT);
            
            if ($id_eleccion) {
                if ($this->generadorModel->limpiarPersonalEleccion($id_eleccion)) {
                    $_SESSION['mensaje'] = "Personal eliminado exitosamente de todas las mesas.";
                    $_SESSION['tipo'] = "success";
                } else {
                    $_SESSION['mensaje'] = "Error al eliminar el personal.";
                    $_SESSION['tipo'] = "danger";
                }
            } else {
                $_SESSION['mensaje'] = "ID de elección inválido.";
                $_SESSION['tipo'] = "danger";
            }
        }

        header('Location: /Login/admin/mesas-virtuales');
        exit;
    }

    /**
     * Regenerar personal para mesas incompletas
     */
    public function regenerarPersonal() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_eleccion = filter_input(INPUT_POST, 'id_eleccion', FILTER_VALIDATE_INT);
            
            if ($id_eleccion) {
                $resultado = $this->generadorModel->regenerarPersonalIncompleto($id_eleccion);
                
                $_SESSION['mensaje'] = $resultado['mensaje'];
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "ID de elección inválido.";
                $_SESSION['tipo'] = "danger";
            }
        }

        header('Location: /Login/admin/mesas-virtuales');
        exit;
    }

    /**
     * Obtener estadísticas de personal en formato JSON
     */
    public function estadisticasPersonalJson() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        $id_eleccion = filter_input(INPUT_GET, 'eleccion', FILTER_VALIDATE_INT);
        
        if (!$id_eleccion) {
            // Obtener elección activa
            $eleccionActiva = $this->eleccionModel->getConfiguracionActiva();
            $id_eleccion = $eleccionActiva ? $eleccionActiva['id'] : null;
        }

        if ($id_eleccion) {
            $estadisticas = $this->generadorModel->getEstadisticasPersonal($id_eleccion);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'estadisticas' => $estadisticas
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'No hay elección activa'
            ]);
        }
        exit;
    }
}
?>
