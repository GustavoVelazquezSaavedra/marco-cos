<?php
include_once('../includes/database.php');
include_once('../includes/functions.php');

$database = new Database();
$db = $database->getConnection();

// Obtener categorías para el menú
$queryCategorias = "SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre";
$stmtCategorias = $db->prepare($queryCategorias);
$stmtCategorias->execute();
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

// Variables para filtros
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$categoria_id = isset($_GET['categoria_id']) ? $_GET['categoria_id'] : '';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'recientes';

// Obtener todos los productos activos con filtros
$queryProductos = "SELECT p.*, c.nombre as categoria_nombre 
                   FROM productos p 
                   LEFT JOIN categorias c ON p.categoria_id = c.id 
                   WHERE p.activo = 1";
$params = [];

// Aplicar filtros
if (!empty($search)) {
    $queryProductos .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.codigo LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($categoria_id)) {
    $queryProductos .= " AND p.categoria_id = ?";
    $params[] = $categoria_id;
}

// Ordenar
switch ($orden) {
    case 'precio_asc':
        $queryProductos .= " ORDER BY p.precio_publico ASC";
        break;
    case 'precio_desc':
        $queryProductos .= " ORDER BY p.precio_publico DESC";
        break;
    case 'nombre':
        $queryProductos .= " ORDER BY p.nombre ASC";
        break;
    default:
        $queryProductos .= " ORDER BY p.fecha_creacion DESC";
        break;
}

$stmtProductos = $db->prepare($queryProductos);
$stmtProductos->execute($params);
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

// Obtener nombre de categoría si hay filtro
$categoria_nombre = '';
if (!empty($categoria_id)) {
    foreach ($categorias as $cat) {
        if ($cat['id'] == $categoria_id) {
            $categoria_nombre = $cat['nombre'];
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo Completo - Marco Cos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .price {
            font-size: 1.25rem;
            font-weight: bold;
            color: #28a745;
        }
        .category-badge {
            position: absolute;
            top: 10px;
            left: 10px;
        }
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
        }
        .filter-sidebar {
            position: sticky;
            top: 20px;
        }
        .list-group-item.active {
            background-color: #007bff;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gem me-2"></i>Marco Cos
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="catalogo.php">Catálogo Completo</a>
                    </li>
                </ul>
                
                <!-- Buscador -->
                <form class="d-flex me-3" method="GET" action="catalogo.php">
                    <input class="form-control me-2" type="search" name="search" placeholder="Buscar productos..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <!-- Carrito -->
                <div class="navbar-nav">
                    <a class="nav-link position-relative" href="carrito.php">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                        <span class="cart-badge badge bg-danger rounded-pill" id="cart-count">0</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar de Filtros -->
            <div class="col-md-3">
                <div class="filter-sidebar">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtrar por Categoría</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <a href="catalogo.php" 
                                   class="list-group-item list-group-item-action <?php echo empty($categoria_id) ? 'active' : ''; ?>">
                                    <i class="fas fa-th-large me-2"></i>Todas las categorías
                                </a>
                                <?php foreach ($categorias as $cat): ?>
                                <a href="catalogo.php?categoria_id=<?php echo $cat['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $categoria_id == $cat['id'] ? 'active' : ''; ?>">
                                    <i class="fas fa-tag me-2"></i><?php echo $cat['nombre']; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-sort me-2"></i>Ordenar por</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php
                                $base_url = "catalogo.php?"; 
                                if (!empty($categoria_id)) $base_url .= "categoria_id=" . $categoria_id . "&";
                                if (!empty($search)) $base_url .= "search=" . urlencode($search) . "&";
                                ?>
                                <a href="<?php echo $base_url; ?>orden=recientes" 
                                   class="list-group-item list-group-item-action <?php echo $orden == 'recientes' ? 'active' : ''; ?>">
                                    <i class="fas fa-clock me-2"></i>Más recientes
                                </a>
                                <a href="<?php echo $base_url; ?>orden=nombre" 
                                   class="list-group-item list-group-item-action <?php echo $orden == 'nombre' ? 'active' : ''; ?>">
                                    <i class="fas fa-sort-alpha-down me-2"></i>Nombre A-Z
                                </a>
                                <a href="<?php echo $base_url; ?>orden=precio_asc" 
                                   class="list-group-item list-group-item-action <?php echo $orden == 'precio_asc' ? 'active' : ''; ?>">
                                    <i class="fas fa-sort-numeric-down me-2"></i>Precio: Menor a Mayor
                                </a>
                                <a href="<?php echo $base_url; ?>orden=precio_desc" 
                                   class="list-group-item list-group-item-action <?php echo $orden == 'precio_desc' ? 'active' : ''; ?>">
                                    <i class="fas fa-sort-numeric-down-alt me-2"></i>Precio: Mayor a Menor
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Información de contacto -->
                    <div class="card mt-4">
                        <div class="card-body text-center">
                            <h6><i class="fas fa-headset me-2"></i>¿Necesitas ayuda?</h6>
                            <p class="small mb-2">
                                <i class="fas fa-phone me-1"></i>+595 972 366-265
                            </p>
                            <a href="https://wa.me/595972366265" target="_blank" class="btn btn-success btn-sm w-100">
                                <i class="fab fa-whatsapp me-1"></i>WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Productos -->
            <div class="col-md-9">
                <!-- Header del catálogo -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">
                            <?php if (!empty($search)): ?>
                                <i class="fas fa-search me-2"></i>Resultados para "<?php echo htmlspecialchars($search); ?>"
                            <?php elseif (!empty($categoria_id)): ?>
                                <i class="fas fa-tag me-2"></i><?php echo $categoria_nombre; ?>
                            <?php else: ?>
                                <i class="fas fa-th-large me-2"></i>Catálogo Completo
                            <?php endif; ?>
                        </h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-box me-1"></i><?php echo count($productos); ?> producto(s) encontrado(s)
                        </p>
                    </div>
                    
                    <?php if (!empty($search) || !empty($categoria_id)): ?>
                    <a href="catalogo.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Limpiar filtros
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Productos -->
                <div class="row">
                    <?php if (empty($productos)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No se encontraron productos</h4>
                        <p class="text-muted mb-4">Intenta con otros términos de búsqueda o categorías</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="catalogo.php" class="btn btn-primary">
                                <i class="fas fa-th-large me-1"></i>Ver Todo el Catálogo
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-1"></i>Volver al Inicio
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <?php if (!empty($producto['imagen'])): ?>
                                <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                                     class="card-img-top product-image" alt="<?php echo $producto['nombre']; ?>">
                                <?php else: ?>
                                <div class="card-img-top product-image bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                                <?php endif; ?>
                                <span class="category-badge badge bg-primary"><?php echo $producto['categoria_nombre']; ?></span>
                                
                                <!-- Badge de stock -->
                                <?php if ($producto['stock'] == 0): ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2">Agotado</span>
                                <?php elseif ($producto['stock'] < 10): ?>
                                <span class="badge bg-warning position-absolute top-0 end-0 m-2">Últimas unidades</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $producto['nombre']; ?></h5>
                                <p class="card-text flex-grow-1">
                                    <small class="text-muted"><?php echo substr($producto['descripcion'], 0, 80); ?>...</small>
                                </p>
                                <div class="mt-auto">
                                    <div class="price mb-2">Gs. <?php echo number_format($producto['precio_publico'], 0, ',', '.'); ?></div>
                                    
                                    <?php if ($producto['stock'] > 0): ?>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary btn-sm add-to-cart" 
                                                data-product-id="<?php echo $producto['id']; ?>"
                                                data-product-name="<?php echo $producto['nombre']; ?>"
                                                data-product-price="<?php echo $producto['precio_publico']; ?>"
                                                data-product-image="<?php echo $producto['imagen']; ?>">
                                            <i class="fas fa-cart-plus me-1"></i>Agregar al Carrito
                                        </button>
                                        <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Ver Detalles
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <div class="d-grid">
                                        <a href="https://wa.me/595972366265?text=Hola, me interesa el producto <?php echo urlencode($producto['nombre']); ?> (<?php echo $producto['codigo']; ?>) que está agotado. ¿Cuándo tendrán stock?" 
                                           target="_blank" class="btn btn-outline-warning btn-sm">
                                            <i class="fab fa-whatsapp me-1"></i>Consultar Stock
                                        </a>
                                        <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-secondary btn-sm mt-1">
                                            <i class="fas fa-eye me-1"></i>Ver Detalles
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Mensaje cuando hay muchos productos -->
                <?php if (count($productos) > 12): ?>
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Mostrando <?php echo count($productos); ?> productos. Usa los filtros para encontrar lo que buscas.
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-gem me-2"></i>Marco Cos</h5>
                    <p>Joyería y accesorios de la más alta calidad para momentos especiales.</p>
                </div>
                <div class="col-md-4">
                    <h5>Contacto</h5>
                    <p>
                        <i class="fas fa-phone me-2"></i>+595 972 366-265<br>
                        <i class="fas fa-envelope me-2"></i>info@marccos.com
                    </p>
                </div>
                <div class="col-md-4">
                    <h5>Enlaces Rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Inicio</a></li>
                        <li><a href="catalogo.php" class="text-white">Catálogo</a></li>
                        <li><a href="carrito.php" class="text-white">Carrito</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
            <small>&copy; 2025 Marco Cos. Todos los derechos reservados. <a href="https://www.facebook.com/gustavogabriel.velazquez1">Desarrollador</a></small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Sistema de carrito
        let cart = JSON.parse(localStorage.getItem('marccos_cart')) || [];
        
        function updateCartCount() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            $('#cart-count').text(totalItems);
        }
        
        // Agregar producto al carrito
        // Reemplaza esta parte en todos los archivos donde esté el add-to-cart:

        $('.add-to-cart').click(function() {
            const productId = $(this).data('product-id');
            const productName = $(this).data('product-name');
            const productPrice = $(this).data('product-price');
            const productImage = $(this).data('product-image');
            
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    image: productImage,
                    quantity: 1
                });
            }
            
            localStorage.setItem('marccos_cart', JSON.stringify(cart));
            updateCartCount();
            
            // En lugar de alert, usa showToast (solo si existe la función)
            if (typeof showToast === 'function') {
                showToast(`¡${productName} agregado al carrito!`, 'success');
            }
        });
        
        // Inicializar contador del carrito
        updateCartCount();
    </script>
</body>
</html>