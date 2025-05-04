<?php
namespace models;

use config\Database;

class EstudianteModel {
    private $conn;

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
     * Obtiene todos los estudiantes
     * @return array Lista de estudiantes
     */
    public function getAllEstudiantes() {
        try {
            $query = "SELECT * FROM estudiantes ORDER BY grado, grupo, nombre";
            $result = $this->conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            
            // Si no hay resultados, registrar en el log
            error_log("No se encontraron estudiantes en la tabla");
            return [];
        } catch (\Exception $e) {
            error_log("Error al obtener estudiantes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene un estudiante por su ID
     * @param string $id_estudiante ID del estudiante
     * @return array|null Datos del estudiante o null si no existe
     */
    public function getEstudiantePorId($id_estudiante) {
        try {
            $query = "SELECT * FROM estudiantes WHERE id_estudiante = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $id_estudiante);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            return null;
        } catch (\Exception $e) {
            error_log("Error al obtener estudiante por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un nuevo estudiante
     * @param array $datos Datos del estudiante (id_estudiante, nombre, grado, grupo, estado)
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crearEstudiante($datos) {
        try {
            $query = "INSERT INTO estudiantes (id_estudiante, nombre, grado, grupo, estado) 
                      VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssssi", 
                $datos['id_estudiante'], 
                $datos['nombre'], 
                $datos['grado'], 
                $datos['grupo'], 
                $datos['estado']
            );
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error al crear estudiante: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un estudiante existente
     * @param string $id_estudiante ID del estudiante
     * @param array $datos Datos a actualizar (nombre, grado, grupo, estado)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarEstudiante($id_estudiante, $datos) {
        try {
            $query = "UPDATE estudiantes 
                      SET nombre = ?, grado = ?, grupo = ?, estado = ? 
                      WHERE id_estudiante = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssss", 
                $datos['nombre'], 
                $datos['grado'], 
                $datos['grupo'], 
                $datos['estado'],
                $id_estudiante
            );
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error al actualizar estudiante: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un estudiante
     * @param string $id_estudiante ID del estudiante a eliminar
     * @return bool True si se eliminó correctamente, False en caso contrario
     */
    public function eliminarEstudiante($id_estudiante) {
        try {
            $query = "DELETE FROM estudiantes WHERE id_estudiante = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $id_estudiante);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error al eliminar estudiante: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el total de estudiantes para la paginación
     * @param string $busqueda Término de búsqueda (opcional)
     * @return int Total de estudiantes
     */
    public function getTotalEstudiantes($busqueda = '') {
        try {
            $query = "SELECT COUNT(*) as total FROM estudiantes";
            $params = [];
            
            // Si hay un término de búsqueda, añadir la condición WHERE
            if (!empty($busqueda)) {
                $query .= " WHERE id_estudiante LIKE ? OR nombre LIKE ?";
                $busquedaParam = "%{$busqueda}%";
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
            }
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters if any
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return (int)$row['total'];
            }
            
            return 0;
        } catch (\Exception $e) {
            error_log("Error al obtener el total de estudiantes: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtiene estudiantes con paginación
     * @param int $offset Número de registros a omitir
     * @param int $limit Número máximo de registros a devolver
     * @param string $busqueda Término de búsqueda (opcional)
     * @return array Lista de estudiantes paginada
     */
    public function getEstudiantesPaginados($offset, $limit, $busqueda = '') {
        try {
            $query = "SELECT * FROM estudiantes";
            $params = [];
            
            // Si hay un término de búsqueda, añadir la condición WHERE
            if (!empty($busqueda)) {
                $query .= " WHERE id_estudiante LIKE ? OR nombre LIKE ?";
                $busquedaParam = "%{$busqueda}%";
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
            }
            
            $query .= " ORDER BY grado, grupo, nombre LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $limit;
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            if (count($params) == 2) {
                // Solo los parámetros de LIMIT
                $stmt->bind_param("ii", $params[0], $params[1]);
            } else {
                // Parámetros de búsqueda + LIMIT
                $stmt->bind_param("ssii", $params[0], $params[1], $params[2], $params[3]);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            
            // Si no hay resultados, registrar en el log
            error_log("No se encontraron estudiantes en la página solicitada");
            return [];
        } catch (\Exception $e) {
            error_log("Error al obtener estudiantes paginados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Autentica un estudiante
     * @param string $id_estudiante ID del estudiante
     * @return array|null Datos del estudiante si la autenticación es exitosa, null en caso contrario
     */
    public function autenticarEstudiante($id_estudiante) {
        try {
            error_log("Intentando autenticar con ID: " . $id_estudiante);
            
            // Consulta simplificada que solo busca por id_estudiante
            $query = "SELECT * FROM estudiantes WHERE id_estudiante = ?";
            error_log("Consulta SQL: " . $query . " con valor: " . $id_estudiante);
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $id_estudiante);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            error_log("Número de filas encontradas: " . $resultado->num_rows);
            
            if ($resultado->num_rows > 0) {
                $estudiante = $resultado->fetch_assoc();
                error_log("Estudiante encontrado: " . json_encode($estudiante));
                return $estudiante;
            }
            
            error_log("No se encontró ningún estudiante con ID: " . $id_estudiante);
            return null;
        } catch (\Exception $e) {
            error_log("Error en autenticarEstudiante: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si un estudiante ya ha votado
     * @param string $id_estudiante ID del estudiante
     * @return bool True si ya votó, False en caso contrario
     */
    public function haVotado($id_estudiante) {
        try {
            
            $query = "SELECT COUNT(*) as voto_count 
                     FROM votos v 
                     INNER JOIN estudiantes e ON v.documento_estudiante = e.id_estudiante 
                     WHERE e.id_estudiante = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $id_estudiante);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $row = $result->fetch_assoc();
            
            return $row['voto_count'] > 0;
        } catch (\Exception $e) {
            error_log("Error en haVotado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estudiantes por grado
     * @param string $grado Grado de los estudiantes a obtener
     * @return array Lista de estudiantes del grado especificado
     */
    public function getEstudianteByGrado($grado) {
        try {
            $query = "SELECT * FROM estudiantes 
                     WHERE grado = ? 
                     ORDER BY grupo, nombre";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $grado);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado && $resultado->num_rows > 0) {
                return $resultado->fetch_all(MYSQLI_ASSOC);
            }
            return [];
        } catch (\Exception $e) {
            error_log("Error en getEstudianteByGrado: " . $e->getMessage());
            return [];
        }
    }
}
