// Manejo de la subida de imágenes de perfil
function setupProfileImageUpload() {
    // Vista previa de la imagen seleccionada
    $('#profile_image').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').show();
                $('#imagePreview img').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        } else {
            $('#imagePreview').hide();
        }
    });
    
    // Subir la imagen al servidor
    $('#uploadImageBtn').click(function() {
        const fileInput = $('#profile_image')[0];
        if (fileInput.files.length === 0) {
            $('#uploadError').text('Por favor, selecciona una imagen').show();
            return;
        }
        
        const formData = new FormData();
        formData.append('profile_image', fileInput.files[0]);
        
        // Mostrar indicador de carga
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Subiendo...');
        $(this).prop('disabled', true);
        $('#uploadError').hide();
        $('#uploadSuccess').hide();
        
        $.ajax({
            url: '/Login/upload_profile_image_simple.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Actualizar la imagen de perfil en TODAS las instancias de la página
                    const newImageUrl = response.image_url + '?v=' + new Date().getTime();
                    
                    // Actualizar todas las imágenes de perfil del administrador (sidebar, header, etc.)
                    $('img[id*="profile-image"], img[alt*="perfil"], img[alt*="Imagen de perfil"]').each(function() {
                        $(this).attr('src', newImageUrl);
                    });
                    
                    // Si ya hay una imagen específica en el sidebar, actualizarla
                    if ($('#profile-image').length) {
                        $('#profile-image').attr('src', newImageUrl);
                    }
                    // Si hay un ícono, reemplazarlo por la imagen
                    else if ($('#profile-icon').length) {
                        const imgHtml = '<img id="profile-image" src="' + newImageUrl + '" alt="Imagen de perfil" ' +
                                       'class="rounded-circle img-fluid mb-2" style="width: 80px; height: 80px; object-fit: cover;">';
                        $('#profile-icon').replaceWith(imgHtml);
                    }
                    
                    // Actualizar cualquier imagen de administrador en el header o navbar
                    $('.navbar img, .header img, .admin-profile img, .dropdown img').each(function() {
                        if ($(this).attr('alt') && ($(this).attr('alt').includes('admin') || $(this).attr('alt').includes('perfil') || $(this).hasClass('profile-img-sm'))) {
                            $(this).attr('src', newImageUrl);
                        }
                    });
                    
                    // Actualizar específicamente las imágenes pequeñas del header
                    $('.profile-img-sm').attr('src', newImageUrl);
                    
                    // Mostrar mensaje de éxito
                    $('#uploadSuccess').text(response.message).show();
                    
                    // Cerrar el modal después de 2 segundos
                    setTimeout(function() {
                        $('#profileImageModal').modal('hide');
                        // Limpiar el formulario
                        $('#profileImageForm')[0].reset();
                        $('#imagePreview').hide();
                        $('#uploadSuccess').hide();
                    }, 2000);
                } else {
                    $('#uploadError').text(response.message).show();
                }
            },
            error: function(xhr, status, error) {
                $('#uploadError').text('Error al subir la imagen: ' + error).show();
            },
            complete: function() {
                $('#uploadImageBtn').html('Subir imagen');
                $('#uploadImageBtn').prop('disabled', false);
            }
        });
    });
}


