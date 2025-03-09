<?php

namespace models;

use mysqli;

class Candidatos {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function getCandidatosPorTipo($tipo) {
        $sql = "SELECT * FROM candidatos WHERE tipo_candidato = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $tipo);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}