<?php
include_once('../includes/functions.php');

// Verificar que está logueado
if (!isLoggedIn()) {
    redirect('login.php');
}

include_once('../includes/database.php');
$database = new Database();
$db = $database->getConnection();

// Procesar acciones
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Variables para filtros
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Cambiar estado del pedido
if ($action == 'cambiar_estado' && $id) {
    $nuevo_estado = sanitize($_POST['nuevo_estado']);
    $notas = sanitize($_POST['notas']);
    
    $query = "UPDATE pedidos SET estado = ?, notas = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$nuevo_estado, $notas, $id])) {
        $success = "Estado del pedido actualizado exitosamente";
        $action = 'view';
    } else {
        $error = "Error al actualizar el estado del pedido";
    }
}

// Obtener lista de pedidos con filtros
if ($action == 'list') {
    $query = "SELECT * FROM pedidos WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($estado)) {
        $query .= " AND estado = ?";
        $params[] = $estado;
    }
    
    if (!empty($fecha_desde)) {
        $query .= " AND DATE(fecha_pedido) >= ?";
        $params[] = $fecha_desde;
    }
    
    if (!empty($fecha_hasta)) {
        $query .= " AND DATE(fecha_pedido) <= ?";
        $params[] = $fecha_hasta;
    }
    
    $query .= " ORDER BY fecha_pedido DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener datos de pedido específico
if (($action == 'view' || $action == 'cambiar_estado') && $id) {
    $query = "SELECT * FROM pedidos WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        $error = "Pedido no encontrado";
        $action = 'list';
    } else {
        // Decodificar productos del pedido
        $productos_pedido = json_decode($pedido['productos'], true);
    }
}

// Estadísticas de pedidos
$queryStats = "SELECT 
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'procesado' THEN 1 ELSE 0 END) as procesados,
    SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
    SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
    SUM(total) as total_ventas
    FROM pedidos 
    WHERE DATE(fecha_pedido) = CURDATE()";
$stmtStats = $db->prepare($queryStats);
$stmtStats->execute();
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Marco Cos Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .badge-pendiente { background-color: #ffc107; color: #000; }
        .badge-procesado { background-color: #17a2b8; }
        .badge-completado { background-color: #28a745; }
        .badge-cancelado { background-color: #dc3545; }
        .pedido-card { border-left: 4px solid; transition: transform 0.2s; }
        .pedido-card:hover { transform: translateY(-2px); }
        .pedido-pendiente { border-left-color: #ffc107; }
        .pedido-procesado { border-left-color: #17a2b8; }
        .pedido-completado { border-left-color: #28a745; }
        .pedido-cancelado { border-left-color: #dc3545; }
        .producto-img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-gem"></i> Marco Cos Admin
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Hola, <?php echo $_SESSION['user_name']; ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="productos.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-box"></i> Productos
                    </a>
                    <a href="categorias.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tags"></i> Categorías
                    </a>
                    <a href="inventario.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-warehouse"></i> Inventario
                    </a>
                    <a href="pedidos.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-shopping-cart"></i> Pedidos
                    </a>
                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <a href="usuarios.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="col-md-9">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($action == 'list'): ?>
                    <!-- Lista de pedidos -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Gestión de Pedidos</h2>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#estadisticasModal">
                                <i class="fas fa-chart-bar"></i> Estadísticas
                            </button>
                        </div>
                    </div>

                    <!-- Estadísticas rápidas -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card text-white bg-primary">
                                <div class="card-body text-center p-3">
                                    <div class="h5"><?php echo $stats['total_pedidos']; ?></div>
                                    <small>Total Hoy</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-white bg-warning">
                                <div class="card-body text-center p-3">
                                    <div class="h5"><?php echo $stats['pendientes']; ?></div>
                                    <small>Pendientes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-white bg-info">
                                <div class="card-body text-center p-3">
                                    <div class="h5"><?php echo $stats['procesados']; ?></div>
                                    <small>Procesados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-white bg-success">
                                <div class="card-body text-center p-3">
                                    <div class="h5"><?php echo $stats['completados']; ?></div>
                                    <small>Completados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-white bg-danger">
                                <div class="card-body text-center p-3">
                                    <div class="h5"><?php echo $stats['cancelados']; ?></div>
                                    <small>Cancelados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-white bg-dark">
                                <div class="card-body text-center p-3">
                                    <div class="h5">Gs. <?php echo number_format($stats['total_ventas'], 0, ',', '.'); ?></div>
                                    <small>Ventas Hoy</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="action" value="list">
                                
                                <div class="col-md-3">
                                    <label for="estado" class="form-label">Estado</label>
                                    <select class="form-control" id="estado" name="estado">
                                        <option value="">Todos los estados</option>
                                        <option value="pendiente" <?php echo $estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="procesado" <?php echo $estado == 'procesado' ? 'selected' : ''; ?>>Procesado</option>
                                        <option value="completado" <?php echo $estado == 'completado' ? 'selected' : ''; ?>>Completado</option>
                                        <option value="cancelado" <?php echo $estado == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                           value="<?php echo $fecha_desde; ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                           value="<?php echo $fecha_hasta; ?>">
                                </div>
                                
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter"></i> Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de pedidos -->
                    <div class="row">
                        <?php if (empty($pedidos)): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center text-muted py-5">
                                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                    <h5>No se encontraron pedidos</h5>
                                    <p>No hay pedidos que coincidan con los filtros seleccionados</p>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($pedidos as $ped): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card pedido-card pedido-<?php echo $ped['estado']; ?> h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="card-title mb-1">Pedido #<?php echo $ped['id']; ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($ped['fecha_pedido'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-<?php echo $ped['estado']; ?>">
                                            <?php echo ucfirst($ped['estado']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <strong><?php echo $ped['cliente_nombre']; ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone"></i> <?php echo $ped['cliente_telefono']; ?>
                                            <?php if (!empty($ped['cliente_email'])): ?>
                                            <br><i class="fas fa-envelope"></i> <?php echo $ped['cliente_email']; ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small><strong>Total:</strong> Gs. <?php echo number_format($ped['total'], 0, ',', '.'); ?></small>
                                    </div>
                                    
                                    <?php if (!empty($ped['notas'])): ?>
                                    <div class="mb-2">
                                        <small><strong>Notas:</strong> <?php echo substr($ped['notas'], 0, 50); ?>...</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex gap-2 mt-3">
                                        <a href="?action=view&id=<?php echo $ped['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                        <a href="?action=cambiar_estado&id=<?php echo $ped['id']; ?>" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i> Estado
                                        </a>
                                        <?php if (!empty($ped['cliente_telefono'])): ?>
                                        <a href="https://wa.me/595<?php echo substr($ped['cliente_telefono'], -9); ?>?text=Hola <?php echo urlencode($ped['cliente_nombre']); ?>, te contactamos por tu pedido #<?php echo $ped['id']; ?> en Marco Cos" 
                                           target="_blank" class="btn btn-sm btn-outline-success">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                <?php elseif ($action == 'view' && isset($pedido)): ?>
                    <!-- Detalle del pedido -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Detalle del Pedido #<?php echo $pedido['id']; ?></h2>
                        <div class="btn-group">
                            <a href="?action=cambiar_estado&id=<?php echo $pedido['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Cambiar Estado
                            </a>
                            <a href="pedidos.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <!-- Productos del pedido -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Productos del Pedido</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Cantidad</th>
                                                    <th>Precio Unit.</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($productos_pedido as $producto): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($producto['imagen'])): ?>
                                                            <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                                                                 alt="<?php echo $producto['nombre']; ?>" class="producto-img me-3">
                                                            <?php else: ?>
                                                            <div class="producto-img bg-light d-flex align-items-center justify-content-center me-3">
                                                                <i class="fas fa-box text-muted"></i>
                                                            </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?php echo $producto['nombre']; ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?php echo $producto['codigo']; ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $producto['cantidad']; ?></td>
                                                    <td>Gs. <?php echo number_format($producto['precio'], 0, ',', '.'); ?></td>
                                                    <td><strong>Gs. <?php echo number_format($producto['subtotal'], 0, ',', '.'); ?></strong></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                                    <td><strong class="text-success">Gs. <?php echo number_format($pedido['total'], 0, ',', '.'); ?></strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Información del cliente -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Información del Cliente</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Nombre:</strong><br><?php echo $pedido['cliente_nombre']; ?></p>
                                    <p><strong>Teléfono:</strong><br><?php echo $pedido['cliente_telefono']; ?></p>
                                    <?php if (!empty($pedido['cliente_email'])): ?>
                                    <p><strong>Email:</strong><br><?php echo $pedido['cliente_email']; ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($pedido['cliente_telefono'])): ?>
                                    <div class="d-grid gap-2">
                                        <a href="https://wa.me/595<?php echo substr($pedido['cliente_telefono'], -9); ?>?text=Hola <?php echo urlencode($pedido['cliente_nombre']); ?>, te contactamos por tu pedido #<?php echo $pedido['id']; ?> en Marco Cos" 
                                           target="_blank" class="btn btn-success">
                                            <i class="fab fa-whatsapp"></i> Contactar por WhatsApp
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Información del pedido -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Información del Pedido</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Estado:</strong><br>
                                        <span class="badge badge-<?php echo $pedido['estado']; ?>">
                                            <?php echo ucfirst($pedido['estado']); ?>
                                        </span>
                                    </p>
                                    <p><strong>Fecha:</strong><br>
                                        <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?>
                                    </p>
                                    <p><strong>ID Pedido:</strong><br>#<?php echo $pedido['id']; ?></p>
                                    
                                    <?php if (!empty($pedido['notas'])): ?>
                                    <p><strong>Notas:</strong><br><?php echo nl2br($pedido['notas']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($action == 'cambiar_estado' && isset($pedido)): ?>
                    <!-- Cambiar estado del pedido -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Cambiar Estado - Pedido #<?php echo $pedido['id']; ?></h2>
                        <a href="?action=view&id=<?php echo $pedido['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al pedido
                        </a>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="nuevo_estado" class="form-label">Nuevo Estado *</label>
                                            <select class="form-control" id="nuevo_estado" name="nuevo_estado" required>
                                                <option value="pendiente" <?php echo $pedido['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="procesado" <?php echo $pedido['estado'] == 'procesado' ? 'selected' : ''; ?>>Procesado</option>
                                                <option value="completado" <?php echo $pedido['estado'] == 'completado' ? 'selected' : ''; ?>>Completado</option>
                                                <option value="cancelado" <?php echo $pedido['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="notas" class="form-label">Notas Adicionales</label>
                                            <textarea class="form-control" id="notas" name="notas" rows="4" 
                                                      placeholder="Agregar notas sobre el cambio de estado..."><?php echo $pedido['notas']; ?></textarea>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Actualizar Estado
                                            </button>
                                            <a href="?action=view&id=<?php echo $pedido['id']; ?>" class="btn btn-secondary">Cancelar</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Información Actual</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Estado Actual:</strong><br>
                                        <span class="badge badge-<?php echo $pedido['estado']; ?>">
                                            <?php echo ucfirst($pedido['estado']); ?>
                                        </span>
                                    </p>
                                    <p><strong>Cliente:</strong><br><?php echo $pedido['cliente_nombre']; ?></p>
                                    <p><strong>Total:</strong><br>Gs. <?php echo number_format($pedido['total'], 0, ',', '.'); ?></p>
                                    <p><strong>Fecha Pedido:</strong><br><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Estadísticas -->
    <div class="modal fade" id="estadisticasModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Estadísticas de Pedidos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h3><?php echo $stats['total_pedidos']; ?></h3>
                                    <p class="text-muted">Pedidos Hoy</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h3>Gs. <?php echo number_format($stats['total_ventas'], 0, ',', '.'); ?></h3>
                                    <p class="text-muted">Ventas Hoy</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>