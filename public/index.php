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

// Obtener tipo de cambio actual (ya está en functions.php)
$tipo_cambio = getTipoCambioActual();

// Obtener categorías principales organizadas con imagen aleatoria
$queryCategorias = "SELECT c.*, 
                   (SELECT imagen FROM productos p 
                    INNER JOIN producto_categorias pc ON p.id = pc.producto_id 
                    WHERE pc.categoria_id = c.id AND p.imagen IS NOT NULL AND p.imagen != '' 
                    ORDER BY RAND() LIMIT 1) as imagen_aleatoria
                   FROM categorias c 
                   WHERE c.activo = 1 
                   ORDER BY 
                     CASE 
                       WHEN c.nombre LIKE '%PERFUME MASCULINO%' THEN 1
                       WHEN c.nombre LIKE '%PERFUME FEMENINO%' THEN 2
                       WHEN c.nombre LIKE '%PERFUME ARABE%' THEN 3
                       WHEN c.nombre LIKE '%ARMAF%' THEN 4
                       WHEN c.nombre LIKE '%AFNAN%' THEN 5
                       WHEN c.nombre LIKE '%Rasasi%' THEN 6
                       WHEN c.nombre LIKE '%Lattafa%' THEN 7
                       WHEN c.nombre LIKE '%French Avenue%' THEN 8
                       ELSE 9
                     END, c.nombre";

$stmtCategorias = $db->prepare($queryCategorias);
$stmtCategorias->execute();
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

// Organizar categorías por tipo
$categoriasOrganizadas = [
    'masculino' => [],
    'femenino' => [],
    'arabe' => [],
    'marcas' => []
];

foreach ($categorias as $categoria) {
    $nombre = strtoupper($categoria['nombre']);
    
    if (strpos($nombre, 'MASCULINO') !== false) {
        $categoriasOrganizadas['masculino'][] = $categoria;
    } elseif (strpos($nombre, 'FEMENINO') !== false) {
        $categoriasOrganizadas['femenino'][] = $categoria;
    } elseif (strpos($nombre, 'ARABE') !== false) {
        $categoriasOrganizadas['arabe'][] = $categoria;
    } else {
        $categoriasOrganizadas['marcas'][] = $categoria;
    }
}

// Obtener productos destacados (los más recientes) con múltiples categorías
$queryProductos = "SELECT p.*, 
                          GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres,
                          GROUP_CONCAT(c.id SEPARATOR ',') as categorias_ids
                   FROM productos p 
                   LEFT JOIN producto_categorias pc ON p.id = pc.producto_id 
                   LEFT JOIN categorias c ON pc.categoria_id = c.id 
                   WHERE p.activo = 1 
                   GROUP BY p.id
                   ORDER BY p.fecha_creacion DESC 
                   LIMIT 8";
$stmtProductos = $db->prepare($queryProductos);
$stmtProductos->execute();
$productos_destacados = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

// Procesar búsqueda
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$categoria_id = isset($_GET['categoria_id']) ? $_GET['categoria_id'] : '';

// Obtener productos con filtros y múltiples categorías - CONSULTA CORREGIDA
$queryProductosFiltro = "SELECT p.*, 
                                GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres,
                                GROUP_CONCAT(c.id SEPARATOR ',') as categorias_ids
                         FROM productos p 
                         LEFT JOIN producto_categorias pc ON p.id = pc.producto_id 
                         LEFT JOIN categorias c ON pc.categoria_id = c.id 
                         WHERE p.activo = 1";
$params = [];

if (!empty($search)) {
    $queryProductosFiltro .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($categoria_id)) {
    $queryProductosFiltro .= " AND p.id IN (SELECT producto_id FROM producto_categorias WHERE categoria_id = ?)";
    $params[] = $categoria_id;
}

$queryProductosFiltro .= " GROUP BY p.id ORDER BY p.fecha_creacion DESC";
$stmtProductosFiltro = $db->prepare($queryProductosFiltro);
$stmtProductosFiltro->execute($params);
$productos = $stmtProductosFiltro->fetchAll(PDO::FETCH_ASSOC);

// Obtener slides activos
$querySlides = "SELECT s.*, p.id as producto_id, p.nombre as producto_nombre 
                FROM slider_principal s 
                LEFT JOIN productos p ON s.producto_id = p.id 
                WHERE s.activo = 1 
                ORDER BY s.orden ASC 
                LIMIT 5";
$stmtSlides = $db->prepare($querySlides);
$stmtSlides->execute();
$slides = $stmtSlides->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_sistema; ?> - <?php echo $subtitulo_sistema; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0a0a0a;
            --secondary: #8b7d5a;
            --accent: #b8a86d;
            --light: #f8f9fa;
            --dark: #1a1a1a;
            --success: #9caf88;
            --text-light: #e8e6e3;
            --text-muted: #a5a5a5;
            --gold-light: #d4c19c;
            --gold-dark: #8b7d5a;
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
            border-bottom: 1px solid rgba(139, 125, 90, 0.2);
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
            background: rgba(184, 168, 109, 0.1);
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
            border: 1px solid rgba(139, 125, 90, 0.2) !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
        }
        
        .dropdown-item {
            color: var(--text-light) !important;
            padding: 10px 15px !important;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background: rgba(184, 168, 109, 0.1) !important;
            color: var(--accent) !important;
        }
        
        /* Search Container */
        .search-container {
            position: relative;
            margin-right: 20px;
        }
        
        .search-box-bloom {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(139, 125, 90, 0.3);
            border-radius: 25px;
            padding: 10px 45px 10px 20px;
            font-size: 0.9rem;
            width: 300px;
            transition: all 0.3s;
            color: var(--text-light);
        }
        
        .search-box-bloom:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 0.25rem rgba(184, 168, 109, 0.25);
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-light);
        }
        
        .search-box-bloom::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .search-icon-bloom {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            background: none;
            border: none;
        }
        
        .cart-icon-bloom, .shop-icon-bloom {
            color: var(--accent);
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }
        
        .cart-icon-bloom:hover, .shop-icon-bloom:hover {
            color: var(--text-light);
            transform: scale(1.1);
        }
        
        /* Card Style */
        .bloom-card {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            border: 1px solid rgba(139, 125, 90, 0.1);
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
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(184, 168, 109, 0.15);
            border-color: rgba(184, 168, 109, 0.3);
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
            box-shadow: 0 4px 12px rgba(184, 168, 109, 0.3);
        }
        
        .btn-bloom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(184, 168, 109, 0.4);
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
            box-shadow: 0 6px 20px rgba(184, 168, 109, 0.3);
        }
        
        /* Slider Styles - Controles discretos */
        .slider-section {
            background: var(--primary);
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        
        #mainSlider {
            margin-bottom: 0;
        }
        
        .carousel-item {
            height: 500px;
            transition: transform 0.6s ease-in-out;
        }
        
        .slider-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .slider-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(10,10,10,0.7) 0%, rgba(26,26,26,0.5) 100%);
        }
        
        .slider-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            align-items: center;
            padding: 0 2rem;
        }
        
        .season-badge-slider {
            display: inline-block;
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            color: var(--primary);
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.8rem;
            box-shadow: 0 4px 12px rgba(184, 168, 109, 0.3);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Controles del slider discretos */
        .carousel-control-prev,
        .carousel-control-next {
            width: 40px;
            height: 40px;
            background: rgba(184, 168, 109, 0.1);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            margin: 0 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(184, 168, 109, 0.2);
            transition: all 0.3s ease;
            opacity: 0.7;
        }
        
        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: rgba(184, 168, 109, 0.3);
            opacity: 1;
            transform: translateY(-50%) scale(1.1);
        }
        
        .carousel-control-prev {
            left: 10px;
        }
        
        .carousel-control-next {
            right: 10px;
        }
        
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            width: 20px;
            height: 20px;
            filter: brightness(0) invert(1);
        }
        
        /* Indicadores discretos */
        .carousel-indicators {
            bottom: 20px;
            margin: 0;
        }
        
        .carousel-indicators button {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin: 0 4px;
            background: rgba(255, 255, 255, 0.3);
            border: none;
            transition: all 0.3s ease;
        }
        
        .carousel-indicators button.active {
            background: var(--accent);
            transform: scale(1.2);
        }
        
        /* Product Cards */
        .product-card-bloom {
            border: 1px solid rgba(139, 125, 90, 0.1);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 25px;
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card-bloom:hover {
            box-shadow: 0 15px 35px rgba(184, 168, 109, 0.15);
            transform: translateY(-5px);
            border-color: rgba(184, 168, 109, 0.3);
        }
        
        .product-image-bloom {
            height: 250px;
            object-fit: cover;
            width: 100%;
            border-bottom: 1px solid rgba(139, 125, 90, 0.1);
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
            color: var(--text-light);
            min-height: 40px;
        }
        
        .product-description-bloom {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 10px;
            line-height: 1.4;
            flex-grow: 1;
            min-height: 40px;
        }
        
        .product-price-bloom {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 15px;
        }
        
        .price-usd {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        
        .product-actions-bloom {
            margin-top: auto;
        }
        
        .btn-add-cart-bloom {
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            color: var(--primary);
            border: none;
            border-radius: 8px;
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
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            transform: translateY(-2px);
        }
        
        .btn-details-bloom {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
            display: block;
            padding: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-details-bloom:hover {
            color: var(--text-light);
        }
        
        /* Categorías badges */
        .categorias-badge {
            margin: 2px;
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 12px;
            background: rgba(184, 168, 109, 0.15);
            color: var(--accent);
            border: 1px solid rgba(184, 168, 109, 0.3);
        }
        
        .categorias-container {
            min-height: 40px;
            margin-bottom: 8px;
        }
        
        /* Section Titles */
        .section-title-bloom {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .section-subtitle-bloom {
            color: var(--text-muted);
            margin-bottom: 3rem;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .section-divider {
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--secondary));
            margin: 0 auto 3rem;
        }
        
        /* Categories Section - NUEVO ESTILO */
        .categories-section-bloom {
            background: var(--dark);
            padding: 80px 0;
            border-top: 1px solid rgba(139, 125, 90, 0.1);
        }
        
        .category-card-bloom {
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            border: 1px solid rgba(139, 125, 90, 0.1);
            border-radius: 12px;
            padding: 30px 25px;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s;
            height: 100%;
        }
        
        .category-card-bloom:hover {
            border-color: var(--accent);
            transform: translateY(-5px);
        }
        
        .category-image-bloom {
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .category-title-bloom {
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--accent);
        }
        
        .category-description-bloom {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 20px;
            line-height: 1.5;
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
        
        /* Mobile Styles */
        .mobile-icons {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .mobile-search-btn {
            background: none;
            border: none;
            color: var(--accent);
            font-size: 1.2rem;
            padding: 5px;
        }
        
        /* Modal Styles */
        .search-modal-bloom .modal-content {
            background: var(--dark);
            border: 1px solid rgba(139, 125, 90, 0.2);
            border-radius: 16px;
            color: var(--text-light);
        }
        
        .search-modal-bloom .modal-header {
            border-bottom: 1px solid rgba(139, 125, 90, 0.2);
            padding: 20px;
        }
        
        .search-modal-bloom .modal-body {
            padding: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.5rem;
            }
            
            .search-box-bloom {
                width: 250px;
            }
            
            .section-title-bloom {
                font-size: 2rem;
            }
            
            .nav-link {
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
            
            .categorias-badge {
                font-size: 0.65rem;
                padding: 3px 6px;
            }
            
            .carousel-item {
                height: 450px;
            }
            
            .slider-content {
                padding: 0 1rem;
            }
            
            .carousel-control-prev,
            .carousel-control-next {
                width: 35px;
                height: 35px;
                margin: 0 5px;
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
            
            .section-title-bloom {
                font-size: 1.8rem;
            }
            
            .carousel-item {
                height: 400px;
            }
            
            .carousel-control-prev,
            .carousel-control-next {
                display: none;
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
                <button class="mobile-search-btn" type="button" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="fas fa-search"></i>
                </button>
                
                <a href="catalogo.php" class="text-decoration-none">
                    <i class="fas fa-store shop-icon-bloom"></i>
                </a>
                
                <a href="carrito.php" class="text-decoration-none position-relative">
                    <i class="fas fa-shopping-bag cart-icon-bloom"></i>
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
                        <a class="nav-link active" href="index.php">INICIO</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
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
                        <a class="nav-link" href="catalogo.php">CATÁLOGO</a>
                    </li>
                </ul>
                
                <!-- Elementos para desktop -->
                <div class="d-none d-lg-flex align-items-center">
                    <div class="navbar-nav me-3">
                        <a class="nav-link" href="catalogo.php">
                            <i class="fas fa-store shop-icon-bloom"></i>
                        </a>
                    </div>
                    
                    <div class="search-container">
                        <form method="GET" action="index.php" class="position-relative">
                            <input class="form-control search-box-bloom" type="search" name="search" placeholder="Buscar productos..." 
                                   value="<?php echo $search; ?>">
                            <button class="search-icon-bloom" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="index.php">
                        <div class="input-group">
                            <input type="search" name="search" class="form-control" placeholder="Buscar productos..." 
                                   value="<?php echo $search; ?>">
                            <button class="btn btn-bloom" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Slider Principal -->
    <?php if (!empty($slides)): ?>
    <section class="slider-section">
        <div id="mainSlider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
            <!-- Indicadores -->
            <div class="carousel-indicators">
                <?php foreach ($slides as $index => $slide): ?>
                <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="<?php echo $index; ?>" 
                        class="<?php echo $index === 0 ? 'active' : ''; ?>" 
                        <?php if($index === 0): ?>aria-current="true"<?php endif; ?>
                        aria-label="Slide <?php echo $index + 1; ?>"></button>
                <?php endforeach; ?>
            </div>
            
            <!-- Slides -->
            <div class="carousel-inner">
                <?php foreach ($slides as $index => $slide): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <?php
                    $backgroundImage = 'https://images.unsplash.com/photo-1547887537-6158d64c35b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80';
                    if (!empty($slide['imagen'])) {
                        $backgroundImage = '../uploads/slider/' . $slide['imagen'];
                    }
                    ?>
                    <div class="slider-background" style="background-image: url('<?php echo $backgroundImage; ?>');"></div>
                    <div class="container">
                        <div class="slider-content">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <div class="season-badge-slider">
                                        COLECCIÓN EXCLUSIVA
                                    </div>
                                    
                                    <h1 class="display-4 title-font fw-bold mb-4 text-white">
                                        <?php echo !empty($slide['titulo']) ? $slide['titulo'] : $titulo_sistema . ' ' . $subtitulo_sistema; ?>
                                    </h1>
                                    
                                    <?php if (!empty($slide['subtitulo'])): ?>
                                    <p class="lead mb-5 fs-5 text-light opacity-75">
                                        <?php echo $slide['subtitulo']; ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex flex-wrap gap-3">
                                        <?php if (!empty($slide['producto_id'])): ?>
                                        <a href="producto.php?id=<?php echo $slide['producto_id']; ?>" class="btn btn-bloom btn-lg">
                                            <i class="fas fa-gem me-2"></i><?php echo !empty($slide['texto_boton']) ? $slide['texto_boton'] : 'VER PRODUCTO'; ?>
                                        </a>
                                        <?php else: ?>
                                        <a href="catalogo.php" class="btn btn-bloom btn-lg">
                                            <i class="fas fa-gem me-2"></i><?php echo !empty($slide['texto_boton']) ? $slide['texto_boton'] : 'EXPLORAR COLECCIÓN'; ?>
                                        </a>
                                        <?php endif; ?>
                                        <a href="catalogo.php" class="btn btn-outline-bloom btn-lg">
                                            <i class="fas fa-store me-2"></i>VER CATÁLOGO
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Controles -->
            <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>
    </section>
    <?php else: ?>
    <!-- Hero Section por defecto si no hay slides -->
    <section class="slider-section">
        <div id="mainSlider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="slider-background" style="background-image: url('https://images.unsplash.com/photo-1547887537-6158d64c35b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80');"></div>
                    <div class="container">
                        <div class="slider-content">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <div class="season-badge-slider">
                                        BIENVENIDO
                                    </div>
                                    
                                    <h1 class="display-4 title-font fw-bold mb-4 text-white">
                                        <?php echo $titulo_sistema; ?> <?php echo $subtitulo_sistema; ?>
                                    </h1>
                                    
                                    <p class="lead mb-5 fs-5 text-light opacity-75">
                                        Descubre la esencia de la elegancia en cada fragancia. Perfumes únicos para momentos inolvidables.
                                    </p>
                                    
                                    <div class="d-flex flex-wrap gap-3">
                                        <a href="catalogo.php" class="btn btn-bloom btn-lg">
                                            <i class="fas fa-gem me-2"></i>EXPLORAR COLECCIÓN
                                        </a>
                                        <a href="catalogo.php?categoria_id=1" class="btn btn-outline-bloom btn-lg">
                                            <i class="fas fa-star me-2"></i>PERFUMES ÁRABES
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Controles -->
            <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>
    </section>
    <?php endif; ?>

    <!-- Productos Destacados -->
    <section class="py-5" style="background: var(--primary);">
        <div class="container py-5">
            <?php if (empty($search) && empty($categoria_id)): ?>
                <!-- Mostrar productos destacados cuando no hay búsqueda -->
                <h2 class="section-title-bloom">Nuestra Colección</h2>
                <p class="section-subtitle-bloom">Fragancias exclusivas que definen tu estilo</p>
                <div class="section-divider"></div>
                
                <div class="row">
                    <?php if (empty($productos_destacados)): ?>
                    <div class="col-12 text-center">
                        <div class="bloom-card p-5">
                            <i class="fas fa-info-circle display-1 text-accent mb-3"></i>
                            <h3 class="title-font h2 mb-3">Próximamente</h3>
                            <p class="fs-5 mb-4">Estamos preparando nuevas fragancias exclusivas para ti.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($productos_destacados as $producto): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="product-card-bloom bloom-card">
                            <div class="position-relative">
                                <?php if (!empty($producto['imagen'])): ?>
                                <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                                     class="product-image-bloom" alt="<?php echo $producto['nombre']; ?>">
                                <?php else: ?>
                                <div class="product-image-bloom bg-dark d-flex align-items-center justify-content-center">
                                    <i class="fas fa-spa fa-3x text-accent"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info-bloom">
                                <h5 class="product-title-bloom"><?php echo $producto['nombre']; ?></h5>
                                <p class="product-description-bloom">
                                    <?php echo substr($producto['descripcion'], 0, 80); ?>...
                                </p>
                                
                                <!-- Categorías del producto -->
                                <div class="categorias-container">
                                    <?php if (!empty($producto['categorias_nombres'])): ?>
                                        <?php 
                                        $categorias_array = explode(', ', $producto['categorias_nombres']);
                                        foreach (array_slice($categorias_array, 0, 2) as $categoria): ?>
                                            <span class="badge categorias-badge"><?php echo $categoria; ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($categorias_array) > 2): ?>
                                            <span class="badge bg-secondary categorias-badge">+<?php echo count($categorias_array) - 2; ?> más</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary categorias-badge">Sin categorías</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-price-bloom">
                                    <?php 
                                    $precios = formatPrecioDual($producto['precio_publico']);
                                    ?>
                                    <div class="fw-bold"><?php echo $precios['gs']; ?></div>
                                    <div class="price-usd"><?php echo $precios['usd']; ?></div>
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
                        <a href="catalogo.php" class="btn btn-outline-bloom btn-lg">
                            VER TODA LA COLECCIÓN <i class="fas fa-arrow-right ms-2"></i>
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
                        <div class="section-divider"></div>
                    </div>
                </div>
                
                <div class="row">
                    <?php if (empty($productos)): ?>
                    <div class="col-12 text-center">
                        <div class="bloom-card p-5">
                            <i class="fas fa-search display-1 text-accent mb-3"></i>
                            <h3 class="title-font h2 mb-3">No se encontraron productos</h3>
                            <p class="fs-5 mb-4">No hay productos que coincidan con tu búsqueda.</p>
                            <a href="index.php" class="btn btn-bloom">VER TODOS LOS PRODUCTOS</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="product-card-bloom bloom-card">
                            <div class="position-relative">
                                <?php if (!empty($producto['imagen'])): ?>
                                <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                                     class="product-image-bloom" alt="<?php echo $producto['nombre']; ?>">
                                <?php else: ?>
                                <div class="product-image-bloom bg-dark d-flex align-items-center justify-content-center">
                                    <i class="fas fa-spa fa-3x text-accent"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info-bloom">
                                <h5 class="product-title-bloom"><?php echo $producto['nombre']; ?></h5>
                                <p class="product-description-bloom">
                                    <?php echo substr($producto['descripcion'], 0, 80); ?>...
                                </p>
                                
                                <!-- Categorías del producto -->
                                <div class="categorias-container">
                                    <?php if (!empty($producto['categorias_nombres'])): ?>
                                        <?php 
                                        $categorias_array = explode(', ', $producto['categorias_nombres']);
                                        foreach (array_slice($categorias_array, 0, 2) as $categoria): ?>
                                            <span class="badge categorias-badge"><?php echo $categoria; ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($categorias_array) > 2): ?>
                                            <span class="badge bg-secondary categorias-badge">+<?php echo count($categorias_array) - 2; ?> más</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary categorias-badge">Sin categorías</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-price-bloom">
                                    <?php 
                                    $precios = formatPrecioDual($producto['precio_publico']);
                                    ?>
                                    <div class="fw-bold"><?php echo $precios['gs']; ?></div>
                                    <div class="price-usd"><?php echo $precios['usd']; ?></div>
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
                        <a href="index.php" class="btn btn-outline-bloom">
                            <i class="fas fa-arrow-left me-2"></i>VOLVER AL INICIO
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Categorías ORGANIZADAS - SECCIÓN CORREGIDA -->
    <section class="categories-section-bloom">
        <div class="container">
            <h2 class="section-title-bloom">Nuestras Categorías</h2>
            <p class="section-subtitle-bloom">Explora nuestras colecciones organizadas</p>
            <div class="section-divider"></div>
            
            <!-- Perfumes Masculinos -->
            <?php if (!empty($categoriasOrganizadas['masculino'])): ?>
            <div class="row mb-5">
                <div class="col-12">
                    <h3 class="title-font h2 mb-4 text-center" style="color: var(--accent);">Perfumes Masculinos</h3>
                </div>
                <?php foreach ($categoriasOrganizadas['masculino'] as $categoria): ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="category-card-bloom bloom-card">
                        <?php if (!empty($categoria['imagen_aleatoria'])): ?>
                        <img src="../uploads/products/<?php echo $categoria['imagen_aleatoria']; ?>" 
                             class="category-image-bloom" alt="<?php echo $categoria['nombre']; ?>">
                        <?php else: ?>
                        <div class="category-image-bloom bg-dark d-flex align-items-center justify-content-center">
                            <i class="fas fa-mars fa-3x text-accent"></i>
                        </div>
                        <?php endif; ?>
                        <h4 class="category-title-bloom"><?php echo $categoria['nombre']; ?></h4>
                        <p class="category-description-bloom">
                            <?php echo !empty($categoria['descripcion']) ? $categoria['descripcion'] : 'Descubre nuestra exclusiva colección de perfumes masculinos'; ?>
                        </p>
                        <a href="?categoria_id=<?php echo $categoria['id']; ?>" class="btn btn-bloom">
                            Explorar Colección
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Perfumes Femeninos -->
            <?php if (!empty($categoriasOrganizadas['femenino'])): ?>
            <div class="row mb-5">
                <div class="col-12">
                    <h3 class="title-font h2 mb-4 text-center" style="color: var(--accent);">Perfumes Femeninos</h3>
                </div>
                <?php foreach ($categoriasOrganizadas['femenino'] as $categoria): ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="category-card-bloom bloom-card">
                        <?php if (!empty($categoria['imagen_aleatoria'])): ?>
                        <img src="../uploads/products/<?php echo $categoria['imagen_aleatoria']; ?>" 
                             class="category-image-bloom" alt="<?php echo $categoria['nombre']; ?>">
                        <?php else: ?>
                        <div class="category-image-bloom bg-dark d-flex align-items-center justify-content-center">
                            <i class="fas fa-venus fa-3x text-accent"></i>
                        </div>
                        <?php endif; ?>
                        <h4 class="category-title-bloom"><?php echo $categoria['nombre']; ?></h4>
                        <p class="category-description-bloom">
                            <?php echo !empty($categoria['descripcion']) ? $categoria['descripcion'] : 'Fragancias femeninas que realzan tu belleza interior'; ?>
                        </p>
                        <a href="?categoria_id=<?php echo $categoria['id']; ?>" class="btn btn-bloom">
                            Explorar Colección
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Perfumes Árabes -->
            <?php if (!empty($categoriasOrganizadas['arabe'])): ?>
            <div class="row mb-5">
                <div class="col-12">
                    <h3 class="title-font h2 mb-4 text-center" style="color: var(--accent);">Perfumes Árabes</h3>
                </div>
                <?php foreach ($categoriasOrganizadas['arabe'] as $categoria): ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="category-card-bloom bloom-card">
                        <?php if (!empty($categoria['imagen_aleatoria'])): ?>
                        <img src="../uploads/products/<?php echo $categoria['imagen_aleatoria']; ?>" 
                             class="category-image-bloom" alt="<?php echo $categoria['nombre']; ?>">
                        <?php else: ?>
                        <div class="category-image-bloom bg-dark d-flex align-items-center justify-content-center">
                            <i class="fas fa-gem fa-3x text-accent"></i>
                        </div>
                        <?php endif; ?>
                        <h4 class="category-title-bloom"><?php echo $categoria['nombre']; ?></h4>
                        <p class="category-description-bloom">
                            <?php echo !empty($categoria['descripcion']) ? $categoria['descripcion'] : 'Fragancias árabes de lujo con esencias exóticas'; ?>
                        </p>
                        <a href="?categoria_id=<?php echo $categoria['id']; ?>" class="btn btn-bloom">
                            Explorar Colección
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Marcas -->
            <?php if (!empty($categoriasOrganizadas['marcas'])): ?>
            <div class="row">
                <div class="col-12">
                    <h3 class="title-font h2 mb-4 text-center" style="color: var(--accent);">Nuestras Marcas</h3>
                </div>
                <?php foreach ($categoriasOrganizadas['marcas'] as $categoria): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="category-card-bloom bloom-card">
                        <?php if (!empty($categoria['imagen_aleatoria'])): ?>
                        <img src="../uploads/products/<?php echo $categoria['imagen_aleatoria']; ?>" 
                             class="category-image-bloom" alt="<?php echo $categoria['nombre']; ?>">
                        <?php else: ?>
                        <div class="category-image-bloom bg-dark d-flex align-items-center justify-content-center">
                            <i class="fas fa-crown fa-3x text-accent"></i>
                        </div>
                        <?php endif; ?>
                        <h4 class="category-title-bloom"><?php echo $categoria['nombre']; ?></h4>
                        <a href="?categoria_id=<?php echo $categoria['id']; ?>" class="btn btn-outline-bloom btn-sm">
                            Ver Productos
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-bloom">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center mb-4">
                        <i class="fas fa-spa fs-2 me-2" style="color: var(--accent);"></i>
                        <h3 class="title-font h4 mb-0"><?php echo $titulo_sistema; ?></h3>
                    </div>
                    <p class="mb-4"><?php echo $subtitulo_sistema; ?> de la más alta calidad para momentos especiales. Descubre la esencia de la elegancia en cada fragancia.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="btn btn-outline-bloom btn-sm rounded-circle p-2">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="btn btn-outline-bloom btn-sm rounded-circle p-2">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://wa.me/<?php echo str_replace('+', '', $telefono_empresa); ?>" class="btn btn-outline-bloom btn-sm rounded-circle p-2">
                            <i class="fab fa-whatsapp"></i>
                        </a>
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
                            <span><?php echo $telefono_empresa; ?></span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-envelope me-2 mt-1" style="color: var(--accent);"></i>
                            <span><?php echo $email_empresa; ?></span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-map-marker-alt me-2 mt-1" style="color: var(--accent);"></i>
                            <span>CDE - Paraguay</span>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5 class="footer-title-bloom">Newsletter</h5>
                    <p class="mb-3">Suscríbete para recibir novedades y ofertas exclusivas.</p>
                    <div class="d-flex">
                        <input type="email" class="form-control me-2" placeholder="Tu correo" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(139,125,90,0.3); color: white;">
                        <button class="btn btn-bloom">Enviar</button>
                    </div>
                </div>
            </div>
            <hr class="my-5" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p>&copy; 2025 <?php echo $titulo_sistema; ?>. Todos los derechos reservados. <a href="https://www.facebook.com/gustavogabriel.velazquez1/" style="color: var(--accent);">Desarrollador</a></p>
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
            
            // Mostrar notificación
            showToast('¡Producto agregado!', `${productName} se agregó al carrito`, 'success');
        });
        
        // Función para mostrar notificaciones
        function showToast(title, message, type = 'info') {
            // Crear toast dinámico
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'warning'} border-0 position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}</strong><br>${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            $('body').append(toastHtml);
            const toast = new bootstrap.Toast($('.toast').last()[0]);
            toast.show();
            
            // Remover después de cerrar
            $('.toast').last().on('hidden.bs.toast', function () {
                $(this).remove();
            });
        }
        
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
        
        // Smooth scroll para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>