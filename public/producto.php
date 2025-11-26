<?php
include_once('../includes/database.php');
include_once('../includes/functions.php');

$database = new Database();
$db = $database->getConnection();

// Obtener información de la empresa desde la base de datos
$titulo_sistema = "BLOOM"; // Valor por defecto
$subtitulo_sistema = "Perfumes"; // Valor por defecto
$telefono_empresa = "+595972366265"; // Valor por defecto
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

// Verificar que se proporcionó un ID de producto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$producto_id = sanitize($_GET['id']);

// Obtener información del producto con múltiples categorías
$queryProducto = "SELECT p.*, 
                         GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres,
                         GROUP_CONCAT(c.id SEPARATOR ',') as categorias_ids
                  FROM productos p 
                  LEFT JOIN producto_categorias pc ON p.id = pc.producto_id 
                  LEFT JOIN categorias c ON pc.categoria_id = c.id 
                  WHERE p.id = ? AND p.activo = 1
                  GROUP BY p.id";
$stmtProducto = $db->prepare($queryProducto);
$stmtProducto->execute([$producto_id]);
$producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);

// Si el producto no existe o no está activo, redirigir
if (!$producto) {
    header("Location: index.php");
    exit;
}

// Obtener productos relacionados (misma categoría)
$queryRelacionados = "SELECT p.*, 
                             GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres
                      FROM productos p 
                      LEFT JOIN producto_categorias pc ON p.id = pc.producto_id 
                      LEFT JOIN categorias c ON pc.categoria_id = c.id 
                      WHERE p.id IN (
                          SELECT DISTINCT p2.id 
                          FROM productos p2 
                          LEFT JOIN producto_categorias pc2 ON p2.id = pc2.producto_id 
                          WHERE pc2.categoria_id IN (
                              SELECT categoria_id 
                              FROM producto_categorias 
                              WHERE producto_id = ?
                          ) AND p2.id != ? AND p2.activo = 1
                      )
                      GROUP BY p.id 
                      ORDER BY p.fecha_creacion DESC 
                      LIMIT 4";
$stmtRelacionados = $db->prepare($queryRelacionados);
$stmtRelacionados->execute([$producto_id, $producto_id]);
$productos_relacionados = $stmtRelacionados->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el menú
$queryCategorias = "SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre";
$stmtCategorias = $db->prepare($queryCategorias);
$stmtCategorias->execute();
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
// Obtener redes sociales activas
$queryRedes = "SELECT * FROM redes_sociales WHERE activo = 1 ORDER BY orden";
$stmtRedes = $db->prepare($queryRedes);
$stmtRedes->execute();
$redes_sociales = $stmtRedes->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $producto['nombre']; ?> - <?php echo $titulo_sistema; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary: #0a0a0a;
        --secondary: #c8c8c8;
        --accent: #e8e8e8;
        --light: #f8f9fa;
        --dark: #1a1a1a;
        --success: #afafaf;
        --text-light: #ffffff;
        --text-muted: #b0b0b0;
        --text-dark: #e8e6e3;
        --gold-light: #f0f0f0;
        --gold-dark: #d0d0d0;
    }
    
    body {
        font-family: 'Montserrat', sans-serif;
        background-color: var(--primary);
        color: var(--text-light);
        padding-top: 76px;
        overflow-x: hidden;
    }
    
    .title-font {
        font-family: 'Playfair Display', serif;
    }
    
    /* Navbar estilo */
    .navbar {
        background: rgba(10, 10, 10, 0.95) !important;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        border-bottom: 1px solid rgba(200, 200, 200, 0.2);
        z-index: 1030;
    }
    
    .navbar.scrolled {
        background: rgba(10, 10, 10, 0.98) !important;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    }
    
    .navbar-brand {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 1.8rem;
        color: var(--accent) !important;
    }
    
    .nav-link {
        color: var(--text-light) !important;
        font-weight: 500;
        position: relative;
        margin: 0 5px;
        padding: 8px 15px !important;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .nav-link:hover,
    .nav-link.active {
        background: rgba(232, 232, 232, 0.1);
        color: var(--accent) !important;
    }
    
    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 15px;
        right: 15px;
        height: 2px;
        background: var(--accent);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .nav-link:hover::after,
    .nav-link.active::after {
        transform: scaleX(1);
    }
    
    /* Dropdown styles */
    .dropdown-menu {
        background: var(--dark) !important;
        border: 1px solid rgba(200, 200, 200, 0.2) !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
    }
    
    .dropdown-item {
        color: var(--text-light) !important;
        padding: 10px 15px !important;
        transition: all 0.3s ease;
    }
    
    .dropdown-item:hover {
        background: rgba(232, 232, 232, 0.1) !important;
        color: var(--accent) !important;
    }
    
    /* ==================== */
    /* CORRECCIONES ESPECÍFICAS */
    /* ==================== */
    
    /* Corrección para el menú móvil */
    @media (max-width: 991px) {
        .navbar-collapse {
            background: rgba(10, 10, 10, 0.98);
            padding: 20px;
            border-radius: 0 0 12px 12px;
            margin-top: 10px;
            border: 1px solid rgba(200, 200, 200, 0.2);
        }
        
        .nav-link {
            margin: 5px 0;
            text-align: center;
            padding: 10px 15px !important;
        }
        
        .dropdown-menu {
            background: rgba(26, 26, 26, 0.95) !important;
            border: 1px solid rgba(200, 200, 200, 0.3) !important;
            margin-top: 5px;
            text-align: center;
        }
        
        .dropdown-item {
            padding: 8px 15px !important;
        }
        
        .navbar-toggler {
            border: 1px solid rgba(232, 232, 232, 0.3);
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(232, 232, 232, 0.25);
        }
    }
    
    /* Corrección de los íconos ovalados - REDES SOCIALES CIRCULARES PERFECTAS */
    .btn-outline-bloom.btn-sm.rounded-circle {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50% !important;
        padding: 0;
        border: 2px solid var(--accent) !important;
        transition: all 0.3s ease;
    }
    
    .btn-outline-bloom.btn-sm.rounded-circle i {
        font-size: 1rem;
    }
    
    .btn-outline-bloom.btn-sm.rounded-circle:hover {
        background: var(--accent);
        color: var(--primary);
        transform: scale(1.05);
    }
    
    /* Cart Badge */
    .cart-badge-bloom {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--accent);
        color: var(--primary);
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
        background: linear-gradient(135deg, rgba(10,10,10,0.9), rgba(26,26,26,0.8)), 
                    url('https://images.unsplash.com/photo-1547887537-6158d64c35b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
        background-size: cover;
        background-position: center;
        color: var(--text-light);
        padding: 80px 0;
        margin-bottom: 40px;
        text-align: center;
    }
    
    .page-title {
        font-family: 'Playfair Display', serif;
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--accent);
    }
    
    .page-subtitle {
        font-size: 1.2rem;
        opacity: 0.9;
        color: var(--text-light);
    }
    
    /* Card Style */
    .bloom-card {
        background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        overflow: hidden;
        border: 1px solid rgba(200, 200, 200, 0.1);
        position: relative;
    }
    
    .bloom-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent), var(--secondary));
        transform: scaleX(0);
        transition: transform 0.4s ease;
    }
    
    .bloom-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(232, 232, 232, 0.15);
        border-color: rgba(232, 232, 232, 0.3);
    }
    
    .bloom-card:hover::before {
        transform: scaleX(1);
    }
    
    /* Buttons */
    .btn-bloom {
        background: linear-gradient(135deg, var(--accent), var(--secondary));
        color: var(--primary);
        border: none;
        border-radius: 8px;
        font-weight: 600;
        padding: 12px 30px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(232, 232, 232, 0.3);
    }
    
    .btn-bloom:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(232, 232, 232, 0.4);
        color: var(--primary);
        background: linear-gradient(135deg, var(--secondary), var(--accent));
    }
    
    .btn-outline-bloom {
        background: transparent;
        color: var(--accent);
        border: 2px solid var(--accent);
        border-radius: 8px;
        font-weight: 600;
        padding: 12px 30px;
        transition: all 0.3s ease;
    }
    
    .btn-outline-bloom:hover {
        background: var(--accent);
        color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(232, 232, 232, 0.3);
    }
    
    .btn-whatsapp {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        padding: 12px 30px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
    }
    
    .btn-whatsapp:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
        color: white;
        background: linear-gradient(135deg, #128C7E, #25D366);
    }
    
    /* ==================== */
    /* ESTILOS DE IMÁGENES MEJORADOS */
    /* ==================== */
    
    /* Product Gallery - IMÁGENES UNIFORMES */
    .product-image-container {
        height: 500px; /* Altura fija para imagen principal */
        width: 100%;
        overflow: hidden;
        position: relative;
        background: var(--dark);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }
    
    .product-image-bloom {
        width: 100%;
        height: 100%;
        object-fit: contain; /* Muestra toda la imagen sin recortar */
        padding: 30px;
        transition: transform 0.3s ease;
    }
    
    .product-gallery {
        margin-top: 15px;
    }
    
    .gallery-thumb-container {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        overflow: hidden;
        background: var(--dark);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .gallery-thumb-container:hover,
    .gallery-thumb-container.active {
        border-color: var(--accent);
        transform: scale(1.05);
    }
    
    .gallery-thumb {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 8px;
    }
    
    /* Product Details */
    .product-price-bloom {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--accent);
        margin-bottom: 1rem;
    }
    
    .price-usd {
        font-size: 1.1rem;
        color: var(--text-muted);
        font-weight: 500;
    }
    
    .stock-badge {
        font-size: 0.9rem;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .product-features {
        list-style: none;
        padding: 0;
    }
    
    .product-features li {
        padding: 10px 0;
        border-bottom: 1px solid rgba(200, 200, 200, 0.1);
        color: var(--text-light);
    }
    
    .product-features li:last-child {
        border-bottom: none;
    }
    
    .feature-icon {
        color: var(--accent);
        margin-right: 10px;
        width: 20px;
    }
    
    /* Categorías badges */
    .categorias-badge {
        margin: 2px;
        font-size: 0.7rem;
        padding: 4px 8px;
        border-radius: 12px;
        background: rgba(232, 232, 232, 0.15);
        color: var(--accent);
        border: 1px solid rgba(232, 232, 232, 0.3);
    }
    
    .breadcrumb-category {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
        vertical-align: middle;
    }
    
    /* Breadcrumb */
    .breadcrumb-bloom {
        background: rgba(26, 26, 26, 0.8);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(200, 200, 200, 0.1);
    }
    
    .breadcrumb-bloom .breadcrumb-item a {
        color: var(--accent);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-bloom .breadcrumb-item a:hover {
        color: var(--text-light);
    }
    
    .breadcrumb-bloom .breadcrumb-item.active {
        color: var(--text-muted);
    }
    
    /* Quantity Input */
    .quantity-input {
        width: 80px;
        text-align: center;
        border: 1px solid rgba(200, 200, 200, 0.3);
        border-radius: 8px;
        padding: 10px;
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-light);
        font-weight: 600;
    }
    
    .quantity-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 0.25rem rgba(232, 232, 232, 0.25);
        background: rgba(255, 255, 255, 0.08);
        color: var(--text-light);
    }
    
    /* Related Products - IMÁGENES UNIFORMES */
    .related-product-card {
        border: 1px solid rgba(200, 200, 200, 0.1);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 25px;
        background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .related-product-card:hover {
        box-shadow: 0 15px 35px rgba(232, 232, 232, 0.15);
        transform: translateY(-5px);
        border-color: rgba(232, 232, 232, 0.3);
    }
    
    .related-product-image-container {
        height: 200px;
        width: 100%;
        overflow: hidden;
        position: relative;
        background: var(--dark);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .related-product-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 15px;
        transition: transform 0.3s ease;
    }
    
    .related-product-card:hover .related-product-image {
        transform: scale(1.05);
    }
    
    /* WhatsApp Float */
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
    
    /* Toast Notifications */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
    }
    
    .toast-success {
        background: linear-gradient(135deg, var(--accent), var(--secondary));
        color: var(--primary);
        border: none;
        border-radius: 8px;
    }
    
    /* Footer */
    .footer-bloom {
        background: var(--dark);
        color: var(--text-light);
        padding: 60px 0 20px;
        border-top: 3px solid var(--accent);
    }
    
    .footer-title-bloom {
        font-weight: 700;
        margin-bottom: 20px;
        font-size: 1.2rem;
        color: var(--accent);
    }
    
    .footer-links-bloom a {
        color: var(--text-muted);
        text-decoration: none;
        display: block;
        margin-bottom: 8px;
        transition: color 0.3s;
        font-size: 0.9rem;
    }
    
    .footer-links-bloom a:hover {
        color: var(--accent);
    }
    
    /* Text Colors Fix */
    .text-muted {
        color: var(--text-muted) !important;
    }
    
    .text-light {
        color: var(--text-light) !important;
    }
    
    .text-dark {
        color: var(--text-dark) !important;
    }
    
    /* Form Controls */
    .form-control {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(200, 200, 200, 0.3);
        border-radius: 8px;
        color: var(--text-light);
        padding: 10px 15px;
    }
    
    .form-control:focus {
        background: rgba(255, 255, 255, 0.08);
        border-color: var(--accent);
        box-shadow: 0 0 0 0.25rem rgba(232, 232, 232, 0.25);
        color: var(--text-light);
    }
    
    .form-label {
        color: var(--text-light);
        font-weight: 500;
    }
    
    /* Mobile Styles */
    .mobile-icons {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    /* ==================== */
    /* RESPONSIVE MEJORADO */
    /* ==================== */
    
    @media (max-width: 768px) {
        .navbar-brand {
            font-size: 1.5rem;
        }
        
        .page-title {
            font-size: 2.2rem;
        }
        
        .product-price-bloom {
            font-size: 2rem;
        }
        
        .gallery-thumb-container {
            width: 60px;
            height: 60px;
        }
        
        /* IMÁGENES EN MOBILE */
        .product-image-container {
            height: 400px !important;
        }
        
        .product-image-bloom {
            padding: 20px;
        }
        
        .related-product-image-container {
            height: 180px !important;
        }
        
        .related-product-image {
            padding: 10px;
        }
        
        .btn-bloom, .btn-outline-bloom, .btn-whatsapp {
            padding: 10px 20px;
            font-size: 0.9rem;
        }
        
        /* Corrección adicional para menú móvil */
        .mobile-icons {
            gap: 12px;
        }
    }
    
    @media (max-width: 576px) {
        .page-title {
            font-size: 1.8rem;
        }
        
        .product-price-bloom {
            font-size: 1.8rem;
        }
        
        /* IMÁGENES EN MÓVILES PEQUEÑOS */
        .product-image-container {
            height: 350px !important;
        }
        
        .product-image-bloom {
            padding: 15px;
        }
        
        .related-product-image-container {
            height: 160px !important;
        }
        
        .gallery-thumb-container {
            width: 50px;
            height: 50px;
        }
        
        /* Corrección para menú móvil en pantallas pequeñas */
        .navbar-collapse {
            padding: 15px;
        }
        
        .nav-link {
            padding: 10px 15px !important;
            font-size: 0.9rem;
        }
        
        .dropdown-item {
            padding: 8px 15px !important;
            font-size: 0.85rem;
        }
    }

    /* Mejoras para tablets */
    @media (min-width: 768px) and (max-width: 991px) {
        .product-image-container {
            height: 450px !important;
        }
        
        .related-product-image-container {
            height: 200px !important;
        }
    }

    /* Ajustes para pantallas muy grandes */
    @media (min-width: 1400px) {
        .container {
            max-width: 1320px;
        }
        
        .product-image-container {
            height: 550px !important;
        }
    }
</style>
</head>
<body>
    <!-- WhatsApp Float -->
    <a href="https://wa.me/<?php echo str_replace('+', '', $telefono_empresa); ?>" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-spa me-2"></i><?php echo $titulo_sistema; ?>
            </a>
            
            <!-- Iconos para mobile -->
            <div class="mobile-icons d-lg-none">
                <a href="catalogo.php" class="text-decoration-none">
                    <i class="fas fa-store" style="color: var(--accent); font-size: 1.2rem;"></i>
                </a>
                
                <a href="carrito.php" class="text-decoration-none position-relative ms-3">
                    <i class="fas fa-shopping-bag" style="color: var(--accent); font-size: 1.2rem;"></i>
                    <span class="cart-badge-bloom" id="cart-count">0</span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <!-- Contenido del menú -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">INICIO</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            CATEGORÍAS
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
                        <a class="nav-link" href="catalogo.php">CATÁLOGO</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="carrito.php">CARRITO</a>
                    </li>
                </ul>
                
                <!-- Elementos para desktop -->
                <div class="d-none d-lg-flex align-items-center">
                    <div class="navbar-nav me-3">
                        <a class="nav-link" href="catalogo.php">
                            <i class="fas fa-store" style="color: var(--accent); font-size: 1.2rem;"></i>
                        </a>
                    </div>
                    
                    <div class="navbar-nav">
                        <a class="nav-link position-relative" href="carrito.php">
                            <i class="fas fa-shopping-bag" style="color: var(--accent); font-size: 1.2rem;"></i>
                            <span class="cart-badge-bloom" id="cart-count-desktop">0</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Container para notificaciones Toast -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Breadcrumb -->
    <nav class="breadcrumb-bloom py-3">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="catalogo.php">Catálogo</a></li>
                <?php if (!empty($producto['categorias_nombres'])): ?>
                <li class="breadcrumb-item">
                    <span class="breadcrumb-category" title="<?php echo $producto['categorias_nombres']; ?>">
                        <?php 
                        $categorias_array = explode(', ', $producto['categorias_nombres']);
                        echo count($categorias_array) > 1 ? 'Múltiples categorías' : $categorias_array[0];
                        ?>
                    </span>
                </li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?php echo $producto['nombre']; ?></li>
            </ol>
        </div>
    </nav>

    <!-- Detalles del Producto -->
    <div class="container py-5">
        <div class="row">
            <!-- Galería de Imágenes -->
            <div class="col-md-6 mb-5">
                <div class="bloom-card p-4">
                    <div class="product-image-container">
                        <?php if (!empty($producto['imagen'])): ?>
                        <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                             class="product-image-bloom" 
                             alt="<?php echo $producto['nombre']; ?>"
                             id="main-product-image">
                        <?php else: ?>
                        <i class="fas fa-spa fa-5x text-accent"></i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mini galería -->
                    <div class="product-gallery">
                        <div class="row g-2 justify-content-center">
                            <?php if (!empty($producto['imagen'])): ?>
                            <div class="col-auto">
                                <div class="gallery-thumb-container active" onclick="changeMainImage('../uploads/products/<?php echo $producto['imagen']; ?>')">
                                    <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                                         class="gallery-thumb" 
                                         alt="<?php echo $producto['nombre']; ?>">
                                </div>
                            </div>
                            <?php endif; ?>
                            <!-- Aquí puedes agregar más imágenes si tienes una galería -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Producto -->
            <div class="col-md-6">
                <div class="bloom-card p-4 h-100">
                    <div class="product-details">
                        <!-- Categorías -->
                        <div class="mb-4">
                            <?php if (!empty($producto['categorias_nombres'])): ?>
                                <?php 
                                $categorias_array = explode(', ', $producto['categorias_nombres']);
                                foreach ($categorias_array as $categoria): ?>
                                    <span class="badge categorias-badge"><?php echo $categoria; ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary categorias-badge">Sin categorías</span>
                            <?php endif; ?>
                            <span class="text-muted ms-2">Código: <?php echo $producto['codigo']; ?></span>
                        </div>
                        
                        <!-- Nombre del producto -->
                        <h1 class="h2 title-font mb-4 text-light"><?php echo $producto['nombre']; ?></h1>
                        
                        <!-- Precio -->
                        <div class="mb-4">
                            <div class="product-price-bloom">
                                <?php 
                                $precios = formatPrecioDual($producto['precio_publico']);
                                ?>
                                <div class="fw-bold"><?php echo $precios['gs']; ?></div>
                                <div class="price-usd"><?php echo $precios['usd']; ?></div>
                            </div>
                        </div>
                        
                        <!-- Stock -->
                        <div class="mb-4">
                            <?php if ($producto['stock'] > 0): ?>
                            <span class="badge stock-badge" style="background: linear-gradient(135deg, var(--success), #8a8a8a); color: var(--primary);">
                                <i class="fas fa-check me-1"></i>
                                <?php echo $producto['stock']; ?> disponibles en stock
                            </span>
                            <?php else: ?>
                            <span class="badge stock-badge bg-danger text-white">
                                <i class="fas fa-times me-1"></i>
                                Agotado
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="mb-4">
                            <h5 class="mb-3 text-accent">Descripción</h5>
                            <p class="text-muted"><?php echo nl2br($producto['descripcion']); ?></p>
                        </div>
                        
                        <!-- Características -->
                        <div class="mb-4">
                            <h5 class="mb-3 text-accent">Características</h5>
                            <ul class="product-features">
                                <li>
                                    <i class="fas fa-tags feature-icon"></i>
                                    <strong>Categorías:</strong> 
                                    <?php if (!empty($producto['categorias_nombres'])): ?>
                                        <?php echo $producto['categorias_nombres']; ?>
                                    <?php else: ?>
                                        Sin categorías
                                    <?php endif; ?>
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
                            <div class="row g-3 align-items-center mb-4">
                                <div class="col-auto">
                                    <label for="quantity" class="form-label"><strong class="text-light">Cantidad:</strong></label>
                                </div>
                                <div class="col-auto">
                                    <input type="number" id="quantity" class="form-control quantity-input" 
                                           value="1" min="1" max="<?php echo $producto['stock']; ?>">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-3 d-md-flex">
                                <button class="btn btn-bloom btn-lg flex-fill add-to-cart-detailed" 
                                        data-product-id="<?php echo $producto['id']; ?>"
                                        data-product-name="<?php echo $producto['nombre']; ?>"
                                        data-product-price="<?php echo $producto['precio_publico']; ?>"
                                        data-product-image="<?php echo $producto['imagen']; ?>">
                                    <i class="fas fa-cart-plus me-2"></i>Agregar al Carrito
                                </button>
                                <button class="btn btn-outline-bloom btn-lg flex-fill buy-now">
                                    <i class="fas fa-bolt me-2"></i>Comprar Ahora
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="alert bloom-card mb-4" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3);">
                                <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                                <span class="text-light">Este producto está temporalmente agotado. Contáctanos para más información.</span>
                            </div>
                            <a href="https://wa.me/<?php echo str_replace('+', '', $telefono_empresa); ?>?text=Hola, me interesa el producto <?php echo urlencode($producto['nombre']); ?> (<?php echo $producto['codigo']; ?>) que está agotado. ¿Cuándo tendrán stock?" 
                               target="_blank" class="btn btn-whatsapp w-100">
                                <i class="fab fa-whatsapp me-2"></i>Consultar por WhatsApp
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos Relacionados -->
        <?php if (!empty($productos_relacionados)): ?>
        <div class="row mt-5 pt-5 border-top" style="border-color: rgba(200, 200, 200, 0.1) !important;">
            <div class="col-12">
                <h3 class="title-font mb-4 text-accent">Productos Relacionados</h3>
                <div class="row">
                    <?php foreach ($productos_relacionados as $relacionado): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="related-product-card bloom-card">
                            <div class="related-product-image-container">
                                <?php if (!empty($relacionado['imagen'])): ?>
                                <img src="../uploads/products/<?php echo $relacionado['imagen']; ?>" 
                                     class="related-product-image" 
                                     alt="<?php echo $relacionado['nombre']; ?>">
                                <?php else: ?>
                                <i class="fas fa-spa fa-3x text-accent"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <h6 class="mb-2 text-light" style="font-weight: 600; min-height: 40px;"><?php echo $relacionado['nombre']; ?></h6>
                                
                                <!-- Categorías del producto relacionado -->
                                <?php if (!empty($relacionado['categorias_nombres'])): ?>
                                <div class="mb-2">
                                    <?php 
                                    $categorias_rel_array = explode(', ', $relacionado['categorias_nombres']);
                                    foreach (array_slice($categorias_rel_array, 0, 2) as $categoria_rel): ?>
                                        <span class="badge categorias-badge"><?php echo $categoria_rel; ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($categorias_rel_array) > 2): ?>
                                        <span class="badge bg-secondary categorias-badge">+<?php echo count($categorias_rel_array) - 2; ?> más</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <p class="text-muted flex-grow-1" style="font-size: 0.85rem; line-height: 1.4; min-height: 40px;">
                                    <?php echo substr($relacionado['descripcion'], 0, 80); ?>...
                                </p>
                                <div class="mt-auto">
                                    <div class="mb-2" style="font-size: 1.1rem; font-weight: 700; color: var(--accent);">
                                        <?php 
                                        $precios_rel = formatPrecioDual($relacionado['precio_publico']);
                                        ?>
                                        <div class="fw-bold"><?php echo $precios_rel['gs']; ?></div>
                                        <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;"><?php echo $precios_rel['usd']; ?></div>
                                    </div>
                                    <div class="d-grid">
                                        <a href="producto.php?id=<?php echo $relacionado['id']; ?>" 
                                           class="btn btn-outline-bloom btn-sm">
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
    <footer class="footer-bloom">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center mb-4">
                        <i class="fas fa-spa fs-2 me-2" style="color: var(--accent);"></i>
                        <h3 class="title-font h4 mb-0 text-light"><?php echo $titulo_sistema; ?></h3>
                    </div>
                    <p class="mb-4 text-light"><?php echo $subtitulo_sistema; ?> de la más alta calidad para momentos especiales. Descubre la esencia de la elegancia en cada fragancia.</p>
                    <div class="d-flex gap-3">
                    <div class="d-flex gap-3">
                        <?php foreach ($redes_sociales as $red): ?>
                            <a href="<?php echo $red['url']; ?>" class="btn btn-outline-bloom btn-sm rounded-circle p-2" target="_blank">
                                <i class="<?php echo $red['icono']; ?>"></i>
                            </a>
                        <?php endforeach; ?>
        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-title-bloom">Enlaces</h5>
                    <ul class="list-unstyled footer-links-bloom">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="catalogo.php">Catálogo</a></li>
                        <li><a href="carrito.php">Carrito</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-title-bloom">Contacto</h5>
                    <ul class="list-unstyled footer-links-bloom">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-phone me-2 mt-1" style="color: var(--accent);"></i>
                            <span class="text-light"><?php echo $telefono_empresa; ?></span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-phone me-2 mt-1" style="color: var(--accent);"></i>
                            <span class="text-light">+595981934464</span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-envelope me-2 mt-1" style="color: var(--accent);"></i>
                            <span class="text-light"><?php echo $email_empresa; ?></span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-map-marker-alt me-2 mt-1" style="color: var(--accent);"></i>
                            <span class="text-light">CDE - Paraguay</span>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5 class="footer-title-bloom">Newsletter</h5>
                    <p class="mb-3 text-light">Suscríbete para recibir novedades y ofertas exclusivas.</p>
                    <div class="d-flex">
                        <input type="email" class="form-control me-2" placeholder="Tu correo">
                        <button class="btn btn-bloom">Enviar</button>
                    </div>
                </div>
            </div>
            <hr class="my-5" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="text-light">&copy; 2025 <?php echo $titulo_sistema; ?>. Todos los derechos reservados. <a href="https://www.facebook.com/gustavogabriel.velazquez1/" style="color: var(--accent);">Desarrollador</a></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Sistema de carrito
        let cart = JSON.parse(localStorage.getItem('bloom_cart')) || [];
        const telefonoEmpresa = '<?php echo str_replace('+', '', $telefono_empresa); ?>';
        
        function updateCartCount() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            $('#cart-count').text(totalItems);
            $('#cart-count-desktop').text(totalItems);
        }
        
        // Función para mostrar notificaciones bonitas
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHTML = `
                <div id="${toastId}" class="toast toast-${type} align-items-center" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            const toastElement = new bootstrap.Toast(document.getElementById(toastId));
            toastElement.show();
            
            // Remover el toast del DOM después de que se oculte
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function () {
                this.remove();
            });
        }
        
        // Cambiar imagen principal en la galería
        function changeMainImage(src) {
            $('#main-product-image').attr('src', src);
            $('.gallery-thumb-container').removeClass('active');
            $(event.target).closest('.gallery-thumb-container').addClass('active');
        }
        
        // Agregar al carrito desde la página de detalles
        $('.add-to-cart-detailed').click(function() {
            const productId = $(this).data('product-id');
            const productName = $(this).data('product-name');
            const productPrice = $(this).data('product-price');
            const productImage = $(this).data('product-image');
            const quantity = parseInt($('#quantity').val()) || 1;
            
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    image: productImage,
                    quantity: quantity
                });
            }
            
            localStorage.setItem('bloom_cart', JSON.stringify(cart));
            updateCartCount();
            
            showToast(`${productName} agregado al carrito`, 'success');
        });
        
        // Comprar ahora
        $('.buy-now').click(function() {
            const productId = $('.add-to-cart-detailed').data('product-id');
            const productName = $('.add-to-cart-detailed').data('product-name');
            const productPrice = $('.add-to-cart-detailed').data('product-price');
            const quantity = parseInt($('#quantity').val()) || 1;
            
            // Crear mensaje para WhatsApp
            const message = `¡Hola! Quiero comprar el siguiente producto:%0A%0A• ${productName}%0A• Cantidad: ${quantity}%0A• Precio unitario: Gs. ${productPrice.toLocaleString()}%0A• Total: Gs. ${(productPrice * quantity).toLocaleString()}%0A%0APor favor, contactame para coordinar la compra. ¡Gracias!`;
            
            // Abrir WhatsApp con el teléfono de la empresa
            window.open(`https://wa.me/${telefonoEmpresa}?text=${message}`, '_blank');
        });
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Inicializar contador del carrito
        updateCartCount();
    </script>
</body>
</html>