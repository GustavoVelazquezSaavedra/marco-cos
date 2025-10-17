<?php
// includes/save_order.php
include_once('database.php');
include_once('functions.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Obtener datos del cliente (con valores por defecto)
    $cliente_nombre = !empty($input['cliente_nombre']) ? sanitize($input['cliente_nombre']) : 'Cliente por Contactar';
    $cliente_telefono = !empty($input['cliente_telefono']) ? sanitize($input['cliente_telefono']) : '000000000';
    $cliente_email = '';
    $productos = $input['productos'] ?? [];
    $total = $input['total'] ?? 0;
    
    // Validar y preparar productos
    $productos_validados = [];
    foreach ($productos as $producto) {
        $productos_validados[] = [
            'id' => $producto['id'] ?? 0,
            'name' => $producto['name'] ?? 'Producto sin nombre',
            'price' => floatval($producto['price'] ?? 0),
            'quantity' => intval($producto['quantity'] ?? 1),
            'image' => $producto['image'] ?? '',
            'subtotal' => floatval(($producto['price'] ?? 0) * ($producto['quantity'] ?? 1))
        ];
    }
    
    // Convertir productos a JSON
    $productos_json = json_encode($productos_validados);
    
    try {
        $query = "INSERT INTO pedidos 
                  (cliente_nombre, cliente_telefono, cliente_email, productos, total, estado, fecha_pedido) 
                  VALUES 
                  (:cliente_nombre, :cliente_telefono, :cliente_email, :productos, :total, 'pendiente', NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":cliente_nombre", $cliente_nombre);
        $stmt->bindParam(":cliente_telefono", $cliente_telefono);
        $stmt->bindParam(":cliente_email", $cliente_email);
        $stmt->bindParam(":productos", $productos_json);
        $stmt->bindParam(":total", $total);
        
        if ($stmt->execute()) {
            $pedido_id = $db->lastInsertId();
            
            // DEBUG: Registrar en logs
            error_log("Pedido creado: ID $pedido_id, Cliente: $cliente_nombre, Tel: $cliente_telefono");
            
            echo json_encode([
                'success' => true, 
                'pedido_id' => $pedido_id,
                'message' => 'Pedido guardado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => 'No se pudo guardar el pedido en la base de datos'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Error save_order: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Error de base de datos: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Método no permitido'
    ]);
}
?>