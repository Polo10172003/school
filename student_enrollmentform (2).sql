-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 11, 2025 at 04:38 PM
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
  `student_type` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students_registration`
--

INSERT INTO `students_registration` (`id`, `year`, `course`, `lrn`, `lastname`, `firstname`, `middlename`, `specaddress`, `brgy`, `city`, `mother`, `father`, `gname`, `sex`, `dob`, `religion`, `emailaddress`, `contactno`, `schooltype`, `sname`, `created_at`, `enrollment_status`, `student_type`) VALUES
(1, 'g11', '', '02000307451', 'VICTORIO', 'POLO EMMANUELLE', 'MATUTO', 'a', 'a', 'a', 'a', 'a', '', 'Female', '2003-06-03', 'romCat', 'victoriopolo03@gmail.com', '09622694772', 'private', 'esr', '2025-06-02 17:12:47', 'enrolled', 'Old'),
(2, 'g11', '', '02000307451', 'VICTORIO', 'POLO EMMANUELLE', 'MATUTO', 'a', 'a', 'a', 'a', 'a', '', 'Female', '2003-06-03', 'romCat', 'deadpoolvictorio@gmail.com', '09622694772', 'private', 'esr', '2025-06-02 17:13:21', 'waiting', 'Old'),
(3, 'g11', '', '02000307451', 'VICTORIO', 'POLO EMMANUELLE', 'MATUTO', 'a', 'a', 'a', 'a', 'a', '', 'Female', '2003-06-03', 'romCat', 'deadpoolvictorio@gmail.com', '09622694772', 'private', 'esr', '2025-06-02 17:17:11', 'waiting', 'Old'),
(4, 'g11', '', '02000307451', 'VICTORIO', 'POLO EMMANUELLE', 'MATUTO', 'a', 'a', 'a', 'a', 'a', '', 'Female', '2003-06-03', 'romCat', 'deadpoolvictorio@gmail.com', '09622694772', 'private', 'esr', '2025-06-02 17:38:21', 'waiting', 'Old'),
(13, 'g11', 'stem', '09093', 'CONCEPCION', 'RHAVENE', 'SISON', '130 DR SIXTO ROSARIO', 'ROSARIO', 'PASIG', 'tt', 'tt', '', 'Male', '2004-02-16', 'romCat', 'rhaveneconcepcion@gmail.com', '09120194012', 'public', 'ROSARIO ELEM', '2025-06-07 13:09:32', 'enrolled', 'Old'),
(14, 'g4', '', '9999', 'PEREIRA', 'SAMANTHA AMIARRA', 'BACANI', '130', 'ROSARIO', 'PASIG', 'm', 'd', '', 'Female', '2002-03-28', 'romCat', 'samiarrapereira@gmail.com', '11', 'public', 'esr', '2025-06-11 07:19:08', 'enrolled', 'Old'),
(15, 'g4', '', '090897', 'VICTORIO', 'PATRICIA ANN', 'MATUTO', 'CAPRI', 'MAYBUNGA', 'PASIG', 'sdasfa', 'asfaf', '', 'Female', '1996-03-23', 'romCat', 'patriciaannvictorio@gmail.com', '0908', 'private', 'esr', '2025-06-12 04:47:48', 'enrolled', 'Old');

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
(1, 'victoriopolo03@gmail.com', '$2y$10$/mziiYqnQefT7yRjV283tuzHZZh.MHQuAtX9MwYIm4.v5xBRLn7LK', 0, '2025-06-11 06:50:28', NULL, NULL),
(3, 'rhaveneconcepcion@gmail.com', '$2y$10$RhMaGNLlCcOAD28gi86Qi.Xj7Q9XiQ2lKIxwWQ17Foykiulcv.x3a', 0, '2025-06-11 06:50:28', NULL, NULL),
(4, 'samiarrapereira@gmail.com', '$2y$10$pMu5EyXjwkc.OteV5I.d9uKvM7fXr/rzO.5n5p48jkf0jdL9LWmiu', 0, '2025-06-11 07:32:12', NULL, NULL),
(5, 'patriciaannvictorio@gmail.com', '$2y$10$2rvasLuOQkckESRoRPdGsOrARlzKRmE4QavzGD55eOKTV.E6zFSWm', 0, '2025-06-12 04:53:40', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_payments`
--

CREATE TABLE `student_payments` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `payment_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reference_number` varchar(100) DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `screenshot` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `student_payments`
--

INSERT INTO `student_payments` (`id`, `student_id`, `payment_type`, `amount`, `payment_status`, `payment_date`, `created_at`, `reference_number`, `screenshot_path`, `screenshot`) VALUES
(1, '1', 'Enrollment Fee', '7000.00', 'paid', NULL, '2025-06-06 12:00:06', NULL, NULL, NULL),
(2, '1', 'Enrollment Fee', '7000.00', 'paid', NULL, '2025-06-06 12:01:19', NULL, NULL, NULL),
(3, '10', 'Miscellaneous Fee', '8000.00', 'paid', NULL, '2025-06-06 12:03:37', NULL, NULL, NULL),
(4, '10', 'Enrollment Fee', '7000.00', 'paid', NULL, '2025-06-06 12:27:42', NULL, NULL, NULL),
(5, '1', 'Enrollment Fee', '7000.00', 'paid', NULL, '2025-06-06 12:28:20', NULL, NULL, NULL),
(6, '1', 'Miscellaneous Fee', '8000.00', 'paid', NULL, '2025-06-07 07:33:24', NULL, NULL, NULL),
(7, '1', 'Enrollment Fee', '8000.00', 'paid', NULL, '2025-06-07 07:33:35', NULL, NULL, NULL),
(8, '10', 'Miscellaneous Fee', '10000.00', 'paid', NULL, '2025-06-07 10:31:50', NULL, NULL, NULL),
(9, '10', 'Miscellaneous Fee', '10000.00', 'paid', NULL, '2025-06-07 11:13:44', NULL, NULL, NULL),
(10, '10', 'Miscellaneous Fee', '10000.00', 'paid', NULL, '2025-06-07 11:15:26', NULL, NULL, NULL),
(11, '10', 'Miscellaneous Fee', '10000.00', 'paid', NULL, '2025-06-07 11:17:04', NULL, NULL, NULL),
(12, '10', 'Miscellaneous Fee', '10000.00', 'paid', NULL, '2025-06-07 12:10:13', NULL, NULL, NULL),
(13, '10', 'Miscellaneous Fee', '10000.00', 'paid', NULL, '2025-06-07 12:17:55', NULL, NULL, NULL),
(14, '11', 'Tuition Fee', '8500.00', 'paid', NULL, '2025-06-07 12:18:16', NULL, NULL, NULL),
(15, '12', 'Online', '10000.00', 'pending', NULL, '2025-06-07 12:25:17', '10920384', '0', NULL),
(16, '12', 'Enrollment Fee', '8500.00', 'paid', NULL, '2025-06-07 12:27:05', NULL, NULL, NULL),
(17, '12', 'Enrollment Fee', '8500.00', 'paid', NULL, '2025-06-07 12:35:31', NULL, NULL, NULL),
(18, '12', 'Enrollment Fee', '8500.00', 'paid', NULL, '2025-06-07 12:38:18', NULL, NULL, NULL),
(19, '12', 'Enrollment Fee', '8500.00', 'paid', NULL, '2025-06-07 12:41:55', NULL, NULL, NULL),
(20, '12', 'Enrollment Fee', '8500.00', 'paid', NULL, '2025-06-07 12:42:24', NULL, NULL, NULL),
(21, '12', 'Enrollment Fee', '8500.00', 'paid', NULL, '2025-06-07 12:42:33', NULL, NULL, NULL),
(22, '12', 'Enrollment Fee', '8500.00', 'paid', NULL, '2025-06-07 12:42:54', NULL, NULL, NULL),
(23, '12', 'Enrollment Fee', '8500.00', 'paid', NULL, '2025-06-07 12:44:45', NULL, NULL, NULL),
(24, '12', 'Enrollment Fee', '8500.00', 'paid', NULL, '2025-06-07 12:48:21', NULL, NULL, NULL),
(25, '12', 'Enrollment Fee', '8500.00', 'paid', NULL, '2025-06-07 12:51:59', NULL, NULL, NULL),
(26, '13', 'Online', '4500.00', 'pending', NULL, '2025-06-07 13:10:00', '0920490195', '0', NULL),
(27, '13', 'Enrollment Fee', '4500.00', 'paid', NULL, '2025-06-07 13:10:56', NULL, NULL, NULL),
(28, '14', 'Online', '2500.00', 'pending', NULL, '2025-06-11 07:19:29', '555', '0', NULL),
(29, '14', 'Tuition Fee', '2500.00', 'paid', NULL, '2025-06-11 07:20:22', NULL, NULL, NULL),
(30, '15', 'Online', '2500.00', 'pending', NULL, '2025-06-12 04:48:09', '099886', '0', NULL),
(31, '15', 'Enrollment Fee', '2500.00', 'paid', NULL, '2025-06-12 04:48:39', NULL, NULL, NULL),
(32, '15', 'Enrollment Fee', '2500.00', 'paid', NULL, '2025-06-12 04:50:02', NULL, NULL, NULL),
(33, '15', 'Enrollment Fee', '2500.00', 'paid', NULL, '2025-06-12 04:51:32', NULL, NULL, NULL);

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
(2, 'cashier1', 'fb94a64d00a870ffb9e78912dacb8cb5', 'Matthew', 'cashier'),
(3, 'regis1', '80ef704be23f62b75cac58564ffa96ac', '', 'registrar');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `students_registration`
--
ALTER TABLE `students_registration`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `student_accounts`
--
ALTER TABLE `student_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `student_payments`
--
ALTER TABLE `student_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
--
-- AUTO_INCREMENT for table `tuition_fees`
--
ALTER TABLE `tuition_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
