<?php
namespace models;

use config\Database;

class AdministrativoModel {
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

    /**
     * Obtiene un administrativo por su cédula
     * @param string $cedula Cédula del administrativo
     * @return array|false Datos del administrativo o false si no existe
     */
    public function getAdministrativoPorCedula($cedula) {
        try {
            $query = "SELECT * FROM administrativos WHERE cedula = ? AND estado = 'Activo' LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $cedula);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                return $resultado->fetch_assoc();
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en getAdministrativoPorCedula: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene un administrativo por su ID
     * @param int $id ID del administrativo
     * @return array|false Datos del administrativo o false si no existe
     */
    public function getAdministrativoPorId($id) {
        try {
            $query = "SELECT * FROM administrativos WHERE id_administrativo = ? AND estado = 'Activo' LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                return $resultado->fetch_assoc();
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en getAdministrativoPorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los administrativos activos
     * @return array Lista de administrativos
     */
    public function getAllAdministrativos() {
        try {
            $query = "SELECT * FROM administrativos WHERE estado = 'Activo' ORDER BY nombre";
            $resultado = $this->conn->query($query);
            
            if ($resultado && $resultado->num_rows > 0) {
                return $resultado->fetch_all(MYSQLI_ASSOC);
            }
            
            return [];
        } catch (\Exception $e) {
            error_log("Error en getAllAdministrativos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crea un nuevo administrativo
     * @param array $datos Datos del administrativo
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function crearAdministrativo($datos) {
        try {
            // Asegurarse de que el estado sea 'Activo' por defecto si no se proporciona
            if (!isset($datos['estado']) || empty($datos['estado'])) {
                $datos['estado'] = 'Activo';
            }
            
            $query = "INSERT INTO administrativos (nombre, apellido, correo, cedula, telefono, direccion, cargo, estado) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssssssss", 
                $datos['nombre'], 
                $datos['apellido'], 
                $datos['correo'], 
                $datos['cedula'], 
                $datos['telefono'], 
                $datos['direccion'], 
                $datos['cargo'], 
                $datos['estado']
            );
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error al crear administrativo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un administrativo existente
     * @param int $codigo_admin Código del administrativo
     * @param array $datos Datos a actualizar
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarAdministrativo($codigo_admin, $datos) {
        try {
            $query = "UPDATE administrativos 
                      SET nombre = ?, apellido = ?, correo = ?, cedula = ?, telefono = ?, direccion = ?, cargo = ?, estado = ? 
                      WHERE codigo_admin = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssssssssi", 
                $datos['nombre'], 
                $datos['apellido'], 
                $datos['correo'], 
                $datos['cedula'], 
                $datos['telefono'], 
                $datos['direccion'], 
                $datos['cargo'], 
                $datos['estado'],
                $codigo_admin
            );
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error al actualizar administrativo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene administrativos con paginación
     * @param int $limite Número de registros por página
     * @param int $offset Desplazamiento
     * @return array Lista de administrativos
     */
    public function getAllAdministrativosPaginados($limite, $offset) {
        try {
            $query = "SELECT * FROM administrativos WHERE estado = 'Activo' ORDER BY nombre, apellido LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $limite, $offset);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            return $resultado->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {
            error_log("Error en getAllAdministrativosPaginados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de administrativos activos
     * @return int Total de administrativos
     */
    public function contarAdministrativos() {
        try {
            $query = "SELECT COUNT(*) as total FROM administrativos WHERE estado = 'Activo'";
            $resultado = $this->conn->query($query);
            
            if ($resultado) {
                $fila = $resultado->fetch_assoc();
                return (int)$fila['total'];
            }
            
            return 0;
        } catch (\Exception $e) {
            error_log("Error en contarAdministrativos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Busca administrativos por término de búsqueda
     * @param string $busqueda Término de búsqueda
     * @param int $limite Número de registros por página
     * @param int $offset Desplazamiento
     * @return array Lista de administrativos encontrados
     */
    public function buscarAdministrativos($busqueda, $limite, $offset) {
        try {
            $termino = "%{$busqueda}%";
            $query = "SELECT * FROM administrativos
                     WHERE estado = 'Activo'
                     AND (cedula LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR correo LIKE ? OR cargo LIKE ?)
                     ORDER BY nombre, apellido
                     LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssssii", $termino, $termino, $termino, $termino, $termino, $limite, $offset);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            return $resultado->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {
            error_log("Error en buscarAdministrativos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta administrativos que coinciden con la búsqueda
     * @param string $busqueda Término de búsqueda
     * @return int Total de administrativos encontrados
     */
    public function contarAdministrativosBusqueda($busqueda) {
        try {
            $termino = "%{$busqueda}%";
            $query = "SELECT COUNT(*) as total FROM administrativos
                     WHERE estado = 'Activo'
                     AND (cedula LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR correo LIKE ? OR cargo LIKE ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssss", $termino, $termino, $termino, $termino, $termino);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado) {
                $fila = $resultado->fetch_assoc();
                return (int)$fila['total'];
            }
            
            return 0;
        } catch (\Exception $e) {
            error_log("Error en contarAdministrativosBusqueda: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualiza un administrativo por su cédula original
     * @param string $cedula_original Cédula original del administrativo
     * @param array $datos Datos a actualizar
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualizarAdministrativoPorCedula($cedula_original, $datos) {
        try {
            $query = "UPDATE administrativos
                     SET cedula = ?, nombre = ?, apellido = ?, correo = ?, cargo = ?, telefono = ?, direccion = ?, estado = ?
                     WHERE cedula = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssssssss",
                $datos['cedula'],
                $datos['nombre'],
                $datos['apellido'],
                $datos['correo'],
                $datos['cargo'],
                $datos['telefono'],
                $datos['direccion'],
                $datos['estado'],
                $cedula_original
            );
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error al actualizar administrativo por cédula: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un administrativo
     * @param int $codigo_admin Código del administrativo a eliminar
     * @return bool True si se eliminó correctamente, False en caso contrario
     */
    public function eliminarAdministrativo($codigo_admin) {
        try {
            $query = "DELETE FROM administrativos WHERE codigo_admin = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $codigo_admin);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error al eliminar administrativo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el total de administrativos para la paginación
     * @param string $busqueda Término de búsqueda (opcional)
     * @return int Total de administrativos
     */
    public function getTotalAdministrativos($busqueda = '') {
        try {
            $query = "SELECT COUNT(*) as total FROM administrativos WHERE estado = 'Activo'";
            $params = [];
            
            // Si hay un término de búsqueda, añadir la condición WHERE
            if (!empty($busqueda)) {
                $query .= " AND (cedula LIKE ? OR nombre LIKE ? OR apellido LIKE ?)";
                $busquedaParam = "%{$busqueda}%";
                $params[] = $busquedaParam;
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
            error_log("Error al obtener el total de administrativos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtiene administrativos con paginación
     * @param int $offset Número de registros a omitir
     * @param int $limit Número máximo de registros a devolver
     * @param string $busqueda Término de búsqueda (opcional)
     * @return array Lista de administrativos paginada
     */
    public function getAdministrativosPaginados($offset, $limit, $busqueda = '') {
        try {
            $query = "SELECT * FROM administrativos WHERE estado = 'Activo'";
            $params = [];
            
            // Si hay un término de búsqueda, añadir la condición WHERE
            if (!empty($busqueda)) {
                $query .= " AND (cedula LIKE ? OR nombre LIKE ? OR apellido LIKE ?)";
                $busquedaParam = "%{$busqueda}%";
                $params[] = $busquedaParam;
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
                $stmt->bind_param("sssii", $params[0], $params[1], $params[2], $params[3], $params[4]);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            
            return [];
        } catch (\Exception $e) {
            error_log("Error al obtener administrativos paginados: " . $e->getMessage());
            return [];
        }
    }
}