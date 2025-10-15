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
?>