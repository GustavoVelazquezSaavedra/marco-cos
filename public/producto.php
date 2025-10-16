<?php
include_once('../includes/database.php');
include_once('../includes/functions.php');

$database = new Database();
$db = $database->getConnection();

// Verificar que se proporcionó un ID de producto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$producto_id = sanitize($_GET['id']);

// Obtener información del producto
$queryProducto = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM productos p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  WHERE p.id = ? AND p.activo = 1";
$stmtProducto = $db->prepare($queryProducto);
$stmtProducto->execute([$producto_id]);
$producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);

// Si el producto no existe o no está activo, redirigir
if (!$producto) {
    header("Location: index.php");
    exit;
}

// Obtener productos relacionados (misma categoría)
$queryRelacionados = "SELECT p.*, c.nombre as categoria_nombre 
                      FROM productos p 
                      LEFT JOIN categorias c ON p.categoria_id = c.id 
                      WHERE p.categoria_id = ? AND p.id != ? AND p.activo = 1 
                      ORDER BY p.fecha_creacion DESC 
                      LIMIT 4";
$stmtRelacionados = $db->prepare($queryRelacionados);
$stmtRelacionados->execute([$producto['categoria_id'], $producto_id]);
$productos_relacionados = $stmtRelacionados->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el menú
$queryCategorias = "SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre";
$stmtCategorias = $db->prepare($queryCategorias);
$stmtCategorias->execute();
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $producto['nombre']; ?> - Marco Cos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .product-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 10px;
        }
        .product-gallery {
            margin-top: 10px;
        }
        .gallery-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.3s;
        }
        .gallery-thumb:hover,
        .gallery-thumb.active {
            border-color: #007bff;
        }
        .price {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
        .original-price {
            font-size: 1.2rem;
            text-decoration: line-through;
            color: #6c757d;
        }
        .stock-badge {
            font-size: 0.9rem;
        }
        .product-features {
            list-style: none;
            padding: 0;
        }
        .product-features li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .product-features li:last-child {
            border-bottom: none;
        }
        .feature-icon {
            color: #28a745;
            margin-right: 10px;
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Categorías
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categorias as $cat): ?>
                            <li>
                                <a class="dropdown-item" href="catalogo.php?categoria_id=<?php echo $cat['id']; ?>">
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

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-light py-3">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="catalogo.php">Catálogo</a></li>
                <li class="breadcrumb-item"><a href="catalogo.php?categoria_id=<?php echo $producto['categoria_id']; ?>">
                    <?php echo $producto['categoria_nombre']; ?>
                </a></li>
                <li class="breadcrumb-item active"><?php echo $producto['nombre']; ?></li>
            </ol>
        </div>
    </nav>

    <!-- Detalles del Producto -->
    <div class="container py-5">
        <div class="row">
            <!-- Galería de Imágenes -->
            <div class="col-md-6">
                <div class="product-gallery-main">
                    <?php if (!empty($producto['imagen'])): ?>
                    <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                         alt="<?php echo $producto['nombre']; ?>" 
                         class="product-image" 
                         id="main-product-image">
                    <?php else: ?>
                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                        <i class="fas fa-image fa-5x text-muted"></i>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Mini galería (si hay más imágenes) -->
                <div class="product-gallery">
                    <div class="row g-2">
                        <?php if (!empty($producto['imagen'])): ?>
                        <div class="col-3">
                            <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                                 alt="<?php echo $producto['nombre']; ?>" 
                                 class="gallery-thumb active"
                                 onclick="changeMainImage(this.src)">
                        </div>
                        <?php endif; ?>
                        <!-- Aquí puedes agregar más imágenes si tienes una galería -->
                    </div>
                </div>
            </div>

            <!-- Información del Producto -->
            <div class="col-md-6">
                <div class="product-details">
                    <!-- Categoría y código -->
                    <div class="mb-3">
                        <span class="badge bg-primary"><?php echo $producto['categoria_nombre']; ?></span>
                        <span class="text-muted ms-2">Código: <?php echo $producto['codigo']; ?></span>
                    </div>
                    
                    <!-- Nombre del producto -->
                    <h1 class="h2 mb-3"><?php echo $producto['nombre']; ?></h1>
                    
                    <!-- Precio -->
                    <div class="mb-4">
                        <div class="price">Gs. <?php echo number_format($producto['precio_publico'], 0, ',', '.'); ?></div>
                        <?php if ($producto['precio_real'] < $producto['precio_publico']): ?>
                        <div class="original-price">
                            Precio real: Gs. <?php echo number_format($producto['precio_real'], 0, ',', '.'); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Stock -->
                    <div class="mb-4">
                        <?php if ($producto['stock'] > 0): ?>
                        <span class="badge bg-success stock-badge">
                            <i class="fas fa-check me-1"></i>
                            <?php echo $producto['stock']; ?> disponibles en stock
                        </span>
                        <?php else: ?>
                        <span class="badge bg-danger stock-badge">
                            <i class="fas fa-times me-1"></i>
                            Agotado
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="mb-4">
                        <h5 class="mb-3">Descripción</h5>
                        <p class="text-muted"><?php echo nl2br($producto['descripcion']); ?></p>
                    </div>
                    
                    <!-- Características -->
                    <div class="mb-4">
                        <h5 class="mb-3">Características</h5>
                        <ul class="product-features">
                            <li>
                                <i class="fas fa-tag feature-icon"></i>
                                <strong>Categoría:</strong> <?php echo $producto['categoria_nombre']; ?>
                            </li>
                            <li>
                                <i class="fas fa-barcode feature-icon"></i>
                                <strong>Código:</strong> <?php echo $producto['codigo']; ?>
                            </li>
                            <li>
                                <i class="fas fa-box feature-icon"></i>
                                <strong>Stock:</strong> <?php echo $producto['stock']; ?> unidades
                            </li>
                            <li>
                                <i class="fas fa-calendar feature-icon"></i>
                                <strong>Agregado:</strong> <?php echo date('d/m/Y', strtotime($producto['fecha_creacion'])); ?>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="product-actions">
                        <?php if ($producto['stock'] > 0): ?>
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-auto">
                                <label for="quantity" class="form-label"><strong>Cantidad:</strong></label>
                            </div>
                            <div class="col-auto">
                                <input type="number" id="quantity" class="form-control" value="1" min="1" max="<?php echo $producto['stock']; ?>" style="width: 80px;">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex">
                            <button class="btn btn-primary btn-lg flex-fill add-to-cart-detailed" 
                                    data-product-id="<?php echo $producto['id']; ?>"
                                    data-product-name="<?php echo $producto['nombre']; ?>"
                                    data-product-price="<?php echo $producto['precio_publico']; ?>"
                                    data-product-image="<?php echo $producto['imagen']; ?>">
                                <i class="fas fa-cart-plus me-2"></i>Agregar al Carrito
                            </button>
                            <button class="btn btn-outline-success btn-lg flex-fill buy-now">
                                <i class="fas fa-bolt me-2"></i>Comprar Ahora
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Este producto está temporalmente agotado. Contáctanos para más información.
                        </div>
                        <a href="https://wa.me/595972366265?text=Hola, me interesa el producto <?php echo urlencode($producto['nombre']); ?> (<?php echo $producto['codigo']; ?>) que está agotado. ¿Cuándo tendrán stock?" 
                           target="_blank" class="btn btn-success w-100">
                            <i class="fab fa-whatsapp me-2"></i>Consultar por WhatsApp
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Envío y garantía -->
                    <div class="mt-4 pt-4 border-top">
                        <div class="row text-center">
                            <div class="col-4">
                                <i class="fas fa-shipping-fast text-primary fa-2x mb-2"></i>
                                <div class="small">Envío Gratis</div>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-shield-alt text-success fa-2x mb-2"></i>
                                <div class="small">Garantía</div>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-undo text-info fa-2x mb-2"></i>
                                <div class="small">Devoluciones</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos Relacionados -->
        <?php if (!empty($productos_relacionados)): ?>
        <div class="row mt-5 pt-5 border-top">
            <div class="col-12">
                <h3 class="mb-4">Productos Relacionados</h3>
                <div class="row">
                    <?php foreach ($productos_relacionados as $relacionado): ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <?php if (!empty($relacionado['imagen'])): ?>
                                <img src="../uploads/products/<?php echo $relacionado['imagen']; ?>" 
                                     class="card-img-top product-image" alt="<?php echo $relacionado['nombre']; ?>"
                                     style="height: 200px;">
                                <?php else: ?>
                                <div class="card-img-top product-image bg-light d-flex align-items-center justify-content-center"
                                     style="height: 200px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title"><?php echo $relacionado['nombre']; ?></h6>
                                <p class="card-text flex-grow-1">
                                    <small class="text-muted"><?php echo substr($relacionado['descripcion'], 0, 60); ?>...</small>
                                </p>
                                <div class="mt-auto">
                                    <div class="price mb-2" style="font-size: 1rem;">
                                        Gs. <?php echo number_format($relacionado['precio_publico'], 0, ',', '.'); ?>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="producto.php?id=<?php echo $relacionado['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Ver Detalles
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

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
        
        // Cambiar imagen principal en la galería
        function changeMainImage(src) {
            $('#main-product-image').attr('src', src);
            $('.gallery-thumb').removeClass('active');
            $(event.target).addClass('active');
        }
        
        // Agregar al carrito desde la página de detalles
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
        
        // Comprar ahora
        $('.buy-now').click(function() {
            const productId = $('.add-to-cart-detailed').data('product-id');
            const productName = $('.add-to-cart-detailed').data('product-name');
            const productPrice = $('.add-to-cart-detailed').data('product-price');
            const quantity = parseInt($('#quantity').val()) || 1;
            
            // Crear mensaje para WhatsApp
            const message = `¡Hola! Quiero comprar el siguiente producto:%0A%0A• ${productName}%0A• Cantidad: ${quantity}%0A• Precio unitario: Gs. ${productPrice.toLocaleString()}%0A• Total: Gs. ${(productPrice * quantity).toLocaleString()}%0A%0APor favor, contactame para coordinar la compra. ¡Gracias!`;
            
            // Abrir WhatsApp
            const phone = "595972366265";
            window.open(`https://wa.me/${phone}?text=${message}`, '_blank');
        });
        
        // Inicializar contador del carrito
        updateCartCount();
    </script>
</body>
</html>