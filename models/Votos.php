<?php
namespace models;

use config\Database;
use PDO;
use models\DocenteModel;
use models\RepresentanteDocenteModel;

class Votos {
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
     * Verifica si un estudiante ya ha votado por un tipo específico de candidato
     * @param int $id_estudiante ID del estudiante
     * @param string $tipo_voto Tipo de voto (PERSONERO o REPRESENTANTE)
     * @return bool Verdadero si ya ha votado, falso en caso contrario
     */
    public function haVotadoPorTipo($id_estudiante, $tipo_voto) {
        try {
            $query = "SELECT COUNT(*) as total FROM votos 
                      WHERE id_estudiante = ? AND tipo_voto = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("is", $id_estudiante, $tipo_voto);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                return $row['total'] > 0;
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en haVotadoPorTipo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un voto en la base de datos
     * @param int $id_estudiante ID del estudiante que vota
     * @param int $id_candidato ID del candidato por el que se vota
     * @param string $tipo_voto Tipo de voto (PERSONERO o REPRESENTANTE)
     * @return bool Verdadero si el voto se registró correctamente, falso en caso contrario
     */
    public function registrarVoto($id_estudiante, $id_candidato, $tipo_voto) {
        try {
            // Verificar si el estudiante ya ha votado para este tipo
            if ($this->haVotadoPorTipo($id_estudiante, $tipo_voto)) {
                return false;
            }
            
            $query = "INSERT INTO votos (id_estudiante, id_candidato, tipo_voto) 
                      VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iis", $id_estudiante, $id_candidato, $tipo_voto);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error en registrarVoto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un voto en blanco en la base de datos
     * @param int $id_estudiante ID del estudiante que vota
     * @param string $tipo_voto Tipo de voto (PERSONERO o REPRESENTANTE)
     * @return bool Verdadero si el voto se registró correctamente, falso en caso contrario
     */
    public function registrarVotoEnBlanco($id_estudiante, $tipo_voto) {
        try {
            // Verificar si el estudiante ya ha votado para este tipo
            if ($this->haVotadoPorTipo($id_estudiante, $tipo_voto)) {
                error_log("El estudiante ID: $id_estudiante ya ha votado para el tipo: $tipo_voto");
                return false;
            }
            
            // Para votos en blanco, usamos NULL como id_candidato
            // Aseguramos que $tipo_voto sea una cadena válida (PERSONERO o REPRESENTANTE)
            if (!in_array($tipo_voto, ['PERSONERO', 'REPRESENTANTE'])) {
                error_log("Tipo de voto inválido: $tipo_voto");
                return false;
            }
            
            $query = "INSERT INTO votos (id_estudiante, id_candidato, tipo_voto) 
                      VALUES (?, NULL, ?)";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error en la preparación de la consulta: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("is", $id_estudiante, $tipo_voto);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Error al ejecutar la consulta: " . $stmt->error);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error en registrarVotoEnBlanco: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Elimina todos los votos de un estudiante si no ha completado los dos tipos de voto
     * @param int $id_estudiante ID del estudiante
     * @return bool Verdadero si los votos fueron eliminados correctamente o si ya votó por ambos tipos
     */
    public function eliminarVotosIncompletos($id_estudiante) {
        try {
            // Verificar si el estudiante ha votado por ambos tipos
            $votoPersonero = $this->haVotadoPorTipo($id_estudiante, 'PERSONERO');
            $votoRepresentante = $this->haVotadoPorTipo($id_estudiante, 'REPRESENTANTE');
            
            // Si no ha votado por ambos tipos, eliminar los votos existentes
            if (!($votoPersonero && $votoRepresentante)) {
                $query = "DELETE FROM votos WHERE id_estudiante = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("i", $id_estudiante);
                return $stmt->execute();
            }
            
            return true; // Ya votó por ambos tipos, no es necesario eliminar
        } catch (\Exception $e) {
            error_log("Error en eliminarVotosIncompletos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finaliza la votación de un estudiante verificando que ha votado por ambos tipos
     * @param int $id_estudiante ID del estudiante
     * @return array Resultado con estado y mensaje
     */
    public function finalizarVotacion($id_estudiante) {
        try {
            // Verificar si el estudiante ha votado por ambos tipos
            $votoPersonero = $this->haVotadoPorTipo($id_estudiante, 'PERSONERO');
            $votoRepresentante = $this->haVotadoPorTipo($id_estudiante, 'REPRESENTANTE');
            
            if ($votoPersonero && $votoRepresentante) {
                return [
                    'completo' => true,
                    'mensaje' => 'Votación completada correctamente'
                ];
            } else {
                // Si no ha votado por ambos tipos, eliminar los votos y retornar mensaje
                $this->eliminarVotosIncompletos($id_estudiante);
                
                $faltantes = [];
                if (!$votoPersonero) $faltantes[] = 'personero';
                if (!$votoRepresentante) $faltantes[] = 'representante';
                
                return [
                    'completo' => false,
                    'mensaje' => 'Votación incompleta. No se registró voto para ' . implode(' y ', $faltantes),
                    'faltantes' => $faltantes
                ];
            }
        } catch (\Exception $e) {
            error_log("Error en finalizarVotacion: " . $e->getMessage());
            return [
                'completo' => false,
                'mensaje' => 'Error al procesar la votación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene todos los votos de la base de datos
     * @return array Arreglo con todos los votos
     */
    public function getAllVotos() {
        try {
            $query = "SELECT v.*, e.nombre as nombre_estudiante, c.nombre as nombre_candidato 
                      FROM votos v
                      LEFT JOIN estudiantes e ON v.id_estudiante = e.id_estudiante
                      LEFT JOIN candidatos c ON v.id_candidato = c.id_candidato
                      ORDER BY v.fecha_voto DESC";
            $resultado = $this->conn->query($query);
            
            if ($resultado && $resultado->num_rows > 0) {
                return $resultado->fetch_all(MYSQLI_ASSOC);
            }
            return [];
        } catch (\Exception $e) {
            error_log("Error en getAllVotos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el conteo de votos por candidato y tipo
     * @param string $tipo_voto Tipo de voto (PERSONERO o REPRESENTANTE)
     * @return array Arreglo con el conteo de votos por candidato
     */
    public function getConteoVotosPorTipo($tipo_voto) {
        try {
            // Consulta más completa para obtener más información de los candidatos
            $query = "SELECT 
                      c.id_candidato, 
                      c.nombre, 
                      c.apellido, 
                      c.foto, 
                      c.numero,
                      c.grado,
                      c.tipo_candidato,
                      IFNULL((
                          SELECT COUNT(*) 
                          FROM votos 
                          WHERE votos.id_candidato = c.id_candidato 
                          AND votos.tipo_voto = ?
                      ), 0) as total_votos
                      FROM candidatos c
                      WHERE c.tipo_candidato = ?
                      ORDER BY total_votos DESC, c.nombre ASC";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error en la preparación de la consulta getConteoVotosPorTipo: " . $this->conn->error);
                return [];
            }
            
            $stmt->bind_param("ss", $tipo_voto, $tipo_voto);
            
            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta getConteoVotosPorTipo: " . $stmt->error);
                return [];
            }
            
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $candidatos = $resultado->fetch_all(MYSQLI_ASSOC);
                
                // Calcular porcentajes
                $totalVotos = 0;
                foreach ($candidatos as $candidato) {
                    $totalVotos += (int)$candidato['total_votos'];
                }
                
                foreach ($candidatos as &$candidato) {
                    $candidato['total_votos'] = (int)$candidato['total_votos'];
                    $candidato['porcentaje'] = $totalVotos > 0 ? 
                        round(($candidato['total_votos'] / $totalVotos) * 100, 1) : 0;
                }
                
                error_log("Candidatos de tipo $tipo_voto: " . json_encode($candidatos));
                return $candidatos;
            }
            
            error_log("No se encontraron candidatos de tipo $tipo_voto");
            return [];
        } catch (\Exception $e) {
            error_log("Error en getConteoVotosPorTipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el conteo de votos en blanco por tipo
     * @param string $tipo_voto Tipo de voto (PERSONERO o REPRESENTANTE)
     * @return int Cantidad de votos en blanco
     */
    public function getConteoVotosEnBlanco($tipo_voto) {
        try {
            $query = "SELECT COUNT(*) as total FROM votos 
                      WHERE id_candidato IS NULL AND tipo_voto = ?";
            $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $tipo_voto);
        $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                return $row['total'];
            }
            return 0;
        } catch (\Exception $e) {
            error_log("Error en getConteoVotosEnBlanco: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verifica si un docente ya ha votado
     * @param int $id_docente ID del docente
     * @return bool Verdadero si ya ha votado, falso en caso contrario
     */
    public function haVotadoDocente($id_docente) {
        try {
            // Verificar si es administrativo
            if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'administrativo') {
                return $this->haVotadoAdministrativo($id_docente);
            }
            
            $query = "SELECT COUNT(*) as total FROM votos_docentes WHERE id_docente = ?";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error en la preparación de la consulta: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("s", $id_docente);
            
            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta: " . $stmt->error);
                return false;
            }
            
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                return (int)$row['total'] > 0;
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en haVotadoDocente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un administrativo ya ha votado
     * @param int $id_administrativo ID del administrativo
     * @return bool Verdadero si ya ha votado, falso en caso contrario
     */
    public function haVotadoAdministrativo($id_administrativo) {
        try {
            // Verificar si existe la tabla votos_administrativos, si no, crearla
            $this->crearTablaVotosAdministrativos();
            
            $query = "SELECT COUNT(*) as total FROM votos_administrativos WHERE id_administrativo = ?";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error en la preparación de la consulta: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("i", $id_administrativo);
            
            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta: " . $stmt->error);
                return false;
            }
            
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                return (int)$row['total'] > 0;
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en haVotadoAdministrativo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea la tabla votos_administrativos si no existe
     */
    private function crearTablaVotosAdministrativos() {
        try {
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'votos_administrativos'");
            if ($checkTable->num_rows == 0) {
                $sql = "CREATE TABLE votos_administrativos (
                    id_voto INT AUTO_INCREMENT PRIMARY KEY,
                    id_administrativo INT NOT NULL,
                    codigo_representante VARCHAR(20),
                    voto_blanco TINYINT(1) DEFAULT 0,
                    fecha_voto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    INDEX idx_administrativo (id_administrativo),
                    INDEX idx_representante (codigo_representante),
                    INDEX idx_fecha_voto (fecha_voto),
                    UNIQUE KEY unique_voto_administrativo (id_administrativo)
                )";
                
                $this->conn->query($sql);
                error_log("Tabla votos_administrativos creada correctamente");
            }
        } catch (\Exception $e) {
            error_log("Error al crear tabla votos_administrativos: " . $e->getMessage());
        }
    }
    
    /**
     * Registra un voto de docente en la base de datos
     * @param int $id_docente ID del docente que vota
     * @param int|null $id_representante ID del representante por el que se vota (null si es voto en blanco)
     * @param bool $voto_blanco Indica si es un voto en blanco
     * @return bool Verdadero si el voto se registró correctamente, falso en caso contrario
     */
    public function registrarVotoDocente($id_docente, $id_representante = null, $voto_blanco = false) {
        try {
            // Verificar si es administrativo
            if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'administrativo') {
                return $this->registrarVotoAdministrativo($id_docente, $id_representante, $voto_blanco);
            }
            
            // Iniciar transacción
            $this->conn->begin_transaction();
            
            // Verificar si el docente ya ha votado
            if ($this->haVotadoDocente($id_docente)) {
                $this->conn->rollback();
                return false;
            }
            
            // Registrar el voto
            $query = "INSERT INTO votos_docentes (id_docente, codigo_representante, voto_blanco, fecha_voto) VALUES (?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            $voto_blanco_int = $voto_blanco ? 1 : 0;
            $stmt->bind_param("ssi", $id_docente, $id_representante, $voto_blanco_int);
            $resultado = $stmt->execute();
            
            if (!$resultado) {
                $this->conn->rollback();
                return false;
            }
            
            // Confirmar transacción
            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            $this->conn->rollback();
            error_log("Error en registrarVotoDocente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un voto de administrativo en la base de datos
     * @param int $id_administrativo ID del administrativo que vota
     * @param int|null $id_representante ID del representante por el que se vota (null si es voto en blanco)
     * @param bool $voto_blanco Indica si es un voto en blanco
     * @return bool Verdadero si el voto se registró correctamente, falso en caso contrario
     */
    public function registrarVotoAdministrativo($id_administrativo, $id_representante = null, $voto_blanco = false) {
        try {
            // Asegurar que la tabla existe
            $this->crearTablaVotosAdministrativos();
            
            // Iniciar transacción
            $this->conn->begin_transaction();
            
            // Verificar si el administrativo ya ha votado
            if ($this->haVotadoAdministrativo($id_administrativo)) {
                $this->conn->rollback();
                return false;
            }
            
            // Obtener IP y User Agent para auditoría
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Registrar el voto
            $query = "INSERT INTO votos_administrativos (id_administrativo, codigo_representante, voto_blanco, fecha_voto, ip_address, user_agent) VALUES (?, ?, ?, NOW(), ?, ?)";
            $stmt = $this->conn->prepare($query);
            $voto_blanco_int = $voto_blanco ? 1 : 0;
            $stmt->bind_param("isiss", $id_administrativo, $id_representante, $voto_blanco_int, $ip_address, $user_agent);
            $resultado = $stmt->execute();
            
            if (!$resultado) {
                $this->conn->rollback();
                return false;
            }
            
            // Confirmar transacción
            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            $this->conn->rollback();
            error_log("Error en registrarVotoAdministrativo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene las estadísticas de la votación de docentes
     * @return array Arreglo con las estadísticas de la votación
     */
    public function getEstadisticasVotacionDocentes() {
        try {
            // Total de docentes
            $query1 = "SELECT COUNT(*) as total FROM docentes WHERE estado = 'ACTIVO'";
            $stmt1 = $this->conn->prepare($query1);
            
            if (!$stmt1) {
                error_log("Error en la preparación de la consulta 1: " . $this->conn->error);
                return [
                    'totalDocentes' => 0,
                    'totalVotos' => 0,
                    'totalVotosBlanco' => 0,
                    'porcentajeParticipacion' => 0
                ];
            }
            
            $stmt1->execute();
            $resultado1 = $stmt1->get_result();
            $totalDocentes = 0;
            
            if ($resultado1 && $resultado1->num_rows > 0) {
                $row = $resultado1->fetch_assoc();
                $totalDocentes = (int)$row['total'];
            }
            
            // Total de votos
            $query2 = "SELECT COUNT(*) as total FROM votos_docentes";
            $stmt2 = $this->conn->prepare($query2);
            
            if (!$stmt2) {
                error_log("Error en la preparación de la consulta 2: " . $this->conn->error);
                return [
                    'totalDocentes' => $totalDocentes,
                    'totalVotos' => 0,
                    'totalVotosBlanco' => 0,
                    'porcentajeParticipacion' => 0
                ];
            }
            
            $stmt2->execute();
            $resultado2 = $stmt2->get_result();
            $totalVotos = 0;
            
            if ($resultado2 && $resultado2->num_rows > 0) {
                $row = $resultado2->fetch_assoc();
                $totalVotos = (int)$row['total'];
            }
            
            // Total de votos en blanco
            $query3 = "SELECT COUNT(*) as total FROM votos_docentes WHERE voto_blanco = 1";
            $stmt3 = $this->conn->prepare($query3);
            
            if (!$stmt3) {
                error_log("Error en la preparación de la consulta 3: " . $this->conn->error);
                return [
                    'totalDocentes' => $totalDocentes,
                    'totalVotos' => $totalVotos,
                    'totalVotosBlanco' => 0,
                    'porcentajeParticipacion' => $totalDocentes > 0 ? round(($totalVotos / $totalDocentes) * 100, 2) : 0
                ];
            }
            
            $stmt3->execute();
            $resultado3 = $stmt3->get_result();
            $totalVotosBlanco = 0;
            
            if ($resultado3 && $resultado3->num_rows > 0) {
                $row = $resultado3->fetch_assoc();
                $totalVotosBlanco = (int)$row['total'];
            }
            
            // Porcentaje de participación
            $porcentajeParticipacion = $totalDocentes > 0 ? round(($totalVotos / $totalDocentes) * 100, 2) : 0;
            
            error_log("Estadísticas de votación docentes - Total docentes: $totalDocentes, Total votos: $totalVotos, Votos en blanco: $totalVotosBlanco, Participación: $porcentajeParticipacion%");
            
            return [
                'totalDocentes' => $totalDocentes,
                'totalVotos' => $totalVotos,
                'totalVotosBlanco' => $totalVotosBlanco,
                'porcentajeParticipacion' => $porcentajeParticipacion
            ];
        } catch (\Exception $e) {
            error_log("Error en getEstadisticasVotacionDocentes: " . $e->getMessage());
            return [
                'totalDocentes' => 0,
                'totalVotos' => 0,
                'totalVotosBlanco' => 0,
                'porcentajeParticipacion' => 0
            ];
        }
    }
    
    /**
     * Obtiene el conteo de votos por representante docente
     * @return array Arreglo con el conteo de votos por representante
     */
    public function getConteoVotosRepresentantesDocentes() {
        try {
            // Consulta directa sin JOIN para evitar problemas de colación
            $query = "SELECT rd.codigo_repres_docente, rd.nombre_repre_docente, 
                      (SELECT COUNT(*) FROM votos_docentes vd WHERE vd.codigo_representante = rd.codigo_repres_docente AND vd.voto_blanco = 0) as total_votos 
                      FROM representante_docente rd
                      ORDER BY total_votos DESC";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error en la preparación de la consulta getConteoVotosRepresentantesDocentes: " . $this->conn->error);
                return [];
            }
            
            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta getConteoVotosRepresentantesDocentes: " . $stmt->error);
                return [];
            }
            
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $votos = $resultado->fetch_all(MYSQLI_ASSOC);
                
                // Asegurarse de que total_votos sea un entero
                foreach ($votos as &$voto) {
                    $voto['total_votos'] = (int)$voto['total_votos'];
                }
                
                error_log("Conteo de votos por representante docente: " . json_encode($votos));
                return $votos;
            }
            
            error_log("No se encontraron votos para representantes docentes");
            return [];
        } catch (\Exception $e) {
            error_log("Error en getConteoVotosRepresentantesDocentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene los votos recientes de docentes
     * @param int $limite Número máximo de votos a retornar
     * @return array Arreglo con los votos recientes
     */
    public function getVotosRecientesDocentes($limite = 5) {
        try {
            // Verificar si la tabla existe
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'votos_docentes'");
            if ($checkTable->num_rows == 0) {
                error_log("La tabla votos_docentes no existe");
                return [];
            }
            
            // Cargar los modelos necesarios
            require_once __DIR__ . '/DocenteModel.php';
            require_once __DIR__ . '/RepresentanteDocenteModel.php';
            
            // Obtener los votos recientes
            $query = "SELECT vd.id_voto, vd.id_docente, vd.codigo_representante, vd.voto_blanco, vd.fecha_voto
                      FROM votos_docentes vd
                      ORDER BY vd.fecha_voto DESC
                      LIMIT ?";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error en la preparación de la consulta getVotosRecientesDocentes: " . $this->conn->error);
                return [];
            }
            
            $stmt->bind_param("i", $limite);
            
            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta getVotosRecientesDocentes: " . $stmt->error);
                return [];
            }
            
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $votos = $resultado->fetch_all(MYSQLI_ASSOC);
                $docenteModel = new DocenteModel();
                $representanteModel = new RepresentanteDocenteModel();
                
                // Obtener todos los docentes para buscarlos por ID
                $todosDocentes = $docenteModel->getAllDocentes();
                $docentesPorId = [];
                foreach ($todosDocentes as $docente) {
                    $docentesPorId[$docente['id']] = $docente;
                }
                
                // Procesar los votos para añadir información adicional
                foreach ($votos as &$voto) {
                    // Obtener información del docente por ID
                    $idDocente = $voto['id_docente'];
                    if (isset($docentesPorId[$idDocente])) {
                        $docente = $docentesPorId[$idDocente];
                        $voto['nombre_docente'] = $docente['nombre'] ?? 'Docente';
                        $voto['area'] = $docente['area'] ?? '';
                    } else {
                        $voto['nombre_docente'] = 'Docente';
                        $voto['area'] = '';
                    }
                    
                    $voto['nombre_completo'] = $voto['nombre_docente'];
                    $voto['rol'] = 'Docente' . ($voto['area'] ? ' - ' . $voto['area'] : '');
                    
                    // Obtener información del representante
                    if (!$voto['voto_blanco'] && $voto['codigo_representante']) {
                        $representante = $representanteModel->getByCodigo($voto['codigo_representante']);
                        $voto['nombre_representante'] = $representante['nombre_repre_docente'] ?? 'Representante';
                    }
                    
                    // Información sobre el voto
                    if ($voto['voto_blanco'] == 1) {
                        $voto['info_voto'] = 'Votó en blanco';
                    } else if (isset($voto['nombre_representante']) && !empty($voto['nombre_representante'])) {
                        $voto['info_voto'] = 'Votó por ' . $voto['nombre_representante'];
                    } else {
                        $voto['info_voto'] = 'Votó';
                    }
                    
                    // Formatear fecha
                    $voto['fecha'] = date('d/m/Y H:i', strtotime($voto['fecha_voto']));
                }
                
                error_log("Votos recientes docentes encontrados: " . count($votos));
                return $votos;
            }
            
            error_log("No se encontraron votos recientes de docentes");
            
            // Si no hay votos recientes, intentar obtener docentes que hayan votado
            $docenteModel = new DocenteModel();
            $docentes = $docenteModel->getAllDocentes();
            
            if (!empty($docentes)) {
                // Crear un array con al menos un docente para mostrar en la interfaz
                $docente = $docentes[0];
                return [
                    [
                        'id_docente' => $docente['codigo_docente'],
                        'nombre_docente' => $docente['nombre'],
                        'nombre_completo' => $docente['nombre'],
                        'rol' => 'Docente',
                        'info_voto' => 'Sin actividad reciente',
                        'fecha_voto' => date('Y-m-d H:i:s'),
                        'fecha' => date('d/m/Y H:i')
                    ]
                ];
            }
            
            return [];
        } catch (\Exception $e) {
            error_log("Error en getVotosRecientesDocentes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los votos recientes de administrativos
     * @param int $limite Número máximo de votos a retornar
     * @return array Arreglo con los votos recientes
     */
    public function getVotosRecientesAdministrativos($limite = 5) {
        try {
            // Verificar si la tabla existe
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'votos_administrativos'");
            if ($checkTable->num_rows == 0) {
                error_log("La tabla votos_administrativos no existe");
                return [];
            }
            
            // Cargar los modelos necesarios
            require_once __DIR__ . '/AdministrativoModel.php';
            require_once __DIR__ . '/RepresentanteDocenteModel.php';
            
            // Obtener los votos recientes
            $query = "SELECT va.id_voto, va.id_administrativo, va.codigo_representante, va.voto_blanco, va.fecha_voto
                      FROM votos_administrativos va
                      ORDER BY va.fecha_voto DESC
                      LIMIT ?";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error en la preparación de la consulta getVotosRecientesAdministrativos: " . $this->conn->error);
                return [];
            }
            
            $stmt->bind_param("i", $limite);
            
            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta getVotosRecientesAdministrativos: " . $stmt->error);
                return [];
            }
            
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                $votos = $resultado->fetch_all(MYSQLI_ASSOC);
                $administrativoModel = new AdministrativoModel();
                $representanteModel = new RepresentanteDocenteModel();
                
                // Obtener todos los administrativos para buscarlos por cédula (ya que id_administrativo en votos es realmente la cédula)
                $todosAdministrativos = $administrativoModel->getAllAdministrativos();
                $administrativosPorCedula = [];
                foreach ($todosAdministrativos as $administrativo) {
                    $administrativosPorCedula[$administrativo['cedula']] = $administrativo;
                }
                
                // Procesar los votos para añadir información adicional
                foreach ($votos as &$voto) {
                    // El id_administrativo en la tabla votos_administrativos es realmente la cédula
                    $cedulaAdministrativo = $voto['id_administrativo'];
                    if (isset($administrativosPorCedula[$cedulaAdministrativo])) {
                        $administrativo = $administrativosPorCedula[$cedulaAdministrativo];
                        $nombreCompleto = trim(($administrativo['nombre'] ?? '') . ' ' . ($administrativo['apellido'] ?? ''));
                        $voto['nombre_administrativo'] = $nombreCompleto ?: 'Administrativo';
                        $voto['cargo'] = $administrativo['cargo'] ?? '';
                    } else {
                        // Si no se encuentra en el array, buscar directamente por cédula
                        $administrativo = $administrativoModel->getAdministrativoPorCedula($cedulaAdministrativo);
                        if ($administrativo) {
                            $nombreCompleto = trim(($administrativo['nombre'] ?? '') . ' ' . ($administrativo['apellido'] ?? ''));
                            $voto['nombre_administrativo'] = $nombreCompleto ?: 'Administrativo';
                            $voto['cargo'] = $administrativo['cargo'] ?? '';
                        } else {
                            $voto['nombre_administrativo'] = 'Administrativo';
                            $voto['cargo'] = '';
                        }
                    }
                    
                    $voto['nombre_completo'] = $voto['nombre_administrativo'];
                    $voto['rol'] = 'Administrativo' . ($voto['cargo'] ? ' - ' . $voto['cargo'] : '');
                    
                    // Obtener información del representante
                    if (!$voto['voto_blanco'] && $voto['codigo_representante']) {
                        $representante = $representanteModel->getByCodigo($voto['codigo_representante']);
                        $voto['nombre_representante'] = $representante['nombre_repre_docente'] ?? 'Representante';
                    }
                    
                    // Información sobre el voto
                    if ($voto['voto_blanco'] == 1) {
                        $voto['info_voto'] = 'Votó en blanco';
                    } else if (isset($voto['nombre_representante']) && !empty($voto['nombre_representante'])) {
                        $voto['info_voto'] = 'Votó por ' . $voto['nombre_representante'];
                    } else {
                        $voto['info_voto'] = 'Votó';
                    }
                    
                    // Formatear fecha
                    $voto['fecha'] = date('d/m/Y H:i', strtotime($voto['fecha_voto']));
                }
                
                error_log("Votos recientes administrativos encontrados: " . count($votos));
                return $votos;
            }
            
            error_log("No se encontraron votos recientes de administrativos");
            return [];
        } catch (\Exception $e) {
            error_log("Error en getVotosRecientesAdministrativos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las estadísticas de la votación de administrativos
     * @return array Arreglo con las estadísticas de la votación
     */
    public function getEstadisticasVotacionAdministrativos() {
        try {
            // Total de administrativos
            $query1 = "SELECT COUNT(*) as total FROM administrativos WHERE estado = 'Activo'";
            $stmt1 = $this->conn->prepare($query1);
            
            if (!$stmt1) {
                error_log("Error en la preparación de la consulta 1: " . $this->conn->error);
                return [
                    'totalAdministrativos' => 0,
                    'totalVotos' => 0,
                    'totalVotosBlanco' => 0,
                    'porcentajeParticipacion' => 0
                ];
            }
            
            $stmt1->execute();
            $resultado1 = $stmt1->get_result();
            $totalAdministrativos = 0;
            
            if ($resultado1 && $resultado1->num_rows > 0) {
                $row = $resultado1->fetch_assoc();
                $totalAdministrativos = (int)$row['total'];
            }
            
            // Total de votos
            $query2 = "SELECT COUNT(*) as total FROM votos_administrativos";
            $stmt2 = $this->conn->prepare($query2);
            
            if (!$stmt2) {
                error_log("Error en la preparación de la consulta 2: " . $this->conn->error);
                return [
                    'totalAdministrativos' => $totalAdministrativos,
                    'totalVotos' => 0,
                    'totalVotosBlanco' => 0,
                    'porcentajeParticipacion' => 0
                ];
            }
            
            $stmt2->execute();
            $resultado2 = $stmt2->get_result();
            $totalVotos = 0;
            
            if ($resultado2 && $resultado2->num_rows > 0) {
                $row = $resultado2->fetch_assoc();
                $totalVotos = (int)$row['total'];
            }
            
            // Total de votos en blanco
            $query3 = "SELECT COUNT(*) as total FROM votos_administrativos WHERE voto_blanco = 1";
            $stmt3 = $this->conn->prepare($query3);
            
            if (!$stmt3) {
                error_log("Error en la preparación de la consulta 3: " . $this->conn->error);
                return [
                    'totalAdministrativos' => $totalAdministrativos,
                    'totalVotos' => $totalVotos,
                    'totalVotosBlanco' => 0,
                    'porcentajeParticipacion' => $totalAdministrativos > 0 ? round(($totalVotos / $totalAdministrativos) * 100, 2) : 0
                ];
            }
            
            $stmt3->execute();
            $resultado3 = $stmt3->get_result();
            $totalVotosBlanco = 0;
            
            if ($resultado3 && $resultado3->num_rows > 0) {
                $row = $resultado3->fetch_assoc();
                $totalVotosBlanco = (int)$row['total'];
            }
            
            // Porcentaje de participación
            $porcentajeParticipacion = $totalAdministrativos > 0 ? round(($totalVotos / $totalAdministrativos) * 100, 2) : 0;
            
            error_log("Estadísticas de votación administrativos - Total administrativos: $totalAdministrativos, Total votos: $totalVotos, Votos en blanco: $totalVotosBlanco, Participación: $porcentajeParticipacion%");
            
            return [
                'totalAdministrativos' => $totalAdministrativos,
                'totalVotos' => $totalVotos,
                'totalVotosBlanco' => $totalVotosBlanco,
                'porcentajeParticipacion' => $porcentajeParticipacion
            ];
        } catch (\Exception $e) {
            error_log("Error en getEstadisticasVotacionAdministrativos: " . $e->getMessage());
            return [
                'totalAdministrativos' => 0,
                'totalVotos' => 0,
                'totalVotosBlanco' => 0,
                'porcentajeParticipacion' => 0
            ];
        }
    }

    public function obtenerResultadosPersonero() {
        $query = "SELECT 
                    c.nombre_personero as nombre,
                    COUNT(v.id) as votos,
                    ROUND((COUNT(v.id) * 100.0 / (SELECT COUNT(*) FROM votos WHERE tipo_voto = 'PERSONERO')), 2) as porcentaje
                FROM candidatos c
                LEFT JOIN votos v ON c.codigo_personero = v.codigo_candidato AND v.tipo_voto = 'PERSONERO'
                WHERE c.tipo_candidato = 'PERSONERO'
                GROUP BY c.codigo_personero, c.nombre_personero
                ORDER BY votos DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado && $resultado->num_rows > 0) {
            return $resultado->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function obtenerGrados() {
        $query = "SELECT DISTINCT grado 
                 FROM candidatos 
                 WHERE tipo_candidato = 'REPRESENTANTE' 
                 ORDER BY grado";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $grados = [];
        if ($resultado && $resultado->num_rows > 0) {
            while ($row = $resultado->fetch_assoc()) {
                $grados[] = $row['grado'];
            }
        }
        return $grados;
    }

    public function obtenerResultadosRepresentante($grado) {
        $query = "SELECT 
                    c.nombre_repre_docente as nombre,
                    COUNT(v.id) as votos,
                    ROUND((COUNT(v.id) * 100.0 / (
                        SELECT COUNT(*) 
                        FROM votos 
                        WHERE tipo_voto = 'REPRESENTANTE' 
                        AND codigo_candidato IN (
                            SELECT codigo_repres_docente 
                            FROM candidatos 
                            WHERE tipo_candidato = 'REPRESENTANTE' 
                            AND grado = ?
                        )
                    )), 2) as porcentaje
                FROM candidatos c
                LEFT JOIN votos v ON c.codigo_repres_docente = v.codigo_candidato 
                    AND v.tipo_voto = 'REPRESENTANTE'
                WHERE c.tipo_candidato = 'REPRESENTANTE' 
                AND c.grado = ?
                GROUP BY c.codigo_repres_docente, c.nombre_repre_docente
                ORDER BY votos DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $grado, $grado);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado && $resultado->num_rows > 0) {
            return $resultado->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
}
?>