<?php
namespace utils;

use models\EleccionConfigModel;
use models\LogsAccesoModel;
use models\ConfiguracionSistemaModel;

class EleccionMiddleware {
    /**
     * Verifica si un votante puede acceder al sistema según la configuración de elecciones
     * @param string $tipoUsuario Tipo de usuario ('estudiante', 'docente', 'administrativo')
     * @return array Resultado de la verificación [puede_acceder, motivo, mensaje, eleccion_activa]
     */
    public static function verificarAccesoVotante($tipoUsuario) {
        $resultado = [
            'puede_acceder' => false,
            'motivo' => 'No hay elecciones activas en este momento.',
            'mensaje' => 'No hay elecciones programadas en este momento. Contacte al administrador.',
            'eleccion_activa' => null
        ];
        
        // Obtener la configuración de elecciones activa
        $eleccionModel = new EleccionConfigModel();
        $eleccionActiva = $eleccionModel->getConfiguracionActiva();
        
        if (!$eleccionActiva) {
            // Verificar si hay alguna elección programada
            $proximaEleccion = $eleccionModel->getProximaEleccion();
            if ($proximaEleccion) {
                $fechaInicio = new \DateTime($proximaEleccion['fecha_inicio']);
                $resultado['motivo'] = 'Elecciones no iniciadas.';
                $resultado['mensaje'] = 'Las elecciones comenzarán el ' . 
                                       $fechaInicio->format('d/m/Y') . ' a las ' . 
                                       $fechaInicio->format('H:i') . '.';
            }
            return $resultado;
        }
        
        // Verificar que realmente estemos dentro del horario de votación
        $ahora = new \DateTime();
        $fechaInicio = new \DateTime($eleccionActiva['fecha_inicio']);
        $fechaCierre = new \DateTime($eleccionActiva['fecha_cierre']);
        
        if ($ahora < $fechaInicio) {
            $resultado['motivo'] = 'La elección aún no ha comenzado.';
            $resultado['mensaje'] = 'Las elecciones comenzarán el ' . 
                                   $fechaInicio->format('d/m/Y') . ' a las ' . 
                                   $fechaInicio->format('H:i') . '.';
            return $resultado;
        }
        
        if ($ahora > $fechaCierre) {
            $resultado['motivo'] = 'La elección ya ha finalizado.';
            $resultado['mensaje'] = 'Las elecciones finalizaron el ' . 
                                   $fechaCierre->format('d/m/Y') . ' a las ' . 
                                   $fechaCierre->format('H:i') . '.';
            return $resultado;
        }
        
        // Verificar si el tipo de usuario está habilitado para esta elección
        $tiposVotacion = $eleccionActiva['tipos_votacion'] ?? [];
        
        // Convertir el tipo de usuario a minúsculas y asegurarse de que sea plural
        $tipoUsuarioNormalizado = strtolower($tipoUsuario);
        if ($tipoUsuarioNormalizado === 'docente') {
            $tipoUsuarioNormalizado = 'docentes';
        } else if ($tipoUsuarioNormalizado === 'estudiante') {
            $tipoUsuarioNormalizado = 'estudiantes';
        } else if ($tipoUsuarioNormalizado === 'administrativo') {
            $tipoUsuarioNormalizado = 'administrativos';
        }
        
        if (!in_array($tipoUsuarioNormalizado, $tiposVotacion)) {
            $resultado['motivo'] = 'Tipo de usuario no habilitado para esta elección.';
            $resultado['mensaje'] = 'Su tipo de usuario no está habilitado para participar en esta elección.';
            $resultado['eleccion_activa'] = $eleccionActiva;
            return $resultado;
        }
        
        // Todo correcto, puede acceder
        $resultado['puede_acceder'] = true;
        $resultado['motivo'] = '';
        $resultado['mensaje'] = 'Acceso permitido.';
        $resultado['eleccion_activa'] = $eleccionActiva;
        
        return $resultado;
    }
    
    /**
     * Verifica si hay elecciones activas en este momento
     * @return bool True si hay elecciones activas, false en caso contrario
     */
    public static function verificarEleccionesActivas() {
        $eleccionModel = new EleccionConfigModel();
        return $eleccionModel->verificarEleccionesActivas();
    }
    
    /**
     * Verifica si es horario de votación según la configuración del sistema
     * @return bool True si es horario de votación, false en caso contrario
     */
    public static function verificarHorarioVotacion() {
        $configModel = new ConfiguracionSistemaModel();
        $horario = $configModel->getHorarioVotacion();
        
        // Si no está activo el control de horario, siempre es válido
        if (!$horario['activo']) {
            return true;
        }
        
        // Si no hay horario configurado, no es válido
        if (!$horario['inicio'] || !$horario['cierre']) {
            return false;
        }
        
        // Verificar si estamos dentro del horario
        $ahora = new \DateTime();
        $inicio = new \DateTime($horario['inicio']);
        $cierre = new \DateTime($horario['cierre']);
        
        return $ahora >= $inicio && $ahora <= $cierre;
    }
    
    /**
     * Registra un intento de acceso en los logs
     * @param array $datos Datos del intento de acceso
     * @return int|bool ID del log o false en caso de error
     */
    public static function registrarIntentoAcceso($datos) {
        $logsModel = new LogsAccesoModel();
        $datos['accion'] = 'intento_login';
        return $logsModel->registrarLog($datos);
    }
    
    /**
     * Registra un acceso exitoso en los logs
     * @param array $datos Datos del acceso exitoso
     * @return int|bool ID del log o false en caso de error
     */
    public static function registrarAccesoExitoso($datos) {
        $logsModel = new LogsAccesoModel();
        $datos['accion'] = 'login_exitoso';
        return $logsModel->registrarLog($datos);
    }
    
    /**
     * Registra un acceso bloqueado en los logs
     * @param array $datos Datos del acceso bloqueado
     * @return int|bool ID del log o false en caso de error
     */
    public static function registrarAccesoBloqueado($datos) {
        $logsModel = new LogsAccesoModel();
        $datos['accion'] = 'login_bloqueado';
        return $logsModel->registrarLog($datos);
    }
    
    /**
     * Registra un voto en los logs
     * @param array $datos Datos del voto
     * @return int|bool ID del log o false en caso de error
     */
    public static function registrarVoto($datos) {
        $logsModel = new LogsAccesoModel();
        $datos['accion'] = 'voto_registrado';
        return $logsModel->registrarLog($datos);
    }
    
    /**
     * Obtiene un mensaje informativo sobre el estado actual de las elecciones
     * @return array Información de estado [estado, mensaje, tiempo_restante]
     */
    public static function obtenerMensajeEstado() {
        $eleccionModel = new EleccionConfigModel();
        
        // Primero verificar si hay elecciones activas según la configuración de fechas
        $eleccionActiva = $eleccionModel->getConfiguracionActiva();
        
        // Si no hay elecciones activas según fechas, verificar si hay alguna con estado "activa"
        // pero validar que realmente esté dentro del horario permitido
        if (!$eleccionActiva) {
            $sql = "SELECT * FROM configuracion_elecciones WHERE estado = 'activa' ORDER BY fecha_inicio ASC LIMIT 1";
            $db = new \config\Database();
            $conn = $db->getConnection();
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $eleccionPotencial = $result->fetch_assoc();
                
                // Verificar si realmente está dentro del horario
                $ahora = new \DateTime();
                $fechaInicio = new \DateTime($eleccionPotencial['fecha_inicio']);
                $fechaCierre = new \DateTime($eleccionPotencial['fecha_cierre']);
                
                // Solo considerar activa si estamos dentro del rango de tiempo
                if ($ahora >= $fechaInicio && $ahora <= $fechaCierre) {
                    $eleccionActiva = $eleccionPotencial;
                    // Decodificar campos JSON
                    if (!empty($eleccionActiva['tipos_votacion'])) {
                        $eleccionActiva['tipos_votacion'] = json_decode($eleccionActiva['tipos_votacion'], true);
                    } else {
                        $eleccionActiva['tipos_votacion'] = [];
                    }
                    if (!empty($eleccionActiva['configuracion_adicional'])) {
                        $eleccionActiva['configuracion_adicional'] = json_decode($eleccionActiva['configuracion_adicional'], true);
                    } else {
                        $eleccionActiva['configuracion_adicional'] = [];
                    }
                }
            }
        }
        
        if ($eleccionActiva) {
            $fechaCierre = new \DateTime($eleccionActiva['fecha_cierre']);
            $ahora = new \DateTime();
            
            // Verificar si la fecha de cierre ya pasó
            if ($fechaCierre->getTimestamp() < $ahora->getTimestamp()) {
                // La elección ha finalizado
                return [
                    'estado' => 'finalizada',
                    'mensaje' => 'Las elecciones han finalizado el ' . 
                                $fechaCierre->format('d/m/Y') . ' a las ' . 
                                $fechaCierre->format('H:i') . '.',
                    'tiempo_restante' => '0 minuto(s)'
                ];
            }
            
            // La elección está activa
            $intervalo = $ahora->diff($fechaCierre);
            
            $tiempoRestante = '';
            if ($intervalo->days > 0) {
                $tiempoRestante .= $intervalo->days . ' día(s) ';
            }
            if ($intervalo->h > 0) {
                $tiempoRestante .= $intervalo->h . ' hora(s) ';
            }
            $tiempoRestante .= $intervalo->i . ' minuto(s)';
            
            return [
                'estado' => 'activa',
                'mensaje' => 'Las elecciones están activas. Puede ejercer su voto hasta las ' . 
                            $fechaCierre->format('H:i') . '.',
                'tiempo_restante' => $tiempoRestante
            ];
        }
        
        // Verificar si hay alguna elección programada
        $proximaEleccion = $eleccionModel->getProximaEleccion();
        if ($proximaEleccion) {
            $fechaInicio = new \DateTime($proximaEleccion['fecha_inicio']);
            $ahora = new \DateTime();
            $intervalo = $ahora->diff($fechaInicio);
            
            $tiempoRestante = '';
            if ($intervalo->days > 0) {
                $tiempoRestante .= $intervalo->days . ' día(s) ';
            }
            if ($intervalo->h > 0) {
                $tiempoRestante .= $intervalo->h . ' hora(s) ';
            }
            $tiempoRestante .= $intervalo->i . ' minuto(s)';
            
            return [
                'estado' => 'programada',
                'mensaje' => 'Las elecciones comenzarán el ' . 
                            $fechaInicio->format('d/m/Y') . ' a las ' . 
                            $fechaInicio->format('H:i') . '.',
                'tiempo_restante' => $tiempoRestante
            ];
        }
        
        // No hay elecciones activas ni programadas
        return [
            'estado' => 'sin_elecciones',
            'mensaje' => 'No hay elecciones programadas en este momento.',
            'tiempo_restante' => ''
        ];
    }
    
    /**
     * Obtiene el tiempo restante para el inicio o cierre de elecciones
     * @return array Tiempo restante [para_inicio, para_cierre, formato_humano]
     */
    public static function obtenerTiempoRestante() {
        $eleccionModel = new EleccionConfigModel();
        $eleccionActiva = $eleccionModel->getConfiguracionActiva();
        
        $resultado = [
            'para_inicio' => 0,
            'para_cierre' => 0,
            'formato_humano' => 'No hay elecciones programadas'
        ];
        
        if ($eleccionActiva) {
            // Elección activa, calcular tiempo para cierre
            $fechaCierre = new \DateTime($eleccionActiva['fecha_cierre']);
            $ahora = new \DateTime();
            $segundosParaCierre = max(0, $fechaCierre->getTimestamp() - $ahora->getTimestamp());
            
            $resultado['para_inicio'] = 0;
            $resultado['para_cierre'] = $segundosParaCierre;
            
            if ($segundosParaCierre > 0) {
                $horas = floor($segundosParaCierre / 3600);
                $minutos = floor(($segundosParaCierre % 3600) / 60);
                
                $formatoHumano = '';
                if ($horas > 0) {
                    $formatoHumano .= $horas . ' hora(s) ';
                }
                $formatoHumano .= $minutos . ' minuto(s) restante(s)';
                
                $resultado['formato_humano'] = $formatoHumano;
            } else {
                $resultado['formato_humano'] = 'Las elecciones están por finalizar';
            }
        } else {
            // Verificar si hay alguna elección programada
            $proximaEleccion = $eleccionModel->getProximaEleccion();
            if ($proximaEleccion) {
                $fechaInicio = new \DateTime($proximaEleccion['fecha_inicio']);
                $ahora = new \DateTime();
                $segundosParaInicio = max(0, $fechaInicio->getTimestamp() - $ahora->getTimestamp());
                
                $resultado['para_inicio'] = $segundosParaInicio;
                $resultado['para_cierre'] = 0;
                
                if ($segundosParaInicio > 0) {
                    $dias = floor($segundosParaInicio / 86400);
                    $horas = floor(($segundosParaInicio % 86400) / 3600);
                    $minutos = floor(($segundosParaInicio % 3600) / 60);
                    
                    $formatoHumano = '';
                    if ($dias > 0) {
                        $formatoHumano .= $dias . ' día(s) ';
                    }
                    if ($horas > 0) {
                        $formatoHumano .= $horas . ' hora(s) ';
                    }
                    $formatoHumano .= $minutos . ' minuto(s) para inicio';
                    
                    $resultado['formato_humano'] = $formatoHumano;
                } else {
                    $resultado['formato_humano'] = 'Las elecciones están por comenzar';
                }
            }
        }
        
        return $resultado;
    }
    
    /**
     * Obtiene información detallada sobre la elección actual o próxima
     * @return array|null Información de la elección o null si no hay ninguna
     */
    public static function obtenerInformacionEleccion() {
        $eleccionModel = new EleccionConfigModel();
        
        // Primero verificar si hay elecciones activas según la configuración de fechas
        $eleccionActiva = $eleccionModel->getConfiguracionActiva();
        
        // Si no hay elecciones activas según fechas, verificar si hay alguna con estado "activa"
        // pero validar que realmente esté dentro del horario permitido
        if (!$eleccionActiva) {
            $sql = "SELECT * FROM configuracion_elecciones WHERE estado = 'activa' ORDER BY fecha_inicio ASC LIMIT 1";
            $db = new \config\Database();
            $conn = $db->getConnection();
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $eleccionPotencial = $result->fetch_assoc();
                
                // Verificar si realmente está dentro del horario
                $ahora = new \DateTime();
                $fechaInicio = new \DateTime($eleccionPotencial['fecha_inicio']);
                $fechaCierre = new \DateTime($eleccionPotencial['fecha_cierre']);
                
                // Solo considerar activa si estamos dentro del rango de tiempo
                if ($ahora >= $fechaInicio && $ahora <= $fechaCierre) {
                    $eleccionActiva = $eleccionPotencial;
                    // Decodificar campos JSON
                    if (!empty($eleccionActiva['tipos_votacion'])) {
                        $eleccionActiva['tipos_votacion'] = json_decode($eleccionActiva['tipos_votacion'], true);
                    } else {
                        $eleccionActiva['tipos_votacion'] = [];
                    }
                    if (!empty($eleccionActiva['configuracion_adicional'])) {
                        $eleccionActiva['configuracion_adicional'] = json_decode($eleccionActiva['configuracion_adicional'], true);
                    } else {
                        $eleccionActiva['configuracion_adicional'] = [];
                    }
                }
            }
        }
        
        if ($eleccionActiva) {
            return [
                'id' => $eleccionActiva['id'],
                'nombre' => $eleccionActiva['nombre_eleccion'],
                'descripcion' => $eleccionActiva['descripcion'],
                'fecha_inicio' => $eleccionActiva['fecha_inicio'],
                'fecha_cierre' => $eleccionActiva['fecha_cierre'],
                'estado' => 'activa',
                'tipos_votacion' => $eleccionActiva['tipos_votacion'],
                'tiempo_restante' => self::obtenerTiempoRestante()
            ];
        }
        
        // Verificar si hay alguna elección programada
        $proximaEleccion = $eleccionModel->getProximaEleccion();
        if ($proximaEleccion) {
            return [
                'id' => $proximaEleccion['id'],
                'nombre' => $proximaEleccion['nombre_eleccion'],
                'descripcion' => $proximaEleccion['descripcion'],
                'fecha_inicio' => $proximaEleccion['fecha_inicio'],
                'fecha_cierre' => $proximaEleccion['fecha_cierre'],
                'estado' => 'programada',
                'tipos_votacion' => $proximaEleccion['tipos_votacion'],
                'tiempo_restante' => self::obtenerTiempoRestante()
            ];
        }
        
        return null;
    }
    
    /**
     * Verifica si un usuario puede votar en la elección actual
     * @param string $tipoUsuario Tipo de usuario ('estudiante', 'docente', 'administrativo')
     * @param string $idUsuario ID del usuario
     * @return array Resultado de la verificación [puede_votar, motivo]
     */
    public static function puedeVotar($tipoUsuario, $idUsuario) {
        $resultado = [
            'puede_votar' => false,
            'motivo' => 'No hay elecciones activas.'
        ];
        
        // Convertir el tipo de usuario a minúsculas y asegurarse de que sea plural
        $tipoUsuarioNormalizado = strtolower($tipoUsuario);
        if ($tipoUsuarioNormalizado === 'docente') {
            $tipoUsuarioNormalizado = 'docentes';
        } else if ($tipoUsuarioNormalizado === 'estudiante') {
            $tipoUsuarioNormalizado = 'estudiantes';
        } else if ($tipoUsuarioNormalizado === 'administrativo') {
            $tipoUsuarioNormalizado = 'administrativos';
        }
        
        // Verificar si hay elecciones activas
        $verificacionAcceso = self::verificarAccesoVotante($tipoUsuarioNormalizado);
        if (!$verificacionAcceso['puede_acceder']) {
            $resultado['motivo'] = $verificacionAcceso['motivo'];
            return $resultado;
        }
        
        // Obtener la elección activa
        $eleccionModel = new EleccionConfigModel();
        $eleccionActiva = $eleccionModel->getConfiguracionActiva();
        
        // Verificar que estamos dentro del rango de horas de la elección
        if ($eleccionActiva) {
            $ahora = new \DateTime();
            $fechaInicio = new \DateTime($eleccionActiva['fecha_inicio']);
            $fechaCierre = new \DateTime($eleccionActiva['fecha_cierre']);
            
            // Verificamos si la fecha de inicio es posterior a la fecha actual
            // Margen de 1 minuto para evitar problemas menores de sincronización
            $fechaInicioTimestamp = $fechaInicio->getTimestamp();
            $ahoraTimestamp = $ahora->getTimestamp();
            
            // 1 minuto = 60 segundos
            if ($ahoraTimestamp < ($fechaInicioTimestamp - 60)) {
                $resultado['motivo'] = 'La elección aún no ha comenzado. Comienza el ' . 
                                      $fechaInicio->format('d/m/Y') . ' a las ' . 
                                      $fechaInicio->format('H:i') . '.';
                return $resultado;
            }
            
            // Verificamos si la fecha de cierre es anterior a la fecha actual
            // Margen de 1 minuto para evitar problemas menores de sincronización
            $fechaCierreTimestamp = $fechaCierre->getTimestamp();
            
            // 1 minuto = 60 segundos
            if ($ahoraTimestamp > ($fechaCierreTimestamp + 60)) {
                $resultado['motivo'] = 'La elección ya ha finalizado. Finalizó el ' . 
                                      $fechaCierre->format('d/m/Y') . ' a las ' . 
                                      $fechaCierre->format('H:i') . '.';
                return $resultado;
            }
        }
        
        // Verificar si ya votó
        $yaVoto = self::yaVoto($tipoUsuario, $idUsuario);
        if ($yaVoto['ya_voto']) {
            $resultado['motivo'] = $yaVoto['motivo'];
            return $resultado;
        }
        
        // Todo correcto, puede votar
        $resultado['puede_votar'] = true;
        $resultado['motivo'] = '';
        
        return $resultado;
    }
    
    /**
     * Verifica si un usuario ya votó en la elección actual
     * @param string $tipoUsuario Tipo de usuario ('estudiante', 'docente', 'administrativo')
     * @param string $idUsuario ID del usuario
     * @return array Resultado de la verificación [ya_voto, motivo]
     */
    public static function yaVoto($tipoUsuario, $idUsuario) {
        $resultado = [
            'ya_voto' => false,
            'motivo' => ''
        ];
        
        // Obtener la elección activa
        $eleccionModel = new EleccionConfigModel();
        $eleccionActiva = $eleccionModel->getConfiguracionActiva();
        
        if (!$eleccionActiva) {
            return $resultado;
        }
        
        // Verificar en la tabla correspondiente según el tipo de usuario
        $database = new \config\Database();
        $db = $database->getConnection();
        
        switch ($tipoUsuario) {
            case 'estudiante':
                $sql = "SELECT COUNT(*) as total FROM votos 
                        WHERE id_estudiante = ? AND fecha_voto >= ?";
                break;
            case 'docente':
                $sql = "SELECT COUNT(*) as total FROM votos_docentes 
                        WHERE id_docente = ? AND fecha_voto >= ?";
                break;
            case 'administrativo':
                $sql = "SELECT COUNT(*) as total FROM votos_administrativos 
                        WHERE id_administrativo = ? AND fecha_voto >= ?";
                break;
            default:
                return $resultado;
        }
        
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $idUsuario, $eleccionActiva['fecha_inicio']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                $row = $result->fetch_assoc();
                $conteo = $row['total'];
                
                if ($conteo > 0) {
                    $resultado['ya_voto'] = true;
                    $resultado['motivo'] = 'Ya ha ejercido su voto en esta elección.';
                }
            }
        }
        
        return $resultado;
    }
}
