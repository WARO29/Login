<?php
namespace models;

use config\Database;

class Votos {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function __destruct() {
        if ($this->conn) {
            $database = new Database();
            $database->closeConnection();
        }
    }

    /**
     * Verifica si un estudiante ya ha votado por un tipo específico de candidato
     * @param int $id_estudiante ID del estudiante
     * @param string $tipo_voto Tipo de voto (PERSONERO o REPRESENTANTE)
     * @return bool Verdadero si ya ha votado, falso en caso contrario
     */
    public function haVotadoPorTipo($id_estudiante, $tipo_voto) {
        try {
            $query = "SELECT COUNT(*) as total FROM votos 
                      WHERE id_estudiante = ? AND tipo_voto = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("is", $id_estudiante, $tipo_voto);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                return $row['total'] > 0;
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en haVotadoPorTipo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un voto en la base de datos
     * @param int $id_estudiante ID del estudiante que vota
     * @param int $id_candidato ID del candidato por el que se vota
     * @param string $tipo_voto Tipo de voto (PERSONERO o REPRESENTANTE)
     * @return bool Verdadero si el voto se registró correctamente, falso en caso contrario
     */
    public function registrarVoto($id_estudiante, $id_candidato, $tipo_voto) {
        try {
            // Verificar si el estudiante ya ha votado para este tipo
            if ($this->haVotadoPorTipo($id_estudiante, $tipo_voto)) {
                return false;
            }
            
            $query = "INSERT INTO votos (id_estudiante, id_candidato, tipo_voto) 
                      VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iis", $id_estudiante, $id_candidato, $tipo_voto);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error en registrarVoto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un voto en blanco en la base de datos
     * @param int $id_estudiante ID del estudiante que vota
     * @param string $tipo_voto Tipo de voto (PERSONERO o REPRESENTANTE)
     * @return bool Verdadero si el voto se registró correctamente, falso en caso contrario
     */
    public function registrarVotoEnBlanco($id_estudiante, $tipo_voto) {
        try {
            // Verificar si el estudiante ya ha votado para este tipo
            if ($this->haVotadoPorTipo($id_estudiante, $tipo_voto)) {
                error_log("El estudiante ID: $id_estudiante ya ha votado para el tipo: $tipo_voto");
                return false;
            }
            
            // Para votos en blanco, usamos NULL como id_candidato
            // Aseguramos que $tipo_voto sea una cadena válida (PERSONERO o REPRESENTANTE)
            if (!in_array($tipo_voto, ['PERSONERO', 'REPRESENTANTE'])) {
                error_log("Tipo de voto inválido: $tipo_voto");
                return false;
            }
            
            $query = "INSERT INTO votos (id_estudiante, id_candidato, tipo_voto) 
                      VALUES (?, NULL, ?)";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error en la preparación de la consulta: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("is", $id_estudiante, $tipo_voto);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Error al ejecutar la consulta: " . $stmt->error);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error en registrarVotoEnBlanco: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Elimina todos los votos de un estudiante si no ha completado los dos tipos de voto
     * @param int $id_estudiante ID del estudiante
     * @return bool Verdadero si los votos fueron eliminados correctamente o si ya votó por ambos tipos
     */
    public function eliminarVotosIncompletos($id_estudiante) {
        try {
            // Verificar si el estudiante ha votado por ambos tipos
            $votoPersonero = $this->haVotadoPorTipo($id_estudiante, 'PERSONERO');
            $votoRepresentante = $this->haVotadoPorTipo($id_estudiante, 'REPRESENTANTE');
            
            // Si no ha votado por ambos tipos, eliminar los votos existentes
            if (!($votoPersonero && $votoRepresentante)) {
                $query = "DELETE FROM votos WHERE id_estudiante = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("i", $id_estudiante);
                return $stmt->execute();
            }
            
            return true; // Ya votó por ambos tipos, no es necesario eliminar
        } catch (\Exception $e) {
            error_log("Error en eliminarVotosIncompletos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finaliza la votación de un estudiante verificando que ha votado por ambos tipos
     * @param int $id_estudiante ID del estudiante
     * @return array Resultado con estado y mensaje
     */
    public function finalizarVotacion($id_estudiante) {
        try {
            // Verificar si el estudiante ha votado por ambos tipos
            $votoPersonero = $this->haVotadoPorTipo($id_estudiante, 'PERSONERO');
            $votoRepresentante = $this->haVotadoPorTipo($id_estudiante, 'REPRESENTANTE');
            
            if ($votoPersonero && $votoRepresentante) {
                return [
                    'completo' => true,
                    'mensaje' => 'Votación completada correctamente'
                ];
            } else {
                // Si no ha votado por ambos tipos, eliminar los votos y retornar mensaje
                $this->eliminarVotosIncompletos($id_estudiante);
                
                $faltantes = [];
                if (!$votoPersonero) $faltantes[] = 'personero';
                if (!$votoRepresentante) $faltantes[] = 'representante';
                
                return [
                    'completo' => false,
                    'mensaje' => 'Votación incompleta. No se registró voto para ' . implode(' y ', $faltantes),
                    'faltantes' => $faltantes
                ];
            }
        } catch (\Exception $e) {
            error_log("Error en finalizarVotacion: " . $e->getMessage());
            return [
                'completo' => false,
                'mensaje' => 'Error al procesar la votación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene todos los votos de la base de datos
     * @return array Arreglo con todos los votos
     */
    public function getAllVotos() {
        try {
            $query = "SELECT v.*, e.nombre as nombre_estudiante, c.nombre as nombre_candidato 
                      FROM votos v
                      LEFT JOIN estudiantes e ON v.id_estudiante = e.id_estudiante
                      LEFT JOIN candidatos c ON v.id_candidato = c.id_candidato
                      ORDER BY v.fecha_voto DESC";
            $resultado = $this->conn->query($query);
            
            if ($resultado && $resultado->num_rows > 0) {
                return $resultado->fetch_all(MYSQLI_ASSOC);
            }
            return [];
        } catch (\Exception $e) {
            error_log("Error en getAllVotos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el conteo de votos por candidato y tipo
     * @param string $tipo_voto Tipo de voto (PERSONERO o REPRESENTANTE)
     * @return array Arreglo con el conteo de votos por candidato
     */
    public function getConteoVotosPorTipo($tipo_voto) {
        try {
            $query = "SELECT c.id_candidato, c.nombre, c.apellido, c.foto, c.numero, 
                      COUNT(v.id_voto) as total_votos 
                      FROM candidatos c
                      LEFT JOIN votos v ON c.id_candidato = v.id_candidato AND v.tipo_voto = ?
                      WHERE c.tipo_candidato = ?
                      GROUP BY c.id_candidato
                      ORDER BY total_votos DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ss", $tipo_voto, $tipo_voto);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                return $resultado->fetch_all(MYSQLI_ASSOC);
            }
            return [];
        } catch (\Exception $e) {
            error_log("Error en getConteoVotosPorTipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el conteo de votos en blanco por tipo
     * @param string $tipo_voto Tipo de voto (PERSONERO o REPRESENTANTE)
     * @return int Cantidad de votos en blanco
     */
    public function getConteoVotosEnBlanco($tipo_voto) {
        try {
            $query = "SELECT COUNT(*) as total FROM votos 
                      WHERE id_candidato IS NULL AND tipo_voto = ?";
            $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $tipo_voto);
        $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                return $row['total'];
            }
            return 0;
        } catch (\Exception $e) {
            error_log("Error en getConteoVotosEnBlanco: " . $e->getMessage());
            return 0;
        }
    }
}
?>