<?php
namespace controllers;

use models\ConfiguracionSistemaModel;

class ConfiguracionSistemaController {
    private $configModel;
    
    public function __construct() {
        $this->configModel = new ConfiguracionSistemaModel();
    }
    
    /**
     * Muestra la página de configuración del sistema
     */
    public function mostrarConfiguracion() {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Obtener todas las configuraciones organizadas por categoría
        $configuraciones = $this->configModel->obtenerTodas();
        
        // Organizar configuraciones por categoría
        $configuracionesPorCategoria = [];
        foreach ($configuraciones as $config) {
            $categoria = $config['categoria'] ?? 'general';
            if (!isset($configuracionesPorCategoria[$categoria])) {
                $configuracionesPorCategoria[$categoria] = [];
            }
            $configuracionesPorCategoria[$categoria][] = $config;
        }
        
        // Cargar la vista
        require_once 'views/admin/configuracion_sistema.php';
    }
    
    /**
     * Actualiza una configuración del sistema
     */
    public function actualizarConfiguracion() {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        // Verificar que sea una petición POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /Login/admin/configuracion-sistema');
            exit;
        }
        
        $clave = $_POST['clave'] ?? '';
        $valor = $_POST['valor'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $tipo = $_POST['tipo'] ?? 'string';
        $categoria = $_POST['categoria'] ?? 'general';
        
        if (empty($clave)) {
            $_SESSION['mensaje'] = 'La clave de configuración es obligatoria.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-sistema');
            exit;
        }
        
        // Validar el valor según el tipo
        if (!$this->configModel->validarConfiguracion($tipo, $valor)) {
            $_SESSION['mensaje'] = 'El valor no es válido para el tipo especificado.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-sistema');
            exit;
        }
        
        // Actualizar la configuración
        $resultado = $this->configModel->establecer(
            $clave, 
            $valor, 
            $descripcion, 
            $tipo, 
            $categoria, 
            $_SESSION['admin_id']
        );
        
        if ($resultado) {
            $_SESSION['mensaje'] = 'Configuración actualizada correctamente.';
            $_SESSION['tipo'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al actualizar la configuración.';
            $_SESSION['tipo'] = 'error';
        }
        
        header('Location: /Login/admin/configuracion-sistema');
        exit;
    }
    
    /**
     * Elimina una configuración del sistema
     */
    public function eliminarConfiguracion() {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /Login/admin/login');
            exit;
        }
        
        $clave = $_GET['clave'] ?? '';
        
        if (empty($clave)) {
            $_SESSION['mensaje'] = 'Clave de configuración no especificada.';
            $_SESSION['tipo'] = 'error';
            header('Location: /Login/admin/configuracion-sistema');
            exit;
        }
        
        // Eliminar la configuración
        $resultado = $this->configModel->eliminar($clave);
        
        if ($resultado) {
            $_SESSION['mensaje'] = 'Configuración eliminada correctamente.';
            $_SESSION['tipo'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al eliminar la configuración.';
            $_SESSION['tipo'] = 'error';
        }
        
        header('Location: /Login/admin/configuracion-sistema');
        exit;
    }
    
    /**
     * API para obtener una configuración específica
     */
    public function obtenerConfiguracion() {
        header('Content-Type: application/json');
        
        $clave = $_GET['clave'] ?? '';
        
        if (empty($clave)) {
            echo json_encode(['error' => 'Clave no especificada']);
            exit;
        }
        
        $valor = $this->configModel->obtener($clave);
        
        echo json_encode([
            'clave' => $clave,
            'valor' => $valor
        ]);
        exit;
    }
}


