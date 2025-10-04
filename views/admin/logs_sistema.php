<?php
// Verificar si la sesión está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    header("Location: /Login/admin/login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logs del Sistema - Panel Administrativo</title>
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
        .profile-img-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        .log-item {
            border-left: 4px solid;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background-color: #f8f9fa;
        }
        .log-sistema { border-left-color: #6c757d; }
        .log-mesas_virtuales { border-left-color: #0d6efd; }
        .log-elecciones { border-left-color: #198754; }
        .log-usuarios { border-left-color: #fd7e14; }
        .log-error { border-left-color: #dc3545; }
        .log-timestamp {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .badge-tipo {
            font-size: 0.75rem;
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
                        <h4 class="mb-0">Logs del Sistema</h4>
                        <small class="text-muted">Auditoría y registro de actividades</small>
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
                        <div class="alert alert-<?= $_SESSION['tipo'] ?> alert-dismissible fade show" role="alert">
                            <?= $_SESSION['mensaje'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php 
                        unset($_SESSION['mensaje']);
                        unset($_SESSION['tipo']);
                        ?>
                    <?php endif; ?>

                    <!-- Filtros -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="/Login/admin/logs">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="tipo" class="form-label">Tipo</label>
                                        <select class="form-select" id="tipo" name="tipo">
                                            <option value="">Todos los tipos</option>
                                            <option value="sistema" <?= ($_GET['tipo'] ?? '') === 'sistema' ? 'selected' : '' ?>>Sistema</option>
                                            <option value="mesas_virtuales" <?= ($_GET['tipo'] ?? '') === 'mesas_virtuales' ? 'selected' : '' ?>>Mesas Virtuales</option>
                                            <option value="elecciones" <?= ($_GET['tipo'] ?? '') === 'elecciones' ? 'selected' : '' ?>>Elecciones</option>
                                            <option value="usuarios" <?= ($_GET['tipo'] ?? '') === 'usuarios' ? 'selected' : '' ?>>Usuarios</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="fecha_desde" class="form-label">Desde</label>
                                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?= $_GET['fecha_desde'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="fecha_hasta" class="form-label">Hasta</label>
                                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?= $_GET['fecha_hasta'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="limite" class="form-label">Límite</label>
                                        <select class="form-select" id="limite" name="limite">
                                            <option value="25" <?= ($_GET['limite'] ?? '50') === '25' ? 'selected' : '' ?>>25</option>
                                            <option value="50" <?= ($_GET['limite'] ?? '50') === '50' ? 'selected' : '' ?>>50</option>
                                            <option value="100" <?= ($_GET['limite'] ?? '50') === '100' ? 'selected' : '' ?>>100</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                        <a href="/Login/admin/logs" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Limpiar
                                        </a>
                                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#limpiarLogsModal">
                                            <i class="fas fa-trash"></i> Limpiar Logs Antiguos
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Logs -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Registros de Actividad</h5>
                            <span class="badge bg-primary"><?= count($logs) ?> registros</span>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($logs)): ?>
                                <?php foreach ($logs as $log): ?>
                                    <div class="log-item log-<?= $log['tipo'] ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="badge badge-tipo bg-secondary me-2"><?= strtoupper($log['tipo']) ?></span>
                                                    <strong><?= htmlspecialchars($log['descripcion']) ?></strong>
                                                </div>
                                                <div class="log-timestamp">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= date('d/m/Y H:i:s', strtotime($log['fecha_hora'])) ?>
                                                    <?php if ($log['admin_usuario']): ?>
                                                        | <i class="fas fa-user me-1"></i><?= htmlspecialchars($log['admin_usuario']) ?>
                                                    <?php endif; ?>
                                                    <?php if ($log['ip_address']): ?>
                                                        | <i class="fas fa-globe me-1"></i><?= $log['ip_address'] ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($log['datos_adicionales']): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            <code><?= htmlspecialchars($log['datos_adicionales']) ?></code>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5>No hay logs disponibles</h5>
                                    <p class="text-muted">No se encontraron registros con los filtros aplicados.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para limpiar logs -->
    <div class="modal fade" id="limpiarLogsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Limpiar Logs Antiguos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/Login/admin/logs/limpiar">
                    <div class="modal-body">
                        <p>Esta acción eliminará permanentemente los logs más antiguos que el número de días especificado.</p>
                        <div class="mb-3">
                            <label for="dias" class="form-label">Eliminar logs anteriores a (días):</label>
                            <input type="number" class="form-control" id="dias" name="dias" value="90" min="1" max="365" required>
                            <div class="form-text">Por defecto se eliminan logs de más de 90 días.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Limpiar Logs</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
