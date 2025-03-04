<?php

namespace models;

//use config\Database;
use mysqli;

class Estudiantes {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function authenticate($documento) {
        $sql = "SELECT * FROM estudiantes WHERE documento = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $documento);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc(); // Retorna el usuario si se encuentra
        }
        return false; // Retorna falso si no se encuentra el usuario
    }

    // Método para verificar si el estudiante ya votó
    public function hasVoted($documento) {
        $sql = "SELECT * FROM votos WHERE id_estudiante = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $documento);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }


}
?> 