CREATE TABLE tipo_cambio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fecha DATE NOT NULL,
    compra DECIMAL(10,3) NOT NULL,
    venta DECIMAL(10,3) NOT NULL,
    activo TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fecha (fecha)
);