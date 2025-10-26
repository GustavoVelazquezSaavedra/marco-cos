<?php
// config/config_sistema.php

function obtenerConfiguracionSistema($clave, $valor_default = '') {
    // Incluir la conexión a la base de datos si no está disponible
    if (!isset($GLOBALS['conexion'])) {
        // Intentar incluir el archivo de conexión
        $database_path = __DIR__ . '/../includes/database.php';
        if (file_exists($database_path)) {
            include_once $database_path;
        } else {
            return $valor_default;
        }
    }
    
    // Verificar si la conexión existe después de incluir el archivo
    if (!isset($GLOBALS['conexion'])) {
        return $valor_default;
    }
    
    $conexion = $GLOBALS['conexion'];
    
    $query = "SELECT valor FROM configuraciones WHERE clave = ?";
    $stmt = $conexion->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("s", $clave);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['valor'];
        }
        $stmt->close();
    }
    
    return $valor_default;
}

// Función para guardar configuración en la base de datos
function guardarConfiguracionSistema($clave, $valor, $descripcion = '') {
    if (!isset($GLOBALS['conexion'])) {
        return false;
    }
    
    $conexion = $GLOBALS['conexion'];
    
    $descripciones = [
        'titulo_sistema' => 'Título principal del sistema',
        'subtitulo_sistema' => 'Subtítulo del sistema',
        'telefono' => 'Teléfono de la empresa',
        'email' => 'Email de la empresa',
        'horario' => 'Horario de atención',
        'direccion' => 'Dirección de la empresa',
        'footer_info' => 'Información del footer'
    ];
    
    $descripcion = $descripciones[$clave] ?? $descripcion;
    
    $query = "INSERT INTO configuraciones (clave, valor, descripcion) 
              VALUES (?, ?, ?) 
              ON DUPLICATE KEY UPDATE valor = ?, descripcion = ?";
    $stmt = $conexion->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("sssss", $clave, $valor, $descripcion, $valor, $descripcion);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

// Función para obtener todas las configuraciones
function obtenerTodasConfiguracionesSistema() {
    if (!isset($GLOBALS['conexion'])) {
        return [];
    }
    
    $conexion = $GLOBALS['conexion'];
    $configs = [];
    
    $query = "SELECT clave, valor, descripcion FROM configuraciones ORDER BY clave";
    $stmt = $conexion->prepare($query);
    
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $configs[$row['clave']] = [
                'valor' => $row['valor'],
                'descripcion' => $row['descripcion']
            ];
        }
        $stmt->close();
    }
    
    return $configs;
}

// Definir variables globales con los valores de la base de datos
$titulo_sistema = obtenerConfiguracionSistema('titulo_sistema', 'Mi Sistema');
$subtitulo_sistema = obtenerConfiguracionSistema('subtitulo_sistema', 'Panel de Administración');
$telefono_empresa = obtenerConfiguracionSistema('telefono', '+595972366265');
$email_empresa = obtenerConfiguracionSistema('email', 'info@bloom.com');
$horario_empresa = obtenerConfiguracionSistema('horario', 'Lun-Vie 8:00-18:00');
$direccion_empresa = obtenerConfiguracionSistema('direccion', 'Tu dirección aquí');
$footer_info = obtenerConfiguracionSistema('footer_info', 'Sistema de Gestión');
?>