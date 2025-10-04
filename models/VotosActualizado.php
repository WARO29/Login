<?php
namespace models;

use config\Database;
use models\DocenteModel;
use models\RepresentanteDocenteModel;
use models\MesasVirtualesModel;

class VotosActualizado {
    private $conn;
    private $table_name = "votos";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function __destruct() {
        if ($this->conn) {
            $database = new Database();
            $database->closeConnection();
        }
    }

    /**
     * Obtener ID de la elección activa actual
     * @return int|null ID de la elección activa
     */
    private function getEleccionActivaId() {
        $sql = "SELECT id FROM configuracion_elecciones 
                WHERE estado = 'activa' 
                AND fecha_inicio <= NOW() 
                AND fecha_cierre > NOW() 
                ORDER BY fecha_inicio ASC 
                LIMIT 1";
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        return null;
    }

    /**
     * Verifica si un estudiante ya ha votado en una elección específica
     * @param string $id_estudiante ID del estudiante
     * @param int $id_eleccion ID de la elección (opcional, usa la activa si no se especifica)
     * @return bool Verdadero si ya ha votado, falso en caso contrario
     */
    public function haVotadoEstudiante($id_estudiante, $id_eleccion = null) {
        try {
            if ($id_eleccion === null) {
                $id_eleccion = $this->getEleccionActivaId();
            }
            
            if (!$id_eleccion) {
                return false;
            }

            $query = "SELECT COUNT(*) as total FROM votos 
                      WHERE id_estudiante = ? AND id_eleccion = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $id_estudiante, $id_eleccion);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                return $row['total'] > 0;
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en haVotadoEstudiante: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un voto de estudiante en la base de datos
     * @param string $id_estudiante ID del estudiante que vota
     * @param int $id_candidato ID del candidato por el que se vota
     * @param bool $voto_blanco Indica si es un voto en blanco
     * @param int $id_eleccion ID de la elección (opcional, usa la activa si no se especifica)
     * @return bool Verdadero si el voto se registró correctamente, falso en caso contrario
     */
    public function registrarVotoEstudiante($id_estudiante, $id_candidato = null, $voto_blanco = false, $id_eleccion = null) {
        try {
            if ($id_eleccion === null) {
                $id_eleccion = $this->getEleccionActivaId();
            }
            
            if (!$id_eleccion) {
                error_log("No hay elección activa para registrar voto");
                return false;
            }

            // Verificar si el estudiante ya ha votado en esta elección
            if ($this->haVotadoEstudiante($id_estudiante, $id_eleccion)) {
                error_log("El estudiante $id_estudiante ya ha votado en la elección $id_eleccion");
                return false;
            }
            
            // Marcar estudiante como votado en mesa virtual
            $mesasModel = new MesasVirtualesModel();
            $mesasModel->marcarEstudianteVotado($id_estudiante, $id_eleccion);
            
            $query = "INSERT INTO votos (id_estudiante, id_candidato, voto_blanco, id_eleccion, fecha_voto) 
                      VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            $voto_blanco_int = $voto_blanco ? 1 : 0;
            $stmt->bind_param("siii", $id_estudiante, $id_candidato, $voto_blanco_int, $id_eleccion);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error en registrarVotoEstudiante: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un docente ya ha votado en una elección específica
     * @param string $id_docente ID del docente
     * @param int $id_eleccion ID de la elección (opcional, usa la activa si no se especifica)
     * @return bool Verdadero si ya ha votado, falso en caso contrario
     */
    public function haVotadoDocente($id_docente, $id_eleccion = null) {
        try {
            if ($id_eleccion === null) {
                $id_eleccion = $this->getEleccionActivaId();
            }
            
            if (!$id_eleccion) {
                return false;
            }

            $query = "SELECT COUNT(*) as total FROM votos_docentes 
                      WHERE id_docente = ? AND id_eleccion = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $id_docente, $id_eleccion);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                return $row['total'] > 0;
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en haVotadoDocente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un voto de docente en la base de datos
     * @param string $id_docente ID del docente que vota
     * @param int $id_candidato_docente ID del representante docente por el que se vota
     * @param bool $voto_blanco_docente Indica si es un voto en blanco
     * @param int $id_eleccion ID de la elección (opcional, usa la activa si no se especifica)
     * @return bool Verdadero si el voto se registró correctamente, falso en caso contrario
     */
    public function registrarVotoDocente($id_docente, $id_candidato_docente = null, $voto_blanco_docente = false, $id_eleccion = null) {
        try {
            if ($id_eleccion === null) {
                $id_eleccion = $this->getEleccionActivaId();
            }
            
            if (!$id_eleccion) {
                error_log("No hay elección activa para registrar voto docente");
                return false;
            }

            // Verificar si el docente ya ha votado en esta elección
            if ($this->haVotadoDocente($id_docente, $id_eleccion)) {
                error_log("El docente $id_docente ya ha votado en la elección $id_eleccion");
                return false;
            }
            
            $query = "INSERT INTO votos_docentes (id_docente, id_candidato_docente, voto_blanco_docente, id_eleccion, fecha_voto) 
                      VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            $voto_blanco_int = $voto_blanco_docente ? 1 : 0;
            $stmt->bind_param("siii", $id_docente, $id_candidato_docente, $voto_blanco_int, $id_eleccion);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error en registrarVotoDocente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un administrativo ya ha votado en una elección específica
     * @param string $cedula_administrativo Cédula del administrativo
     * @param int $id_eleccion ID de la elección (opcional, usa la activa si no se especifica)
     * @return bool Verdadero si ya ha votado, falso en caso contrario
     */
    public function haVotadoAdministrativo($cedula_administrativo, $id_eleccion = null) {
        try {
            if ($id_eleccion === null) {
                $id_eleccion = $this->getEleccionActivaId();
            }
            
            if (!$id_eleccion) {
                return false;
            }

            $query = "SELECT COUNT(*) as total FROM votos_administrativos 
                      WHERE cedula_administrativo = ? AND id_eleccion = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $cedula_administrativo, $id_eleccion);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                return $row['total'] > 0;
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en haVotadoAdministrativo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un voto de administrativo en la base de datos
     * @param string $cedula_administrativo Cédula del administrativo que vota
     * @param int $id_candidato_docente ID del representante docente por el que se vota
     * @param bool $voto_blanco_docente Indica si es un voto en blanco
     * @param int $id_eleccion ID de la elección (opcional, usa la activa si no se especifica)
     * @return bool Verdadero si el voto se registró correctamente, falso en caso contrario
     */
    public function registrarVotoAdministrativo($cedula_administrativo, $id_candidato_docente = null, $voto_blanco_docente = false, $id_eleccion = null) {
        try {
            if ($id_eleccion === null) {
                $id_eleccion = $this->getEleccionActivaId();
            }
            
            if (!$id_eleccion) {
                error_log("No hay elección activa para registrar voto administrativo");
                return false;
            }

            // Verificar si el administrativo ya ha votado en esta elección
            if ($this->haVotadoAdministrativo($cedula_administrativo, $id_eleccion)) {
                error_log("El administrativo $cedula_administrativo ya ha votado en la elección $id_eleccion");
                return false;
            }
            
            $query = "INSERT INTO votos_administrativos (cedula_administrativo, id_candidato_docente, voto_blanco_docente, id_eleccion, fecha_voto) 
                      VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            $voto_blanco_int = $voto_blanco_docente ? 1 : 0;
            $stmt->bind_param("siii", $cedula_administrativo, $id_candidato_docente, $voto_blanco_int, $id_eleccion);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error en registrarVotoAdministrativo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener resultados de candidatos estudiantiles para una elección específica
     * @param int $id_eleccion ID de la elección (opcional, usa la activa si no se especifica)
     * @return array Resultados de candidatos estudiantiles
     */
    public function getResultadosEstudiantes($id_eleccion = null) {
        try {
            if ($id_eleccion === null) {
                $id_eleccion = $this->getEleccionActivaId();
            }
            
            if (!$id_eleccion) {
                return [];
            }

            $query = "SELECT 
                        c.id_candidato,
                        c.nombre,
                        c.apellido,
                        c.grado,
                        c.grupo,
                        c.numero,
                        c.foto,
                        c.propuestas,
                        COUNT(v.id_voto) as total_votos,
                        ROUND(COUNT(v.id_voto) * 100.0 / 
                              NULLIF((SELECT COUNT(*) FROM votos WHERE id_eleccion = ? AND voto_blanco = 0), 0), 2) as porcentaje
                    FROM candidatos c
                    LEFT JOIN votos v ON c.id_candidato = v.id_candidato AND v.id_eleccion = ?
                    WHERE c.tipo_candidato = 'estudiante'
                    GROUP BY c.id_candidato
                    ORDER BY total_votos DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $id_eleccion, $id_eleccion);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
        } catch (\Exception $e) {
            error_log("Error en getResultadosEstudiantes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener resultados de representantes docentes para una elección específica
     * @param int $id_eleccion ID de la elección (opcional, usa la activa si no se especifica)
     * @return array Resultados de representantes docentes
     */
    public function getResultadosDocentes($id_eleccion = null) {
        try {
            if ($id_eleccion === null) {
                $id_eleccion = $this->getEleccionActivaId();
            }
            
            if (!$id_eleccion) {
                return [];
            }

            $query = "SELECT 
                        rd.id_representante,
                        rd.nombre,
                        rd.area_especialidad,
                        rd.propuestas,
                        rd.foto,
                        (SELECT COUNT(*) FROM votos_docentes WHERE id_candidato_docente = rd.id_representante AND id_eleccion = ?) +
                        (SELECT COUNT(*) FROM votos_administrativos WHERE id_candidato_docente = rd.id_representante AND id_eleccion = ?) as total_votos,
                        ROUND(
                            ((SELECT COUNT(*) FROM votos_docentes WHERE id_candidato_docente = rd.id_representante AND id_eleccion = ?) +
                             (SELECT COUNT(*) FROM votos_administrativos WHERE id_candidato_docente = rd.id_representante AND id_eleccion = ?)) * 100.0 / 
                            NULLIF(
                                (SELECT COUNT(*) FROM votos_docentes WHERE id_eleccion = ? AND voto_blanco_docente = 0) +
                                (SELECT COUNT(*) FROM votos_administrativos WHERE id_eleccion = ? AND voto_blanco_docente = 0), 0
                            ), 2
                        ) as porcentaje
                    FROM representante_docente rd
                    ORDER BY total_votos DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iiiiii", $id_eleccion, $id_eleccion, $id_eleccion, $id_eleccion, $id_eleccion, $id_eleccion);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
        } catch (\Exception $e) {
            error_log("Error en getResultadosDocentes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas generales de una elección
     * @param int $id_eleccion ID de la elección (opcional, usa la activa si no se especifica)
     * @return array Estadísticas de la elección
     */
    public function getEstadisticasEleccion($id_eleccion = null) {
        try {
            if ($id_eleccion === null) {
                $id_eleccion = $this->getEleccionActivaId();
            }
            
            if (!$id_eleccion) {
                return [];
            }

            // Estadísticas de estudiantes
            $sql_estudiantes = "SELECT COUNT(*) as total FROM estudiantes WHERE estado = 1";
            $result = $this->conn->query($sql_estudiantes);
            $total_estudiantes = $result->fetch_assoc()['total'];

            $sql_votos_estudiantes = "SELECT COUNT(*) as total FROM votos WHERE id_eleccion = ?";
            $stmt = $this->conn->prepare($sql_votos_estudiantes);
            $stmt->bind_param("i", $id_eleccion);
            $stmt->execute();
            $votos_estudiantes = $stmt->get_result()->fetch_assoc()['total'];

            // Estadísticas de docentes
            $sql_docentes = "SELECT COUNT(*) as total FROM docentes";
            $result = $this->conn->query($sql_docentes);
            $total_docentes = $result->fetch_assoc()['total'];

            $sql_votos_docentes = "SELECT COUNT(*) as total FROM votos_docentes WHERE id_eleccion = ?";
            $stmt = $this->conn->prepare($sql_votos_docentes);
            $stmt->bind_param("i", $id_eleccion);
            $stmt->execute();
            $votos_docentes = $stmt->get_result()->fetch_assoc()['total'];

            // Estadísticas de administrativos
            $sql_administrativos = "SELECT COUNT(*) as total FROM administrativos";
            $result = $this->conn->query($sql_administrativos);
            $total_administrativos = $result->fetch_assoc()['total'];

            $sql_votos_administrativos = "SELECT COUNT(*) as total FROM votos_administrativos WHERE id_eleccion = ?";
            $stmt = $this->conn->prepare($sql_votos_administrativos);
            $stmt->bind_param("i", $id_eleccion);
            $stmt->execute();
            $votos_administrativos = $stmt->get_result()->fetch_assoc()['total'];

            return [
                'id_eleccion' => $id_eleccion,
                'estudiantes' => [
                    'total_habilitados' => $total_estudiantes,
                    'total_votos' => $votos_estudiantes,
                    'porcentaje_participacion' => $total_estudiantes > 0 ? round(($votos_estudiantes * 100.0) / $total_estudiantes, 2) : 0
                ],
                'docentes' => [
                    'total_habilitados' => $total_docentes,
                    'total_votos' => $votos_docentes,
                    'porcentaje_participacion' => $total_docentes > 0 ? round(($votos_docentes * 100.0) / $total_docentes, 2) : 0
                ],
                'administrativos' => [
                    'total_habilitados' => $total_administrativos,
                    'total_votos' => $votos_administrativos,
                    'porcentaje_participacion' => $total_administrativos > 0 ? round(($votos_administrativos * 100.0) / $total_administrativos, 2) : 0
                ],
                'totales' => [
                    'total_habilitados' => $total_estudiantes + $total_docentes + $total_administrativos,
                    'total_votos' => $votos_estudiantes + $votos_docentes + $votos_administrativos,
                    'porcentaje_participacion_general' => ($total_estudiantes + $total_docentes + $total_administrativos) > 0 ? 
                        round((($votos_estudiantes + $votos_docentes + $votos_administrativos) * 100.0) / ($total_estudiantes + $total_docentes + $total_administrativos), 2) : 0
                ]
            ];
        } catch (\Exception $e) {
            error_log("Error en getEstadisticasEleccion: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener votos recientes de una elección específica
     * @param int $limite Número máximo de votos a retornar
     * @param int $id_eleccion ID de la elección (opcional, usa la activa si no se especifica)
     * @return array Votos recientes
     */
    public function getVotosRecientes($limite = 10, $id_eleccion = null) {
        try {
            if ($id_eleccion === null) {
                $id_eleccion = $this->getEleccionActivaId();
            }
            
            if (!$id_eleccion) {
                return [];
            }

            $votos_recientes = [];

            // Votos de estudiantes
            $query_estudiantes = "SELECT 
                                    'estudiante' as tipo_votante,
                                    v.id_estudiante as id_votante,
                                    e.nombre as nombre_votante,
                                    e.grado,
                                    e.grupo,
                                    c.nombre as candidato_elegido,
                                    v.voto_blanco,
                                    v.fecha_voto
                                FROM votos v
                                LEFT JOIN estudiantes e ON v.id_estudiante = e.id_estudiante
                                LEFT JOIN candidatos c ON v.id_candidato = c.id_candidato
                                WHERE v.id_eleccion = ?
                                ORDER BY v.fecha_voto DESC
                                LIMIT ?";
            
            $stmt = $this->conn->prepare($query_estudiantes);
            $stmt->bind_param("ii", $id_eleccion, $limite);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado) {
                $votos_recientes = array_merge($votos_recientes, $resultado->fetch_all(MYSQLI_ASSOC));
            }

            // Votos de docentes
            $query_docentes = "SELECT 
                                'docente' as tipo_votante,
                                vd.id_docente as id_votante,
                                d.nombre as nombre_votante,
                                '' as grado,
                                '' as grupo,
                                rd.nombre as candidato_elegido,
                                vd.voto_blanco_docente as voto_blanco,
                                vd.fecha_voto
                            FROM votos_docentes vd
                            LEFT JOIN docentes d ON vd.id_docente = d.codigo_docente
                            LEFT JOIN representante_docente rd ON vd.id_candidato_docente = rd.id_representante
                            WHERE vd.id_eleccion = ?
                            ORDER BY vd.fecha_voto DESC
                            LIMIT ?";
            
            $stmt = $this->conn->prepare($query_docentes);
            $stmt->bind_param("ii", $id_eleccion, $limite);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado) {
                $votos_recientes = array_merge($votos_recientes, $resultado->fetch_all(MYSQLI_ASSOC));
            }

            // Ordenar por fecha
            usort($votos_recientes, function($a, $b) {
                return strtotime($b['fecha_voto']) - strtotime($a['fecha_voto']);
            });

            return array_slice($votos_recientes, 0, $limite);
        } catch (\Exception $e) {
            error_log("Error en getVotosRecientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Migrar votos existentes a la elección actual
     * @param int $id_eleccion ID de la elección a la que migrar los votos
     * @return bool True si la migración fue exitosa
     */
    public function migrarVotosExistentes($id_eleccion) {
        try {
            // Migrar votos de estudiantes que no tienen id_eleccion
            $sql = "UPDATE votos SET id_eleccion = ? WHERE id_eleccion IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id_eleccion);
            $stmt->execute();
            
            // Migrar votos de docentes que no tienen id_eleccion
            $sql = "UPDATE votos_docentes SET id_eleccion = ? WHERE id_eleccion IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id_eleccion);
            $stmt->execute();
            
            // Migrar votos de administrativos que no tienen id_eleccion
            $sql = "UPDATE votos_administrativos SET id_eleccion = ? WHERE id_eleccion IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id_eleccion);
            $stmt->execute();
            
            return true;
        } catch (\Exception $e) {
            error_log("Error en migrarVotosExistentes: " . $e->getMessage());
            return false;
        }
    }
}
?>
