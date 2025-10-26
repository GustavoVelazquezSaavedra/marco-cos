<?php
// admin/pdf/funciones_pdf.php

// Verificar sesión
if (!isset($_SESSION)) {
    session_start();
}

// Incluir dependencias
include_once('../../includes/tcpdf/tcpdf.php');
include_once('../../includes/database.php');
include_once('../../includes/functions.php');

class PDFGenerator extends TCPDF {
    
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false) {
        try {
            parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
            
            // Configuración básica
            $this->SetCreator('BLOOM - Perfumes y cosmeticos');
            $this->SetAuthor('BLOOM');
            $this->SetTitle('Documento del Sistema');
            $this->SetSubject('');
            $this->SetKeywords('BLOOM, perfumes, cosmeticos, PDF');
            
            // Configurar márgenes según el formato
            if ($format == array(80, 200)) {
                // Ticket pequeño
                $this->SetMargins(3, 3, 3);
                $this->SetAutoPageBreak(true, 5);
            } else {
                // Formatos estándar
                $this->SetMargins(10, 15, 10);
                $this->SetAutoPageBreak(true, 15);
            }
            
            $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $this->setFontSubsetting(true);
            
        } catch (Exception $e) {
            error_log('Error en PDFGenerator: ' . $e->getMessage());
            throw $e;
        }
    }
    
    // Header personalizado adaptativo
    public function Header() {
        $pageWidth = $this->getPageWidth();
        
        // Para tickets (ancho pequeño) - HEADER MUY COMPACTO
        if ($pageWidth <= 100) {
            $this->SetY(3);
            $this->SetFont('helvetica', 'B', 9);
            $this->Cell(0, 4, 'BLOOM - Perfumes y cosmeticos', 0, 1, 'C');
            $this->SetFont('helvetica', '', 7);
            $this->Cell(0, 3, '', 0, 1, 'C');
            $this->SetY($this->GetY() + 2);
        } 
        // Para formatos A4/Oficio - HEADER COMPLETO
        else {
            $this->SetY(10);
            
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 8, 'BLOOM', 0, 1, 'C');
            $this->SetFont('helvetica', '', 12);
            $this->Cell(0, 6, 'Perfumes y Cosméticos', 0, 1, 'C');
            $this->SetFont('helvetica', '', 9);
            $this->Cell(0, 5, '', 0, 1, 'C');
            
            // Línea separadora
            $this->Line(10, $this->GetY() + 2, $pageWidth-10, $this->GetY() + 2);
            $this->SetY($this->GetY() + 5);
        }
    }
    
    // Footer personalizado adaptativo
    public function Footer() {
        $pageWidth = $this->getPageWidth();
        
        // Para tickets - FOOTER MÍNIMO
        if ($pageWidth <= 100) {
            $this->SetY(-8);
            $this->SetFont('helvetica', 'I', 6);
            $this->Cell(0, 3, 'Pág. '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
        } 
        // Para formatos grandes - FOOTER COMPLETO
        else {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages() . ' - BLOOM Perfumes y cosmeticos', 0, 0, 'C');
        }
    }
    
    // Función auxiliar para truncar texto
    private function truncateText($text, $maxLength) {
        if (strlen($text) > $maxLength) {
            return substr($text, 0, $maxLength - 3) . '...';
        }
        return $text;
    }
    
    // Función para generar tabla de productos ADAPTATIVA
    public function generarTablaProductos($productos) {
        $pageWidth = $this->getPageWidth();
        
        if ($pageWidth <= 80) {
            return $this->generarTablaTicket($productos);
        } 
        elseif ($pageWidth <= 100) {
            return $this->generarTablaTicketMediano($productos);
        }
        else {
            return $this->generarTablaCompleta($productos);
        }
    }
    
    // Tabla ultra compacta para tickets de 80mm
    private function generarTablaTicket($productos) {
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.1);
        $this->SetFont('helvetica', 'B', 7);
        
        $header = array('Cant', 'Producto', 'Total');
        $w = array(10, 45, 25);
        
        for($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 5, $header[$i], 1, 0, 'C', 1);
        }
        $this->Ln();
        
        $this->SetFont('helvetica', '', 6);
        $this->SetFillColor(255, 255, 255);
        $total = 0;
        
        foreach($productos as $producto) {
            $cantidad = $producto['quantity'] ?? $producto['cantidad'] ?? 1;
            $precio_unitario = $producto['price'] ?? $producto['precio'] ?? 0;
            $nombre = $producto['name'] ?? $producto['nombre'] ?? 'Producto';
            $subtotal = $cantidad * $precio_unitario;
            $total += $subtotal;
            
            $nombre = $this->truncateText($nombre, 30);
            
            $this->Cell($w[0], 4, $cantidad, 'LR', 0, 'C', true);
            $this->Cell($w[1], 4, $nombre, 'LR', 0, 'L', true);
            
            // Mostrar en ambas monedas si está disponible
            if (function_exists('formatPrecioDual')) {
                $precio_dual = formatPrecioDual($subtotal);
                $this->Cell($w[2], 4, $precio_dual['gs'], 'LR', 0, 'R', true);
            } else {
                $this->Cell($w[2], 4, 'Gs. ' . number_format($subtotal, 0, ',', '.'), 'LR', 0, 'R', true);
            }
            
            $this->Ln();
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(2);
        
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell(array_sum($w) - $w[2], 5, 'TOTAL:', 0, 0, 'R', true);
        
        if (function_exists('formatPrecioDual')) {
            $precio_total = formatPrecioDual($total);
            $this->Cell($w[2], 5, $precio_total['gs'], 0, 0, 'R', true);
        } else {
            $this->Cell($w[2], 5, 'Gs. ' . number_format($total, 0, ',', '.'), 0, 0, 'R', true);
        }
        
        return $total;
    }
    
    // Tabla para tickets medianos de 100mm
    private function generarTablaTicketMediano($productos) {
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.1);
        $this->SetFont('helvetica', 'B', 8);
        
        $header = array('Cant', 'Producto', 'P.Unit', 'Total');
        $w = array(10, 50, 20, 20);
        
        for($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 6, $header[$i], 1, 0, 'C', 1);
        }
        $this->Ln();
        
        $this->SetFont('helvetica', '', 7);
        $this->SetFillColor(255, 255, 255);
        $total = 0;
        
        foreach($productos as $producto) {
            $cantidad = $producto['quantity'] ?? $producto['cantidad'] ?? 1;
            $precio_unitario = $producto['price'] ?? $producto['precio'] ?? 0;
            $nombre = $producto['name'] ?? $producto['nombre'] ?? 'Producto';
            $subtotal = $cantidad * $precio_unitario;
            $total += $subtotal;
            
            $nombre = $this->truncateText($nombre, 35);
            
            $this->Cell($w[0], 5, $cantidad, 'LR', 0, 'C', true);
            $this->Cell($w[1], 5, $nombre, 'LR', 0, 'L', true);
            
            if (function_exists('formatPrecioDual')) {
                $precio_unit = formatPrecioDual($precio_unitario);
                $precio_subtotal = formatPrecioDual($subtotal);
                $this->Cell($w[2], 5, $precio_unit['gs'], 'LR', 0, 'R', true);
                $this->Cell($w[3], 5, $precio_subtotal['gs'], 'LR', 0, 'R', true);
            } else {
                $this->Cell($w[2], 5, 'Gs. ' . number_format($precio_unitario, 0, ',', '.'), 'LR', 0, 'R', true);
                $this->Cell($w[3], 5, 'Gs. ' . number_format($subtotal, 0, ',', '.'), 'LR', 0, 'R', true);
            }
            
            $this->Ln();
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(2);
        
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(array_sum($w) - $w[3], 6, 'TOTAL:', 0, 0, 'R', true);
        
        if (function_exists('formatPrecioDual')) {
            $precio_total = formatPrecioDual($total);
            $this->Cell($w[3], 6, $precio_total['gs'], 0, 0, 'R', true);
        } else {
            $this->Cell($w[3], 6, 'Gs. ' . number_format($total, 0, ',', '.'), 0, 0, 'R', true);
        }
        
        return $total;
    }
    
    // Tabla completa para formatos A4/Oficio
    private function generarTablaCompleta($productos) {
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.1);
        $this->SetFont('helvetica', 'B', 10);
        
        $header = array('Código', 'Producto', 'Cantidad', 'P. Unitario', 'Subtotal');
        $w = array(25, 80, 25, 35, 35);
        
        for($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $this->Ln();
        
        $this->SetFont('helvetica', '', 9);
        $this->SetFillColor(255, 255, 255);
        $total = 0;
        
        foreach($productos as $producto) {
            $cantidad = $producto['quantity'] ?? $producto['cantidad'] ?? 1;
            $precio_unitario = $producto['price'] ?? $producto['precio'] ?? 0;
            $nombre = $producto['name'] ?? $producto['nombre'] ?? 'Producto';
            $codigo = $producto['codigo'] ?? $producto['sku'] ?? 'N/A';
            $subtotal = $cantidad * $precio_unitario;
            $total += $subtotal;
            
            $this->Cell($w[0], 6, $codigo, 'LR', 0, 'C', true);
            
            $nombre = $this->truncateText($nombre, 50);
            $this->Cell($w[1], 6, $nombre, 'LR', 0, 'L', true);
            
            $this->Cell($w[2], 6, $cantidad, 'LR', 0, 'C', true);
            
            if (function_exists('formatPrecioDual')) {
                $precio_unit = formatPrecioDual($precio_unitario);
                $precio_subtotal = formatPrecioDual($subtotal);
                $this->Cell($w[3], 6, $precio_unit['gs'], 'LR', 0, 'R', true);
                $this->Cell($w[4], 6, $precio_subtotal['gs'], 'LR', 0, 'R', true);
            } else {
                $this->Cell($w[3], 6, 'Gs. ' . number_format($precio_unitario, 0, ',', '.'), 'LR', 0, 'R', true);
                $this->Cell($w[4], 6, 'Gs. ' . number_format($subtotal, 0, ',', '.'), 'LR', 0, 'R', true);
            }
            
            $this->Ln();
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln();
        
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(array_sum($w) - $w[4], 7, 'TOTAL:', 0, 0, 'R', true);
        
        if (function_exists('formatPrecioDual')) {
            $precio_total = formatPrecioDual($total);
            $this->Cell($w[4], 7, $precio_total['gs'], 0, 0, 'R', true);
        } else {
            $this->Cell($w[4], 7, 'Gs. ' . number_format($total, 0, ',', '.'), 0, 0, 'R', true);
        }
        
        return $total;
    }
    
    // Función para generar tabla de movimientos de inventario - SOLO LANDSCAPE
    public function generarTablaMovimientos($movimientos) {
        // Siempre usar formato landscape para movimientos (más ancho)
        return $this->generarTablaMovimientosLandscape($movimientos);
    }
    
    // Tabla de movimientos para formato landscape
    private function generarTablaMovimientosLandscape($movimientos) {
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.1);
        $this->SetFont('helvetica', 'B', 8);
        
        $header = array('Fecha', 'Producto', 'Código', 'Tipo', 'Cantidad', 'Motivo', 'Usuario');
        $w = array(22, 55, 18, 15, 15, 60, 35);
        
        for($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 6, $header[$i], 1, 0, 'C', 1);
        }
        $this->Ln();
        
        $this->SetFont('helvetica', '', 7);
        $this->SetFillColor(255, 255, 255);
        
        if (empty($movimientos)) {
            $this->Cell(array_sum($w), 6, 'No se encontraron movimientos', 1, 1, 'C', true);
        } else {
            foreach($movimientos as $mov) {
                $fecha = date('d/m/Y H:i', strtotime($mov['fecha_movimiento']));
                $this->Cell($w[0], 5, $fecha, 'LR', 0, 'C', true);
                
                $producto = $mov['producto_nombre'];
                $producto = $this->truncateText($producto, 45);
                $this->Cell($w[1], 5, $producto, 'LR', 0, 'L', true);
                
                $this->Cell($w[2], 5, $mov['producto_codigo'], 'LR', 0, 'C', true);
                
                // Tipo con color
                if ($mov['tipo'] == 'entrada') {
                    $this->SetTextColor(0, 128, 0);
                    $this->Cell($w[3], 5, 'ENTRADA', 'LR', 0, 'C', true);
                } else {
                    $this->SetTextColor(255, 0, 0);
                    $this->Cell($w[3], 5, 'SALIDA', 'LR', 0, 'C', true);
                }
                $this->SetTextColor(0);
                
                $cantidad = ($mov['tipo'] == 'entrada') ? '+'.$mov['cantidad'] : '-'.$mov['cantidad'];
                $this->Cell($w[4], 5, $cantidad, 'LR', 0, 'C', true);
                
                $motivo = $mov['motivo'];
                $motivo = $this->truncateText($motivo, 55);
                $this->Cell($w[5], 5, $motivo, 'LR', 0, 'L', true);
                
                $usuario = $mov['usuario_nombre'];
                $usuario = $this->truncateText($usuario, 25);
                $this->Cell($w[6], 5, $usuario, 'LR', 0, 'L', true);
                
                $this->Ln();
            }
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
        return count($movimientos);
    }
    
    // Función para agregar información del cliente
    public function agregarInfoCliente($cliente_nombre, $cliente_telefono = '', $cliente_direccion = '') {
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(0, 5, 'CLIENTE:', 0, 1, 'L');
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 4, 'Nombre: ' . $cliente_nombre, 0, 1, 'L');
        
        if (!empty($cliente_telefono)) {
            $this->Cell(0, 4, 'Teléfono: ' . $cliente_telefono, 0, 1, 'L');
        }
        
        if (!empty($cliente_direccion)) {
            $this->Cell(0, 4, 'Dirección: ' . $cliente_direccion, 0, 1, 'L');
        }
        
        $this->Ln(3);
    }
}

// =============================================================================
// FUNCIONES DE COMPATIBILIDAD CON EL SISTEMA EXISTENTE
// =============================================================================

// Función para obtener información de la empresa
function getInfoEmpresa($db) {
    $info = array(
        'titulo_sistema' => "BLOOM - Perfumes y cosmeticos",
        'subtitulo_sistema' => "Sistema de Gestión",
        'telefono_empresa' => "+595 21 123 456",
        'horario_empresa' => "Lun-Vie 8:00-18:00, Sáb 8:00-12:00"
    );
    
    if ($db) {
        try {
            $query_config = "SELECT clave, valor FROM configuraciones WHERE clave IN ('titulo_sistema', 'subtitulo_sistema', 'telefono', 'horario')";
            $stmt_config = $db->prepare($query_config);
            $stmt_config->execute();
            $configs = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
            
            if (isset($configs['titulo_sistema'])) {
                $info['titulo_sistema'] = $configs['titulo_sistema'];
            }
            if (isset($configs['subtitulo_sistema'])) {
                $info['subtitulo_sistema'] = $configs['subtitulo_sistema'];
            }
            if (isset($configs['telefono'])) {
                $info['telefono_empresa'] = $configs['telefono'];
            }
            if (isset($configs['horario'])) {
                $info['horario_empresa'] = $configs['horario'];
            }
        } catch (Exception $e) {
            // Usar valores por defecto en caso de error
            error_log("Error obteniendo información de empresa: " . $e->getMessage());
        }
    }
    
    return $info;
}

// Función para generar ticket de venta (compatibilidad)
function generarTicketVenta($pedido_id, $db) {
    try {
        // Obtener datos del pedido
        $query = "SELECT * FROM pedidos WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido) {
            throw new Exception("No se encontró el pedido con ID: " . $pedido_id);
        }
        
        $productos = json_decode($pedido['productos'], true);
        if (!is_array($productos)) {
            $productos = array();
        }
        
        // Crear PDF para ticket
        $pdf = new PDFGenerator('P', 'mm', array(80, 200));
        
        $pdf->AddPage();
        
        // Información del pedido
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'COMPROBANTE DE VENTA', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 4, 'N° Pedido: #' . $pedido_id, 0, 1, 'L');
        $pdf->Cell(0, 4, 'Fecha: ' . date('d/m/Y H:i:s', strtotime($pedido['fecha_creacion'])), 0, 1, 'L');
        
        // Información del cliente
        if (!empty($pedido['cliente_nombre'])) {
            $pdf->Ln(2);
            $pdf->agregarInfoCliente($pedido['cliente_nombre'], $pedido['cliente_telefono'] ?? '');
        }
        
        $pdf->Ln(2);
        
        // Generar tabla de productos
        $total = $pdf->generarTablaProductos($productos);
        
        $pdf->Ln(5);
        
        // Información de contacto
        $pdf->SetFont('helvetica', 'I', 6);
        $pdf->Cell(0, 3, '¡Gracias por su compra!', 0, 1, 'C');
        $pdf->Cell(0, 3, 'Este documento no es válido como factura oficial', 0, 1, 'C');
        
        return $pdf;
        
    } catch (Exception $e) {
        throw new Exception("Error al generar ticket: " . $e->getMessage());
    }
}

// Función para generar reporte de inventario (compatibilidad)
function generarReporteInventario($db, $filtros = array()) {
    try {
        $info_empresa = getInfoEmpresa($db);
        
        // Crear PDF en landscape para más espacio
        $pdf = new PDFGenerator('L', 'mm', 'A4');
        
        $pdf->AddPage();
        
        // Título
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'REPORTE DE INVENTARIO', 0, 1, 'C');
        
        // Información de la empresa
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $info_empresa['titulo_sistema'], 0, 1, 'C');
        $pdf->Cell(0, 6, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        
        // Filtros aplicados
        if (!empty($filtros)) {
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 5, 'Filtros aplicados:', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 8);
            
            foreach($filtros as $key => $value) {
                if (!empty($value)) {
                    $pdf->Cell(0, 4, '- ' . ucfirst($key) . ': ' . $value, 0, 1, 'L');
                }
            }
        }
        
        $pdf->Ln(10);
        
        // Obtener productos del inventario
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM productos p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  WHERE p.activo = 1";
        
        $params = array();
        
        if (!empty($filtros['categoria'])) {
            $query .= " AND c.nombre = ?";
            $params[] = $filtros['categoria'];
        }
        
        if (!empty($filtros['stock'])) {
            if ($filtros['stock'] == 'bajo') {
                $query .= " AND p.cantidad <= p.stock_minimo AND p.cantidad > 0";
            } elseif ($filtros['stock'] == 'agotado') {
                $query .= " AND p.cantidad = 0";
            }
        }
        
        $query .= " ORDER BY p.nombre";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($productos)) {
            // Preparar datos para la tabla
            $productos_tabla = array();
            foreach($productos as $prod) {
                $productos_tabla[] = array(
                    'codigo' => $prod['codigo'] ?? 'N/A',
                    'nombre' => $prod['nombre'],
                    'cantidad' => $prod['cantidad'],
                    'precio' => $prod['precio']
                );
            }
            
            // Generar tabla
            $total_valor = $pdf->generarTablaProductos($productos_tabla);
            
            // Resumen
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'RESUMEN DEL INVENTARIO', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 5, 'Total de productos: ' . count($productos), 0, 1, 'L');
            
            if (function_exists('formatPrecioDual')) {
                $precio_total = formatPrecioDual($total_valor);
                $pdf->Cell(0, 5, 'Valor total del inventario: ' . $precio_total['gs'] . ' / ' . $precio_total['usd'], 0, 1, 'L');
            } else {
                $pdf->Cell(0, 5, 'Valor total del inventario: Gs. ' . number_format($total_valor, 0, ',', '.'), 0, 1, 'L');
            }
            
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No se encontraron productos con los filtros aplicados', 0, 1, 'C');
        }
        
        return $pdf;
        
    } catch (Exception $e) {
        throw new Exception("Error al generar reporte de inventario: " . $e->getMessage());
    }
}

// Función para generar reporte de movimientos (compatibilidad)
function generarReporteMovimientos($db, $filtros = array()) {
    try {
        $info_empresa = getInfoEmpresa($db);
        
        // Crear PDF en landscape
        $pdf = new PDFGenerator('L', 'mm', 'A4');
        
        $pdf->AddPage();
        
        // Título
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'REPORTE DE MOVIMIENTOS DE INVENTARIO', 0, 1, 'C');
        
        // Información
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $info_empresa['titulo_sistema'], 0, 1, 'C');
        $pdf->Cell(0, 6, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        
        $pdf->Ln(10);
        
        // Aquí iría la lógica para obtener movimientos de la base de datos
        // Por ahora usamos datos de ejemplo
        $movimientos = array(); // Obtener movimientos reales de la BD
        
        // Generar tabla de movimientos
        $total_movimientos = $pdf->generarTablaMovimientos($movimientos);
        
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6, 'Total de movimientos: ' . $total_movimientos, 0, 1, 'L');
        
        return $pdf;
        
    } catch (Exception $e) {
        throw new Exception("Error al generar reporte de movimientos: " . $e->getMessage());
    }
}

// Función para ticket de presupuesto (compatibilidad)
function generarTicketPresupuesto($presupuesto_id, $db) {
    // Por ahora usamos la misma función que ticket de venta
    return generarTicketVenta($presupuesto_id, $db);
}
?>