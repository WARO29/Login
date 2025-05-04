<?php
namespace models;

use config\Database;

class RepresentanteDocenteModel {
    private $db;
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
     * Obtiene todos los representantes docentes ordenados por nombre
     * @return array Array con todos los representantes docentes
     */
    public function getAll() {
        try {
            $representantes = [];
            $query = "SELECT * FROM representante_docente ORDER BY nombre_repre_docente";
            $resultado = $this->conn->query($query);
            
            if ($resultado && $resultado->num_rows > 0) {
                while ($row = $resultado->fetch_assoc()) {
                    $representantes[] = $row;
                }
            }
            return $representantes;
        } catch (\Exception $e) {
            error_log("Error en RepresentanteDocenteModel::getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un representante docente por su código
     * @param string $codigo Código del representante docente
     * @return array|false Datos del representante o false si no existe
     */
    public function getByCodigo($codigo) {
        try {
            $query = "SELECT * FROM representante_docente WHERE codigo_repres_docente = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                return $resultado->fetch_assoc();
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en RepresentanteDocenteModel::getByCodigo: " . $e->getMessage());
            return false;
        }
    }
}
