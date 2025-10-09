<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Final - Cambio de Foto de Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h1>🎯 Test Final - Cambio de Foto de Perfil</h1>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>🔍 Diagnóstico Completo</h5>
                    </div>
                    <div class="card-body">
                        <h6>📊 Estado de la Sesión:</h6>
                        <ul>
                            <li><strong>Admin ID:</strong> <?= $_SESSION['admin_id'] ?? '❌ No definido' ?></li>
                            <li><strong>Admin Nombre:</strong> <?= $_SESSION['admin_nombre'] ?? '❌ No definido' ?></li>
                            <li><strong>Imagen Actual:</strong> <?= $_SESSION['admin_imagen'] ?? '❌ Sin imagen' ?></li>
                        </ul>
                        
                        <h6>📁 Verificación de Archivos:</h6>
                        <ul>
                            <li><strong>Modal:</strong> <?= file_exists('views/admin/includes/profile-modal.php') ? '✅ Existe' : '❌ No existe' ?></li>
                            <li><strong>Procesador:</strong> <?= file_exists('views/admin/includes/upload-handler.php') ? '✅ Existe' : '❌ No existe' ?></li>
                            <li><strong>Directorio img:</strong> <?= is_dir('views/admin/img') ? '✅ Existe' : '❌ No existe' ?></li>
                            <li><strong>Permisos escritura:</strong> <?= is_writable('views/admin/img') ? '✅ Escribible' : '❌ No escribible' ?></li>
                        </ul>
                        
                        <?php if (isset($_SESSION['admin_imagen'])): ?>
                        <h6>🖼️ Imagen Actual:</h6>
                        <ul>
                            <li><strong>URL:</strong> <?= $_SESSION['admin_imagen'] ?></li>
                            <li><strong>Archivo existe:</strong> <?= file_exists('.' . $_SESSION['admin_imagen']) ? '✅ Sí' : '❌ No' ?></li>
                            <?php if (file_exists('.' . $_SESSION['admin_imagen'])): ?>
                            <li><strong>Tamaño:</strong> <?= number_format(filesize('.' . $_SESSION['admin_imagen']) / 1024, 2) ?> KB</li>
                            <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>🧪 Pruebas Paso a Paso</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="testStep1()">
                                1️⃣ Verificar Elementos DOM
                            </button>
                            <button class="btn btn-info" onclick="testStep2()">
                                2️⃣ Probar Modal
                            </button>
                            <button class="btn btn-warning" onclick="testStep3()">
                                3️⃣ Simular Actualización
                            </button>
                            <button class="btn btn-success" onclick="testStep4()">
                                4️⃣ Test Completo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>👤 Simulación Sidebar</h5>
                    </div>
                    <div class="card-body bg-dark text-white text-center">
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
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>📋 Log en Tiempo Real</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="realTimeLog" style="height: 300px; overflow-y: auto; background: #1e1e1e; color: #fff; padding: 10px; font-family: monospace; font-size: 12px;">
                            <div style="color: #28a745;">[Inicio] Sistema de debug iniciado</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal incluido -->
    <?php include 'views/admin/includes/profile-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Sistema de logging
    function logMessage(message, type = 'info') {
        const logDiv = document.getElementById('realTimeLog');
        const timestamp = new Date().toLocaleTimeString();
        const colors = {
            info: '#17a2b8',
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107'
        };
        
        logDiv.innerHTML += `<div style="color: ${colors[type]}">[${timestamp}] ${message}</div>`;
        logDiv.scrollTop = logDiv.scrollHeight;
    }
    
    // Interceptar console.log
    const originalLog = console.log;
    const originalError = console.error;
    
    console.log = function(...args) {
        logMessage(args.join(' '), 'info');
        originalLog.apply(console, args);
    };
    
    console.error = function(...args) {
        logMessage(args.join(' '), 'error');
        originalError.apply(console, args);
    };
    
    // Tests paso a paso
    function testStep1() {
        logMessage('🔍 PASO 1: Verificando elementos DOM...', 'warning');
        
        const sidebarImg = document.getElementById('sidebar-profile-image');
        const profileIcon = document.getElementById('profile-icon');
        const modal = document.getElementById('profileModal');
        
        logMessage(`Imagen sidebar: ${sidebarImg ? '✅ Encontrada' : '❌ No encontrada'}`, sidebarImg ? 'success' : 'error');
        logMessage(`Ícono perfil: ${profileIcon ? '✅ Encontrado' : '❌ No encontrado'}`, profileIcon ? 'success' : 'error');
        logMessage(`Modal: ${modal ? '✅ Encontrado' : '❌ No encontrado'}`, modal ? 'success' : 'error');
        
        if (sidebarImg) {
            logMessage(`URL actual: ${sidebarImg.src}`, 'info');
        }
    }
    
    function testStep2() {
        logMessage('🧪 PASO 2: Probando modal...', 'warning');
        
        const modal = new bootstrap.Modal(document.getElementById('profileModal'));
        modal.show();
        
        logMessage('✅ Modal abierto', 'success');
        
        setTimeout(() => {
            modal.hide();
            logMessage('✅ Modal cerrado', 'success');
        }, 3000);
    }
    
    function testStep3() {
        logMessage('🔄 PASO 3: Simulando actualización...', 'warning');
        
        const testUrl = '/Login/views/admin/img/admin_1.png?v=' + Date.now();
        
        const sidebarImg = document.getElementById('sidebar-profile-image');
        if (sidebarImg) {
            sidebarImg.src = testUrl;
            logMessage('✅ Imagen actualizada', 'success');
        }
        
        const profileIcon = document.getElementById('profile-icon');
        if (profileIcon) {
            const img = document.createElement('img');
            img.id = 'sidebar-profile-image';
            img.src = testUrl;
            img.alt = 'Imagen de perfil';
            img.className = 'rounded-circle img-fluid mb-2 profile-img-main';
            img.style.cssText = 'width: 80px; height: 80px; object-fit: cover;';
            profileIcon.replaceWith(img);
            logMessage('✅ Ícono reemplazado por imagen', 'success');
        }
    }
    
    function testStep4() {
        logMessage('🎯 PASO 4: Test completo iniciado...', 'warning');
        
        // Ejecutar todos los tests
        testStep1();
        setTimeout(() => testStep2(), 1000);
        setTimeout(() => testStep3(), 5000);
        
        logMessage('✅ Test completo finalizado', 'success');
    }
    
    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {
        logMessage('🚀 Sistema iniciado correctamente', 'success');
        testStep1();
    });
    </script>
</body>
</html>
