-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 11, 2025 at 04:09 PM
-- Server version: 10.1.22-MariaDB
-- PHP Version: 7.0.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_enrollmentform`
--

-- --------------------------------------------------------

--
-- Table structure for table `students_registration`
--

CREATE TABLE `students_registration` (
  `id` int(11) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `year` varchar(50) NOT NULL,
  `course` varchar(50) DEFAULT NULL,
  `lrn` varchar(50) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) NOT NULL,
  `specaddress` varchar(255) NOT NULL,
  `brgy` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `mother` varchar(100) NOT NULL,
  `father` varchar(100) NOT NULL,
  `gname` varchar(100) DEFAULT NULL,
  `sex` varchar(10) NOT NULL,
  `dob` date NOT NULL,
  `religion` varchar(50) NOT NULL,
  `emailaddress` varchar(100) NOT NULL,
  `contactno` varchar(20) NOT NULL,
  `schooltype` varchar(50) NOT NULL,
  `sname` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `enrollment_status` enum('waiting','enrolled') DEFAULT 'waiting',
  `student_type` varchar(10) DEFAULT NULL,
  `academic_status` varchar(50) NOT NULL DEFAULT '',
  `portal_status` enum('pending','activated') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students_registration`
--

INSERT INTO `students_registration` (`id`, `student_number`, `year`, `course`, `lrn`, `lastname`, `firstname`, `middlename`, `specaddress`, `brgy`, `city`, `mother`, `father`, `gname`, `sex`, `dob`, `religion`, `emailaddress`, `contactno`, `schooltype`, `sname`, `created_at`, `enrollment_status`, `student_type`, `academic_status`, `portal_status`) VALUES
(36, 'ESR-2025-03304', 'Grade 11', '', '02000307451', 'Victorio ', 'Polo Emmanuelle ', 'Matuto', '212V CAPRI OASIS', 'Maybunga ', 'Pasig', 'Baby', 'rex', '', 'Male', '2003-10-17', 'romCat', 'victoriopolo03@gmail.com', '09863', 'private', 'APEC', '2025-09-11 11:03:13', 'enrolled', 'New', 'Ongoing', 'activated');

-- --------------------------------------------------------

--
-- Table structure for table `student_accounts`
--

CREATE TABLE `student_accounts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_first_login` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `student_accounts`
--

INSERT INTO `student_accounts` (`id`, `email`, `password`, `is_first_login`, `created_at`, `reset_token`, `token_expiry`) VALUES
(7, 'jhonbernvitto04@gmail.com', '$2y$10$IGvlZJ/Es54oosfsVkJQSOIF.q0MxlqYkAVbbFWSv3PSkIAsBQgPe', 0, '2025-09-01 05:23:59', NULL, NULL),
(8, 'matthew.4402@gmail.com', '$2y$10$3UrR26VAVPLGYdiDJqxymuyhPasHxIEG4dS4Ud3vFBoWs2y6c.EzO', 0, '2025-09-01 06:39:41', NULL, NULL),
(9, 'victoriopolo03@gmail.com', '$2y$10$ZvXI.HSbGXsECqLaQY1F3ed307AU8kjpIDn.0mjv22GAfV02MYyGe', 0, '2025-09-11 14:06:14', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_payments`
--

CREATE TABLE `student_payments` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `payment_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','declined') NOT NULL DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reference_number` varchar(100) DEFAULT NULL,
  `or_number` varchar(100) DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `screenshot` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `student_payments`
--

INSERT INTO `student_payments` (`id`, `student_id`, `firstname`, `lastname`, `payment_type`, `amount`, `payment_status`, `payment_date`, `created_at`, `reference_number`, `or_number`, `screenshot_path`, `screenshot`) VALUES
(43, '19', 'POLO EMMANUELLE', 'VICTORIO', 'Online', '7500.00', 'paid', '2025-09-08', '2025-08-23 06:23:25', '1092837', NULL, 'payment_uploads/1755930205_Screenshot 2025-08-20 at 21.12.39.png', NULL),
(44, '20', 'Andrei', 'Santos ', 'Online', '7500.00', 'declined', NULL, '2025-08-26 14:58:03', '1235448156', NULL, 'payment_uploads/1756220283_14e6ca6f-9e56-4df3-a8eb-d4e3f066b2e3.jpeg', NULL),
(45, '21', 'JHON BERN', 'VITTO', 'Online', '5000.00', 'paid', '2025-09-04', '2025-09-01 05:21:47', '9863136', NULL, 'payment_uploads/1756704107_1a4dc36a-262c-402c-9370-31465768eb5c.jpeg', NULL),
(46, '21', 'JHON BERN', 'VITTO', 'Online', '4000.00', 'paid', '2025-09-11', '2025-09-01 06:12:20', '56787654', NULL, 'payment_uploads/1756707140_6c280b88-1b46-4448-9dd5-a0482a0e7c81.jpeg', NULL),
(47, '23', 'MATTHEW', 'SANTOS', 'Online', '4000.00', 'paid', '2025-09-02', '2025-09-01 06:38:14', '987654', NULL, 'payment_uploads/1756708694_1a4dc36a-262c-402c-9370-31465768eb5c.jpeg', NULL),
(48, '23', 'MATTHEW', 'SANTAS', 'Online', '2000.00', 'paid', '2025-09-02', '2025-09-02 13:27:17', '123', NULL, 'payment_uploads/1756819637_1a4dc36a-262c-402c-9370-31465768eb5c.jpeg', NULL),
(49, '24', 'q', 'q', 'Online', '1234.00', 'paid', '2025-09-08', '2025-09-08 09:31:02', '1235448156', NULL, 'payment_uploads/1757323862_1a4dc36a-262c-402c-9370-31465768eb5c.jpeg', NULL),
(50, '27', 'a', 'a', 'Online', '800.00', 'paid', '2025-09-09', '2025-09-08 15:37:36', '876', NULL, 'payment_uploads/1757345856_Screenshot 2025-09-02 at 22.55.23.png', NULL),
(51, '19', NULL, NULL, 'cash', '5000.00', 'paid', '2025-09-08', '2025-09-08 16:09:27', NULL, NULL, NULL, NULL),
(52, '19', NULL, NULL, 'cash', '5000.00', 'paid', '2025-09-08', '2025-09-08 16:11:03', NULL, NULL, NULL, NULL),
(53, '19', NULL, NULL, 'cash', '5000.00', 'paid', '2025-09-08', '2025-09-08 16:14:37', NULL, NULL, NULL, NULL),
(54, '19', NULL, NULL, 'cash', '5000.00', 'paid', '2025-09-08', '2025-09-08 16:15:44', NULL, NULL, NULL, NULL),
(55, '32', NULL, NULL, 'Cash', '5000.00', 'paid', '2025-09-09', '2025-09-09 12:42:03', '55', '21', NULL, NULL),
(56, '33', NULL, NULL, 'Cash', '5000.00', 'paid', '2025-09-09', '2025-09-09 15:13:20', NULL, '2', NULL, NULL),
(57, '31', 'ee', 'ee', 'Cash', '5000.00', 'pending', NULL, '2025-09-09 15:25:17', NULL, NULL, NULL, NULL),
(58, '19', 'POLO EMMANUELLE', 'VICTORIO', 'Cash', '5000.00', 'pending', NULL, '2025-09-09 15:25:34', NULL, NULL, NULL, NULL),
(59, '19', 'POLO EMMANUELLE', 'VICTORIO', 'Online', '2500.00', 'paid', '2025-09-09', '2025-09-09 15:26:09', '56', NULL, 'payment_uploads/1757431569_Screenshot 2025-09-02 at 22.55.23.png', NULL),
(60, '30', 'a', 'a', 'Cash', '5000.00', 'pending', NULL, '2025-09-09 15:28:05', NULL, NULL, NULL, NULL),
(61, '27', 'a', 'a', 'Online', '666.00', 'pending', '2025-09-09', '2025-09-09 15:28:26', '666', NULL, 'payment_uploads/1757431706_Screenshot 2025-09-02 at 22.55.27.png', NULL),
(62, '19', 'POLO EMMANUELLE', 'VICTORIO', 'Cash', '5000.00', 'pending', NULL, '2025-09-09 15:32:22', NULL, NULL, NULL, NULL),
(63, '34', 'KALOY', 'DIMAGIBA', 'Cash', '5000.00', 'paid', '2025-09-09', '2025-09-09 15:32:42', NULL, '123', NULL, NULL),
(65, '36', NULL, NULL, 'Cash', '5000.00', 'paid', '2025-09-11', '2025-09-11 11:03:41', NULL, '123456789', NULL, NULL),
(66, '36', NULL, NULL, 'Cash', '5000.00', 'paid', '2025-09-11', '2025-09-11 11:03:44', NULL, '78', NULL, NULL),
(67, '36', 'Polo Emmanuelle ', 'Victorio ', 'Cash', '6000.00', 'paid', '2025-09-11', '2025-09-11 12:31:05', NULL, '129', NULL, NULL),
(68, '36', 'Polo Emmanuelle ', 'Victorio ', 'Cash', '6000.00', 'paid', '2025-09-11', '2025-09-11 12:40:17', NULL, '129', NULL, NULL),
(69, '36', 'Polo Emmanuelle ', 'Victorio ', 'Cash', '6000.00', 'paid', '2025-09-11', '2025-09-11 13:42:24', NULL, '129', NULL, NULL),
(70, '36', 'Polo Emmanuelle ', 'Victorio ', 'Cash', '2000.00', 'paid', '2025-09-11', '2025-09-11 13:42:40', NULL, '0928731', NULL, NULL),
(71, '36', 'Polo Emmanuelle ', 'Victorio ', 'Cash', '2000.00', 'paid', '2025-09-11', '2025-09-11 13:42:54', NULL, '0928731', NULL, NULL),
(72, '36', 'Polo Emmanuelle ', 'Victorio ', 'Cash', '1500.00', 'paid', '2025-09-11', '2025-09-11 13:49:00', NULL, '109283721', NULL, NULL),
(73, '37', 'RAIN', 'SOLOMON', 'Online', '5500.00', 'paid', '2025-09-11', '2025-09-11 13:55:25', '1738', NULL, 'payment_uploads/1757598925_pixel-art-city-game-level-600nw-2515685417 copy.png', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tuition_fees`
--

CREATE TABLE `tuition_fees` (
  `id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `student_type` enum('new','old') NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `entrance_fee` decimal(10,2) DEFAULT NULL,
  `miscellaneous_fee` decimal(10,2) DEFAULT NULL,
  `tuition_fee` decimal(10,2) DEFAULT NULL,
  `monthly_payment` decimal(10,2) DEFAULT NULL,
  `total_upon_enrollment` decimal(10,2) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tuition_fees`
--

INSERT INTO `tuition_fees` (`id`, `school_year`, `student_type`, `grade_level`, `entrance_fee`, `miscellaneous_fee`, `tuition_fee`, `monthly_payment`, `total_upon_enrollment`, `updated_at`) VALUES
(1, '2025-2026', 'new', 'kinder1', '4000.00', '4500.00', '7500.00', NULL, '7000.00', '2025-09-01 01:04:35'),
(2, '2025-2026', 'new', 'kinder2', '5000.00', '5000.00', '5000.00', NULL, '7000.00', '2025-09-01 01:05:07'),
(3, '2025-2026', 'new', 'grade1', '6000.00', '4000.00', '20000.00', NULL, '10000.00', '2025-09-01 06:56:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'cashier'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `role`) VALUES
(1, 'admin1', '12345', 'Polo Victorio', 'cashier'),
(6, 'Sam', 'sam12345', 'Samantha Pereira', 'cashier'),
(7, 'Vitto', '12345', 'Jhon Bern', 'registrar'),
(8, 'admin2', 'admin2', 'ABC', 'cashier'),
(9, 'ace', '12345', 'ace', 'registrar');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `students_registration`
--
ALTER TABLE `students_registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`);

--
-- Indexes for table `student_accounts`
--
ALTER TABLE `student_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `student_payments`
--
ALTER TABLE `student_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `tuition_fees`
--
ALTER TABLE `tuition_fees`
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
-- AUTO_INCREMENT for table `students_registration`
--
ALTER TABLE `students_registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;
--
-- AUTO_INCREMENT for table `student_accounts`
--
ALTER TABLE `student_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `student_payments`
--
ALTER TABLE `student_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;
--
-- AUTO_INCREMENT for table `tuition_fees`
--
ALTER TABLE `tuition_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
