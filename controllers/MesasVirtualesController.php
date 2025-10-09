<?php
namespace controllers;

use models\MesasVirtualesModel;
use models\EleccionConfigModel;
use models\GeneradorPersonalModel;
use models\LogsModel;
use utils\SessionManager;
use Exception;

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
     * Verificar si una elección es modificable (no ha terminado)
     */
    private function esEleccionModificable($id_eleccion) {
        $eleccion = $this->eleccionModel->getEleccionPorId($id_eleccion);
        if (!$eleccion) {
            return false;
        }
        
        $fechaActual = new \DateTime();
        $fechaCierre = new \DateTime($eleccion['fecha_cierre']);
        return ($fechaActual <= $fechaCierre);
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

        // Determinar si la elección es modificable (no ha terminado)
        $eleccionModificable = false;
        if ($eleccionActual) {
            $fechaActual = new \DateTime();
            $fechaCierre = new \DateTime($eleccionActual['fecha_cierre']);
            $eleccionModificable = ($fechaActual <= $fechaCierre);
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
                // Verificar si la elección es modificable
                if (!$this->esEleccionModificable($id_eleccion)) {
                    $_SESSION['mensaje'] = "No se pueden crear mesas para una elección que ya finalizó.";
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar intento no autorizado
                    $this->logsModel->registrarAccion('mesas_virtuales', 'Intento de crear mesas en elección finalizada', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'crear_mesas']);
                    
                    header('Location: /Login/admin/mesas-virtuales');
                    exit;
                }
                
                if ($this->mesasModel->crearMesasParaEleccion($id_eleccion)) {
                    $_SESSION['mensaje'] = "Mesas virtuales creadas exitosamente para la elección.";
                    $_SESSION['tipo'] = "success";
                    
                    // Registrar en logs
                    $this->logsModel->registrarAccion('mesas_virtuales', '12 mesas creadas automáticamente', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'crear_mesas']);
                    
                    // Asignar estudiantes automáticamente
                    $estudiantesAsignados = $this->mesasModel->asignarEstudiantesAMesas($id_eleccion);
                    $_SESSION['mensaje'] .= " Se asignaron $estudiantesAsignados estudiantes.";
                    
                    // Registrar asignación de estudiantes
                    $this->logsModel->registrarAccion('mesas_virtuales', "$estudiantesAsignados estudiantes asignados", $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'asignar_estudiantes']);
                } else {
                    $_SESSION['mensaje'] = "Error al crear las mesas virtuales.";
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar error en logs
                    $this->logsModel->registrarAccion('mesas_virtuales', 'Error al crear mesas virtuales', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'crear_mesas']);
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
        $mesa = $this->mesasModel->getMesaPorId($id_mesa);

        if (!$mesa) {
            $_SESSION['mensaje'] = "Mesa no encontrada.";
            $_SESSION['tipo'] = "danger";
            header('Location: /Login/admin/mesas-virtuales');
            exit;
        }

        // Obtener personal actual
        $personalMesa = $this->mesasModel->getPersonalMesa($id_mesa);
        $validacionPersonal = $this->mesasModel->validarPersonalCompleto($id_mesa);
        
        // Determinar si la elección es modificable
        $eleccionModificable = $this->esEleccionModificable($mesa['id_eleccion']);

        // Verificar que las variables estén definidas (para debugging)
        if (!$personalMesa) {
            $personalMesa = [];
        }
        if (!$validacionPersonal) {
            $validacionPersonal = [
                'completo' => false,
                'total_actual' => 0,
                'faltantes' => [
                    'jurado' => 1,
                    'testigo_docente' => 1,
                    'testigo_estudiante' => 2
                ]
            ];
        }

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
            $tipo_personal = filter_input(INPUT_POST, 'tipo_personal', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            // Obtener datos según el tipo de personal
            $datos = [];
            
            if ($tipo_personal === 'jurado') {
                // Para jurados, usar datos manuales
                $datos = [
                    'nombre_completo' => filter_input(INPUT_POST, 'nombre_completo', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                    'documento_identidad' => filter_input(INPUT_POST, 'documento_identidad', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                    'telefono' => filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                    'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
                    'observaciones' => filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_FULL_SPECIAL_CHARS)
                ];
            } elseif ($tipo_personal === 'testigo_docente') {
                // Para testigos docentes, obtener datos de la tabla docentes
                $docente_id = filter_input(INPUT_POST, 'docente_id', FILTER_VALIDATE_INT);
                if ($docente_id) {
                    $datosDocente = $this->mesasModel->getDatosDocente($docente_id);
                    if ($datosDocente) {
                        $datos = [
                            'nombre_completo' => $datosDocente['nombres'] . ' ' . $datosDocente['apellidos'],
                            'documento_identidad' => $datosDocente['numero_documento'],
                            'telefono' => $datosDocente['telefono'] ?? '',
                            'email' => $datosDocente['email'] ?? '',
                            'observaciones' => 'Docente - ' . ($datosDocente['area_especialidad'] ?? 'Sin área especificada'),
                            'id_referencia' => $docente_id,
                            'tipo_referencia' => 'docente'
                        ];
                    }
                }
            } elseif ($tipo_personal === 'testigo_estudiante') {
                // Para testigos estudiantes, obtener datos de la tabla estudiantes
                $estudiante_id = filter_input(INPUT_POST, 'estudiante_id', FILTER_VALIDATE_INT);
                if ($estudiante_id) {
                    $datosEstudiante = $this->mesasModel->getDatosEstudiante($estudiante_id);
                    if ($datosEstudiante) {
                        $datos = [
                            'nombre_completo' => $datosEstudiante['nombres'] . ' ' . $datosEstudiante['apellidos'],
                            'documento_identidad' => $datosEstudiante['numero_documento'],
                            'telefono' => '',
                            'email' => '',
                            'observaciones' => 'Estudiante - Grado ' . $datosEstudiante['grado'],
                            'id_referencia' => $estudiante_id,
                            'tipo_referencia' => 'estudiante'
                        ];
                    }
                }
            }

            if ($id_mesa && $tipo_personal && !empty($datos) && $datos['nombre_completo'] && $datos['documento_identidad']) {
                // Obtener información de la mesa para validar la elección
                $mesa = $this->mesasModel->getMesaPorId($id_mesa);
                
                if (!$mesa) {
                    $_SESSION['mensaje'] = "Mesa no encontrada.";
                    $_SESSION['tipo'] = "danger";
                    header('Location: /Login/admin/mesas-virtuales');
                    exit;
                }
                
                // Verificar si la elección es modificable
                if (!$this->esEleccionModificable($mesa['id_eleccion'])) {
                    $_SESSION['mensaje'] = "No se puede agregar personal a una elección que ya finalizó.";
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar intento no autorizado
                    $this->logsModel->registrarAccion('mesas_virtuales', 'Intento de agregar personal en elección finalizada', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $mesa['id_eleccion'], 'id_mesa' => $id_mesa, 'accion' => 'agregar_personal']);
                    
                    header('Location: /Login/admin/gestionar-personal?mesa=' . $id_mesa);
                    exit;
                }
                
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

        if ($id_personal && $id_mesa) {
            // Obtener información de la mesa para validar la elección
            $mesa = $this->mesasModel->getMesaPorId($id_mesa);
            
            if (!$mesa) {
                $_SESSION['mensaje'] = "Mesa no encontrada.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/gestionar-personal?mesa=$id_mesa");
                exit;
            }
            
            // Verificar si la elección es modificable
            if (!$this->esEleccionModificable($mesa['id_eleccion'])) {
                $_SESSION['mensaje'] = "No se puede eliminar personal de una elección que ya finalizó.";
                $_SESSION['tipo'] = "danger";
                
                // Registrar intento no autorizado
                $this->logsModel->registrarAccion('mesas_virtuales', 'Intento de eliminar personal en elección finalizada', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $mesa['id_eleccion'], 'id_mesa' => $id_mesa, 'accion' => 'eliminar_personal']);
                
                header("Location: /Login/admin/gestionar-personal?mesa=$id_mesa");
                exit;
            }
            
            if ($this->mesasModel->eliminarPersonalMesa($id_personal)) {
                $_SESSION['mensaje'] = "Personal eliminado exitosamente.";
                $_SESSION['tipo'] = "success";
                
                // Registrar en logs
                $this->logsModel->registrarAccion('mesas_virtuales', 'Personal eliminado de mesa', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $mesa['id_eleccion'], 'id_mesa' => $id_mesa, 'accion' => 'eliminar_personal']);
            } else {
                $_SESSION['mensaje'] = "Error al eliminar personal.";
                $_SESSION['tipo'] = "danger";
            }
        } else {
            $_SESSION['mensaje'] = "Parámetros inválidos.";
            $_SESSION['tipo'] = "danger";
        }

        header("Location: /Login/admin/gestionar-personal?mesa=$id_mesa");
        exit;
    }

    /**
     * Obtener estadísticas de mesas en formato JSON
     */
    public function estadisticasJson() {
        SessionManager::iniciarSesion();
        
        if (!SessionManager::esAdminAutenticado()) {
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
                // Verificar si la elección es modificable
                if (!$this->esEleccionModificable($id_eleccion)) {
                    $_SESSION['mensaje'] = "No se pueden reasignar estudiantes para una elección que ya finalizó.";
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar intento no autorizado
                    $this->logsModel->registrarAccion('mesas_virtuales', 'Intento de reasignar estudiantes en elección finalizada', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'reasignar_estudiantes']);
                    
                    header('Location: /Login/admin/mesas-virtuales');
                    exit;
                }
                
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
        require_once 'views/admin/gestionar_personal_mesa.php';
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
                // Verificar si la elección es modificable
                if (!$this->esEleccionModificable($id_eleccion)) {
                    $_SESSION['mensaje'] = "No se puede generar personal para una elección que ya finalizó.";
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar intento no autorizado
                    $this->logsModel->registrarAccion('mesas_virtuales', 'Intento de generar personal en elección finalizada', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'generar_personal']);
                    
                    header('Location: /Login/admin/mesas-virtuales');
                    exit;
                }
                
                $resultado = $this->generadorModel->generarPersonalCompleto($id_eleccion);
                
                if ($resultado['success']) {
                    $_SESSION['mensaje'] = $resultado['mensaje'];
                    $_SESSION['tipo'] = "success";
                    
                    // Registrar en logs
                    $this->logsModel->registrarAccion('mesas_virtuales', $resultado['mensaje'], $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'generar_personal']);
                } else {
                    $_SESSION['mensaje'] = $resultado['mensaje'];
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar error en logs
                    $this->logsModel->registrarAccion('mesas_virtuales', 'Error: ' . $resultado['mensaje'], $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'generar_personal']);
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
                // Verificar si la elección es modificable
                if (!$this->esEleccionModificable($id_eleccion)) {
                    $_SESSION['mensaje'] = "No se puede limpiar personal de una elección que ya finalizó.";
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar intento no autorizado
                    $this->logsModel->registrarAccion('mesas_virtuales', 'Intento de limpiar personal en elección finalizada', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'limpiar_personal']);
                    
                    header('Location: /Login/admin/mesas-virtuales');
                    exit;
                }
                
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
                // Verificar si la elección es modificable
                if (!$this->esEleccionModificable($id_eleccion)) {
                    $_SESSION['mensaje'] = "No se puede regenerar personal para una elección que ya finalizó.";
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar intento no autorizado
                    $this->logsModel->registrarAccion('mesas_virtuales', 'Intento de regenerar personal en elección finalizada', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'regenerar_personal']);
                    
                    header('Location: /Login/admin/mesas-virtuales');
                    exit;
                }
                
                $resultado = $this->generadorModel->regenerarPersonalIncompleto($id_eleccion);
                
                $_SESSION['mensaje'] = $resultado['mensaje'];
                $_SESSION['tipo'] = $resultado['success'] ? "success" : "danger";
                
                // Registrar en logs
                $this->logsModel->registrarAccion('mesas_virtuales', $resultado['mensaje'], $_SESSION['admin_id'] ?? null, ['id_eleccion' => $id_eleccion, 'accion' => 'regenerar_personal']);
            } else {
                $_SESSION['mensaje'] = "ID de elección inválido.";
                $_SESSION['tipo'] = "danger";
            }
        }

        header('Location: /Login/admin/mesas-virtuales');
        exit;
    }

    /**
     * Mostrar vista para asignar estudiantes a una mesa específica
     */
    public function asignarEstudiantes() {
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
        $mesa = $this->mesasModel->getMesaPorId($id_mesa);

        if (!$mesa) {
            $_SESSION['mensaje'] = "Mesa no encontrada.";
            $_SESSION['tipo'] = "danger";
            header('Location: /Login/admin/mesas-virtuales');
            exit;
        }

        // Determinar el grado basado en el nombre de la mesa
        $grado = '';
        if (preg_match('/Grado (\d+)/', $mesa['nombre_mesa'], $matches)) {
            $grado = $matches[1];
        } elseif (strpos($mesa['nombre_mesa'], 'Preescolar') !== false) {
            $grado = 'Preescolar';
        }

        // Obtener estudiantes del grado correspondiente
        $estudiantesGrado = $this->mesasModel->getEstudiantesPorGrado($grado);
        
        // Obtener estudiantes ya asignados a esta mesa
        $estudiantesAsignados = $this->mesasModel->getEstudiantesAsignadosMesa($id_mesa);

        // Debug temporal - eliminar después
        error_log("Debug - Grado: " . $grado);
        error_log("Debug - Estudiantes grado: " . count($estudiantesGrado));
        error_log("Debug - Estudiantes asignados: " . count($estudiantesAsignados));
        if (!empty($estudiantesGrado)) {
            error_log("Debug - Primer estudiante: " . json_encode($estudiantesGrado[0]));
        }

        // Cargar vista
        require_once 'views/admin/asignar_estudiantes_mesa.php';
    }

    /**
     * Procesar asignación de estudiantes a una mesa
     */
    public function procesarAsignacionEstudiantes() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_mesa = filter_input(INPUT_POST, 'id_mesa', FILTER_VALIDATE_INT);
            $estudiantes = $_POST['estudiantes'] ?? [];

            if ($id_mesa && is_array($estudiantes)) {
                // Obtener información de la mesa para validar la elección
                $mesa = $this->mesasModel->getMesaPorId($id_mesa);
                
                if (!$mesa) {
                    $_SESSION['mensaje'] = "Mesa no encontrada.";
                    $_SESSION['tipo'] = "danger";
                    header('Location: /Login/admin/mesas-virtuales');
                    exit;
                }
                
                // Verificar si la elección es modificable
                if (!$this->esEleccionModificable($mesa['id_eleccion'])) {
                    $_SESSION['mensaje'] = "No se pueden asignar estudiantes a una elección que ya finalizó.";
                    $_SESSION['tipo'] = "danger";
                    
                    // Registrar intento no autorizado
                    $this->logsModel->registrarAccion('mesas_virtuales', 'Intento de asignar estudiantes en elección finalizada', $_SESSION['admin_id'] ?? null, ['id_eleccion' => $mesa['id_eleccion'], 'id_mesa' => $id_mesa, 'accion' => 'asignar_estudiantes']);
                    
                    header('Location: /Login/admin/asignar-estudiantes?mesa=' . $id_mesa);
                    exit;
                }

                // Procesar asignación
                $resultado = $this->mesasModel->asignarEstudiantesEspecificosMesa($id_mesa, $estudiantes);
                
                if ($resultado['success']) {
                    $_SESSION['mensaje'] = "Estudiantes asignados exitosamente. Total: " . $resultado['asignados'];
                    $_SESSION['tipo'] = "success";
                    
                    // Registrar en logs
                    $this->logsModel->registrarAccion('mesas_virtuales', "Asignados {$resultado['asignados']} estudiantes a mesa", $_SESSION['admin_id'] ?? null, ['id_eleccion' => $mesa['id_eleccion'], 'id_mesa' => $id_mesa, 'accion' => 'asignar_estudiantes_especificos']);
                } else {
                    $_SESSION['mensaje'] = "Error al asignar estudiantes: " . $resultado['mensaje'];
                    $_SESSION['tipo'] = "danger";
                }
            } else {
                $_SESSION['mensaje'] = "Datos inválidos para la asignación.";
                $_SESSION['tipo'] = "danger";
            }

            header('Location: /Login/admin/asignar-estudiantes?mesa=' . $id_mesa);
            exit;
        }

        header('Location: /Login/admin/mesas-virtuales');
        exit;
    }

    /**
     * Registrar nuevo estudiante y asignarlo a mesa
     */
    public function registrarEstudianteMesa() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_mesa = filter_input(INPUT_POST, 'id_mesa', FILTER_VALIDATE_INT);
            $asignar_mesa = filter_input(INPUT_POST, 'asignar_mesa', FILTER_VALIDATE_INT);
            
            $datos_estudiante = [
                'nombres' => trim($_POST['nombres'] ?? ''),
                'apellidos' => trim($_POST['apellidos'] ?? ''),
                'tipo_documento' => $_POST['tipo_documento'] ?? '',
                'numero_documento' => trim($_POST['numero_documento'] ?? ''),
                'grado' => $_POST['grado'] ?? '',
                'grupo' => $_POST['grupo'] ?? '',
                'codigo_estudiante' => trim($_POST['codigo_estudiante'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? '')
            ];

            // Validar datos requeridos
            if (empty($datos_estudiante['nombres']) || empty($datos_estudiante['apellidos']) || 
                empty($datos_estudiante['tipo_documento']) || empty($datos_estudiante['numero_documento']) ||
                empty($datos_estudiante['grado']) || empty($datos_estudiante['grupo'])) {
                $_SESSION['mensaje'] = "Todos los campos marcados con * son obligatorios.";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/admin/asignar-estudiantes?mesa=' . $id_mesa);
                exit;
            }

            // Verificar que la mesa existe
            $mesa = $this->mesasModel->getMesaPorId($id_mesa);
            if (!$mesa) {
                $_SESSION['mensaje'] = "Mesa no encontrada.";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/admin/mesas-virtuales');
                exit;
            }

            // Verificar si la elección es modificable
            if (!$this->esEleccionModificable($mesa['id_eleccion'])) {
                $_SESSION['mensaje'] = "No se pueden registrar estudiantes en una elección que ya finalizó.";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/admin/asignar-estudiantes?mesa=' . $id_mesa);
                exit;
            }

            try {
                // Debug temporal - eliminar después
                error_log("DEBUG - Datos del estudiante: " . json_encode($datos_estudiante));
                
                // Registrar estudiante
                $resultado = $this->mesasModel->registrarNuevoEstudiante($datos_estudiante);
                
                // Debug temporal - eliminar después
                error_log("DEBUG - Resultado del registro: " . json_encode($resultado));
                
                if ($resultado['success']) {
                    $id_estudiante = $resultado['id_estudiante'];
                    
                    // Si se debe asignar a la mesa
                    if ($asignar_mesa && $id_mesa) {
                        $asignacion = $this->mesasModel->asignarEstudianteMesa($id_mesa, $id_estudiante);
                        
                        if ($asignacion['success']) {
                            $_SESSION['mensaje'] = "Estudiante registrado y asignado exitosamente a la mesa.";
                            $_SESSION['tipo'] = "success";
                            
                            // Registrar en logs
                            $this->logsModel->registrarAccion('estudiantes', 'Estudiante registrado y asignado a mesa', $_SESSION['admin_id'] ?? null, [
                                'id_estudiante' => $id_estudiante,
                                'id_mesa' => $id_mesa,
                                'nombres' => $datos_estudiante['nombres'] . ' ' . $datos_estudiante['apellidos']
                            ]);
                        } else {
                            $_SESSION['mensaje'] = "Estudiante registrado pero no se pudo asignar a la mesa: " . $asignacion['mensaje'];
                            $_SESSION['tipo'] = "warning";
                        }
                    } else {
                        $_SESSION['mensaje'] = "Estudiante registrado exitosamente.";
                        $_SESSION['tipo'] = "success";
                    }
                } else {
                    $_SESSION['mensaje'] = "Error al registrar estudiante: " . $resultado['mensaje'];
                    $_SESSION['tipo'] = "danger";
                }
            } catch (Exception $e) {
                $_SESSION['mensaje'] = "Error interno del servidor: " . $e->getMessage();
                $_SESSION['tipo'] = "danger";
            }

            header('Location: /Login/admin/asignar-estudiantes?mesa=' . $id_mesa);
            exit;
        }

        header('Location: /Login/admin/mesas-virtuales');
        exit;
    }

    /**
     * Desasignar estudiante de mesa
     */
    public function desasignarEstudianteMesa() {
        SessionManager::iniciarSesion();
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Location: /Login/admin/login');
            exit;
        }

        $id_estudiante = filter_input(INPUT_GET, 'id_estudiante', FILTER_VALIDATE_INT);
        $id_mesa = filter_input(INPUT_GET, 'id_mesa', FILTER_VALIDATE_INT);

        if ($id_estudiante && $id_mesa) {
            // Verificar que la mesa existe
            $mesa = $this->mesasModel->getMesaPorId($id_mesa);
            if (!$mesa) {
                $_SESSION['mensaje'] = "Mesa no encontrada.";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/admin/mesas-virtuales');
                exit;
            }

            // Verificar si la elección es modificable
            if (!$this->esEleccionModificable($mesa['id_eleccion'])) {
                $_SESSION['mensaje'] = "No se pueden desasignar estudiantes de una elección que ya finalizó.";
                $_SESSION['tipo'] = "danger";
                header('Location: /Login/admin/asignar-estudiantes?mesa=' . $id_mesa);
                exit;
            }

            $resultado = $this->mesasModel->desasignarEstudianteMesa($id_mesa, $id_estudiante);
            
            if ($resultado['success']) {
                $_SESSION['mensaje'] = "Estudiante desasignado exitosamente de la mesa.";
                $_SESSION['tipo'] = "success";
                
                // Registrar en logs
                $this->logsModel->registrarAccion('mesas_virtuales', 'Estudiante desasignado de mesa', $_SESSION['admin_id'] ?? null, [
                    'id_estudiante' => $id_estudiante,
                    'id_mesa' => $id_mesa
                ]);
            } else {
                $_SESSION['mensaje'] = "Error al desasignar estudiante: " . $resultado['mensaje'];
                $_SESSION['tipo'] = "danger";
            }
        } else {
            $_SESSION['mensaje'] = "Parámetros inválidos.";
            $_SESSION['tipo'] = "danger";
        }

        header('Location: /Login/admin/asignar-estudiantes?mesa=' . $id_mesa);
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
