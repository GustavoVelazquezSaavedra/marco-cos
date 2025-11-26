<?php
// Agregar session_start() aquí para que esté disponible en todos lados
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para redireccionar
function redirect($url) {
    header("Location: " . $url);
    exit;
}
// Agrega estas funciones al final de functions.php

/**
 * Obtener tipo de cambio actual
 */
function getTipoCambioActual() {
    global $db;
    $query = "SELECT * FROM tipo_cambio WHERE fecha = CURDATE() AND activo = 1 ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Convertir guaraníes a dólares
 */
function gsToUsd($gs, $tipo_cambio = null) {
    if (!$tipo_cambio) {
        $tipo_cambio = getTipoCambioActual();
    }
    if (!$tipo_cambio) {
        return 0;
    }
    return $gs / $tipo_cambio['venta'];
}

/**
 * Convertir dólares a guaraníes
 */
function usdToGs($usd, $tipo_cambio = null) {
    if (!$tipo_cambio) {
        $tipo_cambio = getTipoCambioActual();
    }
    if (!$tipo_cambio) {
        return 0;
    }
    return $usd * $tipo_cambio['venta'];
}

/**
 * Formatear precio en ambas monedas
 */
function formatPrecioDual($precio_gs) {
    $tipo_cambio = getTipoCambioActual();
    $precio_usd = gsToUsd($precio_gs, $tipo_cambio);
    
    return [
        'gs' => 'Gs. ' . number_format($precio_gs, 0, ',', '.'),
        'usd' => '$ ' . number_format($precio_usd, 2, '.', ',')
    ];
}

// Función para sanitizar datos
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Función para subir imágenes
function uploadImage($file, $folder = "products") {
    $target_dir = "../uploads/" . $folder . "/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $filename = uniqid() . "." . $imageFileType;
    $target_file = $target_dir . $filename;
    
    // Validar que es una imagen
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ["success" => false, "message" => "El archivo no es una imagen."];
    }
    
    // Validar tamaño (max 2MB)
    if ($file["size"] > 2000000) {
        return ["success" => false, "message" => "La imagen es muy grande."];
    }
    
    // Validar formato
    if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
        return ["success" => false, "message" => "Solo JPG, JPEG, PNG & GIF permitidos."];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "filename" => $filename];
    } else {
        return ["success" => false, "message" => "Error al subir la imagen."];
    }
}

/**
 * Obtener redes sociales activas
 */
function getRedesSociales() {
    global $db;
    $query = "SELECT * FROM redes_sociales WHERE activo = 1 ORDER BY orden";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>