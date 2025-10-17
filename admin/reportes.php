<?php
include_once('../includes/functions.php');

if (!isLoggedIn()) {
    redirect('login.php');
}

include_once('../includes/database.php');
$database = new Database();
$db = $database->getConnection();

// Incluir librería PDF
require_once('../includes/tcpdf/tcpdf.php');

$action = isset($_GET['action']) ? $_GET['action'] : 'menu';

// Generar reporte de inventario PDF
if ($action == 'inventario_pdf') {
    // Consulta para obtener inventario
    $query = "SELECT p.*, c.nombre as categoria_nombre 
              FROM productos p 
              LEFT JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.activo = 1 
              ORDER BY c.nombre, p.nombre";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Marco Cos');
    $pdf->SetAuthor('Marco Cos');
    $pdf->SetTitle('Reporte de Inventario');
    $pdf->AddPage();
    
    // Contenido del PDF
    $html = '
    <h1>Marco Cos - Reporte de Inventario</h1>
    <p>Fecha: ' . date('d/m/Y H:i') . '</p>
    <table border="1" cellpadding="5">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($productos as $producto) {
        $estado = $producto['stock'] > 10 ? 'Óptimo' : ($producto['stock'] > 0 ? 'Bajo' : 'Agotado');
        $html .= '
            <tr>
                <td>' . $producto['codigo'] . '</td>
                <td>' . $producto['nombre'] . '</td>
                <td>' . $producto['categoria_nombre'] . '</td>
                <td>Gs. ' . number_format($producto['precio_publico'], 0, ',', '.') . '</td>
                <td>' . $producto['stock'] . '</td>
                <td>' . $estado . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('inventario_marco_cos.pdf', 'I');
    exit;
}

// Generar ticket de venta PDF
if ($action == 'ticket_venta' && isset($_GET['pedido_id'])) {
    $pedido_id = $_GET['pedido_id'];
    
    $query = "SELECT * FROM pedidos WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pedido) {
        $productos = json_decode($pedido['productos'], true);
        
        $pdf = new TCPDF('P', 'mm', array(80, 200), true, 'UTF-8');
        $pdf->SetMargins(5, 5, 5);
        $pdf->AddPage();
        
        $html = '
        <div style="text-align: center;">
            <h3>Marco Cos</h3>
            <p>Joyería y Accesorios</p>
            <p>Ticket de Venta #' . $pedido_id . '</p>
        </div>
        <hr>
        <p><strong>Fecha:</strong> ' . date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) . '</p>
        <p><strong>Cliente:</strong> ' . $pedido['cliente_nombre'] . '</p>
        <hr>
        <table width="100%">
            <thead>
                <tr>
                    <th align="left">Producto</th>
                    <th align="right">Cant</th>
                    <th align="right">Total</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($productos as $producto) {
            $html .= '
                <tr>
                    <td>' . $producto['name'] . '</td>
                    <td align="right">' . $producto['quantity'] . '</td>
                    <td align="right">Gs. ' . number_format($producto['price'] * $producto['quantity'], 0, ',', '.') . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" align="right"><strong>TOTAL:</strong></td>
                    <td align="right"><strong>Gs. ' . number_format($pedido['total'], 0, ',', '.') . '</strong></td>
                </tr>
            </tfoot>
        </table>
        <hr>
        <p style="text-align: center;">¡Gracias por su compra!</p>
        <p style="text-align: center;">Marco Cos - Joyería de Calidad</p>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('ticket_' . $pedido_id . '.pdf', 'I');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Marco Cos Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include('navbar.php'); ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <?php include('sidebar.php'); ?>

            <div class="col-md-9">
                <h2>Reportes y Documentos</h2>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-boxes fa-3x text-primary mb-3"></i>
                                <h5>Reporte de Inventario</h5>
                                <p>Lista completa de productos con stock</p>
                                <a href="?action=inventario_pdf" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-file-pdf"></i> Generar PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-receipt fa-3x text-success mb-3"></i>
                                <h5>Tickets de Venta</h5>
                                <p>Generar ticket para pedidos</p>
                                <form method="GET" class="mt-3">
                                    <input type="hidden" name="action" value="ticket_venta">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="pedido_id" placeholder="ID Pedido" required>
                                        <button class="btn btn-success" type="submit">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                                <h5>Reporte de Ventas</h5>
                                <p>Estadísticas y análisis de ventas</p>
                                <a href="?action=ventas_pdf" class="btn btn-info">
                                    <i class="fas fa-chart-line"></i> Generar Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>