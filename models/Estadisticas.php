<?php
namespace models;

use config\Database;

class Estadisticas {
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
     * Obtiene el total de estudiantes registrados en el sistema
     * @return int Total de estudiantes
     */
    public function getTotalEstudiantes() {
        try {
            $query = "SELECT COUNT(*) as total FROM estudiantes";
            $result = $this->conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return (int)$row['total'];
            }
            
            return 0;
        } catch (\Exception $e) {
            error_log("Error al obtener el total de estudiantes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene el total de votos registrados en el sistema
     * @return int Total de votos
     */
    public function getTotalVotos() {
        try {
            $query = "SELECT COUNT(DISTINCT id_estudiante) as total FROM votos";
            $result = $this->conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return (int)$row['total'];
            }
            
            return 0;
        } catch (\Exception $e) {
            error_log("Error al obtener el total de votos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene el total de candidatos registrados en el sistema
     * @return int Total de candidatos
     */
    public function getTotalCandidatos() {
        try {
            $query = "SELECT COUNT(*) as total FROM candidatos";
            $result = $this->conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return (int)$row['total'];
            }
            
            return 0;
        } catch (\Exception $e) {
            error_log("Error al obtener el total de candidatos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcula el porcentaje de participación en la votación
     * @return float Porcentaje de participación
     */
    public function getPorcentajeParticipacion() {
        try {
            $totalEstudiantes = $this->getTotalEstudiantes();
            
            if ($totalEstudiantes == 0) {
                return 0;
            }
            
            $totalVotos = $this->getTotalVotos();
            
            return round(($totalVotos / $totalEstudiantes) * 100, 1);
        } catch (\Exception $e) {
            error_log("Error al calcular el porcentaje de participación: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene los estudiantes que han votado recientemente
     * @param int $limit Número máximo de registros a devolver
     * @return array Lista de votos recientes
     */
    public function getVotosRecientes($limit = 5) {
        try {
            $query = "SELECT v.id_voto, v.id_estudiante, v.id_candidato, v.tipo_voto, v.fecha_voto, 
                      e.nombre as nombre_estudiante, e.apellido as apellido_estudiante,
                      c.nombre as nombre_candidato, c.apellido as apellido_candidato
                      FROM votos v
                      LEFT JOIN estudiantes e ON v.id_estudiante = e.id_estudiante
                      LEFT JOIN candidatos c ON v.id_candidato = c.id_candidato
                      ORDER BY v.fecha_voto DESC
                      LIMIT ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            
            return [];
        } catch (\Exception $e) {
            error_log("Error al obtener votos recientes: " . $e->getMessage());
            return [];
        }
    }
}
?>
