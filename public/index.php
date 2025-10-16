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

// Obtener productos destacados (los más recientes)
$queryProductos = "SELECT p.*, c.nombre as categoria_nombre 
                   FROM productos p 
                   LEFT JOIN categorias c ON p.categoria_id = c.id 
                   WHERE p.activo = 1 
                   ORDER BY p.fecha_creacion DESC 
                   LIMIT 8";
$stmtProductos = $db->prepare($queryProductos);
$stmtProductos->execute();
$productos_destacados = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

// Procesar búsqueda
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$categoria_id = isset($_GET['categoria_id']) ? $_GET['categoria_id'] : '';

// Obtener productos con filtros
$queryProductosFiltro = "SELECT p.*, c.nombre as categoria_nombre 
                         FROM productos p 
                         LEFT JOIN categorias c ON p.categoria_id = c.id 
                         WHERE p.activo = 1";
$params = [];

if (!empty($search)) {
    $queryProductosFiltro .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.codigo LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($categoria_id)) {
    $queryProductosFiltro .= " AND p.categoria_id = ?";
    $params[] = $categoria_id;
}

$queryProductosFiltro .= " ORDER BY p.fecha_creacion DESC";
$stmtProductosFiltro = $db->prepare($queryProductosFiltro);
$stmtProductosFiltro->execute($params);
$productos = $stmtProductosFiltro->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marco Cos - Joyería y Accesorios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            margin-bottom: 20px;
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
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
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
                        <a class="nav-link active" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Categorías
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categorias as $cat): ?>
                            <li>
                                <a class="dropdown-item" href="?categoria_id=<?php echo $cat['id']; ?>">
                                    <?php echo $cat['nombre']; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="catalogo.php">Catálogo Completo</a>
                    </li>
                </ul>
                
                <!-- Buscador -->
                <form class="d-flex me-3" method="GET" action="index.php">
                    <input class="form-control me-2" type="search" name="search" placeholder="Buscar productos..." 
                           value="<?php echo $search; ?>">
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Descubre la Elegancia en Cada Detalle</h1>
            <p class="lead mb-4">Joyería exclusiva y accesorios únicos para ocasiones especiales</p>
            <a href="catalogo.php" class="btn btn-light btn-lg">
                <i class="fas fa-gem me-2"></i>Ver Catálogo Completo
            </a>
        </div>
    </section>

    <!-- Productos Destacados -->
        <!-- Productos (Destacados o Resultados de Búsqueda) -->
        <section class="py-5">
        <div class="container">
            <?php if (empty($search) && empty($categoria_id)): ?>
                <!-- Mostrar productos destacados cuando no hay búsqueda -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h2 class="text-center mb-3">Productos Destacados</h2>
                        <p class="text-center text-muted">Nuestras joyas más exclusivas</p>
                    </div>
                </div>
                
                <div class="row">
                    <?php if (empty($productos_destacados)): ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Próximamente tendremos nuevos productos destacados
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($productos_destacados as $producto): ?>
                    <div class="col-md-3 col-sm-6">
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
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $producto['nombre']; ?></h5>
                                <p class="card-text flex-grow-1">
                                    <small class="text-muted"><?php echo substr($producto['descripcion'], 0, 80); ?>...</small>
                                </p>
                                <div class="mt-auto">
                                    <div class="price mb-2">Gs. <?php echo number_format($producto['precio_publico'], 0, ',', '.'); ?></div>
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
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <a href="catalogo.php" class="btn btn-outline-primary">
                            Ver Todos los Productos <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Mostrar resultados de búsqueda/filtros -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h2 class="mb-3">
                            <?php if (!empty($search)): ?>
                                Resultados de búsqueda: "<?php echo $search; ?>"
                            <?php elseif (!empty($categoria_id)): ?>
                                <?php 
                                $cat_nombre = '';
                                foreach ($categorias as $cat) {
                                    if ($cat['id'] == $categoria_id) {
                                        $cat_nombre = $cat['nombre'];
                                        break;
                                    }
                                }
                                ?>
                                Categoría: <?php echo $cat_nombre; ?>
                            <?php endif; ?>
                        </h2>
                        <p class="text-muted"><?php echo count($productos); ?> producto(s) encontrado(s)</p>
                    </div>
                </div>
                
                <div class="row">
                    <?php if (empty($productos)): ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-warning">
                            <i class="fas fa-search me-2"></i>
                            No se encontraron productos que coincidan con tu búsqueda.
                        </div>
                        <a href="index.php" class="btn btn-primary">Ver Todos los Productos</a>
                    </div>
                    <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                    <div class="col-md-3 col-sm-6">
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
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $producto['nombre']; ?></h5>
                                <p class="card-text flex-grow-1">
                                    <small class="text-muted"><?php echo substr($producto['descripcion'], 0, 80); ?>...</small>
                                </p>
                                <div class="mt-auto">
                                    <div class="price mb-2">Gs. <?php echo number_format($producto['precio_publico'], 0, ',', '.'); ?></div>
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
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Inicio
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Categorías -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-center mb-3">Explora por Categoría</h2>
                    <p class="text-center text-muted">Encuentra lo que buscas</p>
                </div>
            </div>
            
            <div class="row">
                <?php foreach ($categorias as $categoria): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-gem fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title"><?php echo $categoria['nombre']; ?></h5>
                            <p class="card-text text-muted"><?php echo substr($categoria['descripcion'], 0, 100); ?>...</p>
                            <a href="?categoria_id=<?php echo $categoria['id']; ?>" class="btn btn-outline-primary">
                                Ver Productos
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
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
                <small>&copy; 2024 Marco Cos. Todos los derechos reservados.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Sistema de carrito
        let cart = JSON.parse(localStorage.getItem('marccos_cart')) || [];
        
        // Actualizar contador del carrito
        function updateCartCount() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            $('#cart-count').text(totalItems);
        }
        
        // Agregar producto al carrito
        $('.add-to-cart').click(function() {
            const productId = $(this).data('product-id');
            const productName = $(this).data('product-name');
            const productPrice = $(this).data('product-price');
            const productImage = $(this).data('product-image');
            
            // Verificar si el producto ya está en el carrito
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
            
            // Guardar en localStorage
            localStorage.setItem('marccos_cart', JSON.stringify(cart));
            
            // Actualizar contador
            updateCartCount();
            
            // Mostrar notificación
            alert(`¡${productName} agregado al carrito!`);
        });
        
        // Inicializar contador del carrito
        updateCartCount();
    </script>
</body>
</html>