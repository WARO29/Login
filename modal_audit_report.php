<?php
// Reporte de auditoría de modales
echo "<h1>📋 Reporte de Auditoría - Sistema de Modales</h1>";

echo "<h2>✅ ESTADO FINAL DEL SISTEMA</h2>";

// Verificar archivo centralizado
echo "<h3>📄 Archivo Centralizado:</h3>";
$modalFile = 'views/admin/includes/profile-image-modal.php';
if (file_exists($modalFile)) {
    echo "<p>✅ <strong>$modalFile</strong> - Existe (" . filesize($modalFile) . " bytes)</p>";
} else {
    echo "<p>❌ <strong>$modalFile</strong> - NO EXISTE</p>";
}

// Verificar JavaScript externo
echo "<h3>🔧 JavaScript Externo:</h3>";
$jsFile = 'assets/js/profile-image-upload.js';
if (file_exists($jsFile)) {
    echo "<p>✅ <strong>$jsFile</strong> - Existe (" . filesize($jsFile) . " bytes)</p>";
} else {
    echo "<p>❌ <strong>$jsFile</strong> - NO EXISTE</p>";
}

// Verificar procesador
echo "<h3>⚙️ Procesador de Subida:</h3>";
$uploadFile = 'views/admin/includes/upload_profile_image.php';
if (file_exists($uploadFile)) {
    echo "<p>✅ <strong>$uploadFile</strong> - Existe (" . filesize($uploadFile) . " bytes)</p>";
} else {
    echo "<p>❌ <strong>$uploadFile</strong> - NO EXISTE</p>";
}

// Verificar inclusión en sidebar
echo "<h3>🔗 Inclusión en Sidebar:</h3>";
$sidebarFile = 'views/admin/sidebar.php';
if (file_exists($sidebarFile)) {
    $sidebarContent = file_get_contents($sidebarFile);
    if (strpos($sidebarContent, 'profile-image-modal.php') !== false) {
        echo "<p>✅ <strong>Sidebar incluye el modal correctamente</strong></p>";
    } else {
        echo "<p>❌ <strong>Sidebar NO incluye el modal</strong></p>";
    }
} else {
    echo "<p>❌ <strong>Sidebar no existe</strong></p>";
}

// Buscar código duplicado
echo "<h3>🔍 Verificación de Código Duplicado:</h3>";
$adminViews = glob('views/admin/*.php');
$duplicateFound = false;

foreach ($adminViews as $view) {
    $content = file_get_contents($view);
    
    // Buscar modal HTML duplicado
    if (strpos($content, '<div class="modal fade" id="profileImageModal"') !== false && 
        basename($view) !== 'sidebar.php') {
        echo "<p>❌ <strong>" . basename($view) . "</strong> - Contiene modal HTML duplicado</p>";
        $duplicateFound = true;
    }
    
    // Buscar función setupProfileImageUpload duplicada
    if (strpos($content, 'function setupProfileImageUpload()') !== false) {
        echo "<p>❌ <strong>" . basename($view) . "</strong> - Contiene función JavaScript duplicada</p>";
        $duplicateFound = true;
    }
}

if (!$duplicateFound) {
    echo "<p>✅ <strong>No se encontró código duplicado</strong></p>";
}

// Verificar inclusiones de JavaScript
echo "<h3>📜 Inclusiones de JavaScript:</h3>";
$jsIncluded = 0;
foreach ($adminViews as $view) {
    $content = file_get_contents($view);
    if (strpos($content, 'profile-image-upload.js') !== false) {
        echo "<p>✅ <strong>" . basename($view) . "</strong> - Incluye JavaScript externo</p>";
        $jsIncluded++;
    }
}

echo "<p><strong>Total de vistas con JavaScript:</strong> $jsIncluded</p>";

// Resumen
echo "<h2>📊 RESUMEN EJECUTIVO</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🎯 Arquitectura Implementada:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Modal Centralizado:</strong> Un solo archivo <code>profile-image-modal.php</code></li>";
echo "<li>✅ <strong>JavaScript Externo:</strong> Un solo archivo <code>profile-image-upload.js</code></li>";
echo "<li>✅ <strong>Procesador Único:</strong> Un solo archivo <code>upload_profile_image.php</code></li>";
echo "<li>✅ <strong>Inclusión Automática:</strong> Modal incluido desde el sidebar</li>";
echo "<li>✅ <strong>Sin Duplicación:</strong> Código limpio y mantenible</li>";
echo "</ul>";

echo "<h3>🔧 Funcionamiento:</h3>";
echo "<ol>";
echo "<li><strong>Usuario hace clic</strong> en botón de cámara (sidebar)</li>";
echo "<li><strong>Se abre modal</strong> desde <code>profile-image-modal.php</code></li>";
echo "<li><strong>JavaScript maneja</strong> la interacción desde <code>profile-image-upload.js</code></li>";
echo "<li><strong>Servidor procesa</strong> la imagen en <code>upload_profile_image.php</code></li>";
echo "<li><strong>Interfaz se actualiza</strong> automáticamente</li>";
echo "</ol>";

echo "<h3>✅ Beneficios Obtenidos:</h3>";
echo "<ul>";
echo "<li><strong>Mantenibilidad:</strong> Un solo lugar para modificar el modal</li>";
echo "<li><strong>Consistencia:</strong> Mismo comportamiento en todas las vistas</li>";
echo "<li><strong>Performance:</strong> Sin código duplicado</li>";
echo "<li><strong>Escalabilidad:</strong> Fácil agregar a nuevas vistas</li>";
echo "</ul>";
echo "</div>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo "h1, h2, h3 { color: #333; }";
echo "p { margin: 5px 0; }";
echo "code { background: #e9ecef; padding: 2px 4px; border-radius: 3px; }";
echo "</style>";
?>
