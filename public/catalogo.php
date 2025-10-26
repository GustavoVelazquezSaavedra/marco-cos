<?php
include_once('../includes/database.php');
include_once('../includes/functions.php');

$database = new Database();
$db = $database->getConnection();

// Obtener información de la empresa desde la base de datos
$titulo_sistema = "BLOOM"; // Valor por defecto
$subtitulo_sistema = "Perfumes"; // Valor por defecto
$telefono_empresa = "+595976588694"; // Valor por defecto
$email_empresa = "info@bloom.com"; // Valor por defecto

// Intentar obtener de la base de datos si hay conexión
try {
    $query_config = "SELECT clave, valor FROM configuraciones WHERE clave IN ('titulo_sistema', 'subtitulo_sistema', 'telefono', 'email')";
    $stmt_config = $db->prepare($query_config);
    $stmt_config->execute();
    $configs = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (isset($configs['titulo_sistema'])) {
        $titulo_sistema = $configs['titulo_sistema'];
    }
    if (isset($configs['subtitulo_sistema'])) {
        $subtitulo_sistema = $configs['subtitulo_sistema'];
    }
    if (isset($configs['telefono'])) {
        $telefono_empresa = $configs['telefono'];
    }
    if (isset($configs['email'])) {
        $email_empresa = $configs['email'];
    }
} catch (Exception $e) {
    // Si hay error, usar valores por defecto
}

// Obtener tipo de cambio actual
$queryTipoCambio = "SELECT * FROM tipo_cambio WHERE fecha = CURDATE() AND activo = 1 ORDER BY id DESC LIMIT 1";
$stmtTipoCambio = $db->prepare($queryTipoCambio);
$stmtTipoCambio->execute();
$tipo_cambio = $stmtTipoCambio->fetch(PDO::FETCH_ASSOC);

// Si no hay tipo de cambio para hoy, usar el más reciente
if (!$tipo_cambio) {
    $queryTipoCambio = "SELECT * FROM tipo_cambio WHERE activo = 1 ORDER BY fecha DESC LIMIT 1";
    $stmtTipoCambio = $db->prepare($queryTipoCambio);
    $stmtTipoCambio->execute();
    $tipo_cambio = $stmtTipoCambio->fetch(PDO::FETCH_ASSOC);
}

// Si aún no hay tipo de cambio, usar valores por defecto
if (!$tipo_cambio) {
    $tipo_cambio = ['compra' => 7000, 'venta' => 7100];
}

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
    <title>Catálogo Completo - <?php echo $titulo_sistema; ?></title>
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
        
        /* Navbar estilo */
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
        
        /* Cart Badge */
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
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
            text-align: center;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Filter Sidebar */
        .filter-sidebar {
            position: sticky;
            top: 20px;
        }
        
        .filter-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            background: white;
        }
        
        .filter-card .card-header {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .filter-list li {
            border-bottom: 1px solid var(--border-color);
        }
        
        .filter-list li:last-child {
            border-bottom: none;
        }
        
        .filter-list a {
            display: block;
            padding: 12px 20px;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .filter-list a:hover,
        .filter-list a.active {
            background: var(--bg-light);
            color: var(--primary-color);
        }
        
        .filter-list a.active {
            font-weight: 600;
            border-left: 3px solid var(--primary-color);
        }
        
        /* Product Cards */
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
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
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
            margin-bottom: 5px;
        }
        
        .product-price-usd {
            font-size: 0.9rem;
            color: var(--accent-color);
            font-weight: 600;
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
            padding: 10px 15px;
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
        
        .btn-outline-primary-bloom {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            border-radius: 6px;
            padding: 8px 15px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-outline-primary-bloom:hover {
            background: var(--primary-color);
            color: white;
        }
        
        /* Category Badge */
        .category-badge-bloom {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--accent-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Stock Badges */
        .stock-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        /* Contact Card */
        .contact-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: white;
        }
        
        .contact-card .card-body {
            padding: 20px;
            text-align: center;
        }
        
        /* Exchange Rate Info */
        .exchange-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .exchange-info small {
            opacity: 0.9;
        }
        
        /* Footer */
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
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .navbar-brand-bloom {
                font-size: 1.6rem;
            }
            
            .search-box-bloom {
                width: 250px;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .nav-link-bloom {
                margin: 0 8px;
                font-size: 0.9rem;
            }
            
            .product-title-bloom,
            .product-description-bloom {
                min-height: auto;
            }
            
            .filter-sidebar {
                position: static;
                margin-bottom: 30px;
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
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-bloom sticky-top">
        <div class="container">
            <a class="navbar-brand navbar-brand-bloom" href="index.php">
                <?php echo $titulo_sistema; ?>
            </a>
            
            <!-- Iconos para mobile -->
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
            
            <!-- Contenido del menú -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link nav-link-bloom" href="index.php">INICIO</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-bloom active" href="catalogo.php">CATÁLOGO</a>
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
                        <form method="GET" action="catalogo.php" class="position-relative">
                            <input class="form-control search-box-bloom" type="search" name="search" placeholder="Buscar productos..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
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
    <div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buscar productos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="catalogo.php">
                        <div class="input-group">
                            <input type="search" name="search" class="form-control" placeholder="Buscar productos..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><i class="fas fa-th-large me-2"></i>Catálogo Completo</h1>
            <p class="page-subtitle">Descubre toda nuestra colección de <?php echo strtolower($subtitulo_sistema); ?></p>
        </div>
    </section>

    <div class="container py-4">
        <div class="row">
            <!-- Sidebar de Filtros -->
            <div class="col-md-3">
                <div class="filter-sidebar">
                    <!-- Filtro por Categoría -->
                    <div class="filter-card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtrar por Categoría</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="filter-list">
                                <li>
                                    <a href="catalogo.php" class="<?php echo empty($categoria_id) ? 'active' : ''; ?>">
                                        <i class="fas fa-th-large me-2"></i>Todas las categorías
                                    </a>
                                </li>
                                <?php foreach ($categorias as $cat): ?>
                                <li>
                                    <a href="catalogo.php?categoria_id=<?php echo $cat['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="<?php echo $categoria_id == $cat['id'] ? 'active' : ''; ?>">
                                        <i class="fas fa-tag me-2"></i><?php echo $cat['nombre']; ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Ordenar -->
                    <div class="filter-card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-sort me-2"></i>Ordenar por</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="filter-list">
                                <?php
                                $base_url = "catalogo.php?"; 
                                if (!empty($categoria_id)) $base_url .= "categoria_id=" . $categoria_id . "&";
                                if (!empty($search)) $base_url .= "search=" . urlencode($search) . "&";
                                ?>
                                <li>
                                    <a href="<?php echo $base_url; ?>orden=recientes" 
                                       class="<?php echo $orden == 'recientes' ? 'active' : ''; ?>">
                                        <i class="fas fa-clock me-2"></i>Más recientes
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo $base_url; ?>orden=nombre" 
                                       class="<?php echo $orden == 'nombre' ? 'active' : ''; ?>">
                                        <i class="fas fa-sort-alpha-down me-2"></i>Nombre A-Z
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo $base_url; ?>orden=precio_asc" 
                                       class="<?php echo $orden == 'precio_asc' ? 'active' : ''; ?>">
                                        <i class="fas fa-sort-numeric-down me-2"></i>Precio: Menor a Mayor
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo $base_url; ?>orden=precio_desc" 
                                       class="<?php echo $orden == 'precio_desc' ? 'active' : ''; ?>">
                                        <i class="fas fa-sort-numeric-down-alt me-2"></i>Precio: Mayor a Menor
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Información de contacto -->
                    <div class="contact-card mt-4">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-headset me-2"></i>¿Necesitas ayuda?</h6>
                            <p class="small mb-3">
                                <i class="fas fa-phone me-1"></i><?php echo $telefono_empresa; ?>
                            </p>
                            <a href="https://wa.me/<?php echo str_replace('+', '', $telefono_empresa); ?>" target="_blank" class="btn btn-success btn-sm w-100">
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
                                <i class="fas fa-th-large me-2"></i>Todos los Productos
                            <?php endif; ?>
                        </h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-box me-1"></i><?php echo count($productos); ?> producto(s) encontrado(s)
                        </p>
                    </div>
                    
                    <?php if (!empty($search) || !empty($categoria_id)): ?>
                    <a href="catalogo.php" class="btn btn-outline-primary-bloom">
                        <i class="fas fa-times me-1"></i>Limpiar filtros
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Productos -->
                <div class="row">
                    <?php if (empty($productos)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted mb-3">No se encontraron productos</h4>
                        <p class="text-muted mb-4">Intenta con otros términos de búsqueda o categorías</p>
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="catalogo.php" class="btn btn-primary-bloom">
                                <i class="fas fa-th-large me-1"></i>Ver Todo el Catálogo
                            </a>
                            <a href="index.php" class="btn btn-outline-primary-bloom">
                                <i class="fas fa-home me-1"></i>Volver al Inicio
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($productos as $producto): 
                        // Calcular precio en dólares
                        $precio_usd = $producto['precio_publico'] / $tipo_cambio['venta'];
                    ?>
                    <div class="col-lg-4 col-md-6 mb-4">
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
                                
                                <span class="category-badge-bloom"><?php echo $producto['categoria_nombre']; ?></span>
                                
                                <!-- Badge de stock -->
                                <?php if ($producto['stock'] == 0): ?>
                                <span class="stock-badge bg-danger text-white">Agotado</span>
                                <?php elseif ($producto['stock'] < 10): ?>
                                <span class="stock-badge bg-warning text-dark">Últimas unidades</span>
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
                                <div class="product-price-usd">
                                    <i class="fas fa-dollar-sign me-1"></i>USD <?php echo number_format($precio_usd, 2, '.', ','); ?>
                                </div>
                                
                                <div class="product-actions-bloom">
                                    <?php if ($producto['stock'] > 0): ?>
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
                                    <?php else: ?>
                                    <a href="https://wa.me/<?php echo str_replace('+', '', $telefono_empresa); ?>?text=Hola, me interesa el producto <?php echo urlencode($producto['nombre']); ?> (<?php echo $producto['codigo']; ?>) que está agotado. ¿Cuándo tendrán stock?" 
                                       target="_blank" class="btn btn-outline-primary-bloom w-100 mb-2">
                                        <i class="fab fa-whatsapp me-1"></i>Consultar Stock
                                    </a>
                                    <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn-details-bloom">
                                        <i class="fas fa-eye me-1"></i>Ver detalles
                                    </a>
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
    <footer class="footer-bloom">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title-bloom"><?php echo $titulo_sistema; ?></h5>
                    <p style="color: rgba(255,255,255,0.8); font-size: 0.9rem;"><?php echo $subtitulo_sistema; ?> de la más alta calidad para momentos especiales.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="footer-title-bloom">CONTACTO</h5>
                    <div class="footer-links-bloom">
                        <p><i class="fas fa-phone me-2"></i><?php echo $telefono_empresa; ?></p>
                        <p><i class="fas fa-envelope me-2"></i><?php echo $email_empresa; ?></p>
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
                <small style="color: rgba(255,255,255,0.7);">&copy; 2025 <?php echo $titulo_sistema; ?>. Todos los derechos reservados, <a href="https://www.facebook.com/gustavogabriel.velazquez1/">Desarrollador</a>.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Sistema de carrito
        let cart = JSON.parse(localStorage.getItem('bloom_cart')) || [];
        
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