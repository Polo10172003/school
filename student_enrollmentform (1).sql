-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2025 at 06:57 PM
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `enrollment_status` enum('waiting','enrolled') DEFAULT 'waiting',
  `student_type` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students_registration`
--

INSERT INTO `students_registration` (`id`, `year`, `course`, `lrn`, `lastname`, `firstname`, `middlename`, `specaddress`, `brgy`, `city`, `mother`, `father`, `gname`, `sex`, `dob`, `religion`, `emailaddress`, `contactno`, `schooltype`, `sname`, `created_at`, `enrollment_status`, `student_type`) VALUES
(2, 'g12', 'TVL - ICT', '02000307451', 'Victorio', 'Polo', 'MATUTO', '123 Bahay ', 'Maybunga', 'Pasig', 'Baby', 'Rex', '', 'Male', '2003-10-17', 'Roman Catholic', 'victoriopolo03@gmail.com', '09622694772', 'Public', 'ESR', '2025-04-02 06:13:30', 'waiting', NULL),
(12, 'g6', '', '12345677', 'Sta Ana', 'Kali Amirra', 'Bacani', '130 Dr. Sixto Antonio Avenue', 'Rosario', 'Pasig', 'Dulce Bacani', 'Ronald Sta Ana', '', 'Female', '2019-02-28', 'romCat', 'samanthapereira@gmail.com', '09123456789', 'private', 'ESR', '2025-05-15 07:55:33', 'waiting', NULL),
(23, 'g11', 'stem', '02000307451', 'Vitto', 'Jhon Bern', 'Dimagiba', '123 Pinagbuhatan st', 'Pinagbuhatan', 'Pasig', 'Dkoalam', 'Dkoalam', '', 'Male', '2003-02-10', 'romCat', 'jhonbern@gmail.com', '09992134566', 'public', 'Pinagbuhatan High School', '2025-05-15 09:11:12', 'waiting', NULL),
(37, 'g3', '', '02000307451', 'Pereira', 'Samantha Amiarra', 'Bacani', '12134 Cheche', 'Rosario', 'Pasig', 'MAMA', 'PAPA', '', 'Female', '2002-03-28', 'romCat', 'victoriopolo03@gmail.com', '09120920121', 'public', 'asdasdas', '2025-05-20 09:01:47', 'waiting', NULL),
(38, 'g3', '', '02000307451', 'Pereira', 'Samantha Amiarra', 'Bacani', '12134 Cheche', 'Rosario', 'Pasig', 'MAMA', 'PAPA', '', 'Female', '2002-03-28', 'romCat', 'samiarrapereira@gmail.com', '09120920121', 'public', 'asdasdas', '2025-05-20 09:03:57', 'waiting', NULL),
(39, 'g12', 'TVL - ICT', '02000307451', 'Pereira', 'Samantha Amiarra', 'Bacani', '12134 Cheche', 'Rosario', 'Pasig', 'MAMA', 'PAPA', '', 'Female', '2002-03-28', 'Roman Catholic', 'samiarrapereira@gmail.com', '09120920121', 'Public', 'asdasdas', '2025-05-20 09:05:25', 'waiting', NULL),
(40, 'g11', '', '02000307451', 'VITTOBETLOG', 'JHONBERN', 'LIITTITE', '12134 Cheche', 'Pinagbuhatan', 'Pasig', 'MAMA', 'PAPA', '', 'Female', '2002-03-28', 'romCat', 'rainsolomon1212@gmail.com', '09120920121', 'public', 'asdasdas', '2025-05-20 09:06:31', 'waiting', NULL),
(41, 'g11', '', '02000307451', 'VITTOBETLOG', 'JHONBERN', 'LIITTITE', '12134 Cheche', 'Pinagbuhatan', 'Pasig', 'MAMA', 'PAPA', '', 'Female', '2002-03-28', 'romCat', 'jhonjanvitto04@gmail.com', '09120920121', 'public', 'asdasdas', '2025-05-20 09:07:05', 'waiting', NULL),
(42, 'g11', '', '02000307451', 'VITTOBETLOG', 'JHONBERN', 'LIITTITE', '12134 Cheche', 'Pinagbuhatan', 'Pasig', 'MAMA', 'PAPA', '', 'Female', '2002-03-28', 'romCat', 'jhonjanvitto04@gmail.com', '09120920121', 'public', 'asdasdas', '2025-05-20 09:07:05', 'waiting', NULL),
(43, 'g11', 'abm', '12345677', 'aa', 'aa', 'aa', 'aa', 'aa', 'a', 'a', 'a', 'a', 'Female', '2001-12-10', 'buddhism', 'victoriopolo03@gmail.com', '09992134566', 'public', 'asaa', '2025-05-20 09:45:38', 'waiting', 'Old'),
(44, 'g11', 'abm', '12345677', 'aa', 'aa', 'aa', 'aa', 'aa', 'a', 'a', 'a', 'a', 'Female', '2001-12-10', 'buddhism', 'victoriopolo03@gmail.com', '09992134566', 'public', 'asaa', '2025-05-20 09:49:28', 'waiting', 'New'),
(45, 'g11', '', '12345677', 'aa', 'aa', 'aa', 'aa', 'aa', 'a', 'a', 'a', 'a', 'Female', '2001-12-10', 'buddhism', 'victoriopolo03@gmail.com', '09992134566', 'public', 'asaa', '2025-05-20 09:52:45', 'waiting', 'Old'),
(46, 'g11', 'stem', '02000123456', 'Santos', 'Matthew ', 'Chups', 'Bahay Kubo', 'Kubo', 'Pasig', 'mom', 'dad', '', 'Male', '2002-12-10', 'romCat', 'matthew.4402@gmail.com', '0987654321', 'public', 'San Isidro', '2025-05-23 15:44:07', 'waiting', 'New'),
(47, 'g12', 'TVL - HE', '0123456789', 'Soberano', 'Franz', 'Dimagiba', 'Catoc Compound', 'Rosario', 'Pasig', 'Mommy Milk', 'Chaooo', '', 'Male', '2002-02-02', 'Roman Catholic', 'lesterjim0916@gmail.com', '09167959614', 'Public', 'ESR', '2025-05-23 15:55:48', 'waiting', 'New'),
(48, '', '', '', '', '', '', '', '', '', 'Baby', 'Rex', 'a', '', '0000-00-00', '', '', '', '', '', '2025-05-31 13:14:00', 'waiting', ''),
(49, '', '', '', '', '', '', '', '', '', '', '', '', '', '0000-00-00', '', '', '', 'public', 'ESR', '2025-05-31 13:14:58', 'waiting', ''),
(50, '', '', '', '', '', '', '', '', '', '', '', '', '', '0000-00-00', '', '', '', 'public', 'ESR', '2025-05-31 13:23:01', 'waiting', ''),
(51, '', '', '', '', '', '', '', '', '', '', '', '', '', '0000-00-00', '', '', '', '', 'ESR', '2025-05-31 13:23:06', 'waiting', ''),
(52, '', '', '', '', '', '', '', '', '', '', '', '', '', '0000-00-00', '', '', '', 'public', 'ESR', '2025-05-31 13:25:08', 'waiting', '');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reference_number` varchar(100) DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `screenshot` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_payments`
--

INSERT INTO `student_payments` (`id`, `student_id`, `payment_type`, `amount`, `payment_status`, `payment_date`, `created_at`, `reference_number`, `screenshot_path`, `screenshot`) VALUES
(5, '12', 'Online', 12345.00, 'pending', NULL, '2025-05-15 09:03:19', '1823041', '0', NULL),
(6, '12', 'Online', 12000.00, 'pending', NULL, '2025-05-15 09:05:40', '0000000', '0', NULL),
(7, '2', 'Online', 7000.00, 'pending', NULL, '2025-05-15 09:11:41', '7894564123', '0', NULL),
(9, '23', 'Miscellaneous Fee', 7000.00, 'paid', NULL, '2025-05-15 09:15:16', NULL, NULL, NULL),
(14, '24', 'Online', 1222.00, 'pending', NULL, '2025-05-15 09:42:46', '12314', '0', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects_schedule`
--

CREATE TABLE `subjects_schedule` (
  `id` int(11) NOT NULL,
  `grade_level` varchar(10) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `subject_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects_schedule`
--

INSERT INTO `subjects_schedule` (`id`, `grade_level`, `subject_name`, `subject_time`) VALUES
(1, 'Kinder 1', 'Mathematics', '05:33:00'),
(2, 'Kinder 1', 'Science', '07:33:00'),
(3, 'preschool', 'GMRC', '07:30:00'),
(4, 'Kinder 1', 'PE', '08:07:00');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tuition_fees`
--

INSERT INTO `tuition_fees` (`id`, `school_year`, `student_type`, `grade_level`, `entrance_fee`, `miscellaneous_fee`, `tuition_fee`, `monthly_payment`, `total_upon_enrollment`, `updated_at`) VALUES
(1, '2024-2025', 'new', 'kinder1', 7000.00, 6000.00, 27500.00, NULL, 40500.00, '2025-04-30 06:30:43'),
(2, '2024-2025', 'new', 'grade1', 8000.00, 6000.00, 29500.00, NULL, 43500.00, '2025-04-30 06:37:09'),
(3, '2025-2026', 'new', 'grade10', 8000.00, 6000.00, 40000.00, NULL, 40000.00, '2025-05-03 13:29:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`) VALUES
(1, 'admin1', '12345', 'Polo Victorio');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `students_registration`
--
ALTER TABLE `students_registration`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_payments`
--
ALTER TABLE `student_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `subjects_schedule`
--
ALTER TABLE `subjects_schedule`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `student_payments`
--
ALTER TABLE `student_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `subjects_schedule`
--
ALTER TABLE `subjects_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tuition_fees`
--
ALTER TABLE `tuition_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
