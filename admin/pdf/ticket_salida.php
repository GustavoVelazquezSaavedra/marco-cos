<?php
// admin/pdf/ticket_salida.php
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

// Crear PDF
$pdf = new PDFGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('Marco Cos');
$pdf->SetAuthor('Marco Cos');
$pdf->SetTitle('Ticket de Salida - Marco Cos');
$pdf->SetSubject('Ticket de Salida de Inventario');

$pdf->AddPage();

// Título del ticket
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'TICKET DE SALIDA DE INVENTARIO', 0, 1, 'C');
$pdf->Ln(5);

// Información del ticket
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'N° Ticket:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, 'SAL-' . date('Ymd') . '-' . rand(1000, 9999), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'Fecha:', 0, 0, 'L');
$pdf->Cell(50, 6, date('d/m/Y H:i:s'), 0, 1, 'L');

$pdf->Cell(40, 6, 'Responsable:', 0, 0, 'L');
$pdf->Cell(50, 6, $_SESSION['user_name'], 0, 1, 'L');

$pdf->Cell(40, 6, 'Motivo:', 0, 0, 'L');
$pdf->Cell(50, 6, 'Venta al público', 0, 1, 'L');

$pdf->Cell(40, 6, 'Retirado por:', 0, 0, 'L');
$pdf->Cell(50, 6, 'Cliente final', 0, 1, 'L');
$pdf->Ln(10);

// Ejemplo de datos de productos
$productos_ejemplo = array(
    array(
        'codigo' => 'PROD001',
        'nombre' => 'Anillo Oro 18k Solitario',
        'cantidad' => 1,
        'precio_unitario' => 450000
    )
);

// Generar tabla de productos
$pdf->generarTablaProductos($productos_ejemplo);
$pdf->Ln(10);

// Observaciones
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'Observaciones:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6, 'Producto vendido y entregado al cliente.', 0, 'L');

$pdf->Ln(15);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 6, '_________________________________', 0, 1, 'C');
$pdf->Cell(0, 6, 'Firma del Responsable', 0, 1, 'C');

// Salida del PDF
$pdf->Output('ticket_salida_' . date('Ymd_His') . '.pdf', 'I');
?>