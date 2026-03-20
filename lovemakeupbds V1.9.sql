-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-02-2026 a las 05:33:27
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `lovemakeupbds2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

CREATE TABLE `bitacora` (
  `id_bitacora` int(11) NOT NULL,
  `cedula` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` varchar(250) DEFAULT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo`
--

CREATE TABLE `modulo` (
  `id_modulo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `modulo`
--

INSERT INTO `modulo` (`id_modulo`, `nombre`) VALUES
(1, 'Reporte'),
(2, 'Compra'),
(3, 'Venta'),
(4, 'Reserva'),
(5, 'Pedido Web'),
(6, 'Producto'),
(7, 'Marca'),
(8, 'Categoria'),
(9, 'Proveedor'),
(10, 'Cliente'),
(11, 'Delivery'),
(12, 'Metodo Entrega'),
(13, 'Metodo Pago'),
(14, 'Tasa de Cambio'),
(15, 'Bitacora'),
(16, 'Usuario'),
(17, 'Tipo Usuario'),
(18, 'Notificaciones'),
(19, 'Lista de Deseos'),
(20, 'Ver Mis Datos'),
(21, 'Ver Mis Pedidos'),
(22, 'Ver Carrito'),
(23, 'Pedido Entrega'),
(24, 'Pedido Pago');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `id_permiso` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `permiso`
--

INSERT INTO `permiso` (`id_permiso`, `nombre`) VALUES
(1, 'Ver'),
(2, 'Registrar'),
(3, 'Modificar'),
(4, 'Eliminar'),
(5, 'Especial');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso_rol`
--

CREATE TABLE `permiso_rol` (
  `id_permiso_rol` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL,
  `estado` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `permiso_rol`
--

INSERT INTO `permiso_rol` (`id_permiso_rol`, `id_rol`, `id_modulo`, `id_permiso`, `estado`) VALUES
(1, 1, 1, 1, 1),
(2, 1, 2, 1, 1),
(3, 1, 2, 2, 1),
(4, 1, 2, 3, 1),
(5, 1, 3, 1, 1),
(6, 1, 3, 2, 1),
(7, 1, 4, 1, 1),
(8, 1, 4, 5, 1),
(9, 1, 5, 1, 1),
(10, 1, 5, 5, 1),
(11, 1, 6, 1, 1),
(12, 1, 6, 2, 1),
(13, 1, 6, 3, 1),
(14, 1, 6, 4, 1),
(15, 1, 6, 5, 1),
(16, 1, 7, 1, 1),
(17, 1, 7, 2, 1),
(18, 1, 7, 3, 1),
(19, 1, 7, 4, 1),
(20, 1, 8, 1, 1),
(21, 1, 8, 2, 1),
(22, 1, 8, 3, 1),
(23, 1, 8, 4, 1),
(24, 1, 9, 1, 1),
(25, 1, 9, 2, 1),
(26, 1, 9, 3, 1),
(27, 1, 9, 4, 1),
(28, 1, 10, 1, 1),
(29, 1, 10, 3, 1),
(30, 1, 11, 1, 1),
(31, 1, 11, 2, 1),
(32, 1, 11, 3, 1),
(33, 1, 11, 4, 1),
(34, 1, 12, 1, 1),
(35, 1, 12, 2, 1),
(36, 1, 12, 3, 1),
(37, 1, 12, 4, 1),
(38, 1, 13, 1, 1),
(39, 1, 13, 2, 1),
(40, 1, 13, 3, 1),
(41, 1, 13, 4, 1),
(42, 1, 14, 1, 1),
(43, 1, 14, 3, 1),
(44, 1, 15, 1, 1),
(45, 1, 15, 4, 1),
(46, 1, 16, 1, 1),
(47, 1, 16, 2, 1),
(48, 1, 16, 3, 1),
(49, 1, 16, 4, 1),
(50, 1, 17, 1, 1),
(51, 1, 17, 2, 1),
(52, 1, 17, 3, 1),
(53, 1, 17, 4, 1),
(54, 1, 17, 5, 1),
(55, 1, 18, 1, 1),
(56, 1, 18, 5, 1),
(57, 2, 19, 1, 1),
(58, 2, 19, 2, 1),
(59, 2, 19, 3, 1),
(60, 2, 19, 4, 1),
(61, 2, 20, 1, 1),
(62, 2, 20, 3, 1),
(63, 2, 20, 4, 1),
(64, 2, 21, 1, 1),
(65, 2, 22, 1, 1),
(66, 2, 22, 2, 1),
(67, 2, 23, 1, 1),
(68, 2, 23, 2, 1),
(69, 2, 24, 1, 1),
(70, 2, 24, 2, 1),
(71, 2, 24, 1, 1),
(72, 3, 1, 1, 1),
(73, 3, 3, 1, 1),
(74, 3, 3, 2, 1),
(75, 3, 4, 1, 1),
(76, 3, 4, 5, 1),
(77, 3, 5, 1, 1),
(78, 3, 5, 5, 1),
(79, 3, 6, 1, 1),
(80, 3, 6, 5, 1),
(81, 3, 10, 1, 1),
(82, 3, 10, 3, 1),
(83, 3, 14, 1, 1),
(84, 3, 14, 3, 1),
(85, 3, 18, 1, 1),
(86, 3, 18, 5, 1),
(87, 4, 1, 1, 1),
(88, 4, 2, 1, 1),
(89, 4, 2, 2, 1),
(90, 4, 2, 3, 1),
(91, 4, 3, 1, 1),
(92, 4, 3, 2, 1),
(93, 4, 4, 1, 1),
(94, 4, 4, 5, 1),
(95, 4, 5, 1, 1),
(96, 4, 5, 5, 1),
(97, 4, 6, 1, 1),
(98, 4, 6, 2, 1),
(99, 4, 6, 3, 1),
(100, 4, 6, 4, 1),
(101, 4, 6, 5, 1),
(102, 4, 7, 1, 1),
(103, 4, 7, 2, 1),
(104, 4, 7, 3, 1),
(105, 4, 7, 4, 1),
(106, 4, 8, 1, 1),
(107, 4, 8, 2, 1),
(108, 4, 8, 3, 1),
(109, 4, 8, 4, 1),
(110, 4, 9, 1, 1),
(111, 4, 9, 2, 1),
(112, 4, 9, 3, 1),
(113, 4, 9, 4, 1),
(114, 4, 10, 1, 1),
(115, 4, 10, 3, 1),
(116, 4, 11, 1, 1),
(117, 4, 11, 2, 1),
(118, 4, 11, 3, 1),
(119, 4, 11, 4, 1),
(120, 4, 12, 1, 1),
(121, 4, 12, 2, 1),
(122, 4, 12, 3, 1),
(123, 4, 12, 4, 1),
(124, 4, 13, 1, 1),
(125, 4, 13, 2, 1),
(126, 4, 13, 3, 1),
(127, 4, 13, 4, 1),
(128, 4, 14, 1, 1),
(129, 4, 14, 3, 1),
(130, 4, 15, 1, 1),
(131, 4, 15, 4, 1),
(132, 4, 16, 1, 1),
(133, 4, 16, 2, 1),
(134, 4, 16, 3, 1),
(135, 4, 16, 4, 1),
(136, 4, 17, 1, 1),
(137, 4, 17, 2, 1),
(138, 4, 17, 3, 1),
(139, 4, 17, 4, 1),
(140, 4, 17, 5, 1),
(141, 4, 18, 1, 1),
(142, 4, 18, 5, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `persona`
--

CREATE TABLE `persona` (
  `cedula` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `tipo_documento` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `persona`
--

INSERT INTO `persona` (`cedula`, `nombre`, `apellido`, `correo`, `telefono`, `tipo_documento`) VALUES
(10090080, 'Soporte', 'Dev', 'corre@gmail.com', '0424-1243265', 'V'),
(10200300, 'Jefe', 'Lovemakeup', 'correo@gmail.com', '0424-0000000', 'V'),
(15200300, 'Cajera', 'Makeup', 'cajera@gmail.com', '0414-0000000', 'V');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `nivel` int(1) DEFAULT NULL,
  `estatus` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre`, `nivel`, `estatus`) VALUES
(1, 'Desarrollador', 3, 1),
(2, 'Cliente', 1, 1),
(3, 'Asesora de Venta', 2, 1),
(4, 'Administrador', 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `cedula` int(11) NOT NULL,
  `clave` varchar(512) NOT NULL,
  `estatus` int(1) DEFAULT NULL,
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `cedula`, `clave`, `estatus`, `id_rol`) VALUES
(1, 10090080, 'c42t7J2RXdedsSUKKahzaE15eGhEcE1rbWs5ZWoxNU5vVEQrRUE9PQ==', 1, 1),
(2, 10200300, 'GJ2LRyGX9XUpCmkwhg+ibVFaV0dZQkNnSldQcVRaY281dStrWHc9PQ==', 1, 4),
(3, 15200300, 'UFx0aI+aFhDxgi0ZrYwEAXBtaHBwbFR2WHJvdUo4V3pIOGplbXc9PQ==', 1, 3);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`id_bitacora`),
  ADD KEY `cedula` (`cedula`);

--
-- Indices de la tabla `modulo`
--
ALTER TABLE `modulo`
  ADD PRIMARY KEY (`id_modulo`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`id_permiso`);

--
-- Indices de la tabla `permiso_rol`
--
ALTER TABLE `permiso_rol`
  ADD PRIMARY KEY (`id_permiso_rol`),
  ADD KEY `id_rol` (`id_rol`),
  ADD KEY `id_modulo` (`id_modulo`),
  ADD KEY `id_permiso` (`id_permiso`);

--
-- Indices de la tabla `persona`
--
ALTER TABLE `persona`
  ADD PRIMARY KEY (`cedula`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `cedula` (`cedula`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulo`
--
ALTER TABLE `modulo`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `permiso_rol`
--
ALTER TABLE `permiso_rol`
  MODIFY `id_permiso_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD CONSTRAINT `bitacora_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `persona` (`cedula`) ON DELETE CASCADE;

--
-- Filtros para la tabla `permiso_rol`
--
ALTER TABLE `permiso_rol`
  ADD CONSTRAINT `permiso_rol_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE CASCADE,
  ADD CONSTRAINT `permiso_rol_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`),
  ADD CONSTRAINT `permiso_rol_ibfk_3` FOREIGN KEY (`id_permiso`) REFERENCES `permiso` (`id_permiso`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `persona` (`cedula`) ON DELETE CASCADE,
  
  ADD CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`);
COMMIT;

ALTER TABLE `usuario` DROP FOREIGN KEY `usuario_ibfk_1`; ALTER TABLE `usuario` ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `persona`(`cedula`) ON DELETE CASCADE ON UPDATE CASCADE;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
