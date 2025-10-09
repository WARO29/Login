<?php
namespace controllers;

use models\EleccionConfigModel;
use models\LogsAccesoModel;
use utils\EleccionMiddleware;

class EleccionConfigController {
    private $eleccionModel;
    private $logsModel;
    
    public function __construct() {
        $this->eleccionModel = new EleccionConfigModel();
        $this->logsModel = new LogsAccesoModel();
    }
    
    /**
     * Muestra el panel de configuración de elecciones
     */
    public function panelConfiguracion() {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Obtener todas las elecciones
        $todasElecciones = $this->eleccionModel->getTodasElecciones();
        $eleccionActiva = $this->eleccionModel->getConfiguracionActiva();
        $proximaEleccion = $this->eleccionModel->getProximaEleccion();
        $eleccionesPasadas = $this->eleccionModel->getEleccionesHistoricas();
        
        // Información de estado
        $estadoElecciones = EleccionMiddleware::obtenerMensajeEstado();
        $tiempoRestante = EleccionMiddleware::obtenerTiempoRestante();
        
        // Cargar la vista
        require_once 'views/admin/configuracion_elecciones.php';
    }
    
    /**
     * Muestra el formulario para crear una nueva elección
     */
    public function formularioNuevaEleccion() {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Cargar la vista
        require_once 'views/admin/nueva_eleccion.php';
    }
    
    /**
     * Muestra los detalles de una elección específica
     * @param int $id ID de la elección
     */
    public function detalleEleccion($id) {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Obtener la elección
        $eleccion = $this->eleccionModel->getEleccionPorId($id);
        if (!$eleccion) {
            $_SESSION['mensaje'] = 'La elección solicitada no existe.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Obtener logs de esta elección
        $logs = $this->logsModel->getLogsPorEleccion($id, 100);
        
        // Cargar la vista
        require_once 'views/admin/detalle_eleccion.php';
    }
    
    /**
     * Procesa la creación de una nueva elección
     */
    public function crearEleccion() {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Verificar que sea una petición POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Recoger los datos del formulario
        $datos = [
            'nombre_eleccion' => $_POST['nombre_eleccion'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_cierre' => $_POST['fecha_cierre'] ?? '',
            'tipos_votacion' => $_POST['tipos_votacion'] ?? [],
            'configuracion_adicional' => [
                'mostrar_resultados_parciales' => isset($_POST['mostrar_resultados_parciales']),
                'permitir_voto_blanco' => isset($_POST['permitir_voto_blanco']),
                'tiempo_maximo_votacion' => $_POST['tiempo_maximo_votacion'] ?? 0
            ],
            'estado' => 'programada',
            'creado_por' => $_SESSION['admin_id']
        ];
        
        // Validar datos
        if (empty($datos['nombre_eleccion']) || empty($datos['fecha_inicio']) || empty($datos['fecha_cierre'])) {
            $_SESSION['mensaje'] = 'Todos los campos obligatorios deben ser completados.';
            $_SESSION['tipo'] = 'error';
            $_SESSION['datos_formulario'] = $datos;
            header('Location: /Login/admin/nueva-eleccion');
            exit;
        }
        
        // Validar horarios
        $validacionHorarios = $this->eleccionModel->validarHorarios($datos['fecha_inicio'], $datos['fecha_cierre']);
        if (!$validacionHorarios['valido']) {
            $_SESSION['mensaje'] = $validacionHorarios['mensaje'];
            $_SESSION['tipo'] = 'error';
            $_SESSION['datos_formulario'] = $datos;
            header('Location: /Login/admin/nueva-eleccion');
            exit;
        }
        
        // Verificar conflictos de horario
        $verificacionConflictos = $this->eleccionModel->verificarConflictosHorarios($datos['fecha_inicio'], $datos['fecha_cierre']);
        if ($verificacionConflictos['hayConflicto']) {
            $_SESSION['mensaje'] = $verificacionConflictos['mensaje'];
            $_SESSION['tipo'] = 'error';
            $_SESSION['datos_formulario'] = $datos;
            header('Location: /Login/admin/nueva-eleccion');
            exit;
        }
        
        // Crear la elección
        $resultado = $this->eleccionModel->crearConfiguracion($datos);
        
        if ($resultado) {
            $_SESSION['mensaje'] = 'Elección creada correctamente.';
            $_SESSION['tipo'] = 'success';
            header('Location: /Login/admin/configuracion-elecciones');
        } else {
            $_SESSION['mensaje'] = 'Error al crear la elección.';
            $_SESSION['tipo'] = 'error';
            $_SESSION['datos_formulario'] = $datos;
            header('Location: /Login/admin/nueva-eleccion');
        }
        exit;
    }
    
    /**
     * Procesa la edición de una elección existente
     * @param int $id ID de la elección
     */
    public function editarEleccion($id) {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Verificar que sea una petición POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Verificar que la elección existe
        $eleccion = $this->eleccionModel->getEleccionPorId($id);
        if (!$eleccion) {
            $_SESSION['mensaje'] = 'La elección solicitada no existe.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Recoger los datos del formulario
        $datos = [
            'nombre_eleccion' => $_POST['nombre_eleccion'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_cierre' => $_POST['fecha_cierre'] ?? '',
            'tipos_votacion' => $_POST['tipos_votacion'] ?? [],
            'configuracion_adicional' => [
                'mostrar_resultados_parciales' => isset($_POST['mostrar_resultados_parciales']),
                'permitir_voto_blanco' => isset($_POST['permitir_voto_blanco']),
                'tiempo_maximo_votacion' => $_POST['tiempo_maximo_votacion'] ?? 0
            ],
            'estado' => $_POST['estado'] ?? $eleccion['estado']
        ];
        
        // Validar datos
        if (empty($datos['nombre_eleccion']) || empty($datos['fecha_inicio']) || empty($datos['fecha_cierre'])) {
            $_SESSION['mensaje'] = 'Todos los campos obligatorios deben ser completados.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/editar-eleccion/' . $id);
            exit;
        }
        
        // Validar horarios si no está activa o cerrada
        if ($datos['estado'] === 'programada') {
            $validacionHorarios = $this->eleccionModel->validarHorarios($datos['fecha_inicio'], $datos['fecha_cierre']);
            if (!$validacionHorarios['valido']) {
                $_SESSION['mensaje'] = $validacionHorarios['mensaje'];
                $_SESSION['tipo'] = 'error';
                header('Location: /Login/admin/editar-eleccion/' . $id);
                exit;
            }
            
            // Verificar conflictos de horario
            $verificacionConflictos = $this->eleccionModel->verificarConflictosHorarios($datos['fecha_inicio'], $datos['fecha_cierre'], $id);
            if ($verificacionConflictos['hayConflicto']) {
                $_SESSION['mensaje'] = $verificacionConflictos['mensaje'];
                $_SESSION['tipo'] = 'error';
                header('Location: /Login/admin/editar-eleccion/' . $id);
                exit;
            }
        }
        
        // Actualizar la elección
        $resultado = $this->eleccionModel->actualizarConfiguracion($id, $datos);
        
        if ($resultado) {
            $_SESSION['mensaje'] = 'Elección actualizada correctamente.';
            $_SESSION['tipo'] = 'success';
            header('Location: /Login/admin/configuracion-elecciones');
        } else {
            $_SESSION['mensaje'] = 'Error al actualizar la elección.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/editar-eleccion/' . $id);
        }
        exit;
    }
    
    /**
     * Elimina una elección
     * @param int $id ID de la elección
     */
    public function eliminarEleccion($id) {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Verificar que la elección existe
        $eleccion = $this->eleccionModel->getEleccionPorId($id);
        if (!$eleccion) {
            $_SESSION['mensaje'] = 'La elección solicitada no existe.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // No permitir eliminar elecciones activas
        if ($eleccion['estado'] === 'activa') {
            $_SESSION['mensaje'] = 'No se puede eliminar una elección activa. Cancélela primero.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Obtener información sobre los datos que se van a eliminar
        $datosRelacionados = $this->eleccionModel->obtenerDatosRelacionados($id);
        
        // Eliminar la elección y todos sus datos relacionados
        $resultado = $this->eleccionModel->eliminarConfiguracion($id);
        
        if ($resultado) {
            $mensaje = 'Elección eliminada correctamente.';
            
            // Agregar información sobre los datos eliminados
            $detalles = [];
            if ($datosRelacionados['votos_estudiantes'] > 0) {
                $detalles[] = $datosRelacionados['votos_estudiantes'] . ' votos de estudiantes';
            }
            if ($datosRelacionados['votos_docentes'] > 0) {
                $detalles[] = $datosRelacionados['votos_docentes'] . ' votos de docentes';
            }
            if ($datosRelacionados['votos_administrativos'] > 0) {
                $detalles[] = $datosRelacionados['votos_administrativos'] . ' votos administrativos';
            }
            if ($datosRelacionados['mesas_virtuales'] > 0) {
                $detalles[] = $datosRelacionados['mesas_virtuales'] . ' mesas virtuales';
            }
            if ($datosRelacionados['logs_acceso'] > 0) {
                $detalles[] = $datosRelacionados['logs_acceso'] . ' registros de acceso';
            }
            
            if (!empty($detalles)) {
                $mensaje .= ' También se eliminaron: ' . implode(', ', $detalles) . '.';
            }
            
            $_SESSION['mensaje'] = $mensaje;
            $_SESSION['tipo'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al eliminar la elección. Algunos datos relacionados podrían no haberse eliminado correctamente.';
            $_SESSION['tipo'] = 'error';
        }
        
        header('Location: /Login/admin/configuracion-elecciones');
        exit;
    }
    
    /**
     * Activa manualmente una elección
     * @param int $id ID de la elección
     */
    public function activarEleccion($id) {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Verificar que la elección existe
        $eleccion = $this->eleccionModel->getEleccionPorId($id);
        if (!$eleccion) {
            $_SESSION['mensaje'] = 'La elección solicitada no existe.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Verificar que la elección no esté ya activa, cerrada o cancelada
        if ($eleccion['estado'] === 'activa') {
            $_SESSION['mensaje'] = 'La elección ya está activa.';
            $_SESSION['tipo'] = 'warning';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        } else if ($eleccion['estado'] === 'cerrada' || $eleccion['estado'] === 'cancelada') {
            $_SESSION['mensaje'] = 'No se puede activar una elección que ya está cerrada o cancelada.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Si la elección está programada, se puede activar en cualquier momento
        
        // Verificar que no haya otra elección activa
        $eleccionActiva = $this->eleccionModel->getConfiguracionActiva();
        if ($eleccionActiva && $eleccionActiva['id'] != $id) {
            $_SESSION['mensaje'] = 'Ya hay otra elección activa. Cierre la elección actual antes de activar una nueva.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Activar la elección y ajustar fecha de inicio si es necesario
        $resultado = $this->eleccionModel->activarEleccionManual($id);
        
        if ($resultado) {
            $_SESSION['mensaje'] = 'Elección activada correctamente.';
            $_SESSION['tipo'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al activar la elección.';
            $_SESSION['tipo'] = 'error';
        }
        
        header('Location: /Login/admin/configuracion-elecciones');
        exit;
    }
    
    /**
     * Cierra manualmente una elección
     * @param int $id ID de la elección
     */
    public function cerrarEleccion($id) {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Verificar que la elección existe
        $eleccion = $this->eleccionModel->getEleccionPorId($id);
        if (!$eleccion) {
            $_SESSION['mensaje'] = 'La elección solicitada no existe.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Verificar que la elección esté activa
        if ($eleccion['estado'] !== 'activa') {
            $_SESSION['mensaje'] = 'Solo se pueden cerrar elecciones activas.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Cerrar la elección
        $resultado = $this->eleccionModel->cambiarEstadoEleccion($id, 'cerrada');
        
        if ($resultado) {
            $_SESSION['mensaje'] = 'Elección cerrada correctamente.';
            $_SESSION['tipo'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al cerrar la elección.';
            $_SESSION['tipo'] = 'error';
        }
        
        header('Location: /Login/admin/configuracion-elecciones');
        exit;
    }
    
    /**
     * Cancela una elección
     * @param int $id ID de la elección
     */
    public function cancelarEleccion($id) {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Verificar que la elección existe
        $eleccion = $this->eleccionModel->getEleccionPorId($id);
        if (!$eleccion) {
            $_SESSION['mensaje'] = 'La elección solicitada no existe.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Verificar que la elección no esté cerrada
        if ($eleccion['estado'] === 'cerrada') {
            $_SESSION['mensaje'] = 'No se puede cancelar una elección que ya está cerrada.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-elecciones');
            exit;
        }
        
        // Cancelar la elección
        $resultado = $this->eleccionModel->cambiarEstadoEleccion($id, 'cancelada');
        
        if ($resultado) {
            $_SESSION['mensaje'] = 'Elección cancelada correctamente.';
            $_SESSION['tipo'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al cancelar la elección.';
            $_SESSION['tipo'] = 'error';
        }
        
        header('Location: /Login/admin/configuracion-elecciones');
        exit;
    }
    
    /**
     * API para obtener el estado actual de las elecciones
     */
    public function obtenerEstadoElecciones() {
        header('Content-Type: application/json');
        
        $estadoElecciones = EleccionMiddleware::obtenerMensajeEstado();
        $tiempoRestante = EleccionMiddleware::obtenerTiempoRestante();
        $informacionEleccion = EleccionMiddleware::obtenerInformacionEleccion();
        
        $respuesta = [
            'estado' => $estadoElecciones['estado'],
            'mensaje' => $estadoElecciones['mensaje'],
            'tiempo_restante' => $tiempoRestante,
            'eleccion' => $informacionEleccion
        ];
        
        echo json_encode($respuesta);
        exit;
    }
    
    /**
     * API para obtener la configuración actual de elecciones
     */
    public function obtenerConfiguracionActual() {
        header('Content-Type: application/json');
        
        $eleccionActiva = $this->eleccionModel->getConfiguracionActiva();
        if ($eleccionActiva) {
            $respuesta = [
                'hay_eleccion_activa' => true,
                'eleccion' => $eleccionActiva
            ];
        } else {
            $proximaEleccion = $this->eleccionModel->getProximaEleccion();
            $respuesta = [
                'hay_eleccion_activa' => false,
                'proxima_eleccion' => $proximaEleccion
            ];
        }
        
        echo json_encode($respuesta);
        exit;
    }
    
    /**
     * API para verificar la disponibilidad de votación
     */
    public function verificarDisponibilidadVotacion() {
        header('Content-Type: application/json');
        
        $tipoUsuario = $_GET['tipo_usuario'] ?? '';
        $idUsuario = $_GET['id_usuario'] ?? '';
        
        if (empty($tipoUsuario) || empty($idUsuario)) {
            echo json_encode([
                'error' => 'Parámetros incompletos',
                'puede_votar' => false
            ]);
            exit;
        }
        
        $verificacion = EleccionMiddleware::puedeVotar($tipoUsuario, $idUsuario);
        echo json_encode($verificacion);
        exit;
    }
    
    /**
     * Muestra los logs de elecciones
     */
    public function mostrarLogs() {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Obtener logs de acceso
        $logs = $this->logsModel->getLogsRecientes(100);
        
        // Obtener estadísticas de logs
        $estadisticasAcceso = $this->logsModel->getEstadisticasAcceso();
        $estadisticasPorTipo = $this->logsModel->getEstadisticasAccesoPorTipoUsuario();
        
        // Cargar la vista
        require_once 'views/admin/logs_elecciones.php';
    }
    
    /**
     * Muestra el estado actual de elecciones para administradores
     */
    public function mostrarEstadoAdmin() {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Obtener información de estado
        $eleccionActiva = $this->eleccionModel->getConfiguracionActiva();
        $proximaEleccion = $this->eleccionModel->getProximaEleccion();
        $todasElecciones = $this->eleccionModel->getTodasElecciones();
        
        // Información de estado
        $estadoElecciones = \utils\EleccionMiddleware::obtenerMensajeEstado();
        $tiempoRestante = \utils\EleccionMiddleware::obtenerTiempoRestante();
        $informacionEleccion = \utils\EleccionMiddleware::obtenerInformacionEleccion();
        
        // Cargar la vista
        require_once 'views/admin/estado_elecciones_admin.php';
    }
}
