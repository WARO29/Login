<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    header("Location: /Login/admin/login");
    exit();
}

// Cargar autoload y helper de imágenes
require_once __DIR__ . '/../../autoload.php';
use utils\CandidatoImageHelper;

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
    <title>Gestión de Candidatos - Sistema de Votación</title>
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
        .badge-tipo {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .badge-personero {
            background-color: #0d6efd;
        }
        .badge-representante {
            background-color: #198754;
        }
        .foto-candidato {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        .filtros-busqueda {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        /* Estilos para el botón de cambiar imagen de perfil */
        #change-profile-image {
            z-index: 1000 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
            border: 2px solid #fff !important;
            position: absolute !important;
            bottom: 5px !important;
            right: -5px !important;
            border-radius: 50% !important;
            width: 30px !important;
            height: 30px !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-color: #007bff !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        #change-profile-image:hover {
            transform: scale(1.1) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3) !important;
        }
        #change-profile-image i {
            font-size: 14px !important;
            color: white !important;
        }
        
        /* Optimización para modales en ventanas pequeñas */
        .modal-dialog {
            max-height: 90vh;
        }
        .modal-content {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        .modal-body {
            overflow-y: auto;
            flex: 1;
            max-height: calc(90vh - 120px);
        }
        .modal-header, .modal-footer {
            flex-shrink: 0;
        }
        
        /* Compactar formularios en modales */
        .modal .mb-3 {
            margin-bottom: 0.75rem !important;
        }
        .modal .form-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .modal textarea {
            min-height: 60px;
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
                <div class="content-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Gestión de Candidatos</h4>
                        <small class="text-muted">Administra los candidatos del sistema</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarCandidato">
                            <i class="fas fa-plus-circle me-1"></i> Agregar Candidato
                        </button>
                    </div>
                </div>
                
                <div class="container-fluid px-4 py-3">
                    <!-- Alertas y mensajes -->
                    <?php if(isset($_SESSION['mensaje']) && isset($_SESSION['tipo'])): ?>
                        <div class="alert alert-<?= $_SESSION['tipo'] ?> alert-dismissible fade show" role="alert">
                            <?= $_SESSION['mensaje'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php
                        unset($_SESSION['mensaje']);
                        unset($_SESSION['tipo']);
                        ?>
                    <?php endif; ?>

                    <!-- Estadísticas rápidas -->
                    <?php if(isset($estadisticas)): ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-primary"><?= $estadisticas['total'] ?></h5>
                                    <p class="card-text">Total Candidatos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-info"><?= $estadisticas['personeros'] ?></h5>
                                    <p class="card-text">Personeros</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-success"><?= $estadisticas['representantes'] ?></h5>
                                    <p class="card-text">Representantes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-warning"><?= $estadisticas['grados_con_candidatos'] ?></h5>
                                    <p class="card-text">Grados con Candidatos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Filtros de búsqueda -->
                    <div class="filtros-busqueda">
                        <form method="GET" action="/Login/admin/candidatos" class="row g-3">
                            <div class="col-md-4">
                                <label for="busqueda" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                       placeholder="Nombre, apellido, número..." 
                                       value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="tipo_filtro" class="form-label">Tipo de Candidato</label>
                                <select class="form-select" id="tipo_filtro" name="tipo_filtro">
                                    <option value="">Todos los tipos</option>
                                    <option value="PERSONERO" <?= ($_GET['tipo_filtro'] ?? '') === 'PERSONERO' ? 'selected' : '' ?>>Personeros</option>
                                    <option value="REPRESENTANTE" <?= ($_GET['tipo_filtro'] ?? '') === 'REPRESENTANTE' ? 'selected' : '' ?>>Representantes</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="registros_por_pagina" class="form-label">Mostrar</label>
                                <select class="form-select" name="registros_por_pagina" id="registros_por_pagina">
                                    <option value="10" <?= ($registros_por_pagina ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                                    <option value="20" <?= ($registros_por_pagina ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                                    <option value="50" <?= ($registros_por_pagina ?? 10) == 50 ? 'selected' : '' ?>>50</option>
                                    <option value="100" <?= ($registros_por_pagina ?? 10) == 100 ? 'selected' : '' ?>>100</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="/Login/admin/candidatos" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Tabla de candidatos -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Foto</th>
                                            <th>Nombre</th>
                                            <th>Apellido</th>
                                            <th>Número</th>
                                            <th>Tipo</th>
                                            <th>Grado</th>
                                            <th>Propuesta</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($candidatos)): ?>
                                            <?php foreach ($candidatos as $candidato): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($candidato['foto'])): ?>
                                                            <img src="<?= htmlspecialchars($candidato['foto']) ?>?v=<?= time() ?>" 
                                                                 alt="Foto de <?= htmlspecialchars($candidato['nombre']) ?>" 
                                                                 class="foto-candidato">
                                                        <?php else: ?>
                                                            <div class="foto-candidato bg-secondary d-flex align-items-center justify-content-center">
                                                                <i class="fas fa-user text-white"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($candidato['nombre']) ?></td>
                                                    <td><?= htmlspecialchars($candidato['apellido'] ?? '') ?></td>
                                                    <td><strong><?= htmlspecialchars($candidato['numero'] ?? '') ?></strong></td>
                                                    <td>
                                                        <?php if (!empty($candidato['tipo_candidato'])): ?>
                                                            <?php $tipo_lower = strtolower($candidato['tipo_candidato']); ?>
                                                            <span class="badge badge-tipo badge-<?= $tipo_lower ?>">
                                                                <?= ucfirst($tipo_lower) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Sin tipo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= !empty($candidato['grado']) ? htmlspecialchars($candidato['grado']) : '<span class="text-muted">N/A</span>' ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($candidato['propuesta'])): ?>
                                                            <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                                  title="<?= htmlspecialchars($candidato['propuesta']) ?>">
                                                                <?= htmlspecialchars($candidato['propuesta']) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Sin propuesta</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning me-1" 
                                                                onclick="editarCandidato(<?= htmlspecialchars(json_encode($candidato)) ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" 
                                                                onclick="confirmarEliminar(<?= $candidato['id_candidato'] ?>, '<?= htmlspecialchars($candidato['nombre']) ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">No hay candidatos registrados.</p>
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarCandidato">
                                                        <i class="fas fa-plus-circle me-1"></i> Agregar Primer Candidato
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Paginación -->
                    <?php if(isset($total_paginas) && $total_paginas > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            Mostrando <?= ($pagina_actual - 1) * $registros_por_pagina + 1 ?> a
                            <?= min($pagina_actual * $registros_por_pagina, $total_candidatos) ?>
                            de <?= $total_candidatos ?> candidatos
                        </div>
                        <nav aria-label="Navegación de páginas">
                            <ul class="pagination justify-content-center">
                                <!-- Botón Anterior -->
                                <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?>&registros_por_pagina=<?= $registros_por_pagina ?><?= !empty($_GET['busqueda']) ? '&busqueda=' . urlencode($_GET['busqueda']) : '' ?><?= !empty($_GET['tipo_filtro']) ? '&tipo_filtro=' . urlencode($_GET['tipo_filtro']) : '' ?>">
                                        <span>&laquo;</span>
                                    </a>
                                </li>
                                
                                <!-- Números de página -->
                                <?php
                                $inicio_paginas = max(1, $pagina_actual - 2);
                                $fin_paginas = min($total_paginas, $inicio_paginas + 4);
                                
                                if ($fin_paginas - $inicio_paginas < 4) {
                                    $inicio_paginas = max(1, $fin_paginas - 4);
                                }
                                
                                for ($i = $inicio_paginas; $i <= $fin_paginas; $i++):
                                ?>
                                    <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $i ?>&registros_por_pagina=<?= $registros_por_pagina ?><?= !empty($_GET['busqueda']) ? '&busqueda=' . urlencode($_GET['busqueda']) : '' ?><?= !empty($_GET['tipo_filtro']) ? '&tipo_filtro=' . urlencode($_GET['tipo_filtro']) : '' ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Botón Siguiente -->
                                <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?>&registros_por_pagina=<?= $registros_por_pagina ?><?= !empty($_GET['busqueda']) ? '&busqueda=' . urlencode($_GET['busqueda']) : '' ?><?= !empty($_GET['tipo_filtro']) ? '&tipo_filtro=' . urlencode($_GET['tipo_filtro']) : '' ?>">
                                        <span>&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Agregar Candidato -->
    <div class="modal fade" id="modalAgregarCandidato" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="/Login/admin/candidatos/agregar" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Nuevo Candidato</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombre" required maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Apellido</label>
                                    <input type="text" class="form-control" name="apellido" maxlength="100">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Número de Tarjetón <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="numero" required maxlength="10">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Candidato <span class="text-danger">*</span></label>
                                    <select class="form-select" name="tipo_candidato" id="add_tipo_candidato" required>
                                        <option value="">Seleccione el tipo</option>
                                        <option value="PERSONERO">Personero</option>
                                        <option value="REPRESENTANTE">Representante</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="add_grado_div">
                            <label class="form-label">Grado <span id="add_grado_required" class="text-danger" style="display:none;">*</span></label>
                            <select class="form-select" name="grado" id="add_grado_input">
                                <option value="">Seleccione el grado (opcional)</option>
                                <option value="6">6°</option>
                                <option value="7">7°</option>
                                <option value="8">8°</option>
                                <option value="9">9°</option>
                                <option value="10">10°</option>
                                <option value="11">11°</option>
                            </select>
                            <div class="form-text" id="add_grado_help">Grado del candidato (obligatorio para representantes, opcional para personeros).</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Propuesta</label>
                            <textarea class="form-control" name="propuesta" rows="3" placeholder="Describe la propuesta del candidato..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto</label>
                            <input type="file" class="form-control" name="foto" accept="image/jpeg,image/jpg,image/png,image/gif">
                            <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Candidato</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Candidato -->
    <div class="modal fade" id="modalEditarCandidato" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="/Login/admin/candidatos/editar" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_candidato" id="edit_id_candidato">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Candidato</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombre" id="edit_nombre" required maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Apellido</label>
                                    <input type="text" class="form-control" name="apellido" id="edit_apellido" maxlength="100">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Número de Tarjetón <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="numero" id="edit_numero" required maxlength="10">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Candidato <span class="text-danger">*</span></label>
                                    <select class="form-select" name="tipo_candidato" id="edit_tipo_candidato" required>
                                        <option value="">Seleccione el tipo</option>
                                        <option value="PERSONERO">Personero</option>
                                        <option value="REPRESENTANTE">Representante</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="edit_grado_div">
                            <label class="form-label">Grado <span id="edit_grado_required" class="text-danger" style="display:none;">*</span></label>
                            <select class="form-select" name="grado" id="edit_grado_input">
                                <option value="">Seleccione el grado (opcional)</option>
                                <option value="6">6°</option>
                                <option value="7">7°</option>
                                <option value="8">8°</option>
                                <option value="9">9°</option>
                                <option value="10">10°</option>
                                <option value="11">11°</option>
                            </select>
                            <div class="form-text" id="edit_grado_help">Grado del candidato (obligatorio para representantes, opcional para personeros).</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Propuesta</label>
                            <textarea class="form-control" name="propuesta" id="edit_propuesta" rows="3" placeholder="Describe la propuesta del candidato..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto Nueva (Opcional)</label>
                            <input type="file" class="form-control" name="foto" accept="image/jpeg,image/jpg,image/png,image/gif">
                            <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto Actual</label><br>
                            <img id="preview_foto" src="" alt="Foto Actual" width="100" class="img-thumbnail" style="display: none;">
                            <span id="no_foto_text" class="text-muted">Sin foto</span>
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
    <div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/Login/admin/candidatos/eliminar" method="POST">
                    <input type="hidden" name="id_candidato" id="eliminar_id_candidato">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro que desea eliminar al candidato <strong id="eliminar_nombre_candidato"></strong>?</p>
                        <p class="text-danger small">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar Candidato</button>
                    </div>
                </form>
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
            
            // Funcionalidad de carga de imagen manejada por profile-image-upload.js
        });
        
        // Función para manejar el cambio de tipo de candidato
        function toggleGrado(selectElement, gradoDivId, gradoInputId, requiredSpanId, helpTextId) {
            const gradoInput = document.getElementById(gradoInputId);
            const requiredSpan = document.getElementById(requiredSpanId);
            const helpText = document.getElementById(helpTextId);
            
            if (selectElement.value === 'REPRESENTANTE') {
                // Para representantes, el grado es obligatorio
                gradoInput.setAttribute('required', 'required');
                requiredSpan.style.display = 'inline';
                helpText.textContent = 'Grado obligatorio para representantes (6° a 11°).';
                gradoInput.classList.add('border-warning');
            } else if (selectElement.value === 'PERSONERO') {
                // Para personeros, el grado es opcional
                gradoInput.removeAttribute('required');
                requiredSpan.style.display = 'none';
                helpText.textContent = 'Grado opcional para personeros (6° a 11°).';
                gradoInput.classList.remove('border-warning');
            } else {
                // Sin selección
                gradoInput.removeAttribute('required');
                requiredSpan.style.display = 'none';
                helpText.textContent = 'Grado del candidato (obligatorio para representantes, opcional para personeros).';
                gradoInput.classList.remove('border-warning');
            }
        }

        // Event listeners para los selectores de tipo de candidato
        document.getElementById('add_tipo_candidato').addEventListener('change', function() {
            toggleGrado(this, 'add_grado_div', 'add_grado_input', 'add_grado_required', 'add_grado_help');
        });

        document.getElementById('edit_tipo_candidato').addEventListener('change', function() {
            toggleGrado(this, 'edit_grado_div', 'edit_grado_input', 'edit_grado_required', 'edit_grado_help');
        });

        // Función para editar candidato
        function editarCandidato(candidato) {
            // Llenar campos del modal
            document.getElementById('edit_id_candidato').value = candidato.id_candidato;
            document.getElementById('edit_nombre').value = candidato.nombre || '';
            document.getElementById('edit_apellido').value = candidato.apellido || '';
            document.getElementById('edit_numero').value = candidato.numero || '';
            document.getElementById('edit_tipo_candidato').value = candidato.tipo_candidato || '';
            document.getElementById('edit_grado_input').value = candidato.grado || '';
            document.getElementById('edit_propuesta').value = candidato.propuesta || '';
            
            // Manejar foto actual
            const previewImg = document.getElementById('preview_foto');
            const noFotoText = document.getElementById('no_foto_text');
            
            if (candidato.foto && candidato.foto.trim() !== '') {
                previewImg.src = candidato.foto + '?v=' + new Date().getTime();
                previewImg.style.display = 'block';
                noFotoText.style.display = 'none';
            } else {
                previewImg.style.display = 'none';
                noFotoText.style.display = 'block';
            }
            
            // Actualizar campos según el tipo
            toggleGrado(
                document.getElementById('edit_tipo_candidato'), 
                'edit_grado_div', 
                'edit_grado_input', 
                'edit_grado_required', 
                'edit_grado_help'
            );
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalEditarCandidato'));
            modal.show();
        }

        // Función para confirmar eliminación
        function confirmarEliminar(id, nombre) {
            document.getElementById('eliminar_id_candidato').value = id;
            document.getElementById('eliminar_nombre_candidato').textContent = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminar'));
            modal.show();
        }

        // Auto-dismiss para alertas
        document.addEventListener('DOMContentLoaded', function() {
            const alertElements = document.querySelectorAll('.alert');
            alertElements.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                }, 5000);
            });
        });

        // Validación simple del formulario (sin interceptores)
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const tipoSelect = form.querySelector('select[name="tipo_candidato"]');
                    const gradoSelect = form.querySelector('select[name="grado"]');
                    
                    if (tipoSelect && gradoSelect) {
                        if (tipoSelect.value === 'REPRESENTANTE' && !gradoSelect.value) {
                            e.preventDefault();
                            alert('El grado es obligatorio para candidatos a representante.');
                            gradoSelect.focus();
                            return false;
                        }
                    }
                });
            });
        });
    </script>
    
    <!-- Modal y JavaScript incluidos desde sidebar.php -->
    
</body>
</html>