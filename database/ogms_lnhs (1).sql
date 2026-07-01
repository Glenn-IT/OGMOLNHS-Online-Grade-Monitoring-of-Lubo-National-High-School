-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 01, 2026 at 09:15 AM
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
(1, 10, 1, 1, '2026-06-18 16:04:26'),
(2, 11, 1, 1, '2026-06-18 16:04:27'),
(3, 12, 2, 1, '2026-06-18 16:04:27'),
(4, 13, 2, 1, '2026-06-18 16:04:27'),
(5, 14, 3, 1, '2026-06-18 16:04:27');

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
(1, 10, 1, 1, 88.00, 88.00, 88.00, 88.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:11:33'),
(2, 10, 1, 2, 85.00, 85.00, 85.00, 85.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(3, 10, 1, 3, 90.00, 90.00, 90.00, 90.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(4, 10, 1, 4, 87.00, 87.00, 87.00, 87.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(5, 10, 2, 1, 92.00, 92.00, 92.00, 92.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(6, 10, 2, 2, 89.00, 89.00, 89.00, 89.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(7, 10, 2, 3, 91.00, 91.00, 91.00, 91.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(8, 10, 2, 4, 93.00, 93.00, 93.00, 93.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(9, 10, 3, 1, 85.00, 85.00, 85.00, 85.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(10, 10, 3, 2, 87.00, 87.00, 87.00, 87.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(11, 10, 3, 3, 84.00, 84.00, 84.00, 84.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(12, 10, 3, 4, 86.00, 86.00, 86.00, 86.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(13, 10, 4, 1, 90.00, 90.00, 90.00, 90.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(14, 10, 4, 2, 88.00, 88.00, 88.00, 88.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(15, 10, 4, 3, 92.00, 92.00, 92.00, 92.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(16, 10, 4, 4, 91.00, 91.00, 91.00, 91.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(17, 10, 5, 1, 83.00, 83.00, 83.00, 83.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(18, 10, 5, 2, 85.00, 85.00, 85.00, 85.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(19, 10, 5, 3, 80.00, 80.00, 80.00, 80.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(20, 10, 5, 4, 82.00, 82.00, 82.00, 82.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(21, 10, 6, 1, 94.00, 94.00, 94.00, 94.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(22, 10, 6, 2, 92.00, 92.00, 92.00, 92.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(23, 10, 6, 3, 95.00, 95.00, 95.00, 95.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(24, 10, 6, 4, 93.00, 93.00, 93.00, 93.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(25, 10, 7, 1, 89.00, 89.00, 89.00, 89.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(26, 10, 7, 2, 91.00, 91.00, 91.00, 91.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(27, 10, 7, 3, 88.00, 88.00, 88.00, 88.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(28, 10, 7, 4, 90.00, 90.00, 90.00, 90.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(29, 10, 8, 1, 95.00, 95.00, 95.00, 95.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(30, 10, 8, 2, 93.00, 93.00, 93.00, 93.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(31, 10, 8, 3, 96.00, 96.00, 96.00, 96.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(32, 10, 8, 4, 94.00, 94.00, 94.00, 94.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(33, 11, 1, 1, 78.00, 78.00, 78.00, 78.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(34, 11, 1, 2, 80.00, 80.00, 80.00, 80.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(35, 11, 1, 3, 82.00, 82.00, 82.00, 82.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(36, 11, 1, 4, 79.00, 79.00, 79.00, 79.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(37, 11, 2, 1, 85.00, 85.00, 85.00, 85.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(38, 11, 2, 2, 83.00, 83.00, 83.00, 83.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(39, 11, 2, 3, 87.00, 87.00, 87.00, 87.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(40, 11, 2, 4, 86.00, 86.00, 86.00, 86.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(41, 11, 3, 1, 92.00, 92.00, 92.00, 92.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(42, 11, 3, 2, 94.00, 94.00, 94.00, 94.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(43, 11, 3, 3, 91.00, 91.00, 91.00, 91.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(44, 11, 3, 4, 93.00, 93.00, 93.00, 93.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(45, 11, 4, 1, 88.00, 88.00, 88.00, 88.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(46, 11, 4, 2, 87.00, 87.00, 87.00, 87.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(47, 11, 4, 3, 89.00, 89.00, 89.00, 89.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(48, 11, 4, 4, 90.00, 90.00, 90.00, 90.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(49, 11, 5, 1, 75.00, 75.00, 75.00, 75.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(50, 11, 5, 2, 77.00, 77.00, 77.00, 77.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(51, 11, 5, 3, 76.00, 76.00, 76.00, 76.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(52, 11, 5, 4, 78.00, 78.00, 78.00, 78.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(53, 11, 6, 1, 82.00, 82.00, 82.00, 82.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(54, 11, 6, 2, 84.00, 84.00, 84.00, 84.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(55, 11, 6, 3, 81.00, 81.00, 81.00, 81.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(56, 11, 6, 4, 83.00, 83.00, 83.00, 83.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(57, 11, 7, 1, 79.00, 79.00, 79.00, 79.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(58, 11, 7, 2, 81.00, 81.00, 81.00, 81.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(59, 11, 7, 3, 80.00, 80.00, 80.00, 80.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(60, 11, 7, 4, 82.00, 82.00, 82.00, 82.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(61, 11, 8, 1, 90.00, 90.00, 90.00, 90.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(62, 11, 8, 2, 88.00, 88.00, 88.00, 88.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(63, 11, 8, 3, 91.00, 91.00, 91.00, 91.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(64, 11, 8, 4, 89.00, 89.00, 89.00, 89.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(65, 12, 1, 1, 72.00, 72.00, 72.00, 72.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(66, 12, 1, 2, 74.00, 74.00, 74.00, 74.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(67, 12, 1, 3, 73.00, 73.00, 73.00, 73.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(68, 12, 1, 4, 75.00, 75.00, 75.00, 75.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(69, 12, 2, 1, 80.00, 80.00, 80.00, 80.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(70, 12, 2, 2, 79.00, 79.00, 79.00, 79.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(71, 12, 2, 3, 81.00, 81.00, 81.00, 81.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(72, 12, 2, 4, 82.00, 82.00, 82.00, 82.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(73, 12, 3, 1, 76.00, 76.00, 76.00, 76.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(74, 12, 3, 2, 78.00, 78.00, 78.00, 78.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(75, 12, 3, 3, 75.00, 75.00, 75.00, 75.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(76, 12, 3, 4, 77.00, 77.00, 77.00, 77.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(77, 12, 4, 1, 83.00, 83.00, 83.00, 83.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(78, 12, 4, 2, 81.00, 81.00, 81.00, 81.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(79, 12, 4, 3, 84.00, 84.00, 84.00, 84.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(80, 12, 4, 4, 82.00, 82.00, 82.00, 82.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(81, 12, 5, 1, 70.00, 70.00, 70.00, 70.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(82, 12, 5, 2, 72.00, 72.00, 72.00, 72.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(83, 12, 5, 3, 71.00, 71.00, 71.00, 71.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(84, 12, 5, 4, 74.00, 74.00, 74.00, 74.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(85, 12, 6, 1, 85.00, 85.00, 85.00, 85.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(86, 12, 6, 2, 83.00, 83.00, 83.00, 83.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(87, 12, 6, 3, 86.00, 86.00, 86.00, 86.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(88, 12, 6, 4, 84.00, 84.00, 84.00, 84.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(89, 12, 7, 1, 88.00, 88.00, 88.00, 88.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(90, 12, 7, 2, 87.00, 87.00, 87.00, 87.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(91, 12, 7, 3, 89.00, 89.00, 89.00, 89.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(92, 12, 7, 4, 90.00, 90.00, 90.00, 90.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(93, 12, 8, 1, 79.00, 79.00, 79.00, 79.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(94, 12, 8, 2, 80.00, 80.00, 80.00, 80.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(95, 12, 8, 3, 78.00, 78.00, 78.00, 78.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(96, 12, 8, 4, 81.00, 81.00, 81.00, 81.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(97, 13, 1, 1, 95.00, 95.00, 95.00, 95.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(98, 13, 1, 2, 93.00, 93.00, 93.00, 93.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(99, 13, 1, 3, 96.00, 96.00, 96.00, 96.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(100, 13, 1, 4, 94.00, 94.00, 94.00, 94.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(101, 13, 2, 1, 97.00, 97.00, 97.00, 97.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(102, 13, 2, 2, 95.00, 95.00, 95.00, 95.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(103, 13, 2, 3, 98.00, 98.00, 98.00, 98.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(104, 13, 2, 4, 96.00, 96.00, 96.00, 96.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(105, 13, 3, 1, 93.00, 93.00, 93.00, 93.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(106, 13, 3, 2, 95.00, 95.00, 95.00, 95.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(107, 13, 3, 3, 92.00, 92.00, 92.00, 92.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(108, 13, 3, 4, 94.00, 94.00, 94.00, 94.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(109, 13, 4, 1, 91.00, 91.00, 91.00, 91.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(110, 13, 4, 2, 93.00, 93.00, 93.00, 93.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(111, 13, 4, 3, 90.00, 90.00, 90.00, 90.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(112, 13, 4, 4, 92.00, 92.00, 92.00, 92.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(113, 13, 5, 1, 89.00, 89.00, 89.00, 89.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(114, 13, 5, 2, 91.00, 91.00, 91.00, 91.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(115, 13, 5, 3, 88.00, 88.00, 88.00, 88.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(116, 13, 5, 4, 90.00, 90.00, 90.00, 90.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(117, 13, 6, 1, 96.00, 96.00, 96.00, 96.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(118, 13, 6, 2, 94.00, 94.00, 94.00, 94.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(119, 13, 6, 3, 97.00, 97.00, 97.00, 97.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(120, 13, 6, 4, 95.00, 95.00, 95.00, 95.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(121, 13, 7, 1, 92.00, 92.00, 92.00, 92.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(122, 13, 7, 2, 94.00, 94.00, 94.00, 94.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(123, 13, 7, 3, 91.00, 91.00, 91.00, 91.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(124, 13, 7, 4, 93.00, 93.00, 93.00, 93.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(125, 13, 8, 1, 98.00, 98.00, 98.00, 98.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(126, 13, 8, 2, 96.00, 96.00, 96.00, 96.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(127, 13, 8, 3, 97.00, 97.00, 97.00, 97.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(128, 13, 8, 4, 95.00, 95.00, 95.00, 95.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(129, 14, 1, 1, 68.00, 68.00, 68.00, 68.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(130, 14, 1, 2, 70.00, 70.00, 70.00, 70.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(131, 14, 1, 3, 72.00, 72.00, 72.00, 72.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(132, 14, 1, 4, 71.00, 71.00, 71.00, 71.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(133, 14, 2, 1, 75.00, 75.00, 75.00, 75.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(134, 14, 2, 2, 73.00, 73.00, 73.00, 73.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(135, 14, 2, 3, 76.00, 76.00, 76.00, 76.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(136, 14, 2, 4, 74.00, 74.00, 74.00, 74.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(137, 14, 3, 1, 71.00, 71.00, 71.00, 71.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(138, 14, 3, 2, 73.00, 73.00, 73.00, 73.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(139, 14, 3, 3, 70.00, 70.00, 70.00, 70.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(140, 14, 3, 4, 72.00, 72.00, 72.00, 72.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(141, 14, 4, 1, 77.00, 77.00, 77.00, 77.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(142, 14, 4, 2, 75.00, 75.00, 75.00, 75.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(143, 14, 4, 3, 78.00, 78.00, 78.00, 78.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(144, 14, 4, 4, 76.00, 76.00, 76.00, 76.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(145, 14, 5, 1, 65.00, 65.00, 65.00, 65.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(146, 14, 5, 2, 67.00, 67.00, 67.00, 67.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(147, 14, 5, 3, 66.00, 66.00, 66.00, 66.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(148, 14, 5, 4, 68.00, 68.00, 68.00, 68.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(149, 14, 6, 1, 80.00, 80.00, 80.00, 80.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(150, 14, 6, 2, 78.00, 78.00, 78.00, 78.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(151, 14, 6, 3, 81.00, 81.00, 81.00, 81.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(152, 14, 6, 4, 79.00, 79.00, 79.00, 79.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(153, 14, 7, 1, 83.00, 83.00, 83.00, 83.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(154, 14, 7, 2, 81.00, 81.00, 81.00, 81.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(155, 14, 7, 3, 84.00, 84.00, 84.00, 84.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(156, 14, 7, 4, 82.00, 82.00, 82.00, 82.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(157, 14, 8, 1, 74.00, 74.00, 74.00, 74.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(158, 14, 8, 2, 76.00, 76.00, 76.00, 76.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(159, 14, 8, 3, 73.00, 73.00, 73.00, 73.00, 'Failed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(160, 14, 8, 4, 75.00, 75.00, 75.00, 75.00, 'Passed', 1, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27');

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

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 17, 'ce5804272c28556428a21142d7de9cd57b05d03b0680b92b0af7fc6b0d8687b1', '2026-07-01 09:32:35', 0, '2026-07-01 06:32:35'),
(2, 17, 'd178c6fff7403ab142a734291318fac35230ffa92346e883b7248427b92103bb', '2026-07-01 09:45:29', 0, '2026-07-01 06:45:29'),
(3, 17, '41193b619cbaa53b88d193668f5c154fd201ade7da4aa663f6758af71e02e5bd', '2026-07-01 09:49:45', 0, '2026-07-01 06:49:45'),
(4, 17, '415498', '2026-07-01 09:07:48', 0, '2026-07-01 06:52:48'),
(5, 17, '225286', '2026-07-01 15:11:29', 1, '2026-07-01 06:56:29'),
(6, 17, '783815', '2026-07-01 15:29:37', 1, '2026-07-01 07:14:37');

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
(1, '2024-2025', 1);

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
(1, 'Grade 10 - Rizal', 10, 1),
(2, 'Grade 10 - Bonifacio', 10, 1),
(3, 'Grade 10 - Mabini', 10, 1),
(4, 'Grade 7 - Rizal', 7, 1),
(5, 'Grade 7 - Bonifacio', 7, 1),
(6, 'Grade 7 - Mabini', 7, 1),
(7, 'Grade 8 - Rizal', 8, 1),
(8, 'Grade 8 - Bonifacio', 8, 1),
(9, 'Grade 8 - Mabini', 8, 1),
(10, 'Grade 9 - Rizal', 9, 1),
(11, 'Grade 9 - Bonifacio', 9, 1),
(12, 'Grade 9 - Mabini', 9, 1);

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
(1, 'Mathematics', 'MATH', 2),
(2, 'Science', 'SCI', 3),
(3, 'English', 'ENG', 4),
(4, 'Filipino', 'FIL', 5),
(5, 'Araling Panlipunan', 'AP', 6),
(6, 'MAPEH', 'MAPEH', 7),
(7, 'TLE', 'TLE', 8),
(8, 'Values Education', 'VE', 9);

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
  `avatar_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `lrn`, `full_name`, `email`, `password`, `role`, `phone`, `address`, `birthdate`, `gender`, `avatar_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Administrator', 'admin@lnhs.edu.ph', '$2y$10$SXMzNtCAy.utx6sEtU4imuzXdRk18pEUGrFoVlIrs3filvasZQiYC', 'admin', '09111222333', NULL, NULL, NULL, NULL, 1, '2026-06-18 16:04:26', '2026-06-19 08:08:02'),
(2, NULL, 'Mr. Roberto Santos', 'roberts@lnhs.edu.ph', '$2y$10$1FD9m7gAI6geVYgtj0AMceGqUqRw2Hm8FP/JMReCoZxxAD6txV7fO', 'teacher', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-18 16:04:26', '2026-06-18 16:04:26'),
(3, NULL, 'Ms. Elena Reyes', 'elenar@lnhs.edu.ph', '$2y$10$67UGSU.ynNShiVNVjPkx9.WE3.pCKC3XYPBWnBCrNBi7vtJ1RWkPO', 'teacher', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-18 16:04:26', '2026-06-18 16:04:26'),
(4, NULL, 'Ms. Patricia Cruz', 'patriciac@lnhs.edu.ph', '$2y$10$NoxONUnK/SBuxmgIjmhoe.TOOXV3Drmv7Y7l3/CbDMvbYnufMa65K', 'teacher', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-18 16:04:26', '2026-06-18 16:04:26'),
(5, NULL, 'Mr. Antonio Ramos', 'antonior@lnhs.edu.ph', '$2y$10$tO08ieenxM3xOOL4FsafK.PF3OdrATIB4ZY3pFGBRjy.t1qkny/BO', 'teacher', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-18 16:04:26', '2026-06-18 16:04:26'),
(6, NULL, 'Mrs. Carmen Flores', 'carmenf@lnhs.edu.ph', '$2y$10$OgnERGFJMBcJw3JypvTHRu75Cs.ZOcyaFbzGWkYN2yByUIatAgvcS', 'teacher', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-18 16:04:26', '2026-06-18 16:04:26'),
(7, NULL, 'Mr. Joseph Villanueva', 'josephv@lnhs.edu.ph', '$2y$10$ovzkDMAuLTv3hNugakYVoOzaOEHpu4KiDY3bL65xbzamTbRJn.yBG', 'teacher', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-18 16:04:26', '2026-06-18 16:04:26'),
(8, NULL, 'Ms. Grace Domingo', 'graced@lnhs.edu.ph', '$2y$10$qYUt1G5oWddtpF2E5P3fIutLdAjxzDG1cPq4IL49pqjLt/6sWo4Xy', 'teacher', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-18 16:04:26', '2026-06-18 16:04:26'),
(9, NULL, 'Mrs. Maria Concepcion', 'mariac@lnhs.edu.ph', '$2y$10$uxF8fFDFxIUipCShVV4KMelJ63pyYbU2nWoP4YxVcNFUX6v42HSNu', 'teacher', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-18 16:04:26', '2026-06-18 16:04:26'),
(10, '202400000001', 'Juan dela Cruz', 'student@lnhs.edu.ph', '$2y$10$4bXdtdeKyrNXz4zpRfQrf.SWF8AAhDW3ye9Hqx.ah8vlm.t0kBPaW', 'student', '09123456789', 'Brgy. Lubo, Cavite City', '2008-05-15', 'Male', NULL, 1, '2026-06-18 16:04:26', '2026-06-18 16:04:26'),
(11, '202400000002', 'Maria Santos', 'maria@lnhs.edu.ph', '$2y$10$coNCZd0h5Y.M3w9IHjVrcO0vKvlBzLz5Fl3nePUiwceZuG0LRvkZu', 'student', '09234567890', 'Brgy. Lubo, Cavite City', '2008-03-22', 'Female', NULL, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(12, '202400000003', 'Pedro Reyes', 'pedro@lnhs.edu.ph', '$2y$10$dKimmO2GOIDoh0K7uhFNWurjZMyEizSZubcAPq2GN4IgKV8AIOdFC', 'student', '09345678901', 'Brgy. Lubo, Cavite City', '2008-07-10', 'Male', NULL, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(13, '202400000004', 'Ana Garcia', 'ana@lnhs.edu.ph', '$2y$10$cRqX2ZwprAfJVjyibkhrB.9Ih/W66987omblQt0lvkIIRQbJvqLIu', 'student', '09456789012', 'Brgy. Lubo, Cavite City', '2008-11-05', 'Female', NULL, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(14, '202400000005', 'Carlos Mendoza', 'carlos@lnhs.edu.ph', '$2y$10$eiDjLOlwU0GzkH6wPcidJeW/IYlnYzfmnK7dnV7QYblxbnHS6cyBa', 'student', '09567890123', 'Brgy. Lubo, Cavite City', '2008-09-18', 'Male', NULL, 1, '2026-06-18 16:04:27', '2026-06-18 16:04:27'),
(16, '123456788912', 'Glenard Pagurayan', 'glenn@gmail.com', '$2y$10$MsGPDaCW9G0kVRr9WAunfuWWEmTIy.6K.uNnYgaSJJePXiHrOcLQm', 'student', NULL, NULL, NULL, NULL, NULL, 1, '2026-06-19 08:20:36', '2026-06-19 08:20:36'),
(17, '012839810298', 'Glenn Pag', 'glenard2308@gmail.com', '$2y$10$.BxxCi/c21gn1m2yRpoDqOaNYcYDDlQ.kDNLBLARLH5nd23HPrtsy', 'student', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-01 06:32:12', '2026-07-01 07:15:01');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
