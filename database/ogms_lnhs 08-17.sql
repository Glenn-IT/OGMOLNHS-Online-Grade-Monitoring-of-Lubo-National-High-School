-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 17, 2026 at 04:24 PM
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
-- Database: `ogms_lnhs`
--

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `section_id`, `school_year_id`, `enrolled_at`) VALUES
(57, 62, 40, 5, '2026-08-14 11:57:42'),
(58, 63, 40, 5, '2026-08-14 11:57:42'),
(59, 64, 41, 5, '2026-08-14 11:57:42'),
(60, 65, 41, 5, '2026-08-14 11:57:42'),
(61, 66, 42, 5, '2026-08-14 11:57:42'),
(62, 67, 42, 5, '2026-08-14 11:57:42'),
(63, 68, 43, 5, '2026-08-14 11:57:42'),
(64, 69, 43, 5, '2026-08-14 11:57:42'),
(65, 70, 44, 5, '2026-08-14 11:57:42'),
(66, 71, 44, 5, '2026-08-14 11:57:43'),
(67, 72, 45, 5, '2026-08-14 11:57:43'),
(68, 73, 45, 5, '2026-08-14 11:57:43'),
(69, 74, 46, 5, '2026-08-14 11:57:43'),
(70, 75, 46, 5, '2026-08-14 11:57:43'),
(71, 76, 48, 5, '2026-08-14 11:57:43'),
(72, 77, 48, 5, '2026-08-14 11:57:43'),
(73, 78, 50, 5, '2026-08-14 11:57:43'),
(74, 79, 50, 5, '2026-08-14 11:57:43'),
(75, 80, 40, 5, '2026-08-17 13:28:30'),
(76, 81, 52, 5, '2026-08-17 14:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `quarter` tinyint(4) NOT NULL CHECK (`quarter` between 1 and 4),
  `written_works` decimal(5,2) DEFAULT NULL,
  `performance_tasks` decimal(5,2) DEFAULT NULL,
  `quarterly_exam` decimal(5,2) DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `remarks` enum('Passed','Failed','Incomplete') DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `school_year_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `quarter`, `written_works`, `performance_tasks`, `quarterly_exam`, `final_grade`, `remarks`, `encoded_by`, `school_year_id`, `created_at`, `updated_at`) VALUES
(1340, 81, 17, 1, 88.00, 88.00, 88.00, 88.00, 'Passed', NULL, 5, '2026-08-17 14:20:54', '2026-08-17 14:20:54'),
(1341, 81, 17, 2, 88.00, 88.00, 88.00, 88.00, 'Passed', NULL, 5, '2026-08-17 14:20:57', '2026-08-17 14:20:57'),
(1342, 81, 17, 3, 88.00, 88.00, 88.00, 88.00, 'Passed', NULL, 5, '2026-08-17 14:21:01', '2026-08-17 14:21:01'),
(1343, 81, 20, 1, 99.00, 99.00, 99.00, 99.00, 'Passed', NULL, 5, '2026-08-17 14:21:14', '2026-08-17 14:21:14'),
(1344, 81, 20, 2, 99.00, 99.00, 99.00, 99.00, 'Passed', NULL, 5, '2026-08-17 14:21:19', '2026-08-17 14:21:19'),
(1345, 81, 20, 3, 99.00, 99.00, 99.00, 99.00, 'Passed', NULL, 5, '2026-08-17 14:21:22', '2026-08-17 14:21:22');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `id` int(11) NOT NULL,
  `label` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`id`, `label`, `is_active`) VALUES
(5, '2024-2025', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `grade_level` tinyint(4) NOT NULL,
  `school_year_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `name`, `grade_level`, `school_year_id`) VALUES
(52, 'Rizal', 7, 5);

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_logs`
--

INSERT INTO `sms_logs` (`id`, `recipient_phone`, `recipient_name`, `message`, `status`, `sent_at`, `created_at`) VALUES
(8, '09169751409', 'Maria Santos (Parent of Juan Dela cruz)', 'LNHS: Juan Dela cruz (Gr.7-Rizal) 1st Term Grades: AP:88,ENG:99. Avg: 93.50 (PASSED).', 'sent', '2026-08-17 14:21:44', '2026-08-17 14:21:41');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `code`, `teacher_id`) VALUES
(17, 'Araling Panlipunan', 'AP', 58),
(18, 'Mathematics', 'MATH', 54),
(19, 'Science', 'SCI', 55),
(20, 'English', 'ENG', 56),
(21, 'Filipino', 'FIL', 57),
(22, 'MAPEH', 'MAPEH', 59),
(23, 'TLE', 'TLE', 60),
(24, 'Values Education', 'VE', 61);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `lrn` varchar(12) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `lrn`, `full_name`, `email`, `password`, `role`, `phone`, `address`, `birthdate`, `gender`, `guardian_name`, `guardian_phone`, `avatar_url`, `is_active`, `created_at`, `updated_at`) VALUES
(53, NULL, 'Administrator', 'admin@lnhs.edu.ph', '$2y$10$z/GbkIDyI3tQm9JzZPerUesuKh2L//gZ0dlovn0rFbta8O/bNS4vi', 'admin', '09111222333', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-08-14 11:57:41', '2026-08-14 11:57:41'),
(81, '454654546546', 'Juan Dela cruz', 'juan@gmail.com', '$2y$10$o/xYKP/nj8GTevkXQhrJWOdnobmjF3825L1cIZn4nqnYZmKm2XbOu', 'student', '09557997409', 'sample', '2000-08-08', 'Male', 'Maria Santos', '09169751409', NULL, 1, '2026-08-17 14:14:28', '2026-08-17 14:14:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_enrollment` (`student_id`,`school_year_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `school_year_id` (`school_year_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_grade` (`student_id`,`subject_id`,`quarter`,`school_year_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `encoded_by` (`encoded_by`),
  ADD KEY `school_year_id` (`school_year_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_section` (`name`,`grade_level`,`school_year_id`),
  ADD KEY `school_year_id` (`school_year_id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `lrn` (`lrn`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1346;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`);

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `grades_ibfk_3` FOREIGN KEY (`encoded_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `grades_ibfk_4` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
