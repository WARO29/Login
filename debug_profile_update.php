<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Profile Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h1>🔍 Debug Actualización de Imagen de Perfil</h1>
        
        <!-- Simulación del sidebar -->
        <div class="row">
            <div class="col-md-6">
                <h3>🎯 Simulación del Sidebar</h3>
                <div class="bg-dark text-white p-4 rounded">
                    <div class="text-center mb-3">
                        <div class="position-relative d-inline-block">
                            <?php if (isset($_SESSION['admin_imagen']) && !empty($_SESSION['admin_imagen'])): ?>
                                <img id="sidebar-profile-image" src="<?= $_SESSION['admin_imagen'] ?>?v=<?= time() ?>" 
                                     alt="<?= htmlspecialchars($_SESSION['admin_nombre'] ?? 'Administrador') ?>" 
                                     class="rounded-circle img-fluid mb-2 profile-img-main" 
                                     style="width: 80px; height: 80px; object-fit: cover;">
                            <?php else: ?>
                                <i id="profile-icon" class="fas fa-user-circle fa-3x text-white-50"></i>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-sm btn-primary position-absolute" 
                                    style="bottom: 10px; right: -5px; border-radius: 50%; width: 25px; height: 25px; padding: 0;" 
                                    data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="fas fa-camera" style="font-size: 12px;"></i>
                            </button>
                        </div>
                        <div class="text-white-50 small"><?= htmlspecialchars($_SESSION['admin_nombre'] ?? 'Administrador') ?></div>
                        <small class="text-white-50">Administrador</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <h3>📊 Estado Actual</h3>
                <div class="bg-white p-3 rounded border">
                    <p><strong>Admin ID:</strong> <?= $_SESSION['admin_id'] ?? 'No definido' ?></p>
                    <p><strong>Admin Nombre:</strong> <?= $_SESSION['admin_nombre'] ?? 'No definido' ?></p>
                    <p><strong>Imagen Actual:</strong> <?= $_SESSION['admin_imagen'] ?? 'Sin imagen' ?></p>
                    
                    <?php if (isset($_SESSION['admin_imagen'])): ?>
                        <p><strong>Archivo existe:</strong> 
                            <?= file_exists('.' . $_SESSION['admin_imagen']) ? '✅ Sí' : '❌ No' ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <h4 class="mt-3">🔧 Controles de Debug</h4>
                <button onclick="debugElements()" class="btn btn-info btn-sm">Inspeccionar Elementos</button>
                <button onclick="testUpdate()" class="btn btn-warning btn-sm">Test Actualización</button>
                <button onclick="clearCache()" class="btn btn-secondary btn-sm">Limpiar Cache</button>
            </div>
        </div>
        
        <!-- Log de debug -->
        <div class="row mt-4">
            <div class="col-12">
                <h3>📋 Log de Debug</h3>
                <div id="debugLog" class="bg-dark text-light p-3 rounded" style="height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                    <div>Esperando actividad...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal incluido -->
    <?php include 'views/admin/includes/profile-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Funciones de debug
    function log(message, type = 'info') {
        const logDiv = document.getElementById('debugLog');
        const timestamp = new Date().toLocaleTimeString();
        const colors = {
            info: '#17a2b8',
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107'
        };
        
        if (logDiv.innerHTML.includes('Esperando actividad')) {
            logDiv.innerHTML = '';
        }
        
        logDiv.innerHTML += `<div style="color: ${colors[type] || colors.info}">[${timestamp}] ${message}</div>`;
        logDiv.scrollTop = logDiv.scrollHeight;
    }
    
    function debugElements() {
        log('🔍 Inspeccionando elementos del DOM...', 'info');
        
        // Verificar imagen del sidebar
        const sidebarImg = document.getElementById('sidebar-profile-image');
        if (sidebarImg) {
            log(`✅ Imagen del sidebar encontrada: ${sidebarImg.src}`, 'success');
            log(`   - ID: ${sidebarImg.id}`, 'info');
            log(`   - Clases: ${sidebarImg.className}`, 'info');
            log(`   - Dimensiones: ${sidebarImg.width}x${sidebarImg.height}`, 'info');
        } else {
            log('❌ Imagen del sidebar NO encontrada', 'error');
        }
        
        // Verificar ícono
        const profileIcon = document.getElementById('profile-icon');
        if (profileIcon) {
            log(`✅ Ícono de perfil encontrado: ${profileIcon.className}`, 'success');
        } else {
            log('❌ Ícono de perfil NO encontrado', 'error');
        }
        
        // Verificar todas las imágenes con clases específicas
        const allProfileImgs = document.querySelectorAll('.profile-img-main, .profile-img-sm');
        log(`📊 Total de imágenes de perfil encontradas: ${allProfileImgs.length}`, 'info');
        
        allProfileImgs.forEach((img, index) => {
            log(`   ${index + 1}. ${img.tagName} - ID: ${img.id || 'sin ID'} - Clases: ${img.className}`, 'info');
        });
    }
    
    function testUpdate() {
        log('🧪 Probando actualización manual...', 'warning');
        
        const testUrl = '/Login/views/admin/img/test_image.jpg?v=' + Date.now();
        
        // Intentar actualizar imagen existente
        const sidebarImg = document.getElementById('sidebar-profile-image');
        if (sidebarImg) {
            const oldSrc = sidebarImg.src;
            sidebarImg.src = testUrl;
            log(`🔄 Imagen actualizada de: ${oldSrc}`, 'info');
            log(`🔄 Imagen actualizada a: ${testUrl}`, 'success');
        }
        
        // Intentar reemplazar ícono
        const profileIcon = document.getElementById('profile-icon');
        if (profileIcon) {
            const img = document.createElement('img');
            img.id = 'sidebar-profile-image';
            img.src = testUrl;
            img.alt = 'Imagen de perfil';
            img.className = 'rounded-circle img-fluid mb-2 profile-img-main';
            img.style.cssText = 'width: 80px; height: 80px; object-fit: cover;';
            profileIcon.replaceWith(img);
            log('🔄 Ícono reemplazado por imagen', 'success');
        }
    }
    
    function clearCache() {
        log('🧹 Limpiando cache...', 'info');
        
        // Forzar recarga de todas las imágenes
        document.querySelectorAll('img').forEach(img => {
            if (img.src.includes('admin_')) {
                const newSrc = img.src.split('?')[0] + '?v=' + Date.now();
                img.src = newSrc;
                log(`🔄 Cache limpiado para: ${newSrc}`, 'info');
            }
        });
    }
    
    // Interceptar la función updateProfileImages del modal
    document.addEventListener('DOMContentLoaded', function() {
        log('🚀 Debug iniciado', 'success');
        debugElements();
        
        // Interceptar actualizaciones
        const originalConsoleLog = console.log;
        console.log = function(...args) {
            if (args[0] && args[0].includes('Imagen de perfil actualizada')) {
                log(`📸 ${args.join(' ')}`, 'success');
            }
            originalConsoleLog.apply(console, args);
        };
        
        // Monitorear cambios en el DOM
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                    log(`🔄 Detectado cambio de src en: ${mutation.target.tagName}#${mutation.target.id}`, 'warning');
                    log(`   Nuevo src: ${mutation.target.src}`, 'info');
                }
            });
        });
        
        // Observar cambios en imágenes
        document.querySelectorAll('img').forEach(img => {
            observer.observe(img, { attributes: true, attributeFilter: ['src'] });
        });
    });
    </script>
</body>
</html>
