<?php
namespace controllers;

use config\Database;

class ProfileImageController {
    private $conn;
    private $upload_dir;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->upload_dir = __DIR__ . '/../views/admin/img/';
        
        // Crear el directorio si no existe
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    public function __destruct() {
        if ($this->conn) {
            $database = new Database();
            $database->closeConnection();
        }
    }
    
    public function uploadImage() {
        // Verificar si el usuario está autenticado como administrador
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            $this->returnJson(['success' => false, 'message' => 'Acceso no autorizado']);
            return;
        }
        
        // Verificar si se ha enviado un archivo
        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] != UPLOAD_ERR_OK) {
            $this->returnJson(['success' => false, 'message' => 'No se ha seleccionado ninguna imagen o ha ocurrido un error al subir el archivo']);
            return;
        }
        
        $file = $_FILES['profile_image'];
        
        // Validar el tipo de archivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!in_array($file['type'], $allowed_types)) {
            $this->returnJson(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG y GIF']);
            return;
        }
        
        // Validar el tamaño del archivo (máximo 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->returnJson(['success' => false, 'message' => 'El archivo es demasiado grande. El tamaño máximo permitido es 2MB']);
            return;
        }
        
        // Generar un nombre único para el archivo
        $admin_id = $_SESSION['admin_id'];
        $admin_nombre = $_SESSION['admin_nombre'];
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $admin_nombre . '.' . $extension;
        $filepath = $this->upload_dir . $filename;
        
        // Mover el archivo subido al directorio de destino
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Actualizar la ruta de la imagen en la base de datos
            $image_url = '/Login/views/admin/img/' . $filename;
            $query = "UPDATE administradores SET imagen_url = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $image_url, $admin_id);
            
            if ($stmt->execute()) {
                // Actualizar la sesión
                $_SESSION['admin_imagen'] = $image_url;
                
                $this->returnJson(['success' => true, 'message' => 'Imagen de perfil actualizada correctamente', 'image_url' => $image_url]);
            } else {
                $this->returnJson(['success' => false, 'message' => 'Error al actualizar la imagen en la base de datos: ' . $stmt->error]);
            }
        } else {
            $this->returnJson(['success' => false, 'message' => 'Error al guardar la imagen']);
        }
    }
    
    private function returnJson($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>
