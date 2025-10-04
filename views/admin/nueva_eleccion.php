<?php
// Verificar que el usuario sea administrador
if (!isset($_SESSION['admin_id'])) {
    header('Location: /Login/admin/login');
    exit;
}

// Recuperar datos del formulario en caso de error
$datos = $_SESSION['datos_formulario'] ?? [
    'nombre_eleccion' => '',
    'descripcion' => '',
    'fecha_inicio' => '',
    'fecha_cierre' => '',
    'tipos_votacion' => [],
    'configuracion_adicional' => [
        'mostrar_resultados_parciales' => false,
        'permitir_voto_blanco' => true,
        'tiempo_maximo_votacion' => 300
    ]
];
unset($_SESSION['datos_formulario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Elección - Panel de Administración</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr para selector de fecha/hora -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-submit {
            padding: 0.75rem 2rem;
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
                        <h4 class="mb-0">Nueva Elección</h4>
                        <small class="text-muted">Crear una nueva configuración de elección</small>
                    </div>
                    <div>
                        <a href="/Login/admin/configuracion-elecciones" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="container-fluid px-4 py-3">
                    <!-- Alertas y mensajes -->
                    <?php if (isset($_SESSION['mensaje'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['tipo'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['mensaje']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
                    <?php endif; ?>
                    
                    <!-- Formulario de nueva elección -->
                    <div class="card">
                        <div class="card-body">
                            <form action="/Login/admin/crear-eleccion" method="POST" class="needs-validation" novalidate>
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h5 class="border-bottom pb-2 mb-3">Información General</h5>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre_eleccion" class="form-label">Nombre de la Elección *</label>
                                        <input type="text" class="form-control" id="nombre_eleccion" name="nombre_eleccion" 
                                               value="<?php echo htmlspecialchars($datos['nombre_eleccion']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese un nombre para la elección.
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label for="descripcion" class="form-label">Descripción</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($datos['descripcion']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h5 class="border-bottom pb-2 mb-3">Programación</h5>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_inicio" class="form-label">Fecha y Hora de Inicio *</label>
                                        <input type="text" class="form-control flatpickr-datetime" id="fecha_inicio" name="fecha_inicio" 
                                               value="<?php echo htmlspecialchars($datos['fecha_inicio']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor seleccione una fecha y hora de inicio.
                                        </div>
                                        <small class="text-muted">Puede seleccionar cualquier fecha y hora.</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_cierre" class="form-label">Fecha y Hora de Cierre *</label>
                                        <input type="text" class="form-control" id="fecha_cierre" name="fecha_cierre" 
                                               value="<?php echo htmlspecialchars($datos['fecha_cierre']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor seleccione una fecha y hora de cierre.
                                        </div>
                                        <small class="text-muted">Se establece automáticamente 60 minutos después de la fecha de inicio. Mínimo 15 minutos de duración.</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h5 class="border-bottom pb-2 mb-3">Tipos de Votación</h5>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> Por defecto, todos los tipos de usuarios están habilitados para votar.
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="tipo_estudiantes" name="tipos_votacion[]" 
                                                   value="estudiantes" checked>
                                            <label class="form-check-label" for="tipo_estudiantes">
                                                Estudiantes
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="tipo_docentes" name="tipos_votacion[]" 
                                                   value="docentes" checked>
                                            <label class="form-check-label" for="tipo_docentes">
                                                Docentes
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="tipo_administrativos" name="tipos_votacion[]" 
                                                   value="administrativos" checked>
                                            <label class="form-check-label" for="tipo_administrativos">
                                                Administrativos
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h5 class="border-bottom pb-2 mb-3">Configuración Adicional</h5>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="mostrar_resultados_parciales" name="mostrar_resultados_parciales" 
                                                   <?php echo isset($datos['configuracion_adicional']['mostrar_resultados_parciales']) && $datos['configuracion_adicional']['mostrar_resultados_parciales'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="mostrar_resultados_parciales">
                                                Mostrar resultados parciales
                                            </label>
                                        </div>
                                        <div class="form-text">Si se activa, los resultados parciales serán visibles durante la votación.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="permitir_voto_blanco" name="permitir_voto_blanco" 
                                                   <?php echo isset($datos['configuracion_adicional']['permitir_voto_blanco']) && $datos['configuracion_adicional']['permitir_voto_blanco'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="permitir_voto_blanco">
                                                Permitir voto en blanco
                                            </label>
                                        </div>
                                        <div class="form-text">Si se activa, los votantes podrán seleccionar la opción de voto en blanco.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="tiempo_maximo_votacion" class="form-label">Tiempo máximo para completar votación (segundos)</label>
                                        <input type="number" class="form-control" id="tiempo_maximo_votacion" name="tiempo_maximo_votacion" 
                                               value="<?php echo htmlspecialchars($datos['configuracion_adicional']['tiempo_maximo_votacion'] ?? 300); ?>" min="0">
                                        <div class="form-text">Tiempo en segundos que tiene un votante para completar su votación (0 para ilimitado).</div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="reset" class="btn btn-outline-secondary me-md-2">Limpiar</button>
                                    <button type="submit" class="btn btn-primary btn-submit">Crear Elección</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Flatpickr para selector de fecha/hora -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    
    <script>
        // Inicializar Flatpickr para selectores de fecha/hora
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar Flatpickr para fecha de inicio
            const fechaInicioFlatpickr = flatpickr("#fecha_inicio", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                locale: "es",
                minuteIncrement: 5,
                allowInput: true,
                onChange: function(selectedDates, dateStr, instance) {
                    // Cuando cambia la fecha de inicio, actualizar la fecha de cierre
                    if (selectedDates.length > 0) {
                        const fechaInicio = new Date(selectedDates[0]);
                        const fechaCierre = new Date(fechaInicio);
                        
                        // Agregar 60 minutos (1 hora) por defecto
                        fechaCierre.setMinutes(fechaCierre.getMinutes() + 60);
                        
                        // Actualizar el campo de fecha de cierre
                        fechaCierreFlatpickr.setDate(fechaCierre);
                    }
                }
            });
            
            // Inicializar Flatpickr para fecha de cierre
            const fechaCierreFlatpickr = flatpickr("#fecha_cierre", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                locale: "es",
                minuteIncrement: 5,
                allowInput: true
            });
            
            // Validación de formulario Bootstrap
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    // Verificar que la fecha de cierre sea posterior a la de inicio
                    const fechaInicio = new Date(document.getElementById('fecha_inicio').value);
                    const fechaCierre = new Date(document.getElementById('fecha_cierre').value);
                    
                    // Verificar que la fecha de cierre sea posterior a la de inicio
                    if (fechaCierre <= fechaInicio) {
                        alert('La fecha de cierre debe ser posterior a la fecha de inicio.');
                        event.preventDefault();
                        event.stopPropagation();
                        return false;
                    }
                    
                    // Calcular la diferencia en minutos
                    const diferencia = (fechaCierre - fechaInicio) / (1000 * 60);
                    
                    if (diferencia < 15) {
                        alert('La duración mínima de una elección debe ser de 15 minutos.');
                        event.preventDefault();
                        event.stopPropagation();
                        return false;
                    }
                    
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });
    </script>
</body>
</html>
