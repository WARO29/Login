<?php
namespace models;

use config\Database;
use PDO;
use Exception;

class LogsModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Registrar una acción en el sistema de logs
     */
    public function registrarAccion($tipo, $descripcion, $usuario_id = null, $datos_adicionales = null) {
        try {
            $sql = "INSERT INTO logs_sistema (tipo, descripcion, usuario_id, datos_adicionales, fecha_hora, ip_address) 
                    VALUES (?, ?, ?, ?, NOW(), ?)";
            
            $stmt = $this->db->prepare($sql);
            
            // Obtener IP del usuario
            $ip_address = $this->obtenerIPUsuario();
            
            // Convertir datos adicionales a JSON si es un array
            if (is_array($datos_adicionales)) {
                $datos_adicionales = json_encode($datos_adicionales);
            }
            
            $stmt->execute([
                $tipo,
                $descripcion,
                $usuario_id,
                $datos_adicionales,
                $ip_address
            ]);
            
            return $this->db->insert_id;
            
        } catch (Exception $e) {
            error_log("Error al registrar log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar acciones específicas de mesas virtuales
     */
    public function registrarAccionMesas($accion, $id_eleccion, $id_mesa = null, $detalles = null) {
        $usuario_id = $_SESSION['admin_id'] ?? null;
        
        $descripcion = $this->generarDescripcionMesas($accion, $id_eleccion, $id_mesa, $detalles);
        
        return $this->registrarAccion('mesas_virtuales', $descripcion, $usuario_id, [
            'accion' => $accion,
            'id_eleccion' => $id_eleccion,
            'id_mesa' => $id_mesa,
            'detalles' => $detalles
        ]);
    }

    /**
     * Obtener logs del sistema con filtros
     */
    public function obtenerLogs($filtros = []) {
        try {
            $sql = "SELECT l.*, a.usuario as admin_usuario 
                    FROM logs_sistema l 
                    LEFT JOIN administradores a ON l.usuario_id = a.id 
                    WHERE 1=1";
            
            $params = [];
            
            // Filtro por tipo
            if (!empty($filtros['tipo'])) {
                $sql .= " AND l.tipo = ?";
                $params[] = $filtros['tipo'];
            }
            
            // Filtro por fecha desde
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND DATE(l.fecha_hora) >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            
            // Filtro por fecha hasta
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND DATE(l.fecha_hora) <= ?";
                $params[] = $filtros['fecha_hasta'];
            }
            
            // Filtro por usuario
            if (!empty($filtros['usuario_id'])) {
                $sql .= " AND l.usuario_id = ?";
                $params[] = $filtros['usuario_id'];
            }
            
            $sql .= " ORDER BY l.fecha_hora DESC";
            
            // Límite de resultados
            $limite = $filtros['limite'] ?? 100;
            $sql .= " LIMIT ?";
            $params[] = $limite;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error al obtener logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de logs
     */
    public function obtenerEstadisticasLogs($dias = 30) {
        try {
            $sql = "SELECT 
                        tipo,
                        COUNT(*) as total,
                        DATE(fecha_hora) as fecha
                    FROM logs_sistema 
                    WHERE fecha_hora >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY tipo, DATE(fecha_hora)
                    ORDER BY fecha DESC, total DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dias]);
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas de logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpiar logs antiguos
     */
    public function limpiarLogsAntiguos($dias = 90) {
        try {
            $sql = "DELETE FROM logs_sistema WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dias]);
            
            return $stmt->affected_rows;
            
        } catch (Exception $e) {
            error_log("Error al limpiar logs antiguos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener logs específicos de mesas virtuales
     */
    public function obtenerLogsMesasVirtuales($id_eleccion = null, $limite = 50) {
        $filtros = [
            'tipo' => 'mesas_virtuales',
            'limite' => $limite
        ];
        
        $logs = $this->obtenerLogs($filtros);
        
        // Filtrar por elección si se especifica
        if ($id_eleccion) {
            $logs = array_filter($logs, function($log) use ($id_eleccion) {
                $datos = json_decode($log['datos_adicionales'], true);
                return isset($datos['id_eleccion']) && $datos['id_eleccion'] == $id_eleccion;
            });
        }
        
        return $logs;
    }

    /**
     * Generar descripción para acciones de mesas virtuales
     */
    private function generarDescripcionMesas($accion, $id_eleccion, $id_mesa, $detalles) {
        $descripciones = [
            'crear_mesas' => "Creó mesas virtuales para la elección ID: $id_eleccion",
            'generar_personal' => "Generó personal automático para la elección ID: $id_eleccion",
            'agregar_personal' => "Agregó personal a la mesa ID: $id_mesa",
            'eliminar_personal' => "Eliminó personal de la mesa ID: $id_mesa",
            'asignar_estudiantes' => "Reasignó estudiantes para la elección ID: $id_eleccion",
            'limpiar_personal' => "Limpió personal de la elección ID: $id_eleccion",
            'cerrar_mesas' => "Cerró mesas de la elección ID: $id_eleccion",
            'ver_mesa' => "Consultó detalles de la mesa ID: $id_mesa",
            'exportar_datos' => "Exportó datos de la elección ID: $id_eleccion"
        ];
        
        $descripcion_base = $descripciones[$accion] ?? "Acción '$accion' en mesas virtuales";
        
        if ($detalles) {
            $descripcion_base .= " - Detalles: $detalles";
        }
        
        return $descripcion_base;
    }

    /**
     * Obtener IP del usuario
     */
    private function obtenerIPUsuario() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return '0.0.0.0';
    }

    /**
     * Crear tabla de logs si no existe
     */
    public function crearTablaLogs() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS logs_sistema (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tipo VARCHAR(50) NOT NULL,
                descripcion TEXT NOT NULL,
                usuario_id INT NULL,
                datos_adicionales JSON NULL,
                fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45) NULL,
                INDEX idx_tipo (tipo),
                INDEX idx_fecha (fecha_hora),
                INDEX idx_usuario (usuario_id),
                FOREIGN KEY (usuario_id) REFERENCES administradores(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->query($sql);
            return true;
            
        } catch (Exception $e) {
            error_log("Error al crear tabla de logs: " . $e->getMessage());
            return false;
        }
    }
}
?>
