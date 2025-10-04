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
    <title>Gestión de Administrativos - Sistema de Votación</title>
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
                        <h4 class="mb-0">Gestión de Administrativos</h4>
                        <small class="text-muted">Administra el personal administrativo del sistema</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarAdministrativo">
                            <i class="fas fa-plus-circle me-1"></i> Agregar Administrativo
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
                                <input type="text" id="busqueda-administrativos" class="form-control form-control-sm" placeholder="Buscar por cédula o nombre..." value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
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
                    
                    <!-- Tabla de administrativos -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cédula</th>
                                            <th>Nombre Completo</th>
                                            <th>Correo</th>
                                            <th>Cargo</th>
                                            <th>Teléfono</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(isset($administrativos) && !empty($administrativos)): ?>
                                            <?php foreach($administrativos as $administrativo): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($administrativo['cedula']) ?></td>
                                                    <td><?= htmlspecialchars($administrativo['nombre'] . ' ' . $administrativo['apellido']) ?></td>
                                                    <td><?= htmlspecialchars($administrativo['correo'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($administrativo['cargo'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($administrativo['telefono'] ?? 'N/A') ?></td>
                                                    <td>
                                                        <?php if(strtolower($administrativo['estado']) == 'activo'): ?>
                                                            <span class="badge bg-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactivo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning" onclick="editarAdministrativo('<?= $administrativo['cedula'] ?>', '<?= htmlspecialchars($administrativo['nombre']) ?>', '<?= htmlspecialchars($administrativo['apellido']) ?>', '<?= htmlspecialchars($administrativo['correo'] ?? '') ?>', '<?= htmlspecialchars($administrativo['cargo'] ?? '') ?>', '<?= htmlspecialchars($administrativo['telefono'] ?? '') ?>', '<?= htmlspecialchars($administrativo['direccion'] ?? '') ?>', '<?= $administrativo['estado'] ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="confirmarEliminar('<?= $administrativo['cedula'] ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No hay administrativos registrados</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginación -->
                            <?php if(isset($total_paginas)): ?>
                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                <div>
                                    Mostrando <?= ($pagina_actual - 1) * $administrativos_por_pagina + 1 ?> a 
                                    <?= min($pagina_actual * $administrativos_por_pagina, $total_administrativos) ?> 
                                    de <?= $total_administrativos ?> administrativos
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="me-2">Mostrar:</span>
                                    <select class="form-select form-select-sm" style="width: auto;" onchange="cambiarRegistrosPorPagina(this.value)">
                                        <option value="10" <?= $administrativos_por_pagina == 10 ? 'selected' : '' ?>>10</option>
                                        <option value="20" <?= $administrativos_por_pagina == 20 ? 'selected' : '' ?>>20</option>
                                        <option value="50" <?= $administrativos_por_pagina == 50 ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= $administrativos_por_pagina == 100 ? 'selected' : '' ?>>100</option>
                                    </select>
                                </div>
                            </div>
                            <nav aria-label="Navegación de páginas">
                                <ul class="pagination justify-content-center mt-4">
                                    <!-- Botón Anterior -->
                                    <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?>&registros_por_pagina=<?= $administrativos_por_pagina ?>" aria-label="Anterior">
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
                                            <a class="page-link" href="?pagina=<?= $i ?>&registros_por_pagina=<?= $administrativos_por_pagina ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Botón Siguiente -->
                                    <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?>&registros_por_pagina=<?= $administrativos_por_pagina ?>" aria-label="Siguiente">
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
    
    <!-- Modal Agregar Administrativo -->
    <div class="modal fade" id="modalAgregarAdministrativo" tabindex="-1" aria-labelledby="modalAgregarAdministrativoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarAdministrativoLabel">Agregar Nuevo Administrativo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/Login/admin/administrativos/agregar" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cedula" class="form-label">Cédula</label>
                            <input type="text" class="form-control" id="cedula" name="cedula" required>
                        </div>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" required>
                        </div>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo">
                        </div>
                        <div class="mb-3">
                            <label for="cargo" class="form-label">Cargo</label>
                            <select class="form-select" id="cargo" name="cargo">
                                <option value="">Seleccione un cargo</option>
                                <option value="Coordinador(a)">Coordinador(a)</option>
                                <option value="Psicólogo(a)">Psicólogo(a)</option>
                                <option value="Rector(a)">Rector(a)</option>
                                <option value="Contador(a)">Contador(a)</option>
                                <option value="Auxiliar-Sistemas">Auxiliar-Sistemas</option>
                                <option value="Practicante">Practicante</option>
                                <option value="Pagador(a)">Pagador(a)</option>
                                <option value="Secretaria Académica">Secretaria Académica</option>
                                <option value="Asistente de Rectoría">Asistente de Rectoría</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion">
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
    
    <!-- Modal Editar Administrativo -->
    <div class="modal fade" id="modalEditarAdministrativo" tabindex="-1" aria-labelledby="modalEditarAdministrativoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarAdministrativoLabel">Editar Administrativo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/Login/admin/administrativos/editar" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_cedula_original" name="cedula_original">
                        <div class="mb-3">
                            <label for="edit_cedula" class="form-label">Cédula</label>
                            <input type="text" class="form-control" id="edit_cedula" name="cedula" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_apellido" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="edit_apellido" name="apellido" required>
                        </div>
                        <div class="mb-3">
                           <label for="edit_correo" class="form-label">Correo Electrónico</label>
                           <input type="email" class="form-control" id="edit_correo" name="correo">
                       </div>
                        <div class="mb-3">
                            <label for="edit_cargo" class="form-label">Cargo</label>
                            <select class="form-select" id="edit_cargo" name="cargo">
                                <option value="">Seleccione un cargo</option>
                                <option value="Coordinador(a)">Coordinador(a)</option>
                                <option value="Psicólogo(a)">Psicólogo(a)</option>
                                <option value="Rector(a)">Rector(a)</option>
                                <option value="Contador(a)">Contador(a)</option>
                                <option value="Auxiliar-Sistemas">Auxiliar-Sistemas</option>
                                <option value="Practicante">Practicante</option>
                                <option value="Pagador(a)">Pagador(a)</option>
                                <option value="Secretaria Académica">Secretaria Académica</option>
                                <option value="Asistente de Rectoría">Asistente de Rectoría</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="edit_telefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label for="edit_direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="edit_direccion" name="direccion">
                        </div>
                        <div class="mb-3">
                            <label for="edit_estado" class="form-label">Estado</label>
                            <select class="form-select" id="edit_estado" name="estado" required>
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
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
                    <p>¿Está seguro que desea eliminar este administrativo? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="/Login/admin/administrativos/eliminar" method="POST">
                        <input type="hidden" id="eliminar_cedula" name="cedula">
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
        });
        
        function editarAdministrativo(cedula, nombre, apellido, correo, cargo, telefono, direccion, estado) {
            document.getElementById('edit_cedula_original').value = cedula;
            document.getElementById('edit_cedula').value = cedula;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_apellido').value = apellido;
            document.getElementById('edit_correo').value = correo;
            document.getElementById('edit_cargo').value = cargo;
            document.getElementById('edit_telefono').value = telefono;
            document.getElementById('edit_direccion').value = direccion;
            document.getElementById('edit_estado').value = estado;
            
            var modal = new bootstrap.Modal(document.getElementById('modalEditarAdministrativo'));
            modal.show();
        }
        
        function confirmarEliminar(cedula) {
            document.getElementById('eliminar_cedula').value = cedula;
            
            var modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminar'));
            modal.show();
        }
        
        function cambiarRegistrosPorPagina(registros) {
            window.location.href = "?pagina=1&registros_por_pagina=" + registros;
        }

        // Búsqueda en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const inputBusqueda = document.getElementById('busqueda-administrativos');
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