<?php
// Verificar que el usuario sea administrador
if (!isset($_SESSION['admin_id'])) {
    header('Location: /Login/admin/login');
    exit;
}

// Obtener datos de la mesa
$id_mesa = $mesa['id_mesa'] ?? 0;
$grado_mesa = $mesa['grado_asignado'] ?? '';
$nombre_mesa = $mesa['nombre_mesa'] ?? '';
$id_eleccion = $mesa['id_eleccion'] ?? 0;

// Determinar el grado numérico basado en el nombre de la mesa
$grado_numero = '';
if (preg_match('/Grado (\d+)/', $nombre_mesa, $matches)) {
    $grado_numero = $matches[1];
} elseif (strpos($nombre_mesa, 'Preescolar') !== false) {
    $grado_numero = 'Preescolar';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asignación de Estudiantes - Mesa <?= htmlspecialchars($nombre_mesa) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .estudiante-card {
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
            cursor: pointer;
        }
        .estudiante-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .estudiante-asignado {
            border-left-color: #28a745;
            background-color: #f8fff9;
        }
        .estudiante-no-asignado {
            border-left-color: #6c757d;
        }
        .foto-estudiante {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            background: linear-gradient(45deg, #007bff, #0056b3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .mesa-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px;
        }
        .btn-asignar {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
        }
        .btn-asignar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.3);
        }
        .search-box {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
        }
        .search-box:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
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
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Header -->
                <div class="content-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">
                                <i class="fas fa-user-graduate text-primary"></i>
                                Gestión de Asignación de Estudiantes
                            </h1>
                            <p class="text-muted mb-0">Mesa: <?= htmlspecialchars($nombre_mesa) ?></p>
                        </div>
                        <div>
                            <a href="/Login/admin/mesas-virtuales?eleccion=<?= $id_eleccion ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Mesas
                            </a>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <!-- Alertas -->
                    <?php if(isset($_SESSION['mensaje']) && isset($_SESSION['tipo'])): ?>
                        <div class="alert alert-<?= $_SESSION['tipo'] ?> alert-dismissible fade show" role="alert">
                            <?= $_SESSION['mensaje'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php 
                        unset($_SESSION['mensaje']);
                        unset($_SESSION['tipo']);
                        ?>
                    <?php endif; ?>

                    <!-- Información de la Mesa -->
                    <div class="mesa-info">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="mb-2">
                                    <i class="fas fa-table me-2"></i>
                                    <?= htmlspecialchars($nombre_mesa) ?>
                                </h3>
                                <p class="mb-1"><strong>Grado:</strong> <?= htmlspecialchars($grado_numero) ?></p>
                                <p class="mb-0"><strong>ID Mesa:</strong> <?= $id_mesa ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="stats-card p-3">
                                    <h5 class="mb-1">Estudiantes Asignados</h5>
                                    <h2 class="mb-0" id="contador-asignados"><?= count($estudiantesAsignados ?? []) ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones Rápidas -->
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" class="form-control search-box" id="buscarEstudiante" 
                                               placeholder="Buscar estudiante...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filtroEstado">
                                        <option value="">Todos los estudiantes</option>
                                        <option value="asignados">Solo asignados</option>
                                        <option value="disponibles">Solo disponibles</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoEstudiante">
                                        <i class="fas fa-plus"></i> Registrar Nuevo
                                    </button>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button type="button" class="btn btn-asignar" onclick="guardarAsignaciones()">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Estudiantes -->
                    <div class="card mb-4">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); border-bottom: 3px solid #3498db;">
                            <h5 class="mb-0" style="font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                <i class="fas fa-user-graduate me-2" style="opacity: 0.9;"></i>
                                Estudiantes del Grado <?= htmlspecialchars($grado_numero) ?>
                            </h5>
                            <small style="opacity: 0.9;">Selecciona los estudiantes que deseas asignar a esta mesa virtual</small>
                        </div>
                    </div>
                    <div class="row" id="listaEstudiantes">
                        <?php if (!empty($estudiantesGrado)): ?>
                            <?php foreach ($estudiantesGrado as $estudiante): ?>
                                <?php 
                                $isAsignado = in_array($estudiante['id_estudiante'], array_column($estudiantesAsignados ?? [], 'id_estudiante'));
                                $iniciales = strtoupper(substr($estudiante['nombres'], 0, 1) . substr($estudiante['nombres'], 1, 1));
                                ?>
                                <div class="col-md-6 col-lg-4 mb-3 estudiante-item" 
                                     data-nombre="<?= strtolower($estudiante['nombres']) ?>"
                                     data-documento="<?= $estudiante['numero_documento'] ?>"
                                     data-codigo="<?= $estudiante['codigo_estudiante'] ?>"
                                     data-asignado="<?= $isAsignado ? 'true' : 'false' ?>">
                                    <div class="card estudiante-card h-100 <?= $isAsignado ? 'estudiante-asignado' : 'estudiante-no-asignado' ?>" 
                                         onclick="toggleEstudiante('<?= $estudiante['id_estudiante'] ?>', this)">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="foto-estudiante me-3">
                                                    <?= $iniciales ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="card-title mb-1">
                                                        <?= htmlspecialchars($estudiante['nombres']) ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-id-card"></i> <?= htmlspecialchars($estudiante['numero_documento']) ?>
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <?php if ($isAsignado): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check"></i> Asignado
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-plus"></i> Disponible
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Código</small>
                                                    <strong><?= htmlspecialchars($estudiante['codigo_estudiante']) ?></strong>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Grado</small>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($estudiante['grado']) ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3 d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" 
                                                        onclick="event.stopPropagation(); editarEstudiante('<?= $estudiante['id_estudiante'] ?>')">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <?php if ($isAsignado): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="event.stopPropagation(); desasignarEstudiante('<?= $estudiante['id_estudiante'] ?>')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No hay estudiantes disponibles</h5>
                                        <p class="text-muted">No se encontraron estudiantes para el grado <?= htmlspecialchars($grado_numero) ?></p>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoEstudiante">
                                            <i class="fas fa-plus"></i> Registrar Primer Estudiante
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Estudiante -->
    <div class="modal fade" id="modalNuevoEstudiante" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus"></i> Registrar Nuevo Estudiante y Asignar a Mesa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formNuevoEstudiante" method="POST" action="/Login/admin/registrar-estudiante-mesa">
                    <div class="modal-body">
                        <input type="hidden" name="id_mesa" value="<?= $id_mesa ?>">
                        <input type="hidden" name="asignar_mesa" value="1">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombres *</label>
                                    <input type="text" class="form-control" name="nombres" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Apellidos *</label>
                                    <input type="text" class="form-control" name="apellidos" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Documento *</label>
                                    <select class="form-select" name="tipo_documento" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="TI">Tarjeta de Identidad</option>
                                        <option value="CC">Cédula de Ciudadanía</option>
                                        <option value="CE">Cédula de Extranjería</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Número de Documento *</label>
                                    <input type="text" class="form-control" name="numero_documento" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Grado *</label>
                                    <select class="form-select" name="grado" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="Preescolar" <?= $grado_numero == 'Preescolar' ? 'selected' : '' ?>>Preescolar</option>
                                        <option value="1" <?= $grado_numero == '1' ? 'selected' : '' ?>>1°</option>
                                        <option value="2" <?= $grado_numero == '2' ? 'selected' : '' ?>>2°</option>
                                        <option value="3" <?= $grado_numero == '3' ? 'selected' : '' ?>>3°</option>
                                        <option value="4" <?= $grado_numero == '4' ? 'selected' : '' ?>>4°</option>
                                        <option value="5" <?= $grado_numero == '5' ? 'selected' : '' ?>>5°</option>
                                        <option value="6" <?= $grado_numero == '6' ? 'selected' : '' ?>>6°</option>
                                        <option value="7" <?= $grado_numero == '7' ? 'selected' : '' ?>>7°</option>
                                        <option value="8" <?= $grado_numero == '8' ? 'selected' : '' ?>>8°</option>
                                        <option value="9" <?= $grado_numero == '9' ? 'selected' : '' ?>>9°</option>
                                        <option value="10" <?= $grado_numero == '10' ? 'selected' : '' ?>>10°</option>
                                        <option value="11" <?= $grado_numero == '11' ? 'selected' : '' ?>>11°</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Grupo *</label>
                                    <select class="form-select" name="grupo" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="telefono">
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Nota:</strong> Este estudiante será registrado en el sistema y automáticamente asignado a la mesa <?= htmlspecialchars($nombre_mesa) ?>.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Registrar y Asignar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Estudiante -->
    <div class="modal fade" id="modalEditarEstudiante" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Editar Estudiante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarEstudiante" method="POST" action="/Login/admin/editar-estudiante">
                    <div class="modal-body">
                        <input type="hidden" name="id_estudiante" id="edit_id_estudiante">
                        <input type="hidden" name="id_mesa" value="<?= $id_mesa ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombres *</label>
                                    <input type="text" class="form-control" name="nombres" id="edit_nombres" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Apellidos *</label>
                                    <input type="text" class="form-control" name="apellidos" id="edit_apellidos" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Documento *</label>
                                    <select class="form-select" name="tipo_documento" id="edit_tipo_documento" required>
                                        <option value="TI">Tarjeta de Identidad</option>
                                        <option value="CC">Cédula de Ciudadanía</option>
                                        <option value="CE">Cédula de Extranjería</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Número de Documento *</label>
                                    <input type="text" class="form-control" name="numero_documento" id="edit_numero_documento" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Grado *</label>
                                    <select class="form-select" name="grado" id="edit_grado" required>
                                        <option value="Preescolar">Preescolar</option>
                                        <option value="1">1°</option>
                                        <option value="2">2°</option>
                                        <option value="3">3°</option>
                                        <option value="4">4°</option>
                                        <option value="5">5°</option>
                                        <option value="6">6°</option>
                                        <option value="7">7°</option>
                                        <option value="8">8°</option>
                                        <option value="9">9°</option>
                                        <option value="10">10°</option>
                                        <option value="11">11°</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Código Estudiante</label>
                                    <input type="text" class="form-control" name="codigo_estudiante" id="edit_codigo_estudiante">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let estudiantesSeleccionados = new Set();
        
        // Inicializar estudiantes ya asignados
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.estudiante-asignado').forEach(card => {
                const estudianteId = parseInt(card.getAttribute('onclick').match(/\d+/)[0]);
                estudiantesSeleccionados.add(estudianteId);
            });
        });

        function toggleEstudiante(id, element) {
            if (estudiantesSeleccionados.has(id)) {
                estudiantesSeleccionados.delete(id);
                element.classList.remove('estudiante-asignado');
                element.classList.add('estudiante-no-asignado');
                element.querySelector('.badge').innerHTML = '<i class="fas fa-plus"></i> Disponible';
                element.querySelector('.badge').className = 'badge bg-secondary';
                element.closest('.estudiante-item').setAttribute('data-asignado', 'false');
            } else {
                estudiantesSeleccionados.add(id);
                element.classList.remove('estudiante-no-asignado');
                element.classList.add('estudiante-asignado');
                element.querySelector('.badge').innerHTML = '<i class="fas fa-check"></i> Asignado';
                element.querySelector('.badge').className = 'badge bg-success';
                element.closest('.estudiante-item').setAttribute('data-asignado', 'true');
            }
            
            // Actualizar contador
            document.getElementById('contador-asignados').textContent = estudiantesSeleccionados.size;
        }

        function guardarAsignaciones() {
            if (estudiantesSeleccionados.size === 0) {
                alert('Debe seleccionar al menos un estudiante para asignar a la mesa.');
                return;
            }

            if (confirm(`¿Está seguro de asignar ${estudiantesSeleccionados.size} estudiantes a esta mesa?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/Login/admin/asignar-estudiantes-mesa';

                // Agregar ID de mesa
                const inputMesa = document.createElement('input');
                inputMesa.type = 'hidden';
                inputMesa.name = 'id_mesa';
                inputMesa.value = '<?= $id_mesa ?>';
                form.appendChild(inputMesa);

                // Agregar estudiantes seleccionados
                estudiantesSeleccionados.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'estudiantes[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }

        function editarEstudiante(id) {
            // Aquí cargarías los datos del estudiante via AJAX
            // Por ahora, mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('modalEditarEstudiante'));
            modal.show();
        }

        function desasignarEstudiante(id) {
            if (confirm('¿Está seguro de desasignar este estudiante de la mesa?')) {
                window.location.href = `/Login/admin/desasignar-estudiante-mesa?id_estudiante=${id}&id_mesa=<?= $id_mesa ?>`;
            }
        }

        // Función de búsqueda
        document.getElementById('buscarEstudiante').addEventListener('input', function() {
            filtrarEstudiantes();
        });

        // Función de filtro por estado
        document.getElementById('filtroEstado').addEventListener('change', function() {
            filtrarEstudiantes();
        });

        function filtrarEstudiantes() {
            const searchTerm = document.getElementById('buscarEstudiante').value.toLowerCase();
            const filtroEstado = document.getElementById('filtroEstado').value;
            
            document.querySelectorAll('.estudiante-item').forEach(item => {
                const nombre = item.getAttribute('data-nombre');
                const documento = item.getAttribute('data-documento');
                const codigo = item.getAttribute('data-codigo');
                const asignado = item.getAttribute('data-asignado') === 'true';
                
                let mostrar = true;
                
                // Filtro de búsqueda
                if (searchTerm && !nombre.includes(searchTerm) && 
                    !documento.includes(searchTerm) && !codigo.includes(searchTerm)) {
                    mostrar = false;
                }
                
                // Filtro de estado
                if (filtroEstado === 'asignados' && !asignado) {
                    mostrar = false;
                } else if (filtroEstado === 'disponibles' && asignado) {
                    mostrar = false;
                }
                
                item.style.display = mostrar ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>
