-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 27-04-2026 a las 23:32:15
-- Versión del servidor: 8.4.8
-- Versión de PHP: 8.3.26

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `efservice_vue`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `memberships`
--

CREATE TABLE `memberships` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `pricing_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'plan',
  `billing_period` enum('weekly','monthly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `carrier_price` decimal(10,2) DEFAULT NULL,
  `driver_price` decimal(10,2) DEFAULT NULL,
  `vehicle_price` decimal(10,2) DEFAULT NULL,
  `max_carrier` int NOT NULL,
  `max_drivers` int NOT NULL,
  `max_vehicles` int NOT NULL,
  `image_membership` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `show_in_register` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `memberships_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `memberships`
--

INSERT INTO `memberships` (`id`, `name`, `description`, `price`, `pricing_type`, `billing_period`, `carrier_price`, `driver_price`, `vehicle_price`, `max_carrier`, `max_drivers`, `max_vehicles`, `image_membership`, `status`, `show_in_register`, `created_at`, `updated_at`) VALUES
(4, 'Lone Hauler', 'Plan for 1 Driver', 75.01, 'plan', 'weekly', NULL, NULL, NULL, 1, 1, 2, NULL, 1, 1, '2025-05-21 23:27:42', '2026-04-27 23:28:18'),
(5, 'Dual Drive', 'Plan for 2 Drivers', 100.00, 'plan', 'weekly', NULL, NULL, NULL, 1, 2, 4, NULL, 1, 1, '2025-05-21 23:31:10', '2025-05-21 23:37:34'),
(6, 'Triple Track', 'Plan for 3 Drivers', 130.00, 'plan', 'weekly', NULL, NULL, NULL, 1, 3, 6, NULL, 1, 1, '2025-05-21 23:33:35', '2025-12-11 05:36:57'),
(7, 'Quad Road Tier', 'Plan for 4 Drivers', 160.00, 'plan', 'weekly', NULL, NULL, NULL, 1, 4, 8, NULL, 1, 1, '2025-05-21 23:35:39', '2026-04-27 23:29:36'),
(8, 'Five Axle Plan', 'Plan for 5 Drivers', 190.00, 'plan', 'weekly', NULL, NULL, NULL, 2, 5, 10, NULL, 1, 1, '2025-05-21 23:39:08', '2026-04-27 23:29:41'),
(9, 'FleetStart 6', 'Plan for 6 Drivers', 210.00, 'plan', 'weekly', NULL, NULL, NULL, 2, 6, 12, NULL, 1, 1, '2025-05-21 23:41:53', '2026-04-27 23:29:45'),
(10, 'FleetStart 7', 'Plan for 7 Drivers', 239.99, 'plan', 'weekly', NULL, NULL, NULL, 2, 7, 14, NULL, 1, 1, '2025-05-21 23:42:45', '2026-04-27 23:29:48'),
(11, 'FleetForce 8', 'Plan for 8 Drivers', 270.00, 'plan', 'weekly', NULL, NULL, NULL, 2, 8, 16, NULL, 1, 1, '2025-05-21 23:43:32', '2026-04-27 23:29:52'),
(12, 'FleetForce 9', 'Plan for 9 Drivers', 300.00, 'plan', 'weekly', NULL, NULL, NULL, 2, 9, 18, NULL, 1, 1, '2025-05-21 23:44:31', '2026-04-27 23:29:56'),
(13, 'Fleet Ten', 'Plan for 10 Drivers', 330.00, 'plan', 'weekly', NULL, NULL, NULL, 3, 10, 20, NULL, 1, 1, '2025-05-21 23:45:40', '2026-04-27 23:30:00'),
(14, 'FleetSurge 11', 'Plan for 11 Drivers', 350.00, 'plan', 'weekly', NULL, NULL, NULL, 3, 11, 22, NULL, 1, 1, '2025-05-21 23:49:24', '2026-04-27 23:30:11'),
(15, 'FleetSurge 12', 'Plan for 12 Drivers', 370.00, 'plan', 'weekly', NULL, NULL, NULL, 3, 12, 24, NULL, 1, 1, '2025-05-21 23:50:27', '2026-04-27 23:30:15'),
(16, 'FleetSurge 13', 'Plan for 13 Drivers', 390.00, 'plan', 'weekly', NULL, NULL, NULL, 3, 13, 26, NULL, 1, 1, '2025-05-21 23:51:10', '2026-04-27 23:30:18'),
(17, 'FleetSurge 14', 'Plan for 14 Drivers', 410.00, 'plan', 'weekly', NULL, NULL, NULL, 3, 14, 28, NULL, 1, 1, '2025-05-21 23:52:02', '2026-04-27 23:31:16'),
(18, 'FleetSurge 15', 'Plan for 15 Drivers', 430.00, 'plan', 'weekly', NULL, NULL, NULL, 4, 15, 30, NULL, 1, 1, '2025-05-21 23:52:59', '2026-04-27 23:31:20'),
(19, 'FleetSurge 16', 'Plan for 16 Drivers', 450.00, 'plan', 'weekly', NULL, NULL, NULL, 4, 16, 32, NULL, 1, 1, '2025-05-21 23:59:02', '2026-04-27 23:31:24'),
(20, 'FleetSurge 17', 'Plan for 17 Drivers', 470.00, 'plan', 'weekly', NULL, NULL, NULL, 4, 17, 34, NULL, 1, 1, '2025-05-21 23:59:57', '2026-04-27 23:31:27'),
(21, 'FleetSurge 18', 'Plan for 18 Drivers', 490.00, 'plan', 'weekly', NULL, NULL, NULL, 4, 18, 36, NULL, 1, 1, '2025-05-22 00:00:58', '2026-04-27 23:31:31'),
(22, 'FleetSurge 19', 'Plan for 19 Drivers', 510.00, 'plan', 'weekly', NULL, NULL, NULL, 4, 19, 38, NULL, 1, 1, '2025-05-22 00:01:43', '2026-04-27 23:31:34'),
(23, 'FleetSurge 20', 'Plan for 20 Drivers', 530.00, 'plan', 'weekly', NULL, NULL, NULL, 5, 20, 40, NULL, 1, 1, '2025-05-22 00:02:35', '2026-04-27 23:31:37'),
(24, 'Big50', 'Big50', 625.00, 'plan', 'weekly', NULL, NULL, NULL, 1, 50, 100, NULL, 1, 1, '2026-03-24 21:15:40', '2026-04-27 23:31:40');

COMMIT;
SET FOREIGN_KEY_CHECKS=1;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
