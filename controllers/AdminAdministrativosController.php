<?php
namespace controllers;

use models\AdministrativoModel;

class AdminAdministrativosController {
    private $administrativoModel;

    public function __construct() {
        $this->administrativoModel = new AdministrativoModel();
    }

    public function index() {
        // Verificar si la sesión está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar si el usuario está autenticado como administrador
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header("Location: /Login/admin/login");
            exit();
        }

        // Parámetros de paginación
        $pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $registros_por_pagina = isset($_GET['registros_por_pagina']) ? max(10, min(100, (int)$_GET['registros_por_pagina'])) : 20;
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

        // Obtener administrativos con paginación
        $offset = ($pagina_actual - 1) * $registros_por_pagina;
        
        if (!empty($busqueda)) {
            $administrativos = $this->administrativoModel->buscarAdministrativos($busqueda, $registros_por_pagina, $offset);
            $total_administrativos = $this->administrativoModel->contarAdministrativosBusqueda($busqueda);
        } else {
            $administrativos = $this->administrativoModel->getAllAdministrativosPaginados($registros_por_pagina, $offset);
            $total_administrativos = $this->administrativoModel->contarAdministrativos();
        }

        // Calcular información de paginación
        $total_paginas = ceil($total_administrativos / $registros_por_pagina);
        $administrativos_por_pagina = $registros_por_pagina;

        // Cargar la vista
        require_once 'views/admin/administrativos.php';
    }

    public function agregar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar sesión
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
                header("Location: /Login/admin/login");
                exit();
            }

            // Obtener datos del formulario
            $datos = [
                'cedula' => filter_input(INPUT_POST, 'cedula', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'nombre' => filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'apellido' => filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'correo' => filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL),
                'cargo' => filter_input(INPUT_POST, 'cargo', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'telefono' => filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'direccion' => filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'estado' => 'Activo'
            ];

            // Validar datos requeridos
            if (empty($datos['cedula']) || empty($datos['nombre']) || empty($datos['apellido'])) {
                $_SESSION['mensaje'] = "Los campos cédula, nombre y apellido son obligatorios.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/administrativos");
                exit();
            }

            // Verificar si ya existe un administrativo con esa cédula
            $existente = $this->administrativoModel->getAdministrativoPorCedula($datos['cedula']);
            if ($existente) {
                $_SESSION['mensaje'] = "Ya existe un administrativo con esa cédula.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/administrativos");
                exit();
            }

            // Crear el administrativo
            if ($this->administrativoModel->crearAdministrativo($datos)) {
                $_SESSION['mensaje'] = "Administrativo agregado exitosamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al agregar el administrativo.";
                $_SESSION['tipo'] = "danger";
            }

            header("Location: /Login/admin/administrativos");
            exit();
        }
    }

    public function editar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar sesión
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
                header("Location: /Login/admin/login");
                exit();
            }

            $cedula_original = filter_input(INPUT_POST, 'cedula_original', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            $datos = [
                'cedula' => filter_input(INPUT_POST, 'cedula', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'nombre' => filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'apellido' => filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'correo' => filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL),
                'cargo' => filter_input(INPUT_POST, 'cargo', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'telefono' => filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'direccion' => filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                'estado' => filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_FULL_SPECIAL_CHARS)
            ];

            // Validar datos requeridos
            if (empty($datos['cedula']) || empty($datos['nombre']) || empty($datos['apellido'])) {
                $_SESSION['mensaje'] = "Los campos cédula, nombre y apellido son obligatorios.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/administrativos");
                exit();
            }

            // Si cambió la cédula, verificar que no exista otra con la nueva cédula
            if ($cedula_original !== $datos['cedula']) {
                $existente = $this->administrativoModel->getAdministrativoPorCedula($datos['cedula']);
                if ($existente) {
                    $_SESSION['mensaje'] = "Ya existe un administrativo con esa cédula.";
                    $_SESSION['tipo'] = "danger";
                    header("Location: /Login/admin/administrativos");
                    exit();
                }
            }

            // Actualizar el administrativo
            if ($this->administrativoModel->actualizarAdministrativoPorCedula($cedula_original, $datos)) {
                $_SESSION['mensaje'] = "Administrativo actualizado exitosamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al actualizar el administrativo.";
                $_SESSION['tipo'] = "danger";
            }

            header("Location: /Login/admin/administrativos");
            exit();
        }
    }

    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar sesión
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
                header("Location: /Login/admin/login");
                exit();
            }

            $cedula = filter_input(INPUT_POST, 'cedula', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if (empty($cedula)) {
                $_SESSION['mensaje'] = "Cédula no válida.";
                $_SESSION['tipo'] = "danger";
                header("Location: /Login/admin/administrativos");
                exit();
            }

            // Eliminar (soft delete) el administrativo
            if ($this->administrativoModel->eliminarAdministrativo($cedula)) {
                $_SESSION['mensaje'] = "Administrativo eliminado exitosamente.";
                $_SESSION['tipo'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar el administrativo.";
                $_SESSION['tipo'] = "danger";
            }

            header("Location: /Login/admin/administrativos");
            exit();
        }
    }
}
?>