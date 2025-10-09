<?php
// Verificar que el usuario sea administrador
if (!isset($_SESSION['admin_id'])) {
    header('Location: /Login/admin/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - Panel de Administración</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .config-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .config-key {
            font-weight: bold;
            color: #495057;
        }
        .config-value {
            background-color: #f8f9fa;
            padding: 0.5rem;
            border-radius: 4px;
            font-family: monospace;
        }
        .badge-type {
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
                        <h4 class="mb-0">Configuración del Sistema</h4>
                        <small class="text-muted">Gestión de parámetros y configuraciones globales</small>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaConfig">
                            <i class="fas fa-plus"></i> Nueva Configuración
                        </button>
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
                    
                    <!-- Configuraciones por categoría -->
                    <?php if (empty($configuracionesPorCategoria)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-cogs fa-3x text-muted mb-3"></i>
                                <h5>Sin Configuraciones</h5>
                                <p class="text-muted">No hay configuraciones definidas en el sistema.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaConfig">
                                    <i class="fas fa-plus"></i> Agregar Primera Configuración
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($configuracionesPorCategoria as $categoria => $configs): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-folder"></i> <?php echo ucfirst($categoria); ?>
                                        <span class="badge bg-secondary ms-2"><?php echo count($configs); ?></span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($configs as $config): ?>
                                        <div class="config-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="config-key">
                                                        <?php echo htmlspecialchars($config['clave']); ?>
                                                        <span class="badge badge-type bg-<?php 
                                                            echo $config['tipo'] === 'boolean' ? 'success' : 
                                                                ($config['tipo'] === 'integer' ? 'info' : 
                                                                ($config['tipo'] === 'datetime' ? 'warning' : 'secondary')); 
                                                        ?> ms-2">
                                                            <?php echo $config['tipo']; ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($config['descripcion'])): ?>
                                                        <div class="text-muted small mb-2">
                                                            <?php echo htmlspecialchars($config['descripcion']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="config-value">
                                                        <?php 
                                                        if ($config['tipo'] === 'boolean') {
                                                            echo $config['valor'] ? '<span class="text-success">Verdadero</span>' : '<span class="text-danger">Falso</span>';
                                                        } elseif ($config['tipo'] === 'json') {
                                                            echo '<pre class="mb-0">' . json_encode($config['valor'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                                                        } else {
                                                            echo htmlspecialchars($config['valor']);
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="text-muted small mt-2">
                                                        Última modificación: <?php echo date('d/m/Y H:i', strtotime($config['fecha_modificacion'])); ?>
                                                    </div>
                                                </div>
                                                <div class="ms-3">
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                            onclick="editarConfiguracion('<?php echo htmlspecialchars($config['clave']); ?>', '<?php echo htmlspecialchars($config['valor']); ?>', '<?php echo htmlspecialchars($config['descripcion']); ?>', '<?php echo $config['tipo']; ?>', '<?php echo $config['categoria']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="/Login/admin/configuracion-sistema/eliminar?clave=<?php echo urlencode($config['clave']); ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       onclick="return confirm('¿Está seguro de eliminar esta configuración?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para nueva/editar configuración -->
    <div class="modal fade" id="modalNuevaConfig" tabindex="-1" aria-labelledby="modalNuevaConfigLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevaConfigLabel">Nueva Configuración</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/Login/admin/configuracion-sistema/actualizar" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="clave" class="form-label">Clave *</label>
                            <input type="text" class="form-control" id="clave" name="clave" required>
                            <div class="form-text">Identificador único de la configuración</div>
                        </div>
                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor *</label>
                            <textarea class="form-control" id="valor" name="valor" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <input type="text" class="form-control" id="descripcion" name="descripcion">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo" class="form-label">Tipo</label>
                                    <select class="form-select" id="tipo" name="tipo">
                                        <option value="string">String</option>
                                        <option value="integer">Integer</option>
                                        <option value="boolean">Boolean</option>
                                        <option value="datetime">DateTime</option>
                                        <option value="json">JSON</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="categoria" class="form-label">Categoría</label>
                                    <input type="text" class="form-control" id="categoria" name="categoria" value="general">
                                </div>
                            </div>
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
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function editarConfiguracion(clave, valor, descripcion, tipo, categoria) {
            document.getElementById('modalNuevaConfigLabel').textContent = 'Editar Configuración';
            document.getElementById('clave').value = clave;
            document.getElementById('clave').readOnly = true;
            document.getElementById('valor').value = valor;
            document.getElementById('descripcion').value = descripcion;
            document.getElementById('tipo').value = tipo;
            document.getElementById('categoria').value = categoria;
            
            var modal = new bootstrap.Modal(document.getElementById('modalNuevaConfig'));
            modal.show();
        }
        
        // Resetear modal cuando se cierre
        document.getElementById('modalNuevaConfig').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalNuevaConfigLabel').textContent = 'Nueva Configuración';
            document.getElementById('clave').readOnly = false;
            document.querySelector('#modalNuevaConfig form').reset();
        });
        
        // Funcionalidad de carga de imagen manejada por profile-image-upload.js
        $(document).ready(function() {
            // Inicialización automática por el archivo externo
        });
    </script>
    
    <script src="/Login/assets/js/profile-image-upload.js"></script>
    
    <!-- Modal incluido desde sidebar.php -->
</body>
</html>
