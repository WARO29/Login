<?php

namespace models;

use mysqli;
use config\Database;

class Candidatos {
    private $conn;

    public function __construct($connection = null) {
        if ($connection === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $connection;
        }
    }

    public function __destruct() {
        // No cerramos la conexión aquí si fue proporcionada externamente
        if ($this->conn && func_num_args() === 0) {
            $database = new Database();
            $database->closeConnection();
        }
    }

    public function getCandidatosPorTipo($tipo) {
        $sql = "SELECT * FROM candidatos WHERE tipo_candidato = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $tipo);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCandidatosPorTipoYGrado($tipo, $grado) {
        $sql = "SELECT * FROM candidatos WHERE tipo_candidato = ? AND grado = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $tipo, $grado);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtiene un candidato por su ID
     * @param int $id_candidato ID del candidato
     * @return array|null Datos del candidato o null si no existe
     */
    public function getCandidatoPorId($id_candidato) {
        try {
            $sql = "SELECT * FROM candidatos WHERE id_candidato = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id_candidato);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                return $resultado->fetch_assoc();
            }
            return null;
        } catch (\Exception $e) {
            error_log("Error en getCandidatoPorId: " . $e->getMessage());
            return null;
        }
    }
}