<?php
// admin/pdf/funciones_pdf.php
include_once('../../includes/tcpdf/tcpdf.php');
include_once('../../includes/database.php');
include_once('../../includes/functions.php');

class PDFGenerator extends TCPDF {
    
    // Header personalizado para todos los PDFs
    public function Header() {
        // Logo
        $image_file = '../../uploads/logo.jpg'; // Ajusta la ruta de tu logo
        if (file_exists($image_file)) {
            $this->Image($image_file, 10, 10, 30, 0, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Título
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'MARCO COS', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(5);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 10, 'Joyeria y Accesorios', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
        
        // Línea separadora
        $this->Line(10, 30, 200, 30);
        $this->Ln(5);
    }
    
    // Footer personalizado
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
    
    // Función para generar tabla de productos
    public function generarTablaProductos($productos) {
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.1);
        $this->SetFont('', 'B');
        
        // Cabecera de la tabla
        $header = array('Código', 'Producto', 'Cantidad', 'P. Unitario', 'Subtotal');
        $w = array(25, 65, 25, 35, 35);
        
        for($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $this->Ln();
        
        // Datos de productos
        $this->SetFont('', '');
        $this->SetFillColor(255, 255, 255);
        $total = 0;
        
        foreach($productos as $producto) {
            $subtotal = $producto['cantidad'] * $producto['precio_unitario'];
            $total += $subtotal;
            
            $this->Cell($w[0], 6, $producto['codigo'], 'LR', 0, 'C', true);
            $this->Cell($w[1], 6, $producto['nombre'], 'LR', 0, 'L', true);
            $this->Cell($w[2], 6, $producto['cantidad'], 'LR', 0, 'C', true);
            $this->Cell($w[3], 6, 'Gs. ' . number_format($producto['precio_unitario'], 0, ',', '.'), 'LR', 0, 'R', true);
            $this->Cell($w[4], 6, 'Gs. ' . number_format($subtotal, 0, ',', '.'), 'LR', 0, 'R', true);
            $this->Ln();
        }
        
        // Línea de cierre
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln();
        
        // Total
        $this->SetFont('', 'B');
        $this->Cell(array_sum($w) - $w[4], 6, 'TOTAL:', 0, 0, 'R', true);
        $this->Cell($w[4], 6, 'Gs. ' . number_format($total, 0, ',', '.'), 0, 0, 'R', true);
        
        return $total;
    }
}
?>