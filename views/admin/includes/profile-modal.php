<!-- Estilos para el modal -->
<style>
#profileModal .modal-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 1rem !important;
    border-bottom: 1px solid #dee2e6 !important;
    background-color: #fff !important;
}

#profileModal .modal-title {
    margin: 0 !important;
    font-size: 1.25rem !important;
    font-weight: 500 !important;
    color: #212529 !important;
}

#profileModal .btn-close {
    background: none !important;
    border: none !important;
    font-size: 1.5rem !important;
    opacity: 0.5 !important;
    cursor: pointer !important;
    padding: 0 !important;
    width: 24px !important;
    height: 24px !important;
}

#profileModal .btn-close:hover {
    opacity: 0.75 !important;
}

#profileModal .modal-content {
    border-radius: 0.375rem !important;
    border: 1px solid rgba(0,0,0,.125) !important;
}

#profileModal .modal-body {
    padding: 1rem !important;
}

#profileModal .modal-footer {
    padding: 1rem !important;
    border-top: 1px solid #dee2e6 !important;
    display: flex !important;
    justify-content: flex-end !important;
    gap: 0.5rem !important;
}
</style>

<!-- Modal para cambiar imagen de perfil -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" id="profileModalLabel" style="margin: 0; font-size: 1.25rem; font-weight: 500;">Cambiar Foto de Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; opacity: 0.5;">&times;</button>
            </div>
            <div class="modal-body">
                <form id="profileForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profileImage" class="form-label">Seleccionar imagen</label>
                        <input type="file" class="form-control" id="profileImage" name="profile_image" 
                               accept="image/jpeg,image/jpg,image/png,image/gif" required>
                        <div class="form-text">Formatos: JPG, PNG, GIF. Máximo: 2MB</div>
                    </div>
                    
                    <!-- Vista previa -->
                    <div id="imagePreview" class="text-center mb-3" style="display: none;">
                        <img src="" alt="Vista previa" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                    
                    <!-- Mensajes -->
                    <div id="uploadMessage" class="alert" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="uploadBtn">
                    <span id="uploadText">Subir Imagen</span>
                    <span id="uploadSpinner" class="spinner-border spinner-border-sm ms-2" style="display: none;"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript integrado en el modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('profileModal');
    const form = document.getElementById('profileForm');
    const fileInput = document.getElementById('profileImage');
    const preview = document.getElementById('imagePreview');
    const previewImg = preview.querySelector('img');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadText = document.getElementById('uploadText');
    const uploadSpinner = document.getElementById('uploadSpinner');
    const message = document.getElementById('uploadMessage');
    
    // Vista previa de imagen
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validar tamaño (2MB)
            if (file.size > 2 * 1024 * 1024) {
                showMessage('Archivo muy grande. Máximo 2MB.', 'danger');
                fileInput.value = '';
                preview.style.display = 'none';
                return;
            }
            
            // Mostrar vista previa
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
    
    // Subir imagen
    uploadBtn.addEventListener('click', function() {
        const file = fileInput.files[0];
        if (!file) {
            showMessage('Por favor selecciona una imagen.', 'danger');
            return;
        }
        
        // Preparar datos
        const formData = new FormData();
        formData.append('profile_image', file);
        
        // UI de carga
        setLoading(true);
        hideMessage();
        
        // Enviar archivo
        fetch('/Login/views/admin/includes/upload-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('📡 Respuesta recibida del servidor');
            return response.json();
        })
        .then(data => {
            console.log('📊 Datos procesados:', data);
            
            if (data.success) {
                showMessage(data.message, 'success');
                console.log('✅ Subida exitosa, iniciando actualización de imagen...');
                
                // Actualizar imagen en la página
                updateProfileImages(data.data.image_url);
                
                // Mostrar información adicional en consola
                console.log('📋 Información de subida:', {
                    url: data.data.image_url,
                    fileName: data.data.file_name,
                    size: data.data.file_size,
                    dbUpdated: data.data.db_updated,
                    sessionUpdated: data.data.session_updated
                });
                
                // Cerrar modal después de 2 segundos
                setTimeout(() => {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    console.log('🔒 Modal cerrado');
                }, 2000);
            } else {
                console.error('❌ Error en subida:', data.message);
                showMessage(data.message || 'Error al subir imagen', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error de conexión', 'danger');
        })
        .finally(() => {
            setLoading(false);
        });
    });
    
    // Limpiar formulario al cerrar modal
    modal.addEventListener('hidden.bs.modal', function() {
        form.reset();
        preview.style.display = 'none';
        hideMessage();
    });
    
    // Funciones auxiliares
    function setLoading(loading) {
        uploadBtn.disabled = loading;
        uploadText.textContent = loading ? 'Subiendo...' : 'Subir Imagen';
        uploadSpinner.style.display = loading ? 'inline-block' : 'none';
    }
    
    function showMessage(text, type) {
        message.textContent = text;
        message.className = `alert alert-${type}`;
        message.style.display = 'block';
    }
    
    function hideMessage() {
        message.style.display = 'none';
    }
    
    function updateProfileImages(imageUrl) {
        console.log('🔄 Iniciando actualización de imagen:', imageUrl);
        
        // Agregar timestamp para evitar cache
        const newUrl = imageUrl + '?v=' + Date.now();
        console.log('🔄 URL con timestamp:', newUrl);
        
        // Buscar y actualizar imagen principal del sidebar
        const sidebarImg = document.getElementById('sidebar-profile-image');
        if (sidebarImg) {
            console.log('✅ Imagen del sidebar encontrada, actualizando...');
            sidebarImg.src = newUrl;
            console.log('✅ Imagen del sidebar actualizada');
        } else {
            console.log('❌ Imagen del sidebar NO encontrada');
        }
        
        // Si hay ícono, reemplazarlo por imagen
        const profileIcon = document.getElementById('profile-icon');
        if (profileIcon) {
            console.log('✅ Ícono encontrado, reemplazando por imagen...');
            const img = document.createElement('img');
            img.id = 'sidebar-profile-image';
            img.src = newUrl;
            img.alt = 'Imagen de perfil';
            img.className = 'rounded-circle img-fluid mb-2 profile-img-main';
            img.style.cssText = 'width: 80px; height: 80px; object-fit: cover;';
            
            // Agregar evento de carga para verificar
            img.onload = function() {
                console.log('✅ Nueva imagen cargada correctamente');
            };
            img.onerror = function() {
                console.error('❌ Error al cargar nueva imagen:', newUrl);
            };
            
            profileIcon.replaceWith(img);
            console.log('✅ Ícono reemplazado por imagen');
        } else {
            console.log('❌ Ícono de perfil NO encontrado');
        }
        
        // Actualizar todas las imágenes de perfil en la página
        const allProfileImgs = document.querySelectorAll('.profile-img-main, .profile-img-sm');
        console.log(`🔄 Actualizando ${allProfileImgs.length} imágenes de perfil...`);
        
        allProfileImgs.forEach((img, index) => {
            console.log(`🔄 Actualizando imagen ${index + 1}: ${img.id || 'sin ID'}`);
            img.src = newUrl;
        });
        
        // Forzar actualización de cualquier imagen que contenga 'admin_' en el src
        document.querySelectorAll('img').forEach(img => {
            if (img.src.includes('admin_') || img.src.includes('/img/')) {
                console.log('🔄 Actualizando imagen adicional:', img.src);
                img.src = newUrl;
            }
        });
        
        console.log('✅ Actualización de imagen completada:', newUrl);
        
        // Verificar después de un momento
        setTimeout(() => {
            const finalImg = document.getElementById('sidebar-profile-image');
            if (finalImg) {
                console.log('✅ Verificación final - Imagen presente:', finalImg.src);
            } else {
                console.error('❌ Verificación final - Imagen NO encontrada');
            }
        }, 500);
    }
});
</script>
