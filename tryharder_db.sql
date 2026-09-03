-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 30, 2026 at 12:59 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tryharder_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `booking_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `station_id` int NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `duration_hrs` decimal(4,1) NOT NULL,
  `end_time` time NOT NULL,
  `total_cost` decimal(8,2) NOT NULL,
  `status` enum('scheduled','active','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  KEY `fk_bookings_user` (`user_id`),
  KEY `fk_bookings_station` (`station_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `station_id`, `booking_date`, `start_time`, `duration_hrs`, `end_time`, `total_cost`, `status`, `created_at`) VALUES
(4, 6, 15, '2026-06-05', '16:00:00', 4.0, '20:00:00', 20.00, 'cancelled', '2026-05-29 23:06:37'),
(5, 6, 10, '2026-06-01', '10:00:00', 4.0, '14:00:00', 20.00, 'scheduled', '2026-05-29 23:10:26');

-- --------------------------------------------------------

--
-- Table structure for table `stations`
--

DROP TABLE IF EXISTS `stations`;
CREATE TABLE IF NOT EXISTS `stations` (
  `station_id` int NOT NULL AUTO_INCREMENT,
  `station_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone` enum('VIP','Standard') COLLATE utf8mb4_unicode_ci NOT NULL,
  `specs` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate_per_hour` decimal(5,2) NOT NULL DEFAULT '5.00',
  `status` enum('available','occupied','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  PRIMARY KEY (`station_id`),
  UNIQUE KEY `station_code` (`station_code`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stations`
--

INSERT INTO `stations` (`station_id`, `station_code`, `zone`, `specs`, `rate_per_hour`, `status`) VALUES
(1, 'VIP-01', 'VIP', 'RTX 4090 • 32GB DDR5 • 360Hz Display', 6.00, 'available'),
(2, 'VIP-02', 'VIP', 'RTX 4090 • 32GB DDR5 • 360Hz Display', 6.00, 'available'),
(3, 'VIP-03', 'VIP', 'RTX 4090 • 32GB DDR5 • 360Hz Display', 6.00, 'available'),
(4, 'VIP-04', 'VIP', 'RTX 4090 • 32GB DDR5 • 360Hz Display', 6.00, 'available'),
(5, 'VIP-05', 'VIP', 'RTX 4090 • 32GB DDR5 • 360Hz Display', 6.00, 'available'),
(6, 'STD-01', 'Standard', 'RTX 4070 • 16GB DDR5 • 240Hz Display', 5.00, 'available'),
(7, 'STD-02', 'Standard', 'RTX 4070 • 16GB DDR5 • 240Hz Display', 5.00, 'available'),
(8, 'STD-03', 'Standard', 'RTX 4070 • 16GB DDR5 • 240Hz Display', 5.00, 'available'),
(9, 'STD-04', 'Standard', 'RTX 4070 • 16GB DDR5 • 240Hz Display', 5.00, 'available'),
(10, 'STD-05', 'Standard', 'RTX 4070 • 16GB DDR5 • 240Hz Display', 5.00, 'available'),
(11, 'STD-06', 'Standard', 'RTX 4070 • 16GB DDR5 • 240Hz Display', 5.00, 'available'),
(12, 'STD-07', 'Standard', 'RTX 4070 • 16GB DDR5 • 240Hz Display', 5.00, 'available'),
(13, 'STD-08', 'Standard', 'RTX 4070 • 16GB DDR5 • 240Hz Display', 5.00, 'available'),
(14, 'STD-09', 'Standard', 'RTX 4070 • 16GB DDR5 • 240Hz Display', 5.00, 'available'),
(15, 'STD-10', 'Standard', 'RTX 4070 • 16GB DDR5 • 240Hz Display', 5.00, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar_key` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gamepad',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `membership` enum('Bronze','Silver','Gold','Platinum') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Bronze',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `username`, `avatar_key`, `password`, `total_hours`, `membership`, `is_admin`, `created_at`) VALUES
(1, 'AdminTryHarder', 'admin', 'shield-halved', '$2y$10$1auczoh78dHUfmsp.oO7XebXznAi/w798WeBFTQyd4JUyS62MkBIW', 0.00, 'Platinum', 1, '2026-05-29 17:51:16'),
(6, 'NUR NAJMA BINTI ROHIZAM', 'NAJMA', 'crown', '$2y$10$JkRDpvDd0vypuAjM8CZWDOHcXGh.UNIBBLnt9lJ4FuUffrmz6jfWu', 4.00, 'Bronze', 0, '2026-05-29 17:53:23'),
(7, 'SHUASUMI', 'SHUA', 'trophy', '$2y$10$XiiX2NOddgNzLFXQmAHfeeOYGHyOwLAQrXqMlxs13YbFUOuDG7EjC', 0.00, 'Bronze', 0, '2026-05-29 19:57:11');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`station_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
