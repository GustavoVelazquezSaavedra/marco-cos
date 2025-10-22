<?php
include_once('../includes/functions.php');

// Verificar que está logueado
if (!isLoggedIn()) {
    redirect('login.php');
}

include_once('../includes/database.php');
$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas
$stats = [];
$queries = [
    'total_productos' => "SELECT COUNT(*) as total FROM productos WHERE activo = 1",
    'total_categorias' => "SELECT COUNT(*) as total FROM categorias WHERE activo = 1",
    'stock_bajo' => "SELECT COUNT(*) as total FROM productos WHERE stock < 10 AND activo = 1",
    'pedidos_pendientes' => "SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'",
    'pedidos_procesando' => "SELECT COUNT(*) as total FROM pedidos WHERE estado = 'procesado'",
    'pedidos_completados' => "SELECT COUNT(*) as total FROM pedidos WHERE estado = 'completado'"
];

foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Obtener estadísticas del slider
$querySliderStats = "SELECT 
    COUNT(*) as total_slides,
    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as slides_activos
    FROM slider_principal";
$stmtSliderStats = $db->prepare($querySliderStats);
$stmtSliderStats->execute();
$sliderStats = $stmtSliderStats->fetch(PDO::FETCH_ASSOC);

// Obtener ventas del día
$queryVentasHoy = "SELECT SUM(total) as total_ventas FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()";
$stmtVentasHoy = $db->prepare($queryVentasHoy);
$stmtVentasHoy->execute();
$ventasHoy = $stmtVentasHoy->fetch(PDO::FETCH_ASSOC);
$stats['total_ventas'] = $ventasHoy['total_ventas'] ?: 0;

// Verificar si el usuario actual es administrador
$isAdmin = ($_SESSION['user_role'] == 'admin');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BLOOM Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
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
                    <span class="badge bg-<?php echo $isAdmin ? 'danger' : 'info'; ?>">
                        <?php echo $isAdmin ? 'Administrador' : 'Vendedor'; ?>
                    </span>
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
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
                    <a href="presupuestos.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-invoice-dollar"></i> Presupuestos
                    </a>
                    <a href="config_tipo_cambio.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-dollar-sign"></i> Tipo de Cambio
                    </a>
                    <a href="slider.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-images"></i> Slider Principal
                    </a>
                    <?php if ($isAdmin): ?>
                    <a href="usuarios.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="col-md-9">
                <h2>Dashboard</h2>
                
                <!-- Estadísticas Principales -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_productos']; ?></h4>
                                        <p>Productos</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-box fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_categorias']; ?></h4>
                                        <p>Categorías</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-tags fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['stock_bajo']; ?></h4>
                                        <p>Stock Bajo</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['pedidos_pendientes']; ?></h4>
                                        <p>Pedidos Pendientes</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-shopping-cart fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Adicionales -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-secondary">
                            <div class="card-body text-center">
                                <div class="h4"><?php echo $sliderStats['total_slides']; ?></div>
                                <div>Total Slides</div>
                                <small><?php echo $sliderStats['slides_activos']; ?> activos</small>
                            </div>
                            <div class="card-footer text-center p-2">
                                <a href="slider.php" class="text-white text-decoration-none">
                                    <i class="fas fa-cog me-1"></i>Gestionar Slider
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- NUEVAS CARDS DE PEDIDOS -->
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <div class="h4"><?php echo $stats['pedidos_pendientes']; ?></div>
                                <div>Pendientes</div>
                                <small>Por contactar</small>
                            </div>
                            <div class="card-footer text-center p-2">
                                <a href="pedidos.php?estado=pendiente" class="text-white text-decoration-none">
                                    <i class="fas fa-eye me-1"></i>Ver Pedidos
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <div class="h4"><?php echo $stats['pedidos_procesando']; ?></div>
                                <div>Procesando</div>
                                <small>En preparación</small>
                            </div>
                            <div class="card-footer text-center p-2">
                                <a href="pedidos.php?estado=procesado" class="text-white text-decoration-none">
                                    <i class="fas fa-cog me-1"></i>Gestionar
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <div class="h4"><?php echo $stats['pedidos_completados']; ?></div>
                                <div>Completados</div>
                                <small>Ventas finalizadas</small>
                            </div>
                            <div class="card-footer text-center p-2">
                                <a href="pedidos.php?estado=completado" class="text-white text-decoration-none">
                                    <i class="fas fa-check me-1"></i>Ver Completados
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones rápidas -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <a href="productos.php?action=add" class="btn btn-outline-primary btn-lg">
                                            <i class="fas fa-plus"></i><br>
                                            Nuevo Producto
                                        </a>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <a href="inventario.php?action=entrada" class="btn btn-outline-success btn-lg">
                                            <i class="fas fa-arrow-down"></i><br>
                                            Entrada Stock
                                        </a>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <a href="inventario.php?action=salida" class="btn btn-outline-warning btn-lg">
                                            <i class="fas fa-arrow-up"></i><br>
                                            Salida Stock
                                        </a>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <a href="presupuestos.php?action=create" class="btn btn-outline-info btn-lg">
                                            <i class="fas fa-file-invoice-dollar"></i><br>
                                            Nuevo Presupuesto
                                        </a>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <a href="slider.php" class="btn btn-outline-info btn-lg">
                                            <i class="fas fa-images"></i><br>
                                            Gestionar Slider
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Segunda fila de acciones -->
                                <div class="row mt-3">
                                    <div class="col-md-4 text-center">
                                        <a href="pedidos.php" class="btn btn-outline-secondary btn-lg">
                                            <i class="fas fa-shopping-cart"></i><br>
                                            Ver Pedidos
                                        </a>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <a href="categorias.php" class="btn btn-outline-dark btn-lg">
                                            <i class="fas fa-tags"></i><br>
                                            Categorías
                                        </a>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <?php if ($isAdmin): ?>
                                        <a href="usuarios.php" class="btn btn-outline-danger btn-lg">
                                            <i class="fas fa-users"></i><br>
                                            Usuarios
                                        </a>
                                        <?php else: ?>
                                        <a href="productos.php" class="btn btn-outline-primary btn-lg">
                                            <i class="fas fa-list"></i><br>
                                            Lista Productos
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del usuario -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Información de la Sesión</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Usuario:</strong> <?php echo $_SESSION['user_name']; ?></p>
                                        <p><strong>Email:</strong> <?php 
                                            // Obtener email del usuario actual
                                            $query = "SELECT email FROM usuarios WHERE id = ?";
                                            $stmt = $db->prepare($query);
                                            $stmt->execute([$_SESSION['user_id']]);
                                            $user_email = $stmt->fetch(PDO::FETCH_ASSOC)['email'];
                                            echo $user_email;
                                        ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Rol:</strong> 
                                            <span class="badge bg-<?php echo $isAdmin ? 'danger' : 'info'; ?>">
                                                <?php echo $isAdmin ? 'Administrador' : 'Vendedor'; ?>
                                            </span>
                                        </p>
                                        <p><strong>Permisos:</strong> 
                                            <?php echo $isAdmin ? 'Acceso completo al sistema' : 'Gestión de productos, inventario y pedidos'; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimos pedidos -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Últimos Pedidos</h5>
                                <a href="pedidos.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                            </div>
                            <div class="card-body">
                                <?php
                                $queryUltimosPedidos = "SELECT * FROM pedidos ORDER BY fecha_pedido DESC LIMIT 5";
                                $stmtUltimosPedidos = $db->prepare($queryUltimosPedidos);
                                $stmtUltimosPedidos->execute();
                                $ultimosPedidos = $stmtUltimosPedidos->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                
                                <?php if (empty($ultimosPedidos)): ?>
                                <p class="text-muted text-center mb-0">No hay pedidos recientes</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Cliente</th>
                                                <th>Total</th>
                                                <th>Estado</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimosPedidos as $pedido): ?>
                                            <tr>
                                                <td>#<?php echo $pedido['id']; ?></td>
                                                <td><?php echo $pedido['cliente_nombre']; ?></td>
                                                <td>Gs. <?php echo number_format($pedido['total'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch($pedido['estado']) {
                                                            case 'pendiente': echo 'warning'; break;
                                                            case 'procesado': echo 'info'; break;
                                                            case 'completado': echo 'success'; break;
                                                            case 'cancelado': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($pedido['estado']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>