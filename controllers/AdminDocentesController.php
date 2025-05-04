<?php
namespace controllers;

use models\DocenteModel;

class AdminDocentesController {
    private $docenteModel;

    public function __construct() {
        $this->docenteModel = new DocenteModel();
    }

    /**
     * Muestra la vista de gestión de docentes
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
        
        // Obtener el número de docentes por página (con valores permitidos)
        $valores_permitidos = [10, 20, 50, 100];
        $docentes_por_pagina = isset($_GET['registros_por_pagina']) ? (int)$_GET['registros_por_pagina'] : 20;
        
        // Validar que el valor esté en los permitidos
        if (!in_array($docentes_por_pagina, $valores_permitidos)) {
            $docentes_por_pagina = 20; // Valor por defecto
        }
        
        $offset = ($pagina_actual - 1) * $docentes_por_pagina;

        // Obtener el total de docentes para calcular la paginación
        $total_docentes = $this->docenteModel->getTotalDocentes($busqueda);
        $total_paginas = ceil($total_docentes / $docentes_por_pagina);

        // Asegurarse de que la página actual esté dentro de los límites
        if ($pagina_actual < 1) {
            $pagina_actual = 1;
        } elseif ($pagina_actual > $total_paginas && $total_paginas > 0) {
            $pagina_actual = $total_paginas;
        }

        // Obtener los docentes para la página actual
        $docentes = $this->docenteModel->getDocentesPaginados($offset, $docentes_por_pagina, $busqueda);
        
        // Cargar la vista
        require_once 'views/admin/docentes.php';
    }

    /**
     * Agrega un nuevo docente
     */
    public function agregarDocente() {
        // Verificar si el usuario está autenticado como administrador
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header("Location: /Login/admin/login");
            exit();
        }

        // Verificar si es una solicitud POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener y validar los datos del formulario
            $codigo_docente = filter_input(INPUT_POST, 'codigo_docente', FILTER_SANITIZE_STRING);
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
            $area = filter_input(INPUT_POST, 'area', FILTER_SANITIZE_STRING);
            $estado = isset($_POST['estado']) ? filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING) : 'activo'; // 'activo' por defecto

            // Validar que todos los campos requeridos estén presentes
            if (empty($codigo_docente) || empty($nombre) || empty($correo) || empty($area)) {
                $_SESSION['mensaje'] = "Todos los campos son obligatorios.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/docentes");
                exit();
            }

            // Validar formato de correo electrónico
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['mensaje'] = "El formato del correo electrónico no es válido.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/docentes");
                exit();
            }

            // Verificar si el docente ya existe
            if ($this->docenteModel->getDocentePorDocumento($codigo_docente)) {
                $_SESSION['mensaje'] = "Ya existe un docente con ese código.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/docentes");
                exit();
            }

            // Crear el docente
            $resultado = $this->docenteModel->crearDocente([
                'codigo_docente' => $codigo_docente,
                'nombre' => $nombre,
                'correo' => $correo,
                'area' => $area,
                'estado' => $estado
            ]);

            if ($resultado) {
                $_SESSION['mensaje'] = "Docente agregado correctamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al agregar el docente.";
                $_SESSION['tipo'] = "danger";
            }

            header("Location: /Login/admin/docentes");
            exit();
        } else {
            // Si no es POST, redirigir a la página de docentes
            header("Location: /Login/admin/docentes");
            exit();
        }
    }

    /**
     * Edita un docente existente
     */
    public function editarDocente() {
        // Verificar si el usuario está autenticado como administrador
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header("Location: /Login/admin/login");
            exit();
        }

        // Verificar si es una solicitud POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener y validar los datos del formulario
            $codigo_docente = filter_input(INPUT_POST, 'codigo_docente', FILTER_SANITIZE_STRING);
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
            $area = filter_input(INPUT_POST, 'area', FILTER_SANITIZE_STRING);
            $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);

            // Validar que todos los campos requeridos estén presentes
            if (empty($codigo_docente) || empty($nombre) || empty($correo) || empty($area) || empty($estado)) {
                $_SESSION['mensaje'] = "Todos los campos son obligatorios.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/docentes");
                exit();
            }

            // Validar formato de correo electrónico
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['mensaje'] = "El formato del correo electrónico no es válido.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/docentes");
                exit();
            }

            // Verificar si el docente existe
            if (!$this->docenteModel->getDocentePorDocumento($codigo_docente)) {
                $_SESSION['mensaje'] = "No se encontró el docente con el código especificado.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/docentes");
                exit();
            }

            // Actualizar el docente
            $resultado = $this->docenteModel->actualizarDocente($codigo_docente, [
                'nombre' => $nombre,
                'correo' => $correo,
                'area' => $area,
                'estado' => $estado
            ]);

            if ($resultado) {
                $_SESSION['mensaje'] = "Docente actualizado correctamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al actualizar el docente.";
                $_SESSION['tipo'] = "danger";
            }

            header("Location: /Login/admin/docentes");
            exit();
        } else {
            // Si no es POST, redirigir a la página de docentes
            header("Location: /Login/admin/docentes");
            exit();
        }
    }

    /**
     * Elimina un docente
     */
    public function eliminarDocente() {
        // Verificar si el usuario está autenticado como administrador
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header("Location: /Login/admin/login");
            exit();
        }

        // Verificar si es una solicitud POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtener el código del docente a eliminar
            $codigo_docente = filter_input(INPUT_POST, 'codigo_docente', FILTER_SANITIZE_STRING);

            if (empty($codigo_docente)) {
                $_SESSION['mensaje'] = "Código de docente no válido.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/docentes");
                exit();
            }

            // Verificar si el docente existe
            if (!$this->docenteModel->getDocentePorDocumento($codigo_docente)) {
                $_SESSION['mensaje'] = "El docente no existe.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/docentes");
                exit();
            }

            // Eliminar el docente
            $resultado = $this->docenteModel->eliminarDocente($codigo_docente);

            if ($resultado) {
                $_SESSION['mensaje'] = "Docente eliminado correctamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar el docente.";
                $_SESSION['tipo'] = "danger";
            }

            header("Location: /Login/admin/docentes");
            exit();
        } else {
            // Si no es POST, redirigir a la página de docentes
            header("Location: /Login/admin/docentes");
            exit();
        }
    }
}
