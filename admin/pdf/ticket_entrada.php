<?php
// admin/pdf/ticket_entrada.php
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

// Obtener información de la empresa desde la base de datos
$titulo_sistema = "Mi Sistema"; // Valor por defecto
$subtitulo_sistema = "Administración"; // Valor por defecto

// Intentar obtener de la base de datos si hay conexión
try {
    $query_config = "SELECT clave, valor FROM configuraciones WHERE clave IN ('titulo_sistema', 'subtitulo_sistema')";
    $stmt_config = $db->prepare($query_config);
    $stmt_config->execute();
    $configs = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (isset($configs['titulo_sistema'])) {
        $titulo_sistema = $configs['titulo_sistema'];
    }
    if (isset($configs['subtitulo_sistema'])) {
        $subtitulo_sistema = $configs['subtitulo_sistema'];
    }
} catch (Exception $e) {
    // Si hay error, usar valores por defecto
}

// Crear PDF
$pdf = new PDFGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator($titulo_sistema);
$pdf->SetAuthor($titulo_sistema);
$pdf->SetTitle('Ticket de Entrada - ' . $titulo_sistema);
$pdf->SetSubject('Ticket de Entrada de Inventario');

$pdf->AddPage();

// Título del ticket
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'TICKET DE ENTRADA DE INVENTARIO', 0, 1, 'C');
$pdf->Ln(3);

// Información de la empresa
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, $titulo_sistema, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, $subtitulo_sistema, 0, 1, 'C');
$pdf->Ln(5);

// Información del ticket
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'N° Ticket:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, 'ENT-' . date('Ymd') . '-' . rand(1000, 9999), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'Fecha:', 0, 0, 'L');
$pdf->Cell(50, 6, date('d/m/Y H:i:s'), 0, 1, 'L');

$pdf->Cell(40, 6, 'Responsable:', 0, 0, 'L');
$pdf->Cell(50, 6, $_SESSION['user_name'], 0, 1, 'L');
$pdf->Ln(10);

// Ejemplo de datos de productos (debes adaptar según tu estructura)
$productos_ejemplo = array(
    array(
        'codigo' => 'PROD001',
        'nombre' => 'Anillo Oro 18k Solitario',
        'cantidad' => 5,
        'precio_unitario' => 450000
    ),
    array(
        'codigo' => 'PROD002', 
        'nombre' => 'Collar Plata Corazón',
        'cantidad' => 3,
        'precio_unitario' => 120000
    )
);

// Generar tabla de productos
$pdf->generarTablaProductos($productos_ejemplo);
$pdf->Ln(10);

// Observaciones
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'Observaciones:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6, 'Productos ingresados al inventario principal para venta.', 0, 'L');

$pdf->Ln(15);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 6, '_________________________________', 0, 1, 'C');
$pdf->Cell(0, 6, 'Firma del Responsable', 0, 1, 'C');

// Salida del PDF
$pdf->Output('ticket_entrada_' . date('Ymd_His') . '.pdf', 'I');
?>