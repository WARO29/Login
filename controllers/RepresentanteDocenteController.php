<?php
namespace controllers;

use models\RepresentanteDocenteModel;

class RepresentanteDocenteController {
    private $representanteDocenteModel;

    public function __construct() {
        $this->representanteDocenteModel = new RepresentanteDocenteModel();
    }

    /**
     * Obtiene todos los representantes docentes
     * @return array Array con todos los representantes docentes
     */
    public function getAllRepresentantes() {
        return $this->representanteDocenteModel->getAll();
    }

    /**
     * Obtiene un representante docente por su código
     * @param string $codigo Código del representante docente
     * @return array|false Datos del representante o false si no existe
     */
    public function getRepresentanteByCodigo($codigo) {
        return $this->representanteDocenteModel->getByCodigo($codigo);
    }

    /**
     * Carga la vista del panel con los representantes docentes
     */
    public function mostrarPanel() {
        // Verificar si el docente está autenticado
        if (!isset($_SESSION['es_docente']) || $_SESSION['es_docente'] !== true) {
            header("Location: /Login/docente/login");
            exit();
        }

        // Obtener información del docente
        $nombre_docente = $_SESSION['docente_nombre'];
        
        // Obtener todos los representantes docentes
        $representantes = $this->getAllRepresentantes();
        
        // Cargar la vista
        require_once 'views/docente/panel.php';
    }
}
