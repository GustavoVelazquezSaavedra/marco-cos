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
    <title>BLOOM - Joyería y Accesorios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e74c3c;
            --accent-color: #3498db;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --bg-light: #f8f9fa;
            --border-color: #e0e0e0;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: white;
            color: var(--text-dark);
        }
        
        /* Navbar estilo BLOOM */
        .navbar-bloom {
            background: white;
            border-bottom: 2px solid var(--border-color);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .navbar-brand-bloom {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color) !important;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .nav-link-bloom {
            color: var(--text-dark) !important;
            font-weight: 600;
            font-size: 0.95rem;
            margin: 0 15px;
            padding: 8px 0 !important;
            position: relative;
        }
        
        .nav-link-bloom:hover,
        .nav-link-bloom.active {
            color: var(--primary-color) !important;
        }
        
        .nav-link-bloom.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
        }
        
        .search-container {
            position: relative;
            margin-right: 20px;
        }
        
        .search-box-bloom {
            border: 2px solid var(--border-color);
            border-radius: 25px;
            padding: 10px 45px 10px 20px;
            font-size: 0.9rem;
            width: 300px;
            transition: all 0.3s;
        }
        
        .search-box-bloom:focus {
            border-color: var(--accent-color);
            box-shadow: none;
        }
        
        .search-icon-bloom {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            background: none;
            border: none;
        }
        
        .cart-icon-bloom {
            color: var(--primary-color);
            font-size: 1.4rem;
            position: relative;
        }
        
        .shop-icon-bloom {
            color: var(--primary-color);
            font-size: 1.4rem;
            margin-right: 20px;
        }
        
        /* Mobile Header Icons */
        .mobile-icons {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .mobile-search-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 1.2rem;
            padding: 5px;
        }
        
        /* Hero Section BLOOM */
        .hero-bloom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero-title-bloom {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .hero-subtitle-bloom {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .hero-btn-bloom {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 35px;
            border-radius: 8px;
            font-weight: 600;
            margin: 0 10px;
            font-size: 1rem;
        }
        
        /* Product Grid BLOOM */
        .products-section-bloom {
            padding: 50px 0;
            background: white;
        }
        
        .section-title-bloom {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
            text-align: left;
        }
        
        .section-subtitle-bloom {
            color: var(--text-light);
            margin-bottom: 30px;
            text-align: left;
            font-size: 1rem;
        }
        
        .product-card-bloom {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 25px;
            background: white;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card-bloom:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .product-image-bloom {
            height: 200px;
            object-fit: cover;
            width: 100%;
            border-bottom: 1px solid var(--border-color);
        }
        
        .product-info-bloom {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .product-title-bloom {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: var(--text-dark);
            min-height: 40px;
        }
        
        .product-description-bloom {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-bottom: 10px;
            line-height: 1.4;
            flex-grow: 1;
            min-height: 40px;
        }
        
        .product-meta-bloom {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 15px;
        }
        
        .product-price-bloom {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 15px;
        }
        
        .product-actions-bloom {
            margin-top: auto;
        }
        
        .btn-add-cart-bloom {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 15px;
            font-size: 0.9rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            margin-bottom: 10px;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-add-cart-bloom:hover {
            background: #1a252f;
        }
        
        .btn-details-bloom {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
            display: block;
            padding: 8px;
        }
        
        .btn-details-bloom:hover {
            color: var(--primary-color);
        }
        
        /* Cart Badge BLOOM */
        .cart-badge-bloom {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--secondary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        /* Categories Section BLOOM */
        .categories-section-bloom {
            background: var(--bg-light);
            padding: 50px 0;
            border-top: 1px solid var(--border-color);
        }
        
        .categories-header-bloom {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .categories-title-bloom {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        
        .categories-divider-bloom {
            width: 50px;
            height: 3px;
            background: var(--secondary-color);
            margin: 0 auto 20px;
        }
        
        .category-card-bloom {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 25px 20px;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .category-card-bloom:hover {
            border-color: var(--accent-color);
            transform: translateY(-5px);
        }
        
        .category-title-bloom {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .category-list-bloom {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .category-list-bloom li {
            padding: 5px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .category-list-bloom li:last-child {
            border-bottom: none;
        }
        
        .category-list-bloom a {
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .category-list-bloom a:hover {
            color: var(--primary-color);
        }
        
        /* Footer BLOOM */
        .footer-bloom {
            background: var(--primary-color);
            color: white;
            padding: 40px 0 20px;
            border-top: 3px solid var(--secondary-color);
        }
        
        .footer-title-bloom {
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        
        .footer-links-bloom a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            transition: color 0.3s;
            font-size: 0.9rem;
        }
        
        .footer-links-bloom a:hover {
            color: white;
        }
        
        .whatsapp-float {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #25D366;
            color: white;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .whatsapp-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.6);
        }
        
        /* Mobile Search Modal */
        .search-modal-bloom .modal-content {
            border: none;
            border-radius: 15px;
        }
        
        .search-modal-bloom .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 20px;
        }
        
        .search-modal-bloom .modal-body {
            padding: 20px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .navbar-brand-bloom {
                font-size: 1.6rem;
            }
            
            .search-box-bloom {
                width: 250px;
            }
            
            .hero-title-bloom {
                font-size: 2.2rem;
            }
            
            .nav-link-bloom {
                margin: 0 8px;
                font-size: 0.9rem;
            }
            
            .product-title-bloom,
            .product-description-bloom {
                min-height: auto;
            }
            
            .mobile-icons {
                gap: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .search-container {
                margin: 10px 0;
                width: 100%;
            }
            
            .search-box-bloom {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- WhatsApp Float -->
    <a href="https://wa.me/595972366265" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Navbar estilo BLOOM -->
    <nav class="navbar navbar-expand-lg navbar-bloom sticky-top">
        <div class="container">
            <a class="navbar-brand navbar-brand-bloom" href="index.php">
                BLOOM
            </a>
            
            <!-- Iconos para mobile (fuera del menú hamburguesa) -->
            <div class="mobile-icons d-lg-none">
                <!-- Botón de búsqueda -->
                <button class="mobile-search-btn" type="button" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="fas fa-search"></i>
                </button>
                
                <!-- Icono de tienda -->
                <a href="catalogo.php" class="text-decoration-none">
                    <i class="fas fa-store shop-icon-bloom"></i>
                </a>
                
                <!-- Carrito -->
                <a href="carrito.php" class="text-decoration-none position-relative">
                    <i class="fas fa-shopping-bag cart-icon-bloom"></i>
                    <span class="cart-badge-bloom" id="cart-count">0</span>
                </a>
                
                <!-- Botón hamburguesa -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <!-- Contenido del menú (solo navegación) -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link nav-link-bloom active" href="index.php">INICIO</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-bloom dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            CATEGORÍAS
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
                        <a class="nav-link nav-link-bloom" href="catalogo.php">CATÁLOGO</a>
                    </li>
                </ul>
                
                <!-- Elementos para desktop -->
                <div class="d-none d-lg-flex align-items-center">
                    <!-- Icono de tienda -->
                    <div class="navbar-nav me-3">
                        <a class="nav-link" href="catalogo.php">
                            <i class="fas fa-store shop-icon-bloom"></i>
                        </a>
                    </div>
                    
                    <!-- Buscador -->
                    <div class="search-container">
                        <form method="GET" action="index.php" class="position-relative">
                            <input class="form-control search-box-bloom" type="search" name="search" placeholder="Buscar productos..." 
                                   value="<?php echo $search; ?>">
                            <button class="search-icon-bloom" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Carrito -->
                    <div class="navbar-nav">
                        <a class="nav-link position-relative" href="carrito.php">
                            <i class="fas fa-shopping-bag cart-icon-bloom"></i>
                            <span class="cart-badge-bloom" id="cart-count-desktop">0</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Modal de búsqueda para mobile -->
    <div class="modal fade search-modal-bloom" id="searchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buscar productos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="index.php">
                        <div class="input-group">
                            <input type="search" name="search" class="form-control" placeholder="Buscar productos..." 
                                   value="<?php echo $search; ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hero Section BLOOM -->
    <section class="hero-bloom">
        <div class="container">
            <h1 class="hero-title-bloom">BIENVENIDO A BLOOM</h1>
            <p class="hero-subtitle-bloom">Descubre nuestra exclusiva colección de joyería y accesorios finos</p>
            <div>
                <a href="catalogo.php" class="btn hero-btn-bloom">
                    <i class="fas fa-gem me-2"></i>VER CATÁLOGO
                </a>
                <a href="catalogo.php?categoria_id=1" class="btn hero-btn-bloom" style="background: transparent; border: 2px solid white; color: white;">
                    <i class="fas fa-ring me-2"></i>ANILLOS
                </a>
            </div>
        </div>
    </section>

    <!-- Resto del código se mantiene igual -->
    <!-- Productos Destacados BLOOM -->
    <section class="products-section-bloom">
        <div class="container">
            <?php if (empty($search) && empty($categoria_id)): ?>
                <!-- Mostrar productos destacados cuando no hay búsqueda -->
                <h2 class="section-title-bloom">Nuestras Joyas Más Exclusivas</h2>
                <p class="section-subtitle-bloom">Descubre nuestra colección de joyas únicas y elegantes</p>
                
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
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="product-card-bloom">
                            <div class="position-relative">
                                <?php if (!empty($producto['imagen'])): ?>
                                <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                                     class="product-image-bloom" alt="<?php echo $producto['nombre']; ?>">
                                <?php else: ?>
                                <div class="product-image-bloom bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-gem fa-2x text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info-bloom">
                                <h5 class="product-title-bloom"><?php echo $producto['nombre']; ?></h5>
                                <p class="product-description-bloom">
                                    <?php echo substr($producto['descripcion'], 0, 80); ?>...
                                </p>
                                
                                <div class="product-meta-bloom">
                                    <i class="fas fa-tag me-1"></i><?php echo $producto['categoria_nombre']; ?>
                                </div>
                                
                                <div class="product-price-bloom">
                                    GS. <?php echo number_format($producto['precio_publico'], 0, ',', '.'); ?>
                                </div>
                                
                                <div class="product-actions-bloom">
                                    <button class="btn btn-add-cart-bloom add-to-cart" 
                                            data-product-id="<?php echo $producto['id']; ?>"
                                            data-product-name="<?php echo $producto['nombre']; ?>"
                                            data-product-price="<?php echo $producto['precio_publico']; ?>"
                                            data-product-image="<?php echo $producto['imagen']; ?>">
                                        <i class="fas fa-cart-plus me-2"></i>AGREGAR AL CARRITO
                                    </button>
                                    
                                    <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn-details-bloom">
                                        <i class="fas fa-eye me-1"></i>Ver detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <a href="catalogo.php" class="btn" style="border: 2px solid var(--primary-color); color: var(--primary-color); padding: 12px 35px; border-radius: 6px; font-weight: 600;">
                            VER TODOS LOS PRODUCTOS <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Mostrar resultados de búsqueda/filtros -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h2 class="section-title-bloom">
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
                        <p class="section-subtitle-bloom"><?php echo count($productos); ?> producto(s) encontrado(s)</p>
                    </div>
                </div>
                
                <div class="row">
                    <?php if (empty($productos)): ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-warning">
                            <i class="fas fa-search me-2"></i>
                            No se encontraron productos que coincidan con tu búsqueda.
                        </div>
                        <a href="index.php" class="btn btn-add-cart-bloom">VER TODOS LOS PRODUCTOS</a>
                    </div>
                    <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="product-card-bloom">
                            <div class="position-relative">
                                <?php if (!empty($producto['imagen'])): ?>
                                <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                                     class="product-image-bloom" alt="<?php echo $producto['nombre']; ?>">
                                <?php else: ?>
                                <div class="product-image-bloom bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-gem fa-2x text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info-bloom">
                                <h5 class="product-title-bloom"><?php echo $producto['nombre']; ?></h5>
                                <p class="product-description-bloom">
                                    <?php echo substr($producto['descripcion'], 0, 80); ?>...
                                </p>
                                
                                <div class="product-meta-bloom">
                                    <i class="fas fa-tag me-1"></i><?php echo $producto['categoria_nombre']; ?>
                                </div>
                                
                                <div class="product-price-bloom">
                                    GS. <?php echo number_format($producto['precio_publico'], 0, ',', '.'); ?>
                                </div>
                                
                                <div class="product-actions-bloom">
                                    <button class="btn btn-add-cart-bloom add-to-cart" 
                                            data-product-id="<?php echo $producto['id']; ?>"
                                            data-product-name="<?php echo $producto['nombre']; ?>"
                                            data-product-price="<?php echo $producto['precio_publico']; ?>"
                                            data-product-image="<?php echo $producto['imagen']; ?>">
                                        <i class="fas fa-cart-plus me-2"></i>AGREGAR AL CARRITO
                                    </button>
                                    
                                    <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn-details-bloom">
                                        <i class="fas fa-eye me-1"></i>Ver detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <a href="index.php" class="btn" style="border: 2px solid var(--primary-color); color: var(--primary-color); padding: 12px 35px; border-radius: 6px; font-weight: 600;">
                            <i class="fas fa-arrow-left me-2"></i>VOLVER AL INICIO
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Categorías BLOOM -->
    <section class="categories-section-bloom">
        <div class="container">
            <div class="categories-header-bloom">
                <h2 class="categories-title-bloom">Explorar por Categoría</h2>
                <div class="categories-divider-bloom"></div>
                <p class="text-muted">Encuentra lo que buscas en nuestras categorías</p>
            </div>
            
            <div class="row">
                <?php foreach ($categorias as $categoria): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="category-card-bloom">
                        <h4 class="category-title-bloom"><?php echo $categoria['nombre']; ?></h4>
                        <ul class="category-list-bloom">
                            <li><a href="?categoria_id=<?php echo $categoria['id']; ?>">Ver todos</a></li>
                            <li><a href="?categoria_id=<?php echo $categoria['id']; ?>&search=oro">Oro</a></li>
                            <li><a href="?categoria_id=<?php echo $categoria['id']; ?>&search=plata">Plata</a></li>
                            <li><a href="?categoria_id=<?php echo $categoria['id']; ?>&search=diamante">Diamante</a></li>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer BLOOM -->
    <footer class="footer-bloom">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title-bloom">BLOOM</h5>
                    <p style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Joyería y accesorios de la más alta calidad para momentos especiales.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title-bloom">CONTACTO</h5>
                    <div class="footer-links-bloom">
                        <p><i class="fas fa-phone me-2"></i>+595972366265</p>
                        <p><i class="fas fa-envelope me-2"></i>info@bloom.com</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title-bloom">ENLACES RÁPIDOS</h5>
                    <div class="footer-links-bloom">
                        <a href="index.php">Inicio</a>
                        <a href="catalogo.php">Catálogo</a>
                        <a href="carrito.php">Carrito</a>
                    </div>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center">
                <small style="color: rgba(255,255,255,0.7);">&copy; 2025 BLOOM. Todos los derechos reservados.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Sistema de carrito
        let cart = JSON.parse(localStorage.getItem('bloom_cart')) || [];
        
        // Actualizar contador del carrito
        function updateCartCount() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            $('#cart-count').text(totalItems);
            $('#cart-count-desktop').text(totalItems);
        }
        
        // Agregar producto al carrito
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
            
            localStorage.setItem('bloom_cart', JSON.stringify(cart));
            updateCartCount();
            
            // Mostrar notificación simple
            const toast = document.createElement('div');
            toast.className = 'position-fixed top-0 end-0 p-3';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>¡Éxito!</strong> ${productName} agregado al carrito.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.body.appendChild(toast);
            
            // Auto-remover después de 3 segundos
            setTimeout(() => {
                toast.remove();
            }, 3000);
        });
        
        // Inicializar contador del carrito
        updateCartCount();
    </script>
</body>
</html>