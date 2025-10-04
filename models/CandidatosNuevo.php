<?php

namespace models;

use mysqli;
use config\Database;

/**
 * Modelo Candidatos - Versión completamente nueva y limpia
 * Maneja todas las operaciones CRUD para candidatos del sistema de votación
 */
class CandidatosNuevo {
    private $conn;
    private $tabla = 'candidatos';

    public function __construct($connection = null) {
        if ($connection === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $connection;
        }
        
        if (!$this->conn) {
            throw new \Exception("Error: No se pudo establecer conexión con la base de datos");
        }
    }

    /**
     * Obtiene todos los candidatos con paginación y búsqueda
     * @param int $offset Desplazamiento para paginación
     * @param int $limit Límite de registros por página
     * @param string $busqueda Término de búsqueda (opcional)
     * @param string $tipo_filtro Filtro por tipo de candidato (opcional)
     * @return array Lista de candidatos
     */
    public function obtenerCandidatos($offset = 0, $limit = 10, $busqueda = '', $tipo_filtro = '') {
        try {
            $sql = "SELECT * FROM {$this->tabla} WHERE 1=1";
            $params = [];
            $types = '';

            // Aplicar filtro de búsqueda
            if (!empty($busqueda)) {
                $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR numero LIKE ? OR propuesta LIKE ?)";
                $busqueda_param = "%{$busqueda}%";
                $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param, $busqueda_param]);
                $types .= 'ssss';
            }

            // Aplicar filtro por tipo de candidato
            if (!empty($tipo_filtro) && in_array(strtoupper($tipo_filtro), ['PERSONERO', 'REPRESENTANTE'])) {
                $sql .= " AND tipo_candidato = ?";
                $params[] = strtoupper($tipo_filtro);
                $types .= 's';
            }

            $sql .= " ORDER BY tipo_candidato ASC, grado ASC, nombre ASC LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $limit;
            $types .= 'ii';

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Error preparando consulta: " . $this->conn->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $resultado = $stmt->get_result();
            
            return $resultado->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {
            error_log("Error en obtenerCandidatos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de candidatos (con filtros opcionales)
     * @param string $busqueda Término de búsqueda (opcional)
     * @param string $tipo_filtro Filtro por tipo de candidato (opcional)
     * @return int Total de candidatos
     */
    public function contarCandidatos($busqueda = '', $tipo_filtro = '') {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->tabla} WHERE 1=1";
            $params = [];
            $types = '';

            // Aplicar filtro de búsqueda
            if (!empty($busqueda)) {
                $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR numero LIKE ? OR propuesta LIKE ?)";
                $busqueda_param = "%{$busqueda}%";
                $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param, $busqueda_param]);
                $types .= 'ssss';
            }

            // Aplicar filtro por tipo de candidato
            if (!empty($tipo_filtro) && in_array(strtoupper($tipo_filtro), ['PERSONERO', 'REPRESENTANTE'])) {
                $sql .= " AND tipo_candidato = ?";
                $params[] = strtoupper($tipo_filtro);
                $types .= 's';
            }

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Error preparando consulta: " . $this->conn->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_assoc();
            
            return (int)($resultado['total'] ?? 0);
        } catch (\Exception $e) {
            error_log("Error en contarCandidatos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene un candidato por su ID
     * @param int $id ID del candidato
     * @return array|null Datos del candidato o null si no existe
     */
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM {$this->tabla} WHERE id_candidato = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                throw new \Exception("Error preparando consulta: " . $this->conn->error);
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                return $resultado->fetch_assoc();
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Error en obtenerPorId: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un nuevo candidato
     * @param array $datos Datos del candidato
     * @return bool|int ID del candidato creado o false si falla
     */
    public function crear($datos) {
        try {
            // Validar datos obligatorios
            $this->validarDatos($datos);

            // Verificar que el número no esté duplicado
            if ($this->existeNumero($datos['numero'], $datos['tipo_candidato'], $datos['grado'] ?? null)) {
                throw new \Exception("El número de tarjetón ya está en uso para este tipo de candidato");
            }

            $sql = "INSERT INTO {$this->tabla} (nombre, apellido, numero, tipo_candidato, grado, foto, propuesta) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                throw new \Exception("Error preparando consulta: " . $this->conn->error);
            }

            // Preparar datos
            $nombre = trim($datos['nombre']);
            $apellido = trim($datos['apellido'] ?? '');
            $numero = trim($datos['numero']);
            $tipo_candidato = strtoupper(trim($datos['tipo_candidato']));
            $grado = !empty($datos['grado']) ? (int)$datos['grado'] : null;
            $foto = trim($datos['foto'] ?? '');
            $propuesta = trim($datos['propuesta'] ?? '');

            $stmt->bind_param("ssssiss", $nombre, $apellido, $numero, $tipo_candidato, $grado, $foto, $propuesta);
            
            if ($stmt->execute()) {
                return $this->conn->insert_id;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Error en crear candidato: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza un candidato existente
     * @param int $id ID del candidato
     * @param array $datos Nuevos datos del candidato
     * @return bool True si se actualizó correctamente
     */
    public function actualizar($id, $datos) {
        try {
            // Validar datos obligatorios
            $this->validarDatos($datos);

            // Verificar que el número no esté duplicado (excluyendo el candidato actual)
            if ($this->existeNumero($datos['numero'], $datos['tipo_candidato'], $datos['grado'] ?? null, $id)) {
                throw new \Exception("El número de tarjetón ya está en uso para este tipo de candidato");
            }

            $sql = "UPDATE {$this->tabla} SET nombre = ?, apellido = ?, numero = ?, tipo_candidato = ?, grado = ?, foto = ?, propuesta = ? WHERE id_candidato = ?";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                throw new \Exception("Error preparando consulta: " . $this->conn->error);
            }

            // Preparar datos
            $nombre = trim($datos['nombre']);
            $apellido = trim($datos['apellido'] ?? '');
            $numero = trim($datos['numero']);
            $tipo_candidato = strtoupper(trim($datos['tipo_candidato']));
            $grado = !empty($datos['grado']) ? (int)$datos['grado'] : null;
            $foto = trim($datos['foto'] ?? '');
            $propuesta = trim($datos['propuesta'] ?? '');

            $stmt->bind_param("ssssissi", $nombre, $apellido, $numero, $tipo_candidato, $grado, $foto, $propuesta, $id);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error en actualizar candidato: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina un candidato
     * @param int $id ID del candidato
     * @return bool True si se eliminó correctamente
     */
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM {$this->tabla} WHERE id_candidato = ?";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                throw new \Exception("Error preparando consulta: " . $this->conn->error);
            }

            $stmt->bind_param("i", $id);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error en eliminar candidato: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene candidatos por tipo
     * @param string $tipo Tipo de candidato (PERSONERO o REPRESENTANTE)
     * @param int|null $grado Grado específico (solo para representantes)
     * @return array Lista de candidatos
     */
    public function obtenerPorTipo($tipo, $grado = null) {
        try {
            $sql = "SELECT * FROM {$this->tabla} WHERE tipo_candidato = ?";
            $params = [strtoupper($tipo)];
            $types = 's';

            if ($grado !== null && $tipo === 'REPRESENTANTE') {
                $sql .= " AND grado = ?";
                $params[] = (int)$grado;
                $types .= 'i';
            }

            $sql .= " ORDER BY nombre ASC";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Error preparando consulta: " . $this->conn->error);
            }

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            return $resultado->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {
            error_log("Error en obtenerPorTipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un número de tarjetón ya existe
     * @param string $numero Número del tarjetón
     * @param string $tipo_candidato Tipo de candidato
     * @param int|null $grado Grado (para representantes)
     * @param int|null $excluir_id ID a excluir de la búsqueda
     * @return bool True si existe, false si está disponible
     */
    public function existeNumero($numero, $tipo_candidato, $grado = null, $excluir_id = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->tabla} WHERE numero = ? AND tipo_candidato = ?";
            $params = [trim($numero), strtoupper($tipo_candidato)];
            $types = 'ss';

            // Para representantes, también verificar el grado
            if (strtoupper($tipo_candidato) === 'REPRESENTANTE' && $grado !== null) {
                $sql .= " AND grado = ?";
                $params[] = (int)$grado;
                $types .= 'i';
            }

            // Excluir un ID específico (para actualizaciones)
            if ($excluir_id !== null) {
                $sql .= " AND id_candidato != ?";
                $params[] = (int)$excluir_id;
                $types .= 'i';
            }

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Error preparando consulta: " . $this->conn->error);
            }

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_assoc();
            
            return (int)$resultado['total'] > 0;
        } catch (\Exception $e) {
            error_log("Error en existeNumero: " . $e->getMessage());
            return true; // En caso de error, asumir que existe para evitar duplicados
        }
    }

    /**
     * Obtiene información del candidato que tiene un número específico
     * @param string $numero Número del tarjetón
     * @param string $tipo_candidato Tipo de candidato
     * @param int|null $grado Grado (para representantes)
     * @param int|null $excluir_id ID a excluir de la búsqueda
     * @return array|null Información del candidato conflictivo
     */
    public function obtenerPorNumero($numero, $tipo_candidato, $grado = null, $excluir_id = null) {
        try {
            $sql = "SELECT * FROM {$this->tabla} WHERE numero = ? AND tipo_candidato = ?";
            $params = [trim($numero), strtoupper($tipo_candidato)];
            $types = 'ss';

            // Para representantes, también verificar el grado
            if (strtoupper($tipo_candidato) === 'REPRESENTANTE' && $grado !== null) {
                $sql .= " AND grado = ?";
                $params[] = (int)$grado;
                $types .= 'i';
            }

            // Excluir un ID específico (para actualizaciones)
            if ($excluir_id !== null) {
                $sql .= " AND id_candidato != ?";
                $params[] = (int)$excluir_id;
                $types .= 'i';
            }

            $sql .= " LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Error preparando consulta: " . $this->conn->error);
            }

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                return $resultado->fetch_assoc();
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Error en obtenerPorNumero: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Valida los datos del candidato
     * @param array $datos Datos a validar
     * @throws \Exception Si los datos no son válidos
     */
    private function validarDatos($datos) {
        // Validar campos obligatorios
        if (empty($datos['nombre'])) {
            throw new \Exception("El nombre es obligatorio");
        }

        if (empty($datos['numero'])) {
            throw new \Exception("El número de tarjetón es obligatorio");
        }

        if (empty($datos['tipo_candidato'])) {
            throw new \Exception("El tipo de candidato es obligatorio");
        }

        // Validar tipo de candidato
        $tipo_normalizado = strtoupper(trim($datos['tipo_candidato']));
        if (!in_array($tipo_normalizado, ['PERSONERO', 'REPRESENTANTE'])) {
            throw new \Exception("El tipo de candidato debe ser 'PERSONERO' o 'REPRESENTANTE'");
        }

        // Validar grado para representantes
        if ($tipo_normalizado === 'REPRESENTANTE') {
            if (empty($datos['grado'])) {
                throw new \Exception("El grado es obligatorio para candidatos a representante");
            }
            
            $grado = (int)$datos['grado'];
            if ($grado < 6 || $grado > 11) {
                throw new \Exception("El grado debe estar entre 6 y 11");
            }
        }

        // Validar grado para personeros (opcional pero si se proporciona debe ser válido)
        if ($tipo_normalizado === 'PERSONERO' && !empty($datos['grado'])) {
            $grado = (int)$datos['grado'];
            if ($grado < 6 || $grado > 11) {
                throw new \Exception("Si se especifica un grado, debe estar entre 6 y 11");
            }
        }

        // Validar longitud del número
        if (strlen(trim($datos['numero'])) > 10) {
            throw new \Exception("El número de tarjetón no puede tener más de 10 caracteres");
        }

        // Validar longitud del nombre
        if (strlen(trim($datos['nombre'])) > 100) {
            throw new \Exception("El nombre no puede tener más de 100 caracteres");
        }

        // Validar longitud del apellido
        if (!empty($datos['apellido']) && strlen(trim($datos['apellido'])) > 100) {
            throw new \Exception("El apellido no puede tener más de 100 caracteres");
        }
    }

    /**
     * Obtiene estadísticas de candidatos
     * @return array Estadísticas
     */
    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN tipo_candidato = 'PERSONERO' THEN 1 ELSE 0 END) as personeros,
                        SUM(CASE WHEN tipo_candidato = 'REPRESENTANTE' THEN 1 ELSE 0 END) as representantes,
                        COUNT(DISTINCT grado) as grados_con_candidatos
                    FROM {$this->tabla}";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Error preparando consulta: " . $this->conn->error);
            }

            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_assoc();
            
            return [
                'total' => (int)$resultado['total'],
                'personeros' => (int)$resultado['personeros'],
                'representantes' => (int)$resultado['representantes'],
                'grados_con_candidatos' => (int)$resultado['grados_con_candidatos']
            ];
        } catch (\Exception $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
            return [
                'total' => 0,
                'personeros' => 0,
                'representantes' => 0,
                'grados_con_candidatos' => 0
            ];
        }
    }
}