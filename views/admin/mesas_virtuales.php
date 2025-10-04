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
    <title>Gestión de Mesas Virtuales - Panel Administrativo</title>
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
        .mesa-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .mesa-card:hover {
            transform: translateY(-2px);
        }
        .badge-personal-completo {
            background-color: #28a745;
        }
        .badge-personal-incompleto {
            background-color: #dc3545;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .nivel-card {
            border-left: 4px solid;
            margin-bottom: 1rem;
        }
        .nivel-preescolar { border-left-color: #ff6b6b; }
        .nivel-primaria { border-left-color: #4ecdc4; }
        .nivel-bachillerato { border-left-color: #45b7d1; }
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
                        <h4 class="mb-0">Mesas Virtuales</h4>
                        <small class="text-muted">Gestión de mesas virtuales y personal</small>
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
                    <!-- Mensajes de alerta -->
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
                <?php endif; ?>

                <!-- Selector de Elección -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-vote-yea"></i> Seleccionar Elección</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <select class="form-select" id="selectorEleccion" onchange="cambiarEleccion()">
                                    <option value="">Seleccionar elección...</option>
                                    <?php foreach ($todasElecciones as $eleccion): ?>
                                        <option value="<?php echo $eleccion['id']; ?>" 
                                                <?php echo ($id_eleccion && $eleccion['id'] == $id_eleccion) ? 'selected' : ''; ?>>
                                            <?php echo $eleccion['nombre_eleccion']; ?> 
                                            (<?php echo date('d/m/Y', strtotime($eleccion['fecha_inicio'])); ?>) 
                                            - <?php echo ucfirst($eleccion['estado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <?php if ($id_eleccion): ?>
                                    <div class="btn-group me-2" role="group">
                                        <button class="btn btn-primary" onclick="crearMesas(<?php echo $id_eleccion; ?>)">
                                            <i class="fas fa-plus"></i> Crear Mesas
                                        </button>
                                        <button class="btn btn-success" onclick="generarPersonal(<?php echo $id_eleccion; ?>)">
                                            <i class="fas fa-users"></i> Generar Personal
                                        </button>
                                    </div>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-secondary" onclick="reasignarEstudiantes(<?php echo $id_eleccion; ?>)">
                                            <i class="fas fa-sync"></i> Reasignar
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="limpiarPersonal(<?php echo $id_eleccion; ?>)">
                                            <i class="fas fa-trash"></i> Limpiar Personal
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($id_eleccion && isset($eleccionActual)): ?>
                    <!-- Información de la Elección Seleccionada -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Elección Seleccionada</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><strong><?php echo $eleccionActual['nombre_eleccion']; ?></strong></h6>
                                    <p class="text-muted"><?php echo $eleccionActual['descripcion']; ?></p>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Fecha de Inicio:</small><br>
                                    <strong><?php echo date('d/m/Y H:i', strtotime($eleccionActual['fecha_inicio'])); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Fecha de Cierre:</small><br>
                                    <strong><?php echo date('d/m/Y H:i', strtotime($eleccionActual['fecha_cierre'])); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen por Niveles -->
                    <?php if (!empty($resumenNiveles)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Resumen por Niveles Educativos</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($resumenNiveles as $nivel): ?>
                                        <div class="col-md-4">
                                            <div class="card nivel-card nivel-<?php echo strtolower($nivel['nivel_educativo']); ?>">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo $nivel['nivel_educativo']; ?></h6>
                                                    <div class="row text-center">
                                                        <div class="col-4">
                                                            <small class="text-muted">Mesas</small><br>
                                                            <strong><?php echo $nivel['total_mesas']; ?></strong>
                                                        </div>
                                                        <div class="col-4">
                                                            <small class="text-muted">Estudiantes</small><br>
                                                            <strong><?php echo $nivel['total_estudiantes']; ?></strong>
                                                        </div>
                                                        <div class="col-4">
                                                            <small class="text-muted">Participación</small><br>
                                                            <strong><?php echo $nivel['porcentaje_participacion'] ?? 0; ?>%</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Mesas Virtuales -->
                    <?php if (!empty($estadisticasMesas)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-table"></i> Mesas Virtuales (<?php echo count($estadisticasMesas); ?> mesas)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($estadisticasMesas as $mesa): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card mesa-card h-100" onclick="verMesa(<?php echo $mesa['id_mesa']; ?>)">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0"><?php echo $mesa['nombre_mesa']; ?></h6>
                                                        <span class="badge <?php echo $mesa['estado_personal'] == 'COMPLETA' ? 'badge-personal-completo' : 'badge-personal-incompleto'; ?>">
                                                            <?php echo $mesa['estado_personal']; ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <p class="text-muted small mb-2">Grado: <?php echo ucfirst($mesa['grado_asignado']); ?></p>
                                                    
                                                    <div class="row text-center small">
                                                        <div class="col-4">
                                                            <div class="text-muted">Personal</div>
                                                            <strong><?php echo $mesa['personal_asignado']; ?>/4</strong>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="text-muted">Estudiantes</div>
                                                            <strong><?php echo $mesa['estudiantes_asignados']; ?></strong>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="text-muted">Votos</div>
                                                            <strong><?php echo $mesa['votos_emitidos']; ?></strong>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($mesa['estudiantes_asignados'] > 0): ?>
                                                        <div class="progress mt-2" style="height: 5px;">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="width: <?php echo $mesa['porcentaje_participacion'] ?? 0; ?>%">
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">Participación: <?php echo $mesa['porcentaje_participacion'] ?? 0; ?>%</small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-footer bg-transparent">
                                                    <div class="btn-group w-100" role="group">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); gestionarPersonal(<?php echo $mesa['id_mesa']; ?>)">
                                                            <i class="fas fa-users"></i> Personal
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-info" onclick="event.stopPropagation(); verDetalles(<?php echo $mesa['id_mesa']; ?>)">
                                                            <i class="fas fa-eye"></i> Ver
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-table fa-3x text-muted mb-3"></i>
                                <h5>No hay mesas virtuales creadas</h5>
                                <p class="text-muted">Crea las mesas virtuales para esta elección para comenzar.</p>
                                <button class="btn btn-primary" onclick="crearMesas(<?php echo $id_eleccion; ?>)">
                                    <i class="fas fa-plus"></i> Crear Mesas Virtuales
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-vote-yea fa-3x text-muted mb-3"></i>
                            <h5>No hay elección seleccionada</h5>
                            <p class="text-muted">Selecciona una elección del menú desplegable para gestionar sus mesas virtuales.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function cambiarEleccion() {
            const selector = document.getElementById('selectorEleccion');
            const eleccionId = selector.value;
            
            if (eleccionId) {
                window.location.href = `/Login/admin/mesas-virtuales?eleccion=${eleccionId}`;
            }
        }

        function crearMesas(idEleccion) {
            if (confirm('¿Estás seguro de que deseas crear las mesas virtuales para esta elección? Esto creará 12 mesas (Preescolar a 11°).')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/Login/admin/crear-mesas';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id_eleccion';
                input.value = idEleccion;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function reasignarEstudiantes(idEleccion) {
            if (confirm('¿Deseas reasignar todos los estudiantes a sus mesas correspondientes? Esto sobrescribirá las asignaciones actuales.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/Login/admin/reasignar-estudiantes';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id_eleccion';
                input.value = idEleccion;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function generarPersonal(idEleccion) {
            if (confirm('¿Deseas generar automáticamente el personal para todas las mesas? Esto creará:\n- 1 Jurado (Padre de familia) por mesa\n- 1 Testigo Docente por mesa\n- 2 Testigos Estudiantes por mesa\n\nSolo se agregarán personas a mesas que no tengan personal completo.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/Login/admin/generar-personal';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id_eleccion';
                input.value = idEleccion;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function limpiarPersonal(idEleccion) {
            if (confirm('⚠️ ATENCIÓN: ¿Estás seguro de que deseas eliminar TODO el personal de TODAS las mesas de esta elección?\n\nEsta acción NO se puede deshacer y eliminará:\n- Todos los jurados\n- Todos los testigos docentes\n- Todos los testigos estudiantes\n\n¿Continuar?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/Login/admin/limpiar-personal';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id_eleccion';
                input.value = idEleccion;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function gestionarPersonal(idMesa) {
            window.location.href = `/Login/admin/gestionar-personal?mesa=${idMesa}`;
        }

        function verDetalles(idMesa) {
            window.location.href = `/Login/admin/ver-mesa?id=${idMesa}`;
        }

        function verMesa(idMesa) {
            verDetalles(idMesa);
        }

        // Auto-refresh cada 30 segundos si hay elección seleccionada
        <?php if ($id_eleccion): ?>
        setInterval(function() {
            // Solo refrescar si no hay modales abiertos
            if (!document.querySelector('.modal.show')) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>
    </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
