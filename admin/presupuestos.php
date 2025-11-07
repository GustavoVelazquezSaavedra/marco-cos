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

// Búsqueda AJAX de productos
if (isset($_GET['ajax_search']) && $_GET['ajax_search'] == 'productos') {
    $search_term = isset($_GET['q']) ? sanitize($_GET['q']) : '';
    
    $query = "SELECT p.*, c.nombre as categoria_nombre 
              FROM productos p 
              LEFT JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.activo = 1 AND p.stock > 0 
              AND (p.nombre LIKE ? OR p.codigo LIKE ? OR p.precio_publico LIKE ? OR c.nombre LIKE ?)
              ORDER BY p.nombre 
              LIMIT 20";
    
    $stmt = $db->prepare($query);
    $search_like = "%$search_term%";
    $stmt->execute([$search_like, $search_like, $search_like, $search_like]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($productos);
    exit;
}

// Crear nuevo presupuesto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'create') {
    $cliente_nombre = isset($_POST['cliente_nombre']) ? strtoupper(sanitize($_POST['cliente_nombre'])) : '';
    $cliente_telefono = isset($_POST['cliente_telefono']) ? sanitize($_POST['cliente_telefono']) : '';
    $cliente_documento = isset($_POST['cliente_documento']) && !empty($_POST['cliente_documento']) ? sanitize($_POST['cliente_documento']) : '0';
    $productos_json = isset($_POST['productos_json']) ? $_POST['productos_json'] : '[]';
    $total = isset($_POST['total']) ? sanitize($_POST['total']) : '0';
    $notas = isset($_POST['notas']) ? sanitize($_POST['notas']) : '';
    $moneda = isset($_POST['moneda']) ? sanitize($_POST['moneda']) : 'gs';
    $aplicar_iva = isset($_POST['aplicar_iva']) ? sanitize($_POST['aplicar_iva']) : 'no';
    $tipo_descuento = isset($_POST['tipo_descuento']) ? sanitize($_POST['tipo_descuento']) : '';
    $descuento_general = isset($_POST['descuento_general']) ? floatval($_POST['descuento_general']) : 0;
    
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
            $query = "INSERT INTO presupuestos (cliente_nombre, cliente_telefono, cliente_documento, productos, total, notas, moneda, aplicar_iva, tipo_descuento, descuento_general) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$cliente_nombre, $cliente_telefono, $cliente_documento, $productos_json, $total, $notas, $moneda, $aplicar_iva, $tipo_descuento, $descuento_general])) {
                $presupuesto_id = $db->lastInsertId();
                $success = "Presupuesto creado exitosamente (ID: #$presupuesto_id)";
                $action = 'list';
            } else {
                $error = "Error al crear el presupuesto";
            }
        }
    }
}

// Editar presupuesto existente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'edit' && $id) {
    $cliente_nombre = isset($_POST['cliente_nombre']) ? strtoupper(sanitize($_POST['cliente_nombre'])) : '';
    $cliente_telefono = isset($_POST['cliente_telefono']) ? sanitize($_POST['cliente_telefono']) : '';
    $cliente_documento = isset($_POST['cliente_documento']) && !empty($_POST['cliente_documento']) ? sanitize($_POST['cliente_documento']) : '0';
    $productos_json = isset($_POST['productos_json']) ? $_POST['productos_json'] : '[]';
    $total = isset($_POST['total']) ? sanitize($_POST['total']) : '0';
    $notas = isset($_POST['notas']) ? sanitize($_POST['notas']) : '';
    $moneda = isset($_POST['moneda']) ? sanitize($_POST['moneda']) : 'gs';
    $aplicar_iva = isset($_POST['aplicar_iva']) ? sanitize($_POST['aplicar_iva']) : 'no';
    $tipo_descuento = isset($_POST['tipo_descuento']) ? sanitize($_POST['tipo_descuento']) : '';
    $descuento_general = isset($_POST['descuento_general']) ? floatval($_POST['descuento_general']) : 0;
    
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
            $query = "UPDATE presupuestos SET cliente_nombre = ?, cliente_telefono = ?, cliente_documento = ?, productos = ?, total = ?, notas = ?, moneda = ?, aplicar_iva = ?, tipo_descuento = ?, descuento_general = ?, fecha_actualizacion = NOW() WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$cliente_nombre, $cliente_telefono, $cliente_documento, $productos_json, $total, $notas, $moneda, $aplicar_iva, $tipo_descuento, $descuento_general, $id])) {
                $success = "Presupuesto actualizado exitosamente";
                $action = 'view';
            } else {
                $error = "Error al actualizar el presupuesto";
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
if (($action == 'view' || $action == 'edit' || $action == 'cambiar_estado') && $id) {
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
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
        .select2-container--default .select2-selection--single { height: 38px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        .descuento-input { max-width: 120px; }
        .iva-badge { background-color: #17a2b8; }
        .descuento-badge { background-color: #6f42c1; }
        .moneda-badge { background-color: #fd7e14; }
        
        /* Estilos compactos para productos */
        .producto-item-compact {
            border-left: 3px solid #007bff;
            transition: all 0.2s ease;
            font-size: 0.85rem;
            padding: 8px 12px;
            margin-bottom: 6px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .producto-item-compact:hover {
            background-color: #e9ecef !important;
            transform: translateX(2px);
        }
        
        .producto-item-compact .small {
            font-size: 0.75rem;
        }
        
        .producto-item-compact strong {
            font-size: 0.85rem;
        }
        
        .compact-table {
            font-size: 0.8rem;
        }
        
        .compact-table th {
            padding: 4px 8px;
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .compact-table td {
            padding: 4px 8px;
            vertical-align: middle;
        }
        
        /* Estilos para la lista compacta en móviles */
        @media (max-width: 768px) {
            .producto-item-compact .col-3,
            .producto-item-compact .col-5,
            .producto-item-compact .col-3,
            .producto-item-compact .col-1 {
                padding: 0.25rem;
            }
            
            .producto-item-compact {
                padding: 0.5rem !important;
                font-size: 0.8rem;
            }
            
            .producto-item-compact .small {
                font-size: 0.7rem;
            }
        }
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
                                        <small><strong>Total:</strong> 
                                            <?php if ($pre['moneda'] == 'usd'): ?>
                                                $ <?php echo number_format($pre['total'], 2, '.', ','); ?>
                                            <?php else: ?>
                                                Gs. <?php echo number_format($pre['total'], 0, ',', '.'); ?>
                                            <?php endif; ?>
                                        </small>
                                        <?php if ($pre['aplicar_iva'] == 'si'): ?>
                                        <span class="badge iva-badge ms-1">IVA 10%</span>
                                        <?php endif; ?>
                                        <?php if (!empty($pre['tipo_descuento'])): ?>
                                        <span class="badge descuento-badge ms-1">Desc. <?php echo strtoupper($pre['tipo_descuento']); ?></span>
                                        <?php endif; ?>
                                        <span class="badge moneda-badge ms-1"><?php echo strtoupper($pre['moneda']); ?></span>
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
                                        <?php if ($pre['estado'] == 'pendiente'): ?>
                                        <a href="?action=edit&id=<?php echo $pre['id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <?php endif; ?>
                                        <a href="?action=cambiar_estado&id=<?php echo $pre['id']; ?>" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i> Estado
                                        </a>
                                        <a href="../admin/pdf/ticket_presupuesto.php?presupuesto_id=<?php echo $pre['id']; ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </a>
                                        <?php if (!empty($pre['cliente_telefono'])): ?>
                                        <a href="https://wa.me/<?php echo $pre['cliente_telefono']; ?>?text=Hola <?php echo urlencode($pre['cliente_nombre']); ?>, te enviamos el presupuesto #<?php echo $pre['id']; ?> de BLOOM - Perfumes y cosmeticos. Total: <?php echo $pre['moneda'] == 'usd' ? '$' : 'Gs.'; ?> <?php echo number_format($pre['total'], $pre['moneda'] == 'usd' ? 2 : 0, ',', '.'); ?>" 
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

                <?php elseif ($action == 'create' || $action == 'edit'): ?>
                    <!-- Crear o editar presupuesto -->
                    <?php 
                    $is_edit = $action == 'edit';
                    $titulo = $is_edit ? "Editar Presupuesto #" . $presupuesto['id'] : "Crear Nuevo Presupuesto";
                    $form_action = $is_edit ? "?action=edit&id=" . $id : "?action=create";
                    ?>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><?php echo $titulo; ?></h2>
                        <a href="presupuestos.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>

                    <form method="POST" action="<?php echo $form_action; ?>" id="presupuestoForm">
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
                                            <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" 
                                                   value="<?php echo $is_edit ? $presupuesto['cliente_nombre'] : ''; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cliente_telefono" class="form-label">Teléfono *</label>
                                            <input type="text" class="form-control" id="cliente_telefono" name="cliente_telefono" 
                                                   value="<?php echo $is_edit ? $presupuesto['cliente_telefono'] : ''; ?>" required>
                                            <small class="text-muted">Ej: 0972366265 o 0972 366 265</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cliente_documento" class="form-label">Documento</label>
                                            <input type="text" class="form-control" id="cliente_documento" name="cliente_documento"
                                                   value="<?php echo $is_edit ? $presupuesto['cliente_documento'] : ''; ?>">
                                            <small class="text-muted">Opcional</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="notas" class="form-label">Notas</label>
                                            <textarea class="form-control" id="notas" name="notas" rows="3"><?php echo $is_edit ? $presupuesto['notas'] : ''; ?></textarea>
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
                                            <label for="producto_selector" class="form-label">Buscar Producto</label>
                                            <select class="form-control" id="producto_selector" style="width: 100%;">
                                                <option value="">Buscar producto por código, nombre, precio o categoría...</option>
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
                                <!-- Configuración del presupuesto -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Configuración del Presupuesto</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Moneda</label>
                                                    <div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="moneda" id="moneda_gs" value="gs" <?php echo (!$is_edit || $presupuesto['moneda'] == 'gs') ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="moneda_gs">Guaraníes (Gs)</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="moneda" id="moneda_usd" value="usd" <?php echo ($is_edit && $presupuesto['moneda'] == 'usd') ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="moneda_usd">Dólares (USD)</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Aplicar IVA 10%</label>
                                                    <div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="aplicar_iva" id="iva_no" value="no" <?php echo (!$is_edit || $presupuesto['aplicar_iva'] == 'no') ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="iva_no">No</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="aplicar_iva" id="iva_si" value="si" <?php echo ($is_edit && $presupuesto['aplicar_iva'] == 'si') ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="iva_si">Sí</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Descuento General</label>
                                                    <div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="tipo_descuento" id="sin_descuento" value="" <?php echo (!$is_edit || empty($presupuesto['tipo_descuento'])) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="sin_descuento">Sin descuento</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="tipo_descuento" id="descuento_porcentaje" value="porcentaje" <?php echo ($is_edit && $presupuesto['tipo_descuento'] == 'porcentaje') ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="descuento_porcentaje">%</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="tipo_descuento" id="descuento_valor" value="valor" <?php echo ($is_edit && $presupuesto['tipo_descuento'] == 'valor') ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="descuento_valor">Valor</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="descuento_container" style="display: none;">
                                                    <input type="number" class="form-control descuento-input" id="descuento_general" name="descuento_general" 
                                                           value="<?php echo $is_edit ? $presupuesto['descuento_general'] : '0'; ?>" step="0.01" min="0">
                                                    <small class="text-muted" id="descuento_hint"></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Lista de productos del presupuesto - VERSIÓN COMPACTA -->
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Productos del Presupuesto</h5>
                                        <span class="badge bg-primary" id="contador-productos">0 productos</span>
                                    </div>
                                    <div class="card-body">
                                        <div id="lista-productos" class="mb-4">
                                            <p class="text-muted text-center py-3">No hay productos agregados</p>
                                        </div>
                                        
                                        <!-- Totales -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Subtotal</h6>
                                                        <h5 class="text-primary" id="subtotal-display">Gs. 0</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Total</h6>
                                                        <h4 class="text-success" id="total-display">Gs. 0</h4>
                                                        <small id="total-usd-display" class="text-muted">$ 0.00</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Campos hidden para los datos del presupuesto -->
                                        <input type="hidden" name="productos_json" id="productos_json" value="<?php echo $is_edit ? htmlspecialchars($presupuesto['productos']) : '[]'; ?>">
                                        <input type="hidden" name="total" id="total" value="<?php echo $is_edit ? $presupuesto['total'] : '0'; ?>">
                                        
                                        <div class="d-flex gap-2 mt-4">
                                            <button type="submit" class="btn btn-success" id="btn-guardar" <?php echo $is_edit ? '' : 'disabled'; ?>>
                                                <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar' : 'Guardar'; ?> Presupuesto
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
                            <?php if ($presupuesto['estado'] == 'pendiente'): ?>
                            <a href="?action=edit&id=<?php echo $presupuesto['id']; ?>" class="btn btn-info">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <?php endif; ?>
                            <a href="?action=cambiar_estado&id=<?php echo $presupuesto['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Cambiar Estado
                            </a>
                            <?php if (!empty($presupuesto['cliente_telefono'])): ?>
                            <a href="https://wa.me/<?php echo $presupuesto['cliente_telefono']; ?>?text=Hola <?php echo urlencode($presupuesto['cliente_nombre']); ?>, te enviamos el presupuesto #<?php echo $presupuesto['id']; ?> de BLOOM - Perfumes y cosmeticos. Total: <?php echo $presupuesto['moneda'] == 'usd' ? '$' : 'Gs.'; ?> <?php echo number_format($presupuesto['total'], $presupuesto['moneda'] == 'usd' ? 2 : 0, ',', '.'); ?>" 
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
                            <!-- Productos del presupuesto - VERSIÓN COMPACTA -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Productos del Presupuesto</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped compact-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th width="15%">Código</th>
                                                    <th width="10%">Cantidad</th>
                                                    <th width="45%">Producto</th>
                                                    <th width="15%">P.Unit</th>
                                                    <th width="15%">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($productos_presupuesto as $producto): ?>
                                                <tr>
                                                    <td><strong class="text-primary"><?php echo $producto['codigo'] ?? 'Sin código'; ?></strong></td>
                                                    <td><?php echo $producto['quantity']; ?></td>
                                                    <td>
                                                        <strong><?php echo $producto['name']; ?></strong>
                                                        <?php if (isset($producto['categoria'])): ?>
                                                        <br><small class="text-muted"><?php echo $producto['categoria']; ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($presupuesto['moneda'] == 'usd'): ?>
                                                            $ <?php echo number_format($producto['price'], 2, '.', ','); ?>
                                                        <?php else: ?>
                                                            Gs. <?php echo number_format($producto['price'], 0, ',', '.'); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong class="text-success">
                                                            <?php if ($presupuesto['moneda'] == 'usd'): ?>
                                                                $ <?php echo number_format($producto['price'] * $producto['quantity'], 2, '.', ','); ?>
                                                            <?php else: ?>
                                                                Gs. <?php echo number_format($producto['price'] * $producto['quantity'], 0, ',', '.'); ?>
                                                            <?php endif; ?>
                                                        </strong>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Totales en versión compacta -->
                                    <div class="p-3 bg-light">
                                        <?php 
                                        $subtotal = 0;
                                        foreach ($productos_presupuesto as $producto) {
                                            $subtotal += $producto['price'] * $producto['quantity'];
                                        }
                                        
                                        $iva = 0;
                                        if ($presupuesto['aplicar_iva'] == 'si') {
                                            $iva = $subtotal * 0.10;
                                        }
                                        
                                        $descuento = 0;
                                        if (!empty($presupuesto['tipo_descuento'])) {
                                            if ($presupuesto['tipo_descuento'] == 'porcentaje') {
                                                $descuento = $subtotal * ($presupuesto['descuento_general'] / 100);
                                            } else {
                                                $descuento = $presupuesto['descuento_general'];
                                            }
                                        }
                                        
                                        $total_calculado = $subtotal + $iva - $descuento;
                                        ?>
                                        
                                        <div class="row text-end">
                                            <div class="col-6"><strong>Subtotal:</strong></div>
                                            <div class="col-6">
                                                <strong>
                                                    <?php if ($presupuesto['moneda'] == 'usd'): ?>
                                                        $ <?php echo number_format($subtotal, 2, '.', ','); ?>
                                                    <?php else: ?>
                                                        Gs. <?php echo number_format($subtotal, 0, ',', '.'); ?>
                                                    <?php endif; ?>
                                                </strong>
                                            </div>
                                            
                                            <?php if ($presupuesto['aplicar_iva'] == 'si'): ?>
                                            <div class="col-6"><strong>IVA 10%:</strong></div>
                                            <div class="col-6">
                                                <strong>
                                                    <?php if ($presupuesto['moneda'] == 'usd'): ?>
                                                        $ <?php echo number_format($iva, 2, '.', ','); ?>
                                                    <?php else: ?>
                                                        Gs. <?php echo number_format($iva, 0, ',', '.'); ?>
                                                    <?php endif; ?>
                                                </strong>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($presupuesto['tipo_descuento'])): ?>
                                            <div class="col-6"><strong>Descuento:</strong></div>
                                            <div class="col-6">
                                                <strong class="text-danger">
                                                    <?php if ($presupuesto['moneda'] == 'usd'): ?>
                                                        - $ <?php echo number_format($descuento, 2, '.', ','); ?>
                                                    <?php else: ?>
                                                        - Gs. <?php echo number_format($descuento, 0, ',', '.'); ?>
                                                    <?php endif; ?>
                                                </strong>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="col-6"><strong>Total:</strong></div>
                                            <div class="col-6">
                                                <strong class="text-success">
                                                    <?php if ($presupuesto['moneda'] == 'usd'): ?>
                                                        $ <?php echo number_format($total_calculado, 2, '.', ','); ?>
                                                    <?php else: ?>
                                                        Gs. <?php echo number_format($total_calculado, 0, ',', '.'); ?>
                                                    <?php endif; ?>
                                                </strong>
                                            </div>
                                        </div>
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
                                        <a href="https://wa.me/<?php echo $presupuesto['cliente_telefono']; ?>?text=Hola <?php echo urlencode($presupuesto['cliente_nombre']); ?>, te enviamos el presupuesto #<?php echo $presupuesto['id']; ?> de BLOOM - Perfumes y cosmeticos. Total: <?php echo $presupuesto['moneda'] == 'usd' ? '$' : 'Gs.'; ?> <?php echo number_format($presupuesto['total'], $presupuesto['moneda'] == 'usd' ? 2 : 0, ',', '.'); ?>" 
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
                                    <p><strong>Moneda:</strong><br>
                                        <span class="badge moneda-badge"><?php echo strtoupper($presupuesto['moneda']); ?></span>
                                    </p>
                                    <p><strong>IVA:</strong><br>
                                        <span class="badge <?php echo $presupuesto['aplicar_iva'] == 'si' ? 'iva-badge' : 'bg-secondary'; ?>">
                                            <?php echo $presupuesto['aplicar_iva'] == 'si' ? 'SÍ (10%)' : 'NO'; ?>
                                        </span>
                                    </p>
                                    <?php if (!empty($presupuesto['tipo_descuento'])): ?>
                                    <p><strong>Descuento:</strong><br>
                                        <span class="badge descuento-badge">
                                            <?php echo strtoupper($presupuesto['tipo_descuento']); ?>: 
                                            <?php echo number_format($presupuesto['descuento_general'], $presupuesto['tipo_descuento'] == 'porcentaje' ? 0 : 2); ?>
                                            <?php echo $presupuesto['tipo_descuento'] == 'porcentaje' ? '%' : ($presupuesto['moneda'] == 'usd' ? '$' : 'Gs.'); ?>
                                        </span>
                                    </p>
                                    <?php endif; ?>
                                    <p><strong>Fecha Creación:</strong><br>
                                        <?php echo date('d/m/Y H:i', strtotime($presupuesto['fecha_creacion'])); ?>
                                    </p>
                                    <?php if ($presupuesto['fecha_actualizacion'] != $presupuesto['fecha_creacion']): ?>
                                    <p><strong>Última Actualización:</strong><br>
                                        <?php echo date('d/m/Y H:i', strtotime($presupuesto['fecha_actualizacion'])); ?>
                                    </p>
                                    <?php endif; ?>
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
                                    <p><strong>Total:</strong><br>
                                        <?php if ($presupuesto['moneda'] == 'usd'): ?>
                                            $ <?php echo number_format($presupuesto['total'], 2, '.', ','); ?>
                                        <?php else: ?>
                                            Gs. <?php echo number_format($presupuesto['total'], 0, ',', '.'); ?>
                                        <?php endif; ?>
                                    </p>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.min.js"></script>
    
    <script>
    // Variables globales
    let productosPresupuesto = <?php echo ($action == 'edit' || $action == 'create') && isset($presupuesto) ? $presupuesto['productos'] : '[]'; ?>;
    const tipoCambio = <?php echo $tipo_cambio ? $tipo_cambio['venta'] : 7060; ?>;
    let monedaActual = '<?php echo ($action == 'edit' || $action == 'create') && isset($presupuesto) ? $presupuesto['moneda'] : 'gs'; ?>';
    let aplicarIva = '<?php echo ($action == 'edit' || $action == 'create') && isset($presupuesto) ? $presupuesto['aplicar_iva'] : 'no'; ?>';
    let tipoDescuento = '<?php echo ($action == 'edit' || $action == 'create') && isset($presupuesto) ? $presupuesto['tipo_descuento'] : ''; ?>';
    let descuentoGeneral = <?php echo ($action == 'edit' || $action == 'create') && isset($presupuesto) ? $presupuesto['descuento_general'] : 0; ?>;

    // Inicializar Select2 para búsqueda de productos
    $(document).ready(function() {
        $('#producto_selector').select2({
            language: 'es',
            placeholder: 'Buscar producto por código, nombre, precio o categoría...',
            minimumInputLength: 2,
            ajax: {
                url: 'presupuestos.php?ajax_search=productos',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(function(producto) {
                            return {
                                id: producto.id,
                                text: producto.codigo + ' - ' + producto.nombre + ' - Gs. ' + Number(producto.precio_publico).toLocaleString() + ' - ' + (producto.categoria_nombre || 'Sin categoría'),
                                producto: producto
                            };
                        })
                    };
                },
                cache: true
            }
        });

        // Cargar productos existentes si estamos editando
        if (productosPresupuesto.length > 0) {
            actualizarListaProductos();
            calcularTotales();
        }

        // Configurar eventos para opciones de descuento
        $('input[name="tipo_descuento"]').change(function() {
            tipoDescuento = $(this).val();
            if (tipoDescuento) {
                $('#descuento_container').show();
                if (tipoDescuento === 'porcentaje') {
                    $('#descuento_hint').text('Ingrese el porcentaje de descuento (ej: 10 para 10%)');
                    $('#descuento_general').attr('step', '1').attr('min', '0').attr('max', '100');
                } else {
                    $('#descuento_hint').text('Ingrese el valor del descuento');
                    $('#descuento_general').attr('step', '0.01').attr('min', '0').removeAttr('max');
                }
                $('#descuento_general').val(descuentoGeneral);
            } else {
                $('#descuento_container').hide();
                descuentoGeneral = 0;
            }
            calcularTotales();
        });

        // Inicializar estado del descuento
        if (tipoDescuento) {
            $('input[name="tipo_descuento"][value="' + tipoDescuento + '"]').prop('checked', true).trigger('change');
        }

        // Eventos para moneda e IVA
        $('input[name="moneda"]').change(function() {
            monedaActual = $(this).val();
            actualizarListaProductos();
            calcularTotales();
        });

        $('input[name="aplicar_iva"]').change(function() {
            aplicarIva = $(this).val();
            calcularTotales();
        });

        $('#descuento_general').on('input', function() {
            descuentoGeneral = parseFloat($(this).val()) || 0;
            calcularTotales();
        });
    });

    // Función para agregar producto al presupuesto
    function agregarProducto() {
        const selector = $('#producto_selector');
        const selectedData = selector.select2('data');
        const cantidadInput = document.getElementById('cantidad');
        
        if (!selectedData || selectedData.length === 0) {
            alert('Por favor seleccione un producto');
            return;
        }
        
        const productoData = selectedData[0].producto;
        const cantidad = parseInt(cantidadInput.value);
        
        if (cantidad < 1) {
            alert('La cantidad debe ser mayor a 0');
            return;
        }
        
        // Verificar stock
        if (cantidad > productoData.stock) {
            alert('No hay suficiente stock. Stock disponible: ' + productoData.stock);
            return;
        }
        
        // Verificar si el producto ya está en el presupuesto
        const productoExistente = productosPresupuesto.find(p => p.id == productoData.id);
        if (productoExistente) {
            productoExistente.quantity += cantidad;
        } else {
            productosPresupuesto.push({
                id: productoData.id,
                name: productoData.nombre,
                price: parseFloat(productoData.precio_publico),
                quantity: cantidad,
                codigo: productoData.codigo,
                stock: productoData.stock,
                categoria: productoData.categoria_nombre
            });
        }
        
        actualizarListaProductos();
        calcularTotales();
        
        // Limpiar selector
        selector.val(null).trigger('change');
        cantidadInput.value = 1;
    }

    // Función para actualizar la lista de productos en la interfaz - VERSIÓN COMPACTA
    function actualizarListaProductos() {
        const lista = document.getElementById('lista-productos');
        const contador = document.getElementById('contador-productos');
        
        if (productosPresupuesto.length === 0) {
            lista.innerHTML = '<p class="text-muted text-center py-3">No hay productos agregados</p>';
            contador.textContent = '0 productos';
            return;
        }
        
        let html = '';
        productosPresupuesto.forEach((producto, index) => {
            // Convertir precio según moneda actual
            let precioMostrar = producto.price;
            let subtotalMostrar = producto.price * producto.quantity;
            
            if (monedaActual === 'usd') {
                precioMostrar = producto.price / tipoCambio;
                subtotalMostrar = (producto.price * producto.quantity) / tipoCambio;
            }
            
            // Formatear precios
            const precioFormateado = precioMostrar.toLocaleString(monedaActual === 'usd' ? 'en-US' : 'es-PY', {
                minimumFractionDigits: monedaActual === 'usd' ? 2 : 0,
                maximumFractionDigits: monedaActual === 'usd' ? 2 : 0
            });
            
            const subtotalFormateado = subtotalMostrar.toLocaleString(monedaActual === 'usd' ? 'en-US' : 'es-PY', {
                minimumFractionDigits: monedaActual === 'usd' ? 2 : 0,
                maximumFractionDigits: monedaActual === 'usd' ? 2 : 0
            });
            
            html += `
                <div class="producto-item-compact">
                    <div class="row align-items-center">
                        <!-- Código y Cantidad -->
                        <div class="col-3">
                            <div class="small text-muted">Código</div>
                            <strong class="text-primary">${producto.codigo}</strong>
                            <div class="small text-muted mt-1">Cantidad</div>
                            <strong>${producto.quantity}</strong>
                        </div>
                        
                        <!-- Producto y Categoría -->
                        <div class="col-5">
                            <div class="small text-muted">Producto</div>
                            <strong>${producto.name}</strong>
                            ${producto.categoria ? '<div class="small text-muted">' + producto.categoria + '</div>' : ''}
                        </div>
                        
                        <!-- Precios -->
                        <div class="col-3">
                            <div class="small text-muted">P.Unit</div>
                            <div class="small">${monedaActual === 'usd' ? '$' : 'Gs.'} ${precioFormateado}</div>
                            <div class="small text-muted mt-1">Subtotal</div>
                            <strong class="text-success">${monedaActual === 'usd' ? '$' : 'Gs.'} ${subtotalFormateado}</strong>
                        </div>
                        
                        <!-- Botón Eliminar -->
                        <div class="col-1 text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(${index})" title="Eliminar producto">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
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
        let subtotalGs = 0;
        
        // Calcular subtotal en guaraníes (siempre)
        productosPresupuesto.forEach(producto => {
            subtotalGs += producto.price * producto.quantity;
        });
        
        // Convertir a dólares si es necesario
        let subtotal = subtotalGs;
        if (monedaActual === 'usd') {
            subtotal = subtotalGs / tipoCambio;
        }
        
        // Calcular IVA
        let iva = 0;
        if (aplicarIva === 'si') {
            iva = subtotal * 0.10;
        }
        
        // Calcular descuento
        let descuento = 0;
        if (tipoDescuento === 'porcentaje') {
            descuento = subtotal * (descuentoGeneral / 100);
        } else if (tipoDescuento === 'valor') {
            descuento = descuentoGeneral;
        }
        
        // Calcular total
        let total = subtotal + iva - descuento;
        
        // Actualizar display
        if (monedaActual === 'usd') {
            document.getElementById('subtotal-display').textContent = '$ ' + subtotal.toFixed(2);
            document.getElementById('total-display').textContent = '$ ' + total.toFixed(2);
            // Mostrar conversión a guaraníes
            document.getElementById('total-usd-display').textContent = 'Gs. ' + (total * tipoCambio).toLocaleString('es-PY');
        } else {
            document.getElementById('subtotal-display').textContent = 'Gs. ' + subtotal.toLocaleString('es-PY');
            document.getElementById('total-display').textContent = 'Gs. ' + total.toLocaleString('es-PY');
            // Mostrar conversión a dólares
            document.getElementById('total-usd-display').textContent = '$ ' + (total / tipoCambio).toFixed(2);
        }
        
        // Actualizar campos hidden del formulario
        // IMPORTANTE: Guardar el total en guaraníes en la base de datos
        let totalGs = total;
        if (monedaActual === 'usd') {
            totalGs = total * tipoCambio;
        }
        
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