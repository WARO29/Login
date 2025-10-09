<?php
// Test de la nueva implementación de cambio de foto de perfil
session_start();

echo "<h1>🆕 Test Nueva Implementación - Cambio de Foto de Perfil</h1>";

// Verificar sesión
echo "<h2>👤 Estado de Sesión:</h2>";
if (isset($_SESSION['admin_id'])) {
    echo "<p>✅ <strong>Admin ID:</strong> " . $_SESSION['admin_id'] . "</p>";
    echo "<p>✅ <strong>Admin Nombre:</strong> " . ($_SESSION['admin_nombre'] ?? 'No definido') . "</p>";
    echo "<p>✅ <strong>Imagen Actual:</strong> " . ($_SESSION['admin_imagen'] ?? 'Sin imagen') . "</p>";
} else {
    echo "<p>❌ <strong>No hay sesión de administrador</strong></p>";
    echo "<p>🔗 <a href='/Login/admin/login'>Ir a Login</a></p>";
}

// Verificar archivos de la nueva implementación
echo "<h2>📁 Archivos de la Nueva Implementación:</h2>";
$files = [
    'views/admin/includes/profile-modal.php' => 'Modal con JavaScript integrado',
    'views/admin/includes/upload-handler.php' => 'Procesador de subida',
    'views/admin/img/' => 'Directorio de imágenes'
];

foreach ($files as $file => $desc) {
    if (is_dir($file)) {
        $exists = is_dir($file);
        $writable = is_writable($file);
        echo "<p><strong>$desc:</strong> " . ($exists ? "✅ Existe" : "❌ No existe") . 
             ($exists && $writable ? " - ✅ Escribible" : " - ❌ No escribible") . "</p>";
    } else {
        $exists = file_exists($file);
        $size = $exists ? filesize($file) : 0;
        echo "<p><strong>$desc:</strong> " . ($exists ? "✅ Existe ($size bytes)" : "❌ No existe") . "</p>";
    }
}

// Test directo del procesador
echo "<h2>🧪 Test Directo del Procesador:</h2>";
?>
<div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <form id="testForm" enctype="multipart/form-data">
        <div style="margin: 10px 0;">
            <label for="testFile">Seleccionar imagen:</label>
            <input type="file" id="testFile" name="profile_image" accept="image/*" required>
        </div>
        <div style="margin: 10px 0;">
            <button type="button" onclick="testDirectUpload()" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px;">
                Test Directo
            </button>
        </div>
    </form>
    
    <div id="testResult" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; display: none;"></div>
</div>

<h2>🎯 Instrucciones de Prueba:</h2>
<ol>
    <li><strong>Test del Modal:</strong> Ve a cualquier vista administrativa</li>
    <li><strong>Busca el botón azul</strong> con ícono de cámara (esquina inferior derecha del perfil)</li>
    <li><strong>Haz clic</strong> en el botón - debería abrir el nuevo modal</li>
    <li><strong>Selecciona una imagen</strong> - debería mostrar vista previa</li>
    <li><strong>Haz clic "Subir Imagen"</strong> - debería procesar y actualizar automáticamente</li>
    <li><strong>Test directo:</strong> Usa el formulario de arriba para probar sin modal</li>
</ol>

<h2>✨ Características de la Nueva Implementación:</h2>
<ul>
    <li>✅ <strong>Todo en uno:</strong> Modal con JavaScript integrado</li>
    <li>✅ <strong>Sin dependencias externas:</strong> No requiere archivos JS adicionales</li>
    <li>✅ <strong>Vista previa inmediata:</strong> Muestra la imagen antes de subir</li>
    <li>✅ <strong>Validación en tiempo real:</strong> Verifica tamaño y tipo</li>
    <li>✅ <strong>Actualización automática:</strong> Cambia la imagen sin recargar</li>
    <li>✅ <strong>Manejo de errores robusto:</strong> Mensajes claros y específicos</li>
    <li>✅ <strong>Limpieza automática:</strong> Elimina imágenes anteriores</li>
</ul>

<script>
function testDirectUpload() {
    const fileInput = document.getElementById('testFile');
    const resultDiv = document.getElementById('testResult');
    
    if (fileInput.files.length === 0) {
        alert('Selecciona un archivo primero');
        return;
    }
    
    const formData = new FormData();
    formData.append('profile_image', fileInput.files[0]);
    
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '⏳ Subiendo archivo...';
    
    fetch('/Login/views/admin/includes/upload-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div style="color: green;">
                    <h4>✅ Subida Exitosa</h4>
                    <p><strong>Mensaje:</strong> ${data.message}</p>
                    <p><strong>URL:</strong> ${data.data.image_url}</p>
                    <p><strong>Archivo:</strong> ${data.data.file_name}</p>
                    <p><strong>Tamaño:</strong> ${data.data.file_size} bytes</p>
                    <p><strong>BD Actualizada:</strong> ${data.data.db_updated ? 'Sí' : 'No'}</p>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div style="color: red;">
                    <h4>❌ Error</h4>
                    <p>${data.message}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div style="color: red;">
                <h4>❌ Error de Conexión</h4>
                <p>${error.message}</p>
            </div>
        `;
    });
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1, h2 { color: #333; }
p { margin: 5px 0; }
ol, ul { margin-left: 20px; }
</style>
<?php
echo "<h2>🔗 Enlaces Rápidos:</h2>";
echo "<p><a href='/Login/admin/docentes' target='_blank'>🔗 Probar en Docentes</a></p>";
echo "<p><a href='/Login/admin/estudiantes' target='_blank'>🔗 Probar en Estudiantes</a></p>";
echo "<p><a href='/Login/admin/candidatos' target='_blank'>🔗 Probar en Candidatos</a></p>";
?>
