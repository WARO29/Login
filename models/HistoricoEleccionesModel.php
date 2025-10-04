<?php
namespace models;

use config\Database;

class HistoricoEleccionesModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Crear registro histórico cuando una elección finaliza
     * @param int $id_eleccion ID de la elección
     * @return bool True si se creó exitosamente
     */
    public function crearHistoricoEleccion($id_eleccion) {
        // Obtener datos de la elección
        $sql = "SELECT * FROM configuracion_elecciones WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $eleccion = $stmt->get_result()->fetch_assoc();
        
        if (!$eleccion) {
            return false;
        }

        // Calcular estadísticas
        $estadisticas = $this->calcularEstadisticasEleccion($id_eleccion);
        
        // Obtener ganadores
        $ganadores = $this->obtenerGanadoresEleccion($id_eleccion);

        // Insertar en histórico
        $sql = "INSERT INTO historico_elecciones (
                    id_eleccion, nombre_eleccion, descripcion, fecha_inicio, fecha_cierre,
                    total_estudiantes_habilitados, total_docentes_habilitados, total_administrativos_habilitados,
                    total_votos_estudiantes, total_votos_docentes, total_votos_administrativos,
                    porcentaje_participacion_estudiantes, porcentaje_participacion_docentes, porcentaje_participacion_administrativos,
                    ganador_estudiante, ganador_docente, tipos_votacion, configuracion_adicional
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $id_eleccion,
            $eleccion['nombre_eleccion'],
            $eleccion['descripcion'],
            $eleccion['fecha_inicio'],
            $eleccion['fecha_cierre'],
            $estadisticas['total_estudiantes_habilitados'],
            $estadisticas['total_docentes_habilitados'],
            $estadisticas['total_administrativos_habilitados'],
            $estadisticas['total_votos_estudiantes'],
            $estadisticas['total_votos_docentes'],
            $estadisticas['total_votos_administrativos'],
            $estadisticas['porcentaje_participacion_estudiantes'],
            $estadisticas['porcentaje_participacion_docentes'],
            $estadisticas['porcentaje_participacion_administrativos'],
            $ganadores['ganador_estudiante'],
            $ganadores['ganador_docente'],
            $eleccion['tipos_votacion'],
            $eleccion['configuracion_adicional']
        ]);
    }

    /**
     * Calcular estadísticas de una elección
     * @param int $id_eleccion ID de la elección
     * @return array Estadísticas calculadas
     */
    private function calcularEstadisticasEleccion($id_eleccion) {
        // Total de estudiantes habilitados
        $sql = "SELECT COUNT(*) as total FROM estudiantes WHERE estado = 1";
        $result = $this->db->query($sql);
        $total_estudiantes = $result->fetch_assoc()['total'];

        // Total de docentes habilitados
        $sql = "SELECT COUNT(*) as total FROM docentes";
        $result = $this->db->query($sql);
        $total_docentes = $result->fetch_assoc()['total'];

        // Total de administrativos habilitados
        $sql = "SELECT COUNT(*) as total FROM administrativos";
        $result = $this->db->query($sql);
        $total_administrativos = $result->fetch_assoc()['total'];

        // Votos de estudiantes
        $sql = "SELECT COUNT(*) as total FROM votos WHERE id_eleccion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $votos_estudiantes = $stmt->get_result()->fetch_assoc()['total'];

        // Votos de docentes
        $sql = "SELECT COUNT(*) as total FROM votos_docentes WHERE id_eleccion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $votos_docentes = $stmt->get_result()->fetch_assoc()['total'];

        // Votos de administrativos
        $sql = "SELECT COUNT(*) as total FROM votos_administrativos WHERE id_eleccion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $votos_administrativos = $stmt->get_result()->fetch_assoc()['total'];

        return [
            'total_estudiantes_habilitados' => $total_estudiantes,
            'total_docentes_habilitados' => $total_docentes,
            'total_administrativos_habilitados' => $total_administrativos,
            'total_votos_estudiantes' => $votos_estudiantes,
            'total_votos_docentes' => $votos_docentes,
            'total_votos_administrativos' => $votos_administrativos,
            'porcentaje_participacion_estudiantes' => $total_estudiantes > 0 ? round(($votos_estudiantes * 100.0) / $total_estudiantes, 2) : 0,
            'porcentaje_participacion_docentes' => $total_docentes > 0 ? round(($votos_docentes * 100.0) / $total_docentes, 2) : 0,
            'porcentaje_participacion_administrativos' => $total_administrativos > 0 ? round(($votos_administrativos * 100.0) / $total_administrativos, 2) : 0
        ];
    }

    /**
     * Obtener ganadores de una elección
     * @param int $id_eleccion ID de la elección
     * @return array Ganadores por categoría
     */
    private function obtenerGanadoresEleccion($id_eleccion) {
        $ganadores = [
            'ganador_estudiante' => null,
            'ganador_docente' => null
        ];

        // Ganador estudiante (representante estudiantil)
        $sql = "SELECT c.nombre, COUNT(v.id_voto) as total_votos
                FROM candidatos c
                LEFT JOIN votos v ON c.id_candidato = v.id_candidato AND v.id_eleccion = ?
                WHERE c.tipo_candidato = 'estudiante'
                GROUP BY c.id_candidato
                ORDER BY total_votos DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $ganador = $result->fetch_assoc();
            $ganadores['ganador_estudiante'] = $ganador['nombre'] . ' (' . $ganador['total_votos'] . ' votos)';
        }

        // Ganador docente (representante docente)
        $sql = "SELECT rd.nombre, COUNT(vd.id_voto) as total_votos
                FROM representante_docente rd
                LEFT JOIN votos_docentes vd ON rd.id_representante = vd.id_candidato_docente AND vd.id_eleccion = ?
                GROUP BY rd.id_representante
                ORDER BY total_votos DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $ganador = $result->fetch_assoc();
            $ganadores['ganador_docente'] = $ganador['nombre'] . ' (' . $ganador['total_votos'] . ' votos)';
        }

        return $ganadores;
    }

    /**
     * Obtener todo el histórico de elecciones
     * @return array Lista de elecciones históricas
     */
    public function getHistoricoCompleto() {
        $sql = "SELECT * FROM historico_elecciones 
                ORDER BY fecha_finalizacion DESC";
        
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtener histórico de una elección específica
     * @param int $id_eleccion ID de la elección
     * @return array|null Datos históricos
     */
    public function getHistoricoPorEleccion($id_eleccion) {
        $sql = "SELECT * FROM historico_elecciones WHERE id_eleccion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * Obtener resultados detallados de una elección histórica
     * @param int $id_eleccion ID de la elección
     * @return array Resultados detallados
     */
    public function getResultadosDetallados($id_eleccion) {
        $resultados = [
            'info_eleccion' => $this->getHistoricoPorEleccion($id_eleccion),
            'resultados_estudiantes' => $this->getResultadosEstudiantes($id_eleccion),
            'resultados_docentes' => $this->getResultadosDocentes($id_eleccion),
            'estadisticas_mesas' => $this->getEstadisticasMesasHistorico($id_eleccion),
            'participacion_por_grado' => $this->getParticipacionPorGrado($id_eleccion)
        ];

        return $resultados;
    }

    /**
     * Obtener resultados de candidatos estudiantiles
     * @param int $id_eleccion ID de la elección
     * @return array Resultados de estudiantes
     */
    private function getResultadosEstudiantes($id_eleccion) {
        $sql = "SELECT 
                    c.nombre,
                    c.grado,
                    c.grupo,
                    c.propuestas,
                    COUNT(v.id_voto) as total_votos,
                    ROUND(COUNT(v.id_voto) * 100.0 / 
                          (SELECT COUNT(*) FROM votos WHERE id_eleccion = ? AND voto_blanco = 0), 2) as porcentaje
                FROM candidatos c
                LEFT JOIN votos v ON c.id_candidato = v.id_candidato AND v.id_eleccion = ?
                WHERE c.tipo_candidato = 'estudiante'
                GROUP BY c.id_candidato
                ORDER BY total_votos DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion, $id_eleccion]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtener resultados de representantes docentes
     * @param int $id_eleccion ID de la elección
     * @return array Resultados de docentes
     */
    private function getResultadosDocentes($id_eleccion) {
        $sql = "SELECT 
                    rd.nombre,
                    rd.area_especialidad,
                    rd.propuestas,
                    COUNT(vd.id_voto) as total_votos,
                    ROUND(COUNT(vd.id_voto) * 100.0 / 
                          (SELECT COUNT(*) FROM votos_docentes WHERE id_eleccion = ? AND voto_blanco_docente = 0), 2) as porcentaje
                FROM representante_docente rd
                LEFT JOIN votos_docentes vd ON rd.id_representante = vd.id_candidato_docente AND vd.id_eleccion = ?
                GROUP BY rd.id_representante
                ORDER BY total_votos DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion, $id_eleccion]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtener estadísticas de mesas para elección histórica
     * @param int $id_eleccion ID de la elección
     * @return array Estadísticas de mesas
     */
    private function getEstadisticasMesasHistorico($id_eleccion) {
        $sql = "SELECT 
                    mv.nombre_mesa,
                    mv.grado_asignado,
                    COUNT(em.id_estudiante) as estudiantes_asignados,
                    SUM(CASE WHEN em.estado_voto = 'votado' THEN 1 ELSE 0 END) as votos_emitidos,
                    ROUND(
                        SUM(CASE WHEN em.estado_voto = 'votado' THEN 1 ELSE 0 END) * 100.0 / 
                        NULLIF(COUNT(em.id_estudiante), 0), 2
                    ) as porcentaje_participacion
                FROM mesas_virtuales mv
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
     * Obtener participación por grado
     * @param int $id_eleccion ID de la elección
     * @return array Participación por grado
     */
    private function getParticipacionPorGrado($id_eleccion) {
        $sql = "SELECT 
                    mv.grado_asignado,
                    COUNT(em.id_estudiante) as total_estudiantes,
                    SUM(CASE WHEN em.estado_voto = 'votado' THEN 1 ELSE 0 END) as total_votos,
                    ROUND(
                        SUM(CASE WHEN em.estado_voto = 'votado' THEN 1 ELSE 0 END) * 100.0 / 
                        NULLIF(COUNT(em.id_estudiante), 0), 2
                    ) as porcentaje_participacion
                FROM mesas_virtuales mv
                LEFT JOIN estudiantes_mesas em ON mv.id_mesa = em.id_mesa
                WHERE mv.id_eleccion = ?
                GROUP BY mv.grado_asignado
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
     * Obtener estadísticas generales del histórico
     * @return array Estadísticas generales
     */
    public function getEstadisticasGenerales() {
        $sql = "SELECT 
                    COUNT(*) as total_elecciones,
                    AVG(porcentaje_participacion_estudiantes) as promedio_participacion_estudiantes,
                    AVG(porcentaje_participacion_docentes) as promedio_participacion_docentes,
                    SUM(total_votos_estudiantes) as total_votos_historico_estudiantes,
                    SUM(total_votos_docentes) as total_votos_historico_docentes,
                    MAX(fecha_finalizacion) as ultima_eleccion
                FROM historico_elecciones";
        
        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }

    /**
     * Eliminar registro histórico (solo para administradores)
     * @param int $id_historico ID del registro histórico
     * @return bool True si se eliminó exitosamente
     */
    public function eliminarHistorico($id_historico) {
        $sql = "DELETE FROM historico_elecciones WHERE id_historico = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_historico]);
        
        return $stmt->affected_rows > 0;
    }

    /**
     * Finalizar elección y crear histórico automáticamente
     * @param int $id_eleccion ID de la elección
     * @return bool True si se finalizó exitosamente
     */
    public function finalizarEleccionYCrearHistorico($id_eleccion) {
        // Crear histórico
        if (!$this->crearHistoricoEleccion($id_eleccion)) {
            return false;
        }

        // Cambiar estado de la elección a cerrada
        $sql = "UPDATE configuracion_elecciones SET estado = 'cerrada' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);

        // Cerrar mesas virtuales
        $mesasModel = new MesasVirtualesModel();
        $mesasModel->cerrarMesasEleccion($id_eleccion);

        return true;
    }
}
?>
