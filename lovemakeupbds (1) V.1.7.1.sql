-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-11-2025 a las 05:38:26
-- Versión del servidor: 10.1.9-MariaDB
-- Versión de PHP: 8.0.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `lovemakeupbds1`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

CREATE TABLE `bitacora` (
  `id_bitacora` int(11) NOT NULL,
  `cedula` varchar(15) NOT NULL,
  `accion` varchar(250) DEFAULT NULL,
  `descripcion` varchar(250) DEFAULT NULL,
  `fecha_hora` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo`
--

CREATE TABLE `modulo` (
  `id_modulo` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `modulo`
--

INSERT INTO `modulo` (`id_modulo`, `nombre`) VALUES
(1, 'reporte'),
(2, 'Compra'),
(3, 'Venta'),
(4, 'Reserva'),
(5, 'Pedidoweb'),
(6, 'Producto'),
(7, 'Categoria'),
(8, 'Marca'),
(9, 'Proveedor'),
(10, 'Cliente'),
(11, 'Delivery'),
(12, 'MetodoEntrega'),
(13, 'MetodoPago'),
(14, 'Tasa'),
(15, 'Bitacora'),
(16, 'Usuario'),
(17, 'TipoUsuario'),
(18, 'Notificaciones');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `id_permiso` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `cedula` varchar(15) NOT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `permiso`
--

INSERT INTO `permiso` (`id_permiso`, `id_modulo`, `cedula`, `accion`, `estado`) VALUES
(1, 1, '10080030', 'ver', 1),
(2, 2, '10080030', 'ver', 1),
(3, 2, '10080030', 'registrar', 1),
(4, 2, '10080030', 'editar', 1),
(5, 3, '10080030', 'ver', 1),
(6, 3, '10080030', 'registrar', 1),
(7, 4, '10080030', 'ver', 1),
(8, 4, '10080030', 'especial', 1),
(9, 5, '10080030', 'ver', 1),
(10, 5, '10080030', 'especial', 1),
(11, 6, '10080030', 'ver', 1),
(12, 6, '10080030', 'registrar', 1),
(13, 6, '10080030', 'editar', 1),
(14, 6, '10080030', 'eliminar', 1),
(15, 6, '10080030', 'especial', 1),
(16, 7, '10080030', 'ver', 1),
(17, 7, '10080030', 'registrar', 1),
(18, 7, '10080030', 'editar', 1),
(19, 7, '10080030', 'eliminar', 1),
(20, 8, '10080030', 'ver', 1),
(21, 8, '10080030', 'registrar', 1),
(22, 8, '10080030', 'editar', 1),
(23, 8, '10080030', 'eliminar', 1),
(24, 9, '10080030', 'ver', 1),
(25, 9, '10080030', 'registrar', 1),
(26, 9, '10080030', 'editar', 1),
(27, 9, '10080030', 'eliminar', 1),
(28, 10, '10080030', 'ver', 1),
(29, 10, '10080030', 'editar', 1),
(30, 11, '10080030', 'ver', 1),
(31, 11, '10080030', 'registrar', 1),
(32, 11, '10080030', 'editar', 1),
(33, 11, '10080030', 'eliminar', 1),
(34, 12, '10080030', 'ver', 1),
(35, 12, '10080030', 'registrar', 1),
(36, 12, '10080030', 'editar', 1),
(37, 12, '10080030', 'eliminar', 1),
(38, 13, '10080030', 'ver', 1),
(39, 13, '10080030', 'registrar', 1),
(40, 13, '10080030', 'editar', 1),
(41, 13, '10080030', 'eliminar', 1),
(42, 14, '10080030', 'ver', 1),
(43, 14, '10080030', 'editar', 1),
(44, 15, '10080030', 'ver', 1),
(45, 15, '10080030', 'eliminar', 1),
(46, 16, '10080030', 'ver', 1),
(47, 16, '10080030', 'registrar', 1),
(48, 16, '10080030', 'editar', 1),
(49, 16, '10080030', 'eliminar', 1),
(50, 16, '10080030', 'especial', 1),
(51, 17, '10080030', 'ver', 1),
(52, 17, '10080030', 'registrar', 1),
(53, 17, '10080030', 'editar', 1),
(54, 17, '10080030', 'eliminar', 1),
(55, 18, '10080030', 'ver', 1),
(56, 18, '10080030', 'especial', 1),
(57, 1, '10200300', 'ver', 1),
(58, 2, '10200300', 'ver', 1),
(59, 2, '10200300', 'registrar', 1),
(60, 2, '10200300', 'editar', 1),
(61, 3, '10200300', 'ver', 1),
(62, 3, '10200300', 'registrar', 1),
(63, 4, '10200300', 'ver', 1),
(64, 4, '10200300', 'especial', 1),
(65, 5, '10200300', 'ver', 1),
(66, 5, '10200300', 'especial', 1),
(67, 6, '10200300', 'ver', 1),
(68, 6, '10200300', 'registrar', 1),
(69, 6, '10200300', 'editar', 1),
(70, 6, '10200300', 'eliminar', 1),
(71, 6, '10200300', 'especial', 1),
(72, 7, '10200300', 'ver', 1),
(73, 7, '10200300', 'registrar', 1),
(74, 7, '10200300', 'editar', 1),
(75, 7, '10200300', 'eliminar', 1),
(76, 8, '10200300', 'ver', 1),
(77, 8, '10200300', 'registrar', 1),
(78, 8, '10200300', 'editar', 1),
(79, 8, '10200300', 'eliminar', 1),
(80, 9, '10200300', 'ver', 1),
(81, 9, '10200300', 'registrar', 1),
(82, 9, '10200300', 'editar', 1),
(83, 9, '10200300', 'eliminar', 1),
(84, 10, '10200300', 'ver', 1),
(85, 10, '10200300', 'editar', 1),
(86, 11, '10200300', 'ver', 1),
(87, 11, '10200300', 'registrar', 1),
(88, 11, '10200300', 'editar', 1),
(89, 11, '10200300', 'eliminar', 1),
(90, 12, '10200300', 'ver', 1),
(91, 12, '10200300', 'registrar', 1),
(92, 12, '10200300', 'editar', 1),
(93, 12, '10200300', 'eliminar', 1),
(94, 13, '10200300', 'ver', 1),
(95, 13, '10200300', 'registrar', 1),
(96, 13, '10200300', 'editar', 1),
(97, 13, '10200300', 'eliminar', 1),
(98, 14, '10200300', 'ver', 1),
(99, 14, '10200300', 'editar', 1),
(100, 15, '10200300', 'ver', 1),
(101, 15, '10200300', 'eliminar', 1),
(102, 16, '10200300', 'ver', 1),
(103, 16, '10200300', 'registrar', 1),
(104, 16, '10200300', 'editar', 1),
(105, 16, '10200300', 'eliminar', 1),
(106, 16, '10200300', 'especial', 1),
(107, 17, '10200300', 'ver', 1),
(108, 17, '10200300', 'registrar', 1),
(109, 17, '10200300', 'editar', 1),
(110, 17, '10200300', 'eliminar', 1),
(111, 18, '10200300', 'ver', 1),
(112, 18, '10200300', 'especial', 1),
(113, 1, '12500600', 'ver', 1),
(114, 3, '12500600', 'ver', 1),
(115, 3, '12500600', 'registrar', 1),
(116, 4, '12500600', 'ver', 1),
(117, 4, '12500600', 'especial', 1),
(118, 5, '12500600', 'ver', 1),
(119, 5, '12500600', 'especial', 1),
(120, 6, '12500600', 'ver', 1),
(121, 6, '12500600', 'registrar', 0),
(122, 6, '12500600', 'editar', 0),
(123, 6, '12500600', 'eliminar', 0),
(124, 6, '12500600', 'especial', 0),
(125, 10, '12500600', 'ver', 1),
(126, 10, '12500600', 'editar', 0),
(127, 14, '12500600', 'ver', 1),
(128, 14, '12500600', 'editar', 1),
(129, 18, '12500600', 'ver', 1),
(130, 18, '12500600', 'especial', 1),
(131, 1, '28690885', 'ver', 1),
(132, 2, '28690885', 'ver', 1),
(133, 2, '28690885', 'registrar', 1),
(134, 2, '28690885', 'editar', 1),
(135, 3, '28690885', 'ver', 1),
(136, 3, '28690885', 'registrar', 1),
(137, 4, '28690885', 'ver', 1),
(138, 4, '28690885', 'especial', 1),
(139, 5, '28690885', 'ver', 1),
(140, 5, '28690885', 'especial', 1),
(141, 6, '28690885', 'ver', 1),
(142, 6, '28690885', 'registrar', 1),
(143, 6, '28690885', 'editar', 1),
(144, 6, '28690885', 'eliminar', 1),
(145, 6, '28690885', 'especial', 1),
(146, 7, '28690885', 'ver', 1),
(147, 7, '28690885', 'registrar', 1),
(148, 7, '28690885', 'editar', 1),
(149, 7, '28690885', 'eliminar', 1),
(150, 8, '28690885', 'ver', 1),
(151, 8, '28690885', 'registrar', 1),
(152, 8, '28690885', 'editar', 1),
(153, 8, '28690885', 'eliminar', 1),
(154, 9, '28690885', 'ver', 1),
(155, 9, '28690885', 'registrar', 1),
(156, 9, '28690885', 'editar', 1),
(157, 9, '28690885', 'eliminar', 1),
(158, 10, '28690885', 'ver', 1),
(159, 10, '28690885', 'editar', 1),
(160, 11, '28690885', 'ver', 1),
(161, 11, '28690885', 'registrar', 1),
(162, 11, '28690885', 'editar', 1),
(163, 11, '28690885', 'eliminar', 1),
(164, 12, '28690885', 'ver', 1),
(165, 12, '28690885', 'registrar', 1),
(166, 12, '28690885', 'editar', 1),
(167, 12, '28690885', 'eliminar', 1),
(168, 13, '28690885', 'ver', 1),
(169, 13, '28690885', 'registrar', 1),
(170, 13, '28690885', 'editar', 1),
(171, 13, '28690885', 'eliminar', 1),
(172, 14, '28690885', 'ver', 1),
(173, 14, '28690885', 'editar', 1),
(174, 15, '28690885', 'ver', 1),
(175, 15, '28690885', 'eliminar', 1),
(176, 16, '28690885', 'ver', 1),
(177, 16, '28690885', 'registrar', 1),
(178, 16, '28690885', 'editar', 1),
(179, 16, '28690885', 'eliminar', 1),
(180, 16, '28690885', 'especial', 1),
(181, 17, '28690885', 'ver', 1),
(182, 17, '28690885', 'registrar', 1),
(183, 17, '28690885', 'editar', 1),
(184, 17, '28690885', 'eliminar', 1),
(185, 18, '28690885', 'ver', 1),
(186, 18, '28690885', 'especial', 1),
(187, 1, '31271852', 'ver', 1),
(188, 2, '31271852', 'ver', 1),
(189, 2, '31271852', 'registrar', 1),
(190, 2, '31271852', 'editar', 1),
(191, 3, '31271852', 'ver', 1),
(192, 3, '31271852', 'registrar', 1),
(193, 4, '31271852', 'ver', 1),
(194, 4, '31271852', 'especial', 1),
(195, 5, '31271852', 'ver', 1),
(196, 5, '31271852', 'especial', 1),
(197, 6, '31271852', 'ver', 1),
(198, 6, '31271852', 'registrar', 1),
(199, 6, '31271852', 'editar', 1),
(200, 6, '31271852', 'eliminar', 1),
(201, 6, '31271852', 'especial', 1),
(202, 7, '31271852', 'ver', 1),
(203, 7, '31271852', 'registrar', 1),
(204, 7, '31271852', 'editar', 1),
(205, 7, '31271852', 'eliminar', 1),
(206, 8, '31271852', 'ver', 1),
(207, 8, '31271852', 'registrar', 1),
(208, 8, '31271852', 'editar', 1),
(209, 8, '31271852', 'eliminar', 1),
(210, 9, '31271852', 'ver', 1),
(211, 9, '31271852', 'registrar', 1),
(212, 9, '31271852', 'editar', 1),
(213, 9, '31271852', 'eliminar', 1),
(214, 10, '31271852', 'ver', 1),
(215, 10, '31271852', 'editar', 1),
(216, 11, '31271852', 'ver', 1),
(217, 11, '31271852', 'registrar', 1),
(218, 11, '31271852', 'editar', 1),
(219, 11, '31271852', 'eliminar', 1),
(220, 12, '31271852', 'ver', 1),
(221, 12, '31271852', 'registrar', 1),
(222, 12, '31271852', 'editar', 1),
(223, 12, '31271852', 'eliminar', 1),
(224, 13, '31271852', 'ver', 1),
(225, 13, '31271852', 'registrar', 1),
(226, 13, '31271852', 'editar', 1),
(227, 13, '31271852', 'eliminar', 1),
(228, 14, '31271852', 'ver', 1),
(229, 14, '31271852', 'editar', 1),
(230, 15, '31271852', 'ver', 1),
(231, 15, '31271852', 'eliminar', 1),
(232, 16, '31271852', 'ver', 1),
(233, 16, '31271852', 'registrar', 1),
(234, 16, '31271852', 'editar', 1),
(235, 16, '31271852', 'eliminar', 1),
(236, 16, '31271852', 'especial', 1),
(237, 17, '31271852', 'ver', 1),
(238, 17, '31271852', 'registrar', 1),
(239, 17, '31271852', 'editar', 1),
(240, 17, '31271852', 'eliminar', 1),
(241, 18, '31271852', 'ver', 1),
(242, 18, '31271852', 'especial', 1),
(243, 1, '30559878', 'ver', 1),
(244, 2, '30559878', 'ver', 1),
(245, 2, '30559878', 'registrar', 1),
(246, 2, '30559878', 'editar', 1),
(247, 3, '30559878', 'ver', 1),
(248, 3, '30559878', 'registrar', 1),
(249, 4, '30559878', 'ver', 1),
(250, 4, '30559878', 'especial', 1),
(251, 5, '30559878', 'ver', 1),
(252, 5, '30559878', 'especial', 1),
(253, 6, '30559878', 'ver', 1),
(254, 6, '30559878', 'registrar', 1),
(255, 6, '30559878', 'editar', 1),
(256, 6, '30559878', 'eliminar', 1),
(257, 6, '30559878', 'especial', 1),
(258, 7, '30559878', 'ver', 1),
(259, 7, '30559878', 'registrar', 1),
(260, 7, '30559878', 'editar', 1),
(261, 7, '30559878', 'eliminar', 1),
(262, 8, '30559878', 'ver', 1),
(263, 8, '30559878', 'registrar', 1),
(264, 8, '30559878', 'editar', 1),
(265, 8, '30559878', 'eliminar', 1),
(266, 9, '30559878', 'ver', 1),
(267, 9, '30559878', 'registrar', 1),
(268, 9, '30559878', 'editar', 1),
(269, 9, '30559878', 'eliminar', 1),
(270, 10, '30559878', 'ver', 1),
(271, 10, '30559878', 'editar', 1),
(272, 11, '30559878', 'ver', 1),
(273, 11, '30559878', 'registrar', 1),
(274, 11, '30559878', 'editar', 1),
(275, 11, '30559878', 'eliminar', 1),
(276, 12, '30559878', 'ver', 1),
(277, 12, '30559878', 'registrar', 1),
(278, 12, '30559878', 'editar', 1),
(279, 12, '30559878', 'eliminar', 1),
(280, 13, '30559878', 'ver', 1),
(281, 13, '30559878', 'registrar', 1),
(282, 13, '30559878', 'editar', 1),
(283, 13, '30559878', 'eliminar', 1),
(284, 14, '30559878', 'ver', 1),
(285, 14, '30559878', 'editar', 1),
(286, 15, '30559878', 'ver', 1),
(287, 15, '30559878', 'eliminar', 1),
(288, 16, '30559878', 'ver', 1),
(289, 16, '30559878', 'registrar', 1),
(290, 16, '30559878', 'editar', 1),
(291, 16, '30559878', 'eliminar', 1),
(292, 16, '30559878', 'especial', 1),
(293, 17, '30559878', 'ver', 1),
(294, 17, '30559878', 'registrar', 1),
(295, 17, '30559878', 'editar', 1),
(296, 17, '30559878', 'eliminar', 1),
(297, 18, '30559878', 'ver', 1),
(298, 18, '30559878', 'especial', 1),
(299, 1, '30753995', 'ver', 1),
(300, 2, '30753995', 'ver', 1),
(301, 2, '30753995', 'registrar', 1),
(302, 2, '30753995', 'editar', 1),
(303, 3, '30753995', 'ver', 1),
(304, 3, '30753995', 'registrar', 1),
(305, 4, '30753995', 'ver', 1),
(306, 4, '30753995', 'especial', 1),
(307, 5, '30753995', 'ver', 1),
(308, 5, '30753995', 'especial', 1),
(309, 6, '30753995', 'ver', 1),
(310, 6, '30753995', 'registrar', 1),
(311, 6, '30753995', 'editar', 1),
(312, 6, '30753995', 'eliminar', 1),
(313, 6, '30753995', 'especial', 1),
(314, 7, '30753995', 'ver', 1),
(315, 7, '30753995', 'registrar', 1),
(316, 7, '30753995', 'editar', 1),
(317, 7, '30753995', 'eliminar', 1),
(318, 8, '30753995', 'ver', 1),
(319, 8, '30753995', 'registrar', 1),
(320, 8, '30753995', 'editar', 1),
(321, 8, '30753995', 'eliminar', 1),
(322, 9, '30753995', 'ver', 1),
(323, 9, '30753995', 'registrar', 1),
(324, 9, '30753995', 'editar', 1),
(325, 9, '30753995', 'eliminar', 1),
(326, 10, '30753995', 'ver', 1),
(327, 10, '30753995', 'editar', 1),
(328, 11, '30753995', 'ver', 1),
(329, 11, '30753995', 'registrar', 1),
(330, 11, '30753995', 'editar', 1),
(331, 11, '30753995', 'eliminar', 1),
(332, 12, '30753995', 'ver', 1),
(333, 12, '30753995', 'registrar', 1),
(334, 12, '30753995', 'editar', 1),
(335, 12, '30753995', 'eliminar', 1),
(336, 13, '30753995', 'ver', 1),
(337, 13, '30753995', 'registrar', 1),
(338, 13, '30753995', 'editar', 1),
(339, 13, '30753995', 'eliminar', 1),
(340, 14, '30753995', 'ver', 1),
(341, 14, '30753995', 'editar', 1),
(342, 15, '30753995', 'ver', 1),
(343, 15, '30753995', 'eliminar', 1),
(344, 16, '30753995', 'ver', 1),
(345, 16, '30753995', 'registrar', 1),
(346, 16, '30753995', 'editar', 1),
(347, 16, '30753995', 'eliminar', 1),
(348, 16, '30753995', 'especial', 1),
(349, 17, '30753995', 'ver', 1),
(350, 17, '30753995', 'registrar', 1),
(351, 17, '30753995', 'editar', 1),
(352, 17, '30753995', 'eliminar', 1),
(353, 18, '30753995', 'ver', 1),
(354, 18, '30753995', 'especial', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `persona`
--

CREATE TABLE `persona` (
  `cedula` varchar(15) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `tipo_documento` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `persona`
--

INSERT INTO `persona` (`cedula`, `nombre`, `apellido`, `correo`, `telefono`, `tipo_documento`) VALUES
('10080030', 'Soporte', 'Tecnico', 'danielsanchez7875@hotmail.com', '0414-9739941', 'E'),
('10200300', 'Jefe', 'Lovemakeup', 'love@gmail.com', '0424-0000000', 'V'),
('12500600', 'Maria', 'Perez', 'love2@gmail.com', '0424-0000000', 'V'),
('28690885', 'Angel', 'Sanchez', 'angel@gmail.com', '0424-0000000', 'V'),
('30559878', 'Erick', 'Torrealba', 'erick@gmail.com', '0424-0000000', 'V'),
('30753995', 'Rhichard', 'Virguez', 'rhichard@gmail.com', '0424-0000000', 'V'),
('31271852', 'Miguel', 'Torres', 'miguel@gmail.com', '0424-0000000', 'V');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_usuario`
--

CREATE TABLE `rol_usuario` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `nivel` int(1) DEFAULT NULL,
  `estatus` int(2) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `rol_usuario`
--

INSERT INTO `rol_usuario` (`id_rol`, `nombre`, `nivel`, `estatus`) VALUES
(1, 'Cliente', 1, 1),
(2, 'Asesora de Venta', 2, 1),
(3, 'Administrador', 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `cedula` varchar(15) NOT NULL,
  `clave` varchar(512) NOT NULL,
  `estatus` int(2) DEFAULT '1',
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `cedula`, `clave`, `estatus`, `id_rol`) VALUES
(1, '10080030', 'ugeSUdeq0Y2+5Btbwg/a6nk0TU5QZzkvMXBhZUM3SXlrSXJJS1E9PQ==', 1, 3),
(2, '10200300', 'c42t7J2RXdedsSUKKahzaE15eGhEcE1rbWs5ZWoxNU5vVEQrRUE9PQ==', 1, 3),
(4, '12500600', '84ILxZhWqheLY01PUenoeVFPM0FaRkVLQ3h0K3MyeHdVN0N6cWc9PQ==', 1, 2),
(5, '28690885', 'FEVklMVmBQzOXktuwCfBo2w2MCsvc2lOWGtjQURaS0E5RUpBaUE9PQ==', 1, 3),
(6, '31271852', 'iUqSXb1PfOXNWubmMR4xMUZhTFVWRFZaMlRLbWw0bHVaajBWdlE9PQ==', 1, 3),
(7, '30559878', 'WlmLR1Hl2AbWNY4OyY5JL2VaUUIraExHWkpIeHRjK0QybmdXQkE9PQ==', 1, 3),
(8, '30753995', '+xZ4m0iD4N2rYSZOo7tchHFTWTg0bnNRVHV0bWVwTVB0ZjJmYWc9PQ==', 1, 3);

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
  ADD PRIMARY KEY (`id_permiso`),
  ADD KEY `id_modulo` (`id_modulo`),
  ADD KEY `cedula` (`cedula`);

--
-- Indices de la tabla `persona`
--
ALTER TABLE `persona`
  ADD PRIMARY KEY (`cedula`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `tipo_documento` (`tipo_documento`);

--
-- Indices de la tabla `rol_usuario`
--
ALTER TABLE `rol_usuario`
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
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=355;

--
-- AUTO_INCREMENT de la tabla `rol_usuario`
--
ALTER TABLE `rol_usuario`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD CONSTRAINT `bitacora_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `persona` (`cedula`);

--
-- Filtros para la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD CONSTRAINT `permiso_ibfk_1` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`),
  ADD CONSTRAINT `permiso_ibfk_2` FOREIGN KEY (`cedula`) REFERENCES `persona` (`cedula`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`cedula`) REFERENCES `persona` (`cedula`),
  ADD CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `rol_usuario` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
