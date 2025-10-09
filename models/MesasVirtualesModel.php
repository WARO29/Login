<?php
namespace models;

use config\Database;
use Exception;

class MesasVirtualesModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Crear mesas virtuales para una elección específica
     * @param int $id_eleccion ID de la elección
     * @return bool True si se crearon exitosamente
     */
    public function crearMesasParaEleccion($id_eleccion) {
        $sql = "INSERT INTO mesas_virtuales (id_eleccion, nombre_mesa, grado_asignado) VALUES
                (?, 'Mesa Virtual Preescolar', 'preescolar'),
                (?, 'Mesa Virtual Grado 1°', '1'),
                (?, 'Mesa Virtual Grado 2°', '2'),
                (?, 'Mesa Virtual Grado 3°', '3'),
                (?, 'Mesa Virtual Grado 4°', '4'),
                (?, 'Mesa Virtual Grado 5°', '5'),
                (?, 'Mesa Virtual Grado 6°', '6'),
                (?, 'Mesa Virtual Grado 7°', '7'),
                (?, 'Mesa Virtual Grado 8°', '8'),
                (?, 'Mesa Virtual Grado 9°', '9'),
                (?, 'Mesa Virtual Grado 10°', '10'),
                (?, 'Mesa Virtual Grado 11°', '11')";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array_fill(0, 12, $id_eleccion));
    }

    /**
     * Obtener todas las mesas de una elección
     * @param int $id_eleccion ID de la elección
     * @return array Lista de mesas
     */
    public function getMesasPorEleccion($id_eleccion) {
        $sql = "SELECT * FROM mesas_virtuales 
                WHERE id_eleccion = ? 
                ORDER BY 
                    CASE grado_asignado 
                        WHEN 'preescolar' THEN 0
                        WHEN '1' THEN 1
                        WHEN '2' THEN 2
                        WHEN '3' THEN 3
                        WHEN '4' THEN 4
                        WHEN '5' THEN 5
                        WHEN '6' THEN 6
                        WHEN '7' THEN 7
                        WHEN '8' THEN 8
                        WHEN '9' THEN 9
                        WHEN '10' THEN 10
                        WHEN '11' THEN 11
                    END";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtener estadísticas de mesas para una elección
     * @param int $id_eleccion ID de la elección
     * @return array Estadísticas por mesa
     */
    public function getEstadisticasMesas($id_eleccion) {
        $sql = "SELECT 
                    mv.id_mesa,
                    mv.nombre_mesa,
                    mv.grado_asignado,
                    COUNT(DISTINCT pm.id_personal) as personal_asignado,
                    COUNT(DISTINCT em.id_estudiante) as estudiantes_asignados,
                    SUM(CASE WHEN em.estado_voto = 'votado' THEN 1 ELSE 0 END) as votos_emitidos,
                    ROUND(
                        SUM(CASE WHEN em.estado_voto = 'votado' THEN 1 ELSE 0 END) * 100.0 / 
                        NULLIF(COUNT(DISTINCT em.id_estudiante), 0), 2
                    ) as porcentaje_participacion,
                    CASE 
                        WHEN COUNT(DISTINCT pm.id_personal) = 4 THEN 'COMPLETA' 
                        ELSE 'INCOMPLETA' 
                    END as estado_personal
                FROM mesas_virtuales mv
                LEFT JOIN personal_mesa pm ON mv.id_mesa = pm.id_mesa
                LEFT JOIN estudiantes_mesas em ON mv.id_mesa = em.id_mesa
                WHERE mv.id_eleccion = ?
                GROUP BY mv.id_mesa
                ORDER BY 
                    CASE mv.grado_asignado 
                        WHEN 'preescolar' THEN 0
                        WHEN '1' THEN 1
                        WHEN '2' THEN 2
                        WHEN '3' THEN 3
                        WHEN '4' THEN 4
                        WHEN '5' THEN 5
                        WHEN '6' THEN 6
                        WHEN '7' THEN 7
                        WHEN '8' THEN 8
                        WHEN '9' THEN 9
                        WHEN '10' THEN 10
                        WHEN '11' THEN 11
                    END";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Asignar estudiantes a mesas por grado
     * @param int $id_eleccion ID de la elección
     * @return int Número de estudiantes asignados
     */
    public function asignarEstudiantesAMesas($id_eleccion) {
        // Limpiar asignaciones previas
        $sql = "DELETE em FROM estudiantes_mesas em 
                JOIN mesas_virtuales mv ON em.id_mesa = mv.id_mesa 
                WHERE mv.id_eleccion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        
        // Asignar estudiantes activos
        $sql = "INSERT INTO estudiantes_mesas (id_estudiante, id_mesa)
                SELECT 
                    e.id_estudiante,
                    mv.id_mesa
                FROM estudiantes e
                JOIN mesas_virtuales mv ON e.grado = mv.grado_asignado
                WHERE e.estado = 1 
                AND mv.id_eleccion = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        
        return $stmt->affected_rows;
    }

    /**
     * Agregar personal a una mesa
     * @param int $id_mesa ID de la mesa
     * @param string $tipo_personal Tipo: jurado, testigo_docente, testigo_estudiante
     * @param array $datos Datos del personal
     * @return bool True si se agregó exitosamente
     */
    public function agregarPersonalMesa($id_mesa, $tipo_personal, $datos) {
        $sql = "INSERT INTO personal_mesa 
                (id_mesa, tipo_personal, nombre_completo, documento_identidad, telefono, email, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $id_mesa,
            $tipo_personal,
            $datos['nombre_completo'],
            $datos['documento_identidad'],
            $datos['telefono'] ?? null,
            $datos['email'] ?? null,
            $datos['observaciones'] ?? null
        ]);
    }

    /**
     * Obtener personal de una mesa específica
     * @param int $id_mesa ID de la mesa
     * @return array Lista del personal
     */
    public function getPersonalMesa($id_mesa) {
        $sql = "SELECT 
                    pm.*,
                    mv.nombre_mesa,
                    mv.grado_asignado
                FROM personal_mesa pm
                JOIN mesas_virtuales mv ON pm.id_mesa = mv.id_mesa
                WHERE pm.id_mesa = ?
                ORDER BY 
                    CASE pm.tipo_personal 
                        WHEN 'jurado' THEN 1 
                        WHEN 'testigo_docente' THEN 2 
                        WHEN 'testigo_estudiante' THEN 3 
                    END";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_mesa]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Validar que una mesa tenga personal completo (4 personas)
     * @param int $id_mesa ID de la mesa
     * @return array Estado del personal
     */
    public function validarPersonalCompleto($id_mesa) {
        $sql = "SELECT 
                    SUM(CASE WHEN tipo_personal = 'jurado' THEN 1 ELSE 0 END) as jurados,
                    SUM(CASE WHEN tipo_personal = 'testigo_docente' THEN 1 ELSE 0 END) as testigos_docentes,
                    SUM(CASE WHEN tipo_personal = 'testigo_estudiante' THEN 1 ELSE 0 END) as testigos_estudiantes,
                    COUNT(*) as total_personal
                FROM personal_mesa 
                WHERE id_mesa = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_mesa]);
        $resultado = $stmt->get_result()->fetch_assoc();
        
        $completo = ($resultado['total_personal'] == 4 && 
                    $resultado['jurados'] == 1 && 
                    $resultado['testigos_docentes'] == 1 && 
                    $resultado['testigos_estudiantes'] == 2);
        
        return [
            'completo' => $completo,
            'faltantes' => [
                'jurado' => max(0, 1 - $resultado['jurados']),
                'testigo_docente' => max(0, 1 - $resultado['testigos_docentes']),
                'testigo_estudiante' => max(0, 2 - $resultado['testigos_estudiantes'])
            ],
            'total_actual' => $resultado['total_personal']
        ];
    }

    /**
     * Obtener mesa de un estudiante específico
     * @param string $id_estudiante ID del estudiante
     * @param int $id_eleccion ID de la elección
     * @return array|null Datos de la mesa
     */
    public function getMesaEstudiante($id_estudiante, $id_eleccion) {
        $sql = "SELECT 
                    mv.*,
                    em.estado_voto,
                    em.fecha_asignacion
                FROM mesas_virtuales mv
                JOIN estudiantes_mesas em ON mv.id_mesa = em.id_mesa
                WHERE em.id_estudiante = ? 
                AND mv.id_eleccion = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_estudiante, $id_eleccion]);
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * Marcar que un estudiante ha votado
     * @param string $id_estudiante ID del estudiante
     * @param int $id_eleccion ID de la elección
     * @return bool True si se actualizó exitosamente
     */
    public function marcarEstudianteVotado($id_estudiante, $id_eleccion) {
        $sql = "UPDATE estudiantes_mesas em
                JOIN mesas_virtuales mv ON em.id_mesa = mv.id_mesa
                SET em.estado_voto = 'votado'
                WHERE em.id_estudiante = ? 
                AND mv.id_eleccion = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_estudiante, $id_eleccion]);
        
        return $stmt->affected_rows > 0;
    }

    /**
     * Obtener resumen por niveles educativos
     * @param int $id_eleccion ID de la elección
     * @return array Resumen por niveles
     */
    public function getResumenPorNiveles($id_eleccion) {
        $sql = "SELECT 
                    CASE 
                        WHEN mv.grado_asignado = 'preescolar' THEN 'Preescolar'
                        WHEN mv.grado_asignado IN ('1', '2', '3', '4', '5') THEN 'Primaria'
                        WHEN mv.grado_asignado IN ('6', '7', '8', '9', '10', '11') THEN 'Bachillerato'
                    END as nivel_educativo,
                    COUNT(DISTINCT mv.id_mesa) as total_mesas,
                    COUNT(DISTINCT em.id_estudiante) as total_estudiantes,
                    SUM(CASE WHEN em.estado_voto = 'votado' THEN 1 ELSE 0 END) as total_votos,
                    ROUND(
                        SUM(CASE WHEN em.estado_voto = 'votado' THEN 1 ELSE 0 END) * 100.0 / 
                        NULLIF(COUNT(DISTINCT em.id_estudiante), 0), 2
                    ) as porcentaje_participacion
                FROM mesas_virtuales mv
                LEFT JOIN estudiantes_mesas em ON mv.id_mesa = em.id_mesa
                WHERE mv.id_eleccion = ?
                GROUP BY 
                    CASE 
                        WHEN mv.grado_asignado = 'preescolar' THEN 'Preescolar'
                        WHEN mv.grado_asignado IN ('1', '2', '3', '4', '5') THEN 'Primaria'
                        WHEN mv.grado_asignado IN ('6', '7', '8', '9', '10', '11') THEN 'Bachillerato'
                    END
                ORDER BY 
                    CASE 
                        WHEN mv.grado_asignado = 'preescolar' THEN 1
                        WHEN mv.grado_asignado IN ('1', '2', '3', '4', '5') THEN 2
                        WHEN mv.grado_asignado IN ('6', '7', '8', '9', '10', '11') THEN 3
                    END";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Eliminar personal de una mesa
     * @param int $id_personal ID del personal
     * @return bool True si se eliminó exitosamente
     */
    public function eliminarPersonalMesa($id_personal) {
        $sql = "DELETE FROM personal_mesa WHERE id_personal = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_personal]);
        
        return $stmt->affected_rows > 0;
    }

    /**
     * Cerrar todas las mesas de una elección
     * @param int $id_eleccion ID de la elección
     * @return bool True si se cerraron exitosamente
     */
    public function cerrarMesasEleccion($id_eleccion) {
        $sql = "UPDATE mesas_virtuales 
                SET estado_mesa = 'cerrada' 
                WHERE id_eleccion = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        
        return $stmt->affected_rows > 0;
    }

    /**
     * Obtener estudiantes por grado
     * @param string $grado Grado a buscar
     * @return array Lista de estudiantes del grado
     */
    public function getEstudiantesPorGrado($grado) {
        $sql = "SELECT id_estudiante, nombre as nombres, nombre as apellidos, id_estudiante as numero_documento, id_estudiante as codigo_estudiante, grado, grupo
                FROM estudiantes 
                WHERE grado = ? AND estado = 1
                ORDER BY nombre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$grado]);
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtener estudiantes asignados a una mesa específica
     * @param int $id_mesa ID de la mesa
     * @return array Lista de estudiantes asignados
     */
    public function getEstudiantesAsignadosMesa($id_mesa) {
        $sql = "SELECT em.id_estudiante, e.nombre as nombres, e.nombre as apellidos, e.id_estudiante as numero_documento, e.id_estudiante as codigo_estudiante, e.grado, e.grupo
                FROM estudiantes_mesas em
                INNER JOIN estudiantes e ON em.id_estudiante = e.id_estudiante
                WHERE em.id_mesa = ? AND e.estado = 1
                ORDER BY e.nombre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_mesa]);
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Asignar estudiantes específicos a una mesa
     * @param int $id_mesa ID de la mesa
     * @param array $estudiantes_ids Array de IDs de estudiantes
     * @return array Resultado de la operación
     */
    public function asignarEstudiantesEspecificosMesa($id_mesa, $estudiantes_ids) {
        try {
            // Iniciar transacción
            $this->db->autocommit(false);
            
            // Limpiar asignaciones previas de esta mesa
            $sqlLimpiar = "DELETE FROM estudiantes_mesas WHERE id_mesa = ?";
            $stmt = $this->db->prepare($sqlLimpiar);
            $stmt->execute([$id_mesa]);
            
            // Asignar nuevos estudiantes
            $asignados = 0;
            if (!empty($estudiantes_ids)) {
                $sqlAsignar = "INSERT INTO estudiantes_mesas (id_mesa, id_estudiante) VALUES (?, ?)";
                $stmtAsignar = $this->db->prepare($sqlAsignar);
                
                foreach ($estudiantes_ids as $id_estudiante) {
                    $id_estudiante = (int)$id_estudiante;
                    if ($id_estudiante > 0) {
                        $stmtAsignar->execute([$id_mesa, $id_estudiante]);
                        $asignados++;
                    }
                }
            }
            
            // Confirmar transacción
            $this->db->commit();
            $this->db->autocommit(true);
            
            return [
                'success' => true,
                'asignados' => $asignados,
                'mensaje' => "Se asignaron $asignados estudiantes a la mesa"
            ];
            
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollback();
            $this->db->autocommit(true);
            
            return [
                'success' => false,
                'asignados' => 0,
                'mensaje' => 'Error al asignar estudiantes: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener datos de un docente por ID
     * @param int $id_docente ID del docente
     * @return array|null Datos del docente
     */
    public function getDatosDocente($id_docente) {
        $sql = "SELECT nombres, apellidos, numero_documento, telefono, email, area_especialidad 
                FROM docentes 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_docente]);
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Obtener datos de un estudiante por ID
     * @param int $id_estudiante ID del estudiante
     * @return array|null Datos del estudiante
     */
    public function getDatosEstudiante($id_estudiante) {
        $sql = "SELECT nombre as nombres, nombre as apellidos, id_estudiante as numero_documento, grado, id_estudiante as codigo_estudiante, grupo
                FROM estudiantes 
                WHERE id_estudiante = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_estudiante]);
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Obtener información de una mesa específica por ID
     * @param int $id_mesa ID de la mesa
     * @return array|null Información de la mesa
     */
    public function getMesaPorId($id_mesa) {
        $sql = "SELECT mv.id_mesa, mv.id_eleccion, mv.nombre_mesa, mv.grado_asignado, mv.estado_mesa,
                       ce.nombre_eleccion, ce.fecha_cierre, ce.estado as estado_eleccion
                FROM mesas_virtuales mv
                INNER JOIN configuracion_elecciones ce ON mv.id_eleccion = ce.id
                WHERE mv.id_mesa = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_mesa]);
        
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Registrar nuevo estudiante en el sistema
     */
    public function registrarNuevoEstudiante($datos) {
        try {
            // Verificar si ya existe un estudiante con el mismo documento
            $sql_check = "SELECT id_estudiante FROM estudiantes WHERE id_estudiante = ?";
            $stmt_check = $this->db->prepare($sql_check);
            $stmt_check->execute([$datos['numero_documento']]);
            
            if ($stmt_check->get_result()->num_rows > 0) {
                return [
                    'success' => false,
                    'mensaje' => 'Ya existe un estudiante con este número de documento.'
                ];
            }

            // Preparar nombre completo
            $nombre_completo = trim($datos['nombres'] . ' ' . $datos['apellidos']);
            
            // Insertar nuevo estudiante con estructura mínima
            $sql = "INSERT INTO estudiantes (id_estudiante, nombre, grado, grupo, estado, correo) 
                    VALUES (?, ?, ?, ?, 1, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $datos['numero_documento'], // id_estudiante
                $nombre_completo, // nombre completo
                $datos['grado'], // grado
                $datos['grupo'] ?? 'A', // grupo
                $datos['email'] ?? '' // correo
            ]);

            if ($result && $stmt->affected_rows > 0) {
                return [
                    'success' => true,
                    'id_estudiante' => $datos['numero_documento'],
                    'mensaje' => 'Estudiante registrado exitosamente.'
                ];
            } else {
                return [
                    'success' => false,
                    'mensaje' => 'No se pudo registrar el estudiante. Error SQL: ' . $this->db->error
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'mensaje' => 'Error al registrar estudiante: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generar código único para estudiante
     */
    private function generarCodigoEstudiante($grado) {
        $prefijo = ($grado === 'Preescolar') ? 'PRE' : 'G' . str_pad($grado, 2, '0', STR_PAD_LEFT);
        $año = date('Y');
        
        // Buscar el último número para este grado y año (usando id_estudiante como código)
        $sql = "SELECT id_estudiante FROM estudiantes 
                WHERE id_estudiante LIKE ? 
                ORDER BY id_estudiante DESC LIMIT 1";
        
        $patron = $prefijo . $año . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patron]);
        $resultado = $stmt->get_result()->fetch_assoc();
        
        if ($resultado) {
            // Extraer el número secuencial y incrementarlo
            $ultimo_codigo = $resultado['id_estudiante'];
            $numero = intval(substr($ultimo_codigo, -3)) + 1;
        } else {
            $numero = 1;
        }
        
        return $prefijo . $año . str_pad($numero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Asignar un estudiante específico a una mesa
     */
    public function asignarEstudianteMesa($id_mesa, $id_estudiante) {
        try {
            // Verificar si el estudiante ya está asignado a esta mesa
            $sql_check = "SELECT id_asignacion FROM estudiantes_mesas WHERE id_mesa = ? AND id_estudiante = ?";
            $stmt_check = $this->db->prepare($sql_check);
            $stmt_check->execute([$id_mesa, $id_estudiante]);
            
            if ($stmt_check->get_result()->num_rows > 0) {
                return [
                    'success' => false,
                    'mensaje' => 'El estudiante ya está asignado a esta mesa.'
                ];
            }

            // Asignar estudiante a la mesa
            $sql = "INSERT INTO estudiantes_mesas (id_mesa, id_estudiante, fecha_asignacion) VALUES (?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_mesa, $id_estudiante]);

            if ($stmt->affected_rows > 0) {
                return [
                    'success' => true,
                    'mensaje' => 'Estudiante asignado exitosamente a la mesa.'
                ];
            } else {
                return [
                    'success' => false,
                    'mensaje' => 'No se pudo asignar el estudiante a la mesa.'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'mensaje' => 'Error al asignar estudiante: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Desasignar un estudiante de una mesa
     */
    public function desasignarEstudianteMesa($id_mesa, $id_estudiante) {
        try {
            $sql = "DELETE FROM estudiantes_mesas WHERE id_mesa = ? AND id_estudiante = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_mesa, $id_estudiante]);

            if ($stmt->affected_rows > 0) {
                return [
                    'success' => true,
                    'mensaje' => 'Estudiante desasignado exitosamente de la mesa.'
                ];
            } else {
                return [
                    'success' => false,
                    'mensaje' => 'No se encontró la asignación del estudiante a esta mesa.'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'mensaje' => 'Error al desasignar estudiante: ' . $e->getMessage()
            ];
        }
    }
}
?>
