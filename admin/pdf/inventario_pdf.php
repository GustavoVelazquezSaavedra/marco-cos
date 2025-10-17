<?php
// admin/pdf/inventario_pdf.php
include_once('funciones_pdf.php');

// Verificar sesión
if (!isset($_SESSION)) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    die('Acceso denegado');
}

$database = new Database();
$db = $database->getConnection();

// Obtener parámetros de filtro (los mismos que en inventario.php)
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$tipo_movimiento = isset($_GET['tipo_movimiento']) ? $_GET['tipo_movimiento'] : '';
$producto_id = isset($_GET['producto_id']) ? $_GET['producto_id'] : '';

// Obtener movimientos de inventario (misma consulta que en inventario.php)
$query = "SELECT i.*, p.nombre as producto_nombre, p.codigo as producto_codigo, 
                 u.nombre as usuario_nombre, c.nombre as categoria_nombre
          FROM inventario i 
          LEFT JOIN productos p ON i.producto_id = p.id 
          LEFT JOIN categorias c ON p.categoria_id = c.id
          LEFT JOIN usuarios u ON i.usuario_id = u.id 
          WHERE 1=1";

$params = [];

// Aplicar filtros
if (!empty($fecha_desde)) {
    $query .= " AND DATE(i.fecha_movimiento) >= ?";
    $params[] = $fecha_desde;
}

if (!empty($fecha_hasta)) {
    $query .= " AND DATE(i.fecha_movimiento) <= ?";
    $params[] = $fecha_hasta;
}

if (!empty($tipo_movimiento)) {
    $query .= " AND i.tipo = ?";
    $params[] = $tipo_movimiento;
}

if (!empty($producto_id)) {
    $query .= " AND i.producto_id = ?";
    $params[] = $producto_id;
}

$query .= " ORDER BY i.fecha_movimiento DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos para estadísticas
$queryProductos = "SELECT p.id, p.nombre, p.codigo, p.stock, c.nombre as categoria_nombre 
                   FROM productos p 
                   LEFT JOIN categorias c ON p.categoria_id = c.id 
                   WHERE p.activo = 1 
                   ORDER BY p.nombre";
$stmtProductos = $db->prepare($queryProductos);
$stmtProductos->execute();
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

// Crear PDF
$pdf = new PDFGenerator('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('Marco Cos');
$pdf->SetAuthor('Marco Cos');
$pdf->SetTitle('Reporte de Movimientos de Inventario - Marco Cos');
$pdf->SetSubject('Reporte de Movimientos de Inventario');

$pdf->AddPage();

// Título del reporte
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'REPORTE DE MOVIMIENTOS DE INVENTARIO', 0, 1, 'C');
$pdf->Ln(5);

// Información del reporte
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'Fecha de reporte:', 0, 0, 'L');
$pdf->Cell(50, 6, date('d/m/Y H:i:s'), 0, 1, 'L');

$pdf->Cell(40, 6, 'Generado por:', 0, 0, 'L');
$pdf->Cell(50, 6, $_SESSION['user_name'], 0, 1, 'L');

// Información de filtros aplicados
if (!empty($fecha_desde) || !empty($fecha_hasta) || !empty($tipo_movimiento) || !empty($producto_id)) {
    $pdf->Cell(40, 6, 'Filtros aplicados:', 0, 1, 'L');
    
    if (!empty($fecha_desde)) {
        $pdf->Cell(10, 6, '', 0, 0, 'L');
        $pdf->Cell(30, 6, 'Desde:', 0, 0, 'L');
        $pdf->Cell(40, 6, $fecha_desde, 0, 1, 'L');
    }
    
    if (!empty($fecha_hasta)) {
        $pdf->Cell(10, 6, '', 0, 0, 'L');
        $pdf->Cell(30, 6, 'Hasta:', 0, 0, 'L');
        $pdf->Cell(40, 6, $fecha_hasta, 0, 1, 'L');
    }
    
    if (!empty($tipo_movimiento)) {
        $pdf->Cell(10, 6, '', 0, 0, 'L');
        $pdf->Cell(30, 6, 'Tipo:', 0, 0, 'L');
        $pdf->Cell(40, 6, ucfirst($tipo_movimiento), 0, 1, 'L');
    }
}

$pdf->Ln(10);

// Tabla de movimientos
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.1);
$pdf->SetFont('', 'B');

// Cabecera de la tabla
$header = array('Fecha', 'Producto', 'Código', 'Tipo', 'Cantidad', 'Motivo', 'Usuario');
$w = array(25, 60, 25, 20, 20, 70, 40);

for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Datos de movimientos
$pdf->SetFont('', '');
$pdf->SetFillColor(255, 255, 255);

if (empty($movimientos)) {
    // Si no hay movimientos
    $pdf->Cell(array_sum($w), 6, 'No se encontraron movimientos', 1, 1, 'C', true);
} else {
    foreach($movimientos as $mov) {
        // Fecha
        $pdf->Cell($w[0], 6, date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])), 'LR', 0, 'C', true);
        
        // Producto
        $pdf->Cell($w[1], 6, substr($mov['producto_nombre'], 0, 35), 'LR', 0, 'L', true);
        
        // Código
        $pdf->Cell($w[2], 6, $mov['producto_codigo'], 'LR', 0, 'C', true);
        
        // Tipo (con color)
        if ($mov['tipo'] == 'entrada') {
            $pdf->SetTextColor(0, 128, 0); // Verde para entrada
            $pdf->Cell($w[3], 6, 'ENTRADA', 'LR', 0, 'C', true);
        } else {
            $pdf->SetTextColor(255, 0, 0); // Rojo para salida
            $pdf->Cell($w[3], 6, 'SALIDA', 'LR', 0, 'C', true);
        }
        $pdf->SetTextColor(0);
        
        // Cantidad (con signo)
        $cantidad = ($mov['tipo'] == 'entrada') ? '+'.$mov['cantidad'] : '-'.$mov['cantidad'];
        $pdf->Cell($w[4], 6, $cantidad, 'LR', 0, 'C', true);
        
        // Motivo
        $pdf->Cell($w[5], 6, substr($mov['motivo'], 0, 45), 'LR', 0, 'L', true);
        
        // Usuario
        $pdf->Cell($w[6], 6, $mov['usuario_nombre'], 'LR', 0, 'L', true);
        
        $pdf->Ln();
    }
}

// Línea de cierre
$pdf->Cell(array_sum($w), 0, '', 'T');
$pdf->Ln(10);

// Resumen del reporte
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'RESUMEN DEL REPORTE', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

// Estadísticas de movimientos
$total_movimientos = count($movimientos);
$entradas = array_filter($movimientos, function($m) { return $m['tipo'] == 'entrada'; });
$salidas = array_filter($movimientos, function($m) { return $m['tipo'] == 'salida'; });

$pdf->Cell(60, 6, 'Total de movimientos:', 0, 0, 'L');
$pdf->Cell(30, 6, $total_movimientos, 0, 1, 'L');

$pdf->Cell(60, 6, 'Movimientos de entrada:', 0, 0, 'L');
$pdf->Cell(30, 6, count($entradas), 0, 1, 'L');

$pdf->Cell(60, 6, 'Movimientos de salida:', 0, 0, 'L');
$pdf->Cell(30, 6, count($salidas), 0, 1, 'L');

// Estadísticas de inventario actual
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'ESTADO ACTUAL DEL INVENTARIO', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$total_productos = count($productos);
$productosStockBajo = array_filter($productos, function($prod) { return $prod['stock'] < 10 && $prod['stock'] > 0; });
$productosSinStock = array_filter($productos, function($prod) { return $prod['stock'] == 0; });

$pdf->Cell(60, 6, 'Total de productos:', 0, 0, 'L');
$pdf->Cell(30, 6, $total_productos, 0, 1, 'L');

$pdf->Cell(60, 6, 'Productos con stock bajo:', 0, 0, 'L');
$pdf->Cell(30, 6, count($productosStockBajo), 0, 1, 'L');

$pdf->Cell(60, 6, 'Productos sin stock:', 0, 0, 'L');
$pdf->Cell(30, 6, count($productosSinStock), 0, 1, 'L');

// Pie de página con información adicional
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 6, 'Reporte generado automáticamente por el Sistema de Gestión Marco Cos', 0, 1, 'C');

// Salida del PDF
$pdf->Output('movimientos_inventario_' . date('Ymd_His') . '.pdf', 'I');
?>