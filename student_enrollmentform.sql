-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 18, 2025 at 03:38 PM
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
-- Table structure for table `archived_students`
--

CREATE TABLE `archived_students` (
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
  `portal_status` enum('pending','activated') DEFAULT 'pending',
  `section` varchar(100) DEFAULT NULL,
  `adviser` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `archived_students`
--

INSERT INTO `archived_students` (`id`, `student_number`, `year`, `course`, `lrn`, `lastname`, `firstname`, `middlename`, `specaddress`, `brgy`, `city`, `mother`, `father`, `gname`, `sex`, `dob`, `religion`, `emailaddress`, `contactno`, `schooltype`, `sname`, `created_at`, `enrollment_status`, `student_type`, `academic_status`, `portal_status`, `section`, `adviser`) VALUES
(47, 'ESR-29715', 'Graduated', 'ABM', '02000308654', 'SOLOMON', 'RAIN', 'M', '123 BAHAY', 'TAYTAY', 'RIZAL', 'mama', 'papa', '', 'Male', '2003-10-17', 'romCat', 'rainsolomon1212@gmail.com', '098723921029', 'private', 'URS', '2025-09-18 13:10:03', 'enrolled', 'New', 'Graduated', 'activated', 'ABM - Section 1', 'Sir Mendoza');

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
  `portal_status` enum('pending','activated') DEFAULT 'pending',
  `section` varchar(100) DEFAULT NULL,
  `adviser` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students_registration`
--

INSERT INTO `students_registration` (`id`, `student_number`, `year`, `course`, `lrn`, `lastname`, `firstname`, `middlename`, `specaddress`, `brgy`, `city`, `mother`, `father`, `gname`, `sex`, `dob`, `religion`, `emailaddress`, `contactno`, `schooltype`, `sname`, `created_at`, `enrollment_status`, `student_type`, `academic_status`, `portal_status`, `section`, `adviser`) VALUES
(42, 'ESR-2025-78274', 'Grade 2', '', '02000306451', 'Pereira', 'Samantha', 'Bacani', '130 Dr. Sixto Antonio Avenue', 'Rosario', 'Pasig', 'Dulce Bacani', 'Bong Pereira', '', 'Female', '2002-03-28', 'romCat', 'victoriopolo03@gmail.com', '099876543', 'private', 'APEC', '2025-09-16 14:48:23', 'enrolled', 'New', 'Ongoing', 'activated', 'Section A', 'Ms. Santos'),
(45, 'ESR-04510', 'Grade 3', '', '03000207456', 'Vitto', 'Jhon', 'B', '123', 'a', 'a', 'a', 'a', '', 'Female', '2002-03-28', 'romCat', 'jhonbernvitto04@gmail.com\r\n', '123', 'private', 'Apec', '2025-09-17 15:03:28', 'enrolled', 'New', 'Ongoing', 'activated', 'Section A', 'Ms. Santos');

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
(22, 'victoriopolo03@gmail.com', '$2y$10$FYbAtyz6BpLVpVrP0Map7e5w2IBlIQNFUg5niWEDzirGHSmdTncNK', 0, '2025-09-18 11:23:21', NULL, NULL),
(23, 'jhonbernvitto04@gmail.com\r\n', NULL, 1, '2025-09-18 11:23:21', NULL, NULL),
(24, 'rainsolomon1212@gmail.com', NULL, 1, '2025-09-18 13:11:52', NULL, NULL);

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
(87, '42', 'Samantha', 'Pereira', 'Cash', '1500.00', 'paid', '2025-09-16', '2025-09-16 15:53:35', NULL, '124', NULL, NULL),
(88, '42', 'Samantha', 'Pereira', 'Cash', '1000.00', 'paid', '2025-09-16', '2025-09-16 15:59:26', NULL, '123', NULL, NULL),
(89, '45', 'Jhon', 'Vitto', 'Cash', '6000.00', 'paid', '2025-09-17', '2025-09-17 15:12:03', NULL, '092837271', NULL, NULL),
(98, '42', 'Samantha', 'Pereira', 'Cash', '9110.00', 'paid', '2025-09-18', '2025-09-18 02:27:44', NULL, '17834', NULL, NULL),
(99, '42', 'Samantha', 'Pereira', 'Cash', '9110.00', 'paid', '2025-09-18', '2025-09-18 02:32:12', NULL, '0928731', NULL, NULL),
(100, '42', 'Samantha', 'Pereira', 'Cash', '8000.00', 'paid', '2025-09-18', '2025-09-18 11:31:04', NULL, '9028392', NULL, NULL),
(101, '42', 'Samantha', 'Pereira', 'Cash', '9000.00', 'paid', '2025-09-18', '2025-09-18 11:37:13', NULL, '2131249', NULL, NULL),
(102, '42', 'Samantha', 'Pereira', 'Cash', '5000.00', 'paid', '2025-09-18', '2025-09-18 11:39:50', NULL, '123456', NULL, NULL),
(103, '42', 'Samantha', 'Pereira', 'Cash', '2000.00', 'paid', '2025-09-18', '2025-09-18 11:41:28', NULL, '124241341', NULL, NULL),
(104, '47', 'RAIN', 'SOLOMON', 'Cash', '9110.00', 'paid', '2025-09-18', '2025-09-18 13:10:36', NULL, '12393412', NULL, NULL);

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
-- Indexes for table `archived_students`
--
ALTER TABLE `archived_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`);

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
-- AUTO_INCREMENT for table `archived_students`
--
ALTER TABLE `archived_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;
--
-- AUTO_INCREMENT for table `students_registration`
--
ALTER TABLE `students_registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;
--
-- AUTO_INCREMENT for table `student_accounts`
--
ALTER TABLE `student_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
--
-- AUTO_INCREMENT for table `student_payments`
--
ALTER TABLE `student_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;
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
