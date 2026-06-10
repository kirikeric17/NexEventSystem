-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2025 at 05:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cems`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `mode` enum('Physical','Online','Hybrid') DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `poster_path` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `description`, `category_id`, `venue`, `event_date`, `mode`, `remarks`, `poster_path`, `created_by`, `created_at`) VALUES
(1, 'Lukisan', 'Teknik', 1, 'Fakulti Seni', '2025-10-29', 'Physical', 'Bawa alat sendiri', 'art.jpg', NULL, '2025-10-09 17:01:39'),
(2, 'Photography', 'workshop', 1, 'Perpustakaan', '2025-10-29', 'Physical', 'Kamera', 'kamera.jpg', NULL, '2025-10-09 17:04:28'),
(3, 'Beach Cleaning', 'Ocean Day', 4, 'ODEC', '2025-10-30', 'Physical', 'Sampah', 'beach.jpg', NULL, '2025-10-09 17:08:30'),
(4, 'Tunas Bola UMS', 'Bola sepak UMS junior', 5, 'Stadium UMS', '2025-11-08', 'Physical', 'Kem bola sepak', 'event1.jpg', NULL, '2025-10-09 17:15:51'),
(5, 'Hiking', 'UMS Peak', 4, 'Dewan Canselor', '2025-10-31', 'Physical', 'Sport Attire', '1760413331_hiking.jpg', NULL, '2025-10-13 19:42:11');

-- --------------------------------------------------------

--
-- Table structure for table `event_category`
--

CREATE TABLE `event_category` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `categoryName` varchar(100) NOT NULL,
  `categoryDesc` varchar(100) NOT NULL,
  `createDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_category`
--

INSERT INTO `event_category` (`category_id`, `categoryName`, `categoryDesc`, `createDate`) VALUES
(1, 'Workshop', 'F2F/Online course or hands-on as workshop', '2025-10-14 23:08:40'),
(2, 'Seminar', 'F2F/Online seminar, presentation, etc.', '2025-10-14 23:09:33'),
(3, 'Competition', 'Competition event', '2025-10-14 23:09:33'),
(4, 'Festival', 'All festival event', '2025-10-14 23:09:33'),
(5, 'Sport', 'All types of sport events', '2025-10-14 23:09:33'),
(6, 'Course', 'All types of educational course events', '2025-10-14 23:09:33');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `category` enum('Staff','Student','Public') NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','User') DEFAULT 'User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `category`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Staff', 'admin1', 'admin1@ums.edu.my', '0123456789', '123456', 'Admin', '2025-10-09 15:14:20');

-- --------------------------------------------------------

--
-- Table structure for table `user_event_recommend`
--

CREATE TABLE `user_event_recommend` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `fk_events_createdby` (`created_by`);

--
-- Indexes for table `event_category`
--
ALTER TABLE `event_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_event_recommend`
--
ALTER TABLE `user_event_recommend`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `event_category`
--
ALTER TABLE `event_category`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `event_category` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_createdby` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_event_recommend`
--
ALTER TABLE `user_event_recommend`
  ADD CONSTRAINT `user_event_recommend_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
