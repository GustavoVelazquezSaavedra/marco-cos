<?php
include_once('../includes/database.php');
include_once('../includes/functions.php');

$database = new Database();
$db = $database->getConnection();

// Obtener información de la empresa desde la base de datos
$titulo_sistema = "BLOOM"; // Valor por defecto
$telefono_empresa = "+595976588694"; // Valor por defecto
$horario_empresa = "Lun-Vie: 8:00-18:00"; // Valor por defecto

// Intentar obtener de la base de datos si hay conexión
try {
    $query_config = "SELECT clave, valor FROM configuraciones WHERE clave IN ('titulo_sistema', 'telefono', 'horario')";
    $stmt_config = $db->prepare($query_config);
    $stmt_config->execute();
    $configs = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (isset($configs['titulo_sistema'])) {
        $titulo_sistema = $configs['titulo_sistema'];
    }
    if (isset($configs['telefono'])) {
        $telefono_empresa = $configs['telefono'];
    }
    if (isset($configs['horario'])) {
        $horario_empresa = $configs['horario'];
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - <?php echo $titulo_sistema; ?></title>
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
        
        .btn-danger-bloom {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-danger-bloom:hover {
            background: #c82333;
            transform: translateY(-1px);
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
        
        /* Cart Items - IMÁGENES UNIFORMES */
        .cart-item {
            transition: all 0.3s ease;
            padding: 20px 0;
            border-bottom: 1px solid rgba(200, 200, 200, 0.1);
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item.removing {
            opacity: 0;
            height: 0;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .product-thumb-container {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            border: 1px solid rgba(200, 200, 200, 0.2);
            overflow: hidden;
            background: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-thumb {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Muestra toda la imagen sin recortar */
            padding: 8px;
            transition: transform 0.3s ease;
        }
        
        .cart-item:hover .product-thumb {
            transform: scale(1.05);
        }
        
        .product-title {
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 5px;
        }
        
        .product-price {
            color: var(--accent);
            font-weight: 600;
        }
        
        .product-price-usd {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid rgba(200, 200, 200, 0.3);
            border-radius: 6px;
            padding: 5px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
        }
        
        .btn-quantity {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(200, 200, 200, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
            transition: all 0.3s ease;
        }
        
        .btn-quantity:hover {
            background: rgba(232, 232, 232, 0.2);
            border-color: var(--accent);
        }
        
        /* Empty Cart */
        .empty-cart-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        .cart-items-container {
            min-height: 200px;
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
        
        .toast-danger {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
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
        
        /* Exchange Rate Info */
        .exchange-info {
            background: linear-gradient(135deg, rgba(232, 232, 232, 0.2), rgba(200, 200, 200, 0.1));
            color: var(--text-light);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(200, 200, 200, 0.2);
        }
        
        .exchange-info small {
            opacity: 0.9;
            color: var(--text-light);
        }
        
        /* Summary USD */
        .summary-usd {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        
        /* Text Colors Fix */
        .text-success {
            color: #25D366 !important;
        }
        
        .text-muted {
            color: var(--text-muted) !important;
        }
        
        /* Modal */
        .modal-content {
            background: var(--dark);
            border: 1px solid rgba(200, 200, 200, 0.2);
            border-radius: 16px;
            color: var(--text-light);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            color: var(--primary);
            border: none;
            padding: 20px;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
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
            color: white;
        }
        
        /* ==================== */
        /* ESTILOS MEJORADOS PARA RESUMEN DEL PEDIDO */
        /* ==================== */
        
        .order-summary-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(200, 200, 200, 0.1);
            margin-bottom: 8px;
        }
        
        .order-summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .order-summary-total {
            background: rgba(232, 232, 232, 0.05);
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            border: 1px solid rgba(232, 232, 232, 0.1);
        }
        
        .summary-label {
            font-weight: 500;
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .summary-value {
            font-weight: 600;
            color: var(--accent);
            text-align: right;
        }
        
        .summary-usd-value {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: 2px;
        }
        
        .total-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent);
        }
        
        .total-usd-amount {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-muted);
        }
        
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(232, 232, 232, 0.3), transparent);
            margin: 20px 0;
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
            
            .product-thumb-container {
                width: 60px;
                height: 60px;
            }
            
            .product-thumb {
                padding: 5px;
            }
            
            .cart-item {
                padding: 15px 0;
            }
            
            .btn-bloom, .btn-outline-bloom, .btn-whatsapp {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            
            .mobile-icons {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            
            .order-summary-item {
                padding: 10px 0;
            }
        }
        
        @media (max-width: 576px) {
            .page-title {
                font-size: 1.8rem;
            }
            
            .product-thumb-container {
                width: 50px;
                height: 50px;
            }
            
            .product-title {
                font-size: 0.9rem;
            }
            
            .product-price, .product-price-usd {
                font-size: 0.8rem;
            }
            
            .quantity-input {
                width: 50px;
                font-size: 0.8rem;
            }
            
            .btn-quantity {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }
            
            .exchange-info {
                padding: 15px;
            }
            
            .exchange-info strong {
                font-size: 0.9rem;
            }
            
            .total-amount {
                font-size: 1.1rem;
            }
            
            .total-usd-amount {
                font-size: 0.9rem;
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
                        <a class="nav-link active" href="carrito.php">CARRITO</a>
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><i class="fas fa-shopping-bag me-2"></i>Tu Carrito de Compras</h1>
            <p class="page-subtitle">Revisa y gestiona tus productos seleccionados</p>
        </div>
    </section>

    <div class="container py-4">
        <!-- Información del tipo de cambio -->
        <div class="exchange-info bloom-card">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <strong><i class="fas fa-dollar-sign me-1"></i> Tipo de cambio: GS. <?php echo number_format($tipo_cambio['venta'], 0, ',', '.'); ?> por USD</strong>
                </div>
                <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                    <small><i class="fas fa-info-circle me-1"></i> Los precios en USD se calculan con tipo de cambio venta</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="bloom-card">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--accent), var(--secondary)); color: var(--primary); border: none; padding: 15px 20px; font-weight: 600;">
                        <h5 class="mb-0 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-shopping-cart me-2"></i>Productos en el Carrito</span>
                            <button class="btn btn-danger-bloom btn-sm" id="clear-cart-btn" style="display: none;">
                                <i class="fas fa-trash me-1"></i>Vaciar Carrito
                            </button>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="cart-items-container" id="cart-items-container">
                            <div id="cart-items">
                                <!-- Los productos del carrito se cargan aquí con JavaScript -->
                            </div>
                            
                            <div class="text-center py-5" id="empty-cart-message">
                                <div class="empty-cart-icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <h4 class="text-muted mb-3">Tu carrito está vacío</h4>
                                <p class="text-muted mb-4">Agrega algunos productos para continuar</p>
                                <a href="catalogo.php" class="btn btn-bloom">
                                    <i class="fas fa-store me-2"></i>Ir a Comprar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Resumen del Pedido -->
                <div class="bloom-card mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--accent), var(--secondary)); color: var(--primary); border: none; padding: 15px 20px; font-weight: 600;">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Resumen del Pedido</h5>
                    </div>
                    <div class="card-body">
                        <div id="order-summary">
                            <div class="order-summary-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="summary-label">Subtotal:</span>
                                    <div class="text-end">
                                        <div class="summary-value" id="subtotal">GS. 0</div>
                                        <div class="summary-usd-value" id="subtotal-usd">USD 0.00</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-summary-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="summary-label">Envío:</span>
                                    <span class="text-success fw-bold">Gratis</span>
                                </div>
                            </div>
                            
                            <div class="section-divider"></div>
                            
                            <div class="order-summary-total">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong class="summary-label">Total:</strong>
                                    <div class="text-end">
                                        <div class="total-amount" id="total">GS. 0</div>
                                        <div class="total-usd-amount" id="total-usd">USD 0.00</div>
                                    </div>
                                </div>
                                <div class="summary-usd-value text-center" id="total-usd-label">Total en USD: 0.00</div>
                            </div>
                            
                            <button class="btn btn-whatsapp w-100 mb-3" id="checkout-btn" disabled>
                                <i class="fab fa-whatsapp me-2"></i>Completar Pedido por WhatsApp
                            </button>
                            <a href="catalogo.php" class="btn btn-outline-bloom w-100">
                                <i class="fas fa-arrow-left me-2"></i>Seguir Comprando
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Información de contacto SEPARADA -->
                <div class="contact-card bloom-card">
                    <div class="card-body">
                        <h6 class="mb-3" style="color: var(--accent);"><i class="fas fa-headset me-2"></i>¿Necesitas ayuda?</h6>
                        <p class="small mb-3" style="color: var(--text-light);">
                            <i class="fas fa-phone me-1"></i><?php echo $telefono_empresa; ?><br>
                            <i class="fas fa-phone me-1"></i>+595981934464<br>
                            <i class="fas fa-clock me-1"></i><?php echo $horario_empresa; ?>
                        </p>
                        <a href="https://wa.me/<?php echo str_replace('+', '', $telefono_empresa); ?>" target="_blank" class="btn btn-whatsapp btn-sm w-100">
                            <i class="fab fa-whatsapp me-1"></i>Contactar por WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para datos del cliente -->
    <div class="modal fade" id="clienteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Un último paso
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Para contactarte sobre tu pedido:</p>
                    
                    <div class="mb-3">
                        <label for="cliente_nombre" class="form-label">¿Cómo te llamas? *</label>
                        <input type="text" class="form-control" id="cliente_nombre" 
                               placeholder="Tu nombre completo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cliente_telefono" class="form-label">Tu número de WhatsApp *</label>
                        <input type="tel" class="form-control" id="cliente_telefono" 
                               placeholder="<?php echo $telefono_empresa; ?>" required>
                        <small class="text-muted">Ej: 0972366265, 0985123456</small>
                    </div>
                    
                    <div class="alert" style="background: rgba(232, 232, 232, 0.1); border: 1px solid rgba(232, 232, 232, 0.3); color: var(--text-light);">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Te contactaremos por WhatsApp para confirmar disponibilidad y coordinar el pago.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-bloom" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-whatsapp" id="modal-confirm-btn">
                        <i class="fab fa-whatsapp me-2"></i>Enviar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    let cart = JSON.parse(localStorage.getItem('bloom_cart')) || [];
    const tipoCambioVenta = <?php echo $tipo_cambio['venta']; ?>;
    const telefonoEmpresa = '<?php echo str_replace('+', '', $telefono_empresa); ?>';
    
    // Función para calcular precio en USD
    function calcularPrecioUSD(precioGs) {
        return precioGs / tipoCambioVenta;
    }
    
    // Función para formatear precio en USD
    function formatearPrecioUSD(precio) {
        return 'USD ' + precio.toFixed(2);
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
    
    // Función para actualizar el carrito en tiempo real
    function updateCartInRealTime() {
        updateCartDisplay();
        updateCartCount();
        updateClearCartButton();
    }
    
    function updateCartDisplay() {
        const cartItems = document.getElementById('cart-items');
        const emptyCart = document.getElementById('empty-cart-message');
        const checkoutBtn = document.getElementById('checkout-btn');
        
        if (cart.length === 0) {
            cartItems.innerHTML = '';
            emptyCart.style.display = 'block';
            checkoutBtn.disabled = true;
            
            // RESETEAR LOS PRECIOS A CERO cuando el carrito está vacío
            document.getElementById('subtotal').textContent = 'GS. 0';
            document.getElementById('subtotal-usd').textContent = formatearPrecioUSD(0);
            document.getElementById('total').textContent = 'GS. 0';
            document.getElementById('total-usd').textContent = formatearPrecioUSD(0);
            document.getElementById('total-usd-label').textContent = 'Total en USD: 0.00';
            return;
        }
        
        emptyCart.style.display = 'none';
        checkoutBtn.disabled = false;
        
        let html = '';
        let subtotal = 0;
        
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            const itemTotalUSD = calcularPrecioUSD(itemTotal);
            subtotal += itemTotal;
            
            html += `
                <div class="cart-item row align-items-center" id="cart-item-${index}">
                    <div class="col-3 col-md-2">
                        <div class="product-thumb-container">
                            ${item.image ? 
                                `<img src="../uploads/products/${item.image}" class="product-thumb" alt="${item.name}">` :
                                `<i class="fas fa-spa text-accent"></i>`
                            }
                        </div>
                    </div>
                    <div class="col-5 col-md-4">
                        <h6 class="product-title mb-1">${item.name}</h6>
                        <p class="product-price mb-0">GS. ${item.price.toLocaleString()}</p>
                        <p class="product-price-usd mb-0">${formatearPrecioUSD(calcularPrecioUSD(item.price))}</p>
                    </div>
                    <div class="col-4 col-md-4">
                        <div class="input-group input-group-sm">
                            <button class="btn btn-quantity minus-btn" type="button" data-index="${index}">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="form-control quantity-input" 
                                   value="${item.quantity}" min="1" data-index="${index}">
                            <button class="btn btn-quantity plus-btn" type="button" data-index="${index}">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-2 text-start text-md-end mt-2 mt-md-0">
                        <strong class="product-price d-block">GS. ${itemTotal.toLocaleString()}</strong>
                        <div class="product-price-usd">${formatearPrecioUSD(itemTotalUSD)}</div>
                        <button class="btn btn-sm btn-danger-bloom mt-1 remove-item" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        cartItems.innerHTML = html;
        
        const subtotalUSD = calcularPrecioUSD(subtotal);
        const totalUSD = calcularPrecioUSD(subtotal); // Envío gratis
        
        document.getElementById('subtotal').textContent = 'GS. ' + subtotal.toLocaleString();
        document.getElementById('subtotal-usd').textContent = formatearPrecioUSD(subtotalUSD);
        document.getElementById('total').textContent = 'GS. ' + subtotal.toLocaleString();
        document.getElementById('total-usd').textContent = formatearPrecioUSD(totalUSD);
        document.getElementById('total-usd-label').textContent = 'Total en USD: ' + totalUSD.toFixed(2);
    }
    
    function updateCartCount() {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        document.getElementById('cart-count').textContent = totalItems;
        document.getElementById('cart-count-desktop').textContent = totalItems;
    }
    
    function updateClearCartButton() {
        const clearCartBtn = document.getElementById('clear-cart-btn');
        if (cart.length === 0) {
            clearCartBtn.style.display = 'none';
        } else {
            clearCartBtn.style.display = 'block';
        }
    }
    
    function saveCart() {
        localStorage.setItem('bloom_cart', JSON.stringify(cart));
    }
    
    function removeItemWithAnimation(index) {
        const itemElement = document.getElementById(`cart-item-${index}`);
        if (itemElement) {
            itemElement.classList.add('removing');
            
            setTimeout(() => {
                cart.splice(index, 1);
                saveCart();
                updateCartInRealTime();
                showToast('Producto eliminado del carrito', 'danger');
            }, 300);
        } else {
            // Si no encuentra el elemento, actualizar igual
            cart.splice(index, 1);
            saveCart();
            updateCartInRealTime();
            showToast('Producto eliminado del carrito', 'danger');
        }
    }
    
    // Función para actualizar cantidad
    function updateQuantity(index, newQuantity) {
        if (newQuantity > 0) {
            cart[index].quantity = newQuantity;
            saveCart();
            updateCartInRealTime();
            showToast('Cantidad actualizada', 'success');
        }
    }

    // Función para limpiar y validar teléfono
    function formatearTelefono(telefono) {
        return telefono.replace(/\D/g, '');
    }

    // Función para validar teléfono paraguayo
    function validarTelefono(telefono) {
        const telefonoLimpio = telefono.replace(/\D/g, '');
        
        const patrones = [
            /^09[1-9]\d{6}$/,      // 0972366265
            /^9[1-9]\d{6}$/,       // 972366265
            /^5959[1-9]\d{6}$/,    // 595972366265
            /^09[1-9]\d{7}$/       // 09851234567
        ];
        
        return patrones.some(patron => patron.test(telefonoLimpio));
    }

    // Función para guardar pedido en BD
    async function guardarPedidoEnBD(nombre, telefono) {
        const checkoutBtn = document.getElementById('checkout-btn');
        const originalText = checkoutBtn.innerHTML;
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
        
        try {
            // Convertir nombre a mayúsculas y limpiar teléfono
            const nombreMayusculas = nombre.toUpperCase();
            const telefonoLimpio = formatearTelefono(telefono);
            
            // Preparar productos
            const productosParaBD = cart.map(item => ({
                id: item.id,
                name: item.name,
                price: item.price,
                quantity: item.quantity,
                image: item.image || ''
            }));
            
            const response = await fetch('../includes/save_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cliente_nombre: nombreMayusculas,
                    cliente_telefono: telefonoLimpio,
                    productos: productosParaBD,
                    total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.pedido_id) {
                // Mensaje ultra compacto para WhatsApp
                let message = `PEDIDO num ${result.pedido_id}%0A${nombreMayusculas}%0A%0A`;
                
                cart.forEach(item => {
                    message += `${item.quantity}x ${item.name}%0A`;
                });
                
                message += `%0ACONFIRMAR DISPONIBILIDAD`;
                
                // Abrir WhatsApp con el teléfono de la empresa
                const whatsappUrl = `https://wa.me/${telefonoEmpresa}?text=${message}`;
                window.open(whatsappUrl, '_blank');
                
                // Limpiar carrito después de enviar
                setTimeout(() => {
                    cart = [];
                    saveCart();
                    updateCartInRealTime();
                    showToast('¡Pedido #' + result.pedido_id + ' enviado correctamente!', 'success');
                    
                    // Restaurar botón
                    checkoutBtn.disabled = false;
                    checkoutBtn.innerHTML = originalText;
                }, 2000);
                
            } else {
                const errorMsg = result.error || 'No se pudo obtener el ID del pedido';
                showToast('Error: ' + errorMsg, 'danger');
                checkoutBtn.disabled = false;
                checkoutBtn.innerHTML = originalText;
            }
            
        } catch (error) {
            showToast('Error de conexión. Intenta nuevamente.', 'danger');
            checkoutBtn.disabled = false;
            checkoutBtn.innerHTML = originalText;
        }
    }

    // Función para mostrar modal y capturar datos
    function mostrarModalCliente() {
        const modal = new bootstrap.Modal(document.getElementById('clienteModal'));
        modal.show();
        
        // Limpiar formulario
        document.getElementById('cliente_nombre').value = '';
        document.getElementById('cliente_telefono').value = '';
    }
    
    // Event Listeners
    document.addEventListener('click', function(e) {
        // Botón menos
        if (e.target.closest('.minus-btn')) {
            const btn = e.target.closest('.minus-btn');
            const index = parseInt(btn.dataset.index);
            if (cart[index].quantity > 1) {
                const newQuantity = cart[index].quantity - 1;
                updateQuantity(index, newQuantity);
            }
        }
        
        // Botón más
        if (e.target.closest('.plus-btn')) {
            const btn = e.target.closest('.plus-btn');
            const index = parseInt(btn.dataset.index);
            const newQuantity = cart[index].quantity + 1;
            updateQuantity(index, newQuantity);
        }
        
        // Eliminar producto
        if (e.target.closest('.remove-item')) {
            const btn = e.target.closest('.remove-item');
            const index = parseInt(btn.dataset.index);
            removeItemWithAnimation(index);
        }
        
        // Vaciar carrito
        if (e.target.closest('#clear-cart-btn')) {
            if (cart.length === 0) return;
            
            if (confirm('¿Estás seguro de que quieres vaciar todo el carrito?')) {
                const cartItems = document.querySelectorAll('.cart-item');
                
                // Animar la eliminación de cada item
                cartItems.forEach((item, index) => {
                    setTimeout(() => {
                        item.classList.add('removing');
                    }, index * 100);
                });
                
                // Limpiar después de la animación
                setTimeout(() => {
                    cart = [];
                    saveCart();
                    updateCartInRealTime();
                    showToast('Carrito vaciado correctamente', 'danger');
                }, cartItems.length * 100 + 300);
                
                // Forzar actualización inmediata como respaldo
                setTimeout(() => {
                    updateCartInRealTime();
                }, cartItems.length * 100 + 500);
            }
        }
        
        // Completar pedido
        if (e.target.closest('#checkout-btn')) {
            if (cart.length === 0) return;
            mostrarModalCliente();
        }
        
        // Confirmar pedido desde el modal
        if (e.target.closest('#modal-confirm-btn')) {
            const nombre = document.getElementById('cliente_nombre').value.trim();
            const telefono = document.getElementById('cliente_telefono').value.trim();
            
            if (!nombre) {
                showToast('Por favor ingresa tu nombre', 'danger');
                return;
            }
            
            if (!telefono) {
                showToast('Por favor ingresa tu número de WhatsApp', 'danger');
                return;
            }
            
            if (!validarTelefono(telefono)) {
                showToast('Por favor ingresa un número de WhatsApp válido', 'danger');
                return;
            }
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('clienteModal'));
            modal.hide();
            
            guardarPedidoEnBD(nombre, telefono);
        }
    });
    
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity-input')) {
            const index = parseInt(e.target.dataset.index);
            const quantity = parseInt(e.target.value);
            
            if (!isNaN(quantity) && quantity > 0) {
                updateQuantity(index, quantity);
            }
        }
    });
    
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('quantity-input')) {
            const index = parseInt(e.target.dataset.index);
            const quantity = parseInt(e.target.value);
            
            if (isNaN(quantity) || quantity < 1) {
                e.target.value = 1;
                updateQuantity(index, 1);
            }
        }
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
    
    // Inicializar
    updateCartInRealTime();
</script>
</body>
</html>