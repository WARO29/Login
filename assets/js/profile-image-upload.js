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
            url: '/Login/views/admin/includes/upload_profile_image.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('✅ Respuesta del servidor:', response);
                
                if (response.success) {
                    // Actualizar la imagen de perfil en TODAS las instancias de la página
                    const newImageUrl = response.image_url + '?v=' + new Date().getTime();
                    console.log('🖼️ Nueva URL de imagen:', newImageUrl);
                    
                    // Actualizar imagen principal del sidebar
                    if ($('#sidebar-profile-image').length) {
                        $('#sidebar-profile-image').attr('src', newImageUrl);
                        console.log('📝 Actualizada imagen principal del sidebar');
                    }
                    // Si hay un ícono, reemplazarlo por la imagen
                    else if ($('#profile-icon').length) {
                        const imgHtml = '<img id="sidebar-profile-image" src="' + newImageUrl + '" alt="Imagen de perfil" ' +
                                       'class="rounded-circle img-fluid mb-2 profile-img-main" style="width: 80px; height: 80px; object-fit: cover;">';
                        $('#profile-icon').replaceWith(imgHtml);
                        console.log('📝 Reemplazado ícono por imagen en sidebar');
                    }
                    
                    // Actualizar imagen pequeña en el header/navbar si existe
                    $('.profile-img-sm').attr('src', newImageUrl);
                    console.log('📝 Actualizada imagen pequeña del header');
                    
                    // Actualizar cualquier otra imagen de perfil en la página
                    $('.profile-img-main').attr('src', newImageUrl);
                    console.log('📝 Actualizadas todas las imágenes principales');
                    
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
                console.log('Error AJAX:', {xhr, status, error});
                console.log('Response Text:', xhr.responseText);
                
                let errorMessage = 'Error al subir la imagen: ' + error;
                
                // Intentar parsear la respuesta para obtener más detalles
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                    if (response.debug) {
                        console.log('Debug info:', response.debug);
                    }
                } catch (e) {
                    // Si no es JSON válido, mostrar el texto crudo (truncado)
                    if (xhr.responseText.length > 200) {
                        errorMessage += '\nRespuesta: ' + xhr.responseText.substring(0, 200) + '...';
                    } else {
                        errorMessage += '\nRespuesta: ' + xhr.responseText;
                    }
                }
                
                $('#uploadError').text(errorMessage).show();
            },
            complete: function() {
                $('#uploadImageBtn').html('Subir imagen');
                $('#uploadImageBtn').prop('disabled', false);
            }
        });
    });
}


