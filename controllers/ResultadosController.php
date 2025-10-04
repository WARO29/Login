<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Votos.php';

class ResultadosController {
    private $db;
    private $votosModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->votosModel = new Votos($this->db);
    }

    public function obtenerResultadosPersonero() {
        try {
            $resultados = $this->votosModel->obtenerResultadosPersonero();
            header('Content-Type: application/json');
            echo json_encode($resultados);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function obtenerGrados() {
        try {
            $grados = $this->votosModel->obtenerGrados();
            header('Content-Type: application/json');
            echo json_encode($grados);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function obtenerResultadosRepresentante($grado) {
        try {
            $resultados = $this->votosModel->obtenerResultadosRepresentante($grado);
            header('Content-Type: application/json');
            echo json_encode($resultados);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
} 