<?php
include_once('../includes/functions.php');

// Verificar que está logueado y es admin
if (!isLoggedIn()) {
    redirect('login.php');
}

// Solo administradores pueden acceder a la gestión de usuarios
if ($_SESSION['user_role'] != 'admin') {
    echo "<script>alert('No tienes permisos para acceder a esta sección'); window.location.href = 'dashboard.php';</script>";
    exit;
}

// El resto del código continua igual...
include_once('../includes/database.php');
// ... resto del código
// Verificar que está logueado y es admin
if (!isLoggedIn() || $_SESSION['user_role'] != 'admin') {
    redirect('login.php');
}

include_once('../includes/database.php');
$database = new Database();
$db = $database->getConnection();

// Procesar acciones
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Crear nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'create') {
    $nombre = sanitize($_POST['nombre']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $rol = sanitize($_POST['rol']);
    
    // Verificar si el email ya existe
    $checkEmail = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $checkEmail->execute([$email]);
    
    if ($checkEmail->rowCount() > 0) {
        $error = "El email ya está registrado";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nombre, $email, $passwordHash, $rol])) {
            $success = "Usuario creado exitosamente";
            $action = 'list'; // Volver a la lista
        } else {
            $error = "Error al crear el usuario";
        }
    }
}

// Editar usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'edit') {
    $nombre = sanitize($_POST['nombre']);
    $email = sanitize($_POST['email']);
    $rol = sanitize($_POST['rol']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Si se proporciona nueva contraseña
    if (!empty($_POST['password'])) {
        $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query = "UPDATE usuarios SET nombre = ?, email = ?, password = ?, rol = ?, activo = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombre, $email, $passwordHash, $rol, $activo, $id]);
    } else {
        $query = "UPDATE usuarios SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nombre, $email, $rol, $activo, $id]);
    }
    
    $success = "Usuario actualizado exitosamente";
    $action = 'list';
}

// Eliminar usuario (solo desactivar)
if ($action == 'delete' && $id) {
    $query = "UPDATE usuarios SET activo = 0 WHERE id = ? AND id != ?"; // No desactivarse a sí mismo
    $stmt = $db->prepare($query);
    $stmt->execute([$id, $_SESSION['user_id']]);
    $success = "Usuario desactivado exitosamente";
    $action = 'list';
}

// Obtener lista de usuarios
if ($action == 'list') {
    $query = "SELECT * FROM usuarios ORDER BY fecha_creacion DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener datos de usuario para editar
if (($action == 'edit' || $action == 'view') && $id) {
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $error = "Usuario no encontrado";
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Marco Cos Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include('navbar.php'); ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <?php include('sidebar.php'); ?>

            <!-- Contenido principal -->
            <div class="col-md-9">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($action == 'list'): ?>
                    <!-- Lista de usuarios -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Gestión de Usuarios</h2>
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Usuario
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Rol</th>
                                            <th>Estado</th>
                                            <th>Fecha Creación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo $user['nombre']; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['rol'] == 'admin' ? 'danger' : 'info'; ?>">
                                                    <?php echo ucfirst($user['rol']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['activo'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $user['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($user['fecha_creacion'])); ?></td>
                                            <td>
                                                <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['id'] != $_SESSION['user_id'] && $user['activo']): ?>
                                                <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de desactivar este usuario?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($action == 'create' || $action == 'edit'): ?>
                    <!-- Formulario de crear/editar usuario -->
                    <h2><?php echo $action == 'create' ? 'Crear Nuevo Usuario' : 'Editar Usuario'; ?></h2>
                    
                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre completo</label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                                value="<?php echo isset($usuario) ? $usuario['nombre'] : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                value="<?php echo isset($usuario) ? $usuario['email'] : ''; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">
                                                Contraseña 
                                                <?php if ($action == 'edit'): ?>
                                                <small class="text-muted">(dejar en blanco para no cambiar)</small>
                                                <?php endif; ?>
                                            </label>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                <?php echo $action == 'create' ? 'required' : ''; ?>>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="rol" class="form-label">Rol</label>
                                            <select class="form-control" id="rol" name="rol" required>
                                                <option value="vendedor" <?php echo (isset($usuario) && $usuario['rol'] == 'vendedor') ? 'selected' : ''; ?>>Vendedor</option>
                                                <option value="admin" <?php echo (isset($usuario) && $usuario['rol'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($action == 'edit'): ?>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                            <?php echo (isset($usuario) && $usuario['activo']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="activo">
                                            Usuario activo
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 
                                        <?php echo $action == 'create' ? 'Crear Usuario' : 'Actualizar Usuario'; ?>
                                    </button>
                                    <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>