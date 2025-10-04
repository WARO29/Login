<?php
namespace utils;

/**
 * Gestor de sesiones mejorado para manejar múltiples tipos de usuarios simultáneamente
 * Evita conflictos entre sesiones de estudiantes y administradores
 */
class SessionManager {
    
    /**
     * Inicia una sesión de forma segura
     * Solo inicia si no hay una sesión activa
     */
    public static function iniciarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar parámetros de sesión seguros
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
            
            session_start();
        }
    }
    
    /**
     * Verifica si un usuario estudiante está autenticado
     * @return bool
     */
    public static function esEstudianteAutenticado() {
        self::iniciarSesion();
        return isset($_SESSION['estudiante_id']) && 
               isset($_SESSION['es_estudiante']) && 
               $_SESSION['es_estudiante'] === true;
    }
    
    /**
     * Verifica si un usuario administrador está autenticado
     * @return bool
     */
    public static function esAdminAutenticado() {
        self::iniciarSesion();
        return isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true;
    }
    
    /**
     * Verifica si un usuario docente está autenticado
     * @return bool
     */
    public static function esDocenteAutenticado() {
        self::iniciarSesion();
        return isset($_SESSION['es_docente']) && $_SESSION['es_docente'] === true;
    }
    
    /**
     * Establece las variables de sesión para un estudiante
     * @param array $estudiante Datos del estudiante
     */
    public static function establecerSesionEstudiante($estudiante) {
        self::iniciarSesion();
        
        // NO limpiar sesiones de otros tipos de usuario para permitir sesiones simultáneas
        // Solo limpiar la sesión de estudiante previa si existe
        self::limpiarSesionEstudiante();
        
        $_SESSION['estudiante_id'] = $estudiante['id_estudiante'];
        $_SESSION['documento'] = $estudiante['id_estudiante'];
        $_SESSION['nombre'] = $estudiante['nombre'];
        $_SESSION['apellido'] = isset($estudiante['apellido']) ? $estudiante['apellido'] : '';
        $_SESSION['nombre_completo'] = $estudiante['nombre'] . ' ' . (isset($estudiante['apellido']) ? $estudiante['apellido'] : '');
        $_SESSION['grado'] = $estudiante['grado'];
        $_SESSION['correo'] = $estudiante['correo'];
        $_SESSION['es_estudiante'] = true;
        $_SESSION['tiempo_inicio_votacion'] = time();
        
        // Generar un token único para esta sesión de estudiante
        $_SESSION['estudiante_token'] = bin2hex(random_bytes(32));
    }
    
    /**
     * Establece las variables de sesión para un administrador
     * @param array $admin Datos del administrador
     */
    public static function establecerSesionAdmin($admin) {
        self::iniciarSesion();
        
        // NO limpiar sesiones de otros tipos de usuario para permitir sesiones simultáneas
        // Solo limpiar la sesión de admin previa si existe
        self::limpiarSesionAdmin();
        
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_usuario'] = $admin['usuario'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        $_SESSION['admin_imagen'] = isset($admin['imagen_url']) ? $admin['imagen_url'] : '';
        $_SESSION['es_admin'] = true;
        
        // Generar un token único para esta sesión de administrador
        $_SESSION['admin_token'] = bin2hex(random_bytes(32));
    }
    
    /**
     * Establece las variables de sesión para un docente
     * @param array $docente Datos del docente
     */
    public static function establecerSesionDocente($docente) {
        self::iniciarSesion();
        
        // NO limpiar sesiones de otros tipos de usuario para permitir sesiones simultáneas
        // Solo limpiar la sesión de docente previa si existe
        self::limpiarSesionDocente();
        
        $_SESSION['docente_id'] = $docente['id'];
        $_SESSION['docente_nombre'] = $docente['nombre'];
        $_SESSION['docente_email'] = isset($docente['email']) ? $docente['email'] : '';
        $_SESSION['es_docente'] = true;
        
        // Generar un token único para esta sesión de docente
        $_SESSION['docente_token'] = bin2hex(random_bytes(32));
    }
    
    /**
     * Establece las variables de sesión para un administrativo
     * @param array $administrativo Datos del administrativo
     */
    public static function establecerSesionAdministrativo($administrativo) {
        self::iniciarSesion();
        
        // NO limpiar sesiones de otros tipos de usuario para permitir sesiones simultáneas
        // Solo limpiar la sesión de administrativo previa si existe
        self::limpiarSesionAdministrativo();
        
        $_SESSION['docente_id'] = $administrativo['cedula']; // Usar cedula como ID para compatibilidad
        $_SESSION['docente_documento'] = $administrativo['cedula'];
        $_SESSION['docente_nombre'] = $administrativo['nombre'] . ' ' . $administrativo['apellido'];
        $_SESSION['es_docente'] = true; // Mantener para compatibilidad con el sistema existente
        $_SESSION['es_administrativo'] = true; // Nueva variable para identificar administrativos
        $_SESSION['tipo_usuario'] = 'administrativo';
        $_SESSION['administrativo_nombre'] = $administrativo['nombre'] . ' ' . $administrativo['apellido'];
        $_SESSION['administrativo_cedula'] = $administrativo['cedula'];
        $_SESSION['administrativo_cargo'] = $administrativo['cargo'];
        
        // Generar un token único para esta sesión de administrativo
        $_SESSION['administrativo_token'] = bin2hex(random_bytes(32));
    }
    
    /**
     * Verifica si un usuario administrativo está autenticado
     * @return bool
     */
    public static function esAdministrativoAutenticado() {
        self::iniciarSesion();
        return isset($_SESSION['es_administrativo']) && $_SESSION['es_administrativo'] === true;
    }
    
    /**
     * Limpia las variables de sesión de estudiante
     */
    public static function limpiarSesionEstudiante() {
        self::iniciarSesion();
        
        unset($_SESSION['estudiante_id']);
        unset($_SESSION['documento']);
        unset($_SESSION['nombre']);
        unset($_SESSION['apellido']);
        unset($_SESSION['nombre_completo']);
        unset($_SESSION['grado']);
        unset($_SESSION['correo']);
        unset($_SESSION['es_estudiante']);
        unset($_SESSION['tiempo_inicio_votacion']);
        unset($_SESSION['estudiante_token']);
        unset($_SESSION['nombre_personero']);
        unset($_SESSION['nombre_representante']);
        unset($_SESSION['id_personero']);
        unset($_SESSION['id_representante']);
    }
    
    /**
     * Limpia las variables de sesión de administrador
     */
    public static function limpiarSesionAdmin() {
        self::iniciarSesion();
        
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_usuario']);
        unset($_SESSION['admin_nombre']);
        unset($_SESSION['admin_imagen']);
        unset($_SESSION['es_admin']);
        unset($_SESSION['admin_token']);
    }
    
    /**
     * Limpia las variables de sesión de docente
     */
    public static function limpiarSesionDocente() {
        self::iniciarSesion();
        
        unset($_SESSION['docente_id']);
        unset($_SESSION['docente_nombre']);
        unset($_SESSION['docente_email']);
        unset($_SESSION['es_docente']);
        unset($_SESSION['docente_token']);
    }
    
    /**
     * Limpia las variables de sesión de administrativo
     */
    public static function limpiarSesionAdministrativo() {
        self::iniciarSesion();
        
        // Solo limpiar variables específicas de administrativo, mantener las de docente si es necesario
        unset($_SESSION['es_administrativo']);
        unset($_SESSION['administrativo_nombre']);
        unset($_SESSION['administrativo_cedula']);
        unset($_SESSION['administrativo_cargo']);
        unset($_SESSION['administrativo_token']);
        
        // Si no hay sesión de docente activa, limpiar también las variables compartidas
        if (!isset($_SESSION['docente_token'])) {
            unset($_SESSION['docente_id']);
            unset($_SESSION['docente_documento']);
            unset($_SESSION['docente_nombre']);
            unset($_SESSION['es_docente']);
            unset($_SESSION['tipo_usuario']);
        }
    }
    
    /**
     * Cierra la sesión de estudiante manteniendo otras sesiones activas
     */
    public static function cerrarSesionEstudiante() {
        self::limpiarSesionEstudiante();
        // No destruir la sesión completa, solo limpiar variables de estudiante
    }
    
    /**
     * Cierra la sesión de administrador manteniendo otras sesiones activas
     */
    public static function cerrarSesionAdmin() {
        self::limpiarSesionAdmin();
        // No destruir la sesión completa, solo limpiar variables de admin
    }
    
    /**
     * Cierra la sesión de docente manteniendo otras sesiones activas
     */
    public static function cerrarSesionDocente() {
        self::limpiarSesionDocente();
        // No destruir la sesión completa, solo limpiar variables de docente
    }
    
    /**
     * Cierra la sesión de administrativo manteniendo otras sesiones activas
     */
    public static function cerrarSesionAdministrativo() {
        self::limpiarSesionAdministrativo();
        // No destruir la sesión completa, solo limpiar variables de administrativo
    }
    
    /**
     * Obtiene el ID del usuario actual según el tipo
     * @param string $tipo Tipo de usuario: 'estudiante', 'admin', 'docente', 'administrativo'
     * @return mixed ID del usuario o null si no está autenticado
     */
    public static function obtenerIdUsuario($tipo = 'estudiante') {
        self::iniciarSesion();
        
        switch ($tipo) {
            case 'estudiante':
                return self::esEstudianteAutenticado() ? $_SESSION['estudiante_id'] : null;
            case 'admin':
                return self::esAdminAutenticado() ? $_SESSION['admin_id'] : null;
            case 'docente':
                return self::esDocenteAutenticado() ? $_SESSION['docente_id'] : null;
            case 'administrativo':
                return self::esAdministrativoAutenticado() ? $_SESSION['administrativo_cedula'] : null;
            default:
                return null;
        }
    }
    
    /**
     * Obtiene información del usuario actual
     * @param string $tipo Tipo de usuario
     * @return array|null Información del usuario o null
     */
    public static function obtenerInfoUsuario($tipo = 'estudiante') {
        self::iniciarSesion();
        
        switch ($tipo) {
            case 'estudiante':
                if (!self::esEstudianteAutenticado()) return null;
                return [
                    'id' => $_SESSION['estudiante_id'],
                    'nombre' => $_SESSION['nombre'] ?? '',
                    'apellido' => $_SESSION['apellido'] ?? '',
                    'nombre_completo' => $_SESSION['nombre_completo'] ?? '',
                    'grado' => $_SESSION['grado'] ?? '',
                    'correo' => $_SESSION['correo'] ?? ''
                ];
                
            case 'admin':
                if (!self::esAdminAutenticado()) return null;
                return [
                    'id' => $_SESSION['admin_id'],
                    'usuario' => $_SESSION['admin_usuario'] ?? '',
                    'nombre' => $_SESSION['admin_nombre'] ?? '',
                    'imagen' => $_SESSION['admin_imagen'] ?? ''
                ];
                
            case 'docente':
                if (!self::esDocenteAutenticado()) return null;
                return [
                    'id' => $_SESSION['docente_id'],
                    'nombre' => $_SESSION['docente_nombre'] ?? '',
                    'email' => $_SESSION['docente_email'] ?? ''
                ];
                
            case 'administrativo':
                if (!self::esAdministrativoAutenticado()) return null;
                return [
                    'cedula' => $_SESSION['administrativo_cedula'],
                    'nombre' => $_SESSION['administrativo_nombre'] ?? '',
                    'cargo' => $_SESSION['administrativo_cargo'] ?? ''
                ];
                
            default:
                return null;
        }
    }
    
    /**
     * Regenera el ID de sesión para mayor seguridad
     */
    public static function regenerarIdSesion() {
        self::iniciarSesion();
        session_regenerate_id(true);
    }
}