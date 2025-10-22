<?php
include_once('../includes/functions.php');

// Verificar que está logueado
if (!isLoggedIn()) {
    redirect('login.php');
}

include_once('../includes/database.php');
$database = new Database();
$db = $database->getConnection();

// Obtener tipo de cambio actual
$tipo_cambio = getTipoCambioActual();

// Procesar acciones
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Variables para filtros
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Crear nuevo presupuesto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'create') {
    // Usar isset() para verificar que los campos existen antes de acceder a ellos
    $cliente_nombre = isset($_POST['cliente_nombre']) ? strtoupper(sanitize($_POST['cliente_nombre'])) : '';
    $cliente_telefono = isset($_POST['cliente_telefono']) ? sanitize($_POST['cliente_telefono']) : '';
    $cliente_documento = isset($_POST['cliente_documento']) && !empty($_POST['cliente_documento']) ? sanitize($_POST['cliente_documento']) : '0';
    $productos_json = isset($_POST['productos_json']) ? $_POST['productos_json'] : '[]';
    $total = isset($_POST['total']) ? sanitize($_POST['total']) : '0';
    $notas = isset($_POST['notas']) ? sanitize($_POST['notas']) : '';
    
    // Validar campos obligatorios
    if (empty($cliente_nombre)) {
        $error = "El nombre del cliente es obligatorio";
    } elseif (empty($cliente_telefono)) {
        $error = "El teléfono del cliente es obligatorio";
    } else {
        // Formatear teléfono
        $cliente_telefono = formatearTelefono($cliente_telefono);
        
        // Validar productos
        $productos = json_decode($productos_json, true);
        if (empty($productos) || !is_array($productos)) {
            $error = "Debe agregar al menos un producto al presupuesto";
        } else {
            $query = "INSERT INTO presupuestos (cliente_nombre, cliente_telefono, cliente_documento, productos, total, notas) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$cliente_nombre, $cliente_telefono, $cliente_documento, $productos_json, $total, $notas])) {
                $presupuesto_id = $db->lastInsertId();
                $success = "Presupuesto creado exitosamente (ID: #$presupuesto_id)";
                $action = 'list';
            } else {
                $error = "Error al crear el presupuesto";
            }
        }
    }
}

// Cambiar estado del presupuesto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'cambiar_estado' && $id) {
    $nuevo_estado = isset($_POST['nuevo_estado']) ? sanitize($_POST['nuevo_estado']) : '';
    $notas = isset($_POST['notas']) ? sanitize($_POST['notas']) : '';
    
    if (!empty($nuevo_estado)) {
        $query = "UPDATE presupuestos SET estado = ?, notas = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nuevo_estado, $notas, $id])) {
            // Si el estado es "aceptado", convertir a pedido y descontar stock
            if ($nuevo_estado == 'aceptado') {
                $pedido_id = convertirPresupuestoAPedido($id, $db);
                if ($pedido_id) {
                    $success = "Presupuesto aceptado y convertido a pedido #$pedido_id. Stock descontado automáticamente.";
                } else {
                    $success = "Estado del presupuesto actualizado, pero hubo un error al convertirlo a pedido.";
                }
            } else {
                $success = "Estado del presupuesto actualizado exitosamente";
            }
            $action = 'view';
        } else {
            $error = "Error al actualizar el estado del presupuesto";
        }
    } else {
        $error = "Debe seleccionar un estado válido";
    }
}

// Obtener lista de presupuestos con filtros
if ($action == 'list') {
    $query = "SELECT * FROM presupuestos WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($estado)) {
        $query .= " AND estado = ?";
        $params[] = $estado;
    }
    
    if (!empty($fecha_desde)) {
        $query .= " AND DATE(fecha_creacion) >= ?";
        $params[] = $fecha_desde;
    }
    
    if (!empty($fecha_hasta)) {
        $query .= " AND DATE(fecha_creacion) <= ?";
        $params[] = $fecha_hasta;
    }
    
    $query .= " ORDER BY fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $presupuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener datos de presupuesto específico
if (($action == 'view' || $action == 'cambiar_estado') && $id) {
    $query = "SELECT * FROM presupuestos WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $presupuesto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$presupuesto) {
        $error = "Presupuesto no encontrado";
        $action = 'list';
    } else {
        // Decodificar productos del presupuesto
        $productos_presupuesto = json_decode($presupuesto['productos'], true);
    }
}

// Obtener productos para el selector
$queryProductos = "SELECT id, nombre, codigo, precio_publico, stock, imagen FROM productos WHERE activo = 1 AND stock > 0 ORDER BY nombre";
$stmtProductos = $db->prepare($queryProductos);
$stmtProductos->execute();
$productos_disponibles = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

// Función para formatear teléfono
function formatearTelefono($telefono) {
    // Eliminar espacios y caracteres especiales
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    
    // Si empieza con 0, reemplazar por 595
    if (substr($telefono, 0, 1) == '0') {
        $telefono = '595' . substr($telefono, 1);
    }
    
    // Si tiene 9 dígitos y no empieza con 595, agregar 595
    if (strlen($telefono) == 9 && substr($telefono, 0, 3) != '595') {
        $telefono = '595' . $telefono;
    }
    
    return $telefono;
}

// Función para convertir presupuesto a pedido
function convertirPresupuestoAPedido($presupuesto_id, $db) {
    try {
        // Obtener datos del presupuesto
        $query = "SELECT * FROM presupuestos WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$presupuesto_id]);
        $presupuesto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$presupuesto) {
            return false;
        }
        
        $productos = json_decode($presupuesto['productos'], true);
        
        if (!is_array($productos) || empty($productos)) {
            return false;
        }
        
        // Verificar stock y descontar
        foreach ($productos as $producto) {
            $producto_id = isset($producto['id']) ? $producto['id'] : 0;
            $cantidad = isset($producto['quantity']) ? $producto['quantity'] : 1;
            
            if ($producto_id > 0) {
                // Verificar stock disponible
                $queryStock = "SELECT stock FROM productos WHERE id = ? AND activo = 1";
                $stmtStock = $db->prepare($queryStock);
                $stmtStock->execute([$producto_id]);
                $stock_actual = $stmtStock->fetch(PDO::FETCH_ASSOC);
                
                if (!$stock_actual || $stock_actual['stock'] < $cantidad) {
                    throw new Exception("Stock insuficiente para el producto ID: $producto_id");
                }
                
                // Descontar stock
                $queryUpdateStock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                $stmtUpdateStock = $db->prepare($queryUpdateStock);
                $stmtUpdateStock->execute([$cantidad, $producto_id]);
            }
        }
        
        // Crear pedido
        $queryPedido = "INSERT INTO pedidos (cliente_nombre, cliente_telefono, productos, total, estado, fecha_pedido) VALUES (?, ?, ?, ?, 'completado', NOW())";
        $stmtPedido = $db->prepare($queryPedido);
        $stmtPedido->execute([
            $presupuesto['cliente_nombre'],
            $presupuesto['cliente_telefono'],
            $presupuesto['productos'],
            $presupuesto['total']
        ]);
        
        return $db->lastInsertId();
        
    } catch (Exception $e) {
        error_log("Error al convertir presupuesto a pedido: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Presupuestos - BLOOM Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .badge-pendiente { background-color: #ffc107; color: #000; }
        .badge-aceptado { background-color: #28a745; color: #fff; }
        .badge-rechazado { background-color: #dc3545; color: #fff; }
        .presupuesto-card { border-left: 4px solid; transition: transform 0.2s; }
        .presupuesto-card:hover { transform: translateY(-2px); }
        .presupuesto-pendiente { border-left-color: #ffc107; }
        .presupuesto-aceptado { border-left-color: #28a745; }
        .presupuesto-rechazado { border-left-color: #dc3545; }
        .producto-img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .producto-item { border-bottom: 1px solid #eee; padding: 10px 0; }
        .producto-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-gem"></i> BLOOM Admin
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
                    <a href="pedidos.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart"></i> Pedidos
                    </a>
                    <a href="presupuestos.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-file-invoice-dollar"></i> Presupuestos
                    </a>
                    <a href="config_tipo_cambio.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-dollar-sign"></i> Tipo de Cambio
                    </a>
                    <a href="slider.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-images"></i> Slider Principal
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
                    <!-- Lista de presupuestos -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Gestión de Presupuestos</h2>
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Presupuesto
                        </a>
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
                                        <option value="aceptado" <?php echo $estado == 'aceptado' ? 'selected' : ''; ?>>Aceptado</option>
                                        <option value="rechazado" <?php echo $estado == 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
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

                    <!-- Lista de presupuestos -->
                    <div class="row">
                        <?php if (empty($presupuestos)): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center text-muted py-5">
                                    <i class="fas fa-file-invoice-dollar fa-3x mb-3"></i>
                                    <h5>No se encontraron presupuestos</h5>
                                    <p>No hay presupuestos que coincidan con los filtros seleccionados</p>
                                    <a href="?action=create" class="btn btn-primary mt-3">
                                        <i class="fas fa-plus"></i> Crear Primer Presupuesto
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($presupuestos as $pre): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card presupuesto-card presupuesto-<?php echo $pre['estado']; ?> h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="card-title mb-1">Presupuesto #<?php echo $pre['id']; ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($pre['fecha_creacion'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-<?php echo $pre['estado']; ?>">
                                            <?php echo ucfirst($pre['estado']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <strong><?php echo $pre['cliente_nombre']; ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone"></i> <?php echo $pre['cliente_telefono']; ?>
                                            <?php if ($pre['cliente_documento'] != '0'): ?>
                                            <br><i class="fas fa-id-card"></i> <?php echo $pre['cliente_documento']; ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small><strong>Total:</strong> Gs. <?php echo number_format($pre['total'], 0, ',', '.'); ?></small>
                                    </div>
                                    
                                    <?php if (!empty($pre['notas'])): ?>
                                    <div class="mb-2">
                                        <small><strong>Notas:</strong> <?php echo substr($pre['notas'], 0, 50); ?>...</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex gap-2 mt-3">
                                        <a href="?action=view&id=<?php echo $pre['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                        <a href="?action=cambiar_estado&id=<?php echo $pre['id']; ?>" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i> Estado
                                        </a>
                                        <a href="../admin/pdf/ticket_presupuesto.php?presupuesto_id=<?php echo $pre['id']; ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </a>
                                        <?php if (!empty($pre['cliente_telefono'])): ?>
                                        <a href="https://wa.me/<?php echo $pre['cliente_telefono']; ?>?text=Hola <?php echo urlencode($pre['cliente_nombre']); ?>, te enviamos el presupuesto #<?php echo $pre['id']; ?> de BLOOM - Perfumas y cosmeticos. Total: Gs. <?php echo number_format($pre['total'], 0, ',', '.'); ?>" 
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

                <?php elseif ($action == 'create'): ?>
                    <!-- Crear nuevo presupuesto -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Crear Nuevo Presupuesto</h2>
                        <a href="presupuestos.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>

                    <form method="POST" id="presupuestoForm">
                        <div class="row">
                            <div class="col-md-4">
                                <!-- Información del cliente -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Información del Cliente</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="cliente_nombre" class="form-label">Nombre *</label>
                                            <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cliente_telefono" class="form-label">Teléfono *</label>
                                            <input type="text" class="form-control" id="cliente_telefono" name="cliente_telefono" required>
                                            <small class="text-muted">Ej: 0972366265 o 0972 366 265</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cliente_documento" class="form-label">Documento</label>
                                            <input type="text" class="form-control" id="cliente_documento" name="cliente_documento">
                                            <small class="text-muted">Opcional</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="notas" class="form-label">Notas</label>
                                            <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selector de productos -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Agregar Productos</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="producto_selector" class="form-label">Seleccionar Producto</label>
                                            <select class="form-control" id="producto_selector">
                                                <option value="">Seleccionar producto...</option>
                                                <?php foreach ($productos_disponibles as $prod): ?>
                                                <option value="<?php echo $prod['id']; ?>" 
                                                        data-nombre="<?php echo $prod['nombre']; ?>"
                                                        data-precio="<?php echo $prod['precio_publico']; ?>"
                                                        data-stock="<?php echo $prod['stock']; ?>"
                                                        data-codigo="<?php echo $prod['codigo']; ?>"
                                                        data-imagen="<?php echo $prod['imagen']; ?>">
                                                    <?php echo $prod['nombre']; ?> - Gs. <?php echo number_format($prod['precio_publico'], 0, ',', '.'); ?> (Stock: <?php echo $prod['stock']; ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cantidad" class="form-label">Cantidad</label>
                                            <input type="number" class="form-control" id="cantidad" value="1" min="1">
                                        </div>
                                        
                                        <button type="button" class="btn btn-primary w-100" onclick="agregarProducto()">
                                            <i class="fas fa-plus"></i> Agregar al Presupuesto
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <!-- Lista de productos del presupuesto -->
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Productos del Presupuesto</h5>
                                        <span class="badge bg-primary" id="contador-productos">0 productos</span>
                                    </div>
                                    <div class="card-body">
                                        <div id="lista-productos" class="mb-4">
                                            <p class="text-muted text-center">No hay productos agregados</p>
                                        </div>
                                        
                                        <!-- Totales -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Total en Guaraníes</h6>
                                                        <h4 class="text-success" id="total-gs">Gs. 0</h4>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Total en Dólares</h6>
                                                        <h4 class="text-primary" id="total-usd">$ 0.00</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Campos hidden para los datos del presupuesto -->
                                        <input type="hidden" name="productos_json" id="productos_json" value="[]">
                                        <input type="hidden" name="total" id="total" value="0">
                                        
                                        <div class="d-flex gap-2 mt-4">
                                            <button type="submit" class="btn btn-success" id="btn-guardar" disabled>
                                                <i class="fas fa-save"></i> Guardar Presupuesto
                                            </button>
                                            <a href="presupuestos.php" class="btn btn-secondary">Cancelar</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                <?php elseif ($action == 'view' && isset($presupuesto)): ?>
                    <!-- Detalle del presupuesto -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Detalle del Presupuesto #<?php echo $presupuesto['id']; ?></h2>
                        <div class="btn-group">
                            <a href="../admin/pdf/ticket_presupuesto.php?presupuesto_id=<?php echo $presupuesto['id']; ?>" 
                               target="_blank" class="btn btn-danger">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            <a href="?action=cambiar_estado&id=<?php echo $presupuesto['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Cambiar Estado
                            </a>
                            <?php if (!empty($presupuesto['cliente_telefono'])): ?>
                            <a href="https://wa.me/<?php echo $presupuesto['cliente_telefono']; ?>?text=Hola <?php echo urlencode($presupuesto['cliente_nombre']); ?>, te enviamos el presupuesto #<?php echo $presupuesto['id']; ?> de BLOOM - Perfumes y cosmeticos. Total: Gs. <?php echo number_format($presupuesto['total'], 0, ',', '.'); ?>" 
                               target="_blank" class="btn btn-success">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <?php endif; ?>
                            <a href="presupuestos.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <!-- Productos del presupuesto -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Productos del Presupuesto</h5>
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
                                                <?php foreach ($productos_presupuesto as $producto): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($producto['image'])): ?>
                                                            <img src="../uploads/products/<?php echo $producto['image']; ?>" 
                                                                alt="<?php echo $producto['name']; ?>" class="producto-img me-3">
                                                            <?php else: ?>
                                                            <div class="producto-img bg-light d-flex align-items-center justify-content-center me-3">
                                                                <i class="fas fa-box text-muted"></i>
                                                            </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?php echo $producto['name']; ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?php echo $producto['codigo'] ?? 'Sin código'; ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $producto['quantity']; ?></td>
                                                    <td>Gs. <?php echo number_format($producto['price'], 0, ',', '.'); ?></td>
                                                    <td>
                                                        <strong>Gs. <?php echo number_format($producto['price'] * $producto['quantity'], 0, ',', '.'); ?></strong>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                                    <td><strong class="text-success">Gs. <?php echo number_format($presupuesto['total'], 0, ',', '.'); ?></strong></td>
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
                                    <p><strong>Nombre:</strong><br><?php echo $presupuesto['cliente_nombre']; ?></p>
                                    <p><strong>Teléfono:</strong><br><?php echo $presupuesto['cliente_telefono']; ?></p>
                                    <?php if ($presupuesto['cliente_documento'] != '0'): ?>
                                    <p><strong>Documento:</strong><br><?php echo $presupuesto['cliente_documento']; ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($presupuesto['cliente_telefono'])): ?>
                                    <div class="d-grid gap-2">
                                        <a href="https://wa.me/<?php echo $presupuesto['cliente_telefono']; ?>?text=Hola <?php echo urlencode($presupuesto['cliente_nombre']); ?>, te enviamos el presupuesto #<?php echo $presupuesto['id']; ?> de BLOOM - Perfumes y cosmeticos. Total: Gs. <?php echo number_format($presupuesto['total'], 0, ',', '.'); ?>" 
                                           target="_blank" class="btn btn-success">
                                            <i class="fab fa-whatsapp"></i> Enviar por WhatsApp
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Información del presupuesto -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Información del Presupuesto</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Estado:</strong><br>
                                        <span class="badge badge-<?php echo $presupuesto['estado']; ?>">
                                            <?php echo ucfirst($presupuesto['estado']); ?>
                                        </span>
                                    </p>
                                    <p><strong>Fecha:</strong><br>
                                        <?php echo date('d/m/Y H:i', strtotime($presupuesto['fecha_creacion'])); ?>
                                    </p>
                                    <p><strong>ID Presupuesto:</strong><br>#<?php echo $presupuesto['id']; ?></p>
                                    
                                    <div class="d-grid gap-2 mt-3">
                                        <a href="../admin/pdf/ticket_presupuesto.php?presupuesto_id=<?php echo $presupuesto['id']; ?>" 
                                           target="_blank" class="btn btn-danger">
                                            <i class="fas fa-file-pdf"></i> Generar PDF
                                        </a>
                                    </div>
                                    
                                    <?php if (!empty($presupuesto['notas'])): ?>
                                    <p class="mt-3"><strong>Notas:</strong><br><?php echo nl2br($presupuesto['notas']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($action == 'cambiar_estado' && isset($presupuesto)): ?>
                    <!-- Cambiar estado del presupuesto -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Cambiar Estado - Presupuesto #<?php echo $presupuesto['id']; ?></h2>
                        <a href="?action=view&id=<?php echo $presupuesto['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al presupuesto
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
                                                <option value="pendiente" <?php echo $presupuesto['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="aceptado" <?php echo $presupuesto['estado'] == 'aceptado' ? 'selected' : ''; ?>>Aceptado</option>
                                                <option value="rechazado" <?php echo $presupuesto['estado'] == 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                                            </select>
                                            <small class="text-muted">Al marcar como "Aceptado", se convertirá automáticamente en pedido y se descontará el stock.</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="notas" class="form-label">Notas Adicionales</label>
                                            <textarea class="form-control" id="notas" name="notas" rows="4"><?php echo $presupuesto['notas']; ?></textarea>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Actualizar Estado
                                            </button>
                                            <a href="?action=view&id=<?php echo $presupuesto['id']; ?>" class="btn btn-secondary">Cancelar</a>
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
                                        <span class="badge badge-<?php echo $presupuesto['estado']; ?>">
                                            <?php echo ucfirst($presupuesto['estado']); ?>
                                        </span>
                                    </p>
                                    <p><strong>Cliente:</strong><br><?php echo $presupuesto['cliente_nombre']; ?></p>
                                    <p><strong>Total:</strong><br>Gs. <?php echo number_format($presupuesto['total'], 0, ',', '.'); ?></p>
                                    <p><strong>Fecha Creación:</strong><br><?php echo date('d/m/Y H:i', strtotime($presupuesto['fecha_creacion'])); ?></p>
                                    
                                    <?php if (!empty($presupuesto['cliente_telefono'])): ?>
                                    <div class="d-grid gap-2 mt-3">
                                        <a href="https://wa.me/<?php echo $presupuesto['cliente_telefono']; ?>?text=Hola <?php echo urlencode($presupuesto['cliente_nombre']); ?>, te contactamos sobre el presupuesto #<?php echo $presupuesto['id']; ?> de BLOOM - Perfumes y cosmeticos" 
                                           target="_blank" class="btn btn-success">
                                            <i class="fab fa-whatsapp"></i> Contactar por WhatsApp
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Variables globales
        let productosPresupuesto = [];
        const tipoCambio = <?php echo $tipo_cambio ? $tipo_cambio['venta'] : 7060; ?>;

        // Función para agregar producto al presupuesto
        function agregarProducto() {
            const selector = document.getElementById('producto_selector');
            const cantidadInput = document.getElementById('cantidad');
            const productoId = selector.value;
            const cantidad = parseInt(cantidadInput.value);
            
            if (!productoId) {
                alert('Por favor seleccione un producto');
                return;
            }
            
            if (cantidad < 1) {
                alert('La cantidad debe ser mayor a 0');
                return;
            }
            
            const productoOption = selector.options[selector.selectedIndex];
            const nombre = productoOption.getAttribute('data-nombre');
            const precio = parseFloat(productoOption.getAttribute('data-precio'));
            const stock = parseInt(productoOption.getAttribute('data-stock'));
            const codigo = productoOption.getAttribute('data-codigo');
            const imagen = productoOption.getAttribute('data-imagen');
            
            // Verificar stock
            if (cantidad > stock) {
                alert('No hay suficiente stock. Stock disponible: ' + stock);
                return;
            }
            
            // Verificar si el producto ya está en el presupuesto
            const productoExistente = productosPresupuesto.find(p => p.id == productoId);
            if (productoExistente) {
                productoExistente.quantity += cantidad;
            } else {
                productosPresupuesto.push({
                    id: productoId,
                    name: nombre,
                    price: precio,
                    quantity: cantidad,
                    image: imagen,
                    codigo: codigo,
                    stock: stock
                });
            }
            
            actualizarListaProductos();
            calcularTotales();
            
            // Limpiar selector
            selector.value = '';
            cantidadInput.value = 1;
        }

        // Función para actualizar la lista de productos en la interfaz
        function actualizarListaProductos() {
            const lista = document.getElementById('lista-productos');
            const contador = document.getElementById('contador-productos');
            
            if (productosPresupuesto.length === 0) {
                lista.innerHTML = '<p class="text-muted text-center">No hay productos agregados</p>';
                contador.textContent = '0 productos';
                return;
            }
            
            let html = '';
            productosPresupuesto.forEach((producto, index) => {
                const subtotal = producto.price * producto.quantity;
                html += `
                    <div class="producto-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                ${producto.image ? 
                                    `<img src="../uploads/products/${producto.image}" alt="${producto.name}" class="producto-img me-3">` :
                                    `<div class="producto-img bg-light d-flex align-items-center justify-content-center me-3">
                                        <i class="fas fa-box text-muted"></i>
                                    </div>`
                                }
                                <div>
                                    <strong>${producto.name}</strong>
                                    <br>
                                    <small class="text-muted">${producto.codigo} | Stock: ${producto.stock}</small>
                                    <br>
                                    <small>Gs. ${producto.price.toLocaleString()} x ${producto.quantity} = <strong>Gs. ${subtotal.toLocaleString()}</strong></small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            lista.innerHTML = html;
            contador.textContent = productosPresupuesto.length + ' producto' + (productosPresupuesto.length !== 1 ? 's' : '');
        }

        // Función para eliminar producto del presupuesto
        function eliminarProducto(index) {
            productosPresupuesto.splice(index, 1);
            actualizarListaProductos();
            calcularTotales();
        }

        // Función para calcular totales
        function calcularTotales() {
            let totalGs = 0;
            
            productosPresupuesto.forEach(producto => {
                totalGs += producto.price * producto.quantity;
            });
            
            const totalUsd = totalGs / tipoCambio;
            
            document.getElementById('total-gs').textContent = 'Gs. ' + totalGs.toLocaleString();
            document.getElementById('total-usd').textContent = '$ ' + totalUsd.toFixed(2);
            
            // Actualizar campos hidden del formulario
            document.getElementById('productos_json').value = JSON.stringify(productosPresupuesto);
            document.getElementById('total').value = totalGs;
            
            // Validar si se puede habilitar el botón de guardar
            validarFormulario();
        }

        // Función para validar el formulario completo
        function validarFormulario() {
            const btnGuardar = document.getElementById('btn-guardar');
            const clienteNombre = document.getElementById('cliente_nombre').value.trim();
            const clienteTelefono = document.getElementById('cliente_telefono').value.trim();
            
            const formularioValido = productosPresupuesto.length > 0 && 
                                    clienteNombre !== '' && 
                                    clienteTelefono !== '';
            
            btnGuardar.disabled = !formularioValido;
        }

        // Validar formulario en tiempo real
        document.getElementById('cliente_nombre').addEventListener('input', validarFormulario);
        document.getElementById('cliente_telefono').addEventListener('input', validarFormulario);

        // Formatear teléfono automáticamente
        document.getElementById('cliente_telefono').addEventListener('blur', function(e) {
            let telefono = e.target.value.replace(/[^0-9]/g, '');
            
            if (telefono.length === 9 && telefono.startsWith('09')) {
                telefono = '595' + telefono.substring(1);
            } else if (telefono.length === 10 && telefono.startsWith('5959')) {
                // Ya está formateado correctamente
            } else if (telefono.length === 9 && !telefono.startsWith('09')) {
                telefono = '595' + telefono;
            }
            
            e.target.value = telefono;
            validarFormulario();
        });

        // Validar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            validarFormulario();
        });
    </script>
</body>
</html>