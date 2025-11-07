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

// Obtener información de la empresa desde la base de datos
$titulo_sistema = "BLOOM"; // Valor por defecto
$subtitulo_sistema = "Perfumes y cosmeticos"; // Valor por defecto
$telefono_empresa = "+595976588694"; // Valor por defecto
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

// Obtener tipo de cambio actual
$tipo_cambio = getTipoCambioActual();

// Obtener datos del presupuesto
$presupuesto_id = isset($_GET['presupuesto_id']) ? $_GET['presupuesto_id'] : 0;
$presupuesto = null;
$productos = array();

if ($presupuesto_id) {
    // Obtener presupuesto con las nuevas columnas
    $query = "SELECT *, 
                     COALESCE(moneda, 'gs') as moneda,
                     COALESCE(aplicar_iva, 'no') as aplicar_iva,
                     COALESCE(tipo_descuento, '') as tipo_descuento,
                     COALESCE(descuento_general, 0) as descuento_general
              FROM presupuestos WHERE id = ?";
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

// CONFIGURACIÓN GENERAL CON DATOS DE LA EMPRESA
$pdf->SetCreator($titulo_sistema);
$pdf->SetAuthor($titulo_sistema);
$pdf->SetTitle('Presupuesto - ' . $titulo_sistema);
$pdf->SetSubject('Presupuesto');
$pdf->SetKeywords('presupuesto, ' . $titulo_sistema . ', ticket');

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
$pdf->Cell(0, 8, 'PRESUPUESTO', 0, 1, 'C');
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
$pdf->Cell(0, 5, date('d/m/Y H:i:s', strtotime($presupuesto['fecha_creacion'])), 0, 1, 'L');

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

// Mostrar configuración del presupuesto
$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 4, 'Moneda: ' . strtoupper($presupuesto['moneda']), 0, 1, 'L');
$pdf->Cell(0, 4, 'IVA: ' . ($presupuesto['aplicar_iva'] == 'si' ? 'SÍ (10%)' : 'NO'), 0, 1, 'L');

if (!empty($presupuesto['tipo_descuento'])) {
    $descuento_text = 'Descuento: ' . strtoupper($presupuesto['tipo_descuento']);
    if ($presupuesto['tipo_descuento'] == 'porcentaje') {
        $descuento_text .= ' (' . $presupuesto['descuento_general'] . '%)';
    } else {
        $descuento_text .= ' (' . ($presupuesto['moneda'] == 'usd' ? '$' : 'Gs.') . ' ' . number_format($presupuesto['descuento_general'], $presupuesto['moneda'] == 'usd' ? 2 : 0, ',', '.') . ')';
    }
    $pdf->Cell(0, 4, $descuento_text, 0, 1, 'L');
}

$pdf->Ln(3);

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

    $subtotal_general = 0;
    $pdf->SetFont('helvetica', '', 8);

    foreach ($productos as $prod) {
        $cantidad = $prod['quantity'] ?? $prod['cantidad'] ?? 1;
        $precio_unitario = $prod['price'] ?? $prod['precio'] ?? 0;
        $nombre = $prod['name'] ?? $prod['nombre'] ?? 'Producto';
        $subtotal = $cantidad * $precio_unitario;
        $subtotal_general += $subtotal;

        if (strlen($nombre) > 25) {
            $nombre = substr($nombre, 0, 25) . '...';
        }

        $pdf->Cell(12, 5, $cantidad, 0, 0, 'L');
        $pdf->Cell(38, 5, $nombre, 0, 0, 'L');

        // Mostrar precio según moneda
        if ($presupuesto['moneda'] == 'usd') {
            $pdf->Cell(20, 5, '$ ' . number_format($subtotal, 2, '.', ','), 0, 1, 'R');
        } else {
            $pdf->Cell(20, 5, 'Gs. ' . number_format($subtotal, 0, ',', '.'), 0, 1, 'R');
        }

        if ($cantidad > 1) {
            $pdf->Cell(12, 4, '', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'I', 7);
            if ($presupuesto['moneda'] == 'usd') {
                $pdf->Cell(38, 4, '@ $ ' . number_format($precio_unitario, 2, '.', ','), 0, 1, 'L');
            } else {
                $pdf->Cell(38, 4, '@ Gs. ' . number_format($precio_unitario, 0, ',', '.'), 0, 1, 'L');
            }
            $pdf->SetFont('helvetica', '', 8);
        }
    }

    $pdf->Ln(3);
    $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
    $pdf->Ln(3);

    // --- CÁLCULO DE TOTALES ---
    $subtotal = $subtotal_general;
    $iva = 0;
    $descuento = 0;
    $total = $subtotal;

    // Calcular IVA
    if ($presupuesto['aplicar_iva'] == 'si') {
        $iva = $subtotal * 0.10;
        $total += $iva;
    }

    // Calcular descuento
    if (!empty($presupuesto['tipo_descuento'])) {
        if ($presupuesto['tipo_descuento'] == 'porcentaje') {
            $descuento = $subtotal * ($presupuesto['descuento_general'] / 100);
        } else {
            $descuento = $presupuesto['descuento_general'];
        }
        $total -= $descuento;
    }

    // Mostrar desglose
    $pdf->SetFont('helvetica', '', 8);
    
    // Subtotal
    $pdf->Cell(50, 5, 'Subtotal:', 0, 0, 'R');
    if ($presupuesto['moneda'] == 'usd') {
        $pdf->Cell(20, 5, '$ ' . number_format($subtotal, 2, '.', ','), 0, 1, 'R');
    } else {
        $pdf->Cell(20, 5, 'Gs. ' . number_format($subtotal, 0, ',', '.'), 0, 1, 'R');
    }

    // IVA
    if ($presupuesto['aplicar_iva'] == 'si') {
        $pdf->Cell(50, 5, 'IVA 10%:', 0, 0, 'R');
        if ($presupuesto['moneda'] == 'usd') {
            $pdf->Cell(20, 5, '$ ' . number_format($iva, 2, '.', ','), 0, 1, 'R');
        } else {
            $pdf->Cell(20, 5, 'Gs. ' . number_format($iva, 0, ',', '.'), 0, 1, 'R');
        }
    }

    // Descuento
    if (!empty($presupuesto['tipo_descuento'])) {
        $pdf->SetTextColor(255, 0, 0); // Rojo para descuento
        $pdf->Cell(50, 5, 'Descuento:', 0, 0, 'R');
        if ($presupuesto['moneda'] == 'usd') {
            $pdf->Cell(20, 5, '- $ ' . number_format($descuento, 2, '.', ','), 0, 1, 'R');
        } else {
            $pdf->Cell(20, 5, '- Gs. ' . number_format($descuento, 0, ',', '.'), 0, 1, 'R');
        }
        $pdf->SetTextColor(0, 0, 0); // Volver a negro
    }

    $pdf->Ln(2);
    $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
    $pdf->Ln(2);

    // TOTAL FINAL
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 7, 'TOTAL:', 0, 0, 'R');
    if ($presupuesto['moneda'] == 'usd') {
        $pdf->Cell(20, 7, '$ ' . number_format($total, 2, '.', ','), 0, 1, 'R');
        
        // Mostrar conversión a guaraníes
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(50, 4, '', 0, 0, 'R');
        $total_gs = $total * $tipo_cambio['venta'];
        $pdf->Cell(20, 4, 'Gs. ' . number_format($total_gs, 0, ',', '.'), 0, 1, 'R');
    } else {
        $pdf->Cell(20, 7, 'Gs. ' . number_format($total, 0, ',', '.'), 0, 1, 'R');
        
        // Mostrar conversión a dólares
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(50, 4, '', 0, 0, 'R');
        $total_usd = $total / $tipo_cambio['venta'];
        $pdf->Cell(20, 4, '$ ' . number_format($total_usd, 2, '.', ','), 0, 1, 'R');
    }
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
$pdf->Cell(0, 6, $titulo_sistema, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, $subtitulo_sistema, 0, 1, 'C');
$pdf->Cell(0, 5, 'Tel: ' . $telefono_empresa, 0, 1, 'C');
$pdf->Cell(0, 5, 'Horario: ' . $horario_empresa, 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->Cell(0, 5, 'Este presupuesto es válido por 15 días', 0, 1, 'C');

// Sin header/footer
$pdf->setHeaderData('', 0, '', '');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// SALIDA FINAL
$pdf->Output('presupuesto_' . $presupuesto_id . '.pdf', 'I');
?>