<?php
// Script para forzar la actualización de la imagen de perfil
session_start();

// Verificar sesión
if (!isset($_SESSION['admin_id'])) {
    die('No hay sesión de administrador');
}

// Buscar la imagen más reciente del administrador
$imgDir = 'views/admin/img/';
$adminId = $_SESSION['admin_id'];
$adminName = strtolower(str_replace([' ', '.', '-'], '_', $_SESSION['admin_nombre'] ?? 'admin'));

// Posibles nombres de archivo
$possibleFiles = [
    "admin_{$adminId}.jpg",
    "admin_{$adminId}.png",
    "admin_{$adminId}.gif",
    "{$adminName}.jpg",
    "{$adminName}.png",
    "{$adminName}.gif",
    "administrador_principal.jpg",
    "administrador_principal.png",
    "administrador_principal.gif"
];

$foundImage = null;
foreach ($possibleFiles as $filename) {
    $filepath = $imgDir . $filename;
    if (file_exists($filepath)) {
        $foundImage = '/Login/views/admin/img/' . $filename;
        break;
    }
}

if ($foundImage) {
    // Actualizar sesión
    $_SESSION['admin_imagen'] = $foundImage;
    
    // Actualizar base de datos
    try {
        require_once 'config/config.php';
        $db = new config\Database();
        $conn = $db->getConnection();
        
        $sql = "UPDATE administradores SET imagen_url = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $foundImage, $adminId);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Imagen actualizada correctamente',
            'image_url' => $foundImage,
            'admin_id' => $adminId
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar base de datos: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se encontró imagen para el administrador',
        'searched_files' => $possibleFiles
    ]);
}
?>
