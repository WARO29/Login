<?php
// Script de prueba para verificar la funcionalidad de subida de imágenes
echo "<h1>🔧 Test Completo de Subida de Imágenes</h1>";

// Verificar directorio
$uploadDir = 'views/admin/img/';
echo "<h2>📁 Verificaciones de Directorio:</h2>";
echo "<p><strong>Directorio:</strong> " . $uploadDir . "</p>";
echo "<p><strong>Existe:</strong> " . (is_dir($uploadDir) ? "✅ Sí" : "❌ No") . "</p>";
echo "<p><strong>Escribible:</strong> " . (is_writable($uploadDir) ? "✅ Sí" : "❌ No") . "</p>";

// Verificar configuración PHP
echo "<h2>⚙️ Configuración PHP:</h2>";
echo "<p><strong>file_uploads:</strong> " . (ini_get('file_uploads') ? "✅ Habilitado" : "❌ Deshabilitado") . "</p>";
echo "<p><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</p>";

// Verificar sesión
session_start();
echo "<h2>👤 Sesión:</h2>";
echo "<p><strong>Admin ID:</strong> " . (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : "❌ No establecido") . "</p>";
echo "<p><strong>Admin Nombre:</strong> " . (isset($_SESSION['admin_nombre']) ? $_SESSION['admin_nombre'] : "❌ No establecido") . "</p>";

// Verificar archivos del sistema
echo "<h2>📄 Archivos del Sistema:</h2>";
$archivos_necesarios = [
    'views/admin/includes/upload_profile_image.php' => 'Procesador de imágenes (NUEVO)',
    'views/admin/includes/profile-image-modal.php' => 'Modal de interfaz',
    'assets/js/profile-image-upload.js' => 'JavaScript de conexión',
    'views/admin/sidebar.php' => 'Sidebar con botón'
];

foreach ($archivos_necesarios as $archivo => $descripcion) {
    $existe = file_exists($archivo);
    echo "<p><strong>$descripcion:</strong> " . ($existe ? "✅ $archivo" : "❌ $archivo (NO EXISTE)") . "</p>";
}

// Verificar URLs
echo "<h2>🌐 URLs de Prueba:</h2>";
echo "<p><strong>Panel Admin:</strong> <a href='/Login/admin/panel' target='_blank'>Abrir Panel</a></p>";
echo "<p><strong>Mesas Virtuales:</strong> <a href='/Login/admin/mesas-virtuales' target='_blank'>Abrir Mesas</a></p>";

// Formulario de prueba
echo "<h2>🧪 Formulario de Prueba Directo:</h2>";
?>
<form action="views/admin/includes/upload_profile_image.php" method="post" enctype="multipart/form-data">
    <div style="margin: 10px 0;">
        <label for="profile_image">Seleccionar imagen:</label>
        <input type="file" name="profile_image" id="profile_image" accept="image/*" required>
    </div>
    <div style="margin: 10px 0;">
        <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px;">
            Subir Imagen (Prueba Directa)
        </button>
    </div>
</form>

<h2>🎯 Instrucciones de Prueba:</h2>
<ol>
    <li><strong>Prueba del Modal:</strong> Ve al <a href="/Login/admin/mesas-virtuales" target="_blank">panel de mesas virtuales</a></li>
    <li><strong>Busca el botón azul</strong> con ícono de cámara en la esquina inferior derecha</li>
    <li><strong>Haz clic</strong> en el botón - debería abrir un modal</li>
    <li><strong>Selecciona una imagen</strong> y haz clic en "Subir imagen"</li>
    <li><strong>Prueba directa:</strong> Usa el formulario de arriba para probar sin modal</li>
</ol>

<h2>🔧 Soluciones si No Funciona:</h2>
<ul>
    <li><strong>Si no aparece el modal:</strong> Verifica que el JavaScript esté cargando</li>
    <li><strong>Si hay error 404:</strong> Verifica que el archivo upload_profile_image.php existe</li>
    <li><strong>Si hay error de permisos:</strong> Verifica que el directorio sea escribible</li>
    <li><strong>Si no hay sesión:</strong> Inicia sesión como administrador primero</li>
</ul>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1 { color: #333; }
h2 { color: #666; margin-top: 30px; }
p { margin: 5px 0; }
.success { color: green; }
.error { color: red; }
form { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
</style>
    </div>
    <div style="margin: 10px 0;">
        <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px;">
            Subir Imagen
        </button>
    </div>
</form>

<script>
// Test AJAX
function testAjax() {
    const formData = new FormData();
    const fileInput = document.getElementById('profile_image');
    
    if (fileInput.files.length === 0) {
        alert('Selecciona un archivo primero');
        return;
    }
    
    formData.append('profile_image', fileInput.files[0]);
    
    fetch('/Login/upload_profile_image_simple.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta:', data);
        alert('Resultado: ' + JSON.stringify(data));
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error);
    });
}
</script>

<button onclick="testAjax()" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; margin-left: 10px;">
    Test AJAX
</button>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
p { margin: 5px 0; }
</style>
<?php
echo "<p><strong>Archivo de prueba creado.</strong> Visita: <a href='/Login/test_upload.php'>/Login/test_upload.php</a></p>";
?>
