<?php

namespace controllers;

use models\CandidatosNuevo;

/**
 * Controlador AdminCandidatos - Versión completamente nueva y limpia
 * Maneja todas las operaciones CRUD para la gestión de candidatos en el panel administrativo
 */
class AdminCandidatosControllerNuevo {
    private $candidatoModel;
    private $registros_por_pagina_permitidos = [10, 20, 50, 100];

    public function __construct() {
        // Inicializar sesión si no está activa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar autenticación de administrador
        $this->verificarAutenticacion();

        // Inicializar modelo
        $this->candidatoModel = new CandidatosNuevo();
    }

    /**
     * Verifica que el usuario esté autenticado como administrador
     */
    private function verificarAutenticacion() {
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header("Location: /Login/admin/login");
            exit();
        }
    }

    /**
     * Muestra la vista principal de gestión de candidatos
     */
    public function index() {
        try {
            // Obtener parámetros de la URL
            $busqueda = $this->obtenerParametro('busqueda', '');
            $tipo_filtro = $this->obtenerParametro('tipo_filtro', '');
            $pagina_actual = $this->obtenerParametro('pagina', 1, 'int');
            $registros_por_pagina = $this->obtenerParametro('registros_por_pagina', 10, 'int');

            // Validar registros por página
            if (!in_array($registros_por_pagina, $this->registros_por_pagina_permitidos)) {
                $registros_por_pagina = 10;
            }

            // Calcular offset para paginación
            $offset = ($pagina_actual - 1) * $registros_por_pagina;

            // Obtener datos
            $total_candidatos = $this->candidatoModel->contarCandidatos($busqueda, $tipo_filtro);
            $total_paginas = ceil($total_candidatos / $registros_por_pagina);

            // Validar página actual
            if ($pagina_actual < 1) {
                $pagina_actual = 1;
            } elseif ($pagina_actual > $total_paginas && $total_paginas > 0) {
                $pagina_actual = $total_paginas;
                $offset = ($pagina_actual - 1) * $registros_por_pagina;
            }

            // Obtener candidatos
            $candidatos = $this->candidatoModel->obtenerCandidatos($offset, $registros_por_pagina, $busqueda, $tipo_filtro);

            // Obtener estadísticas
            $estadisticas = $this->candidatoModel->obtenerEstadisticas();

            // Cargar vista
            require_once 'views/admin/candidatos_nuevo.php';

        } catch (\Exception $e) {
            $this->manejarError("Error al cargar candidatos: " . $e->getMessage());
        }
    }

    /**
     * Procesa la creación de un nuevo candidato
     */
    public function agregar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir();
            return;
        }

        try {
            // Obtener y validar datos del formulario
            $datos = $this->obtenerDatosFormulario();
            
            // Procesar foto si se subió
            $ruta_foto = $this->procesarFoto($_FILES['foto'] ?? null);
            if ($ruta_foto) {
                $datos['foto'] = $ruta_foto;
            }

            // Crear candidato
            $id_candidato = $this->candidatoModel->crear($datos);

            if ($id_candidato) {
                $this->establecerMensaje("Candidato agregado correctamente.", "success");
            } else {
                $this->establecerMensaje("Error al agregar el candidato.", "danger");
            }

        } catch (\Exception $e) {
            $this->manejarError("Error al agregar candidato: " . $e->getMessage());
        }

        $this->redirigir();
    }

    /**
     * Procesa la actualización de un candidato existente
     */
    public function editar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir();
            return;
        }

        try {
            // Obtener ID del candidato
            $id_candidato = $this->obtenerParametro('id_candidato', null, 'int');
            if (!$id_candidato) {
                throw new \Exception("ID de candidato no válido");
            }

            // Verificar que el candidato existe
            $candidato_actual = $this->candidatoModel->obtenerPorId($id_candidato);
            if (!$candidato_actual) {
                throw new \Exception("Candidato no encontrado");
            }

            // Obtener y validar datos del formulario
            $datos = $this->obtenerDatosFormulario();

            // Procesar foto nueva si se subió
            $foto_nueva = $_FILES['foto'] ?? null;
            if ($foto_nueva && $foto_nueva['error'] === UPLOAD_ERR_OK) {
                // Eliminar foto anterior si existe
                if (!empty($candidato_actual['foto'])) {
                    $this->eliminarFoto($candidato_actual['foto']);
                }
                
                // Subir nueva foto
                $ruta_foto = $this->procesarFoto($foto_nueva);
                if ($ruta_foto) {
                    $datos['foto'] = $ruta_foto;
                }
            } else {
                // Mantener foto actual
                $datos['foto'] = $candidato_actual['foto'];
            }

            // Actualizar candidato
            $resultado = $this->candidatoModel->actualizar($id_candidato, $datos);

            if ($resultado) {
                $this->establecerMensaje("Candidato actualizado correctamente.", "success");
            } else {
                $this->establecerMensaje("Error al actualizar el candidato.", "danger");
            }

        } catch (\Exception $e) {
            $this->manejarError("Error al editar candidato: " . $e->getMessage());
        }

        $this->redirigir();
    }

    /**
     * Procesa la eliminación de un candidato
     */
    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir();
            return;
        }

        try {
            // Obtener ID del candidato
            $id_candidato = $this->obtenerParametro('id_candidato', null, 'int');
            if (!$id_candidato) {
                throw new \Exception("ID de candidato no válido");
            }

            // Obtener datos del candidato para eliminar la foto
            $candidato = $this->candidatoModel->obtenerPorId($id_candidato);
            if (!$candidato) {
                throw new \Exception("Candidato no encontrado");
            }

            // Eliminar candidato
            $resultado = $this->candidatoModel->eliminar($id_candidato);

            if ($resultado) {
                // Eliminar foto si existe
                if (!empty($candidato['foto'])) {
                    $this->eliminarFoto($candidato['foto']);
                }
                
                $this->establecerMensaje("Candidato eliminado correctamente.", "success");
            } else {
                $this->establecerMensaje("Error al eliminar el candidato.", "danger");
            }

        } catch (\Exception $e) {
            $this->manejarError("Error al eliminar candidato: " . $e->getMessage());
        }

        $this->redirigir();
    }

    /**
     * API para validar número de tarjetón
     */
    public function validarNumero() {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $numero = $input['numero'] ?? '';
            $tipo_candidato = $input['tipo_candidato'] ?? '';
            $grado = $input['grado'] ?? null;
            $excluir_id = $input['excluir_id'] ?? null;

            if (empty($numero) || empty($tipo_candidato)) {
                echo json_encode(['disponible' => true, 'mensaje' => '']);
                return;
            }

            $existe = $this->candidatoModel->existeNumero($numero, $tipo_candidato, $grado, $excluir_id);
            
            if ($existe) {
                $candidato_conflicto = $this->candidatoModel->obtenerPorNumero($numero, $tipo_candidato, $grado, $excluir_id);
                
                if ($candidato_conflicto) {
                    $mensaje = "El número '$numero' ya está siendo usado por: " . 
                              $candidato_conflicto['nombre'] . ' ' . $candidato_conflicto['apellido'];
                } else {
                    $mensaje = "El número '$numero' ya está en uso";
                }
                
                echo json_encode(['disponible' => false, 'mensaje' => $mensaje]);
            } else {
                echo json_encode(['disponible' => true, 'mensaje' => 'Número disponible']);
            }

        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene los datos del formulario y los valida
     * @return array Datos validados
     */
    private function obtenerDatosFormulario() {
        $datos = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'numero' => trim($_POST['numero'] ?? ''),
            'tipo_candidato' => trim($_POST['tipo_candidato'] ?? ''),
            'grado' => null,
            'propuesta' => trim($_POST['propuesta'] ?? '')
        ];

        // Procesar grado
        $grado_input = trim($_POST['grado'] ?? '');
        if (!empty($grado_input) && is_numeric($grado_input)) {
            $grado = (int)$grado_input;
            if ($grado >= 6 && $grado <= 11) {
                $datos['grado'] = $grado;
            }
        }

        return $datos;
    }

    /**
     * Procesa la subida de una foto
     * @param array|null $archivo Archivo de foto
     * @return string|null Ruta de la foto o null si no se subió
     */
    private function procesarFoto($archivo) {
        if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Validar tipo de archivo
        $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($archivo['type'], $tipos_permitidos)) {
            throw new \Exception("Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG y GIF.");
        }

        // Validar tamaño (máximo 5MB)
        if ($archivo['size'] > 5 * 1024 * 1024) {
            throw new \Exception("El archivo es demasiado grande. Tamaño máximo: 5MB.");
        }

        // Generar nombre único
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nombre_archivo = 'candidato_' . time() . '_' . uniqid() . '.' . $extension;
        
        // Crear directorio si no existe
        $directorio = 'assets/img/candidatos/';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $ruta_destino = $directorio . $nombre_archivo;

        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            return '/Login/' . $ruta_destino;
        }

        throw new \Exception("Error al subir la foto.");
    }

    /**
     * Elimina un archivo de foto
     * @param string $ruta_foto Ruta de la foto a eliminar
     */
    private function eliminarFoto($ruta_foto) {
        if (empty($ruta_foto)) {
            return;
        }

        $ruta_archivo = str_replace('/Login/', '', $ruta_foto);
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
    }

    /**
     * Obtiene un parámetro de GET o POST
     * @param string $nombre Nombre del parámetro
     * @param mixed $default Valor por defecto
     * @param string $tipo Tipo de validación ('int', 'string')
     * @return mixed Valor del parámetro
     */
    private function obtenerParametro($nombre, $default = null, $tipo = 'string') {
        $valor = $_GET[$nombre] ?? $_POST[$nombre] ?? $default;

        if ($tipo === 'int') {
            return (int)$valor;
        }

        return is_string($valor) ? trim($valor) : $valor;
    }

    /**
     * Establece un mensaje en la sesión
     * @param string $mensaje Mensaje a mostrar
     * @param string $tipo Tipo de mensaje (success, danger, warning, info)
     */
    private function establecerMensaje($mensaje, $tipo = 'info') {
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['tipo'] = $tipo;
    }

    /**
     * Maneja errores y establece mensaje de error
     * @param string $mensaje Mensaje de error
     */
    private function manejarError($mensaje) {
        error_log("AdminCandidatosControllerNuevo: " . $mensaje);
        $this->establecerMensaje($mensaje, 'danger');
    }

    /**
     * Redirige a la página principal de candidatos
     */
    private function redirigir() {
        header("Location: /Login/admin/candidatos");
        exit();
    }

    /**
     * Obtiene candidatos por tipo (para APIs)
     */
    public function obtenerPorTipo() {
        header('Content-Type: application/json');

        try {
            $tipo = $_GET['tipo'] ?? '';
            $grado = $_GET['grado'] ?? null;

            if (empty($tipo)) {
                echo json_encode(['error' => 'Tipo de candidato requerido']);
                return;
            }

            $candidatos = $this->candidatoModel->obtenerPorTipo($tipo, $grado);
            echo json_encode(['candidatos' => $candidatos]);

        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene estadísticas de candidatos (para APIs)
     */
    public function obtenerEstadisticas() {
        header('Content-Type: application/json');

        try {
            $estadisticas = $this->candidatoModel->obtenerEstadisticas();
            echo json_encode($estadisticas);

        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene un candidato específico por ID (para APIs)
     */
    public function obtenerCandidato() {
        header('Content-Type: application/json');

        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                echo json_encode(['error' => 'ID requerido']);
                return;
            }

            $candidato = $this->candidatoModel->obtenerPorId((int)$id);
            
            if ($candidato) {
                echo json_encode(['candidato' => $candidato]);
            } else {
                echo json_encode(['error' => 'Candidato no encontrado']);
            }

        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}