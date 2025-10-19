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

// Variables para búsqueda
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Crear nueva categoría
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'create') {
    $nombre = sanitize($_POST['nombre']);
    $descripcion = sanitize($_POST['descripcion']);
    
    // Procesar imagen
    $imagen = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $uploadResult = uploadImage($_FILES['imagen'], 'categories');
        if ($uploadResult['success']) {
            $imagen = $uploadResult['filename'];
        } else {
            $error = $uploadResult['message'];
        }
    }
    
    // Verificar si la categoría ya existe
    $checkCategoria = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
    $checkCategoria->execute([$nombre]);
    
    if ($checkCategoria->rowCount() > 0) {
        $error = "La categoría ya existe";
    } else {
        $query = "INSERT INTO categorias (nombre, descripcion, imagen) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nombre, $descripcion, $imagen])) {
            $success = "Categoría creada exitosamente";
            $action = 'list'; // Volver a la lista
        } else {
            $error = "Error al crear la categoría";
        }
    }
}

// Editar categoría
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'edit') {
    $nombre = sanitize($_POST['nombre']);
    $descripcion = sanitize($_POST['descripcion']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Procesar nueva imagen si se subió
    $imagen = $_POST['imagen_actual']; // Mantener la imagen actual por defecto
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $uploadResult = uploadImage($_FILES['imagen'], 'categories');
        if ($uploadResult['success']) {
            $imagen = $uploadResult['filename'];
            // Opcional: eliminar la imagen anterior
        }
    }
    
    // Verificar si la categoría ya existe (excluyendo la categoría actual)
    $checkCategoria = $db->prepare("SELECT id FROM categorias WHERE nombre = ? AND id != ?");
    $checkCategoria->execute([$nombre, $id]);
    
    if ($checkCategoria->rowCount() > 0) {
        $error = "El nombre de categoría ya existe";
    } else {
        $query = "UPDATE categorias SET nombre = ?, descripcion = ?, imagen = ?, activo = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nombre, $descripcion, $imagen, $activo, $id])) {
            $success = "Categoría actualizada exitosamente";
            $action = 'list';
        } else {
            $error = "Error al actualizar la categoría";
        }
    }
}

// Eliminar categoría (solo desactivar)
if ($action == 'delete' && $id) {
    // Verificar si la categoría tiene productos
    $checkProductos = $db->prepare("SELECT COUNT(*) as total FROM productos WHERE categoria_id = ? AND activo = 1");
    $checkProductos->execute([$id]);
    $totalProductos = $checkProductos->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($totalProductos > 0) {
        $error = "No se puede eliminar la categoría porque tiene productos asociados";
    } else {
        $query = "UPDATE categorias SET activo = 0 WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $success = "Categoría desactivada exitosamente";
    }
    $action = 'list';
}

// Activar categoría
if ($action == 'activate' && $id) {
    $query = "UPDATE categorias SET activo = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $success = "Categoría activada exitosamente";
    $action = 'list';
}

// Obtener lista de categorías con filtros
if ($action == 'list') {
    $query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id AND p.activo = 1) as total_productos
              FROM categorias c 
              WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($search)) {
        $query .= " AND (c.nombre LIKE ? OR c.descripcion LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY c.nombre ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener datos de categoría para editar
if (($action == 'edit' || $action == 'view') && $id) {
    $query = "SELECT * FROM categorias WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$categoria) {
        $error = "Categoría no encontrada";
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - BLOOM Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .category-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .product-count {
            font-size: 1.5em;
            font-weight: bold;
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
                    <a href="categorias.php" class="list-group-item list-group-item-action active">
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
                    <!-- Lista de categorías -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Gestión de Categorías</h2>
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Categoría
                        </a>
                    </div>

                    <!-- Estadísticas rápidas -->
                    <?php
                    $totalCategorias = count($categorias);
                    $categoriasActivas = array_filter($categorias, function($cat) { return $cat['activo']; });
                    $totalProductos = array_sum(array_column($categorias, 'total_productos'));
                    ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card text-white bg-primary">
                                <div class="card-body text-center">
                                    <div class="product-count"><?php echo $totalCategorias; ?></div>
                                    <div>Total Categorías</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card text-white bg-success">
                                <div class="card-body text-center">
                                    <div class="product-count"><?php echo count($categoriasActivas); ?></div>
                                    <div>Categorías Activas</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card text-white bg-info">
                                <div class="card-body text-center">
                                    <div class="product-count"><?php echo $totalProductos; ?></div>
                                    <div>Total Productos</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card text-white bg-warning">
                                <div class="card-body text-center">
                                    <div class="product-count"><?php echo $totalCategorias - count($categoriasActivas); ?></div>
                                    <div>Categorías Inactivas</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Búsqueda -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="action" value="list">
                                
                                <div class="col-md-8">
                                    <label for="search" class="form-label">Buscar Categorías</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?php echo $search; ?>" placeholder="Nombre o descripción de la categoría...">
                                </div>
                                
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de categorías -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Imagen</th>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th>Productos</th>
                                            <th>Estado</th>
                                            <th>Fecha Creación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($categorias)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-tags fa-2x mb-2"></i><br>
                                                No se encontraron categorías
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($categorias as $cat): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($cat['imagen'])): ?>
                                                <img src="../uploads/categories/<?php echo $cat['imagen']; ?>" 
                                                     alt="<?php echo $cat['nombre']; ?>" class="category-image">
                                                <?php else: ?>
                                                <div class="category-image bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-folder text-muted fa-2x"></i>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo $cat['nombre']; ?></strong>
                                            </td>
                                            <td>
                                                <?php echo !empty($cat['descripcion']) ? $cat['descripcion'] : '<span class="text-muted">Sin descripción</span>'; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $cat['total_productos'] > 0 ? 'primary' : 'secondary'; ?>">
                                                    <?php echo $cat['total_productos']; ?> productos
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $cat['activo'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $cat['activo'] ? 'Activa' : 'Inactiva'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($cat['fecha_creacion'])); ?>
                                            </td>
                                            <td>
                                                <a href="?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($cat['activo'] && $cat['total_productos'] == 0): ?>
                                                <a href="?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('¿Estás seguro de desactivar esta categoría?')" title="Desactivar">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <?php elseif (!$cat['activo']): ?>
                                                <a href="?action=activate&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-success" 
                                                   onclick="return confirm('¿Estás seguro de activar esta categoría?')" title="Activar">
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
                    <!-- Formulario de crear/editar categoría -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><?php echo $action == 'create' ? 'Crear Nueva Categoría' : 'Editar Categoría'; ?></h2>
                        <a href="categorias.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista
                        </a>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre de la Categoría *</label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                                   value="<?php echo isset($categoria) ? $categoria['nombre'] : ''; ?>" required>
                                            <small class="text-muted">Nombre único para la categoría</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="descripcion" class="form-label">Descripción</label>
                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?php echo isset($categoria) ? $categoria['descripcion'] : ''; ?></textarea>
                                            <small class="text-muted">Describe los productos que pertenecen a esta categoría</small>
                                        </div>
                                        
                                        <?php if ($action == 'edit'): ?>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                                       <?php echo (isset($categoria) && $categoria['activo']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="activo">
                                                    Categoría activa
                                                </label>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="imagen" class="form-label">Imagen de la Categoría</label>
                                            <?php if (isset($categoria) && !empty($categoria['imagen'])): ?>
                                            <div class="mb-2">
                                                <img src="../uploads/categories/<?php echo $categoria['imagen']; ?>" 
                                                     alt="Imagen actual" class="img-thumbnail w-100">
                                                <input type="hidden" name="imagen_actual" value="<?php echo $categoria['imagen']; ?>">
                                                <small class="text-muted">Imagen actual</small>
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                                            <small class="text-muted">Formatos: JPG, PNG, GIF. Máx: 2MB</small>
                                        </div>
                                        
                                        <!-- Información de la categoría -->
                                        <?php if ($action == 'edit' && isset($categoria)): ?>
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Información de la Categoría</h6>
                                                <div class="mb-2">
                                                    <small><strong>ID:</strong> <?php echo $categoria['id']; ?></small>
                                                </div>
                                                <div class="mb-2">
                                                    <small><strong>Fecha Creación:</strong> <?php echo date('d/m/Y H:i', strtotime($categoria['fecha_creacion'])); ?></small>
                                                </div>
                                                <div class="mb-2">
                                                    <?php
                                                    $queryProductos = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = ? AND activo = 1";
                                                    $stmtProductos = $db->prepare($queryProductos);
                                                    $stmtProductos->execute([$categoria['id']]);
                                                    $totalProductos = $stmtProductos->fetch(PDO::FETCH_ASSOC)['total'];
                                                    ?>
                                                    <small><strong>Productos Activos:</strong> <?php echo $totalProductos; ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 
                                        <?php echo $action == 'create' ? 'Crear Categoría' : 'Actualizar Categoría'; ?>
                                    </button>
                                    <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
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
</body>
</html>