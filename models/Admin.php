<?php
namespace models;

use config\Database;

class Admin {
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

    public function authenticate($usuario, $password) {
        try {
            $query = "SELECT id, usuario, nombre, password, imagen_url 
                     FROM administradores 
                     WHERE usuario = ? 
                     LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $admin = $resultado->fetch_assoc();
                
                // Verificar la contraseña usando password_verify si está hasheada
                // o una comparación directa si no lo está (solo para desarrollo)
                if (password_verify($password, $admin['password']) || $admin['password'] === $password) {
                    // No devolver el campo password en la respuesta
                    unset($admin['password']);
                    return $admin;
                }
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en authenticate: " . $e->getMessage());
            return false;
        }
    }

    public function getAllAdmins() {
        try {
            $query = "SELECT id, usuario, nombre, fecha_creacion, imagen_url 
                     FROM administradores 
                     ORDER BY nombre";
            $resultado = $this->conn->query($query);
            
            if ($resultado && $resultado->num_rows > 0) {
                return $resultado->fetch_all(MYSQLI_ASSOC);
            }
            return [];
        } catch (\Exception $e) {
            error_log("Error en getAllAdmins: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $query = "SELECT id, usuario, nombre, fecha_creacion, imagen_url 
                     FROM administradores 
                     WHERE id = ? 
                     LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                return $resultado->fetch_assoc();
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en getById: " . $e->getMessage());
            return false;
        }
    }

    public function updateProfileImage($adminId, $imageUrl) {
        try {
            $query = "UPDATE administradores SET imagen_url = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $imageUrl, $adminId);
            
            if ($stmt->execute()) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            error_log("Error en updateProfileImage: " . $e->getMessage());
            return false;
        }
    }
}