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
    'pedidos_pendientes' => "SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'"
];

foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Verificar si el usuario actual es administrador
$isAdmin = ($_SESSION['user_role'] == 'admin');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Marco Cos Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-gem"></i> Marco Cos Admin
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
                
                <!-- Estadísticas -->
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
                                        <?php if ($isAdmin): ?>
                                        <a href="usuarios.php" class="btn btn-outline-info btn-lg">
                                            <i class="fas fa-users"></i><br>
                                            Gestionar Usuarios
                                        </a>
                                        <?php else: ?>
                                        <a href="pedidos.php" class="btn btn-outline-info btn-lg">
                                            <i class="fas fa-shopping-cart"></i><br>
                                            Ver Pedidos
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>