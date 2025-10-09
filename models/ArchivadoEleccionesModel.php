<?php
namespace models;

use config\Database;
use PDO;
use Exception;

class ArchivadoEleccionesModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Archivar automáticamente elecciones cerradas
     */
    public function archivarEleccionesCerradas() {
        try {
            // Obtener elecciones que han cerrado pero no están archivadas
            $sql = "SELECT id, nombre_eleccion, fecha_cierre, estado 
                    FROM configuracion_elecciones 
                    WHERE fecha_cierre < NOW() 
                    AND estado != 'archivada'
                    AND id NOT IN (SELECT DISTINCT id_eleccion FROM historico_elecciones WHERE id_eleccion IS NOT NULL)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $eleccionesCerradas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $archivadas = 0;
            
            foreach ($eleccionesCerradas as $eleccion) {
                if ($this->archivarEleccion($eleccion['id'])) {
                    $archivadas++;
                }
            }
            
            return [
                'success' => true,
                'elecciones_encontradas' => count($eleccionesCerradas),
                'elecciones_archivadas' => $archivadas,
                'mensaje' => "Se archivaron $archivadas de " . count($eleccionesCerradas) . " elecciones cerradas"
            ];
            
        } catch (Exception $e) {
            error_log("Error al archivar elecciones cerradas: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error al archivar elecciones: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Archivar una elección específica
     */
    public function archivarEleccion($id_eleccion) {
        try {
            $this->db->autocommit(false);
            
            // 1. Obtener información de la elección
            $eleccion = $this->obtenerDatosEleccion($id_eleccion);
            if (!$eleccion) {
                throw new Exception("Elección no encontrada");
            }
            
            // 2. Archivar estadísticas de estudiantes
            $this->archivarEstadisticasEstudiantes($id_eleccion, $eleccion);
            
            // 3. Archivar estadísticas de docentes
            $this->archivarEstadisticasDocentes($id_eleccion, $eleccion);
            
            // 4. Archivar estadísticas de administrativos
            $this->archivarEstadisticasAdministrativos($id_eleccion, $eleccion);
            
            // 5. Archivar datos de mesas virtuales
            $this->archivarMesasVirtuales($id_eleccion, $eleccion);
            
            // 6. Actualizar estado de la elección
            $this->actualizarEstadoEleccion($id_eleccion, 'archivada');
            
            $this->db->commit();
            $this->db->autocommit(true);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->db->autocommit(true);
            error_log("Error al archivar elección $id_eleccion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener datos básicos de la elección
     */
    private function obtenerDatosEleccion($id_eleccion) {
        $sql = "SELECT * FROM configuracion_elecciones WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $result = $stmt->get_result()->fetch_assoc();
        return $result;
    }

    /**
     * Archivar estadísticas de estudiantes
     */
    private function archivarEstadisticasEstudiantes($id_eleccion, $eleccion) {
        // Contar votos de estudiantes
        $sql = "SELECT 
                    COUNT(*) as total_votos,
                    COUNT(DISTINCT id_estudiante) as estudiantes_votaron,
                    (SELECT COUNT(*) FROM estudiantes WHERE estado = 1) as total_estudiantes
                FROM votos 
                WHERE id_eleccion = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $stats = $stmt->get_result()->fetch_assoc();
        
        // Insertar en histórico usando las columnas correctas
        $sqlInsert = "INSERT INTO historico_elecciones 
                      (id_eleccion, nombre_eleccion, tipo_votacion, fecha_inicio, fecha_cierre, 
                       total_estudiantes_habilitados, total_votos_estudiantes, porcentaje_participacion_estudiantes) 
                      VALUES (?, ?, 'estudiantes', ?, ?, ?, ?, ?)";
        
        $participacion = $stats['total_estudiantes'] > 0 ? 
                        ($stats['estudiantes_votaron'] / $stats['total_estudiantes']) * 100 : 0;
        
        $stmt = $this->db->prepare($sqlInsert);
        $stmt->execute([
            $id_eleccion,
            $eleccion['nombre_eleccion'],
            $eleccion['fecha_inicio'],
            $eleccion['fecha_cierre'],
            $stats['total_estudiantes'],
            $stats['total_votos'],
            round($participacion, 2)
        ]);
    }

    /**
     * Archivar estadísticas de docentes
     */
    private function archivarEstadisticasDocentes($id_eleccion, $eleccion) {
        // Verificar si hay votos de docentes
        $sqlCheck = "SELECT COUNT(*) as count FROM votos_docentes WHERE id_eleccion = ?";
        $stmt = $this->db->prepare($sqlCheck);
        $stmt->execute([$id_eleccion]);
        $hasVotes = $stmt->get_result()->fetch_assoc()['count'] > 0;
        
        if ($hasVotes) {
            $sql = "SELECT 
                        COUNT(*) as total_votos,
                        COUNT(DISTINCT id_docente) as docentes_votaron,
                        (SELECT COUNT(*) FROM docentes WHERE estado = 'activo') as total_docentes
                    FROM votos_docentes 
                    WHERE id_eleccion = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_eleccion]);
            $stats = $stmt->get_result()->fetch_assoc();
            
            $participacion = $stats['total_docentes'] > 0 ? 
                            ($stats['docentes_votaron'] / $stats['total_docentes']) * 100 : 0;
            
            $sqlInsert = "INSERT INTO historico_elecciones 
                          (id_eleccion, nombre_eleccion, tipo_votacion, fecha_inicio, fecha_cierre, 
                           total_docentes_habilitados, total_votos_docentes, porcentaje_participacion_docentes) 
                          VALUES (?, ?, 'docentes', ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sqlInsert);
            $stmt->execute([
                $id_eleccion,
                $eleccion['nombre_eleccion'],
                $eleccion['fecha_inicio'],
                $eleccion['fecha_cierre'],
                $stats['total_docentes'],
                $stats['total_votos'],
                round($participacion, 2)
            ]);
        }
    }

    /**
     * Archivar estadísticas de administrativos
     */
    private function archivarEstadisticasAdministrativos($id_eleccion, $eleccion) {
        // Verificar si hay votos de administrativos
        $sqlCheck = "SELECT COUNT(*) as count FROM votos_administrativos WHERE id_eleccion = ?";
        $stmt = $this->db->prepare($sqlCheck);
        $stmt->execute([$id_eleccion]);
        $hasVotes = $stmt->get_result()->fetch_assoc()['count'] > 0;
        
        if ($hasVotes) {
            $sql = "SELECT 
                        COUNT(*) as total_votos,
                        COUNT(DISTINCT id_administrativo) as administrativos_votaron,
                        (SELECT COUNT(*) FROM administrativos WHERE estado = 'Activo') as total_administrativos
                    FROM votos_administrativos 
                    WHERE id_eleccion = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_eleccion]);
            $stats = $stmt->get_result()->fetch_assoc();
            
            $participacion = $stats['total_administrativos'] > 0 ? 
                            ($stats['administrativos_votaron'] / $stats['total_administrativos']) * 100 : 0;
            
            $sqlInsert = "INSERT INTO historico_elecciones 
                          (id_eleccion, nombre_eleccion, tipo_votacion, fecha_inicio, fecha_cierre, 
                           total_administrativos_habilitados, total_votos_administrativos, porcentaje_participacion_administrativos) 
                          VALUES (?, ?, 'administrativos', ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sqlInsert);
            $stmt->execute([
                $id_eleccion,
                $eleccion['nombre_eleccion'],
                $eleccion['fecha_inicio'],
                $eleccion['fecha_cierre'],
                $stats['total_administrativos'],
                $stats['total_votos'],
                round($participacion, 2)
            ]);
        }
    }

    /**
     * Archivar datos de mesas virtuales
     */
    private function archivarMesasVirtuales($id_eleccion, $eleccion) {
        // Verificar si hay mesas virtuales
        $sqlCheck = "SELECT COUNT(*) as count FROM mesas_virtuales WHERE id_eleccion = ?";
        $stmt = $this->db->prepare($sqlCheck);
        $stmt->execute([$id_eleccion]);
        $hasMesas = $stmt->get_result()->fetch_assoc()['count'] > 0;
        
        if ($hasMesas) {
            $sql = "SELECT 
                        COUNT(DISTINCT mv.id) as total_mesas,
                        COUNT(DISTINCT em.id_estudiante) as estudiantes_asignados
                    FROM mesas_virtuales mv
                    LEFT JOIN estudiantes_mesas em ON mv.id = em.id_mesa
                    WHERE mv.id_eleccion = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_eleccion]);
            $stats = $stmt->get_result()->fetch_assoc();
            
            $sqlInsert = "INSERT INTO historico_elecciones 
                          (id_eleccion, nombre_eleccion, tipo_votacion, fecha_inicio, fecha_cierre, 
                           datos_adicionales) 
                          VALUES (?, ?, 'mesas_virtuales', ?, ?, ?)";
            
            $datosAdicionales = json_encode([
                'total_mesas' => $stats['total_mesas'],
                'estudiantes_asignados' => $stats['estudiantes_asignados']
            ]);
            
            $stmt = $this->db->prepare($sqlInsert);
            $stmt->execute([
                $id_eleccion,
                $eleccion['nombre_eleccion'],
                $eleccion['fecha_inicio'],
                $eleccion['fecha_cierre'],
                $datosAdicionales
            ]);
        }
    }

    /**
     * Actualizar estado de la elección
     */
    private function actualizarEstadoEleccion($id_eleccion, $estado) {
        $sql = "UPDATE configuracion_elecciones SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$estado, $id_eleccion]);
    }

    /**
     * Verificar y procesar elecciones que deben cerrarse
     */
    public function procesarCierreAutomatico() {
        try {
            // Buscar elecciones que deberían estar cerradas pero no lo están
            $sql = "SELECT id, nombre_eleccion, estado 
                    FROM configuracion_elecciones 
                    WHERE fecha_cierre < NOW() 
                    AND estado IN ('activa', 'programada')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $elecciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $procesadas = 0;
            
            foreach ($elecciones as $eleccion) {
                // Cambiar estado a cerrada
                $this->actualizarEstadoEleccion($eleccion['id'], 'cerrada');
                $procesadas++;
            }
            
            return [
                'success' => true,
                'elecciones_cerradas' => $procesadas,
                'mensaje' => "Se cerraron automáticamente $procesadas elecciones"
            ];
            
        } catch (Exception $e) {
            error_log("Error en cierre automático: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error en cierre automático: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Limpiar datos de dashboard para elecciones archivadas
     */
    public function limpiarDashboardEleccionesArchivadas() {
        try {
            // Esta función se puede usar para limpiar caches o datos temporales
            // Por ahora, solo retornamos éxito ya que el dashboard se filtra por estado
            
            return [
                'success' => true,
                'mensaje' => 'Dashboard actualizado para mostrar solo elecciones activas'
            ];
            
        } catch (Exception $e) {
            error_log("Error al limpiar dashboard: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error al limpiar dashboard: ' . $e->getMessage()
            ];
        }
    }
}
?>
