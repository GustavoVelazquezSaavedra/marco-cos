<?php
// public/image.php - Servir imágenes públicamente
error_reporting(0);
require_once '../include/database.php';

if(isset($_GET['id'])) {
    $product_id = $_GET['id'];
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT imagen, nombre FROM productos WHERE id = ?");
        $stmt->execute([$product_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($producto && !empty($producto['imagen'])) {
            // RUTA CORRECTA - las imágenes están en /marco/uploads/products/
            $imagePath = '../uploads/products/' . $producto['imagen'];
            
            if(file_exists($imagePath)) {
                $imageInfo = getimagesize($imagePath);
                header("Content-Type: " . $imageInfo['mime']);
                readfile($imagePath);
                exit;
            } else {
                // Para debugging - muestra la ruta que está buscando
                // echo "Ruta no encontrada: " . $imagePath;
            }
        }
    } catch(Exception $e) {
        // Error silencioso
    }
}

// Imagen por defecto
header("Content-Type: image/png");
$im = imagecreate(200, 200);
$bg = imagecolorallocate($im, 240, 240, 240);
$text_color = imagecolorallocate($im, 120, 120, 120);
imagestring($im, 3, 40, 90, 'Imagen No Disponible', $text_color);
imagepng($im);
imagedestroy($im);
exit;
?>