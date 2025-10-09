<?php
// Configurar para evitar cualquier output no deseado
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en pantalla
ini_set('log_errors', 1);     // Registrar errores en log

// Limpiar cualquier output previo
ob_clean();

// Configurar headers para JSON
header('Content-Type: application/json');

session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['id_estudiante']) && !isset($_SESSION['cedula_docente'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que se haya enviado un archivo
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo válido']);
    exit;
}

$file = $_FILES['profile_image'];

// Validar tipo de archivo
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$fileType = $file['type'];

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se permiten JPG, PNG y GIF']);
    exit;
}

// Validar tamaño de archivo (máximo 2MB)
$maxSize = 2 * 1024 * 1024; // 2MB en bytes
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 2MB']);
    exit;
}

// Determinar el directorio de destino y el nombre del archivo
$uploadDir = 'views/admin/img/';

// Crear directorio si no existe
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear directorio de imágenes']);
        exit;
    }
}

// Determinar el nombre del archivo basado en el tipo de usuario
$fileName = '';
if (isset($_SESSION['admin_id'])) {
    // Para administradores, usar el nombre del admin
    $adminName = $_SESSION['admin_nombre'] ?? 'admin';
    $fileName = strtolower(str_replace(' ', '_', $adminName));
} elseif (isset($_SESSION['id_estudiante'])) {
    // Para estudiantes, usar el ID
    $fileName = 'estudiante_' . $_SESSION['id_estudiante'];
} elseif (isset($_SESSION['cedula_docente'])) {
    // Para docentes, usar la cédula
    $fileName = 'docente_' . $_SESSION['cedula_docente'];
}

// Obtener extensión del archivo
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$fileName .= '.' . $extension;

$targetPath = $uploadDir . $fileName;

// Eliminar archivo anterior si existe
if (file_exists($targetPath)) {
    unlink($targetPath);
}

// Mover el archivo subido
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Actualizar la base de datos si es necesario
    $imageUrl = '/Login/' . $targetPath;
    $dbUpdated = false;
    
    try {
        require_once 'config/Database.php';
        $database = new config\Database();
        $db = $database->getConnection();
        
        if (isset($_SESSION['admin_id'])) {
            // Verificar si la tabla administradores tiene la columna imagen_url
            $checkColumn = $db->query("SHOW COLUMNS FROM administradores LIKE 'imagen_url'");
            
            if ($checkColumn && $checkColumn->num_rows > 0) {
                // Actualizar imagen del administrador
                $sql = "UPDATE administradores SET imagen_url = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('si', $imageUrl, $_SESSION['admin_id']);
                    if ($stmt->execute()) {
                        // Actualizar la sesión
                        $_SESSION['admin_imagen'] = $imageUrl;
                        $dbUpdated = true;
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        // Log del error pero continuar
        error_log("Error al actualizar BD: " . $e->getMessage());
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => $dbUpdated ? 'Imagen subida y perfil actualizado correctamente' : 'Imagen subida correctamente',
        'image_url' => $imageUrl,
        'db_updated' => $dbUpdated
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']);
}
?>
