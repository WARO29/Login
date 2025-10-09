<?php
// Archivo de debug para subida de imágenes
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Limpiar buffer de salida
if (ob_get_level()) {
    ob_end_clean();
}

// Configurar headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    session_start();
    
    // Debug: Verificar sesión
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'No hay sesión de administrador',
            'debug' => [
                'session_id' => session_id(),
                'session_data' => array_keys($_SESSION)
            ]
        ]);
        exit;
    }
    
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    // Verificar archivo
    if (!isset($_FILES['profile_image'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'No se recibió archivo',
            'debug' => [
                'files' => array_keys($_FILES),
                'post' => array_keys($_POST)
            ]
        ]);
        exit;
    }
    
    $file = $_FILES['profile_image'];
    
    // Verificar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir archivo',
            UPLOAD_ERR_EXTENSION => 'Extensión de PHP detuvo la subida'
        ];
        
        echo json_encode([
            'success' => false, 
            'message' => 'Error de subida: ' . ($errors[$file['error']] ?? 'Error desconocido'),
            'debug' => [
                'error_code' => $file['error'],
                'file_info' => [
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'size' => $file['size'],
                    'tmp_name' => $file['tmp_name']
                ]
            ]
        ]);
        exit;
    }
    
    // Validaciones básicas
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tipo de archivo no permitido: ' . $file['type']
        ]);
        exit;
    }
    
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode([
            'success' => false, 
            'message' => 'Archivo demasiado grande: ' . round($file['size'] / 1024 / 1024, 2) . 'MB'
        ]);
        exit;
    }
    
    // Crear directorio si no existe
    $uploadDir = 'views/admin/img/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode([
                'success' => false, 
                'message' => 'No se pudo crear directorio: ' . $uploadDir
            ]);
            exit;
        }
    }
    
    // Generar nombre de archivo
    $adminName = $_SESSION['admin_nombre'] ?? 'admin';
    $fileName = strtolower(str_replace(' ', '_', $adminName));
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName .= '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    // Eliminar archivo anterior
    if (file_exists($targetPath)) {
        unlink($targetPath);
    }
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $imageUrl = '/Login/' . $targetPath;
        
        echo json_encode([
            'success' => true,
            'message' => 'Imagen subida correctamente',
            'image_url' => $imageUrl,
            'debug' => [
                'file_path' => $targetPath,
                'file_size' => filesize($targetPath),
                'admin_name' => $adminName,
                'session_admin_id' => $_SESSION['admin_id']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al mover archivo',
            'debug' => [
                'target_path' => $targetPath,
                'tmp_name' => $file['tmp_name'],
                'is_uploaded_file' => is_uploaded_file($file['tmp_name']),
                'target_dir_writable' => is_writable($uploadDir)
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fatal: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
