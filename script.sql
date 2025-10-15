CREATE DATABASE marco_cos;
USE marco_cos;

-- Tabla de usuarios administradores
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'vendedor') DEFAULT 'vendedor',
    activo TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de categorías
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    activo TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria_id INT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    codigo VARCHAR(50) UNIQUE,
    precio_publico DECIMAL(10,2) NOT NULL,
    precio_real DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    imagen VARCHAR(255),
    activo TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabla de movimientos de inventario
CREATE TABLE inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(255),
    usuario_id INT,
    documento VARCHAR(255),
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla de pedidos del e-commerce
CREATE TABLE pedidos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_nombre VARCHAR(100),
    cliente_telefono VARCHAR(20),
    cliente_email VARCHAR(100),
    productos TEXT NOT NULL, -- JSON con los productos
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'procesado', 'completado', 'cancelado') DEFAULT 'pendiente',
    notas TEXT,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar usuario admin por defecto (contraseña: admin123)
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Administrador', 'admin@marccos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');