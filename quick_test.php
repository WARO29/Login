<?php
session_start();
header('Content-Type: text/plain');

echo "=== DIAGNÓSTICO RÁPIDO ===\n";
echo "Sesión Admin ID: " . ($_SESSION['admin_id'] ?? 'NO') . "\n";
echo "Sesión Admin Imagen: " . ($_SESSION['admin_imagen'] ?? 'NO') . "\n";
echo "Modal existe: " . (file_exists('views/admin/includes/profile-modal.php') ? 'SÍ' : 'NO') . "\n";
echo "Handler existe: " . (file_exists('views/admin/includes/upload-handler.php') ? 'SÍ' : 'NO') . "\n";
echo "Directorio img existe: " . (is_dir('views/admin/img') ? 'SÍ' : 'NO') . "\n";
echo "Directorio escribible: " . (is_writable('views/admin/img') ? 'SÍ' : 'NO') . "\n";

if (is_dir('views/admin/img')) {
    $files = glob('views/admin/img/admin_*');
    echo "Archivos admin encontrados: " . count($files) . "\n";
    foreach ($files as $file) {
        echo "  - " . basename($file) . " (" . filesize($file) . " bytes)\n";
    }
}
?>
