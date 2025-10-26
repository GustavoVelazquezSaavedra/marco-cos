<?php
// admin/config_titulos.php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include 'navbar.php';
include 'sidebar.php';

// Incluir el archivo de configuración del sistema
include '../config/config_sistema.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar todas las configuraciones
    $configs = [
        'titulo_sistema' => $_POST['titulo_sistema'] ?? '',
        'subtitulo_sistema' => $_POST['subtitulo_sistema'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'email' => $_POST['email'] ?? '',
        'horario' => $_POST['horario'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'footer_info' => $_POST['footer_info'] ?? ''
    ];
    
    $guardados = 0;
    $errores = [];
    
    foreach ($configs as $clave => $valor) {
        if (!empty($valor)) {
            if (guardarConfiguracionSistema($clave, $valor)) {
                $guardados++;
            } else {
                $errores[] = $clave;
            }
        }
    }
    
    if ($guardados > 0 && empty($errores)) {
        $_SESSION['mensaje'] = 'Configuración guardada correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else if (!empty($errores)) {
        $_SESSION['mensaje'] = 'Error al guardar algunas configuraciones: ' . implode(', ', $errores);
        $_SESSION['tipo_mensaje'] = 'danger';
    } else {
        $_SESSION['mensaje'] = 'No se guardó ninguna configuración';
        $_SESSION['tipo_mensaje'] = 'warning';
    }
    
    // Recargar la página para mostrar los cambios
    header("Location: config_titulos.php");
    exit;
}

// Obtener todas las configuraciones actuales
$configuraciones_actuales = obtenerTodasConfiguracionesSistema();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - <?php echo $titulo_sistema; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Configuración del Sistema</h1>
                </div>

                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] ?? 'success'; ?> alert-dismissible fade show">
                        <?php echo $_SESSION['mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php 
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['tipo_mensaje']);
                    ?>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog me-2"></i>Configuración General del Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="titulo_sistema" class="form-label">
                                            <strong>Título del Sistema</strong>
                                        </label>
                                        <input type="text" class="form-control" id="titulo_sistema" name="titulo_sistema" 
                                               value="<?php echo htmlspecialchars($titulo_sistema); ?>" required>
                                        <div class="form-text">Título principal que aparece en todo el sistema</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="subtitulo_sistema" class="form-label">
                                            <strong>Subtítulo del Sistema</strong>
                                        </label>
                                        <input type="text" class="form-control" id="subtitulo_sistema" name="subtitulo_sistema" 
                                               value="<?php echo htmlspecialchars($subtitulo_sistema); ?>" required>
                                        <div class="form-text">Subtítulo o descripción breve del sistema</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="telefono" class="form-label">
                                            <strong>Teléfono de Contacto</strong>
                                        </label>
                                        <input type="text" class="form-control" id="telefono" name="telefono" 
                                               value="<?php echo htmlspecialchars($telefono_empresa); ?>" required>
                                        <div class="form-text">Número de teléfono para contacto y WhatsApp</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <strong>Email de Contacto</strong>
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($email_empresa); ?>" required>
                                        <div class="form-text">Dirección de email para contacto</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="horario" class="form-label">
                                            <strong>Horario de Atención</strong>
                                        </label>
                                        <input type="text" class="form-control" id="horario" name="horario" 
                                               value="<?php echo htmlspecialchars($horario_empresa); ?>" required>
                                        <div class="form-text">Ej: Lun-Vie 8:00-18:00, Sab 8:00-12:00</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="direccion" class="form-label">
                                            <strong>Dirección</strong>
                                        </label>
                                        <input type="text" class="form-control" id="direccion" name="direccion" 
                                               value="<?php echo htmlspecialchars($direccion_empresa); ?>" required>
                                        <div class="form-text">Dirección física de la empresa</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="footer_info" class="form-label">
                                            <strong>Información del Footer</strong>
                                        </label>
                                        <input type="text" class="form-control" id="footer_info" name="footer_info" 
                                               value="<?php echo htmlspecialchars($footer_info); ?>" required>
                                        <div class="form-text">Texto que aparece en el pie de página</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar Configuración
                                    </button>
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información de configuración actual -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Configuración Actual
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Configuración</th>
                                        <th>Valor Actual</th>
                                        <th>Descripción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($configuraciones_actuales as $clave => $config): ?>
                                    <tr>
                                        <td><strong><?php echo ucfirst(str_replace('_', ' ', $clave)); ?></strong></td>
                                        <td><?php echo htmlspecialchars($config['valor']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($config['descripcion']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>