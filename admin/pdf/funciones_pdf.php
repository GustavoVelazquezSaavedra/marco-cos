<?php
// admin/pdf/funciones_pdf.php
include_once('../../includes/tcpdf/tcpdf.php');
include_once('../../includes/database.php');
include_once('../../includes/functions.php');

// Incluir el archivo de configuración de títulos
include_once('../../config/config_sistema.php');

class PDFGenerator extends TCPDF {
    
    // Header personalizado adaptativo
    public function Header() {
        $pageWidth = $this->getPageWidth();
        
        // Para tickets (ancho pequeño) - HEADER MUY COMPACTO
        if ($pageWidth <= 100) {
            $this->SetY(3);
            $this->SetFont('helvetica', 'B', 9);
            $this->Cell(0, 4, $GLOBALS['titulo_sistema'], 0, 1, 'C');
            $this->SetFont('helvetica', '', 7);
            $this->Cell(0, 3, $GLOBALS['subtitulo_sistema'], 0, 1, 'C');
            $this->SetY($this->GetY() + 2);
        } 
        // Para formatos A4/Oficio - HEADER COMPLETO
        else {
            $this->SetY(10);
            
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 8, $GLOBALS['titulo_sistema'], 0, 1, 'C');
            $this->SetFont('helvetica', '', 12);
            $this->Cell(0, 6, $GLOBALS['subtitulo_sistema'], 0, 1, 'C');
            $this->SetFont('helvetica', '', 9);
            $this->Cell(0, 5, obtenerConfiguracion('direccion', 'Tu dirección aquí'), 0, 1, 'C');
            
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
            $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages() . ' - ' . $GLOBALS['titulo_sistema'] . ' ' . $GLOBALS['subtitulo_sistema'], 0, 0, 'C');
        }
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
            $subtotal = $producto['cantidad'] * $producto['precio_unitario'];
            $total += $subtotal;
            
            $nombre = $producto['nombre'];
            if (strlen($nombre) > 30) {
                $nombre = substr($nombre, 0, 30) . '...';
            }
            
            $this->Cell($w[0], 4, $producto['cantidad'], 'LR', 0, 'C', true);
            $this->Cell($w[1], 4, $nombre, 'LR', 0, 'L', true);
            $this->Cell($w[2], 4, 'Gs. ' . number_format($subtotal, 0, ',', '.'), 'LR', 0, 'R', true);
            $this->Ln();
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(2);
        
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell(array_sum($w) - $w[2], 5, 'TOTAL:', 0, 0, 'R', true);
        $this->Cell($w[2], 5, 'Gs. ' . number_format($total, 0, ',', '.'), 0, 0, 'R', true);
        
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
            $subtotal = $producto['cantidad'] * $producto['precio_unitario'];
            $total += $subtotal;
            
            $nombre = $producto['nombre'];
            if (strlen($nombre) > 35) {
                $nombre = substr($nombre, 0, 35) . '...';
            }
            
            $this->Cell($w[0], 5, $producto['cantidad'], 'LR', 0, 'C', true);
            $this->Cell($w[1], 5, $nombre, 'LR', 0, 'L', true);
            $this->Cell($w[2], 5, 'Gs. ' . number_format($producto['precio_unitario'], 0, ',', '.'), 'LR', 0, 'R', true);
            $this->Cell($w[3], 5, 'Gs. ' . number_format($subtotal, 0, ',', '.'), 'LR', 0, 'R', true);
            $this->Ln();
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(2);
        
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(array_sum($w) - $w[3], 6, 'TOTAL:', 0, 0, 'R', true);
        $this->Cell($w[3], 6, 'Gs. ' . number_format($total, 0, ',', '.'), 0, 0, 'R', true);
        
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
            $subtotal = $producto['cantidad'] * $producto['precio_unitario'];
            $total += $subtotal;
            
            $this->Cell($w[0], 6, $producto['codigo'], 'LR', 0, 'C', true);
            
            $nombre = $producto['nombre'];
            if (strlen($nombre) > 50) {
                $nombre = substr($nombre, 0, 50) . '...';
            }
            $this->Cell($w[1], 6, $nombre, 'LR', 0, 'L', true);
            
            $this->Cell($w[2], 6, $producto['cantidad'], 'LR', 0, 'C', true);
            $this->Cell($w[3], 6, 'Gs. ' . number_format($producto['precio_unitario'], 0, ',', '.'), 'LR', 0, 'R', true);
            $this->Cell($w[4], 6, 'Gs. ' . number_format($subtotal, 0, ',', '.'), 'LR', 0, 'R', true);
            $this->Ln();
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln();
        
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(array_sum($w) - $w[4], 7, 'TOTAL:', 0, 0, 'R', true);
        $this->Cell($w[4], 7, 'Gs. ' . number_format($total, 0, ',', '.'), 0, 0, 'R', true);
        
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
                if (strlen($producto) > 45) {
                    $producto = substr($producto, 0, 45) . '...';
                }
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
                if (strlen($motivo) > 55) {
                    $motivo = substr($motivo, 0, 55) . '...';
                }
                $this->Cell($w[5], 5, $motivo, 'LR', 0, 'L', true);
                
                $usuario = $mov['usuario_nombre'];
                if (strlen($usuario) > 25) {
                    $usuario = substr($usuario, 0, 25) . '...';
                }
                $this->Cell($w[6], 5, $usuario, 'LR', 0, 'L', true);
                
                $this->Ln();
            }
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
        return count($movimientos);
    }
}
?>