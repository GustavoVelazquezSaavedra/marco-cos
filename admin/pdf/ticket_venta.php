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

// Obtener tipo de cambio actual
$tipo_cambio = getTipoCambioActual();

// Obtener datos del pedido
$pedido_id = isset($_GET['pedido_id']) ? $_GET['pedido_id'] : 0;
$pedido = null;
$productos = array();

if ($pedido_id) {
    $query = "SELECT * FROM pedidos WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pedido) {
        $productos = json_decode($pedido['productos'], true);
    }
}

// Obtener información de la empresa desde la base de datos
$titulo_sistema = "Mi Sistema"; // Valor por defecto
$subtitulo_sistema = "Administración"; // Valor por defecto
$telefono_empresa = "+595 972 366-265"; // Valor por defecto
$horario_empresa = "Lun-Vie 8:00-18:00"; // Valor por defecto

// Intentar obtener de la base de datos si hay conexión
try {
    $query_config = "SELECT clave, valor FROM configuraciones WHERE clave IN ('titulo_sistema', 'subtitulo_sistema', 'telefono', 'horario')";
    $stmt_config = $db->prepare($query_config);
    $stmt_config->execute();
    $configs = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (isset($configs['titulo_sistema'])) {
        $titulo_sistema = $configs['titulo_sistema'];
    }
    if (isset($configs['subtitulo_sistema'])) {
        $subtitulo_sistema = $configs['subtitulo_sistema'];
    }
    if (isset($configs['telefono'])) {
        $telefono_empresa = $configs['telefono'];
    }
    if (isset($configs['horario'])) {
        $horario_empresa = $configs['horario'];
    }
} catch (Exception $e) {
    // Si hay error, usar valores por defecto
}

// FORZAR configuración para ticket térmico
$pdf = new PDFGenerator('P', 'mm', array(80, 200), true, 'UTF-8', false);

// CONFIGURAR EXPLÍCITAMENTE TODO CON LOS DATOS DE LA EMPRESA
$pdf->SetCreator($titulo_sistema);
$pdf->SetAuthor($titulo_sistema);
$pdf->SetTitle('Ticket de Venta - ' . $titulo_sistema);
$pdf->SetSubject('Ticket de Venta');
$pdf->SetKeywords('ticket, venta, ' . $titulo_sistema);

// Configuración explícita de márgenes
$pdf->SetMargins(5, 5, 5);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->SetAutoPageBreak(true, 5);

$pdf->AddPage();

// RESETEAR fuentes y configuraciones
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetLineWidth(0.2);

// Título del ticket - CENTRADO CORRECTAMENTE
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'COMPROBANTE DE VENTA', 0, 1, 'C');
$pdf->Ln(1);

// Información del tipo de cambio si está disponible

// Línea separadora
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// Información del pedido - ALINEACIÓN CORRECTA
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(20, 5, 'N° Pedido:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, '#' . $pedido_id, 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(20, 5, 'Fecha:', 0, 0, 'L');
$pdf->Cell(0, 5, date('d/m/Y H:i:s'), 0, 1, 'L');

if ($pedido) {
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(20, 5, 'Cliente:', 0, 0, 'L');
    $pdf->Cell(0, 5, $pedido['cliente_nombre'], 0, 1, 'L');
    
    $pdf->Cell(20, 5, 'Teléfono:', 0, 0, 'L');
    $pdf->Cell(0, 5, $pedido['cliente_telefono'], 0, 1, 'L');
}

$pdf->Ln(5);

// Línea separadora
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// Tabla de productos - FORMATO MEJORADO
if ($pedido && !empty($productos)) {
    // Cabecera de la tabla
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(12, 6, 'Cant', 0, 0, 'L');
    $pdf->Cell(38, 6, 'Producto', 0, 0, 'L');
    $pdf->Cell(20, 6, 'Total', 0, 1, 'R');
    
    // Línea bajo cabecera
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
        
        // Acortar nombre del producto si es muy largo
        if (strlen($nombre) > 25) {
            $nombre = substr($nombre, 0, 25) . '...';
        }
        
        $pdf->Cell(12, 5, $cantidad, 0, 0, 'L');
        $pdf->Cell(38, 5, $nombre, 0, 0, 'L');
        
        // Mostrar precio en Guaraníes
        $precios_subtotal = formatPrecioDual($subtotal);
        $pdf->Cell(20, 5, $precios_subtotal['gs'], 0, 1, 'R');
        
        // Si cantidad es mayor a 1, mostrar precio unitario
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
    
    // TOTAL - CON ESPACIADO CORRECTO Y AMBAS MONEDAS
    $precios_total = formatPrecioDual($total_general);
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 7, 'TOTAL:', 0, 0, 'R');
    $pdf->Cell(20, 7, $precios_total['gs'], 0, 1, 'R');
    
    // Mostrar también en USD
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(50, 4, '', 0, 0, 'R');
    $pdf->Cell(20, 4, $precios_total['usd'], 0, 1, 'R');
}

$pdf->Ln(8);

// Línea separadora final
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(5);

// INFORMACIÓN DE CONTACTO - USAR DATOS CONFIGURABLES
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, '¡Gracias por su compra!', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, $titulo_sistema, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, $subtitulo_sistema, 0, 1, 'C');
$pdf->Cell(0, 5, 'Tel: ' . $telefono_empresa, 0, 1, 'C');
$pdf->Cell(0, 5, 'Horario: ' . $horario_empresa, 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->Cell(0, 5, 'Este documento no es válido como factura oficial', 0, 1, 'C');

// ELIMINAR CUALQUIER POSIBLE HEADER/FOOTER
$pdf->setHeaderData('', 0, '', '');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Salida del PDF
$pdf->Output('ticket_venta_' . $pedido_id . '.pdf', 'I');
?>