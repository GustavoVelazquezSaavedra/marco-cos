-- Tabla para el slider principal
CREATE TABLE slider_principal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT,
    titulo VARCHAR(255),
    subtitulo VARCHAR(255),
    texto_boton VARCHAR(100) DEFAULT 'Ver Producto',
    imagen VARCHAR(255),
    orden INT DEFAULT 0,
    activo TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL
);

-- Insertar algunos slides de ejemplo
INSERT INTO slider_principal (producto_id, titulo, subtitulo, texto_boton, orden) VALUES 
(1, 'Colección Oro 18k', 'Descubre nuestra exclusiva línea en oro de 18 quilates', 'Ver Colección', 1),
(3, 'Cadenas Italianas', 'Elegancia y estilo en cada eslabón', 'Ver Cadenas', 2),
(5, 'Collares con Dijes', 'Personaliza tu estilo con nuestros dijes únicos', 'Ver Collares', 3);