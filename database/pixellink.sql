-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 26, 2025 at 08:26 PM
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
-- Database: `pixellink`
--

-- --------------------------------------------------------

--
-- Table structure for table `client_job_requests`
--

CREATE TABLE `client_job_requests` (
  `request_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `budget` varchar(100) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `posted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('open','assigned','completed','cancelled') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_job_requests`
--

INSERT INTO `client_job_requests` (`request_id`, `client_id`, `title`, `description`, `category_id`, `budget`, `deadline`, `posted_date`, `status`) VALUES
(1, 3, 'ต้องการออกแบบแบนเนอร์โฆษณา', 'แบนเนอร์สำหรับโปรโมทสินค้าใหม่ 5 ชิ้น ขนาดต่างๆ', 1, '2,000-5,000 บาท', '2025-06-20', '2025-06-07 15:44:37', 'assigned'),
(2, 5, 'พัฒนาหน้า Landing Page', 'สำหรับแคมเปญการตลาดใหม่ เน้นการแปลงผู้เข้าชมเป็นลูกค้า', 3, '15,000-30,000 บาท', '2025-07-15', '2025-06-07 15:44:37', 'assigned'),
(3, 3, 'จ้างนักออกแบบ Package Product', 'ออกแบบบรรจุภัณฑ์สินค้าใหม่ 3 ชิ้น', 1, '8,000-15,000 บาท', '2025-07-01', '2025-06-07 15:44:37', 'open');

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `contract_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `designer_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `agreed_price` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `contract_status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','partially_paid','refunded') DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contracts`
--

INSERT INTO `contracts` (`contract_id`, `request_id`, `designer_id`, `client_id`, `agreed_price`, `start_date`, `end_date`, `contract_status`, `payment_status`, `created_at`) VALUES
(1, 1, 2, 3, 3500.00, '2025-06-02', '2025-06-15', 'active', 'pending', '2025-06-09 14:41:52');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `application_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `designer_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `proposal_text` text DEFAULT NULL,
  `offered_price` decimal(10,2) DEFAULT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','rejected','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`application_id`, `request_id`, `designer_id`, `client_id`, `proposal_text`, `offered_price`, `application_date`, `status`) VALUES
(1, 1, 2, 3, 'สามารถออกแบบให้ตรงคอนเซ็ปต์และส่งงานได้ตามกำหนดครับ', 3500.00, '2025-06-07 15:44:37', 'pending'),
(2, 2, 2, 5, 'สนใจงาน Landing Page ครับ มีประสบการณ์ด้านนี้โดยตรง สามารถทำให้ติด SEO ได้', 20000.00, '2025-06-07 15:44:37', 'rejected'),
(3, 3, 4, 3, 'ถนัดงานออกแบบแพ็กเกจจิ้งครับ มีตัวอย่างผลงานให้ดู', 10000.00, '2025-06-07 15:44:37', 'pending'),
(4, 1, 2, 3, '0', 2500.00, '2025-06-09 07:07:29', 'rejected');

-- --------------------------------------------------------

--
-- Table structure for table `job_categories`
--

CREATE TABLE `job_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_categories`
--

INSERT INTO `job_categories` (`category_id`, `category_name`) VALUES
(1, 'Graphic Design'),
(5, 'Illustration'),
(4, 'Logo Design'),
(6, 'Photography'),
(2, 'UI/UX Design'),
(3, 'Web Development');

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `post_id` int(11) NOT NULL,
  `designer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price_range` varchar(100) DEFAULT NULL,
  `posted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `client_id` int(11) NOT NULL,
  `main_image_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_postings`
--

INSERT INTO `job_postings` (`post_id`, `designer_id`, `title`, `description`, `category_id`, `price_range`, `posted_date`, `status`, `client_id`, `main_image_id`) VALUES
(1, 2, 'รับงาน UI/UX Design', 'ออกแบบเว็บไซต์และแอปพลิเคชันที่ใช้งานง่ายและสวยงาม', 2, '10,000-25,000 บาท', '2025-06-07 15:44:37', 'active', 0, NULL),
(2, 2, 'บริการออกแบบโลโก้', 'ออกแบบโลโก้สำหรับธุรกิจขนาดเล็กและสตาร์ทอัพ', 4, '3,000-8,000 บาท', '2025-06-07 15:44:37', 'active', 0, NULL),
(3, 4, 'รับวาดภาพประกอบดิจิทัล', 'รับงานภาพประกอบสำหรับหนังสือ, โฆษณา, เกม', 5, '5,000-15,000 บาท', '2025-06-07 15:44:37', 'active', 0, NULL),
(4, 7, 'รับออกแบบโปสเตอร์สินค้า', 'ออกแบบโปสเตอร์โฆษณาสินค้าแบบมืออาชีพ สะดุดตา', 1, '2,500–6,000 บาท', '2025-07-10 16:34:16', 'active', 0, NULL),
(14, 10, 'ทำอินโฟกราฟิกนำเสนอ', 'ออกแบบภาพอินโฟกราฟิกสำหรับพรีเซนต์หรือโซเชียลมีเดีย', 1, '3,000–8,000 บาท', '2025-07-10 16:40:37', 'active', 0, NULL),
(15, 10, 'วาดภาพประกอบนิทาน', 'วาดภาพประกอบแนวเด็กน่ารักสดใส สำหรับหนังสือนิทาน', 2, '5,000–12,000 บาท', '2025-07-10 16:43:43', 'active', 0, NULL),
(16, 4, 'วาดตัวละครแนวแฟนตาซี', 'รับวาดคาแรคเตอร์สไตล์เกม/อนิเมะแฟนตาซี', 2, '8,000–20,000 บาท', '2025-07-10 16:43:43', 'active', 0, NULL),
(17, 7, 'ออกแบบโลโก้แบรนด์แฟชั่น', 'สร้างโลโก้สำหรับแบรนด์เสื้อผ้าหรือแฟชั่นสมัยใหม่', 3, '4,000–10,000 บาท', '2025-07-10 16:43:43', 'active', 0, NULL),
(18, 7, 'โลโก้ธุรกิจท้องถิ่น', 'โลโก้เรียบง่าย เหมาะสำหรับร้านอาหาร คาเฟ่ และ SME', 3, '2,000–5,000 บาท', '2025-07-10 16:43:43', 'active', 0, NULL),
(19, 4, 'ถ่ายภาพโปรไฟล์', 'รับถ่ายภาพโปรไฟล์สำหรับใช้ในงานหรือโซเชียลมีเดีย', 4, '1,500–4,000 บาท', '2025-07-10 16:43:43', 'active', 0, NULL),
(20, 10, 'ถ่ายสินค้าเพื่อขายออนไลน์', 'ถ่ายภาพสินค้าพร้อมแต่งภาพ เหมาะกับตลาดออนไลน์', 4, '3,000–7,000 บาท', '2025-07-10 16:43:43', 'active', 0, NULL),
(21, 2, 'ออกแบบ UI เว็บไซต์', 'ดีไซน์หน้าเว็บให้สวยงาม น่าใช้งาน และตอบโจทย์ UX', 5, '10,000–25,000 บาท', '2025-07-10 16:43:43', 'active', 0, NULL),
(22, 4, 'พัฒนาเว็บไซต์ด้วย HTML/CSS', 'รับสร้างเว็บไซต์พื้นฐานด้วย HTML/CSS ตามแบบที่ลูกค้าต้องการ', 6, '8,000–20,000 บาท', '2025-07-10 16:43:43', 'active', 0, NULL),
(23, 2, 'ออกแบบป้ายต่างๆ', 'รับออกแบบป้าย ทันสมัย,สีสันสดสวย,คุ้มราคา100%', 1, '500–2,000 บาท', '2025-07-23 17:24:13', 'active', 0, NULL),
(24, 2, 'รับวาดปกหนังสือ', 'ได้งานไวมากกกกกกกกกกกกกกกกก', 1, '1,500-3000 บาท', '2025-07-25 17:47:41', 'active', 0, NULL),
(35, 2, 'TEST', 'TEST', 1, '1,500', '2025-07-26 18:24:35', 'active', 0, 22);

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`log_id`, `user_id`, `action`, `details`, `ip_address`, `timestamp`) VALUES
(1, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-23 23:41:02'),
(2, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-23 23:41:10'),
(3, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 00:13:54'),
(4, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 00:14:02'),
(5, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 00:21:15'),
(6, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 00:26:42'),
(7, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 00:26:49'),
(8, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 00:28:20'),
(9, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 01:04:59'),
(10, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 01:05:15'),
(11, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 01:05:24'),
(12, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 01:07:40'),
(13, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 01:33:18'),
(14, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 01:36:37'),
(15, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 01:36:43'),
(16, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 13:21:56'),
(17, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 13:22:02'),
(18, 8, 'Login Successful', 'User logged in: chalida', '::1', '2025-06-24 13:24:52'),
(19, 2, 'Login Attempt Failed', 'Inactive account: khoapun', '::1', '2025-06-24 13:29:42'),
(20, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 13:29:51'),
(21, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 13:29:58'),
(22, 2, 'Login Attempt Failed', 'Inactive account: khoapun', '::1', '2025-06-24 13:33:15'),
(23, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 13:33:36'),
(24, 2, 'Login Attempt Failed', 'Inactive account: khoapun', '::1', '2025-06-24 13:34:04'),
(25, 7, 'Login Successful', 'User logged in: pakawat.in', '::1', '2025-06-24 13:34:19'),
(26, 7, 'Login Attempt Failed', 'Invalid user type: designer for user pakawat.in', '::1', '2025-06-24 13:34:19'),
(27, 2, 'Login Attempt Failed', 'Inactive account: khoapun', '::1', '2025-06-24 13:36:13'),
(28, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 13:36:34'),
(29, 2, 'Login Attempt Failed', 'Inactive account: khoapun', '::1', '2025-06-24 13:38:45'),
(30, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 13:38:51'),
(31, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 13:46:19'),
(32, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 13:46:27'),
(33, 2, 'Login Attempt Failed', 'Inactive account: khoapun', '::1', '2025-06-24 13:47:01'),
(34, 7, 'Login Successful', 'User logged in: pakawat.in', '::1', '2025-06-24 13:49:33'),
(35, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 13:49:42'),
(36, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 13:49:52'),
(37, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 13:50:18'),
(38, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 13:51:04'),
(39, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 13:51:14'),
(40, 8, 'Login Successful', 'User logged in: chalida', '::1', '2025-06-24 13:52:12'),
(41, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 13:52:22'),
(42, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 13:56:22'),
(43, 8, 'Login Attempt Failed', 'Account not approved: chalida', '::1', '2025-06-24 13:56:36'),
(44, 7, 'Login Attempt Failed', 'Incorrect password for: pakawat.in', '::1', '2025-06-24 13:57:34'),
(45, 7, 'Login Attempt Failed', 'Account not approved: pakawat.in', '::1', '2025-06-24 13:57:41'),
(46, 2, 'Login Attempt Failed', 'Inactive account: khoapun', '::1', '2025-06-24 13:57:49'),
(47, 2, 'Login Attempt Failed', 'Inactive account: khoapun', '::1', '2025-06-24 13:58:07'),
(48, 3, 'Login Attempt Failed', 'Account not approved: beer888', '::1', '2025-06-24 13:58:22'),
(49, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 13:58:28'),
(50, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 13:58:32'),
(51, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 13:58:57'),
(52, 2, 'Login Attempt Failed', 'Inactive account: khoapun', '::1', '2025-06-24 13:59:16'),
(53, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-06-24 13:59:33'),
(54, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 13:59:42'),
(55, 2, 'Login Attempt Failed', 'Account not approved: khoapun', '::1', '2025-06-24 14:00:05'),
(56, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 14:00:09'),
(57, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-06-24 14:00:23'),
(58, 7, 'Login Attempt Failed', 'Account not approved: pakawat.in', '::1', '2025-06-24 14:00:41'),
(59, 8, 'Login Attempt Failed', 'Account not approved: chalida', '::1', '2025-06-24 14:00:45'),
(60, 9, 'Login Attempt Failed', 'Account not approved: party', '::1', '2025-06-24 14:53:24'),
(61, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 14:53:32'),
(62, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 14:53:37'),
(63, 8, 'Login Attempt Failed', 'Incorrect password for: chalida', '::1', '2025-06-24 15:50:39'),
(64, 8, 'Login Attempt Failed', 'Incorrect password for: chalida', '::1', '2025-06-24 15:50:54'),
(65, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 15:51:08'),
(66, 8, 'Login Attempt Failed', 'Incorrect password for: chalida', '::1', '2025-06-24 15:52:06'),
(67, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 16:09:30'),
(68, 8, 'Login Attempt Failed', 'Incorrect password for: chalida', '::1', '2025-06-24 16:17:37'),
(69, 8, 'Login Attempt Failed', 'Incorrect password for: chalida', '::1', '2025-06-24 16:17:52'),
(70, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 16:21:24'),
(71, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 16:39:19'),
(72, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 17:18:39'),
(73, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 17:18:43'),
(74, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 17:19:53'),
(75, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-06-24 17:20:05'),
(76, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-06-24 17:25:53'),
(77, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-24 17:31:26'),
(78, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 17:31:36'),
(79, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 17:34:08'),
(80, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-06-24 17:42:04'),
(81, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-30 21:27:26'),
(82, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-30 21:29:33'),
(83, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-06-30 22:40:26'),
(84, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-03 15:43:27'),
(85, 7, 'Login Attempt Failed', 'Incorrect password for: pakawat.in', '::1', '2025-07-03 15:51:11'),
(86, 7, 'Login Attempt Failed', 'Account not approved: pakawat.in', '::1', '2025-07-03 15:51:24'),
(87, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-03 15:51:34'),
(88, 7, 'Login Successful', 'User logged in: pakawat.in', '::1', '2025-07-03 15:52:42'),
(89, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-03 15:53:32'),
(90, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-03 15:55:30'),
(91, 7, 'Login Successful', 'User logged in: pakawat.in', '::1', '2025-07-03 15:55:46'),
(92, 7, 'Login Successful', 'User logged in: pakawat.in', '::1', '2025-07-03 15:56:48'),
(93, 7, 'Login Successful', 'User logged in: pakawat.in', '::1', '2025-07-03 15:57:26'),
(94, 7, 'Login Successful', 'User logged in: pakawat.in', '::1', '2025-07-03 16:04:11'),
(95, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-03 16:05:04'),
(96, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-03 16:05:44'),
(97, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-07 16:25:23'),
(98, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-07 17:44:12'),
(99, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-07 17:47:09'),
(100, 7, 'Login Successful', 'User logged in: pakawat.in', '::1', '2025-07-07 17:47:19'),
(101, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-07 17:47:28'),
(102, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-07 17:49:05'),
(103, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-07 17:49:51'),
(104, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-07 17:50:23'),
(105, 12, 'Login Attempt Failed', 'Account not approved: TESTTTTT', '::1', '2025-07-07 21:55:43'),
(106, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-07 21:55:49'),
(107, 12, 'Login Successful', 'User logged in: TESTTTTT', '::1', '2025-07-07 21:56:08'),
(108, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-10 21:09:13'),
(109, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-10 21:12:19'),
(110, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 21:25:36'),
(111, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 21:26:31'),
(112, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 21:29:30'),
(113, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-10 21:31:35'),
(114, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-10 21:54:16'),
(115, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-10 21:55:25'),
(116, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 21:56:33'),
(117, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 21:57:08'),
(118, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:02:33'),
(119, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:02:56'),
(120, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:04:01'),
(121, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:14:58'),
(122, 7, 'Login Successful', 'User logged in: pakawat.in', '::1', '2025-07-10 22:16:08'),
(123, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:17:44'),
(124, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:20:19'),
(125, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:20:56'),
(126, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:23:07'),
(127, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:38:20'),
(128, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:44:35'),
(129, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 22:45:31'),
(130, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 23:06:27'),
(131, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 23:09:25'),
(132, 2, 'Login Attempt Failed', 'Incorrect password for: khoapun', '::1', '2025-07-10 23:10:40'),
(133, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 23:12:43'),
(134, 2, 'Login Attempt Failed', 'Incorrect password for: khoapun', '::1', '2025-07-10 23:16:05'),
(135, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 23:16:10'),
(136, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 23:21:58'),
(137, 2, 'Login Attempt Failed', 'Incorrect password for: khoapun', '::1', '2025-07-10 23:24:56'),
(138, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 23:25:02'),
(139, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 23:51:40'),
(140, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 23:56:44'),
(141, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 23:58:46'),
(142, 2, 'Login Attempt Failed', 'Incorrect password for: khoapun', '::1', '2025-07-10 23:59:08'),
(143, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-10 23:59:15'),
(144, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:03:57'),
(145, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:07:26'),
(146, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:11:49'),
(147, 2, 'Login Attempt Failed', 'Incorrect password for: khoapun', '::1', '2025-07-11 00:14:06'),
(148, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:14:12'),
(149, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:17:38'),
(150, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:23:46'),
(151, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:24:55'),
(152, NULL, 'Login Attempt Failed', 'Username not found: ิbeer888', '::1', '2025-07-11 00:31:56'),
(153, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:32:01'),
(154, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:33:33'),
(155, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:35:41'),
(156, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:40:28'),
(157, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:43:42'),
(158, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:44:27'),
(159, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:45:10'),
(160, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:45:51'),
(161, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:46:05'),
(162, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:46:22'),
(163, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:47:29'),
(164, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:48:51'),
(165, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:49:03'),
(166, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:50:51'),
(167, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:54:22'),
(168, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 00:58:14'),
(169, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 00:59:05'),
(170, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 01:00:21'),
(171, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 01:02:51'),
(172, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 01:05:20'),
(173, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-11 14:59:35'),
(174, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-11 14:59:51'),
(175, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-11 15:01:03'),
(176, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-12 21:07:26'),
(177, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-12 21:08:08'),
(178, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-12 21:09:57'),
(179, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-12 21:12:23'),
(180, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-12 21:13:50'),
(181, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-12 21:16:22'),
(182, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-12 21:17:26'),
(183, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-12 21:45:17'),
(184, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-12 21:46:09'),
(185, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-12 21:52:44'),
(186, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-12 22:01:20'),
(187, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-12 22:49:31'),
(188, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-12 23:42:55'),
(189, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-13 01:15:06'),
(190, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-13 02:18:40'),
(191, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-13 02:21:01'),
(192, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-13 02:21:31'),
(193, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 20:57:52'),
(194, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-07-14 20:58:50'),
(195, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-14 20:58:54'),
(196, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-14 21:24:47'),
(197, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-14 21:28:49'),
(198, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 21:32:24'),
(199, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-14 21:58:34'),
(200, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 21:59:48'),
(201, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-14 22:05:16'),
(202, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 22:10:23'),
(203, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-07-14 22:12:31'),
(204, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 22:12:36'),
(205, NULL, 'Login Attempt Failed', 'Username not found: ad', '::1', '2025-07-14 22:28:43'),
(206, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-07-14 22:28:54'),
(207, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-14 22:29:01'),
(208, 2, 'Login Attempt Failed', 'Incorrect password for: khoapun', '::1', '2025-07-14 22:31:13'),
(209, NULL, 'Login Attempt Failed', 'Username not found: khoa', '::1', '2025-07-14 22:31:18'),
(210, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 22:31:23'),
(211, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-07-14 22:41:19'),
(212, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 22:41:25'),
(213, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 22:42:54'),
(214, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 23:27:42'),
(215, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 23:27:59'),
(216, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 23:31:43'),
(217, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 23:38:44'),
(218, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 23:48:37'),
(219, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 23:48:59'),
(220, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 23:52:41'),
(221, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-14 23:53:08'),
(222, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-15 00:00:49'),
(223, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-15 00:14:17'),
(224, 3, 'Login Successful', 'User logged in: beer888', '::1', '2025-07-15 00:14:30'),
(225, 1, 'Login Attempt Failed', 'Incorrect password for: admin', '::1', '2025-07-15 00:22:44'),
(226, NULL, 'Login Attempt Failed', 'Username not found: asd', '::1', '2025-07-15 00:25:48'),
(227, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-15 00:26:06'),
(228, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-15 00:26:24'),
(229, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-15 00:33:42'),
(230, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-16 23:52:18'),
(231, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-23 23:02:23'),
(232, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-23 23:52:03'),
(233, 10, 'Login Attempt Failed', 'Account not approved: party888', '::1', '2025-07-24 00:38:34'),
(234, 1, 'Login Successful', 'User logged in: admin', '::1', '2025-07-24 00:38:42'),
(235, 10, 'Login Successful', 'User logged in: party888', '::1', '2025-07-24 00:38:59'),
(236, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-24 00:42:01'),
(237, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-24 00:43:24'),
(238, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-24 01:19:10'),
(239, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-24 01:37:09'),
(240, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-25 23:02:47'),
(241, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-25 23:05:17'),
(242, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-25 23:09:45'),
(243, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-25 23:11:03'),
(244, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-25 23:13:02'),
(245, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-25 23:15:45'),
(246, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-25 23:21:05'),
(247, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-25 23:25:44'),
(248, 2, 'Login Attempt Failed', 'Incorrect password for: khoapun', '::1', '2025-07-26 00:02:09'),
(249, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-26 00:02:14'),
(250, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-26 00:04:40'),
(251, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-26 00:14:14'),
(252, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-26 00:19:03'),
(253, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-26 01:36:06'),
(254, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-26 01:36:45'),
(255, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-26 01:37:26'),
(256, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-26 22:38:44'),
(257, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-27 00:00:42'),
(258, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-27 00:59:09'),
(259, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-27 01:01:56'),
(260, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-27 01:04:26'),
(261, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-27 01:05:48'),
(262, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-27 01:06:22'),
(263, 2, 'Login Successful', 'User logged in: khoapun', '::1', '2025-07-27 01:09:05');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `message_text` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `contract_id`, `message_text`, `sent_at`, `is_read`) VALUES
(1, 3, 2, 1, 'สวัสดีครับ สนใจงานแบนเนอร์ที่ประกาศไว้ อยากทราบรายละเอียดเพิ่มเติม', '2025-06-07 15:44:37', 1),
(2, 2, 3, 1, 'สวัสดีครับ ยินดีให้บริการครับ มีอะไรสอบถามได้เลย', '2025-06-07 15:44:37', 1),
(3, 5, 2, NULL, 'รบกวนสอบถามเกี่ยวกับงาน Landing Page ครับ ยังรับอยู่ไหม', '2025-06-07 15:44:37', 0);

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`profile_id`, `user_id`, `address`, `company_name`, `bio`, `portfolio_url`, `skills`, `profile_picture_url`) VALUES
(1, 2, '123 Design St, BKK', NULL, 'Passionate UI/UX designer.', 'https://www.twitch.tv/', 'UX/UI, Figma, Photoshop, AI', '/uploads/jane.jpg'),
(2, 3, '456 Business Rd, Nonthaburi', 'Acme Corp', NULL, NULL, NULL, '/uploads/bob.jpg'),
(3, 4, '789 Art Ave, Chiang Mai', NULL, 'Junior graphic designer looking for freelance work.', 'anna.artstation.com', 'Photoshop, Illustrator', '/uploads/anna.png'),
(4, 5, '101 Tech Tower, Bangkok', 'Tech Corp', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reported_user_id` int(11) DEFAULT NULL,
  `reported_post_id` int(11) DEFAULT NULL,
  `report_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `report_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','resolved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `reporter_id`, `reported_user_id`, `reported_post_id`, `report_type`, `description`, `report_date`, `status`) VALUES
(1, 3, NULL, NULL, 'spam', 'มีผู้ใช้งานส่งข้อความสแปมเข้ามาในแชท', '2025-06-07 15:44:37', 'pending'),
(2, 2, 5, NULL, 'user_misconduct', 'ผู้ว่าจ้างไม่ตอบกลับหลังจากตกลงราคาแล้ว', '2025-06-07 15:44:37', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewed_user_id` int(11) NOT NULL,
  `rating` decimal(2,1) DEFAULT NULL CHECK (`rating` >= 1.0 and `rating` <= 5.0),
  `comment` text DEFAULT NULL,
  `review_type` enum('designer_review_client','client_review_designer') DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `contract_id`, `reviewer_id`, `reviewed_user_id`, `rating`, `comment`, `review_type`, `review_date`, `status`) VALUES
(1, 1, 3, 2, 5.0, 'ออกแบบได้สวยงาม รวดเร็ว และเข้าใจความต้องการเป็นอย่างดีครับ', 'client_review_designer', '2025-06-07 15:44:37', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'site_name', 'PixelLink Platform', 'ชื่อเว็บไซต์หรือแพลตฟอร์ม', '2025-06-09 14:46:36'),
(2, 'admin_email', 'admin@example.com', 'อีเมลสำหรับผู้ดูแลระบบหรือการแจ้งเตือน', '2025-06-09 14:46:36'),
(3, 'platform_commission_rate', '10.00', 'อัตราค่าคอมมิชชั่นของแพลตฟอร์ม (เป็นเปอร์เซ็นต์)', '2025-06-09 14:46:36'),
(4, 'min_withdrawal_amount', '500', 'จำนวนเงินขั้นต่ำในการถอนเงิน', '2025-06-09 14:46:36');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `payee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `contract_id`, `payer_id`, `payee_id`, `amount`, `transaction_date`, `payment_method`, `status`) VALUES
(1, 1, 3, 2, 3500.00, '2025-06-07 15:44:37', 'Credit Card', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `uploaded_files`
--

CREATE TABLE `uploaded_files` (
  `file_id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `job_post_id` int(11) DEFAULT NULL,
  `uploader_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `uploaded_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uploaded_files`
--

INSERT INTO `uploaded_files` (`file_id`, `contract_id`, `job_post_id`, `uploader_id`, `file_name`, `file_path`, `uploaded_at`, `file_size`, `uploaded_by_user_id`, `file_type`, `uploaded_date`) VALUES
(22, NULL, NULL, 2, '', '../uploads/job_images/job_img_68851d6311505.png', '2025-07-26 18:24:35', 27410, 2, 'image/png', '2025-07-27 01:24:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `user_type` enum('admin','designer','client') NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `first_name`, `last_name`, `phone_number`, `user_type`, `registration_date`, `is_approved`, `is_active`, `last_login`) VALUES
(1, 'admin', '12345678', 'admin@pixellink.com', 'กฤษดา', 'บุญจันดา', '0901234567', 'admin', '2025-06-07 15:44:37', 1, 1, NULL),
(2, 'khoapun', '1234', 'jane@example.com', 'ศิขริน', 'คอมิธิน', '0812345678', 'designer', '2025-06-07 15:44:37', 1, 1, NULL),
(3, 'beer888', '1234', 'bob@company.com', 'เบียร์', 'สมิท', '0987654321', 'client', '2025-06-07 15:44:37', 1, 1, NULL),
(4, 'anna_design', 'anna_pass', 'anna@portfolio.net', 'Anna', 'Lee', '0891112222', 'designer', '2025-06-07 15:44:37', 1, 1, NULL),
(5, 'tech_corp', 'tech_pass', 'hr@techcorp.com', 'Tech', 'Corp HR', '029998888', 'client', '2025-06-07 15:44:37', 0, 1, NULL),
(6, 'krit.ti', '12345678', 'krit.ti@rmuti.ac.th', 'Krit', 'T.siriwattana', '0000000000', 'admin', '2025-06-08 11:16:59', 1, 1, NULL),
(7, 'kitsada.in', '1234', 'pakawat.in@gmail.com', 'kitsada', 'Ariyawatkul\r\n', '0000000000', 'designer', '2025-06-09 07:58:49', 1, 1, NULL),
(10, 'party888', '1234', 'kkiii@gmail.com', 'กิตติพงศ์', 'เถื่อนกลาง', '0555555555', 'designer', '2025-06-24 09:38:07', 1, 1, NULL),
(12, 'TESTTTTT', 'Test_lll123456789@', 'KKKKKKK@gmail.com', 'TEST', 'PROJECT1', '0999999999', 'designer', '2025-07-07 14:54:31', 0, 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `client_job_requests`
--
ALTER TABLE `client_job_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`contract_id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD KEY `designer_id` (`designer_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `designer_id` (`designer_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `job_categories`
--
ALTER TABLE `job_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `designer_id` (`designer_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_user_id` (`reported_user_id`),
  ADD KEY `reported_post_id` (`reported_post_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `contract_id` (`contract_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `reviewed_user_id` (`reviewed_user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `contract_id` (`contract_id`),
  ADD KEY `payer_id` (`payer_id`),
  ADD KEY `payee_id` (`payee_id`);

--
-- Indexes for table `uploaded_files`
--
ALTER TABLE `uploaded_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `contract_id` (`contract_id`),
  ADD KEY `uploader_id` (`uploader_id`),
  ADD KEY `fk_job_post_id` (`job_post_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client_job_requests`
--
ALTER TABLE `client_job_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `contract_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `job_categories`
--
ALTER TABLE `job_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=264;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `uploaded_files`
--
ALTER TABLE `uploaded_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `client_job_requests`
--
ALTER TABLE `client_job_requests`
  ADD CONSTRAINT `client_job_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `client_job_requests_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`category_id`);

--
-- Constraints for table `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `client_job_requests` (`request_id`),
  ADD CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`designer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `contracts_ibfk_3` FOREIGN KEY (`client_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `client_job_requests` (`request_id`),
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`designer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `job_applications_ibfk_3` FOREIGN KEY (`client_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD CONSTRAINT `job_postings_ibfk_1` FOREIGN KEY (`designer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `job_postings_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`category_id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`contract_id`);

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`reported_post_id`) REFERENCES `job_postings` (`post_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`contract_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`reviewed_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`contract_id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`payer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`payee_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `uploaded_files`
--
ALTER TABLE `uploaded_files`
  ADD CONSTRAINT `fk_job_post_id` FOREIGN KEY (`job_post_id`) REFERENCES `job_postings` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `uploaded_files_ibfk_2` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
