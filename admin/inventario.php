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
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$tipo_movimiento = isset($_GET['tipo_movimiento']) ? $_GET['tipo_movimiento'] : '';
$producto_id = isset($_GET['producto_id']) ? $_GET['producto_id'] : '';

// Registrar movimiento de entrada
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'entrada') {
    $producto_id = sanitize($_POST['producto_id']);
    $cantidad = sanitize($_POST['cantidad']);
    $motivo = sanitize($_POST['motivo']);
    
    // Procesar documento si se subió
    $documento = '';
    if (isset($_FILES['documento']) && $_FILES['documento']['error'] == 0) {
        $uploadResult = uploadDocument($_FILES['documento'], 'invoices');
        if ($uploadResult['success']) {
            $documento = $uploadResult['filename'];
        } else {
            $error = $uploadResult['message'];
        }
    }
    
    // Registrar movimiento - usando el nombre correcto de la columna fecha
    $query = "INSERT INTO inventario (producto_id, tipo, cantidad, motivo, usuario_id, documento, fecha_movimiento) VALUES (?, 'entrada', ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$producto_id, $cantidad, $motivo, $_SESSION['user_id'], $documento])) {
        // Actualizar stock del producto
        $updateStock = "UPDATE productos SET stock = stock + ? WHERE id = ?";
        $stmtUpdate = $db->prepare($updateStock);
        $stmtUpdate->execute([$cantidad, $producto_id]);
        
        $success = "Entrada de inventario registrada exitosamente";
        $action = 'list';
    } else {
        $error = "Error al registrar la entrada de inventario";
    }
}

// Registrar movimiento de salida
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'salida') {
    $producto_id = sanitize($_POST['producto_id']);
    $cantidad = sanitize($_POST['cantidad']);
    $motivo = sanitize($_POST['motivo']);
    
    // Verificar stock disponible
    $checkStock = $db->prepare("SELECT stock FROM productos WHERE id = ?");
    $checkStock->execute([$producto_id]);
    $stockActual = $checkStock->fetch(PDO::FETCH_ASSOC)['stock'];
    
    if ($stockActual < $cantidad) {
        $error = "Stock insuficiente. Stock actual: $stockActual unidades";
    } else {
        // Procesar documento si se subió
        $documento = '';
        if (isset($_FILES['documento']) && $_FILES['documento']['error'] == 0) {
            $uploadResult = uploadDocument($_FILES['documento'], 'tickets');
            if ($uploadResult['success']) {
                $documento = $uploadResult['filename'];
            } else {
                $error = $uploadResult['message'];
            }
        }
        
        // Registrar movimiento - usando el nombre correcto de la columna fecha
        $query = "INSERT INTO inventario (producto_id, tipo, cantidad, motivo, usuario_id, documento, fecha_movimiento) VALUES (?, 'salida', ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$producto_id, $cantidad, $motivo, $_SESSION['user_id'], $documento])) {
            // Actualizar stock del producto
            $updateStock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
            $stmtUpdate = $db->prepare($updateStock);
            $stmtUpdate->execute([$cantidad, $producto_id]);
            
            $success = "Salida de inventario registrada exitosamente";
            $action = 'list';
        } else {
            $error = "Error al registrar la salida de inventario";
        }
    }
}

// Obtener lista de movimientos con filtros
if ($action == 'list') {
    try {
        // Usar el nombre correcto de la columna fecha
        $query = "SELECT i.*, p.nombre as producto_nombre, p.codigo as producto_codigo, 
                         u.nombre as usuario_nombre, c.nombre as categoria_nombre
                  FROM inventario i 
                  LEFT JOIN productos p ON i.producto_id = p.id 
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  LEFT JOIN usuarios u ON i.usuario_id = u.id 
                  WHERE 1=1";
        
        $params = [];
        
        // Aplicar filtros usando la columna de fecha correcta
        if (!empty($fecha_desde)) {
            $query .= " AND DATE(i.fecha_movimiento) >= ?";
            $params[] = $fecha_desde;
        }
        
        if (!empty($fecha_hasta)) {
            $query .= " AND DATE(i.fecha_movimiento) <= ?";
            $params[] = $fecha_hasta;
        }
        
        if (!empty($tipo_movimiento)) {
            $query .= " AND i.tipo = ?";
            $params[] = $tipo_movimiento;
        }
        
        if (!empty($producto_id)) {
            $query .= " AND i.producto_id = ?";
            $params[] = $producto_id;
        }
        
        $query .= " ORDER BY i.fecha_movimiento DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Error al acceder a la base de datos: " . $e->getMessage();
        $movimientos = [];
    }
}

// Obtener productos para los select
$queryProductos = "SELECT p.id, p.nombre, p.codigo, p.stock, c.nombre as categoria_nombre 
                   FROM productos p 
                   LEFT JOIN categorias c ON p.categoria_id = c.id 
                   WHERE p.activo = 1 
                   ORDER BY p.nombre";
$stmtProductos = $db->prepare($queryProductos);
$stmtProductos->execute();
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

// Función para subir documentos (facturas/tickets)
function uploadDocument($file, $folder = "documents") {
    $target_dir = "../uploads/" . $folder . "/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $filename = uniqid() . "." . $fileType;
    $target_file = $target_dir . $filename;
    
    // Validar tamaño (max 5MB para documentos)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "El documento es muy grande."];
    }
    
    // Validar formato
    $allowedTypes = ["pdf", "jpg", "png", "jpeg"];
    if (!in_array($fileType, $allowedTypes)) {
        return ["success" => false, "message" => "Solo PDF, JPG, JPEG, PNG permitidos."];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "filename" => $filename];
    } else {
        return ["success" => false, "message" => "Error al subir el documento."];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Marco Cos Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .movimiento-entrada {
            border-left: 4px solid #28a745;
        }
        .movimiento-salida {
            border-left: 4px solid #dc3545;
        }
        .stock-bajo {
            background-color: #fff3cd !important;
        }
        .badge-entrada {
            background-color: #28a745;
        }
        .badge-salida {
            background-color: #dc3545;
        }
        .document-link {
            font-size: 0.8em;
        }
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
                    <a href="inventario.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-warehouse"></i> Inventario
                    </a>
                    <a href="pedidos.php" class="list-group-item list-group-item-action">
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
                    <!-- Lista de movimientos -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Gestión de Inventario</h2>
                        <div class="btn-group">
                            <a href="?action=entrada" class="btn btn-success">
                                <i class="fas fa-arrow-down"></i> Entrada Stock
                            </a>
                            <a href="?action=salida" class="btn btn-danger">
                                <i class="fas fa-arrow-up"></i> Salida Stock
                            </a>
                        </div>
                    </div>

                    <!-- Resumen de stock -->
                    <?php
                    $totalProductos = count($productos);
                    $productosStockBajo = array_filter($productos, function($prod) { return $prod['stock'] < 10; });
                    $productosSinStock = array_filter($productos, function($prod) { return $prod['stock'] == 0; });
                    ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body text-center">
                                    <div class="h4"><?php echo $totalProductos; ?></div>
                                    <div>Total Productos</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body text-center">
                                    <div class="h4"><?php echo count($productosStockBajo); ?></div>
                                    <div>Stock Bajo</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-danger">
                                <div class="card-body text-center">
                                    <div class="h4"><?php echo count($productosSinStock); ?></div>
                                    <div>Sin Stock</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info">
                                <div class="card-body text-center">
                                    <div class="h4"><?php echo count($movimientos); ?></div>
                                    <div>Movimientos</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <!-- En la sección de filtros, después de los botones Filtrar y Limpiar -->
<div class="col-12">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-filter"></i> Filtrar
    </button>
    <a href="inventario.php" class="btn btn-secondary">Limpiar</a>
    
    <!-- Botón para generar PDF -->
    <button type="button" id="generarPDF" class="btn btn-danger">
        <i class="fas fa-file-pdf"></i> Generar PDF
    </button>
</div>
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="action" value="list">
                                
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
                                
                                <div class="col-md-3">
                                    <label for="tipo_movimiento" class="form-label">Tipo Movimiento</label>
                                    <select class="form-control" id="tipo_movimiento" name="tipo_movimiento">
                                        <option value="">Todos</option>
                                        <option value="entrada" <?php echo $tipo_movimiento == 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                                        <option value="salida" <?php echo $tipo_movimiento == 'salida' ? 'selected' : ''; ?>>Salida</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="producto_id" class="form-label">Producto</label>
                                    <select class="form-control" id="producto_id" name="producto_id">
                                        <option value="">Todos los productos</option>
                                        <?php foreach ($productos as $prod): ?>
                                        <option value="<?php echo $prod['id']; ?>" 
                                                <?php echo $producto_id == $prod['id'] ? 'selected' : ''; ?>>
                                            <?php echo $prod['nombre']; ?> (<?php echo $prod['codigo']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Filtrar
                                    </button>
                                    <a href="inventario.php" class="btn btn-secondary">Limpiar</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de movimientos -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Producto</th>
                                            <th>Tipo</th>
                                            <th>Cantidad</th>
                                            <th>Motivo</th>
                                            <th>Usuario</th>
                                            <th>Documento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($movimientos)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-warehouse fa-2x mb-2"></i><br>
                                                No se encontraron movimientos
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($movimientos as $mov): ?>
                                        <tr class="<?php echo $mov['tipo'] == 'entrada' ? 'movimiento-entrada' : 'movimiento-salida'; ?>">
                                            <td>
                                                <?php echo date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])); ?>
                                            </td>
                                            <td>
                                                <strong><?php echo $mov['producto_nombre']; ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo $mov['producto_codigo']; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $mov['tipo'] == 'entrada' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ucfirst($mov['tipo']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-bold <?php echo $mov['tipo'] == 'entrada' ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $mov['tipo'] == 'entrada' ? '+' : '-'; ?><?php echo $mov['cantidad']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $mov['motivo']; ?>
                                            </td>
                                            <td>
                                                <small><?php echo $mov['usuario_nombre']; ?></small>
                                            </td>
                                            <td>
                                                <?php if (!empty($mov['documento'])): ?>
                                                <a href="../uploads/<?php echo $mov['tipo'] == 'entrada' ? 'invoices' : 'tickets'; ?>/<?php echo $mov['documento']; ?>" 
                                                   target="_blank" class="document-link">
                                                    <i class="fas fa-file-pdf"></i> Ver
                                                </a>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($action == 'entrada' || $action == 'salida'): ?>
                    <!-- Formulario de entrada/salida -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>
                            <i class="fas fa-<?php echo $action == 'entrada' ? 'arrow-down text-success' : 'arrow-up text-danger'; ?>"></i>
                            <?php echo $action == 'entrada' ? 'Registrar Entrada de Stock' : 'Registrar Salida de Stock'; ?>
                        </h2>
                        <a href="inventario.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al listado
                        </a>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="producto_id" class="form-label">Producto *</label>
                                            <select class="form-control" id="producto_id" name="producto_id" required>
                                                <option value="">Seleccionar producto</option>
                                                <?php foreach ($productos as $prod): ?>
                                                <option value="<?php echo $prod['id']; ?>" 
                                                        data-stock="<?php echo $prod['stock']; ?>">
                                                    <?php echo $prod['nombre']; ?> 
                                                    (<?php echo $prod['codigo']; ?>) 
                                                    - Stock: <?php echo $prod['stock']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cantidad" class="form-label">Cantidad *</label>
                                            <input type="number" class="form-control" id="cantidad" name="cantidad" 
                                                   min="1" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="motivo" class="form-label">Motivo *</label>
                                            <textarea class="form-control" id="motivo" name="motivo" rows="3" 
                                                      placeholder="<?php echo $action == 'entrada' ? 'Ej: Compra a proveedor, ajuste de inventario...' : 'Ej: Venta, muestra, daño...'; ?>" required></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="documento" class="form-label">
                                                <?php echo $action == 'entrada' ? 'Factura de Entrada' : 'Ticket de Salida'; ?>
                                            </label>
                                            <input type="file" class="form-control" id="documento" name="documento" 
                                                   accept=".pdf,.jpg,.jpeg,.png">
                                            <small class="text-muted">
                                                <?php echo $action == 'entrada' ? 
                                                    'Subir factura del proveedor (PDF, JPG, PNG)' : 
                                                    'Subir ticket de salida o comprobante (PDF, JPG, PNG)'; ?>
                                            </small>
                                        </div>
                                        
                                        <!-- Información del producto seleccionado -->
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Información del Producto</h6>
                                                <div id="producto-info" class="text-muted">
                                                    Selecciona un producto para ver la información
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Resumen del movimiento -->
                                        <div class="card mt-3">
                                            <div class="card-body">
                                                <h6 class="card-title">Resumen del Movimiento</h6>
                                                <div class="mb-2">
                                                    <small>Tipo: </small>
                                                    <span class="badge bg-<?php echo $action == 'entrada' ? 'success' : 'danger'; ?>">
                                                        <?php echo $action == 'entrada' ? 'ENTRADA' : 'SALIDA'; ?>
                                                    </span>
                                                </div>
                                                <div class="mb-2">
                                                    <small>Usuario: <?php echo $_SESSION['user_name']; ?></small>
                                                </div>
                                                <div class="mb-2">
                                                    <small>Fecha: <?php echo date('d/m/Y H:i'); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-<?php echo $action == 'entrada' ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-save"></i> 
                                        <?php echo $action == 'entrada' ? 'Registrar Entrada' : 'Registrar Salida'; ?>
                                    </button>
                                    <a href="inventario.php" class="btn btn-secondary">Cancelar</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Actualizar información del producto seleccionado
            $('#producto_id').change(function() {
                const selectedOption = $(this).find('option:selected');
                const productoId = selectedOption.val();
                const stockActual = selectedOption.data('stock');
                const productoNombre = selectedOption.text();
                
                if (productoId) {
                    let infoHtml = `
                        <div class="fw-bold">${productoNombre}</div>
                        <div>Stock actual: <span class="badge bg-${stockActual > 10 ? 'success' : (stockActual > 0 ? 'warning' : 'danger')}">${stockActual} unidades</span></div>
                    `;
                    
                    <?php if ($action == 'salida'): ?>
                    if (stockActual == 0) {
                        infoHtml += `<div class="text-danger mt-2"><i class="fas fa-exclamation-triangle"></i> Producto sin stock</div>`;
                    } else if (stockActual < 10) {
                        infoHtml += `<div class="text-warning mt-2"><i class="fas fa-exclamation-triangle"></i> Stock bajo</div>`;
                    }
                    <?php endif; ?>
                    
                    $('#producto-info').html(infoHtml);
                } else {
                    $('#producto-info').html('<span class="text-muted">Selecciona un producto para ver la información</span>');
                }
            });
            
            // Validar cantidad para salidas
            <?php if ($action == 'salida'): ?>
            $('#cantidad').on('input', function() {
                const selectedOption = $('#producto_id').find('option:selected');
                const stockActual = selectedOption.data('stock');
                const cantidad = $(this).val();
                
                if (cantidad > stockActual) {
                    $(this).addClass('is-invalid');
                    $('#cantidad-feedback').remove();
                    $(this).after('<div class="invalid-feedback" id="cantidad-feedback">Stock insuficiente. Máximo disponible: ' + stockActual + '</div>');
                } else {
                    $(this).removeClass('is-invalid');
                    $('#cantidad-feedback').remove();
                }
            });
            <?php endif; ?>
        });
        // Generar PDF con los filtros actuales
$('#generarPDF').click(function() {
    // Obtener los valores actuales de los filtros
    const fecha_desde = $('#fecha_desde').val();
    const fecha_hasta = $('#fecha_hasta').val();
    const tipo_movimiento = $('#tipo_movimiento').val();
    const producto_id = $('#producto_id').val();
    
    // Construir la URL con los parámetros
    let url = 'pdf/inventario_pdf.php?';
    const params = [];
    
    if (fecha_desde) params.push('fecha_desde=' + fecha_desde);
    if (fecha_hasta) params.push('fecha_hasta=' + fecha_hasta);
    if (tipo_movimiento) params.push('tipo_movimiento=' + tipo_movimiento);
    if (producto_id) params.push('producto_id=' + producto_id);
    
    url += params.join('&');
    
    // Abrir en nueva pestaña
    window.open(url, '_blank');
});
    </script>
</body>
</html>