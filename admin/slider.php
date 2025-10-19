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

// Crear nuevo slide
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'create') {
    $producto_id = sanitize($_POST['producto_id']);
    $titulo = sanitize($_POST['titulo']);
    $subtitulo = sanitize($_POST['subtitulo']);
    $texto_boton = sanitize($_POST['texto_boton']);
    $orden = sanitize($_POST['orden']);
    
    // Procesar imagen
    $imagen = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $uploadResult = uploadImage($_FILES['imagen'], 'slider');
        if ($uploadResult['success']) {
            $imagen = $uploadResult['filename'];
        } else {
            $error = $uploadResult['message'];
        }
    }
    
    $query = "INSERT INTO slider_principal (producto_id, titulo, subtitulo, texto_boton, imagen, orden) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$producto_id, $titulo, $subtitulo, $texto_boton, $imagen, $orden])) {
        $success = "Slide creado exitosamente";
        $action = 'list';
    } else {
        $error = "Error al crear el slide";
    }
}

// Editar slide
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'edit') {
    $producto_id = sanitize($_POST['producto_id']);
    $titulo = sanitize($_POST['titulo']);
    $subtitulo = sanitize($_POST['subtitulo']);
    $texto_boton = sanitize($_POST['texto_boton']);
    $orden = sanitize($_POST['orden']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Procesar nueva imagen
    $imagen = $_POST['imagen_actual'];
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $uploadResult = uploadImage($_FILES['imagen'], 'slider');
        if ($uploadResult['success']) {
            $imagen = $uploadResult['filename'];
        }
    }
    
    $query = "UPDATE slider_principal SET producto_id = ?, titulo = ?, subtitulo = ?, texto_boton = ?, imagen = ?, orden = ?, activo = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$producto_id, $titulo, $subtitulo, $texto_boton, $imagen, $orden, $activo, $id])) {
        $success = "Slide actualizado exitosamente";
        $action = 'list';
    } else {
        $error = "Error al actualizar el slide";
    }
}

// Eliminar slide
if ($action == 'delete' && $id) {
    $query = "DELETE FROM slider_principal WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $success = "Slide eliminado exitosamente";
    $action = 'list';
}

// Activar/Desactivar slide
if ($action == 'toggle' && $id) {
    $query = "UPDATE slider_principal SET activo = NOT activo WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $success = "Estado del slide actualizado";
    $action = 'list';
}

// Obtener lista de slides
if ($action == 'list') {
    $query = "SELECT s.*, p.nombre as producto_nombre, p.imagen as producto_imagen 
              FROM slider_principal s 
              LEFT JOIN productos p ON s.producto_id = p.id 
              ORDER BY s.orden ASC, s.fecha_creacion DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener productos para select
$queryProductos = "SELECT id, nombre FROM productos WHERE activo = 1 ORDER BY nombre";
$stmtProductos = $db->prepare($queryProductos);
$stmtProductos->execute();
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos del slide para editar
if (($action == 'edit') && $id) {
    $query = "SELECT * FROM slider_principal WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $slide = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$slide) {
        $error = "Slide no encontrado";
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión del Slider - BLOOM Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .slide-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .slide-preview {
            max-width: 300px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        .badge-active {
            background-color: #28a745;
        }
        .badge-inactive {
            background-color: #6c757d;
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
                    <a href="slider.php" class="list-group-item list-group-item-action active">
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
                    <!-- Lista de slides -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Gestión del Slider Principal</h2>
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Slide
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Orden</th>
                                            <th>Imagen</th>
                                            <th>Título</th>
                                            <th>Producto</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($slides)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-images fa-2x mb-2"></i><br>
                                                No hay slides configurados
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($slides as $slide): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $slide['orden']; ?></strong>
                                            </td>
                                            <td>
                                                <?php if (!empty($slide['imagen'])): ?>
                                                <img src="../uploads/slider/<?php echo $slide['imagen']; ?>" 
                                                     class="slide-image" alt="<?php echo $slide['titulo']; ?>">
                                                <?php else: ?>
                                                <div class="slide-image bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo $slide['titulo']; ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo $slide['subtitulo']; ?></small>
                                            </td>
                                            <td>
                                                <?php if ($slide['producto_nombre']): ?>
                                                <span class="badge bg-info"><?php echo $slide['producto_nombre']; ?></span>
                                                <?php else: ?>
                                                <span class="text-muted">Sin producto</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $slide['activo'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                    <?php echo $slide['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?action=edit&id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=toggle&id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-<?php echo $slide['activo'] ? 'secondary' : 'success'; ?>">
                                                    <i class="fas fa-<?php echo $slide['activo'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('¿Estás seguro de eliminar este slide?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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
                    <!-- Formulario de crear/editar slide -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><?php echo $action == 'create' ? 'Crear Nuevo Slide' : 'Editar Slide'; ?></h2>
                        <a href="slider.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista
                        </a>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="titulo" class="form-label">Título Principal *</label>
                                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                                   value="<?php echo isset($slide) ? $slide['titulo'] : ''; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="subtitulo" class="form-label">Subtítulo</label>
                                            <textarea class="form-control" id="subtitulo" name="subtitulo" rows="2"><?php echo isset($slide) ? $slide['subtitulo'] : ''; ?></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="producto_id" class="form-label">Producto Relacionado</label>
                                                    <select class="form-control" id="producto_id" name="producto_id">
                                                        <option value="">Sin producto específico</option>
                                                        <?php foreach ($productos as $prod): ?>
                                                        <option value="<?php echo $prod['id']; ?>" 
                                                                <?php echo (isset($slide) && $slide['producto_id'] == $prod['id']) ? 'selected' : ''; ?>>
                                                            <?php echo $prod['nombre']; ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="text-muted">El slide redirigirá a este producto</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="texto_boton" class="form-label">Texto del Botón</label>
                                                    <input type="text" class="form-control" id="texto_boton" name="texto_boton" 
                                                           value="<?php echo isset($slide) ? $slide['texto_boton'] : 'Ver Producto'; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="orden" class="form-label">Orden de Visualización</label>
                                                    <input type="number" class="form-control" id="orden" name="orden" 
                                                           value="<?php echo isset($slide) ? $slide['orden'] : '0'; ?>" min="0">
                                                    <small class="text-muted">Menor número = aparece primero</small>
                                                </div>
                                            </div>
                                            <?php if ($action == 'edit'): ?>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check mt-4">
                                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                                               <?php echo (isset($slide) && $slide['activo']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="activo">
                                                            Slide activo
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="imagen" class="form-label">Imagen del Slide *</label>
                                            <?php if (isset($slide) && !empty($slide['imagen'])): ?>
                                            <div class="mb-2">
                                                <img src="../uploads/slider/<?php echo $slide['imagen']; ?>" 
                                                     alt="Imagen actual" class="slide-preview w-100">
                                                <input type="hidden" name="imagen_actual" value="<?php echo $slide['imagen']; ?>">
                                                <small class="text-muted">Imagen actual</small>
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*" <?php echo ($action == 'create') ? 'required' : ''; ?>>
                                            <small class="text-muted">Recomendado: 1200x600 px, formatos JPG, PNG</small>
                                        </div>
                                        
                                        <!-- Vista previa del producto seleccionado -->
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Vista Previa</h6>
                                                <div id="producto-preview">
                                                    <?php if (isset($slide) && $slide['producto_id']): ?>
                                                    <p><strong>Producto:</strong> <?php echo $slide['producto_nombre']; ?></p>
                                                    <?php if ($slide['producto_imagen']): ?>
                                                    <img src="../uploads/products/<?php echo $slide['producto_imagen']; ?>" 
                                                         class="img-thumbnail w-100" alt="Producto">
                                                    <?php endif; ?>
                                                    <?php else: ?>
                                                    <p class="text-muted">Selecciona un producto para ver la preview</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 
                                        <?php echo $action == 'create' ? 'Crear Slide' : 'Actualizar Slide'; ?>
                                    </button>
                                    <a href="slider.php" class="btn btn-secondary">Cancelar</a>
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
        // Actualizar preview cuando cambia el producto
        $('#producto_id').change(function() {
            const productoId = $(this).val();
            const preview = $('#producto-preview');
            
            if (productoId) {
                // Aquí podrías hacer una petición AJAX para obtener los datos del producto
                preview.html('<p class="text-muted">Cargando información del producto...</p>');
                
                // Simulamos la carga por ahora
                setTimeout(() => {
                    const productoNombre = $(this).find('option:selected').text();
                    preview.html(`
                        <p><strong>Producto:</strong> ${productoNombre}</p>
                        <p class="text-muted">La imagen del producto se cargará en el slider</p>
                    `);
                }, 500);
            } else {
                preview.html('<p class="text-muted">Selecciona un producto para ver la preview</p>');
            }
        });
    </script>
</body>
</html>