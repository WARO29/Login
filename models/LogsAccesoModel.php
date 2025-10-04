<?php
namespace models;

use config\Database;

class LogsAccesoModel {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Registra un log de acceso
     * @param array $datos Datos del log
     * @return bool Éxito de la operación
     */
    public function registrarLog($datos) {
        $sql = "INSERT INTO logs_acceso_elecciones 
                (id_eleccion, tipo_usuario, id_usuario, cedula_usuario, nombre_usuario, 
                accion, motivo, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('issssssss',
                $datos['id_eleccion'],
                $datos['tipo_usuario'],
                $datos['id_usuario'],
                $datos['cedula_usuario'],
                $datos['nombre_usuario'],
                $datos['accion'],
                $datos['motivo'],
                $datos['ip_address'],
                $datos['user_agent']
            );
            
            return $stmt->execute();
        }
        
        return false;
    }
    
    /**
     * Obtiene los logs más recientes
     * @param int $limite Número máximo de logs a obtener
     * @return array Lista de logs
     */
    public function getLogsRecientes($limite = 50) {
        $sql = "SELECT l.*, e.nombre_eleccion 
                FROM logs_acceso_elecciones l 
                LEFT JOIN configuracion_elecciones e ON l.id_eleccion = e.id 
                ORDER BY l.fecha_evento DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $logs = [];
        
        if ($stmt) {
            $stmt->bind_param('i', $limite);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        
        return $logs;
    }
    
    /**
     * Obtiene estadísticas de acceso por acción
     * @return array Estadísticas de acceso
     */
    public function getEstadisticasAcceso() {
        $sql = "SELECT accion, COUNT(*) as total 
                FROM logs_acceso_elecciones 
                GROUP BY accion";
        
        $result = $this->db->query($sql);
        $estadisticas = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $estadisticas[$row['accion']] = $row['total'];
            }
        }
        
        return $estadisticas;
    }
    
    /**
     * Obtiene estadísticas de acceso por tipo de usuario
     * @return array Estadísticas por tipo de usuario
     */
    public function getEstadisticasAccesoPorTipoUsuario() {
        $sql = "SELECT tipo_usuario, COUNT(*) as total 
                FROM logs_acceso_elecciones 
                GROUP BY tipo_usuario";
        
        $result = $this->db->query($sql);
        $estadisticas = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $estadisticas[$row['tipo_usuario']] = $row['total'];
            }
        }
        
        return $estadisticas;
    }
    
    /**
     * Obtiene logs de un usuario específico
     * @param string $tipoUsuario Tipo de usuario
     * @param string $idUsuario ID del usuario
     * @param int $limite Límite de resultados
     * @return array Lista de logs del usuario
     */
    public function getLogsPorUsuario($tipoUsuario, $idUsuario, $limite = 20) {
        $sql = "SELECT l.*, e.nombre_eleccion 
                FROM logs_acceso_elecciones l 
                LEFT JOIN configuracion_elecciones e ON l.id_eleccion = e.id 
                WHERE l.tipo_usuario = ? AND l.id_usuario = ? 
                ORDER BY l.fecha_evento DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $logs = [];
        
        if ($stmt) {
            $stmt->bind_param('ssi', $tipoUsuario, $idUsuario, $limite);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        
        return $logs;
    }
    
    /**
     * Limpia logs antiguos (más de X días)
     * @param int $dias Número de días a mantener
     * @return int Número de registros eliminados
     */
    public function limpiarLogsAntiguos($dias = 90) {
        $sql = "DELETE FROM logs_acceso_elecciones 
                WHERE fecha_evento < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $dias);
            if ($stmt->execute()) {
                return $stmt->affected_rows;
            }
        }
        
        return 0;
    }
    
    /**
     * Obtiene logs de una elección específica
     * @param int $idEleccion ID de la elección
     * @param int $limite Límite de resultados
     * @return array Lista de logs de la elección
     */
    public function getLogsPorEleccion($idEleccion, $limite = 50) {
        $sql = "SELECT l.*, e.nombre_eleccion 
                FROM logs_acceso_elecciones l 
                LEFT JOIN configuracion_elecciones e ON l.id_eleccion = e.id 
                WHERE l.id_eleccion = ? 
                ORDER BY l.fecha_evento DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $logs = [];
        
        if ($stmt) {
            $stmt->bind_param('ii', $idEleccion, $limite);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        
        return $logs;
    }
}