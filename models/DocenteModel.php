<?php
namespace models;

use config\Database;

class DocenteModel {
    private $db;
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

    public function getDocentePorDocumento($documento) {
        try {
            $query = "SELECT * FROM docentes WHERE codigo_docente = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $documento);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                return $resultado->fetch_assoc();
            }
            return false;
        } catch (\Exception $e) {
            // Log error if needed
            error_log("Error en getDocentePorDocumento: " . $e->getMessage());
            return false;
        }
    }

    public function getAllDocentes() {
        try {
            // Verificar si la tabla existe
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'docentes'");
            if ($checkTable->num_rows == 0) {
                // La tabla no existe, intentar crearla
                $this->crearTablaDocentes();
                return [];
            }
            
            $query = "SELECT * FROM docentes ORDER BY nombre";
            $resultado = $this->conn->query($query);
            
            if ($resultado && $resultado->num_rows > 0) {
                return $resultado->fetch_all(MYSQLI_ASSOC);
            }
            
            // Si no hay resultados, verificar si hay docentes en la tabla
            error_log("No se encontraron docentes en la tabla");
            return [];
        } catch (\Exception $e) {
            // Log error if needed
            error_log("Error en getAllDocentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Crea la tabla docentes si no existe
     */
    private function crearTablaDocentes() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS docentes (
                codigo_docente VARCHAR(20) PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                correo VARCHAR(100) NOT NULL,
                area VARCHAR(50) NOT NULL,
                estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
                fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            $this->conn->query($sql);
            error_log("Tabla docentes creada correctamente");
            
            // Insertar algunos datos de ejemplo
            $this->insertarDatosEjemplo();
            
            return true;
        } catch (\Exception $e) {
            error_log("Error al crear la tabla docentes: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Inserta datos de ejemplo en la tabla docentes
     */
    private function insertarDatosEjemplo() {
        try {
            $datos = [
                [
                    'codigo_docente' => 'DOC001',
                    'nombre' => 'Pedro Rodríguez',
                    'correo' => 'pedro.rodriguez@escuela.edu.co',
                    'area' => 'Matemáticas',
                    'estado' => 'activo'
                ],
                [
                    'codigo_docente' => 'DOC002',
                    'nombre' => 'Ana Martínez',
                    'correo' => 'ana.martinez@escuela.edu.co',
                    'area' => 'Ciencias Naturales',
                    'estado' => 'activo'
                ],
                [
                    'codigo_docente' => 'DOC003',
                    'nombre' => 'Luis Gómez',
                    'correo' => 'luis.gomez@escuela.edu.co',
                    'area' => 'Lengua Castellana',
                    'estado' => 'activo'
                ]
            ];
            
            foreach ($datos as $docente) {
                $this->crearDocente($docente);
            }
            
            error_log("Datos de ejemplo insertados correctamente");
            return true;
        } catch (\Exception $e) {
            error_log("Error al insertar datos de ejemplo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea un nuevo docente
     * @param array $datos Datos del docente (codigo_docente, nombre, correo, area, estado)
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crearDocente($datos) {
        try {
            // Asegurarse de que el estado sea 'activo' por defecto si no se proporciona
            if (!isset($datos['estado']) || empty($datos['estado'])) {
                $datos['estado'] = 'activo';
            }
            
            $query = "INSERT INTO docentes (codigo_docente, nombre, correo, area, estado) 
                      VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssss", 
                $datos['codigo_docente'], 
                $datos['nombre'], 
                $datos['correo'], 
                $datos['area'], 
                $datos['estado']
            );
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error al crear docente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un docente existente
     * @param string $codigo_docente Código del docente
     * @param array $datos Datos a actualizar (nombre, correo, area, estado)
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarDocente($codigo_docente, $datos) {
        try {
            $query = "UPDATE docentes 
                      SET nombre = ?, correo = ?, area = ?, estado = ? 
                      WHERE codigo_docente = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssss", 
                $datos['nombre'], 
                $datos['correo'], 
                $datos['area'], 
                $datos['estado'],
                $codigo_docente
            );
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error al actualizar docente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un docente
     * @param string $codigo_docente Código del docente a eliminar
     * @return bool True si se eliminó correctamente, False en caso contrario
     */
    public function eliminarDocente($codigo_docente) {
        try {
            $query = "DELETE FROM docentes WHERE codigo_docente = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $codigo_docente);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error al eliminar docente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el total de docentes para la paginación
     * @param string $busqueda Término de búsqueda (opcional)
     * @return int Total de docentes
     */
    public function getTotalDocentes($busqueda = '') {
        try {
            $query = "SELECT COUNT(*) as total FROM docentes";
            $params = [];
            
            // Si hay un término de búsqueda, añadir la condición WHERE
            if (!empty($busqueda)) {
                $query .= " WHERE codigo_docente LIKE ? OR nombre LIKE ?";
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
            error_log("Error al obtener el total de docentes: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtiene docentes con paginación
     * @param int $offset Número de registros a omitir
     * @param int $limit Número máximo de registros a devolver
     * @param string $busqueda Término de búsqueda (opcional)
     * @return array Lista de docentes paginada
     */
    public function getDocentesPaginados($offset, $limit, $busqueda = '') {
        try {
            $query = "SELECT * FROM docentes";
            $params = [];
            
            // Si hay un término de búsqueda, añadir la condición WHERE
            if (!empty($busqueda)) {
                $query .= " WHERE codigo_docente LIKE ? OR nombre LIKE ?";
                $busquedaParam = "%{$busqueda}%";
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
            }
            
            $query .= " ORDER BY nombre LIMIT ?, ?";
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
            error_log("No se encontraron docentes en la página solicitada");
            return [];
        } catch (\Exception $e) {
            error_log("Error al obtener docentes paginados: " . $e->getMessage());
            return [];
        }
    }
}