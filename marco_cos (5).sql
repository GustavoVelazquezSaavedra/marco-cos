-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-11-2025 a las 12:29:29
-- Versión del servidor: 10.4.24-MariaDB
-- Versión de PHP: 7.4.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `marco_cos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `imagen`, `activo`, `fecha_creacion`) VALUES
(7, 'PERFUME MASCULINO', '', '', 1, '2025-11-18 22:22:10'),
(8, 'PERFUME ARABE', '', '', 1, '2025-11-18 22:23:19'),
(9, 'ARMAF', '', '', 1, '2025-11-18 22:29:58'),
(10, 'French Avenue', '', '', 1, '2025-11-18 22:30:50'),
(11, 'AFNAN', '', '', 1, '2025-11-18 22:34:50'),
(12, 'Rasasi', '', '', 1, '2025-11-18 22:37:30'),
(13, 'Lattafa', '', '', 1, '2025-11-18 22:40:43'),
(14, 'PERFUME FEMENINO', '', '', 1, '2025-11-18 22:43:06'),
(15, 'PERFUME ARABE FEMENINO', '', '691cf7a7820b5.jpg', 1, '2025-11-18 22:43:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones`
--

CREATE TABLE `configuraciones` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `configuraciones`
--

INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `descripcion`, `fecha_actualizacion`) VALUES
(1, 'titulo_sistema', 'BLOOM', 'Título principal del sistema', '2025-10-26 16:05:48'),
(2, 'subtitulo_sistema', 'Perfumes y cosmeticos', 'Subtítulo del sistema', '2025-10-26 16:22:53'),
(3, 'telefono', '+595976588694', 'Teléfono de la empresa', '2025-10-26 16:23:43'),
(4, 'horario', 'Lun-Vie 8:00-18:00', 'Horario de atención', '2025-10-26 16:05:48'),
(5, 'direccion', 'CDE', 'Dirección de la empresa', '2025-10-26 16:22:26'),
(6, 'footer_info', 'Sistema de Gestión', 'Información del footer', '2025-10-26 16:05:48'),
(7, 'email', 'bloom@bloom.com', 'Email de la empresa', '2025-10-26 16:22:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `tipo` enum('entrada','salida') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `documento` varchar(255) DEFAULT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `cliente_nombre` varchar(100) DEFAULT NULL,
  `cliente_telefono` varchar(20) DEFAULT NULL,
  `cliente_email` varchar(100) DEFAULT NULL,
  `productos` text NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','procesado','completado','cancelado') DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `presupuestos`
--

CREATE TABLE `presupuestos` (
  `id` int(11) NOT NULL,
  `cliente_nombre` varchar(255) NOT NULL,
  `cliente_telefono` varchar(20) NOT NULL,
  `cliente_documento` varchar(20) DEFAULT '0',
  `productos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`productos`)),
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','aceptado','rechazado') DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `moneda` enum('gs','usd') DEFAULT 'gs',
  `aplicar_iva` enum('si','no') DEFAULT 'no',
  `tipo_descuento` enum('','porcentaje','valor') DEFAULT '',
  `descuento_general` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `precio_publico` decimal(10,2) NOT NULL,
  `precio_real` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `descripcion`, `codigo`, `precio_publico`, `precio_real`, `stock`, `imagen`, `activo`, `fecha_creacion`) VALUES
(13, NULL, 'Asad Lattafa EDP 100ML', 'Notas de Salida: pimienta negra, tabaco y piña\r\nNotas de Corazón: pachulí, café y iris.\r\nNotas de Fondo: vainilla, ámbar, Madera seca, benjuí y ládano.', 'PROD-7', '70000.00', '6000.00', 15, '691cf22e6ef50.jpg', 1, '2025-11-18 22:23:04'),
(14, NULL, 'Club de Nuit Iconic EDP 100ML', 'Notas de salida: Toronja (pomelo), limón (lima ácida), menta, pimienta rosa y cilantro\r\nNotas de corazón: Jengibre, melón, nuez moscada y jazmín\r\nNotas de fondo: Ámbar, incienso, sándalo, notas amaderadas, cedro, pachulí y ládano', 'PROD-8', '70000.00', '60000.00', 15, '691cf32d6d860.jpg', 1, '2025-11-18 22:28:30'),
(15, NULL, 'Liquid Brun EDP 80ml', 'Notas de Salida: Canela, cardamomo, czahar y bergamota\r\nNotas de Corazón: Vainilla bourbon y elemí \r\nNotas de Fondo: Praliné, almizcle, ambroxan y madera de guayaco', 'PROD-9', '70000.00', '60000.00', 15, '691cf440ccbd6.png', 1, '2025-11-18 22:32:27'),
(16, NULL, '9pm Afnan EDP 100ML', 'Notas de salida: Manzana, canela, lavanda silvestre, bergamota\r\nNotas de corazón: Flor de naranja, lirio de los valles\r\nNotas de fondo: Vainilla, haba tonka, ámbar, pachulí', 'PROD-10', '70000.00', '60000.00', 15, '691cf4ea238cc.png', 1, '2025-11-18 22:36:26'),
(17, NULL, 'Hawas For Him Fire EDP 100ML', 'Nota de Salida: esclarea.\r\nNotas de Corazón: jazmín egipcio, notas marinas\r\nNotas de Fondo: ámbar, notas minerales y ámbar gris.', 'PROD-11', '70000.00', '60000.00', 15, '691cf5a69ede5.jpg', 1, '2025-11-18 22:38:14'),
(18, NULL, 'Asad Lattafa EDP 100ML', 'Notas de Salida: pimienta negra, tabaco y piña\r\nNotas de Corazón: pachulí, café y iris.\r\nNotas de Fondo: vainilla, ámbar, Madera seca, benjuí y ládano.', 'PROD-12', '70000.00', '60000.00', 15, '691cf65337255.jpg', 1, '2025-11-18 22:42:27'),
(19, NULL, 'Yara Elixir EDP 100ML', 'Las Notas de Salida: Strawberry S’mores y grosellas negras.\r\nNotas de Corazón: jazmín y flor de azahar del naranjo.\r\nNotas de Fondo: caramelo, vainilla, ámbar y almizcle.', 'PROD-13', '70000.00', '60000.00', 15, '691cf6fd3b29b.jpg', 1, '2025-11-18 22:45:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_categorias`
--

CREATE TABLE `producto_categorias` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `producto_categorias`
--

INSERT INTO `producto_categorias` (`id`, `producto_id`, `categoria_id`, `fecha_creacion`) VALUES
(7, 13, 8, '2025-11-18 22:24:46'),
(8, 13, 7, '2025-11-18 22:24:46'),
(13, 14, 9, '2025-11-18 22:30:06'),
(14, 14, 8, '2025-11-18 22:30:06'),
(15, 14, 7, '2025-11-18 22:30:06'),
(19, 15, 10, '2025-11-18 22:33:36'),
(20, 15, 8, '2025-11-18 22:33:36'),
(21, 15, 7, '2025-11-18 22:33:36'),
(22, 16, 11, '2025-11-18 22:36:26'),
(23, 16, 8, '2025-11-18 22:36:26'),
(24, 16, 7, '2025-11-18 22:36:26'),
(28, 17, 8, '2025-11-18 22:39:34'),
(29, 17, 7, '2025-11-18 22:39:34'),
(30, 17, 12, '2025-11-18 22:39:34'),
(31, 18, 13, '2025-11-18 22:42:27'),
(32, 18, 8, '2025-11-18 22:42:27'),
(33, 18, 7, '2025-11-18 22:42:27'),
(34, 19, 13, '2025-11-18 22:45:17'),
(35, 19, 15, '2025-11-18 22:45:17'),
(36, 19, 14, '2025-11-18 22:45:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `redes_sociales`
--

CREATE TABLE `redes_sociales` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icono` varchar(50) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `redes_sociales`
--

INSERT INTO `redes_sociales` (`id`, `nombre`, `url`, `icono`, `activo`, `orden`, `fecha_actualizacion`) VALUES
(1, 'Facebook', 'https://facebook.com/tupagina', 'fab fa-facebook-f', 1, 1, '2025-11-26 10:24:44'),
(2, 'Instagram', 'https://instagram.com/tupagina', 'fab fa-instagram', 1, 2, '2025-11-26 10:24:44'),
(3, 'WhatsApp', 'https://wa.me/595972366265', 'fab fa-whatsapp', 1, 3, '2025-11-26 10:28:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `slider_principal`
--

CREATE TABLE `slider_principal` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `texto_boton` varchar(100) DEFAULT 'Ver Producto',
  `imagen` varchar(255) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(4) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `slider_principal`
--

INSERT INTO `slider_principal` (`id`, `producto_id`, `titulo`, `subtitulo`, `texto_boton`, `imagen`, `orden`, `activo`, `fecha_creacion`) VALUES
(6, NULL, 'Asad Lattafa EDP 100ML', 'Notas de Salida: pimienta negra, tabaco, piña\r\npachulí, café y iris, vainilla, ámbar, Madera seca, benjuí y ládano.', 'Ver Producto', '691cf26e14436.jpg', 0, 1, '2025-11-18 22:25:50'),
(9, 14, 'Club de Nuit Iconic EDP 100ML', 'Club de Nuit Iconic EDP 100ML', 'Ver Producto', '691cf34f77a11.jpg', 1, 1, '2025-11-18 22:29:35'),
(10, 15, 'Liquid Brun EDP 80ml', '', 'Ver Producto', '691cf45574fbb.png', 2, 1, '2025-11-18 22:33:57'),
(11, 16, '9pm Afnan EDP 100ML', '', 'Ver Producto', '691cf50215791.png', 3, 1, '2025-11-18 22:36:50'),
(14, 17, 'Hawas For Him Fire EDP 100ML', '', 'Ver Producto', '691cf5c637bf2.jpg', 6, 1, '2025-11-18 22:40:06'),
(16, 19, 'Yara Elixir EDP 100ML', '', 'Ver Producto', '691cf716a1dca.jpg', 4, 1, '2025-11-18 22:45:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_cambio`
--

CREATE TABLE `tipo_cambio` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `compra` decimal(10,3) NOT NULL,
  `venta` decimal(10,3) NOT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `tipo_cambio`
--

INSERT INTO `tipo_cambio` (`id`, `fecha`, `compra`, `venta`, `activo`, `fecha_creacion`) VALUES
(1, '2025-10-20', '6960.000', '7060.000', 1, '2025-10-20 17:14:51'),
(2, '2025-10-22', '6960.000', '7060.000', 1, '2025-10-22 20:04:34'),
(3, '2025-10-26', '6960.000', '7060.000', 1, '2025-10-26 16:28:41'),
(4, '2025-10-27', '6960.000', '7060.000', 1, '2025-10-27 17:46:03'),
(5, '2025-10-28', '6960.000', '7060.000', 1, '2025-10-28 13:50:29'),
(6, '2025-11-07', '6960.000', '7060.000', 1, '2025-11-07 14:57:06'),
(7, '2025-11-18', '6960.000', '7060.000', 1, '2025-11-18 22:21:32'),
(8, '2025-11-20', '6960.000', '7060.000', 1, '2025-11-20 19:20:05'),
(9, '2025-11-26', '6960.000', '7060.000', 1, '2025-11-26 10:20:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','vendedor') DEFAULT 'vendedor',
  `activo` tinyint(4) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `activo`, `fecha_creacion`) VALUES
(1, 'Administrador', 'admin@bloom.com', '$2y$10$jC2cZ7dWelcFoXG9t1z3nuHeDyvulAY12yV7ytRnFYRvLdAugUkdO', 'admin', 1, '2025-10-15 14:25:43');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `presupuestos`
--
ALTER TABLE `presupuestos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `producto_categorias`
--
ALTER TABLE `producto_categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_producto_categoria` (`producto_id`,`categoria_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `redes_sociales`
--
ALTER TABLE `redes_sociales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `slider_principal`
--
ALTER TABLE `slider_principal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `tipo_cambio`
--
ALTER TABLE `tipo_cambio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_fecha` (`fecha`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `presupuestos`
--
ALTER TABLE `presupuestos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `producto_categorias`
--
ALTER TABLE `producto_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `redes_sociales`
--
ALTER TABLE `redes_sociales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `slider_principal`
--
ALTER TABLE `slider_principal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `tipo_cambio`
--
ALTER TABLE `tipo_cambio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `inventario_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `producto_categorias`
--
ALTER TABLE `producto_categorias`
  ADD CONSTRAINT `producto_categorias_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `producto_categorias_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `slider_principal`
--
ALTER TABLE `slider_principal`
  ADD CONSTRAINT `slider_principal_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
