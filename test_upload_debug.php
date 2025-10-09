<?php
// Test de debug para subida de imágenes
session_start();

echo "<h1>🔧 Test de Debug - Subida de Imágenes</h1>";

// Verificar sesión
echo "<h2>👤 Estado de Sesión:</h2>";
if (isset($_SESSION['admin_id'])) {
    echo "<p>✅ <strong>Admin ID:</strong> " . $_SESSION['admin_id'] . "</p>";
    echo "<p>✅ <strong>Admin Nombre:</strong> " . ($_SESSION['admin_nombre'] ?? 'No definido') . "</p>";
} else {
    echo "<p>❌ <strong>No hay sesión de administrador</strong></p>";
    echo "<p>🔗 <a href='/Login/admin/login'>Ir a Login</a></p>";
}

// Verificar archivos
echo "<h2>📁 Verificación de Archivos:</h2>";
$files = [
    'views/admin/includes/upload_profile_image.php' => 'Procesador principal',
    'views/admin/includes/profile-image-modal.php' => 'Modal HTML',
    'assets/js/profile-image-upload.js' => 'JavaScript',
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
        echo "<p><strong>$desc:</strong> " . ($exists ? "✅ Existe" : "❌ No existe") . "</p>";
    }
}

// Test AJAX directo
?>
<h2>🔄 Forzar Actualización de Imagen:</h2>
<button onclick="forceRefreshImage()" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; margin-bottom: 20px;">
    Forzar Actualización de Imagen
</button>
<div id="refreshResult" style="margin-bottom: 20px; padding: 10px; display: none;"></div>

<h2>🧪 Test AJAX Directo:</h2>
<div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <form id="testForm" enctype="multipart/form-data">
        <div style="margin: 10px 0;">
            <label for="testFile">Seleccionar imagen:</label>
            <input type="file" id="testFile" name="profile_image" accept="image/*" required>
        </div>
        <div style="margin: 10px 0;">
            <button type="button" onclick="testUpload()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px;">
                Test Upload
            </button>
        </div>
    </form>
    
    <div id="testResult" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; display: none;"></div>
</div>

<h2>📋 Logs en Tiempo Real:</h2>
<div id="logs" style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
    <p>Esperando actividad...</p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function testUpload() {
    const fileInput = document.getElementById('testFile');
    if (fileInput.files.length === 0) {
        alert('Selecciona un archivo primero');
        return;
    }
    
    const formData = new FormData();
    formData.append('profile_image', fileInput.files[0]);
    
    // Log en la página
    addLog('🚀 Iniciando test de subida...');
    addLog('📄 Archivo: ' + fileInput.files[0].name + ' (' + fileInput.files[0].size + ' bytes)');
    
    $.ajax({
        url: '/Login/views/admin/includes/upload_profile_image.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('#testResult').show().html('⏳ Subiendo archivo...');
            addLog('📡 Enviando petición AJAX...');
        },
        success: function(response) {
            addLog('✅ Respuesta recibida: ' + JSON.stringify(response, null, 2));
            $('#testResult').html('<pre style="color: green;">' + JSON.stringify(response, null, 2) + '</pre>');
        },
        error: function(xhr, status, error) {
            addLog('❌ Error AJAX: ' + error);
            addLog('📄 Status: ' + status);
            addLog('🔍 Response Text: ' + xhr.responseText);
            $('#testResult').html('<pre style="color: red;">Error: ' + error + '\n\nStatus: ' + status + '\n\nResponse: ' + xhr.responseText + '</pre>');
        },
        complete: function() {
            addLog('🏁 Petición completada');
        }
    });
}

function addLog(message) {
    const timestamp = new Date().toLocaleTimeString();
    const logDiv = document.getElementById('logs');
    if (logDiv.innerHTML.includes('Esperando actividad')) {
        logDiv.innerHTML = '';
    }
    logDiv.innerHTML += '<div>[' + timestamp + '] ' + message + '</div>';
    logDiv.scrollTop = logDiv.scrollHeight;
}

function forceRefreshImage() {
    addLog('🔄 Forzando actualización de imagen...');
    
    $.ajax({
        url: '/Login/force_refresh_image.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            addLog('✅ Respuesta de actualización: ' + JSON.stringify(response));
            if (response.success) {
                $('#refreshResult').show().html('<div style="color: green;">✅ ' + response.message + '</div>');
                addLog('🖼️ Imagen actualizada: ' + response.image_url);
            } else {
                $('#refreshResult').show().html('<div style="color: red;">❌ ' + response.message + '</div>');
            }
        },
        error: function(xhr, status, error) {
            addLog('❌ Error en actualización: ' + error);
            $('#refreshResult').show().html('<div style="color: red;">❌ Error: ' + error + '</div>');
        }
    });
}

// Auto-refresh de logs cada 2 segundos
setInterval(function() {
    // Aquí podrías hacer una petición para obtener logs del servidor
}, 2000);
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1, h2 { color: #333; }
p { margin: 5px 0; }
</style>
<?php
// Mostrar logs de error recientes si existen
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    echo "<h2>📄 Logs de Error Recientes:</h2>";
    $lines = file($errorLog);
    $recentLines = array_slice($lines, -10);
    echo "<pre style='background: #f8f9fa; padding: 10px; font-size: 12px; max-height: 200px; overflow-y: auto;'>";
    foreach ($recentLines as $line) {
        if (strpos($line, 'UPLOAD DEBUG') !== false || strpos($line, 'upload_profile_image') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
}
?>
