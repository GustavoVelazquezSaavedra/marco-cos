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

// Obtener todos los productos activos con filtros y múltiples categorías - CONSULTA CORREGIDA
$queryProductos = "SELECT p.*, 
                          GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres,
                          GROUP_CONCAT(c.id SEPARATOR ',') as categorias_ids
                   FROM productos p 
                   LEFT JOIN producto_categorias pc ON p.id = pc.producto_id 
                   LEFT JOIN categorias c ON pc.categoria_id = c.id 
                   WHERE p.activo = 1";
$params = [];

// Aplicar filtros - BÚSQUEDA CORREGIDA
if (!empty($search)) {
    $queryProductos .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($categoria_id)) {
    $queryProductos .= " AND p.id IN (SELECT producto_id FROM producto_categorias WHERE categoria_id = ?)";
    $params[] = $categoria_id;
}

$queryProductos .= " GROUP BY p.id";

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
    <title>Catálogo Completo - <?php echo $titulo_sistema; ?></title>
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
        --text-light: #e8e6e3;
        --text-muted: #a5a5a5;
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
    
    /* Search Container */
    .search-container {
        position: relative;
        margin-right: 20px;
    }
    
    .search-box-bloom {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(200, 200, 200, 0.3);
        border-radius: 25px;
        padding: 10px 45px 10px 20px;
        font-size: 0.9rem;
        width: 300px;
        transition: all 0.3s;
        color: var(--text-light);
    }
    
    .search-box-bloom:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 0.25rem rgba(232, 232, 232, 0.25);
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
        transform: translateY(-8px);
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
    
    /* Filter Sidebar */
    .filter-sidebar {
        position: sticky;
        top: 20px;
    }
    
    .filter-card {
        border: 1px solid rgba(200, 200, 200, 0.1);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
        background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
    }
    
    .filter-card .card-header {
        background: linear-gradient(135deg, var(--accent), var(--secondary));
        color: var(--primary);
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
        border-bottom: 1px solid rgba(200, 200, 200, 0.1);
    }
    
    .filter-list li:last-child {
        border-bottom: none;
    }
    
    .filter-list a {
        display: block;
        padding: 12px 20px;
        color: var(--text-light);
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .filter-list a:hover,
    .filter-list a.active {
        background: rgba(232, 232, 232, 0.1);
        color: var(--accent);
    }
    
    .filter-list a.active {
        font-weight: 600;
        border-left: 3px solid var(--accent);
    }
    
    /* ==================== */
    /* ESTILOS DE IMÁGENES MEJORADOS */
    /* ==================== */
    
    /* Product Cards - IMÁGENES UNIFORMES */
    .product-card-bloom {
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
    
    .product-card-bloom:hover {
        box-shadow: 0 15px 35px rgba(232, 232, 232, 0.15);
        transform: translateY(-5px);
        border-color: rgba(232, 232, 232, 0.3);
    }
    
    .product-image-container {
        height: 300px; /* Altura fija para imágenes de productos */
        width: 100%;
        overflow: hidden;
        position: relative;
        background: var(--dark);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .product-image-bloom {
        width: 100%;
        height: 100%;
        object-fit: contain; /* Muestra toda la imagen sin recortar */
        padding: 20px;
        transition: transform 0.3s ease;
    }
    
    .product-card-bloom:hover .product-image-bloom {
        transform: scale(1.05);
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
        background: rgba(232, 232, 232, 0.15);
        color: var(--accent);
        border: 1px solid rgba(232, 232, 232, 0.3);
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
    }
    
    .section-subtitle-bloom {
        color: var(--text-muted);
        margin-bottom: 3rem;
        font-size: 1.1rem;
    }
    
    .section-divider {
        width: 100px;
        height: 3px;
        background: linear-gradient(90deg, var(--accent), var(--secondary));
        margin-bottom: 3rem;
    }
    
    /* Contact Card */
    .contact-card {
        border: 1px solid rgba(200, 200, 200, 0.1);
        border-radius: 12px;
        background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
    }
    
    .contact-card .card-body {
        padding: 20px;
        text-align: center;
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
    
    /* Stock Badges */
    .stock-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.7rem;
        font-weight: 600;
        z-index: 2;
    }
    
    /* Text Colors Fix */
    .text-success {
        color: #25D366 !important;
    }
    
    .text-muted {
        color: var(--text-muted) !important;
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
        border: 1px solid rgba(200, 200, 200, 0.2);
        border-radius: 16px;
        color: var(--text-light);
    }
    
    .search-modal-bloom .modal-header {
        border-bottom: 1px solid rgba(200, 200, 200, 0.2);
        padding: 20px;
    }
    
    .search-modal-bloom .modal-body {
        padding: 20px;
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
        }
        
        .dropdown-menu {
            background: rgba(26, 26, 26, 0.95) !important;
            border: 1px solid rgba(200, 200, 200, 0.3) !important;
            margin-top: 5px;
        }
    }
    
    /* Corrección de los íconos ovalados - REDES SOCIALES CIRCULARES */
    .btn-outline-bloom.btn-sm.rounded-circle {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50% !important;
        padding: 0;
        border: 2px solid var(--accent) !important;
    }
    
    .btn-outline-bloom.btn-sm.rounded-circle i {
        font-size: 1rem;
    }
    
    .btn-outline-bloom.btn-sm.rounded-circle:hover {
        background: var(--accent);
        color: var(--primary);
    }
    
    /* Corrección para el slider en desktop */
    @media (min-width: 992px) {
        .slider-background {
            background-size: cover !important;
            background-position: center center !important;
        }
    }
    
    /* ==================== */
    /* RESPONSIVE MEJORADO */
    /* ==================== */
    
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
        
        .filter-sidebar {
            position: static;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 2.2rem;
        }
        
        /* IMÁGENES EN MOBILE */
        .product-image-container {
            height: 250px !important;
        }
        
        .product-image-bloom {
            padding: 15px;
        }
        
        .btn-bloom, .btn-outline-bloom, .btn-whatsapp {
            padding: 10px 20px;
            font-size: 0.9rem;
        }
        
        /* Corrección adicional para menú móvil */
        .navbar-toggler {
            border: 1px solid rgba(232, 232, 232, 0.3);
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(232, 232, 232, 0.25);
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
        
        .page-title {
            font-size: 1.8rem;
        }
        
        /* IMÁGENES EN MÓVILES PEQUEÑOS */
        .product-image-container {
            height: 220px !important;
        }
        
        .product-image-bloom {
            padding: 10px;
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

    /* Correcciones adicionales para mejor UX en móvil */
    @media (max-width: 991px) {
        .mobile-icons {
            gap: 12px;
        }
        
        .mobile-search-btn,
        .shop-icon-bloom,
        .cart-icon-bloom {
            font-size: 1.1rem;
        }
        
        .cart-badge-bloom {
            width: 18px;
            height: 18px;
            font-size: 0.65rem;
            top: -6px;
            right: -6px;
        }
    }

    /* Mejoras para tablets */
    @media (min-width: 768px) and (max-width: 991px) {
        .product-image-container {
            height: 280px !important;
        }
        
        .category-image-container {
            height: 220px !important;
        }
    }

    /* Ajustes para pantallas muy grandes */
    @media (min-width: 1400px) {
        .container {
            max-width: 1320px;
        }
        
        .product-image-container {
            height: 320px !important;
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
                        <a class="nav-link active" href="catalogo.php">CATÁLOGO</a>
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
                        <form method="GET" action="catalogo.php" class="position-relative">
                            <input class="form-control search-box-bloom" type="search" name="search" placeholder="Buscar productos..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
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
                    <form method="GET" action="catalogo.php">
                        <div class="input-group">
                            <input type="search" name="search" class="form-control" placeholder="Buscar productos..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-bloom" type="submit">
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
                    <div class="filter-card bloom-card">
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
                    <div class="filter-card bloom-card">
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
                    <div class="contact-card bloom-card mt-4">
                        <div class="card-body">
                            <h6 class="mb-3" style="color: var(--accent);"><i class="fas fa-headset me-2"></i>¿Necesitas ayuda?</h6>
                            <p class="small mb-3" style="color: var(--text-light);">
                                <i class="fas fa-phone me-1"></i><?php echo $telefono_empresa; ?>
                            </p>
                            <p class="small mb-3" style="color: var(--text-light);">
                                <i class="fas fa-phone me-1"></i>+595981934464
                            </p>
                            <a href="https://wa.me/<?php echo str_replace('+', '', $telefono_empresa); ?>" target="_blank" class="btn btn-whatsapp btn-sm w-100">
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
                        <h2 class="mb-1" style="color: var(--text-light);">
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
                    <a href="catalogo.php" class="btn btn-outline-bloom">
                        <i class="fas fa-times me-1"></i>Limpiar filtros
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Productos -->
                <div class="row">
                    <?php if (empty($productos)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="bloom-card p-5">
                            <i class="fas fa-search fa-3x text-accent mb-3"></i>
                            <h4 class="text-muted mb-3">No se encontraron productos</h4>
                            <p class="text-muted mb-4">Intenta con otros términos de búsqueda o categorías</p>
                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                <a href="catalogo.php" class="btn btn-bloom">
                                    <i class="fas fa-th-large me-1"></i>Ver Todo el Catálogo
                                </a>
                                <a href="index.php" class="btn btn-outline-bloom">
                                    <i class="fas fa-home me-1"></i>Volver al Inicio
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($productos as $producto): 
                        // Calcular precio en dólares
                        $precio_usd = $producto['precio_publico'] / $tipo_cambio['venta'];
                    ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="product-card-bloom bloom-card">
                            <div class="position-relative">
                                <div class="product-image-container">
                                    <?php if (!empty($producto['imagen'])): ?>
                                    <img src="../uploads/products/<?php echo $producto['imagen']; ?>" 
                                         class="product-image-bloom" alt="<?php echo $producto['nombre']; ?>">
                                    <?php else: ?>
                                    <i class="fas fa-spa fa-4x text-accent"></i>
                                    <?php endif; ?>
                                    
                                    <!-- Badge de stock -->
                                    <?php if ($producto['stock'] == 0): ?>
                                    <span class="stock-badge bg-danger text-white">Agotado</span>
                                    <?php elseif ($producto['stock'] < 10): ?>
                                    <span class="stock-badge bg-warning text-dark">Últimas unidades</span>
                                    <?php endif; ?>
                                </div>
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
                                       target="_blank" class="btn btn-whatsapp w-100 mb-2">
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
                        <div class="alert bloom-card p-3" style="background: rgba(232, 232, 232, 0.1); border: 1px solid rgba(232, 232, 232, 0.3); color: var(--text-light);">
                            <i class="fas fa-info-circle me-2 text-accent"></i>
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
            <div class="row g-5">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center mb-4">
                        <i class="fas fa-spa fs-2 me-2" style="color: var(--accent);"></i>
                        <h3 class="title-font h4 mb-0"><?php echo $titulo_sistema; ?></h3>
                    </div>
                    <p class="mb-4" style="color: var(--text-light);"><?php echo $subtitulo_sistema; ?> de la más alta calidad para momentos especiales. Descubre la esencia de la elegancia en cada fragancia.</p>
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
                            <span style="color: var(--text-light);"><?php echo $telefono_empresa; ?></span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-envelope me-2 mt-1" style="color: var(--accent);"></i>
                            <span style="color: var(--text-light);"><?php echo $email_empresa; ?></span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-map-marker-alt me-2 mt-1" style="color: var(--accent);"></i>
                            <span style="color: var(--text-light);">CDE - Paraguay</span>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5 class="footer-title-bloom">Newsletter</h5>
                    <p class="mb-3" style="color: var(--text-light);">Suscríbete para recibir novedades y ofertas exclusivas.</p>
                    <div class="d-flex">
                        <input type="email" class="form-control me-2" placeholder="Tu correo" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(200,200,200,0.3); color: white;">
                        <button class="btn btn-bloom">Enviar</button>
                    </div>
                </div>
            </div>
            <hr class="my-5" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p style="color: var(--text-light);">&copy; 2025 <?php echo $titulo_sistema; ?>. Todos los derechos reservados. <a href="https://www.facebook.com/gustavogabriel.velazquez1/" style="color: var(--accent);">Desarrollador</a></p>
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