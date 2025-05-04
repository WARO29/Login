<?php
namespace controllers;

use models\Votos;
use models\EstudianteModel;
use models\Candidatos;

class ProcesarVotosController {
    private $votosModel;
    private $estudiantesModel;
    private $candidatosModel;
    private $tiempoMaximoVotacion = 300; // 5 minutos en segundos

    public function __construct() {
        $this->votosModel = new Votos();
        $this->estudiantesModel = new EstudianteModel();
        $this->candidatosModel = new Candidatos();
    }

    public function procesarVoto() {
        // Verificar si hay sesión activa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar que el usuario esté autenticado como estudiante
        if (!isset($_SESSION['estudiante_id']) || !$_SESSION['es_estudiante']) {
            $_SESSION['mensaje'] = "Debes iniciar sesión para votar";
            $_SESSION['tipo'] = "danger";
            header("Location: /Login/");
            exit();
        }

        // Iniciar o actualizar el tiempo de votación
        if (!isset($_SESSION['tiempo_inicio_votacion'])) {
            $_SESSION['tiempo_inicio_votacion'] = time();
        }

        // Verificar que sea una petición POST
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $_SESSION['mensaje'] = "Método de solicitud inválido";
            $_SESSION['tipo'] = "danger";
            header("Location: /Login/views/estudiantes/votos.php");
            exit();
        }

        // Obtener y validar los datos del formulario
        $id_candidato = filter_input(INPUT_POST, 'id_candidato', FILTER_VALIDATE_INT);
        $tipo_voto = filter_input(INPUT_POST, 'tipo_candidato', FILTER_SANITIZE_STRING);
        $id_estudiante = $_SESSION['estudiante_id'];

        // Verificar que los datos sean válidos
        if (!$id_candidato || !in_array($tipo_voto, ['PERSONERO', 'REPRESENTANTE'])) {
            $_SESSION['mensaje'] = "Datos de votación inválidos";
            $_SESSION['tipo'] = "danger";
            header("Location: /Login/views/estudiantes/votos.php");
            exit();
        }

        // Verificar si se excedió el tiempo máximo de votación
        if (time() - $_SESSION['tiempo_inicio_votacion'] > $this->tiempoMaximoVotacion) {
            // Si excedió el tiempo, eliminar votos parciales
            $this->votosModel->eliminarVotosIncompletos($id_estudiante);
            $_SESSION['mensaje'] = "Se ha excedido el tiempo máximo para votar. Tus votos parciales han sido anulados.";
            $_SESSION['tipo'] = "danger";
            unset($_SESSION['tiempo_inicio_votacion']);
            header("Location: /Login/views/estudiantes/votos.php");
            exit();
        }

        // Verificar que el estudiante no haya votado ya para este tipo de candidato
        if ($this->votosModel->haVotadoPorTipo($id_estudiante, $tipo_voto)) {
            $_SESSION['mensaje'] = "Ya has ejercido tu voto para " . strtolower($tipo_voto);
            $_SESSION['tipo'] = "warning";
            header("Location: /Login/views/estudiantes/votos.php");
            exit();
        }

        // Obtener información del candidato
        $candidato = $this->candidatosModel->getCandidatoPorId($id_candidato);
        
        // Registrar el voto
        $resultado = $this->votosModel->registrarVoto($id_estudiante, $id_candidato, $tipo_voto);

        if ($resultado) {
            // Guardar información del candidato en sesión
            if ($tipo_voto == 'PERSONERO') {
                $_SESSION['nombre_personero'] = !empty($candidato) ? $candidato['nombre'] . ' ' . $candidato['apellido'] : 'Candidato #' . $id_candidato;
                $_SESSION['id_personero'] = $id_candidato;
            } else {
                $_SESSION['nombre_representante'] = !empty($candidato) ? $candidato['nombre'] . ' ' . $candidato['apellido'] : 'Candidato #' . $id_candidato;
                $_SESSION['id_representante'] = $id_candidato;
            }
            
            $_SESSION['mensaje'] = "Tu voto por " . strtolower($tipo_voto) . " ha sido registrado correctamente";
            $_SESSION['tipo'] = "success";
            
            // Si ya votó por ambos tipos, redirigir a página de confirmación
            if ($this->votosModel->haVotadoPorTipo($id_estudiante, 'PERSONERO') && 
                $this->votosModel->haVotadoPorTipo($id_estudiante, 'REPRESENTANTE')) {
                $this->finalizarVotacion($id_estudiante);
                exit();
            }
            
            // Si falta votar por un tipo, mostrar mensaje indicando que debe completar la votación
            $_SESSION['mensaje'] = "Has votado por " . strtolower($tipo_voto) . ". Debes completar tu voto para ambos tipos de candidatos.";
            $_SESSION['tipo'] = "warning";
            header("Location: /Login/views/estudiantes/votos.php");
            exit();
        } else {
            $_SESSION['mensaje'] = "Ocurrió un error al registrar tu voto. Por favor, intenta nuevamente.";
            $_SESSION['tipo'] = "danger";
            header("Location: /Login/views/estudiantes/votos.php");
            exit();
        }
    }

    public function procesarVotoEnBlanco() {
        // Verificar si hay sesión activa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        try {
            // Verificar que el usuario esté autenticado como estudiante
            if (!isset($_SESSION['estudiante_id']) || !$_SESSION['es_estudiante']) {
                $_SESSION['mensaje'] = "Debes iniciar sesión para votar";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/");
                exit();
            }

            // Iniciar o actualizar el tiempo de votación
            if (!isset($_SESSION['tiempo_inicio_votacion'])) {
                $_SESSION['tiempo_inicio_votacion'] = time();
            }

            // Verificar que sea una petición POST
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                $_SESSION['mensaje'] = "Método de solicitud inválido";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/views/estudiantes/votos.php");
                exit();
            }
            
            // Obtener y validar el tipo de voto
            $tipo_voto = filter_input(INPUT_POST, 'tipo_voto', FILTER_SANITIZE_STRING);
            $id_estudiante = $_SESSION['estudiante_id'];
            
            // Log de depuración
            error_log("[DEBUG] Iniciando procesamiento de voto en blanco - Estudiante ID: $id_estudiante, Tipo: $tipo_voto");

            // Verificar que el tipo de voto sea válido
            if (!in_array($tipo_voto, ['PERSONERO', 'REPRESENTANTE'])) {
                $_SESSION['mensaje'] = "Tipo de voto inválido: " . htmlspecialchars($tipo_voto);
                $_SESSION['tipo'] = "danger";
                error_log("[ERROR] Tipo de voto inválido: $tipo_voto");
                header("Location: /Login/views/estudiantes/votos.php");
                exit();
            }

            // Verificar si se excedió el tiempo máximo de votación
            if (time() - $_SESSION['tiempo_inicio_votacion'] > $this->tiempoMaximoVotacion) {
                // Si excedió el tiempo, eliminar votos parciales
                $this->votosModel->eliminarVotosIncompletos($id_estudiante);
                $_SESSION['mensaje'] = "Se ha excedido el tiempo máximo para votar. Tus votos parciales han sido anulados.";
                $_SESSION['tipo'] = "danger";
                error_log("[ERROR] Tiempo excedido para estudiante ID: $id_estudiante");
                unset($_SESSION['tiempo_inicio_votacion']);
                header("Location: /Login/views/estudiantes/votos.php");
                exit();
            }

            // Verificar que el estudiante no haya votado ya para este tipo de candidato
            if ($this->votosModel->haVotadoPorTipo($id_estudiante, $tipo_voto)) {
                $_SESSION['mensaje'] = "Ya has ejercido tu voto para " . strtolower($tipo_voto);
                $_SESSION['tipo'] = "warning";
                error_log("[INFO] Estudiante ID: $id_estudiante ya ha votado por $tipo_voto");
                header("Location: /Login/views/estudiantes/votos.php");
                exit();
            }

            // Registrar el voto en blanco usando la función segura
            $resultado = $this->registrarVotoEnBlancoSeguro($id_estudiante, $tipo_voto);

            if ($resultado) {
                // Guardar información del voto en blanco en sesión
                if ($tipo_voto == 'PERSONERO') {
                    $_SESSION['nombre_personero'] = 'Voto en Blanco';
                    $_SESSION['id_personero'] = 0;
                } else {
                    $_SESSION['nombre_representante'] = 'Voto en Blanco';
                    $_SESSION['id_representante'] = 0;
                }
                
                error_log("[SUCCESS] Voto en blanco registrado correctamente - Estudiante ID: $id_estudiante, Tipo: $tipo_voto");
                $_SESSION['mensaje'] = "Tu voto en blanco para " . strtolower($tipo_voto) . " ha sido registrado correctamente";
                $_SESSION['tipo'] = "success";
                
                // Si ya votó por ambos tipos, redirigir a página de confirmación
                if ($this->votosModel->haVotadoPorTipo($id_estudiante, 'PERSONERO') && 
                    $this->votosModel->haVotadoPorTipo($id_estudiante, 'REPRESENTANTE')) {
                    $this->finalizarVotacion($id_estudiante);
                    exit();
                }
                
                // Si falta votar por un tipo, mostrar mensaje indicando que debe completar la votación
                $_SESSION['mensaje'] = "Has votado en blanco para " . strtolower($tipo_voto) . ". Debes completar tu voto para ambos tipos de candidatos.";
                $_SESSION['tipo'] = "warning";
                header("Location: /Login/views/estudiantes/votos.php");
                exit();
            } else {
                error_log("[ERROR] Error al registrar voto en blanco - Estudiante ID: $id_estudiante, Tipo: $tipo_voto");
                $_SESSION['mensaje'] = "Ocurrió un error al registrar tu voto. Por favor, intenta nuevamente.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/views/estudiantes/votos.php");
                exit();
            }
        } catch (\Exception $e) {
            // Capturar cualquier excepción que pueda ocurrir
            error_log("[CRITICAL] Excepción en procesarVotoEnBlanco: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $_SESSION['mensaje'] = "Ocurrió un error al procesar tu voto: " . $e->getMessage();
            $_SESSION['tipo'] = "danger";
            header("Location: /Login/views/estudiantes/votos.php");
            exit();
        }
    }

    /**
     * Método seguro para registrar un voto en blanco
     * Implementación directa para evitar problemas con el modelo
     */
    private function registrarVotoEnBlancoSeguro($id_estudiante, $tipo_voto) {
        try {
            // Verificación de parámetros
            $id_estudiante = (int)$id_estudiante;
            if ($id_estudiante <= 0) {
                error_log("[ERROR] ID de estudiante inválido: $id_estudiante");
                return false;
            }
            
            if (!in_array($tipo_voto, ['PERSONERO', 'REPRESENTANTE'])) {
                error_log("[ERROR] Tipo de voto inválido: $tipo_voto");
                return false;
            }
            
            // Obtener la conexión directamente
            $database = new \config\Database();
            $conn = $database->getConnection();
            
            // Verificar si el estudiante ya votó para este tipo
            $check_query = "SELECT COUNT(*) as total FROM votos WHERE id_estudiante = ? AND tipo_voto = ?";
            $stmt = $conn->prepare($check_query);
            if (!$stmt) {
                error_log("[ERROR] Error en la preparación de consulta de verificación: " . $conn->error);
                return false;
            }
            
            $stmt->bind_param("is", $id_estudiante, $tipo_voto);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['total'] > 0) {
                    error_log("[INFO] El estudiante ya ha votado para este tipo: $tipo_voto");
                    return false;
                }
            }
            
            // Insertar voto en blanco directamente
            $query = "INSERT INTO votos (id_estudiante, id_candidato, tipo_voto) VALUES (?, NULL, ?)";
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                error_log("[ERROR] Error en la preparación de consulta de inserción: " . $conn->error);
                return false;
            }
            
            $stmt->bind_param("is", $id_estudiante, $tipo_voto);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("[ERROR] Error al ejecutar la consulta de inserción: " . $stmt->error);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("[CRITICAL] Excepción en registrarVotoEnBlancoSeguro: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Finaliza la votación del estudiante
     * @param int $id_estudiante ID del estudiante
     */
    public function finalizarVotacion($id_estudiante) {
        // Verificar que el usuario haya votado por ambos tipos de candidatos
        $resultado = $this->votosModel->finalizarVotacion($id_estudiante);
        
        if ($resultado['completo']) {
            // Eliminar el tiempo de votación
            unset($_SESSION['tiempo_inicio_votacion']);
            
            $_SESSION['mensaje'] = $resultado['mensaje'];
            $_SESSION['tipo'] = "success";
            header("Location: /Login/views/confirmacion.php");
            exit();
        } else {
            $_SESSION['mensaje'] = $resultado['mensaje'];
            $_SESSION['tipo'] = "warning";
            header("Location: /Login/views/estudiantes/votos.php");
            exit();
        }
    }

    /**
     * Cancela la votación del estudiante eliminando sus votos parciales
     */
    public function cancelarVotacion() {
        // Verificar si hay sesión activa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar que el usuario esté autenticado
        if (!isset($_SESSION['estudiante_id']) || !$_SESSION['es_estudiante']) {
            $_SESSION['mensaje'] = "Debes iniciar sesión para realizar esta acción";
            $_SESSION['tipo'] = "danger";
            header("Location: /Login/");
            exit();
        }

        $id_estudiante = $_SESSION['estudiante_id'];
        
        // Eliminar los votos parciales
        $resultado = $this->votosModel->eliminarVotosIncompletos($id_estudiante);
        
        if ($resultado) {
            // Limpiar información de votos en la sesión
            unset($_SESSION['nombre_personero']);
            unset($_SESSION['nombre_representante']);
            unset($_SESSION['id_personero']);
            unset($_SESSION['id_representante']);
            
            $_SESSION['mensaje'] = "Has cancelado tu votación. Todos tus votos han sido anulados.";
            $_SESSION['tipo'] = "info";
        } else {
            $_SESSION['mensaje'] = "Ocurrió un error al cancelar tu votación.";
            $_SESSION['tipo'] = "danger";
        }
        
        // Eliminar el tiempo de votación
        unset($_SESSION['tiempo_inicio_votacion']);
        
        header("Location: /Login/views/estudiantes/votos.php");
        exit();
    }
} 