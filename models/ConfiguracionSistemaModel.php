<?php
namespace models;

use config\Database;

class ConfiguracionSistemaModel {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Obtiene el valor de una configuración
     * @param string $clave Clave de la configuración
     * @return mixed Valor de la configuración o null si no existe
     */
    public function obtener($clave) {
        $sql = "SELECT valor, tipo FROM configuracion_sistema WHERE clave = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param('s', $clave);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $this->convertirValor($row['valor'], $row['tipo']);
            }
        }
        
        return null;
    }
    
    /**
     * Establece el valor de una configuración
     * @param string $clave Clave de la configuración
     * @param mixed $valor Valor de la configuración
     * @param string $descripcion Descripción de la configuración
     * @param string $tipo Tipo de dato
     * @param string $categoria Categoría de la configuración
     * @param int $modificadoPor ID del usuario que modifica
     * @return bool Éxito de la operación
     */
    public function establecer($clave, $valor, $descripcion = '', $tipo = 'string', $categoria = 'general', $modificadoPor = null) {
        // Convertir el valor según el tipo
        $valorConvertido = $this->prepararValor($valor, $tipo);
        
        $sql = "INSERT INTO configuracion_sistema 
                (clave, valor, descripcion, tipo, categoria, modificado_por) 
                VALUES (?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                valor = VALUES(valor), 
                descripcion = VALUES(descripcion), 
                tipo = VALUES(tipo), 
                categoria = VALUES(categoria), 
                modificado_por = VALUES(modificado_por)";
        
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sssssi', $clave, $valorConvertido, $descripcion, $tipo, $categoria, $modificadoPor);
            return $stmt->execute();
        }
        
        return false;
    }
    
    /**
     * Obtiene todas las configuraciones
     * @return array Lista de todas las configuraciones
     */
    public function obtenerTodas() {
        $sql = "SELECT * FROM configuracion_sistema ORDER BY categoria, clave";
        $result = $this->db->query($sql);
        
        $configuraciones = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['valor'] = $this->convertirValor($row['valor'], $row['tipo']);
                $configuraciones[] = $row;
            }
        }
        
        return $configuraciones;
    }
    
    /**
     * Obtiene configuraciones por categoría
     * @param string $categoria Categoría de las configuraciones
     * @return array Lista de configuraciones de la categoría
     */
    public function obtenerPorCategoria($categoria) {
        $sql = "SELECT * FROM configuracion_sistema WHERE categoria = ? ORDER BY clave";
        $stmt = $this->db->prepare($sql);
        
        $configuraciones = [];
        if ($stmt) {
            $stmt->bind_param('s', $categoria);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $row['valor'] = $this->convertirValor($row['valor'], $row['tipo']);
                $configuraciones[] = $row;
            }
        }
        
        return $configuraciones;
    }
    
    /**
     * Elimina una configuración
     * @param string $clave Clave de la configuración a eliminar
     * @return bool Éxito de la operación
     */
    public function eliminar($clave) {
        $sql = "DELETE FROM configuracion_sistema WHERE clave = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param('s', $clave);
            return $stmt->execute();
        }
        
        return false;
    }
    
    /**
     * Valida una configuración según su tipo
     * @param string $tipo Tipo de la configuración
     * @param mixed $valor Valor a validar
     * @return bool True si el valor es válido para el tipo
     */
    public function validarConfiguracion($tipo, $valor) {
        switch ($tipo) {
            case 'boolean':
                return in_array(strtolower($valor), ['true', 'false', '1', '0', 'yes', 'no']);
            
            case 'integer':
                return is_numeric($valor) && is_int($valor + 0);
            
            case 'datetime':
                return strtotime($valor) !== false;
            
            case 'json':
                json_decode($valor);
                return json_last_error() === JSON_ERROR_NONE;
            
            case 'string':
            default:
                return is_string($valor) || is_numeric($valor);
        }
    }
    
    /**
     * Convierte un valor desde la base de datos según su tipo
     * @param string $valor Valor desde la base de datos
     * @param string $tipo Tipo del valor
     * @return mixed Valor convertido
     */
    private function convertirValor($valor, $tipo) {
        switch ($tipo) {
            case 'boolean':
                return in_array(strtolower($valor), ['true', '1', 'yes']);
            
            case 'integer':
                return (int) $valor;
            
            case 'datetime':
                return $valor; // Mantener como string para facilidad de uso
            
            case 'json':
                return json_decode($valor, true);
            
            case 'string':
            default:
                return $valor;
        }
    }
    
    /**
     * Prepara un valor para almacenarlo en la base de datos
     * @param mixed $valor Valor a preparar
     * @param string $tipo Tipo del valor
     * @return string Valor preparado para la base de datos
     */
    private function prepararValor($valor, $tipo) {
        switch ($tipo) {
            case 'boolean':
                return $valor ? 'true' : 'false';
            
            case 'integer':
                return (string) (int) $valor;
            
            case 'datetime':
                return $valor; // Asumir que ya está en formato correcto
            
            case 'json':
                return is_string($valor) ? $valor : json_encode($valor);
            
            case 'string':
            default:
                return (string) $valor;
        }
    }
    
    /**
     * Obtiene la configuración de horario de votación
     * @return array Configuración de horario [activo, inicio, cierre]
     */
    public function getHorarioVotacion() {
        $activo = $this->obtener('horario_votacion_activo') ?? false;
        $inicio = $this->obtener('horario_votacion_inicio') ?? '08:00:00';
        $cierre = $this->obtener('horario_votacion_cierre') ?? '17:00:00';
        
        return [
            'activo' => $activo,
            'inicio' => $inicio,
            'cierre' => $cierre
        ];
    }
}