<?php
// Verificar sesión de administrador
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    header("Location: /Login/admin/login");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Personal - <?php echo $mesa['nombre_mesa']; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        .profile-img-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem 1.5rem;
        }
        .personal-card {
            border-left: 4px solid;
            margin-bottom: 1rem;
        }
        .personal-jurado { border-left-color: #dc3545; }
        .personal-testigo-docente { border-left-color: #28a745; }
        .personal-testigo-estudiante { border-left-color: #007bff; }
        .badge-completo {
            background-color: #28a745;
        }
        .badge-incompleto {
            background-color: #dc3545;
        }
        .form-floating {
            margin-bottom: 1rem;
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
                        <h4 class="mb-0">Gestionar Personal</h4>
                        <small class="text-muted">Gestión de personal de mesa virtual</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle text-dark text-decoration-none" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if (isset($_SESSION['admin_imagen']) && !empty($_SESSION['admin_imagen'])) { ?>
                                <img src="<?= $_SESSION['admin_imagen'] ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($_SESSION['admin_nombre']) ?>" class="profile-img-sm me-2">
                            <?php } else { ?>
                                <i class="fas fa-user-circle fa-fw me-2"></i>
                            <?php } ?>
                            <span><?php echo htmlspecialchars($_SESSION['admin_usuario']); ?></span>
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
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/Login/admin/dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/Login/admin/mesas-virtuales">Mesas Virtuales</a></li>
                <li class="breadcrumb-item active">Gestionar Personal</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users"></i> Gestionar Personal - <?php echo isset($mesa['nombre_mesa']) ? htmlspecialchars($mesa['nombre_mesa']) : 'Mesa no encontrada'; ?></h1>
            <a href="/Login/admin/mesas-virtuales<?php echo isset($mesa['id_eleccion']) ? '?eleccion=' . $mesa['id_eleccion'] : ''; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <!-- Mensajes de alerta -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Información de la Mesa -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información de la Mesa</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($mesa) && is_array($mesa)): ?>
                            <h6><strong><?php echo htmlspecialchars($mesa['nombre_mesa']); ?></strong></h6>
                            <p class="text-muted mb-2">Grado: <?php echo htmlspecialchars(ucfirst($mesa['grado_asignado'])); ?></p>
                            <p class="text-muted mb-2">Elección: <?php echo htmlspecialchars($mesa['nombre_eleccion'] ?? 'No especificada'); ?></p>
                            <p class="text-muted mb-3">Estado: 
                                <span class="badge <?php echo ($mesa['estado_mesa'] ?? '') == 'activa' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($mesa['estado_mesa'] ?? 'Desconocido')); ?>
                                </span>
                            </p>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> Error: No se pudo cargar la información de la mesa.
                            </div>
                        <?php endif; ?>

                        <h6>Estado del Personal:</h6>
                        <?php if (isset($validacionPersonal) && is_array($validacionPersonal)): ?>
                            <span class="badge <?php echo $validacionPersonal['completo'] ? 'badge-completo' : 'badge-incompleto'; ?> mb-3">
                                <?php echo $validacionPersonal['completo'] ? 'COMPLETO' : 'INCOMPLETO'; ?>
                                (<?php echo $validacionPersonal['total_actual']; ?>/4)
                            </span>

                            <?php if (!$validacionPersonal['completo']): ?>
                                <div class="alert alert-warning">
                                    <strong>Personal faltante:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php if (isset($validacionPersonal['faltantes']['jurado']) && $validacionPersonal['faltantes']['jurado'] > 0): ?>
                                            <li><?php echo $validacionPersonal['faltantes']['jurado']; ?> Jurado (Padre de familia)</li>
                                        <?php endif; ?>
                                        <?php if (isset($validacionPersonal['faltantes']['testigo_docente']) && $validacionPersonal['faltantes']['testigo_docente'] > 0): ?>
                                            <li><?php echo $validacionPersonal['faltantes']['testigo_docente']; ?> Testigo Docente</li>
                                        <?php endif; ?>
                                        <?php if (isset($validacionPersonal['faltantes']['testigo_estudiante']) && $validacionPersonal['faltantes']['testigo_estudiante'] > 0): ?>
                                            <li><?php echo $validacionPersonal['faltantes']['testigo_estudiante']; ?> Testigo(s) Estudiante</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary mb-3">
                                CARGANDO... (0/4)
                            </span>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Cargando información del personal...
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulario para Agregar Personal -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-plus"></i> Agregar Personal (Jurados y Testigos)</h5>
                        <small class="text-muted">Para testigos, puedes seleccionar de docentes y estudiantes existentes</small>
                    </div>
                    <div class="card-body">
                        <?php if (isset($mesa['id_mesa'])): ?>
                        <form method="POST" action="/Login/admin/agregar-personal" id="formAgregarPersonal">
                            <input type="hidden" name="id_mesa" value="<?php echo $mesa['id_mesa']; ?>">
                            
                            <div class="form-floating">
                                <select class="form-select" id="tipo_personal" name="tipo_personal" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="jurado">Jurado (Padre de familia)</option>
                                    <option value="testigo_docente">Testigo Docente</option>
                                    <option value="testigo_estudiante">Testigo Estudiante</option>
                                </select>
                                <label for="tipo_personal">Tipo de Personal</label>
                            </div>

                            <!-- Campos para Jurado (manual) -->
                            <div id="campos-jurado" style="display: none;">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo">
                                    <label for="nombre_completo">Nombre Completo</label>
                                </div>

                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="documento_identidad" name="documento_identidad">
                                    <label for="documento_identidad">Documento de Identidad</label>
                                </div>

                                <div class="form-floating mb-3">
                                    <input type="tel" class="form-control" id="telefono" name="telefono">
                                    <label for="telefono">Teléfono</label>
                                </div>

                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" name="email">
                                    <label for="email">Email</label>
                                </div>

                                <div class="form-floating mb-3">
                                    <textarea class="form-control" id="observaciones" name="observaciones" style="height: 80px;"></textarea>
                                    <label for="observaciones">Observaciones</label>
                                </div>
                            </div>

                            <!-- Selector para Testigo Docente -->
                            <div id="campos-testigo-docente" style="display: none;">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="docente_seleccionado" name="docente_id">
                                        <option value="">Seleccionar docente...</option>
                                        <?php
                                        // Obtener lista de docentes
                                        $database = new config\Database();
                                        $db = $database->getConnection();
                                        $sqlDocentes = "SELECT id, nombres, apellidos, numero_documento FROM docentes ORDER BY apellidos, nombres";
                                        $resultDocentes = $db->query($sqlDocentes);
                                        while ($docente = $resultDocentes->fetch_assoc()):
                                        ?>
                                            <option value="<?= $docente['id'] ?>" data-nombre="<?= htmlspecialchars($docente['nombres'] . ' ' . $docente['apellidos']) ?>" data-documento="<?= htmlspecialchars($docente['numero_documento']) ?>">
                                                <?= htmlspecialchars($docente['nombres'] . ' ' . $docente['apellidos']) ?> - <?= htmlspecialchars($docente['numero_documento']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <label for="docente_seleccionado">Seleccionar Docente</label>
                                </div>
                            </div>

                            <!-- Selector para Testigo Estudiante -->
                            <div id="campos-testigo-estudiante" style="display: none;">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="estudiante_seleccionado" name="estudiante_id">
                                        <option value="">Seleccionar estudiante...</option>
                                        <?php
                                        // Obtener lista de estudiantes
                                        $sqlEstudiantes = "SELECT id, nombres, apellidos, numero_documento, grado FROM estudiantes ORDER BY grado, apellidos, nombres";
                                        $resultEstudiantes = $db->query($sqlEstudiantes);
                                        while ($estudiante = $resultEstudiantes->fetch_assoc()):
                                        ?>
                                            <option value="<?= $estudiante['id'] ?>" data-nombre="<?= htmlspecialchars($estudiante['nombres'] . ' ' . $estudiante['apellidos']) ?>" data-documento="<?= htmlspecialchars($estudiante['numero_documento']) ?>" data-grado="<?= htmlspecialchars($estudiante['grado']) ?>">
                                                <?= htmlspecialchars($estudiante['nombres'] . ' ' . $estudiante['apellidos']) ?> - Grado <?= htmlspecialchars($estudiante['grado']) ?> - <?= htmlspecialchars($estudiante['numero_documento']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <label for="estudiante_seleccionado">Seleccionar Estudiante</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus"></i> Agregar Personal
                            </button>
                        </form>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> Error: No se puede cargar el formulario. ID de mesa no válido.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Personal Actual -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users"></i> Personal Asignado (<?php echo isset($personalMesa) ? count($personalMesa) : 0; ?>/4)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($personalMesa) && !empty($personalMesa)): ?>
                            <?php 
                            $tiposPersonal = [
                                'jurado' => ['nombre' => 'Jurado (Padre de familia)', 'icono' => 'fas fa-gavel', 'clase' => 'personal-jurado'],
                                'testigo_docente' => ['nombre' => 'Testigo Docente', 'icono' => 'fas fa-chalkboard-teacher', 'clase' => 'personal-testigo-docente'],
                                'testigo_estudiante' => ['nombre' => 'Testigo Estudiante', 'icono' => 'fas fa-user-graduate', 'clase' => 'personal-testigo-estudiante']
                            ];
                            
                            foreach ($tiposPersonal as $tipo => $info):
                                $personalTipo = array_filter($personalMesa, function($p) use ($tipo) {
                                    return $p['tipo_personal'] === $tipo;
                                });
                            ?>
                                <div class="mb-4">
                                    <h6><i class="<?php echo $info['icono']; ?>"></i> <?php echo $info['nombre']; ?></h6>
                                    
                                    <?php if (!empty($personalTipo)): ?>
                                        <?php foreach ($personalTipo as $persona): ?>
                                            <div class="card personal-card <?php echo $info['clase']; ?>">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8">
                                                            <h6 class="mb-1"><?php echo $persona['nombre_completo']; ?></h6>
                                                            <p class="text-muted mb-1">
                                                                <i class="fas fa-id-card"></i> <?php echo $persona['documento_identidad']; ?>
                                                            </p>
                                                            <?php if ($persona['telefono']): ?>
                                                                <p class="text-muted mb-1">
                                                                    <i class="fas fa-phone"></i> <?php echo $persona['telefono']; ?>
                                                                </p>
                                                            <?php endif; ?>
                                                            <?php if ($persona['email']): ?>
                                                                <p class="text-muted mb-1">
                                                                    <i class="fas fa-envelope"></i> <?php echo $persona['email']; ?>
                                                                </p>
                                                            <?php endif; ?>
                                                            <?php if ($persona['observaciones']): ?>
                                                                <p class="text-muted mb-0">
                                                                    <i class="fas fa-comment"></i> <?php echo $persona['observaciones']; ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <small class="text-muted">
                                                                Asignado: <?php echo date('d/m/Y', strtotime($persona['fecha_asignacion'])); ?>
                                                            </small><br>
                                                            <button class="btn btn-sm btn-outline-danger mt-2" 
                                                                    onclick="eliminarPersonal(<?php echo $persona['id_personal']; ?>, '<?php echo $persona['nombre_completo']; ?>')">
                                                                <i class="fas fa-trash"></i> Eliminar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-light">
                                            <i class="fas fa-user-plus"></i> No hay personal asignado para este rol.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5>No hay personal asignado</h5>
                                <p class="text-muted">Agrega personal a esta mesa usando el formulario de la izquierda.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function eliminarPersonal(idPersonal, nombrePersonal) {
            if (confirm(`¿Estás seguro de que deseas eliminar a "${nombrePersonal}" del personal de esta mesa?`)) {
                window.location.href = `/Login/admin/eliminar-personal?id=${idPersonal}&mesa=<?php echo isset($mesa['id_mesa']) ? $mesa['id_mesa'] : '0'; ?>`;
            }
        }

        // Manejar cambio de tipo de personal
        document.getElementById('tipo_personal').addEventListener('change', function() {
            const tipoSeleccionado = this.value;
            
            // Ocultar todos los campos
            document.getElementById('campos-jurado').style.display = 'none';
            document.getElementById('campos-testigo-docente').style.display = 'none';
            document.getElementById('campos-testigo-estudiante').style.display = 'none';
            
            // Limpiar campos
            limpiarCampos();
            
            // Mostrar campos correspondientes
            if (tipoSeleccionado === 'jurado') {
                document.getElementById('campos-jurado').style.display = 'block';
                // Hacer campos obligatorios
                document.getElementById('nombre_completo').required = true;
                document.getElementById('documento_identidad').required = true;
            } else if (tipoSeleccionado === 'testigo_docente') {
                document.getElementById('campos-testigo-docente').style.display = 'block';
                document.getElementById('docente_seleccionado').required = true;
            } else if (tipoSeleccionado === 'testigo_estudiante') {
                document.getElementById('campos-testigo-estudiante').style.display = 'block';
                document.getElementById('estudiante_seleccionado').required = true;
            }
        });

        // Manejar selección de docente
        document.getElementById('docente_seleccionado').addEventListener('change', function() {
            const opcionSeleccionada = this.options[this.selectedIndex];
            if (opcionSeleccionada.value) {
                // Llenar campos automáticamente
                document.getElementById('nombre_completo').value = opcionSeleccionada.getAttribute('data-nombre');
                document.getElementById('documento_identidad').value = opcionSeleccionada.getAttribute('data-documento');
            }
        });

        // Manejar selección de estudiante
        document.getElementById('estudiante_seleccionado').addEventListener('change', function() {
            const opcionSeleccionada = this.options[this.selectedIndex];
            if (opcionSeleccionada.value) {
                // Llenar campos automáticamente
                document.getElementById('nombre_completo').value = opcionSeleccionada.getAttribute('data-nombre');
                document.getElementById('documento_identidad').value = opcionSeleccionada.getAttribute('data-documento');
            }
        });

        function limpiarCampos() {
            // Limpiar todos los campos
            document.getElementById('nombre_completo').value = '';
            document.getElementById('documento_identidad').value = '';
            document.getElementById('telefono').value = '';
            document.getElementById('email').value = '';
            document.getElementById('observaciones').value = '';
            document.getElementById('docente_seleccionado').value = '';
            document.getElementById('estudiante_seleccionado').value = '';
            
            // Quitar required de todos los campos
            document.getElementById('nombre_completo').required = false;
            document.getElementById('documento_identidad').required = false;
            document.getElementById('docente_seleccionado').required = false;
            document.getElementById('estudiante_seleccionado').required = false;
        }

        // Validación del formulario
        document.getElementById('formAgregarPersonal').addEventListener('submit', function(e) {
            const tipoPersonal = document.getElementById('tipo_personal').value;
            
            if (!tipoPersonal) {
                e.preventDefault();
                alert('Por favor selecciona el tipo de personal.');
                return false;
            }
            
            if (tipoPersonal === 'jurado') {
                const nombreCompleto = document.getElementById('nombre_completo').value;
                const documentoIdentidad = document.getElementById('documento_identidad').value;
                
                if (!nombreCompleto || !documentoIdentidad) {
                    e.preventDefault();
                    alert('Por favor completa el nombre completo y documento de identidad para el jurado.');
                    return false;
                }
            } else if (tipoPersonal === 'testigo_docente') {
                const docenteSeleccionado = document.getElementById('docente_seleccionado').value;
                
                if (!docenteSeleccionado) {
                    e.preventDefault();
                    alert('Por favor selecciona un docente para el testigo.');
                    return false;
                }
            } else if (tipoPersonal === 'testigo_estudiante') {
                const estudianteSeleccionado = document.getElementById('estudiante_seleccionado').value;
                
                if (!estudianteSeleccionado) {
                    e.preventDefault();
                    alert('Por favor selecciona un estudiante para el testigo.');
                    return false;
                }
            }
            
            return true;
        });
    </script>
</body>
</html>
