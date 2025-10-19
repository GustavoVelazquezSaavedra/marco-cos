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

// Variables para búsqueda y filtros
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$categoria_id = isset($_GET['categoria_id']) ? $_GET['categoria_id'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Crear nuevo producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'create') {
    $categoria_id = sanitize($_POST['categoria_id']);
    $nombre = sanitize($_POST['nombre']);
    $descripcion = sanitize($_POST['descripcion']);
    $codigo = sanitize($_POST['codigo']);
    $precio_publico = sanitize($_POST['precio_publico']);
    $precio_real = sanitize($_POST['precio_real']);
    $stock = sanitize($_POST['stock']);
    
    // Procesar imagen
    $imagen = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $uploadResult = uploadImage($_FILES['imagen'], 'products');
        if ($uploadResult['success']) {
            $imagen = $uploadResult['filename'];
        } else {
            $error = $uploadResult['message'];
        }
    }
    
    // Verificar si el código ya existe
    $checkCodigo = $db->prepare("SELECT id FROM productos WHERE codigo = ?");
    $checkCodigo->execute([$codigo]);
    
    if ($checkCodigo->rowCount() > 0) {
        $error = "El código del producto ya existe";
    } else {
        $query = "INSERT INTO productos (categoria_id, nombre, descripcion, codigo, precio_publico, precio_real, stock, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$categoria_id, $nombre, $descripcion, $codigo, $precio_publico, $precio_real, $stock, $imagen])) {
            $success = "Producto creado exitosamente";
            $action = 'list'; // Volver a la lista
        } else {
            $error = "Error al crear el producto";
        }
    }
}

// Editar producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'edit') {
    $categoria_id = sanitize($_POST['categoria_id']);
    $nombre = sanitize($_POST['nombre']);
    $descripcion = sanitize($_POST['descripcion']);
    $codigo = sanitize($_POST['codigo']);
    $precio_publico = sanitize($_POST['precio_publico']);
    $precio_real = sanitize($_POST['precio_real']);
    $stock = sanitize($_POST['stock']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Procesar nueva imagen si se subió
    $imagen = $_POST['imagen_actual']; // Mantener la imagen actual por defecto
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $uploadResult = uploadImage($_FILES['imagen'], 'products');
        if ($uploadResult['success']) {
            $imagen = $uploadResult['filename'];
            // Opcional: eliminar la imagen anterior
        }
    }
    
    // Verificar si el código ya existe (excluyendo el producto actual)
    $checkCodigo = $db->prepare("SELECT id FROM productos WHERE codigo = ? AND id != ?");
    $checkCodigo->execute([$codigo, $id]);
    
    if ($checkCodigo->rowCount() > 0) {
        $error = "El código del producto ya existe";
    } else {
        $query = "UPDATE productos SET categoria_id = ?, nombre = ?, descripcion = ?, codigo = ?, precio_publico = ?, precio_real = ?, stock = ?, imagen = ?, activo = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$categoria_id, $nombre, $descripcion, $codigo, $precio_publico, $precio_real, $stock, $imagen, $activo, $id])) {
            $success = "Producto actualizado exitosamente";
            $action = 'list';
        } else {
            $error = "Error al actualizar el producto";
        }
    }
}

// Eliminar producto (solo desactivar)
if ($action == 'delete' && $id) {
    $query = "UPDATE productos SET activo = 0 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $success = "Producto desactivado exitosamente";
    $action = 'list';
}

// Activar producto
if ($action == 'activate' && $id) {
    $query = "UPDATE productos SET activo = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $success = "Producto activado exitosamente";
    $action = 'list';
}

// Obtener lista de productos con filtros
if ($action == 'list') {
    $query = "SELECT p.*, c.nombre as categoria_nombre 
              FROM productos p 
              LEFT JOIN categorias c ON p.categoria_id = c.id 
              WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($search)) {
        $query .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.codigo LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($categoria_id)) {
        $query .= " AND p.categoria_id = ?";
        $params[] = $categoria_id;
    }
    
    if ($estado !== '') {
        $query .= " AND p.activo = ?";
        $params[] = $estado;
    }
    
    $query .= " ORDER BY p.fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener categorías para los filtros y formularios
$queryCategorias = "SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre";
$stmtCategorias = $db->prepare($queryCategorias);
$stmtCategorias->execute();
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos de producto para editar
if (($action == 'edit' || $action == 'view') && $id) {
    $query = "SELECT * FROM productos WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        $error = "Producto no encontrado";
        $action = 'list';
    }
}

// Generar código automático si se está creando
if ($action == 'create') {
    $ultimoCodigo = $db->query("SELECT codigo FROM productos ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $sugerenciaCodigo = 'PROD-' . (intval(substr($ultimoCodigo['codigo'] ?? 'PROD-000', 5)) + 1);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - BLOOM Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .table-actions {
            white-space: nowrap;
        }
        .price-original {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.9em;
        }
        .price-profit {
            color: #28a745;
            font-size: 0.8em;
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
                    <a href="productos.php" class="list-group-item list-group-item-action active">
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
                    <!-- Lista de productos -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Gestión de Productos</h2>
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Producto
                        </a>
                    </div>

                    <!-- Filtros y Búsqueda -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="action" value="list">
                                
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Buscar</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?php echo $search; ?>" placeholder="Nombre, descripción o código...">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="categoria_id" class="form-label">Categoría</label>
                                    <select class="form-control" id="categoria_id" name="categoria_id">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo $categoria_id == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['nombre']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="estado" class="form-label">Estado</label>
                                    <select class="form-control" id="estado" name="estado">
                                        <option value="">Todos</option>
                                        <option value="1" <?php echo $estado === '1' ? 'selected' : ''; ?>>Activos</option>
                                        <option value="0" <?php echo $estado === '0' ? 'selected' : ''; ?>>Inactivos</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de productos -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Imagen</th>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Categoría</th>
                                            <th>Precio Público</th>
                                            <th>Precio Real</th>
                                            <th>Stock</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($productos)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="fas fa-box-open fa-2x mb-2"></i><br>
                                                No se encontraron productos
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($productos as $prod): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($prod['imagen'])): ?>
                                                <img src="../uploads/products/<?php echo $prod['imagen']; ?>" 
                                                     alt="<?php echo $prod['nombre']; ?>" class="product-image">
                                                <?php else: ?>
                                                <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo $prod['codigo']; ?></strong>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo $prod['nombre']; ?></div>
                                                <small class="text-muted"><?php echo substr($prod['descripcion'], 0, 50); ?>...</small>
                                            </td>
                                            <td><?php echo $prod['categoria_nombre']; ?></td>
                                            <td>
                                                <strong class="text-success">Gs. <?php echo number_format($prod['precio_publico'], 0, ',', '.'); ?></strong>
                                            </td>
                                            <td>
                                                <span class="text-primary">Gs. <?php echo number_format($prod['precio_real'], 0, ',', '.'); ?></span>
                                                <?php 
                                                $ganancia = $prod['precio_publico'] - $prod['precio_real'];
                                                $margen = $prod['precio_real'] > 0 ? ($ganancia / $prod['precio_real']) * 100 : 0;
                                                ?>
                                                <br>
                                                <small class="price-profit">
                                                    +Gs. <?php echo number_format($ganancia, 0, ',', '.'); ?> 
                                                    (<?php echo number_format($margen, 1); ?>%)
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $prod['stock'] > 10 ? 'success' : ($prod['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                                    <?php echo $prod['stock']; ?> unidades
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $prod['activo'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $prod['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($prod['activo']): ?>
                                                <a href="?action=delete&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('¿Estás seguro de desactivar este producto?')" title="Desactivar">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <?php else: ?>
                                                <a href="?action=activate&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-success" 
                                                   onclick="return confirm('¿Estás seguro de activar este producto?')" title="Activar">
                                                    <i class="fas fa-check"></i>
                                                </a>
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

                <?php elseif ($action == 'create' || $action == 'edit'): ?>
                    <!-- Formulario de crear/editar producto -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><?php echo $action == 'create' ? 'Crear Nuevo Producto' : 'Editar Producto'; ?></h2>
                        <a href="productos.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista
                        </a>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="nombre" class="form-label">Nombre del Producto *</label>
                                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                                           value="<?php echo isset($producto) ? $producto['nombre'] : ''; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="codigo" class="form-label">Código *</label>
                                                    <input type="text" class="form-control" id="codigo" name="codigo" 
                                                           value="<?php echo isset($producto) ? $producto['codigo'] : $sugerenciaCodigo; ?>" required>
                                                    <small class="text-muted">Código único para identificar el producto</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="descripcion" class="form-label">Descripción</label>
                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo isset($producto) ? $producto['descripcion'] : ''; ?></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="categoria_id" class="form-label">Categoría *</label>
                                                    <select class="form-control" id="categoria_id" name="categoria_id" required>
                                                        <option value="">Seleccionar categoría</option>
                                                        <?php foreach ($categorias as $cat): ?>
                                                        <option value="<?php echo $cat['id']; ?>" 
                                                                <?php echo (isset($producto) && $producto['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                            <?php echo $cat['nombre']; ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="precio_publico" class="form-label">Precio Público *</label>
                                                    <input type="number" class="form-control" id="precio_publico" name="precio_publico" 
                                                           value="<?php echo isset($producto) ? $producto['precio_publico'] : ''; ?>" step="0.01" min="0" required>
                                                    <small class="text-muted">Precio que ven los clientes</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="precio_real" class="form-label">Precio Real *</label>
                                                    <input type="number" class="form-control" id="precio_real" name="precio_real" 
                                                           value="<?php echo isset($producto) ? $producto['precio_real'] : ''; ?>" step="0.01" min="0" required>
                                                    <small class="text-muted">Costo real del producto</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="stock" class="form-label">Stock Inicial</label>
                                                    <input type="number" class="form-control" id="stock" name="stock" 
                                                           value="<?php echo isset($producto) ? $producto['stock'] : '0'; ?>" min="0">
                                                </div>
                                            </div>
                                            <?php if ($action == 'edit'): ?>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check mt-4">
                                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                                               <?php echo (isset($producto) && $producto['activo']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="activo">
                                                            Producto activo
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="imagen" class="form-label">Imagen del Producto</label>
                                            <?php if (isset($producto) && !empty($producto['imagen'])): ?>
                                            <div class="mb-2">
                                                <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                                                     alt="Imagen actual" class="img-thumbnail w-100">
                                                <input type="hidden" name="imagen_actual" value="<?php echo $producto['imagen']; ?>">
                                                <small class="text-muted">Imagen actual</small>
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                                            <small class="text-muted">Formatos: JPG, PNG, GIF. Máx: 2MB</small>
                                        </div>
                                        
                                        <!-- Calculadora de margen -->
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Calculadora de Margen</h6>
                                                <div class="mb-2">
                                                    <small>Precio Público: <span id="display_publico">0</span></small>
                                                </div>
                                                <div class="mb-2">
                                                    <small>Precio Real: <span id="display_real">0</span></small>
                                                </div>
                                                <div class="mb-2">
                                                    <small>Ganancia: <span id="display_ganancia" class="text-success">0</span></small>
                                                </div>
                                                <div>
                                                    <small>Margen: <span id="display_margen" class="text-primary">0%</span></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 
                                        <?php echo $action == 'create' ? 'Crear Producto' : 'Actualizar Producto'; ?>
                                    </button>
                                    <a href="productos.php" class="btn btn-secondary">Cancelar</a>
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
        // Calculadora de margen en tiempo real
        $(document).ready(function() {
            function calcularMargen() {
                const precioPublico = parseFloat($('#precio_publico').val()) || 0;
                const precioReal = parseFloat($('#precio_real').val()) || 0;
                const ganancia = precioPublico - precioReal;
                const margen = precioReal > 0 ? (ganancia / precioReal) * 100 : 0;
                
                $('#display_publico').text('Gs. ' + precioPublico.toLocaleString());
                $('#display_real').text('Gs. ' + precioReal.toLocaleString());
                $('#display_ganancia').text('Gs. ' + ganancia.toLocaleString());
                $('#display_margen').text(margen.toFixed(1) + '%');
            }
            
            $('#precio_publico, #precio_real').on('input', calcularMargen);
            calcularMargen(); // Calcular al cargar la página
        });
    </script>
</body>
</html>