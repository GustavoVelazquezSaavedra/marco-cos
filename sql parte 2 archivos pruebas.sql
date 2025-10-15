-- Insertar categorías de joyería
INSERT INTO categorias (nombre, descripcion) VALUES 
('Anillos', 'Hermosa colección de anillos en oro y plata'),
('Cadenas', 'Cadenas elegantes para todo tipo de ocasiones'),
('Collares', 'Collares únicos con diseños exclusivos'),
('Pulseras', 'Pulseras de diferentes estilos y materiales'),
('Aretes', 'Aretes modernos y clásicos');

-- Insertar productos de ejemplo
INSERT INTO productos (categoria_id, nombre, descripcion, codigo, precio_publico, precio_real, stock, imagen) VALUES 
(1, 'Anillo Oro 18k Solitario', 'Anillo en oro de 18k con diamante solitario', 'ANI-001', 450.00, 320.00, 15, 'anillo-oro-solitario.jpg'),
(1, 'Anillo Plata Leyenda', 'Anillo en plata 925 con diseño moderno', 'ANI-002', 120.00, 85.00, 25, 'anillo-plata-moderno.jpg'),
(2, 'Cadena Oro 14k Italiana', 'Cadena italiana en oro 14k de 45cm', 'CAD-001', 680.00, 480.00, 8, 'cadena-oro-italiana.jpg'),
(2, 'Cad Plata Box 50cm', 'Cadena box en plata 925 de 50cm', 'CAD-002', 180.00, 120.00, 20, 'cadena-plata-box.jpg'),
(3, 'Collar Oro con Dijes', 'Collar en oro con dijes personalizables', 'COL-001', 890.00, 650.00, 5, 'collar-oro-dijes.jpg'),
(3, 'Collar Plata Corazón', 'Collar en plata con dije corazón', 'COL-002', 220.00, 150.00, 18, 'collar-plata-corazon.jpg'),
(4, 'Pulsera Oro Eslabones', 'Pulsera en oro con eslabones entrelazados', 'PUL-001', 350.00, 240.00, 12, 'pulsera-oro-eslabones.jpg'),
(4, 'Pulsera Plata Charm', 'Pulsera en plata con charms intercambiables', 'PUL-002', 190.00, 130.00, 22, 'pulsera-plata-charms.jpg');