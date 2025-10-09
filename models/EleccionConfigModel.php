<?php
namespace models;

use config\Database;
use Exception;

class EleccionConfigModel {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Obtiene la configuración de elección activa actualmente
     * @return array|null Datos de la elección activa o null si no hay ninguna
     */
    public function getConfiguracionActiva() {
        // Primero verificar y actualizar el estado de las elecciones
        $this->verificarYActualizarEstadoElecciones();
        
        // Consulta directa sin márgenes de tiempo para evitar inconsistencias
        $sql = "SELECT * FROM configuracion_elecciones 
                WHERE estado = 'activa' 
                AND fecha_inicio <= NOW() 
                AND fecha_cierre > NOW() 
                ORDER BY fecha_inicio ASC 
                LIMIT 1";
        
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Decodificar campos JSON
            if (!empty($row['tipos_votacion'])) {
                $row['tipos_votacion'] = json_decode($row['tipos_votacion'], true);
            } else {
                $row['tipos_votacion'] = [];
            }
            if (!empty($row['configuracion_adicional'])) {
                $row['configuracion_adicional'] = json_decode($row['configuracion_adicional'], true);
            } else {
                $row['configuracion_adicional'] = [];
            }
            return $row;
        }
        
        return null;
    }
    
    /**
     * Obtiene todas las elecciones
     * @return array Lista de todas las elecciones
     */
    /**
     * Verifica y actualiza el estado de las elecciones según la fecha actual
     * Esta función se llama automáticamente antes de obtener las elecciones
     */
    public function verificarYActualizarEstadoElecciones() {
        // Actualizar elecciones activas cuya fecha de cierre ya pasó
        $sql = "UPDATE configuracion_elecciones 
                SET estado = 'cerrada' 
                WHERE estado = 'activa' 
                AND fecha_cierre < NOW()";
        $this->db->query($sql);
        
        // Activar automáticamente las elecciones programadas cuya fecha de inicio ya llegó
        $sql = "UPDATE configuracion_elecciones 
                SET estado = 'activa' 
                WHERE estado = 'programada' 
                AND fecha_inicio <= NOW() 
                AND fecha_cierre > NOW()";
        $this->db->query($sql);
    }
    
    public function getTodasElecciones() {
        // Primero verificar y actualizar el estado de las elecciones
        $this->verificarYActualizarEstadoElecciones();
        
        $sql = "SELECT * FROM configuracion_elecciones ORDER BY fecha_inicio DESC";
        $result = $this->db->query($sql);
        
        $resultados = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Decodificar campos JSON
                if (!empty($row['tipos_votacion'])) {
                    $row['tipos_votacion'] = json_decode($row['tipos_votacion'], true);
                } else {
                    $row['tipos_votacion'] = [];
                }
                if (!empty($row['configuracion_adicional'])) {
                    $row['configuracion_adicional'] = json_decode($row['configuracion_adicional'], true);
                } else {
                    $row['configuracion_adicional'] = [];
                }
                $resultados[] = $row;
            }
        }
        
        return $resultados;
    }
    
    /**
     * Crea una nueva configuración de elección
     * @param array $datos Datos de la nueva configuración
     * @return int|bool ID de la nueva configuración o false en caso de error
     */
    public function crearConfiguracion($datos) {
        $sql = "INSERT INTO configuracion_elecciones 
                (nombre_eleccion, descripcion, fecha_inicio, fecha_cierre, 
                estado, tipos_votacion, configuracion_adicional, creado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Convertir arrays a JSON
        $tiposVotacion = json_encode($datos['tipos_votacion'] ?? []);
        $configAdicional = json_encode($datos['configuracion_adicional'] ?? []);
        $estado = $datos['estado'] ?? 'programada';
        
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sssssssi',
                $datos['nombre_eleccion'],
                $datos['descripcion'],
                $datos['fecha_inicio'],
                $datos['fecha_cierre'],
                $estado,
                $tiposVotacion,
                $configAdicional,
                $datos['creado_por']
            );
            
            if ($stmt->execute()) {
                return $this->db->insert_id;
            }
        }
        
        return false;
    }
    
    /**
     * Actualiza una configuración de elección existente
     * @param int $id ID de la configuración a actualizar
     * @param array $datos Nuevos datos de la configuración
     * @return bool Éxito de la operación
     */
    public function actualizarConfiguracion($id, $datos) {
        $sql = "UPDATE configuracion_elecciones SET 
                nombre_eleccion = ?, descripcion = ?, fecha_inicio = ?, 
                fecha_cierre = ?, estado = ?, tipos_votacion = ?, 
                configuracion_adicional = ? 
                WHERE id = ?";
        
        // Convertir arrays a JSON
        $tiposVotacion = json_encode($datos['tipos_votacion'] ?? []);
        $configAdicional = json_encode($datos['configuracion_adicional'] ?? []);
        
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sssssssi',
                $datos['nombre_eleccion'],
                $datos['descripcion'],
                $datos['fecha_inicio'],
                $datos['fecha_cierre'],
                $datos['estado'],
                $tiposVotacion,
                $configAdicional,
                $id
            );
            
            return $stmt->execute();
        }
        
        return false;
    }
    
    /**
     * Elimina una configuración de elección y todos sus datos relacionados
     * @param int $id ID de la configuración a eliminar
     * @return bool Éxito de la operación
     */
    public function eliminarConfiguracion($id) {
        try {
            // Iniciar transacción para asegurar integridad
            $this->db->autocommit(false);
            $this->db->begin_transaction();
            
            // 1. Eliminar logs de acceso a elecciones
            $sql = "DELETE FROM logs_acceso_elecciones WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            
            // 2. Eliminar votos de estudiantes
            $sql = "DELETE FROM votos WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            
            // 3. Eliminar votos de docentes
            $sql = "DELETE FROM votos_docentes WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            
            // 4. Eliminar votos administrativos (si existe la tabla)
            $sql = "DELETE FROM votos_administrativos WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            
            // 5. Eliminar asignaciones de estudiantes a mesas
            $sql = "DELETE em FROM estudiantes_mesas em 
                    INNER JOIN mesas_virtuales mv ON em.id_mesa = mv.id_mesa 
                    WHERE mv.id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            
            // 6. Eliminar personal de mesas
            $sql = "DELETE pm FROM personal_mesa pm 
                    INNER JOIN mesas_virtuales mv ON pm.id_mesa = mv.id_mesa 
                    WHERE mv.id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            
            // 7. Eliminar mesas virtuales (esto debería ser automático por CASCADE)
            $sql = "DELETE FROM mesas_virtuales WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            
            // 8. Eliminar logs del sistema relacionados con esta elección
            $sql = "DELETE FROM logs_sistema WHERE datos_adicionales LIKE ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $patron = '%"id_eleccion":' . $id . '%';
                $stmt->bind_param('s', $patron);
                $stmt->execute();
            }
            
            // 9. Eliminar histórico de elecciones
            $sql = "DELETE FROM historico_elecciones WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            
            // 10. Finalmente, eliminar la configuración de elección
            $sql = "DELETE FROM configuracion_elecciones WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $resultado = $stmt->execute();
                
                if ($resultado) {
                    // Confirmar transacción
                    $this->db->commit();
                    $this->db->autocommit(true);
                    return true;
                } else {
                    // Revertir transacción
                    $this->db->rollback();
                    $this->db->autocommit(true);
                    return false;
                }
            }
            
            // Si llegamos aquí, algo salió mal
            $this->db->rollback();
            $this->db->autocommit(true);
            return false;
            
        } catch (Exception $e) {
            // En caso de error, revertir transacción
            $this->db->rollback();
            $this->db->autocommit(true);
            error_log("Error al eliminar elección: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si hay elecciones activas en este momento
     * @return bool True si hay elecciones activas, false en caso contrario
     */
    public function verificarEleccionesActivas() {
        $sql = "SELECT COUNT(*) as total FROM configuracion_elecciones 
                WHERE estado = 'activa' 
                AND fecha_inicio <= NOW() 
                AND fecha_cierre >= NOW()";
        
        $result = $this->db->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total'] > 0;
        }
        
        return false;
    }
    
    /**
     * Obtiene la próxima elección programada
     * @return array|null Datos de la próxima elección o null si no hay ninguna
     */
    public function getProximaEleccion() {
        // Primero verificar y actualizar el estado de las elecciones
        $this->verificarYActualizarEstadoElecciones();
        
        // Obtener cualquier elección programada, independientemente de la fecha de inicio
        $sql = "SELECT * FROM configuracion_elecciones 
                WHERE estado = 'programada' 
                ORDER BY fecha_inicio ASC 
                LIMIT 1";
        
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Decodificar campos JSON
            if (!empty($row['tipos_votacion'])) {
                $row['tipos_votacion'] = json_decode($row['tipos_votacion'], true);
            } else {
                $row['tipos_votacion'] = [];
            }
            if (!empty($row['configuracion_adicional'])) {
                $row['configuracion_adicional'] = json_decode($row['configuracion_adicional'], true);
            } else {
                $row['configuracion_adicional'] = [];
            }
            return $row;
        }
        
        // Asegurarse de devolver null cuando no hay elecciones programadas
        return null;
    }
    
    /**
     * Cambia manualmente el estado de una elección
     * @param int $id ID de la elección
     * @param string $estado Nuevo estado ('programada', 'activa', 'cerrada', 'cancelada')
     * @return bool Éxito de la operación
     */
    public function cambiarEstadoEleccion($id, $estado) {
        $estadosValidos = ['programada', 'activa', 'cerrada', 'cancelada'];
        if (!in_array($estado, $estadosValidos)) {
            return false;
        }
        
        $sql = "UPDATE configuracion_elecciones SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('si', $estado, $id);
            return $stmt->execute();
        }
        
        return false;
    }
    
    /**
     * Obtiene una elección específica por ID
     * @param int $id ID de la elección
     * @return array|null Datos de la elección o null si no existe
     */
    public function getEleccionPorId($id) {
        $sql = "SELECT * FROM configuracion_elecciones WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                // Decodificar campos JSON
                if (!empty($row['tipos_votacion'])) {
                    $row['tipos_votacion'] = json_decode($row['tipos_votacion'], true);
                } else {
                    $row['tipos_votacion'] = [];
                }
                if (!empty($row['configuracion_adicional'])) {
                    $row['configuracion_adicional'] = json_decode($row['configuracion_adicional'], true);
                } else {
                    $row['configuracion_adicional'] = [];
                }
                return $row;
            }
        }
        
        return null;
    }
    
    /**
     * Obtiene elecciones por estado
     * @param string $estado Estado de las elecciones ('programada', 'activa', 'cerrada', 'cancelada')
     * @return array Lista de elecciones con el estado especificado
     */
    public function getEleccionesPorEstado($estado) {
        $estadosValidos = ['programada', 'activa', 'cerrada', 'cancelada'];
        if (!in_array($estado, $estadosValidos)) {
            return [];
        }
        
        $sql = "SELECT * FROM configuracion_elecciones 
                WHERE estado = ? 
                ORDER BY fecha_inicio DESC";
        
        $stmt = $this->db->prepare($sql);
        $resultados = [];
        
        if ($stmt) {
            $stmt->bind_param('s', $estado);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                // Decodificar campos JSON
                if (!empty($row['tipos_votacion'])) {
                    $row['tipos_votacion'] = json_decode($row['tipos_votacion'], true);
                } else {
                    $row['tipos_votacion'] = [];
                }
                if (!empty($row['configuracion_adicional'])) {
                    $row['configuracion_adicional'] = json_decode($row['configuracion_adicional'], true);
                } else {
                    $row['configuracion_adicional'] = [];
                }
                $resultados[] = $row;
            }
        }
        
        return $resultados;
    }
    
    /**
     * Activar automáticamente las elecciones programadas que ya llegaron a su hora de inicio
     * @return int Número de elecciones activadas
     */
    public function activarEleccionesProgramadas() {
        $sql = "UPDATE configuracion_elecciones 
                SET estado = 'activa' 
                WHERE estado = 'programada' 
                AND fecha_inicio <= NOW() 
                AND fecha_cierre >= NOW()";
        
        if ($this->db->query($sql)) {
            return $this->db->affected_rows;
        }
        
        return 0;
    }
    
    /**
     * Cerrar automáticamente las elecciones activas que ya llegaron a su hora de cierre
     * @return int Número de elecciones cerradas
     */
    public function cerrarEleccionesVencidas() {
        $sql = "UPDATE configuracion_elecciones 
                SET estado = 'cerrada' 
                WHERE estado = 'activa' 
                AND fecha_cierre <= NOW()";
        
        if ($this->db->query($sql)) {
            return $this->db->affected_rows;
        }
        
        return 0;
    }
    
    /**
     * Valida que los horarios de elección sean correctos
     * @param string $fechaInicio Fecha y hora de inicio
     * @param string $fechaCierre Fecha y hora de cierre
     * @return array Resultado de la validación [valido => bool, mensaje => string]
     */
    public function validarHorarios($fechaInicio, $fechaCierre) {
        $resultado = [
            'valido' => true,
            'mensaje' => 'Los horarios son válidos.'
        ];
        
        // Convertir a timestamps para comparación
        $timestampInicio = strtotime($fechaInicio);
        $timestampCierre = strtotime($fechaCierre);
        $ahora = time();
        
        // Verificar que las fechas son válidas
        if (!$timestampInicio || !$timestampCierre) {
            return [
                'valido' => false,
                'mensaje' => 'Las fechas proporcionadas no tienen un formato válido.'
            ];
        }
        
        // La fecha de inicio debe ser anterior a la de cierre
        if ($timestampInicio >= $timestampCierre) {
            return [
                'valido' => false,
                'mensaje' => 'La fecha de inicio debe ser anterior a la fecha de cierre.'
            ];
        }
        
        // Desactivamos temporalmente la validación de hora futura
        // para permitir crear elecciones sin restricciones de hora
        // Esto es útil cuando hay diferencias de tiempo entre el cliente y el servidor
        /*
        if ($timestampInicio < $ahora - 30) {
            return [
                'valido' => false,
                'mensaje' => 'La hora de inicio debe ser posterior a la hora actual.'
            ];
        }
        */
        
        // Verificar duración mínima (15 minutos)
        $duracionMinima = 900; // 15 minutos en segundos
        if (($timestampCierre - $timestampInicio) < $duracionMinima) {
            return [
                'valido' => false,
                'mensaje' => 'La duración mínima de una elección debe ser de 15 minutos.'
            ];
        }
        
        return $resultado;
    }
    
    /**
     * Obtiene el historial de elecciones pasadas
     * @param int $limite Límite de resultados
     * @return array Lista de elecciones históricas
     */
    public function getEleccionesHistoricas($limite = 10) {
        // Primero verificar y actualizar el estado de las elecciones
        $this->verificarYActualizarEstadoElecciones();
        
        $sql = "SELECT * FROM configuracion_elecciones 
                WHERE estado = 'cerrada' 
                ORDER BY fecha_cierre DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $resultados = [];
        
        if ($stmt) {
            $stmt->bind_param('i', $limite);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                // Decodificar campos JSON
                if (!empty($row['tipos_votacion'])) {
                    $row['tipos_votacion'] = json_decode($row['tipos_votacion'], true);
                } else {
                    $row['tipos_votacion'] = [];
                }
                if (!empty($row['configuracion_adicional'])) {
                    $row['configuracion_adicional'] = json_decode($row['configuracion_adicional'], true);
                } else {
                    $row['configuracion_adicional'] = [];
                }
                $resultados[] = $row;
            }
        }
        
        return $resultados;
    }
    
    /**
     * Verifica si hay conflictos de horario con otras elecciones
     * @param string $fechaInicio Fecha y hora de inicio
     * @param string $fechaCierre Fecha y hora de cierre
     * @param int $idExcluir ID de elección a excluir (para ediciones)
     * @return array Resultado de la verificación [hayConflicto => bool, mensaje => string]
     */
    public function verificarConflictosHorarios($fechaInicio, $fechaCierre, $idExcluir = null) {
        $resultado = [
            'hayConflicto' => false,
            'mensaje' => 'No hay conflictos de horario.'
        ];
        
        $sql = "SELECT id, nombre_eleccion, fecha_inicio, fecha_cierre FROM configuracion_elecciones 
                WHERE (
                    (fecha_inicio <= ? AND fecha_cierre >= ?) OR
                    (fecha_inicio <= ? AND fecha_cierre >= ?) OR
                    (fecha_inicio >= ? AND fecha_cierre <= ?)
                )
                AND estado IN ('programada', 'activa')";
        
        $params = [$fechaInicio, $fechaInicio, $fechaCierre, $fechaCierre, $fechaInicio, $fechaCierre];
        
        // Excluir la elección actual si se está editando
        if ($idExcluir !== null) {
            $sql .= " AND id != ?";
            $params[] = $idExcluir;
        }
        
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            // Crear string de tipos para bind_param
            $types = str_repeat('s', count($params) - ($idExcluir !== null ? 1 : 0));
            if ($idExcluir !== null) {
                $types .= 'i';
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $conflictos = [];
            while ($row = $result->fetch_assoc()) {
                $conflictos[] = $row;
            }
            
            if (count($conflictos) > 0) {
                $eleccionesConflicto = [];
                foreach ($conflictos as $conflicto) {
                    $eleccionesConflicto[] = $conflicto['nombre_eleccion'] . ' (' . 
                                            date('d/m/Y H:i', strtotime($conflicto['fecha_inicio'])) . ' - ' . 
                                            date('d/m/Y H:i', strtotime($conflicto['fecha_cierre'])) . ')';
                }
                
                return [
                    'hayConflicto' => true,
                    'mensaje' => 'Hay conflictos de horario con las siguientes elecciones: ' . 
                                implode(', ', $eleccionesConflicto)
                ];
            }
        }
        
        return $resultado;
    }

    /**
     * Obtiene información sobre los datos relacionados con una elección
     * @param int $id ID de la elección
     * @return array Contadores de datos relacionados
     */
    public function obtenerDatosRelacionados($id) {
        $datos = [
            'votos_estudiantes' => 0,
            'votos_docentes' => 0,
            'votos_administrativos' => 0,
            'mesas_virtuales' => 0,
            'logs_acceso' => 0,
            'historico' => 0
        ];

        try {
            // Contar votos de estudiantes
            $sql = "SELECT COUNT(*) as total FROM votos WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $datos['votos_estudiantes'] = $row['total'];
                }
            }

            // Contar votos de docentes
            $sql = "SELECT COUNT(*) as total FROM votos_docentes WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $datos['votos_docentes'] = $row['total'];
                }
            }

            // Contar votos administrativos
            $sql = "SELECT COUNT(*) as total FROM votos_administrativos WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $datos['votos_administrativos'] = $row['total'];
                }
            }

            // Contar mesas virtuales
            $sql = "SELECT COUNT(*) as total FROM mesas_virtuales WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $datos['mesas_virtuales'] = $row['total'];
                }
            }

            // Contar logs de acceso
            $sql = "SELECT COUNT(*) as total FROM logs_acceso_elecciones WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $datos['logs_acceso'] = $row['total'];
                }
            }

            // Contar histórico
            $sql = "SELECT COUNT(*) as total FROM historico_elecciones WHERE id_eleccion = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $datos['historico'] = $row['total'];
                }
            }

        } catch (Exception $e) {
            error_log("Error al obtener datos relacionados: " . $e->getMessage());
        }

        return $datos;
    }

    /**
     * Activa una elección manualmente y ajusta la fecha de inicio si es necesario
     * @param int $id ID de la elección
     * @return bool True si se activó correctamente
     */
    public function activarEleccionManual($id) {
        try {
            // Obtener datos de la elección
            $eleccion = $this->getEleccionPorId($id);
            if (!$eleccion) {
                return false;
            }

            // Si la fecha de inicio es futura, ajustarla al momento actual
            $fechaActual = date('Y-m-d H:i:s');
            $fechaInicio = $eleccion['fecha_inicio'];
            
            if ($fechaInicio > $fechaActual) {
                // Ajustar fecha de inicio al momento actual
                $sql = "UPDATE configuracion_elecciones 
                        SET estado = 'activa', fecha_inicio = NOW() 
                        WHERE id = ?";
            } else {
                // Solo cambiar el estado
                $sql = "UPDATE configuracion_elecciones 
                        SET estado = 'activa' 
                        WHERE id = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $id);
            $resultado = $stmt->execute();
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Error al activar elección manual: " . $e->getMessage());
            return false;
        }
    }
}