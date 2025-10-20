<?php
// admin/config_tipo_cambio.php
include_once('../includes/functions.php');

if (!isLoggedIn()) {
    redirect('login.php');
}

include_once('../includes/database.php');
$database = new Database();
$db = $database->getConnection();

// Obtener tipo de cambio actual
$query = "SELECT * FROM tipo_cambio WHERE fecha = CURDATE() AND activo = 1 ORDER BY id DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$tipo_cambio_actual = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $compra = floatval($_POST['compra']);
    $venta = floatval($_POST['venta']);
    $fecha = $_POST['fecha'];
    
    // Verificar si ya existe tipo de cambio para esta fecha
    $checkQuery = "SELECT id FROM tipo_cambio WHERE fecha = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$fecha]);
    
    if ($checkStmt->rowCount() > 0) {
        // Actualizar
        $updateQuery = "UPDATE tipo_cambio SET compra = ?, venta = ? WHERE fecha = ?";
        $updateStmt = $db->prepare($updateQuery);
        if ($updateStmt->execute([$compra, $venta, $fecha])) {
            $success = "Tipo de cambio actualizado correctamente";
        }
    } else {
        // Insertar nuevo
        $insertQuery = "INSERT INTO tipo_cambio (fecha, compra, venta) VALUES (?, ?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        if ($insertStmt->execute([$fecha, $compra, $venta])) {
            $success = "Tipo de cambio guardado correctamente";
        }
    }
    
    // Recargar datos
    $stmt->execute();
    $tipo_cambio_actual = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener historial de tipos de cambio
$historialQuery = "SELECT * FROM tipo_cambio ORDER BY fecha DESC LIMIT 10";
$historialStmt = $db->prepare($historialQuery);
$historialStmt->execute();
$historial = $historialStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Tipo de Cambio - BLOOM Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
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
                    <a href="config_tipo_cambio.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-dollar-sign"></i> Tipo de Cambio
                    </a>
                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <a href="usuarios.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="col-md-9">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Configurar Tipo de Cambio</h2>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Tipo de Cambio Actual</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($tipo_cambio_actual): ?>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-success"><?php echo number_format($tipo_cambio_actual['compra'], 3); ?></h4>
                                        <small class="text-muted">Compra (Gs. por USD)</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-danger"><?php echo number_format($tipo_cambio_actual['venta'], 3); ?></h4>
                                        <small class="text-muted">Venta (Gs. por USD)</small>
                                    </div>
                                </div>
                                <p class="text-center mt-3 mb-0">
                                    <small class="text-muted">Actualizado: <?php echo date('d/m/Y', strtotime($tipo_cambio_actual['fecha'])); ?></small>
                                </p>
                                <?php else: ?>
                                <div class="alert alert-warning text-center">
                                    <i class="fas fa-exclamation-triangle"></i><br>
                                    No hay tipo de cambio configurado para hoy
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Configurar Tipo de Cambio</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="fecha" class="form-label">Fecha</label>
                                        <input type="date" class="form-control" id="fecha" name="fecha" 
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="compra" class="form-label">Compra (Gs.)</label>
                                                <input type="number" class="form-control" id="compra" name="compra" 
                                                       step="0.001" min="0" value="<?php echo $tipo_cambio_actual ? $tipo_cambio_actual['compra'] : '6960'; ?>" required>
                                                <small class="text-muted">Gs. por USD</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="venta" class="form-label">Venta (Gs.)</label>
                                                <input type="number" class="form-control" id="venta" name="venta" 
                                                       step="0.001" min="0" value="<?php echo $tipo_cambio_actual ? $tipo_cambio_actual['venta'] : '7060'; ?>" required>
                                                <small class="text-muted">Gs. por USD</small>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Guardar Tipo de Cambio
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historial -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Historial de Tipos de Cambio</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Compra</th>
                                        <th>Venta</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($historial)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No hay historial disponible</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($historial as $item): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($item['fecha'])); ?></td>
                                        <td class="text-success"><?php echo number_format($item['compra'], 3); ?></td>
                                        <td class="text-danger"><?php echo number_format($item['venta'], 3); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $item['activo'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $item['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>