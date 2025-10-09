<?php
/**
 * Procesador simple de subida de imágenes de perfil
 */

// Configuración básica
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Función para responder y terminar
function respond($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    // Iniciar sesión
    session_start();
    
    // Verificar sesión de administrador
    if (!isset($_SESSION['admin_id'])) {
        respond(false, 'No hay sesión de administrador activa');
    }
    
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Método no permitido');
    }
    
    // Verificar archivo
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'No se recibió archivo válido');
    }
    
    $file = $_FILES['profile_image'];
    
    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        respond(false, 'Tipo de archivo no permitido. Use JPG, PNG o GIF');
    }
    
    // Validar tamaño (2MB máximo)
    if ($file['size'] > 2 * 1024 * 1024) {
        respond(false, 'Archivo muy grande. Máximo 2MB');
    }
    
    // Crear directorio si no existe
    $uploadDir = dirname(__DIR__) . '/img/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            respond(false, 'No se pudo crear directorio de imágenes');
        }
    }
    
    // Verificar permisos de escritura
    if (!is_writable($uploadDir)) {
        respond(false, 'Sin permisos de escritura en directorio');
    }
    
    // Generar nombre de archivo único
    $adminId = $_SESSION['admin_id'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (empty($extension)) {
        $extension = 'jpg'; // Por defecto
    }
    
    $fileName = 'admin_' . $adminId . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    // Eliminar imagen anterior del mismo admin
    $oldFiles = glob($uploadDir . 'admin_' . $adminId . '_*.*');
    foreach ($oldFiles as $oldFile) {
        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }
    
    // Mover archivo subido
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        respond(false, 'Error al guardar archivo');
    }
    
    // Generar URL de la imagen
    $imageUrl = '/Login/views/admin/img/' . $fileName;
    
    // Actualizar base de datos
    try {
        require_once dirname(dirname(dirname(__DIR__))) . '/config/config.php';
        $db = new config\Database();
        $conn = $db->getConnection();
        
        $sql = "UPDATE administradores SET imagen_url = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $imageUrl, $adminId);
        $stmt->execute();
        
        // Actualizar sesión
        $_SESSION['admin_imagen'] = $imageUrl;
        
        $dbUpdated = true;
    } catch (Exception $e) {
        // Log error pero no fallar la subida
        error_log("Error BD: " . $e->getMessage());
        $dbUpdated = false;
    }
    
    // Verificar que el archivo se guardó correctamente
    if (!file_exists($targetPath)) {
        respond(false, 'Error: archivo no se guardó correctamente');
    }
    
    // Log para debug
    error_log("UPLOAD SUCCESS: File saved at $targetPath, URL: $imageUrl, Session updated: " . ($_SESSION['admin_imagen'] ?? 'NO'));
    
    // Respuesta exitosa con información detallada
    respond(true, 'Imagen subida correctamente', [
        'image_url' => $imageUrl,
        'file_name' => $fileName,
        'file_size' => filesize($targetPath),
        'file_path' => $targetPath,
        'db_updated' => $dbUpdated,
        'session_updated' => isset($_SESSION['admin_imagen']),
        'admin_id' => $adminId,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    respond(false, 'Error interno del servidor');
}
?>
