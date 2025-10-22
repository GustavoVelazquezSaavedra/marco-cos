<?php
// admin/pdf/ticket_presupuesto.php
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

// Obtener tipo de cambio actual
$tipo_cambio = getTipoCambioActual();

// Obtener datos del presupuesto
$presupuesto_id = isset($_GET['presupuesto_id']) ? $_GET['presupuesto_id'] : 0;
$presupuesto = null;
$productos = array();

if ($presupuesto_id) {
    $query = "SELECT * FROM presupuestos WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$presupuesto_id]);
    $presupuesto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($presupuesto) {
        $productos = json_decode($presupuesto['productos'], true);
    }
}

// Si no se encuentra el presupuesto
if (!$presupuesto) {
    die('Presupuesto no encontrado');
}

// FORZAR configuración para ticket térmico
$pdf = new PDFGenerator('P', 'mm', array(80, 200), true, 'UTF-8', false);

// CONFIGURACIÓN GENERAL BLOOM
$pdf->SetCreator('BLOOM');
$pdf->SetAuthor('BLOOM');
$pdf->SetTitle('Presupuesto - BLOOM');
$pdf->SetSubject('Presupuesto');
$pdf->SetKeywords('presupuesto, BLOOM, ticket');

// Márgenes y saltos
$pdf->SetMargins(5, 5, 5);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->SetAutoPageBreak(true, 5);

$pdf->AddPage();

// Fuente y color base
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetLineWidth(0.2);

// --- ENCABEZADO ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'PRESUPUESTO BLOOM', 0, 1, 'C');
$pdf->Ln(1);

// Línea separadora
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// --- INFORMACIÓN GENERAL ---
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(20, 5, 'N° Presupuesto:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, '#' . $presupuesto_id, 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(20, 5, 'Fecha:', 0, 0, 'L');
$pdf->Cell(0, 5, date('d/m/Y H:i:s'), 0, 1, 'L');

if ($presupuesto) {
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(20, 5, 'Cliente:', 0, 0, 'L');
    $pdf->Cell(0, 5, $presupuesto['cliente_nombre'], 0, 1, 'L');
    
    $pdf->Cell(20, 5, 'Teléfono:', 0, 0, 'L');
    $pdf->Cell(0, 5, $presupuesto['cliente_telefono'], 0, 1, 'L');
    
    if (!empty($presupuesto['cliente_documento']) && $presupuesto['cliente_documento'] != '0') {
        $pdf->Cell(20, 5, 'Documento:', 0, 0, 'L');
        $pdf->Cell(0, 5, $presupuesto['cliente_documento'], 0, 1, 'L');
    }
}

$pdf->Ln(5);

// Línea separadora
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// --- TABLA DE PRODUCTOS ---
if ($presupuesto && !empty($productos)) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(12, 6, 'Cant', 0, 0, 'L');
    $pdf->Cell(38, 6, 'Producto', 0, 0, 'L');
    $pdf->Cell(20, 6, 'Total', 0, 1, 'R');

    $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
    $pdf->Ln(2);

    $total_general = 0;
    $pdf->SetFont('helvetica', '', 8);

    foreach ($productos as $prod) {
        $cantidad = $prod['quantity'] ?? $prod['cantidad'] ?? 1;
        $precio_unitario = $prod['price'] ?? $prod['precio'] ?? 0;
        $nombre = $prod['name'] ?? $prod['nombre'] ?? 'Producto';
        $subtotal = $cantidad * $precio_unitario;
        $total_general += $subtotal;

        if (strlen($nombre) > 25) {
            $nombre = substr($nombre, 0, 25) . '...';
        }

        $pdf->Cell(12, 5, $cantidad, 0, 0, 'L');
        $pdf->Cell(38, 5, $nombre, 0, 0, 'L');

        $precios_subtotal = formatPrecioDual($subtotal);
        $pdf->Cell(20, 5, $precios_subtotal['gs'], 0, 1, 'R');

        if ($cantidad > 1) {
            $pdf->Cell(12, 4, '', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'I', 7);
            $precios_unitario = formatPrecioDual($precio_unitario);
            $pdf->Cell(38, 4, '@ ' . $precios_unitario['gs'], 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 8);
        }
    }

    $pdf->Ln(3);
    $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
    $pdf->Ln(3);

    // TOTAL
    $precios_total = formatPrecioDual($total_general);

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 7, 'TOTAL:', 0, 0, 'R');
    $pdf->Cell(20, 7, $precios_total['gs'], 0, 1, 'R');

    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(50, 4, '', 0, 0, 'R');
    $pdf->Cell(20, 4, $precios_total['usd'], 0, 1, 'R');
}

$pdf->Ln(8);

// Línea final
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(5);

// --- PIE DE TICKET ---
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, '¡Gracias por su consulta!', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'BLOOM - Perfumes y cosméticos', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, 'Tel: +595 972 366-265', 0, 1, 'C');
$pdf->Cell(0, 5, 'Horario: Lun-Vie 8:00-18:00', 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->Cell(0, 5, 'Este presupuesto es válido por 15 días', 0, 1, 'C');

// Sin header/footer
$pdf->setHeaderData('', 0, '', '');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// SALIDA FINAL
$pdf->Output('presupuesto_bloom_' . $presupuesto_id . '.pdf', 'I');
?>
