<?php
// Usar el autoloader personalizado para cargar todas las clases
require_once 'autoload.php';

require "config/config.php";

// Importar el SessionManager y controladores
use utils\SessionManager;
use controllers\AuthController;
use controllers\DocenteController;

require "controllers/AuthController.php";

// Inicializar sesión de forma segura
SessionManager::iniciarSesion();

// Obtener la ruta actual y limpiarla
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/Login'; // Directorio base de la aplicación
$path = str_replace($base_path, '', $request_uri);
$path = trim(parse_url($path, PHP_URL_PATH), '/');

// Dividir la ruta en segmentos para manejar parámetros dinámicos
$path_segments = explode('/', $path);

// Manejo especial para rutas con parámetros dinámicos
if (count($path_segments) >= 3 && $path_segments[0] === 'admin') {
    $action = $path_segments[1];
    $id = isset($path_segments[2]) ? intval($path_segments[2]) : null;
    
    switch ($action) {
        case 'eliminar-eleccion':
            if ($id) {
                require_once 'controllers/EleccionConfigController.php';
                $controller = new controllers\EleccionConfigController();
                $controller->eliminarEleccion($id);
                exit;
            } else {
                header('Location: /Login/admin/configuracion-elecciones');
                exit;
            }
            break;
            
        case 'detalle-eleccion':
            if ($id) {
                require_once 'controllers/EleccionConfigController.php';
                $controller = new controllers\EleccionConfigController();
                $controller->detalleEleccion($id);
                exit;
            } else {
                header('Location: /Login/admin/configuracion-elecciones');
                exit;
            }
            break;
            
        case 'editar-eleccion':
            if ($id) {
                require_once 'controllers/EleccionConfigController.php';
                $controller = new controllers\EleccionConfigController();
                $controller->editarEleccion($id);
                exit;
            } else {
                header('Location: /Login/admin/configuracion-elecciones');
                exit;
            }
            break;
            
        case 'activar-eleccion':
            if ($id) {
                require_once 'controllers/EleccionConfigController.php';
                $controller = new controllers\EleccionConfigController();
                $controller->activarEleccion($id);
                exit;
            } else {
                header('Location: /Login/admin/configuracion-elecciones');
                exit;
            }
            break;
            
        case 'cerrar-eleccion':
            if ($id) {
                require_once 'controllers/EleccionConfigController.php';
                $controller = new controllers\EleccionConfigController();
                $controller->cerrarEleccion($id);
                exit;
            } else {
                header('Location: /Login/admin/configuracion-elecciones');
                exit;
            }
            break;
            
        case 'cancelar-eleccion':
            if ($id) {
                require_once 'controllers/EleccionConfigController.php';
                $controller = new controllers\EleccionConfigController();
                $controller->cancelarEleccion($id);
                exit;
            } else {
                header('Location: /Login/admin/configuracion-elecciones');
                exit;
            }
            break;
    }
}

// Manejo de rutas
switch ($path) {
    // Rutas de Estudiantes
    case '':
    case 'login':
        // Ruta por defecto - login de estudiantes
        require_once 'controllers/AuthController.php';
        $authController = new controllers\AuthController();
        $authController->login();
        break;
        
    case 'votacion':
        // Página de votación
        if (!isset($_SESSION['estudiante_id'])) {
            header('Location: /Login/');
            exit;
        }
        // Redirigir a la nueva ruta de votación
        header('Location: /Login/estudiante/votos');
        exit;
        break;
    case 'procesar_voto':
        // Procesar el voto del estudiante
        require_once 'controllers/ProcesarVotosController.php';
        $controller = new controllers\ProcesarVotosController();
        $controller->procesarVoto();
        break;
        
    case 'voto_blanco':
        // Procesar voto en blanco
        require_once 'controllers/ProcesarVotosController.php';
        $controller = new controllers\ProcesarVotosController();
        $controller->procesarVotoEnBlanco();
        break;
        
    case 'cancelar_votacion':
        // Cancelar la votación y eliminar votos parciales
        require_once 'controllers/ProcesarVotosController.php';
        $controller = new controllers\ProcesarVotosController();
        $controller->cancelarVotacion();
        break;
        
    case 'finalizar_votacion':
        // Finalizar la votación verificando que se haya votado por ambos tipos
        require_once 'controllers/ProcesarVotosController.php';
        $controller = new controllers\ProcesarVotosController();
        $controller->finalizarVotacion($_SESSION['estudiante_id']);
        break;
        
    case 'estudiante/votos':
        // Página de votación para estudiantes usando SessionManager
        if (!SessionManager::esEstudianteAutenticado()) {
            header('Location: /Login/');
            exit;
        }
        // Iniciar el tiempo de votación si no está establecido
        if (!isset($_SESSION['tiempo_inicio_votacion'])) {
            $_SESSION['tiempo_inicio_votacion'] = time();
        }
        // Cargar la vista de votación
        require_once 'views/estudiantes/votos.php';
        break;
        
    // Rutas de Docentes
    case 'docente/login':
        // Login de docentes
        require_once 'controllers/DocenteController.php';
        $controller = new controllers\DocenteController();
        $controller->login();
        break;

    case 'docente/autenticar':
        // Autenticación de docentes
        require_once 'controllers/DocenteController.php';
        $controller = new controllers\DocenteController();
        $controller->autenticar();
        break;

    case 'docente/cerrar-sesion':
        // Cerrar sesión de docentes
        require_once 'controllers/DocenteController.php';
        $controller = new controllers\DocenteController();
        $controller->cerrarSesion();
        break;

    case 'docente/panel':
        // Panel de docentes con representantes docentes
        require_once 'controllers/RepresentanteDocenteController.php';
        $controller = new controllers\RepresentanteDocenteController();
        $controller->mostrarPanel();
        break;
        
    case 'docente/procesar_voto':
        // Procesar voto de docente
        require_once 'controllers/RepresentanteDocenteController.php';
        $controller = new controllers\RepresentanteDocenteController();
        $controller->procesarVoto();
        break;
        
    // Rutas de Administradores
    case 'admin/login':
        // Login de administradores
        require_once 'controllers/AdminController.php';
        $controller = new controllers\AdminController();
        $controller->login();
        break;
        
    case 'admin/autenticar':
        // Autenticación de administradores
        require_once 'controllers/AdminController.php';
        $controller = new controllers\AdminController();
        $controller->autenticar();
        break;
        
    case 'admin/panel':
        // Panel de administración
        require_once 'controllers/AdminController.php';
        $controller = new controllers\AdminController();
        $controller->panel();
        break;
        
    case 'admin/cerrar-sesion':
        // Cerrar sesión de administradores
        require_once 'controllers/AdminController.php';
        $controller = new controllers\AdminController();
        $controller->cerrarSesion();
        break;

    case 'admin/crear-tabla-votos-docentes':
        // Crear tabla de votos de docentes si no existe
        require_once 'api/crear_tabla_votos_docentes.php';
        break;
        
    case 'admin/obtener_votos_recientes/estudiantes':
        // Obtener votos recientes de estudiantes
        header('Content-Type: application/json');
        require_once 'models/Estadisticas.php';
        $estadisticasModel = new models\Estadisticas();
        $votosRecientes = $estadisticasModel->getVotosRecientes(10);
        
        // Procesar los votos para el formato esperado
        $votosFormateados = [];
        foreach ($votosRecientes as $voto) {
            // Verificar que los datos necesarios existen
            if (!isset($voto['nombre_estudiante'])) {
                continue;
            }
            
            // Formatear el nombre completo
            $nombreCompleto = $voto['nombre_estudiante'];
            if (isset($voto['apellido_estudiante']) && !empty($voto['apellido_estudiante'])) {
                $nombreCompleto .= ' ' . $voto['apellido_estudiante'];
            }
            
            // Formatear la fecha
            $fecha = isset($voto['fecha_voto']) ? date('d/m/Y H:i', strtotime($voto['fecha_voto'])) : '';
            
            // Determinar información sobre el voto
            $infoVoto = '';
            if ($voto['id_candidato'] === null) {
                $infoVoto = 'Votó en blanco';
            } else if (isset($voto['nombre_candidato'])) {
                $nombreCandidato = $voto['nombre_candidato'];
                if (isset($voto['apellido_candidato']) && !empty($voto['apellido_candidato'])) {
                    $nombreCandidato .= ' ' . $voto['apellido_candidato'];
                }
                $infoVoto = 'Votó por ' . $nombreCandidato;
            }
            
            $votosFormateados[] = [
                'nombre_completo' => $nombreCompleto,
                'grado' => $voto['grado'] ?? '',
                'fecha' => $fecha,
                'info_voto' => $infoVoto
            ];
        }
        
        echo json_encode($votosFormateados);
        break;
        
    case 'admin/obtener_votos_recientes/docentes':
        // Obtener votos recientes de docentes
        header('Content-Type: application/json');
        require_once 'models/Votos.php';
        $votosModel = new models\Votos();
        $votosRecientes = $votosModel->getVotosRecientesDocentes(10);
        
        // Los votos ya vienen procesados desde el modelo, solo aseguramos que tengan el formato correcto
        if (empty($votosRecientes)) {
            echo json_encode([]);
        } else {
            echo json_encode($votosRecientes);
        }
        break;

    // Nuevas rutas para gestión de estudiantes
    case 'admin/estudiantes':
        // Gestión de estudiantes
        require_once 'controllers/AdminEstudiantesController.php';
        $controller = new controllers\AdminEstudiantesController();
        $controller->index();
        break;
        
    case 'admin/estudiantes/agregar':
        // Agregar estudiante
        require_once 'controllers/AdminEstudiantesController.php';
        $controller = new controllers\AdminEstudiantesController();
        $controller->agregarEstudiante();
        break;
        
    case 'admin/estudiantes/editar':
        // Editar estudiante
        require_once 'controllers/AdminEstudiantesController.php';
        $controller = new controllers\AdminEstudiantesController();
        $controller->editarEstudiante();
        break;
        
    case 'admin/estudiantes/eliminar':
        // Eliminar estudiante
        require_once 'controllers/AdminEstudiantesController.php';
        $controller = new controllers\AdminEstudiantesController();
        $controller->eliminarEstudiante();
        break;
        
    // Nuevas rutas para gestión de docentes
    case 'admin/docentes':
        // Gestión de docentes
        require_once 'controllers/AdminDocentesController.php';
        $controller = new controllers\AdminDocentesController();
        $controller->index();
        break;
        
    case 'admin/docentes/agregar':
        // Agregar docente
        require_once 'controllers/AdminDocentesController.php';
        $controller = new controllers\AdminDocentesController();
        $controller->agregarDocente();
        break;
        
    case 'admin/docentes/editar':
        // Editar docente
        require_once 'controllers/AdminDocentesController.php';
        $controller = new controllers\AdminDocentesController();
        $controller->editarDocente();
        break;
        
    case 'admin/docentes/eliminar':
        // Eliminar docente
        require_once 'controllers/AdminDocentesController.php';
        $controller = new controllers\AdminDocentesController();
        $controller->eliminarDocente();
        break;

    // Rutas para gestión de administrativos
    case 'admin/administrativos':
        // Gestión de administrativos
        require_once 'controllers/AdminAdministrativosController.php';
        $controller = new controllers\AdminAdministrativosController();
        $controller->index();
        break;
        
    case 'admin/administrativos/agregar':
        // Agregar administrativo
        require_once 'controllers/AdminAdministrativosController.php';
        $controller = new controllers\AdminAdministrativosController();
        $controller->agregar();
        break;
        
    case 'admin/administrativos/editar':
        // Editar administrativo
        require_once 'controllers/AdminAdministrativosController.php';
        $controller = new controllers\AdminAdministrativosController();
        $controller->editar();
        break;
        
    case 'admin/administrativos/eliminar':
        // Eliminar administrativo
        require_once 'controllers/AdminAdministrativosController.php';
        $controller = new controllers\AdminAdministrativosController();
        $controller->eliminar();
        break;

    // Rutas para gestión de candidatos
    case 'admin/candidatos':
        require_once 'controllers/AdminCandidatosController.php';
        $controller = new controllers\AdminCandidatosController();
        $controller->index();
        break;
        
    case 'admin/candidatos/agregar':
        require_once 'controllers/AdminCandidatosController.php';
        $controller = new controllers\AdminCandidatosController();
        $controller->agregarCandidato();
        break;
        
    case 'admin/candidatos/editar':
        require_once 'controllers/AdminCandidatosController.php';
        $controller = new controllers\AdminCandidatosController();
        $controller->editarCandidato();
        break;
        
    case 'admin/candidatos/eliminar':
        require_once 'controllers/AdminCandidatosController.php';
        $controller = new controllers\AdminCandidatosController();
        $controller->eliminarCandidato();
        break;
        
    // Rutas para control temporal de elecciones
    case 'admin/configuracion-elecciones':
        require_once 'controllers/EleccionConfigController.php';
        $controller = new controllers\EleccionConfigController();
        $controller->panelConfiguracion();
        break;
        
    case 'admin/nueva-eleccion':
        require_once 'controllers/EleccionConfigController.php';
        $controller = new controllers\EleccionConfigController();
        $controller->formularioNuevaEleccion();
        break;
        
    case 'admin/crear-eleccion':
        require_once 'controllers/EleccionConfigController.php';
        $controller = new controllers\EleccionConfigController();
        $controller->crearEleccion();
        break;
        
    case 'admin/detalle-eleccion':
        if (isset($_GET['id'])) {
            require_once 'controllers/EleccionConfigController.php';
            $controller = new controllers\EleccionConfigController();
            $controller->detalleEleccion($_GET['id']);
        } else {
            header('Location: /Login/admin/configuracion-elecciones');
        }
        break;
        
    case 'admin/editar-eleccion':
        if (isset($_GET['id'])) {
            require_once 'controllers/EleccionConfigController.php';
            $controller = new controllers\EleccionConfigController();
            $controller->editarEleccion($_GET['id']);
        } else {
            header('Location: /Login/admin/configuracion-elecciones');
        }
        break;
        
    case 'admin/activar-eleccion':
        if (isset($_GET['id'])) {
            require_once 'controllers/EleccionConfigController.php';
            $controller = new controllers\EleccionConfigController();
            $controller->activarEleccion($_GET['id']);
        } else {
            header('Location: /Login/admin/configuracion-elecciones');
        }
        break;
        
    case 'admin/cerrar-eleccion':
        if (isset($_GET['id'])) {
            require_once 'controllers/EleccionConfigController.php';
            $controller = new controllers\EleccionConfigController();
            $controller->cerrarEleccion($_GET['id']);
        } else {
            header('Location: /Login/admin/configuracion-elecciones');
        }
        break;
        
    case 'admin/cancelar-eleccion':
        if (isset($_GET['id'])) {
            require_once 'controllers/EleccionConfigController.php';
            $controller = new controllers\EleccionConfigController();
            $controller->cancelarEleccion($_GET['id']);
        } else {
            header('Location: /Login/admin/configuracion-elecciones');
        }
        break;
        
    case 'admin/eliminar-eleccion':
        if (isset($_GET['id'])) {
            require_once 'controllers/EleccionConfigController.php';
            $controller = new controllers\EleccionConfigController();
            $controller->eliminarEleccion($_GET['id']);
        } else {
            header('Location: /Login/admin/configuracion-elecciones');
        }
        break;
        
    case 'admin/api/estado-elecciones':
        require_once 'controllers/EleccionConfigController.php';
        $controller = new controllers\EleccionConfigController();
        $controller->obtenerEstadoElecciones();
        break;
        
    case 'admin/api/configuracion-actual':
        require_once 'controllers/EleccionConfigController.php';
        $controller = new controllers\EleccionConfigController();
        $controller->obtenerConfiguracionActual();
        break;
        
    case 'api/verificar-disponibilidad-votacion':
        require_once 'controllers/EleccionConfigController.php';
        $controller = new controllers\EleccionConfigController();
        $controller->verificarDisponibilidadVotacion();
        break;
        
    case 'estado-elecciones':
        // Página de estado de elecciones para votantes
        require_once 'views/estado_elecciones.php';
        break;
        
    case 'admin/logs-elecciones':
        // Página de logs de elecciones para administradores
        require_once 'controllers/EleccionConfigController.php';
        $controller = new controllers\EleccionConfigController();
        $controller->mostrarLogs();
        break;
        
    case 'admin/estado-elecciones':
        // Página de estado de elecciones para administradores
        require_once 'controllers/EleccionConfigController.php';
        $controller = new controllers\EleccionConfigController();
        $controller->mostrarEstadoAdmin();
        break;
        
    case 'admin/configuracion-sistema':
        // Configuración del sistema para administradores
        require_once 'controllers/ConfiguracionSistemaController.php';
        $controller = new controllers\ConfiguracionSistemaController();
        $controller->mostrarConfiguracion();
        break;
        
    case 'admin/configuracion-sistema/actualizar':
        // Actualizar configuración del sistema
        require_once 'controllers/ConfiguracionSistemaController.php';
        $controller = new controllers\ConfiguracionSistemaController();
        $controller->actualizarConfiguracion();
        break;
        
    case 'admin/configuracion-sistema/eliminar':
        // Eliminar configuración del sistema
        require_once 'controllers/ConfiguracionSistemaController.php';
        $controller = new controllers\ConfiguracionSistemaController();
        $controller->eliminarConfiguracion();
        break;

    // Rutas para Mesas Virtuales
    case 'admin/mesas-virtuales':
        // Panel principal de mesas virtuales
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->panel();
        break;
        
    case 'admin/crear-mesas':
        // Crear mesas virtuales para una elección
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->crearMesas();
        break;
        
    case 'admin/generar-personal':
        // Generar personal automáticamente
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->generarPersonal();
        break;
        
    case 'admin/limpiar-personal':
        // Limpiar todo el personal de una elección
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->limpiarPersonal();
        break;
        
    case 'admin/regenerar-personal':
        // Regenerar personal para mesas incompletas
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->regenerarPersonal();
        break;
        
    case 'admin/reasignar-estudiantes':
        // Reasignar estudiantes a mesas
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->reasignarEstudiantes();
        break;
        
    case 'admin/gestionar-personal':
        // Gestionar personal de una mesa específica
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->gestionarPersonal();
        break;
        
    // Rutas para Logs del Sistema
    case 'admin/logs':
        // Ver logs del sistema
        require_once 'controllers/LogsController.php';
        $controller = new controllers\LogsController();
        $controller->index();
        break;
        
    case 'admin/logs/limpiar':
        // Limpiar logs antiguos
        require_once 'controllers/LogsController.php';
        $controller = new controllers\LogsController();
        $controller->limpiar();
        break;
        
    case 'admin/logs/mesas-virtuales':
        // API para obtener logs de mesas virtuales
        require_once 'controllers/LogsController.php';
        $controller = new controllers\LogsController();
        $controller->mesasVirtuales();
        break;
        
    case 'admin/agregar-personal':
        // Agregar personal a una mesa
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->agregarPersonal();
        break;
        
    case 'admin/eliminar-personal':
        // Eliminar personal de una mesa
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->eliminarPersonal();
        break;
        
    case 'admin/ver-mesa':
        // Ver detalles de una mesa específica
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->verMesa();
        break;
        
    case 'admin/api/estadisticas-mesas':
        // API para obtener estadísticas de mesas en JSON
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->estadisticasJson();
        break;
        
    case 'admin/api/estadisticas-personal':
        // API para obtener estadísticas de personal en JSON
        require_once 'controllers/MesasVirtualesController.php';
        $controller = new controllers\MesasVirtualesController();
        $controller->estadisticasPersonalJson();
        break;

    case 'enviar-confirmacion':
        // Enviar correo de confirmación
        require_once 'controllers/EmailController.php';
        $controller = new controllers\EmailController();
        $controller->enviarConfirmacion();
        break;

    default:
        // 404 - Página no encontrada
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 - Página no encontrada</h1>";
        echo "<p>La página que buscas no existe.</p>";
        echo "<a href='/Login/'>Volver al inicio</a>";
        break;
}

?>
