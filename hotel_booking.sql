-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 09, 2026 at 03:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ACTION` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `ACTION`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 6, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 05:10:14'),
(2, 6, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 05:19:43'),
(3, 7, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 10:46:31'),
(4, 7, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 11:34:11'),
(5, 6, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 11:34:44'),
(6, 6, 'LOGOUT', 'User logged out from IP: ::1', '::1', NULL, '2026-01-08 11:34:53'),
(7, 6, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 15:17:31'),
(8, 6, 'LOGOUT', 'User logged out from IP: ::1', '::1', NULL, '2026-01-08 15:17:33'),
(9, 7, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 15:17:42'),
(10, 7, 'LOGOUT', 'User logged out from IP: ::1', '::1', NULL, '2026-01-08 15:29:46'),
(11, 7, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 15:29:58'),
(12, 7, 'LOGOUT', 'User logged out from IP: ::1', '::1', NULL, '2026-01-08 15:37:22'),
(13, 14, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 15:37:33'),
(14, 14, 'LOGOUT', 'User logged out from IP: ::1', '::1', NULL, '2026-01-08 15:37:40'),
(15, 6, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 17:04:37'),
(16, 7, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 17:05:05'),
(17, 6, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 17:05:38'),
(18, 6, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 17:21:34'),
(19, 7, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 17:21:54'),
(20, 7, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 17:25:17'),
(21, 7, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 17:26:22'),
(22, 7, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-08 17:27:28'),
(23, 7, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-09 01:42:14'),
(24, 7, 'USER_CREATED', 'Admin created new user: hamzacafe (ID: 15)', '::1', NULL, '2026-01-09 01:59:04'),
(25, 7, 'USER_CREATED', 'Admin created new user: adminhamza (ID: 16)', '::1', NULL, '2026-01-09 01:59:28'),
(26, 7, 'LOGOUT', 'User logged out from IP: ::1', '::1', NULL, '2026-01-09 01:59:34'),
(27, 6, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-09 02:00:13'),
(28, 6, 'BOOKING_CREATED', 'User booked room ID: 5 (Booking ID: 7)', '::1', NULL, '2026-01-09 02:00:22'),
(29, 6, 'LOGOUT', 'User logged out from IP: ::1', '::1', NULL, '2026-01-09 02:04:43'),
(30, 6, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-09 02:08:51'),
(31, 6, 'LOGOUT', 'User logged out from IP: ::1', '::1', NULL, '2026-01-09 02:20:15'),
(32, 6, 'LOGIN_SUCCESS', 'User logged in from IP: ::1', '::1', NULL, '2026-01-09 02:22:28'),
(33, 6, 'LOGOUT', 'User logged out from IP: ::1', '::1', NULL, '2026-01-09 02:24:11');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `check_in_date` date DEFAULT NULL,
  `check_out_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `check_in_date`, `check_out_date`) VALUES
(6, 6, 4, '2026-01-10', '2026-01-30'),
(7, 6, 5, '2026-01-22', '2026-01-30');

-- --------------------------------------------------------

--
-- Table structure for table `failed_login_log`
--

CREATE TABLE `failed_login_log` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `failed_login_log`
--

INSERT INTO `failed_login_log` (`id`, `username`, `reason`, `ip_address`, `attempted_at`) VALUES
(1, 'admin', 'Invalid password', '::1', '2026-01-08 10:45:28'),
(2, 'admin2', 'Invalid password', '::1', '2026-01-08 10:46:26'),
(3, 'nik', 'Invalid password', '::1', '2026-01-08 11:34:38'),
(4, 'nik', 'Invalid password', '::1', '2026-01-08 17:05:32'),
(5, 'admin2', 'Invalid password', '::1', '2026-01-08 17:25:04'),
(6, 'admin2', 'Invalid password', '::1', '2026-01-08 17:25:09'),
(7, 'admin2', 'Invalid password', '::1', '2026-01-08 17:27:24'),
(8, 'hamzacafe', 'Invalid password', '::1', '2026-01-09 01:59:54');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `price`) VALUES
(3, '1', 'Standard', 120.00),
(4, '2', 'Deluxe', 180.00),
(5, '3', 'Family', 250.00),
(6, '4', 'Suite', 350.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` text NOT NULL,
  `role` enum('admin','user') NOT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`) VALUES
(6, 'nik', '$2y$12$WQrSBXJ05s18v979ULEGnOrNrZm01v0tkgLbeBf2hs2gMvVK1sHfK', 'user', 'niknikky03@gmail.com'),
(7, 'admin2', '$2y$12$yRGB/omAd9EI5gcpZSRQJOzpCBTUiHN4W5thWUrHSztcYZe0F0GoC', 'admin', 'admin@gmail.com'),
(12, 'try123456', '$2y$12$gBNqZAhIKpIuPfPytKU4IOT4xLJjn/kXV8hZis45qwvLmkB2YzGim', 'admin', 'trytest@gmail.com'),
(14, 'jiranSebelah', '$2y$12$XnyYupHgAfbsAIGeL6e2a.X8x4Bq648u10767URi64j1f00nUKxdW', 'user', 'jiranpakabu@gs.com'),
(15, 'hamzacafe', '$2y$12$6x2tcPULoVTuBxwGOAI1L.LUqwOV.mXJhAKLMw3MzkSGsyEKV0GKO', 'user', 'hamzacafe@gm.com'),
(16, 'adminhamza', '$2y$12$97nvEQajK5iWVRpUfyM5DekPIh.rj3Qe3vetIEcdohiOEJrj07rRO', 'admin', 'adminhamza12@gm.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `failed_login_log`
--
ALTER TABLE `failed_login_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `failed_login_log`
--
ALTER TABLE `failed_login_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
