<?php
namespace models;

use mysqli;

class Votos {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    // Método para registrar un voto
    public function registrarVoto($documento, $candidato_id, $tipo_voto) {
        $sql = "INSERT INTO votos (estudiante_documento, id_candidato, tipo_voto) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            throw new \Exception("Error en la preparación de la consulta: " . $this->conn->error);
        }

        $stmt->bind_param("sis", $documento, $candidato_id, $tipo_voto);
        
        if (!$stmt->execute()) {
            throw new \Exception("Error al registrar el voto: " . $stmt->error);
        }

        return true; // Retorna true si el voto se registró correctamente
    }

    // Método para registrar un voto en blanco
    public function registrarVotoBlanco($documento, $tipo_voto) {
        $sql = "INSERT INTO votos_blanco (estudiante_documento, tipo_voto) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            throw new \Exception("Error en la preparación de la consulta: " . $this->conn->error);
        }

        $stmt->bind_param("ss", $documento, $tipo_voto);
        
        if (!$stmt->execute()) {
            throw new \Exception("Error al registrar el voto en blanco: " . $stmt->error);
        }

        return true; // Retorna true si el voto en blanco se registró correctamente
    }

    // Método para verificar si un estudiante ya ha votado
    public function hasVoted($documento) {
        $sql = "SELECT COUNT(*) as total FROM votos WHERE estudiante_documento = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            throw new \Exception("Error en la preparación de la consulta: " . $this->conn->error);
        }

        $stmt->bind_param("s", $documento);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'] > 0; // Retorna true si el estudiante ha votado
    }

    // Método para obtener el total de votos por candidato
    public function getTotalVotos($candidato_id) {
        $sql = "SELECT COUNT(*) as total FROM votos WHERE id_candidato = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            throw new \Exception("Error en la preparación de la consulta: " . $this->conn->error);
        }

        $stmt->bind_param("i", $candidato_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total']; // Retorna el total de votos para el candidato
    }

    // Método para obtener el total de votos en blanco por tipo
    public function getTotalVotosBlanco($tipo_voto) {
        $sql = "SELECT COUNT(*) as total FROM votos_blanco WHERE tipo_voto = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            throw new \Exception("Error en la preparación de la consulta: " . $this->conn->error);
        }

        $stmt->bind_param("s", $tipo_voto);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total']; // Retorna el total de votos en blanco para el tipo
    }
}
?>