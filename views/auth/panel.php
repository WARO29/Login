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
$admin_id = $_SESSION['admin_id'] ?? '';
$admin_usuario = $_SESSION['admin_usuario'] ?? '';
$admin_nombre = $_SESSION['admin_nombre'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Administrativo - Sistema de Votación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .sidebar-link {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 0.75rem 1rem;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }
        .sidebar-link:hover, .sidebar-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar-link i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
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
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .profile-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
        }
        .profile-img-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
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
                        <h4 class="mb-0">Dashboard</h4>
                        <small class="text-muted">Bienvenido al panel administrativo</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle text-dark text-decoration-none" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if (isset($_SESSION['admin_imagen']) && !empty($_SESSION['admin_imagen'])) { ?>
                                <img src="<?= $_SESSION['admin_imagen'] ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($admin_nombre) ?>" class="profile-img-sm me-2">
                            <?php } else { ?>
                                <i class="fas fa-user-circle fa-fw me-2"></i>
                            <?php } ?>
                            <span><?php echo htmlspecialchars($admin_usuario); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/Login/admin/cerrar-sesion"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Main Content Area -->
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
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-center mb-2">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                            <i class="fas fa-user-graduate text-primary fa-2x"></i>
                                        </div>
                                    </div>
                                    <h3 class="card-title" id="total-estudiantes">0</h3>
                                    <p class="card-text text-muted">Estudiantes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-center mb-2">
                                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                            <i class="fas fa-chalkboard-teacher text-info fa-2x"></i>
                                        </div>
                                    </div>
                                    <h3 class="card-title" id="total-docentes">0</h3>
                                    <p class="card-text text-muted">Docentes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-center mb-2">
                                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                            <i class="fas fa-vote-yea text-success fa-2x"></i>
                                        </div>
                                    </div>
                                    <h3 class="card-title" id="total-votos">0</h3>
                                    <p class="card-text text-muted">Votos Estudiantes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-center mb-2">
                                        <div class="rounded-circle bg-secondary bg-opacity-10 p-3">
                                            <i class="fas fa-chalkboard-teacher text-secondary fa-2x"></i>
                                        </div>
                                    </div>
                                    <h3 class="card-title" id="total-votos-docentes">0</h3>
                                    <p class="card-text text-muted">Votos Docentes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-center mb-2">
                                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                            <i class="fas fa-user-tie text-warning fa-2x"></i>
                                        </div>
                                    </div>
                                    <h3 class="card-title" id="total-candidatos">0</h3>
                                    <p class="card-text text-muted">Candidatos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-center mb-2">
                                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                            <i class="fas fa-chart-pie text-danger fa-2x"></i>
                                        </div>
                                    </div>
                                    <h3 class="card-title" id="porcentaje-participacion">0%</h3>
                                    <p class="card-text text-muted">Participación</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main Content Rows -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Resultados de Votación - Estudiantes</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Panel de resultados de estudiantes -->
                                    <div id="panel-resultados-estudiantes">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card border-0 shadow-sm mb-3">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Estadísticas de Votación</h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm">
                                                                <tbody>
                                                                    <tr>
                                                                        <td>Total Estudiantes:</td>
                                                                        <td class="text-end fw-bold" id="total-estudiantes-stats">0</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Votos Registrados:</td>
                                                                        <td class="text-end fw-bold" id="total-votos-estudiantes">0</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Votos en Blanco:</td>
                                                                        <td class="text-end fw-bold" id="total-votos-blanco-estudiantes">0</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Participación:</td>
                                                                        <td class="text-end fw-bold" id="porcentaje-participacion-estudiantes">0%</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <!-- Pestañas para Personeros y Representantes -->
                                                <ul class="nav nav-tabs" id="candidatosTabs" role="tablist">
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link active" id="personeros-tab" data-bs-toggle="tab" 
                                                                data-bs-target="#personeros-content" type="button" role="tab" 
                                                                aria-controls="personeros-content" aria-selected="true">
                                                            Personeros
                                                        </button>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link" id="representantes-tab" data-bs-toggle="tab" 
                                                                data-bs-target="#representantes-content" type="button" role="tab" 
                                                                aria-controls="representantes-content" aria-selected="false">
                                                            Representantes
                                                        </button>
                                                    </li>
                                                </ul>
                                                
                                                <!-- Contenido de las pestañas -->
                                                <div class="tab-content" id="candidatosTabsContent">
                                                    <!-- Personeros -->
                                                    <div class="tab-pane fade show active" id="personeros-content" role="tabpanel" 
                                                         aria-labelledby="personeros-tab">
                                                        <div class="card border-0 shadow-sm mb-3">
                                                            <div class="card-body">
                                                                <h6 class="card-title">Votos por Personero</h6>
                                                                <div id="votos-personeros-container">
                                                                    <p class="text-muted text-center my-3">Cargando datos...</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Representantes -->
                                                    <div class="tab-pane fade" id="representantes-content" role="tabpanel" 
                                                         aria-labelledby="representantes-tab">
                                                        <div class="card border-0 shadow-sm mb-3">
                                                            <div class="card-body">
                                                                <h6 class="card-title">Votos por Representante</h6>
                                                                <div id="votos-representantes-container">
                                                                    <p class="text-muted text-center my-3">Cargando datos...</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card border-0 shadow-sm">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Distribución de Votos - Personeros</h6>
                                                        <div id="grafico-circular-personeros-container" style="height: 250px;">
                                                            <canvas id="grafico-circular-personeros"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card border-0 shadow-sm">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Distribución de Votos - Representantes</h6>
                                                        <div id="grafico-circular-representantes-container" style="height: 250px;">
                                                            <canvas id="grafico-circular-representantes"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="card border-0 shadow-sm">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Votos por Personero - Gráfico de Barras</h6>
                                                        <div id="grafico-barras-personeros-container" style="height: 250px;">
                                                            <canvas id="grafico-barras-personeros"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card border-0 shadow-sm">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Votos por Representante - Gráfico de Barras</h6>
                                                        <div id="grafico-barras-representantes-container" style="height: 250px;">
                                                            <canvas id="grafico-barras-representantes"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Actividad Reciente</h5>
                                </div>
                                <div class="card-body p-0">
                                    <!-- Tabs para seleccionar el tipo de actividad -->
                                    <ul class="nav nav-tabs" id="actividadTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="estudiantes-tab" data-bs-toggle="tab"
                                                    data-bs-target="#estudiantes-actividad" type="button" role="tab"
                                                    aria-controls="estudiantes-actividad" aria-selected="true">
                                                Estudiantes
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="docentes-tab" data-bs-toggle="tab"
                                                    data-bs-target="#docentes-actividad" type="button" role="tab"
                                                    aria-controls="docentes-actividad" aria-selected="false">
                                                Docentes
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="administrativos-tab" data-bs-toggle="tab"
                                                    data-bs-target="#administrativos-actividad" type="button" role="tab"
                                                    aria-controls="administrativos-actividad" aria-selected="false">
                                                Administrativos
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <!-- Contenido de las pestañas -->
                                    <div class="tab-content" id="actividadTabsContent">
                                        <!-- Actividad de estudiantes -->
                                        <div class="tab-pane fade show active" id="estudiantes-actividad" role="tabpanel" 
                                             aria-labelledby="estudiantes-tab">
                                            <ul class="list-group list-group-flush" id="votos-recientes-estudiantes">
                                                <li class="list-group-item text-center">
                                                    <p class="text-muted mb-0">Cargando actividad reciente...</p>
                                                </li>
                                            </ul>
                                        </div>
                                        
                                        <!-- Actividad de docentes -->
                                        <div class="tab-pane fade" id="docentes-actividad" role="tabpanel"
                                             aria-labelledby="docentes-tab">
                                            <ul class="list-group list-group-flush" id="votos-recientes-docentes">
                                                <li class="list-group-item text-center">
                                                    <p class="text-muted mb-0">Cargando actividad reciente...</p>
                                                </li>
                                            </ul>
                                        </div>
                                        
                                        <!-- Actividad de administrativos -->
                                        <div class="tab-pane fade" id="administrativos-actividad" role="tabpanel"
                                             aria-labelledby="administrativos-tab">
                                            <ul class="list-group list-group-flush" id="votos-recientes-administrativos">
                                                <li class="list-group-item text-center">
                                                    <p class="text-muted mb-0">Cargando actividad reciente...</p>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para cambiar imagen de perfil -->
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Inicialización de la página
        $(document).ready(function() {
            // Configurar la funcionalidad de carga de imagen de perfil
            setupProfileImageUpload();
            
            // Cerrar alertas automáticamente después de 5 segundos
            setTimeout(function() {
                $('.alert.auto-dismiss').fadeOut('slow', function() {
                    $(this).alert('close');
                });
            }, 5000);

            // Cargar datos iniciales
            cargarEstadisticas();
            cargarVotosRecientes();
            cargarVotosCandidatos();
            cargarVotosAdministrativos();
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

        // Función para cargar las estadísticas generales
        function cargarEstadisticas() {
            fetch('/Login/admin/obtener_estadisticas')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-estudiantes').textContent = data.totalEstudiantes;
                    document.getElementById('total-docentes').textContent = data.totalDocentes;
                    document.getElementById('total-votos').textContent = data.totalVotos;
                    document.getElementById('total-votos-docentes').textContent = data.totalVotosDocentes;
                    document.getElementById('total-candidatos').textContent = data.totalCandidatos;
                    document.getElementById('porcentaje-participacion').textContent = data.porcentajeParticipacion + '%';
                    
                    // Actualizar estadísticas detalladas
                    document.getElementById('total-estudiantes-stats').textContent = data.totalEstudiantes;
                    document.getElementById('total-votos-estudiantes').textContent = data.totalVotos;
                    document.getElementById('total-votos-blanco-estudiantes').textContent = data.totalVotosBlanco;
                    document.getElementById('porcentaje-participacion-estudiantes').textContent = data.porcentajeParticipacion + '%';
                })
                .catch(error => console.error('Error:', error));
        }

        // Función para cargar los votos recientes
        function cargarVotosRecientes() {
            // Cargar votos recientes de estudiantes
            fetch('/Login/admin/obtener_votos_recientes/estudiantes')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('votos-recientes-estudiantes');
                    container.innerHTML = '';
                    
                    if (data.length === 0) {
                        container.innerHTML = '<li class="list-group-item text-center"><p class="text-muted mb-0">No hay votos recientes</p></li>';
                        return;
                    }
                    
                    data.forEach(voto => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item';
                        li.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">${voto.nombre_completo}</h6>
                                    <small class="text-muted">Grado ${voto.grado}</small>
                                    ${voto.info_voto ? `<small class="d-block text-primary">${voto.info_voto}</small>` : ''}
                                </div>
                                <small class="text-muted">${voto.fecha}</small>
                            </div>
                        `;
                        container.appendChild(li);
                    });
                })
                .catch(error => console.error('Error:', error));

            // Cargar votos recientes de docentes
            fetch('/Login/admin/obtener_votos_recientes/docentes')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('votos-recientes-docentes');
                    container.innerHTML = '';
                    
                    if (data.length === 0) {
                        container.innerHTML = '<li class="list-group-item text-center"><p class="text-muted mb-0">No hay votos recientes</p></li>';
                        return;
                    }
                    
                    data.forEach(voto => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item';
                        li.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">${voto.nombre_completo}</h6>
                                    <small class="text-muted">${voto.rol}</small>
                                    ${voto.info_voto ? `<small class="d-block text-primary">${voto.info_voto}</small>` : ''}
                                </div>
                                <small class="text-muted">${voto.fecha}</small>
                            </div>
                        `;
                        container.appendChild(li);
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Función para cargar los votos por candidato
        function cargarVotosCandidatos() {
            fetch('/Login/admin/obtener_votos_candidatos')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('votos-candidatos-container');
                    container.innerHTML = '';
                    
                    if (data.length === 0) {
                        container.innerHTML = '<p class="text-muted text-center my-3">No hay datos disponibles</p>';
                        return;
                    }
                    
                    const table = document.createElement('table');
                    table.className = 'table table-sm';
                    table.innerHTML = `
                        <thead>
                            <tr>
                                <th>Candidato</th>
                                <th class="text-end">Votos</th>
                                <th class="text-end">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(candidato => `
                                <tr>
                                    <td>${candidato.nombre}</td>
                                    <td class="text-end">${candidato.votos}</td>
                                    <td class="text-end">${candidato.porcentaje}%</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    `;
                    container.appendChild(table);

                    // Actualizar gráficos
                    actualizarGraficos(data);
                })
                .catch(error => console.error('Error:', error));
        }

        // Función para cargar los votos recientes de administrativos
        function cargarVotosAdministrativos() {
            fetch('/Login/api/estadisticas_administrativos.php')
                .then(response => response.text())
                .then(responseText => {
                    try {
                        // Extraer JSON de la respuesta, ignorando warnings de PHP
                        let jsonStart = responseText.indexOf('{');
                        if (jsonStart !== -1) {
                            let jsonString = responseText.substring(jsonStart);
                            let data = JSON.parse(jsonString);
                            
                            const container = document.getElementById('votos-recientes-administrativos');
                            container.innerHTML = '';
                            
                            if (!data.votosRecientes || data.votosRecientes.length === 0) {
                                container.innerHTML = '<li class="list-group-item text-center"><p class="text-muted mb-0">No hay votos recientes</p></li>';
                                return;
                            }
                            
                            data.votosRecientes.forEach(voto => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item';
                                li.innerHTML = `
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">${voto.nombre_completo || 'Administrativo'}</h6>
                                            <small class="text-muted">${voto.rol || 'Administrativo'}</small>
                                            ${voto.info_voto ? `<small class="d-block text-primary">${voto.info_voto}</small>` : ''}
                                        </div>
                                        <small class="text-muted">${voto.fecha || 'Reciente'}</small>
                                    </div>
                                `;
                                container.appendChild(li);
                            });
                            
                            console.log('Votos administrativos cargados correctamente:', data.votosRecientes.length);
                        } else {
                            throw new Error("No se encontró JSON válido en la respuesta");
                        }
                    } catch (e) {
                        console.error("Error al procesar datos de administrativos:", e);
                        const container = document.getElementById('votos-recientes-administrativos');
                        container.innerHTML = '<li class="list-group-item text-center"><p class="text-muted mb-0">Error al cargar datos</p></li>';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar administrativos:', error);
                    const container = document.getElementById('votos-recientes-administrativos');
                    container.innerHTML = '<li class="list-group-item text-center"><p class="text-muted mb-0">Error al cargar datos</p></li>';
                });
        }

        // Función para actualizar los gráficos
        function actualizarGraficos(data) {
            const labels = data.map(item => item.nombre);
            const votos = data.map(item => item.votos);
            const porcentajes = data.map(item => item.porcentaje);

            // Gráfico circular
            const ctxCircular = document.getElementById('grafico-circular').getContext('2d');
            if (window.graficoCircular) {
                window.graficoCircular.destroy();
            }
            window.graficoCircular = new Chart(ctxCircular, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: votos,
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        title: {
                            display: true,
                            text: 'Distribución de Votos'
                        }
                    }
                }
            });

            // Gráfico de barras
            const ctxBarras = document.getElementById('grafico-barras').getContext('2d');
            if (window.graficoBarras) {
                window.graficoBarras.destroy();
            }
            window.graficoBarras = new Chart(ctxBarras, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votos',
                        data: votos,
                        backgroundColor: '#36A2EB'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Votos por Candidato'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    </script>
    <!-- Incluir el archivo JavaScript externo -->
    <script src="/Login/views/auth/js/panel-admin.js"></script>
</body>
</html>
