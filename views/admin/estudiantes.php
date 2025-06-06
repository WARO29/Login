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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="d-flex justify-content-center align-items-center py-3">
                    <h5 class="mb-0">Panel Administrativo</h5>
                </div>
                <hr class="text-white-50">
                <div class="px-3 mb-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-circle fa-3x text-white-50"></i>
                        <p class="mt-2 mb-0"><?= htmlspecialchars($admin_nombre) ?></p>
                        <small class="text-white-50">Administrador</small>
                    </div>
                </div>
                <ul class="nav flex-column px-2">
                    <li class="nav-item">
                        <a href="/Login/admin/panel" class="sidebar-link">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/Login/admin/estudiantes" class="sidebar-link active">
                            <i class="fas fa-users"></i> Estudiantes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/Login/admin/docentes" class="sidebar-link">
                            <i class="fas fa-user-tie"></i> Docentes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-user-graduate"></i> Candidatos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-chart-bar"></i> Resultados
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-chart-pie"></i> Estadísticas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="sidebar-link">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a href="/Login/admin/cerrar-sesion" class="sidebar-link text-danger">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
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
                                                        <button class="btn btn-sm btn-warning" onclick="editarEstudiante('<?= $estudiante['id_estudiante'] ?>', '<?= htmlspecialchars($estudiante['nombre']) ?>', '<?= $estudiante['grado'] ?>', '<?= $estudiante['grupo'] ?>', '<?= $estudiante['estado'] ?>')">
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para ocultar automáticamente las alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alertElements = document.querySelectorAll('.auto-dismiss');
            
            alertElements.forEach(function(alert) {
                // Crear una instancia de Bootstrap Alert
                const bsAlert = new bootstrap.Alert(alert);
                
                // Configurar el temporizador para cerrar la alerta después de 5 segundos
                setTimeout(function() {
                    bsAlert.close();
                }, 5000); // 5000 ms = 5 segundos
            });
        });

        function editarEstudiante(documento, nombre, grado, grupo, estado) {
            document.getElementById('edit_documento').value = documento;
            document.getElementById('edit_nombre').value = nombre;
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
</body>
</html>
