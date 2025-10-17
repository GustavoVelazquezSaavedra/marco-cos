<?php
// admin/pdf/ticket_venta.php
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

// Obtener datos del pedido (ejemplo - adaptar según tu estructura)
$pedido_id = isset($_GET['pedido_id']) ? $_GET['pedido_id'] : 0;

if ($pedido_id) {
    $query = "SELECT * FROM pedidos WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pedido) {
        $productos = json_decode($pedido['productos'], true);
    }
}

// Crear PDF
$pdf = new PDFGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('Marco Cos');
$pdf->SetAuthor('Marco Cos');
$pdf->SetTitle('Ticket de Venta - Marco Cos');
$pdf->SetSubject('Ticket de Venta al Cliente');

$pdf->AddPage();

// Título del ticket
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'COMPROBANTE DE VENTA', 0, 1, 'C');
$pdf->Ln(5);

// Información del ticket
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'N° Pedido:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, '#' . $pedido_id, 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'Fecha:', 0, 0, 'L');
$pdf->Cell(50, 6, date('d/m/Y H:i:s'), 0, 1, 'L');

if ($pedido) {
    $pdf->Cell(40, 6, 'Cliente:', 0, 0, 'L');
    $pdf->Cell(50, 6, $pedido['cliente_nombre'], 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Teléfono:', 0, 0, 'L');
    $pdf->Cell(50, 6, $pedido['cliente_telefono'], 0, 1, 'L');
}
$pdf->Ln(10);

// Generar tabla de productos
if ($pedido && $productos) {
    $productos_para_pdf = array();
    foreach ($productos as $producto) {
        $productos_para_pdf[] = array(
            'codigo' => $producto['id'] ?? 'N/A',
            'nombre' => $producto['name'] ?? $producto['nombre'] ?? 'Producto',
            'cantidad' => $producto['quantity'] ?? $producto['cantidad'] ?? 1,
            'precio_unitario' => $producto['price'] ?? $producto['precio'] ?? 0
        );
    }
    $pdf->generarTablaProductos($productos_para_pdf);
}

$pdf->Ln(10);

// Información de contacto
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, '¡Gracias por su compra!', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Marco Cos - Joyeria y Accesorios', 0, 1, 'C');
$pdf->Cell(0, 5, 'Tel: +595 972 366-265', 0, 1, 'C');
$pdf->Cell(0, 5, 'Horario: Lun-Vie 8:00-18:00', 0, 1, 'C');

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'Este documento no es válido como factura oficial', 0, 1, 'C');

// Salida del PDF
$pdf->Output('ticket_venta_' . $pedido_id . '_' . date('Ymd_His') . '.pdf', 'I');
?>