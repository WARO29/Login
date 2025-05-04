<?php
namespace controllers;

use models\EstudianteModel;

class AdminEstudiantesController {
    private $estudianteModel;

    public function __construct() {
        $this->estudianteModel = new EstudianteModel();
    }

    /**
     * Muestra la vista de gestión de estudiantes
     */
    public function index() {
        // Verificar si el usuario está autenticado como administrador
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header("Location: /Login/admin/login");
            exit();
        }

        // Obtener el término de búsqueda
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

        // Obtener el número de página actual
        $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        
        // Obtener el número de estudiantes por página (con valores permitidos)
        $valores_permitidos = [10, 20, 50, 100];
        $estudiantes_por_pagina = isset($_GET['registros_por_pagina']) ? (int)$_GET['registros_por_pagina'] : 20;
        
        // Validar que el valor esté en los permitidos
        if (!in_array($estudiantes_por_pagina, $valores_permitidos)) {
            $estudiantes_por_pagina = 20; // Valor por defecto
        }
        
        $offset = ($pagina_actual - 1) * $estudiantes_por_pagina;

        // Obtener el total de estudiantes para calcular la paginación
        $total_estudiantes = $this->estudianteModel->getTotalEstudiantes($busqueda);
        $total_paginas = ceil($total_estudiantes / $estudiantes_por_pagina);

        // Asegurarse de que la página actual esté dentro de los límites
        if ($pagina_actual < 1) {
            $pagina_actual = 1;
        } elseif ($pagina_actual > $total_paginas && $total_paginas > 0) {
            $pagina_actual = $total_paginas;
        }

        // Obtener los estudiantes para la página actual
        $estudiantes = $this->estudianteModel->getEstudiantesPaginados($offset, $estudiantes_por_pagina, $busqueda);
        
        // Cargar la vista
        require_once 'views/admin/estudiantes.php';
    }

    /**
     * Agrega un nuevo estudiante
     */
    public function agregarEstudiante() {
        // Verificar si el usuario está autenticado como administrador
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header("Location: /Login/admin/login");
            exit();
        }

        // Verificar si es una solicitud POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener y validar los datos del formulario
            $id_estudiante = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_STRING);
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $grado = filter_input(INPUT_POST, 'grado', FILTER_SANITIZE_STRING);
            $grupo = filter_input(INPUT_POST, 'grupo', FILTER_SANITIZE_STRING);
            $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);

            // Validar que todos los campos requeridos estén presentes
            if (empty($id_estudiante) || empty($nombre) || empty($grado) || empty($grupo) || empty($estado)) {
                $_SESSION['mensaje'] = "Todos los campos son obligatorios.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/estudiantes");
                exit();
            }

            // Verificar si el estudiante ya existe
            if ($this->estudianteModel->getEstudiantePorId($id_estudiante)) {
                $_SESSION['mensaje'] = "Ya existe un estudiante con ese número de documento.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/estudiantes");
                exit();
            }

            // Crear el estudiante
            $resultado = $this->estudianteModel->crearEstudiante([
                'id_estudiante' => $id_estudiante,
                'nombre' => $nombre,
                'grado' => $grado,
                'grupo' => $grupo,
                'estado' => $estado
            ]);

            if ($resultado) {
                $_SESSION['mensaje'] = "Estudiante agregado correctamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al agregar el estudiante.";
                $_SESSION['tipo'] = "danger";
            }

            header("Location: /Login/admin/estudiantes");
            exit();
        } else {
            // Si no es POST, redirigir a la página de estudiantes
            header("Location: /Login/admin/estudiantes");
            exit();
        }
    }

    /**
     * Edita un estudiante existente
     */
    public function editarEstudiante() {
        // Verificar si el usuario está autenticado como administrador
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header("Location: /Login/admin/login");
            exit();
        }

        // Verificar si es una solicitud POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener y validar los datos del formulario
            $id_estudiante = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_STRING);
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $grado = filter_input(INPUT_POST, 'grado', FILTER_SANITIZE_STRING);
            $grupo = filter_input(INPUT_POST, 'grupo', FILTER_SANITIZE_STRING);
            $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);

            // Validar que todos los campos requeridos estén presentes
            if (empty($id_estudiante) || empty($nombre) || empty($grado) || empty($grupo) || empty($estado)) {
                $_SESSION['mensaje'] = "Todos los campos son obligatorios.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/estudiantes");
                exit();
            }

            // Verificar si el estudiante existe
            if (!$this->estudianteModel->getEstudiantePorId($id_estudiante)) {
                $_SESSION['mensaje'] = "El estudiante no existe.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/estudiantes");
                exit();
            }

            // Actualizar el estudiante
            $resultado = $this->estudianteModel->actualizarEstudiante($id_estudiante, [
                'nombre' => $nombre,
                'grado' => $grado,
                'grupo' => $grupo,
                'estado' => $estado
            ]);

            if ($resultado) {
                $_SESSION['mensaje'] = "Estudiante actualizado correctamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al actualizar el estudiante.";
                $_SESSION['tipo'] = "danger";
            }

            header("Location: /Login/admin/estudiantes");
            exit();
        } else {
            // Si no es POST, redirigir a la página de estudiantes
            header("Location: /Login/admin/estudiantes");
            exit();
        }
    }

    /**
     * Elimina un estudiante
     */
    public function eliminarEstudiante() {
        // Verificar si el usuario está autenticado como administrador
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header("Location: /Login/admin/login");
            exit();
        }

        // Verificar si es una solicitud POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener el ID del estudiante a eliminar
            $id_estudiante = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_STRING);

            if (empty($id_estudiante)) {
                $_SESSION['mensaje'] = "ID de estudiante no válido.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/estudiantes");
                exit();
            }

            // Verificar si el estudiante existe
            if (!$this->estudianteModel->getEstudiantePorId($id_estudiante)) {
                $_SESSION['mensaje'] = "El estudiante no existe.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/estudiantes");
                exit();
            }

            // Eliminar el estudiante
            $resultado = $this->estudianteModel->eliminarEstudiante($id_estudiante);

            if ($resultado) {
                $_SESSION['mensaje'] = "Estudiante eliminado correctamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar el estudiante.";
                $_SESSION['tipo'] = "danger";
            }

            header("Location: /Login/admin/estudiantes");
            exit();
        } else {
            // Si no es POST, redirigir a la página de estudiantes
            header("Location: /Login/admin/estudiantes");
            exit();
        }
    }
}
