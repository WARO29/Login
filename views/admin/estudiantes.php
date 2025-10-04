<?php
// Verificar si la sesión está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    // Redirigir al login si no está autenticado
    header("Location: /Login/admin/login");
    exit();
}

// Datos del administrador autenticado
$admin_id = $_SESSION['admin_id'];
$admin_usuario = $_SESSION['admin_usuario'];
$admin_nombre = $_SESSION['admin_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Estudiantes - Sistema de Votación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #212529;
            color: white;
            padding-top: 1rem;
        }
        .main-content {
            min-height: 100vh;
        }
        .content-header {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <?php include 'views/admin/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content p-0">
                <!-- Header -->
                <div class="content-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Gestión de Estudiantes</h4>
                        <small class="text-muted">Administra los estudiantes registrados en el sistema</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarEstudiante">
                            <i class="fas fa-plus-circle me-1"></i> Agregar Estudiante
                        </button>
                    </div>
                </div>
                
                <div class="container-fluid px-4 py-3">
                    <!-- Alertas y mensajes -->
                    <?php if(isset($_SESSION['mensaje']) && isset($_SESSION['tipo'])): ?>
                        <div class="alert alert-<?= $_SESSION['tipo'] ?> alert-dismissible fade show auto-dismiss" role="alert">
                            <?= $_SESSION['mensaje'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php 
                        // Limpiar mensajes después de mostrarlos
                        unset($_SESSION['mensaje']);
                        unset($_SESSION['tipo']);
                        ?>
                    <?php endif; ?>
                    
                    <!-- Barra de búsqueda -->
                            <div class="row mb-4">
                                <div class="col-md-4 offset-md-8">
                                    <div class="input-group">
                                        <input type="text" id="busqueda-estudiantes" class="form-control form-control-sm" placeholder="Buscar por ID o nombre..." value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                                        <button type="button" class="btn btn-sm btn-primary" id="btn-buscar">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if(isset($_GET['busqueda']) && !empty($_GET['busqueda'])): ?>
                                            <button type="button" class="btn btn-sm btn-secondary" id="btn-limpiar">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                    
                    <!-- Tabla de estudiantes -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Documento</th>
                                            <th>Nombre</th>
                                            <th>Correo</th>
                                            <th>Grado</th>
                                            <th>Grupo</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(isset($estudiantes) && !empty($estudiantes)): ?>
                                            <?php foreach($estudiantes as $estudiante): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($estudiante['id_estudiante']) ?></td>
                                                    <td><?= htmlspecialchars($estudiante['nombre']) ?></td>
                                                    <td><?= htmlspecialchars($estudiante['correo'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($estudiante['grado']) ?></td>
                                                    <td><?= htmlspecialchars($estudiante['grupo']) ?></td>
                                                    <td>
                                                        <?php if(strtolower($estudiante['estado']) == 'activo' || $estudiante['estado'] == 1): ?>
                                                            <span class="badge bg-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactivo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning" onclick="editarEstudiante('<?= $estudiante['id_estudiante'] ?>', '<?= htmlspecialchars($estudiante['nombre']) ?>', '<?= htmlspecialchars($estudiante['correo'] ?? '') ?>', '<?= $estudiante['grado'] ?>', '<?= $estudiante['grupo'] ?>', '<?= $estudiante['estado'] ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="confirmarEliminar('<?= $estudiante['id_estudiante'] ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No hay estudiantes registrados</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginación -->
                            <?php if(isset($total_paginas)): ?>
                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                <div>
                                    Mostrando <?= ($pagina_actual - 1) * $estudiantes_por_pagina + 1 ?> a 
                                    <?= min($pagina_actual * $estudiantes_por_pagina, $total_estudiantes) ?> 
                                    de <?= $total_estudiantes ?> estudiantes
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="me-2">Mostrar:</span>
                                    <select class="form-select form-select-sm" style="width: auto;" onchange="cambiarRegistrosPorPagina(this.value)">
                                        <option value="10" <?= $estudiantes_por_pagina == 10 ? 'selected' : '' ?>>10</option>
                                        <option value="20" <?= $estudiantes_por_pagina == 20 ? 'selected' : '' ?>>20</option>
                                        <option value="50" <?= $estudiantes_por_pagina == 50 ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= $estudiantes_por_pagina == 100 ? 'selected' : '' ?>>100</option>
                                    </select>
                                </div>
                            </div>
                            <nav aria-label="Navegación de páginas">
                                <ul class="pagination justify-content-center mt-4">
                                    <!-- Botón Anterior -->
                                    <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?>&registros_por_pagina=<?= $estudiantes_por_pagina ?>" aria-label="Anterior">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <!-- Números de página -->
                                    <?php
                                    // Mostrar un máximo de 5 números de página
                                    $inicio_paginas = max(1, $pagina_actual - 2);
                                    $fin_paginas = min($total_paginas, $inicio_paginas + 4);
                                    
                                    // Ajustar el inicio si estamos cerca del final
                                    if ($fin_paginas - $inicio_paginas < 4) {
                                        $inicio_paginas = max(1, $fin_paginas - 4);
                                    }
                                    
                                    for ($i = $inicio_paginas; $i <= $fin_paginas; $i++): 
                                    ?>
                                        <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                                            <a class="page-link" href="?pagina=<?= $i ?>&registros_por_pagina=<?= $estudiantes_por_pagina ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Botón Siguiente -->
                                    <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?>&registros_por_pagina=<?= $estudiantes_por_pagina ?>" aria-label="Siguiente">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Agregar Estudiante -->
    <div class="modal fade" id="modalAgregarEstudiante" tabindex="-1" aria-labelledby="modalAgregarEstudianteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarEstudianteLabel">Agregar Nuevo Estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/Login/admin/estudiantes/agregar" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="documento" class="form-label">Documento</label>
                            <input type="text" class="form-control" id="documento" name="documento" required>
                        </div>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="grado" class="form-label">Grado</label>
                            <select class="form-select" id="grado" name="grado" required>
                                <option value="">Seleccione un grado</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="grupo" class="form-label">Grupo</label>
                            <select class="form-select" id="grupo" name="grupo" required>
                                <option value="">Seleccione un grupo</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Estudiante -->
    <div class="modal fade" id="modalEditarEstudiante" tabindex="-1" aria-labelledby="modalEditarEstudianteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarEstudianteLabel">Editar Estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/Login/admin/estudiantes/editar" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_documento" name="documento">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                           <label for="edit_correo" class="form-label">Correo Electrónico</label>
                           <input type="email" class="form-control" id="edit_correo" name="correo">
                       </div>
                        <div class="mb-3">
                            <label for="edit_grado" class="form-label">Grado</label>
                            <select class="form-select" id="edit_grado" name="grado" required>
                                <option value="">Seleccione un grado</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_grupo" class="form-label">Grupo</label>
                            <select class="form-select" id="edit_grupo" name="grupo" required>
                                <option value="">Seleccione un grupo</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_estado" class="form-label">Estado</label>
                            <select class="form-select" id="edit_estado" name="estado" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-labelledby="modalConfirmarEliminarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConfirmarEliminarLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar este estudiante? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="/Login/admin/estudiantes/eliminar" method="POST">
                        <input type="hidden" id="eliminar_documento" name="documento">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para ocultar automáticamente las alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alertElements = document.querySelectorAll('.auto-dismiss');
            
            if (alertElements.length > 0) {
                setTimeout(function() {
                    alertElements.forEach(function(alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    });
                }, 5000);
            }
            
            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Configurar la funcionalidad de carga de imagen de perfil
            setupProfileImageUpload();
        });
        
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
                            $('.navbar img, .header img, .admin-profile img').each(function() {
                                if ($(this).attr('alt') && ($(this).attr('alt').includes('admin') || $(this).attr('alt').includes('perfil'))) {
                                    $(this).attr('src', newImageUrl);
                                }
                            });
                            
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
        
        function editarEstudiante(documento, nombre, correo, grado, grupo, estado) {
            document.getElementById('edit_documento').value = documento;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_correo').value = correo;
            document.getElementById('edit_grado').value = grado;
            document.getElementById('edit_grupo').value = grupo;
            document.getElementById('edit_estado').value = estado;
            
            var modal = new bootstrap.Modal(document.getElementById('modalEditarEstudiante'));
            modal.show();
        }
        
        function confirmarEliminar(documento) {
            document.getElementById('eliminar_documento').value = documento;
            
            var modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminar'));
            modal.show();
        }
        
        function cambiarRegistrosPorPagina(registros) {
            window.location.href = "?pagina=1&registros_por_pagina=" + registros;
        }

        // Búsqueda en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const inputBusqueda = document.getElementById('busqueda-estudiantes');
            const btnBuscar = document.getElementById('btn-buscar');
            const btnLimpiar = document.getElementById('btn-limpiar');
            let timeoutId;

            // Función para realizar la búsqueda
            function realizarBusqueda() {
                const termino = inputBusqueda.value.trim();
                const registrosPorPagina = <?= isset($_GET['registros_por_pagina']) ? (int)$_GET['registros_por_pagina'] : 20 ?>;
                
                if (termino) {
                    window.location.href = `?busqueda=${encodeURIComponent(termino)}&pagina=1&registros_por_pagina=${registrosPorPagina}`;
                } else if (btnLimpiar) {
                    window.location.href = `?pagina=1&registros_por_pagina=${registrosPorPagina}`;
                }
            }

            // Evento input para búsqueda en tiempo real con debounce
            inputBusqueda.addEventListener('input', function() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(realizarBusqueda, 500); // Esperar 500ms después de que el usuario deje de escribir
            });

            // Evento para el botón de búsqueda
            if (btnBuscar) {
                btnBuscar.addEventListener('click', function() {
                    clearTimeout(timeoutId);
                    realizarBusqueda();
                });
            }

            // Evento para el botón de limpiar
            if (btnLimpiar) {
                btnLimpiar.addEventListener('click', function() {
                    inputBusqueda.value = '';
                    realizarBusqueda();
                });
            }

            // Evento para la tecla Enter
            inputBusqueda.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(timeoutId);
                    realizarBusqueda();
                }
            });
        });
    </script>
    
    <!-- Modal para cambiar la imagen de perfil -->
    <div class="modal fade" id="profileImageModal" tabindex="-1" aria-labelledby="profileImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileImageModalLabel">Cambiar imagen de perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="profileImageForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Selecciona una nueva imagen</label>
                            <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/*" required>
                            <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB.</div>
                        </div>
                        <div id="imagePreview" class="text-center my-3" style="display: none;">
                            <img src="" alt="Vista previa" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                        <div class="alert alert-danger" id="uploadError" style="display: none;"></div>
                        <div class="alert alert-success" id="uploadSuccess" style="display: none;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="uploadImageBtn">Subir imagen</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
