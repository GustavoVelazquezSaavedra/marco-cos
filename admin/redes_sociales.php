<?php
include_once('../includes/functions.php');

if (!isLoggedIn()) {
    redirect('login.php');
}

include_once('../includes/database.php');
$database = new Database();
$db = $database->getConnection();

// Procesar formulario
if ($_POST) {
    if (isset($_POST['guardar_redes'])) {
        foreach ($_POST['redes'] as $id => $datos) {
            $query = "UPDATE redes_sociales SET nombre = ?, url = ?, icono = ?, orden = ?, activo = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $activo = isset($datos['activo']) ? 1 : 0;
            $stmt->execute([
                $datos['nombre'],
                $datos['url'],
                $datos['icono'],
                $datos['orden'],
                $activo,
                $id
            ]);
        }
        $_SESSION['success_message'] = "Redes sociales actualizadas correctamente";
    }
    
    if (isset($_POST['agregar_red'])) {
        $query = "INSERT INTO redes_sociales (nombre, url, icono, orden, activo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $_POST['nueva_red']['nombre'],
            $_POST['nueva_red']['url'],
            $_POST['nueva_red']['icono'],
            $_POST['nueva_red']['orden'],
            1
        ]);
        $_SESSION['success_message'] = "Red social agregada correctamente";
    }
}

// Obtener redes sociales
$query = "SELECT * FROM redes_sociales ORDER BY orden";
$stmt = $db->prepare($query);
$stmt->execute();
$redes_sociales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Redes Sociales - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once('navbar.php'); ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <?php include_once('sidebar.php'); ?>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Gestión de Redes Sociales</h2>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Configurar Redes Sociales</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>URL</th>
                                            <th>Icono</th>
                                            <th>Orden</th>
                                            <th>Activo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($redes_sociales as $red): ?>
                                        <tr>
                                            <td>
                                                <input type="text" name="redes[<?php echo $red['id']; ?>][nombre]" 
                                                       value="<?php echo htmlspecialchars($red['nombre']); ?>" 
                                                       class="form-control form-control-sm" required>
                                            </td>
                                            <td>
                                                <input type="url" name="redes[<?php echo $red['id']; ?>][url]" 
                                                       value="<?php echo htmlspecialchars($red['url']); ?>" 
                                                       class="form-control form-control-sm" required>
                                            </td>
                                            <td>
                                                <input type="text" name="redes[<?php echo $red['id']; ?>][icono]" 
                                                       value="<?php echo htmlspecialchars($red['icono']); ?>" 
                                                       class="form-control form-control-sm" required>
                                                <small class="text-muted">Ej: fab fa-facebook-f</small>
                                            </td>
                                            <td>
                                                <input type="number" name="redes[<?php echo $red['id']; ?>][orden]" 
                                                       value="<?php echo $red['orden']; ?>" 
                                                       class="form-control form-control-sm" min="0">
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="redes[<?php echo $red['id']; ?>][activo]" 
                                                           value="1" <?php echo $red['activo'] ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" name="guardar_redes" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Agregar nueva red social -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Agregar Nueva Red Social</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Nombre</label>
                                    <input type="text" name="nueva_red[nombre]" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label>URL</label>
                                    <input type="url" name="nueva_red[url]" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Icono (FontAwesome)</label>
                                    <input type="text" name="nueva_red[icono]" class="form-control" required>
                                    <small class="text-muted">Ej: fab fa-twitter</small>
                                </div>
                                <div class="col-md-2">
                                    <label>Orden</label>
                                    <input type="number" name="nueva_red[orden]" class="form-control" min="0" value="0">
                                </div>
                            </div>
                            <button type="submit" name="agregar_red" class="btn btn-success mt-3">
                                <i class="fas fa-plus"></i> Agregar Red Social
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>