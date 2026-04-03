-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 03, 2026 at 06:07 PM
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
-- Database: `downtimedb`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(4, 2, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:05:18'),
(5, 2, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:05:57'),
(6, 2, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:07:53'),
(7, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:13:11'),
(8, 18, 'created_service', 'Created service: Mobile Money', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:20:11'),
(9, 18, 'created_component', 'Created component: Credit for service ID 141', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:25:17'),
(10, 18, 'created_component', 'Created component: Debit for service ID 141', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:25:17'),
(11, 18, 'created_component', 'Created component: Reversal for service ID 141', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:25:17'),
(12, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:37:28'),
(13, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:37:30'),
(14, 18, 'created_service', 'Created service: VAS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:54:40'),
(15, 18, 'updated_service', 'Updated service ID 142 to: VASGATE', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:54:47'),
(16, 18, 'created_service', 'Created service: FUNDGATE', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:55:01'),
(17, 18, 'created_service', 'Created service: MPAY', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:55:13'),
(18, 18, 'created_service', 'Created service: JUSTPAY', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:55:57'),
(19, 18, 'created_service', 'Created service: OVA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:56:41'),
(20, 18, 'created_component', 'Created component: Bills for service ID 142', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:57:42'),
(21, 18, 'created_component', 'Created component: Topup for service ID 142', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-18 23:57:42'),
(22, 18, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:31:57'),
(23, 18, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:32:14'),
(24, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 00:32:32'),
(25, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:32:43'),
(26, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:34:28'),
(27, 18, 'used_template', 'Used incident template: FUNDGATE B2W Transfer Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:46:04'),
(28, 18, 'used_template', 'Used incident template: FUNDGATE Credit Processing Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:46:21'),
(29, 18, 'used_template', 'Used incident template: Insufficient Account Balance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:46:56'),
(30, 18, 'used_template', 'Used incident template: Insufficient Account Balance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:51:11'),
(31, 18, 'used_template', 'Used incident template: Application Not Responding', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:51:15'),
(32, 18, 'used_template', 'Used incident template: FUNDGATE B2W Transfer Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:51:17'),
(33, 18, 'used_template', 'Used incident template: Application Not Responding', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:51:39'),
(34, 18, 'used_template', 'Used incident template: Bank-to-Wallet (B2W) Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:51:55'),
(35, 18, 'updated_template', 'Updated incident template: Bank-to-Wallet (B2W) Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:56:09'),
(36, 18, 'used_template', 'Used incident template: Bank-to-Wallet (B2W) Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 00:56:28'),
(37, 18, 'used_template', 'Used incident template: Host Connectivity Issue', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 01:13:55'),
(38, 18, 'used_template', 'Used incident template: Application Not Responding', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 01:14:03'),
(39, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 01:14:32'),
(40, 18, 'user_created', 'Created new user: jacobquarshie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 01:17:34'),
(41, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 01:17:38'),
(42, 19, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 01:17:54'),
(43, 19, 'password_changed', 'User changed their password on first login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 01:19:17'),
(44, 19, 'used_template', 'Used incident template: Insufficient Account Balance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 02:17:28'),
(45, 19, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 02:17:42'),
(46, 19, 'analytics_exported', 'Exported analytics report (PDF)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 02:27:13'),
(47, 19, 'analytics_exported', 'Exported analytics report (PDF)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 02:27:42'),
(48, 19, 'analytics_exported', 'Exported analytics report (PDF)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 02:28:25'),
(49, 19, 'analytics_exported', 'Exported analytics report (PDF)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 02:28:25'),
(50, 19, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 07:00:33'),
(51, 19, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 07:00:41'),
(52, 19, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:16:25'),
(53, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:16:33'),
(54, 18, 'user_updated', 'Updated user: harry_opata', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:18:22'),
(55, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:18:33'),
(56, 2, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:18:53'),
(57, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:19:35'),
(58, 18, 'user_created', 'Created new user: harry.opata', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:21:02'),
(59, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-19 08:21:09'),
(60, 20, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-02-19 08:23:50'),
(61, 20, 'password_changed', 'User changed their password on first login', '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-02-19 08:24:28'),
(62, 18, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 16:27:12'),
(63, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 16:28:27'),
(64, 18, 'user_created', 'Created new user: eric.authur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 16:32:23'),
(65, 18, 'user_created', 'Created new user: test.admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 16:34:00'),
(66, 18, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 16:34:55'),
(67, 21, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:58:14'),
(68, 21, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 23:15:32'),
(69, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 06:49:08'),
(70, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 06:49:32'),
(71, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 06:49:43'),
(72, 18, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 11:30:35'),
(73, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 11:30:46'),
(74, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 11:30:51'),
(75, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 11:30:57'),
(76, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 11:31:33'),
(77, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 11:32:12'),
(78, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 11:32:44'),
(79, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 11:35:56'),
(80, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-20 11:36:28'),
(81, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 15:37:27'),
(82, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-23 09:12:10'),
(83, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-23 11:45:01'),
(84, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-23 11:45:02'),
(85, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-26 09:11:37'),
(86, 18, 'used_template', 'Used incident template: FUNDGATE B2W Transfer Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-26 09:12:16'),
(87, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-26 09:12:43'),
(88, 18, 'login_failed', 'Login attempt failed: Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 09:06:35'),
(89, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 09:06:37'),
(90, 18, 'used_template', 'Used incident template: JUSTPAY Bill Payment Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 09:38:44'),
(91, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 09:39:11'),
(92, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:04:14'),
(93, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:04:18'),
(94, 18, 'used_template', 'Used incident template: OVA Low Float / Insufficient Balance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:05:24'),
(95, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:05:47'),
(96, 18, 'used_template', 'Used incident template: JUSTPAY Bill Payment Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:10:21'),
(97, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:10:41'),
(98, 18, 'user_created', 'Created new user: Jacob', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:15:17'),
(99, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:15:21'),
(100, 23, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:15:34'),
(101, 23, 'password_changed', 'User changed their password on first login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:16:17'),
(102, 23, 'used_template', 'Used incident template: FUNDGATE Credit Processing Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:45:03'),
(103, 23, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:45:32'),
(104, 23, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:53:06'),
(105, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 14:53:10'),
(106, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 16:06:37'),
(107, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 16:06:39'),
(108, 18, 'user_updated', 'Updated user: harry_opata', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 16:07:12'),
(109, 18, 'user_created', 'Created new user: test1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 16:07:54'),
(110, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 16:07:57'),
(111, 24, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 16:08:05'),
(112, 24, 'password_changed', 'User changed their password on first login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 16:13:59'),
(113, 24, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 16:14:09'),
(114, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 16:14:11'),
(115, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 17:06:25'),
(116, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 17:19:01'),
(117, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 23:36:57'),
(118, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 23:36:59'),
(119, 18, 'user_updated', 'Updated user: harry.opata', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-04 23:41:32'),
(120, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-05 09:19:13'),
(121, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-05 09:19:51'),
(122, 18, 'analytics_exported', 'Exported analytics report (PDF)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-05 09:24:43'),
(123, 18, 'used_template', 'Used incident template: Insufficient Account Balance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-05 09:45:11'),
(124, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-05 09:45:28'),
(125, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-16 11:52:58'),
(126, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 14:11:22'),
(127, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 14:40:24'),
(128, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 14:40:26'),
(129, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 14:42:34'),
(130, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-26 14:42:36'),
(131, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-27 22:34:05'),
(132, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-29 17:35:29'),
(133, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 06:51:19'),
(134, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-31 08:01:15'),
(135, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 22:29:06'),
(136, 18, 'used_template', 'Used incident template: JUSTPAY Payment Gateway Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 22:30:08'),
(137, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 22:32:10'),
(138, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 22:49:28'),
(139, 18, 'used_template', 'Used incident template: MoMo Credit Transaction Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-01 23:25:49'),
(140, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 19:12:32'),
(141, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (6)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 19:14:05'),
(142, 18, 'used_template', 'Used incident template: Bank-to-Wallet (B2W) Failure', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 19:29:10'),
(143, 18, 'incident_created', 'Reported multiple incidents: Multiple Services (1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 19:30:28'),
(144, 18, 'analytics_exported', 'Exported analytics report (PDF)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 19:54:23'),
(145, 18, 'analytics_exported', 'Exported analytics report (PDF)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 19:54:24'),
(146, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 21:00:16'),
(147, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 21:00:18'),
(148, 18, 'created_service', 'Created service: harry', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 21:41:07'),
(149, 18, 'deleted_service', 'Deleted service: harry (had 0 components, 0 incidents)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 21:41:45'),
(150, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 23:03:21'),
(151, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-02 23:03:23'),
(152, 18, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-03 09:38:41'),
(153, 18, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-04-03 09:38:45');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`company_id`, `company_name`, `category`, `created_at`, `updated_at`) VALUES
(1, 'Abii National', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(2, 'AirtelTigo', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(3, 'All', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(4, 'Atwima', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(5, 'BOA', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(6, 'Bestpoint', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(7, 'ECG', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(8, 'GCB', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(9, 'MTN', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(10, 'Multi Choice', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(11, 'NIB', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(12, 'NLA', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(13, 'PBL', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(14, 'SISL', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(15, 'STCCU', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(16, 'Telecel', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(17, 'VisionFund', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05'),
(18, 'eTranzact', NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05');

-- --------------------------------------------------------

--
-- Table structure for table `components`
--

CREATE TABLE `components` (
  `component_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `components`
--

INSERT INTO `components` (`component_id`, `name`, `is_active`) VALUES
(1, 'Airtime', 1),
(2, 'B2W', 1),
(3, 'Bills', 1),
(4, 'Credit', 1),
(5, 'Data Bundle', 1),
(6, 'Debit', 1),
(7, 'Reversal', 1),
(8, 'Topup', 1),
(9, 'W2B', 1);

-- --------------------------------------------------------

--
-- Table structure for table `downtime_incidents`
--

CREATE TABLE `downtime_incidents` (
  `downtime_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `actual_start_time` datetime NOT NULL,
  `actual_end_time` datetime DEFAULT NULL,
  `downtime_minutes` int(11) DEFAULT NULL,
  `is_planned` tinyint(1) DEFAULT 0,
  `downtime_category` enum('Network','Server','Maintenance','Third-party','Other') DEFAULT 'Other',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `downtime_incidents`
--

INSERT INTO `downtime_incidents` (`downtime_id`, `incident_id`, `actual_start_time`, `actual_end_time`, `downtime_minutes`, `is_planned`, `downtime_category`, `created_at`, `updated_at`) VALUES
(642, 942, '2026-03-20 19:05:00', '2026-03-21 02:05:00', 420, 0, 'Other', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(643, 943, '2026-03-19 16:15:00', '2026-03-19 20:15:00', 240, 0, 'Other', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(644, 944, '2026-03-19 09:20:00', '2026-03-19 14:50:00', 330, 0, 'Other', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(645, 945, '2026-03-17 06:55:00', '2026-03-22 17:01:00', 7806, 0, 'Server', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(646, 946, '2026-03-15 00:00:00', '2026-03-15 08:00:00', 480, 1, 'Maintenance', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(647, 947, '2026-03-14 22:00:00', '2026-03-15 10:00:00', 720, 1, 'Maintenance', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(648, 948, '2026-03-14 20:00:00', '2026-03-14 21:00:00', 60, 0, 'Server', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(649, 949, '2026-03-14 09:36:00', '2026-03-14 13:11:00', 215, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(650, 950, '2026-03-14 00:00:00', '2026-03-14 14:09:00', 849, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(651, 951, '2026-03-12 17:19:00', '2026-03-12 18:38:00', 79, 0, 'Server', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(652, 952, '2026-03-12 00:03:00', '2026-03-12 01:38:00', 95, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(653, 953, '2026-03-10 01:00:00', '2026-03-10 05:00:00', 240, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(654, 954, '2026-03-09 19:52:00', '2026-03-09 22:00:00', 128, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(655, 955, '2026-03-08 06:40:00', '2026-03-08 08:20:00', 100, 0, 'Server', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(656, 956, '2026-03-06 07:20:00', '2026-03-06 17:23:00', 603, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(657, 957, '2026-03-06 05:50:00', '2026-03-06 16:26:00', 636, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(658, 958, '2026-03-03 19:28:00', '2026-03-03 23:50:00', 262, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(659, 959, '2026-03-03 12:00:00', '2026-03-03 16:16:00', 256, 0, 'Other', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(660, 960, '2026-02-28 01:40:00', '2026-02-28 05:00:00', 200, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(661, 961, '2026-02-27 23:00:00', '2026-02-28 07:00:00', 480, 0, 'Server', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(662, 962, '2026-02-27 14:05:00', '2026-02-27 15:13:00', 68, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(663, 963, '2026-02-25 23:00:00', '2026-02-26 05:00:00', 360, 1, 'Maintenance', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(664, 964, '2026-02-25 11:54:00', '2026-02-28 17:16:00', 4642, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(665, 965, '2026-02-24 20:48:00', '2026-02-24 22:30:00', 102, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(666, 966, '2026-02-24 00:00:00', '2026-02-24 00:00:00', 0, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(667, 967, '2026-02-18 18:59:00', '2026-02-18 20:14:00', 75, 0, 'Other', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(668, 968, '2026-02-17 22:00:00', '2026-02-18 04:00:00', 360, 1, 'Maintenance', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(669, 969, '2026-02-17 11:00:00', '2026-02-17 12:30:00', 90, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(670, 970, '2026-02-17 10:53:00', '2026-02-17 11:54:00', 61, 0, 'Other', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(671, 971, '2026-02-17 07:53:00', '2026-02-17 08:15:00', 22, 0, 'Server', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(672, 972, '2026-02-16 17:30:00', '2026-02-17 07:14:00', 824, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(673, 973, '2026-02-16 02:46:00', '2026-02-16 09:00:00', 374, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(674, 974, '2026-02-16 00:58:00', '2026-02-16 01:43:00', 45, 0, 'Server', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(675, 975, '2026-02-15 22:10:00', '2026-02-15 22:45:00', 35, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(676, 976, '2026-02-15 19:37:00', '2026-02-15 20:09:00', 32, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(677, 977, '2026-02-14 22:00:00', '2026-02-15 10:00:00', 720, 1, 'Maintenance', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(678, 978, '2026-02-13 19:32:00', '2026-02-13 22:12:00', 160, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(679, 979, '2026-02-12 17:19:00', '2026-02-12 20:58:00', 219, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(680, 980, '2026-02-12 10:58:00', '2026-02-12 11:45:00', 47, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(681, 981, '2026-02-11 13:09:00', '2026-02-11 22:56:00', 587, 0, 'Server', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(682, 982, '2026-02-09 15:19:00', '2026-02-09 17:33:00', 134, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(683, 983, '2026-02-09 07:10:00', '2026-02-09 12:30:00', 320, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(684, 984, '2026-02-08 02:00:00', '2026-02-08 07:55:00', 355, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(685, 985, '2026-02-08 01:00:00', '2026-02-08 03:00:00', 120, 1, 'Maintenance', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(686, 986, '2026-02-05 11:13:00', '2026-02-05 13:17:00', 124, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(687, 987, '2026-02-04 11:30:00', '2026-02-04 14:33:00', 183, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(688, 988, '2026-02-02 23:16:00', '2026-02-03 12:14:00', 778, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(689, 989, '2026-02-02 18:40:00', '2026-02-02 21:15:00', 155, 1, 'Maintenance', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(690, 990, '2026-01-31 12:00:00', '2026-01-31 14:00:00', 120, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(691, 991, '2026-01-31 04:00:00', '2026-01-31 19:00:00', 900, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(692, 992, '2026-01-27 04:00:00', '2026-01-27 05:00:00', 60, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(693, 993, '2026-01-26 14:56:00', '2026-01-28 11:57:00', 2701, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(694, 994, '2026-01-25 17:37:00', '2026-01-26 00:00:00', 383, 0, 'Other', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(695, 995, '2026-01-24 19:43:00', '2026-01-25 01:31:00', 348, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(696, 996, '2026-01-23 11:50:00', '2026-01-23 17:45:00', 355, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(697, 997, '2026-01-22 13:39:00', '2026-01-22 13:54:00', 15, 0, 'Server', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(698, 998, '2026-01-20 21:00:00', '2026-01-20 23:25:00', 145, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(699, 999, '2026-01-19 11:00:00', '2026-01-20 05:00:00', 1080, 1, 'Maintenance', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(700, 1000, '2026-01-19 09:00:00', '2026-01-20 05:00:00', 1200, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(701, 1001, '2026-01-18 09:37:00', '2026-01-20 10:00:00', 2903, 0, 'Other', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(702, 1002, '2026-01-17 05:32:00', '2026-01-17 06:42:00', 70, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(703, 1003, '2026-01-17 00:00:00', '2026-01-17 02:00:00', 120, 1, 'Maintenance', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(704, 1004, '2026-01-13 00:44:00', '2026-01-13 03:40:00', 176, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(705, 1005, '2026-01-10 18:26:00', '2026-01-10 19:05:00', 39, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(706, 1006, '2026-01-07 01:32:00', '2026-01-07 07:19:00', 347, 0, 'Network', '2026-03-27 23:18:46', '2026-03-27 23:18:46'),
(707, 1008, '2026-03-31 22:48:00', NULL, NULL, 0, 'Network', '2026-04-01 22:49:28', '2026-04-01 22:49:28'),
(708, 1009, '2026-04-02 19:12:00', NULL, NULL, 0, 'Server', '2026-04-02 19:14:05', '2026-04-02 19:14:05'),
(709, 1010, '2026-04-02 19:12:00', NULL, NULL, 0, 'Server', '2026-04-02 19:14:05', '2026-04-02 19:14:05'),
(710, 1011, '2026-04-02 19:12:00', NULL, NULL, 0, 'Server', '2026-04-02 19:14:05', '2026-04-02 19:14:05'),
(711, 1012, '2026-04-02 19:12:00', NULL, NULL, 0, 'Server', '2026-04-02 19:14:05', '2026-04-02 19:14:05'),
(712, 1013, '2026-04-02 19:12:00', NULL, NULL, 0, 'Server', '2026-04-02 19:14:05', '2026-04-02 19:14:05'),
(713, 1014, '2026-04-02 19:12:00', NULL, NULL, 0, 'Server', '2026-04-02 19:14:05', '2026-04-02 19:14:05'),
(714, 1015, '2026-04-02 18:29:00', NULL, NULL, 0, 'Server', '2026-04-02 19:30:28', '2026-04-02 19:30:28');

--
-- Triggers `downtime_incidents`
--
DELIMITER $$
CREATE TRIGGER `calculate_downtime_minutes` BEFORE UPDATE ON `downtime_incidents` FOR EACH ROW BEGIN
        IF NEW.actual_end_time IS NOT NULL AND (OLD.actual_end_time IS NULL OR NEW.actual_end_time != OLD.actual_end_time) THEN
            SET NEW.downtime_minutes = TIMESTAMPDIFF(MINUTE, NEW.actual_start_time, NEW.actual_end_time);
        END IF;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `calculate_downtime_minutes_insert` BEFORE INSERT ON `downtime_incidents` FOR EACH ROW BEGIN
  IF NEW.actual_end_time IS NOT NULL THEN
    SET NEW.downtime_minutes = TIMESTAMPDIFF(MINUTE, NEW.actual_start_time, NEW.actual_end_time);
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `incident_id` int(11) NOT NULL,
  `incident_ref` varchar(20) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `component_id` int(11) DEFAULT NULL,
  `incident_type_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL COMMENT 'Detailed description of what happened during the incident',
  `impact_level` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `incident_source` enum('internal','external') NOT NULL DEFAULT 'external',
  `root_cause` text DEFAULT NULL,
  `root_cause_file` varchar(255) DEFAULT NULL,
  `lessons_learned` text DEFAULT NULL,
  `lessons_learned_file` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `actual_start_time` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','resolved') NOT NULL DEFAULT 'pending',
  `reported_by` int(11) NOT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolvers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resolvers`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`incident_id`, `incident_ref`, `service_id`, `component_id`, `incident_type_id`, `description`, `impact_level`, `priority`, `incident_source`, `root_cause`, `root_cause_file`, `lessons_learned`, `lessons_learned_file`, `attachment_path`, `actual_start_time`, `status`, `reported_by`, `resolved_by`, `resolved_at`, `created_at`, `updated_at`, `resolvers`) VALUES
(942, 'INC_26_030166', 141, NULL, 1311, 'Mainone Host downtime | Systems Affected: None as traffic was channelled through other ISP.', 'High', 'High', 'external', 'The link was impacted by a suspected fiber cut on our support partner network.', NULL, 'Proactive monitoring of internet traffics.\nHaving a back up ISP', NULL, NULL, '2026-03-20 19:05:00', 'resolved', 7, 7, '2026-03-21 02:05:00', '2026-03-20 19:13:00', '2026-03-21 02:05:00', NULL),
(943, 'INC_26_030165', 141, NULL, 1311, 'Mainone host downtime | Systems Affected: None as traffic was channelled through another ISP.', 'High', 'High', 'external', 'Service outage was due a configuration error on the switch.', NULL, 'Proactive monitoring of Internet speed and traffic.', NULL, NULL, '2026-03-19 16:15:00', 'resolved', 7, 7, '2026-03-19 20:15:00', '2026-03-19 16:05:00', '2026-03-19 20:15:00', NULL),
(944, 'INC_26_030164', 141, NULL, 1311, 'Mainone host was not reachable. | Systems Affected: None as traffic was channelled through another ISP.', 'High', 'High', 'external', 'This was due to a to power failure at mainone last mile segment.', NULL, 'Always have a back up ISP.\nProactive monitoring of internet speed and traffic.', NULL, NULL, '2026-03-19 09:20:00', 'resolved', 7, 7, '2026-03-19 14:50:00', '2026-03-19 09:15:00', '2026-03-19 14:50:00', NULL),
(945, 'INC_26_030167', 142, 5, 1308, 'Adsl transaction were failing because we were getting bad request response from them | Systems Affected: All transactions', 'Critical', 'Urgent', 'external', 'we were getting bad request response from them', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-03-17 06:55:00', 'resolved', 2, 2, '2026-03-22 17:01:00', '2026-03-17 06:55:00', '2026-04-02 21:55:39', NULL),
(946, 'INC_26_030163', 141, NULL, 1310, 'Transactions were failing intermittently | Systems Affected: All Transactions', 'High', 'High', 'external', 'Transactions were failing intermittently because of a planned maintenance', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-03-15 00:00:00', 'resolved', 2, 2, '2026-03-15 08:00:00', '2026-03-15 00:00:00', '2026-03-15 08:00:00', NULL),
(947, 'INC_26_030162', 141, NULL, 1310, 'MTN Transactions were failing intermittently | Systems Affected: All transactions', 'Critical', 'Urgent', 'external', 'Transactions were failing because of a planned maintenance', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-03-14 22:00:00', 'resolved', 2, 2, '2026-03-15 10:00:00', '2026-03-14 22:00:00', '2026-03-15 10:00:00', NULL),
(948, 'INC_26_030161', 141, 8, 1308, 'Transactions were failing intermittenly | Systems Affected: Debit and Top up transactions', 'Medium', 'Medium', 'external', 'Transactions experienced intermittent failures due to unplanned maintenance at that time.', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-03-14 20:00:00', 'resolved', 2, 2, '2026-03-14 21:00:00', '2026-03-14 20:00:00', '2026-04-02 21:32:02', NULL),
(949, 'INC_26_030159', 141, NULL, 1306, 'We were not able to connect to their host | Systems Affected: All Abii National transactions', 'Medium', 'Medium', 'external', 'Connectivity issue', NULL, 'We shoul monitor closely to detect such issues on time', NULL, NULL, '2026-03-14 09:36:00', 'resolved', 6, 6, '2026-03-14 13:11:00', '2026-03-14 09:37:00', '2026-03-14 13:11:00', NULL),
(950, 'INC_26_030160', 141, NULL, 1306, 'We couldn\'t connect to their host | Systems Affected: All Vision fund transactions', 'Critical', 'Urgent', 'external', 'Connectivity issue', NULL, 'We should monitor closely to detect such issues on time', NULL, NULL, '2026-03-14 00:00:00', 'resolved', 6, 6, '2026-03-14 14:09:00', '2026-03-14 00:00:00', '2026-03-14 14:09:00', NULL),
(951, 'INC_26_030158', 141, NULL, 1307, 'USSD Downtime | Systems Affected: USSD Applications', 'Medium', 'Medium', 'external', 'we experienced a technical issue affecting our 30.26 network segment, which impacted our USSD infrastructure and resulted in a temporary shutdown of all associated shortcodes. During this period, users may have experienced failed sessions or an inability to access services via USSD.', NULL, 'Continus monitoring', NULL, NULL, '2026-03-12 17:19:00', 'resolved', 25, 25, '2026-03-12 18:38:00', '2026-03-13 17:15:00', '2026-03-12 18:38:00', NULL),
(952, 'INC_26_030157', 141, 8, 1306, 'All Debit and Top up transactions were failing | Systems Affected: All Debit and Top up transactions', 'Medium', 'Medium', 'external', 'connectivity issue', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-03-12 00:03:00', 'resolved', 2, 2, '2026-03-12 01:38:00', '2026-03-12 00:03:00', '2026-04-02 21:32:02', NULL),
(953, 'INC_26_030156', 142, NULL, 1306, 'System Maintenance | Systems Affected: Mobile Money / Vasgate', 'High', 'High', 'external', 'MTN Maintenance', NULL, 'Continuous monitoring and reporting', NULL, NULL, '2026-03-10 01:00:00', 'resolved', 4, 4, '2026-03-10 05:00:00', '2026-03-10 01:00:00', '2026-04-02 21:55:39', NULL),
(954, 'INC_26_030155', 142, NULL, 1306, 'Server inaccessible | Systems Affected: Mobile Money / Vasgate', 'Medium', 'Medium', 'external', 'The team could not access BESTPOINT server', NULL, 'Continuous monitoring and reporting', NULL, NULL, '2026-03-09 19:52:00', 'resolved', 4, 4, '2026-03-09 22:00:00', '2026-03-09 19:52:00', '2026-04-02 21:55:39', NULL),
(955, 'INC_26_030153', 141, 6, 1308, 'We observed that the majority of debit transactions were in a pending state, and name verification could not be performed on Xcel. Upon investigation, we attempted to access server 30.17, but it was found to be unresponsive. With the assistance of Ezra Ohene and Takyi Owusu Mensah, we restarted the server, after which normal operations were restored and the issue was resolved | Systems Affected: Most debit transactions and Xcel name verification', 'Medium', 'Medium', 'external', '30.17 was not accessible', NULL, 'Continuous monitoring', NULL, NULL, '2026-03-08 06:40:00', 'resolved', 25, 25, '2026-03-08 08:20:00', '2026-03-08 06:43:00', '2026-04-02 21:32:02', NULL),
(956, 'INC_26_030152', 142, NULL, 1306, 'Thier transactions were failing because we could not connect to their host | Systems Affected: Mobile Money and Vasgate', 'High', 'High', 'external', 'Their CBA went off', NULL, 'Continuous monitoring and asking for update till issue is resolved', NULL, NULL, '2026-03-06 07:20:00', 'resolved', 3, 3, '2026-03-06 17:23:00', '2026-03-06 07:20:00', '2026-04-02 21:55:39', NULL),
(957, 'INC_26_030154', 141, NULL, 1306, 'Transactions were failing we could not connect to their host | Systems Affected: All transactions', 'High', 'High', 'external', 'We could not connect to their host.', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-03-06 05:50:00', 'resolved', 2, 2, '2026-03-06 16:26:00', '2026-03-06 05:50:00', '2026-03-06 16:26:00', NULL),
(958, 'INC_26_030150', 142, NULL, 1306, 'Connectivity Issue | Systems Affected: Mobile Money / Vasgate', 'High', 'High', 'external', 'We were not able to access the PBL server', NULL, 'Continuous monitoring and reporting', NULL, NULL, '2026-03-03 19:28:00', 'resolved', 4, 4, '2026-03-03 23:50:00', '2026-03-03 19:28:00', '2026-04-02 21:55:39', NULL),
(959, 'INC_26_030151', 141, NULL, 1311, 'we were not able to reach Vision fund  live endpoint | Systems Affected: momo', 'High', 'High', 'external', 'there was a configuration that was done that caused the endpoint to change from demo to live', NULL, 'we should monior transactions after a configuration change', NULL, NULL, '2026-03-03 12:00:00', 'resolved', 8, 8, '2026-03-03 16:16:00', '2026-03-04 12:00:00', '2026-03-03 16:16:00', NULL),
(960, 'INC_26_020147', 141, NULL, 1306, 'Internet connectivity | Systems Affected: All systems / applications', 'Medium', 'Medium', 'external', 'No internet traffic', NULL, 'Continuous monitoring and reporting', NULL, NULL, '2026-02-28 01:40:00', 'pending', 4, NULL, NULL, '2026-02-28 01:40:00', '2026-02-28 01:40:00', NULL),
(961, 'INC_26_020148', 142, 1, 1308, 'Telecel airtime transactions were failing | Systems Affected: Vasgate', 'High', 'High', 'external', 'All telecel airtime transactions were failing because of connectivity issue', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue.', NULL, NULL, '2026-02-27 23:00:00', 'pending', 2, NULL, NULL, '2026-02-27 23:00:00', '2026-04-02 21:55:39', NULL),
(962, 'INC_26_020146', 141, NULL, 1306, 'We were not able to establish connection with them. | Systems Affected: All AKRB transactions', 'Medium', 'Medium', 'external', 'Error response from their end', NULL, 'We should monitoer closely to detect such issues on time', NULL, NULL, '2026-02-27 14:05:00', 'resolved', 6, 6, '2026-02-27 15:13:00', '2026-02-27 14:06:00', '2026-02-27 15:13:00', NULL),
(963, 'INC_26_020145', 142, NULL, 1310, 'Scheduled Maintenance | Systems Affected: Mobile Money / Vasgate', 'High', 'High', 'external', 'Syatem Maintenance', NULL, 'Continuous monitoring and reporting', NULL, NULL, '2026-02-25 23:00:00', 'resolved', 4, 4, '2026-02-26 05:00:00', '2026-02-25 23:00:00', '2026-04-02 21:55:39', NULL),
(964, 'INC_26_030149', 142, 5, 1306, 'The SSL certificate expired causing VODA ADSL transactions to fail | Systems Affected: Telecel ADSL transactions', 'Critical', 'Urgent', 'external', 'SSL CERTIFICATE EXPIRY', NULL, 'Continuous monitoring and askiung for update till issue is resolved', NULL, NULL, '2026-02-25 11:54:00', 'resolved', 3, 3, '2026-02-28 17:16:00', '2026-02-25 11:54:00', '2026-04-02 21:55:39', NULL),
(965, 'INC_26_020144', 141, NULL, 1306, 'irtime transactions were failing due to an expired SSL certificate. | Systems Affected: All Mtn airtime transactions', 'Medium', 'Medium', 'external', 'expired SSL certificate.', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-02-24 20:48:00', 'resolved', 2, 2, '2026-02-24 22:30:00', '2026-02-24 20:48:00', '2026-02-24 22:30:00', NULL),
(966, 'INC_26_020143', 141, NULL, 1306, 'DOWNTIME | Systems Affected: MOBILE MONEY', 'Low', 'Low', 'external', 'CONNECTION ISSUE', NULL, 'CONTINUOUS MONITORING', NULL, NULL, '2026-02-24 00:00:00', 'resolved', 26, 26, '2026-02-24 00:00:00', '2026-02-24 00:00:00', '2026-02-24 00:00:00', NULL),
(967, 'INC_26_020142', 141, NULL, 1311, 'Insufficient OVA | Systems Affected: ECG Transactions', 'Medium', 'Medium', 'external', 'Insufficient OVA balance', NULL, 'Continuous monitoring and reporting', NULL, NULL, '2026-02-18 18:59:00', 'resolved', 4, 4, '2026-02-18 20:14:00', '2026-02-18 18:59:00', '2026-02-18 20:14:00', NULL),
(968, 'INC_26_020141', 141, NULL, 1310, 'Planned Maintenance by MTN | Systems Affected: All MTN MoMo transactions', 'High', 'High', 'external', 'Planned Maintenance', NULL, 'Always just the schedule time time to confirm if maintenance has started and do same for the end time', NULL, NULL, '2026-02-17 22:00:00', 'resolved', 5, 5, '2026-02-18 04:00:00', '2026-02-17 22:00:00', '2026-02-18 04:00:00', NULL),
(969, 'INC_26_020140', 141, 4, 1306, 'Transactions were failing | Systems Affected: All credit transactions', 'Medium', 'Medium', 'external', 'Transactions were failing', NULL, 'www', NULL, NULL, '2026-02-17 11:00:00', 'pending', 2, NULL, NULL, '2026-02-17 11:00:00', '2026-04-02 21:32:02', NULL),
(970, 'INC_26_020139', 141, NULL, 1311, 'Transactions were failing | Systems Affected: ALL BESTPOINT TRANSACTIONS', 'Medium', 'Medium', 'external', 'We couldnt access their server', NULL, 'Improve on proactive monitoring', NULL, NULL, '2026-02-17 10:53:00', 'resolved', 19, 19, '2026-02-17 11:54:00', '2026-02-17 10:54:00', '2026-02-17 11:54:00', NULL),
(971, 'INC_26_020138', 141, NULL, 1307, 'Bestpoint transactions were not going through. | Systems Affected: All Bestpoint Transactions', 'Low', 'Low', 'external', 'We were not able to access their server', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue.', NULL, NULL, '2026-02-17 07:53:00', 'resolved', 19, 19, '2026-02-17 08:15:00', '2026-02-17 07:53:00', '2026-02-17 08:15:00', NULL),
(972, 'INC_26_020137', 142, NULL, 1306, 'Connectivity error | Systems Affected: Mobile Money / Vasgate', 'Critical', 'Urgent', 'external', 'The application could not connect to the database', NULL, 'Continuous monitoring and reporting', NULL, NULL, '2026-02-16 17:30:00', 'resolved', 4, 4, '2026-02-17 07:14:00', '2026-02-16 17:30:00', '2026-04-02 21:55:39', NULL),
(973, 'INC_26_020136', 141, NULL, 1306, 'Transactions were failing because we could not connect to their host | Systems Affected: All transactions', 'High', 'High', 'external', 'we could not connect to their host', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-02-16 02:46:00', 'pending', 2, NULL, NULL, '2026-02-16 02:46:00', '2026-02-16 02:46:00', NULL),
(974, 'INC_26_020133', 141, NULL, 1308, 'Transactions were failing because we getting response 01 from their cba | Systems Affected: All Transactions', 'Low', 'Low', 'external', 'We were getting response code 01 from their cba', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-02-16 00:58:00', 'resolved', 2, 2, '2026-02-16 01:43:00', '2026-02-16 01:58:00', '2026-02-16 01:43:00', NULL),
(975, 'INC_26_020134', 141, 4, 1306, 'All Telecel Transactions were failing | Systems Affected: Credit Transactions', 'Low', 'Low', 'external', 'All Telecel Transactions were failing', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-02-15 22:10:00', 'resolved', 2, 2, '2026-02-15 22:45:00', '2026-02-15 22:10:00', '2026-04-02 21:32:02', NULL),
(976, 'INC_26_020135', 142, 1, 1306, 'Telecel airtime transactions were failing | Systems Affected: Vasgate', 'Low', 'Low', 'external', 'All telecel airtime transactions were failing because of connectivity issue', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-02-15 19:37:00', 'resolved', 2, 2, '2026-02-15 20:09:00', '2026-02-15 19:37:00', '2026-04-02 21:55:39', NULL),
(977, 'INC_26_020132', 141, NULL, 1310, 'All MTN transactions were failing because of the maintenance | Systems Affected: Mobile Money', 'Critical', 'Urgent', 'external', 'System maintenance', NULL, 'Continuous monitoring and asking for updates till issue is resolved', NULL, NULL, '2026-02-14 22:00:00', 'resolved', 3, 3, '2026-02-15 10:00:00', '2026-02-14 22:00:00', '2026-02-15 10:00:00', NULL),
(978, 'INC_26_020131', 142, NULL, 1306, 'Server was down | Systems Affected: Mobile Money / Vasgate', 'Medium', 'Medium', 'external', 'Both SISL and PBL transactions were not coming through because the server 172.16.30.27 was down', NULL, 'Continous monitoring and reporting', NULL, NULL, '2026-02-13 19:32:00', 'resolved', 4, 4, '2026-02-13 22:12:00', '2026-02-13 19:32:00', '2026-04-02 21:55:39', NULL),
(979, 'INC_26_020130', 141, NULL, 1306, 'Transaction were not coming in because we could not connect to their server. | Systems Affected: All Transactions', 'Medium', 'Medium', 'external', 'we could not connect to their server.', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-02-12 17:19:00', 'resolved', 2, 2, '2026-02-12 20:58:00', '2026-02-12 17:19:00', '2026-02-12 20:58:00', NULL),
(980, 'INC_26_020129', 142, NULL, 1306, 'Error from PBL host | Systems Affected: Mobile Money / Vasgate', 'Low', 'Low', 'external', 'Difficulty connecting to PBL host', NULL, 'Continuous monitoring and reporting', NULL, NULL, '2026-02-12 10:58:00', 'resolved', 4, 4, '2026-02-12 11:45:00', '2026-02-12 10:58:00', '2026-04-02 21:55:39', NULL),
(981, 'INC_26_020128', 141, NULL, 1308, 'PBL\'s server and host were down which was causing transactions to fail | Systems Affected: All PBL transactions', 'High', 'High', 'external', 'Their host was down', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue.', NULL, NULL, '2026-02-11 13:09:00', 'resolved', 5, 5, '2026-02-11 22:56:00', '2026-02-11 13:07:00', '2026-02-11 22:56:00', NULL),
(982, 'INC_26_020127', 142, NULL, 1306, 'Inability to connect to NIB sever | Systems Affected: Mobile Money / Vasgate', 'Medium', 'Medium', 'external', 'NIB server was not accessible', NULL, 'Continuous monitoring and reporting', NULL, NULL, '2026-02-09 15:19:00', 'resolved', 4, 4, '2026-02-09 17:33:00', '2026-02-09 15:19:00', '2026-04-02 21:55:39', NULL),
(983, 'INC_26_020126', 142, NULL, 1306, 'Transactions wre not coming in due to binding issue | Systems Affected: Mobile Money / Vasgate', 'High', 'High', 'external', 'Their CMS went off and it was not able to communicate with the TMC on central (172.16.30.6).', NULL, 'Continues monitoring and reporting.', NULL, NULL, '2026-02-09 07:10:00', 'resolved', 4, 4, '2026-02-09 12:30:00', '2026-02-09 07:10:00', '2026-04-02 21:55:39', NULL),
(984, 'INC_26_020125', 142, NULL, 1306, 'NIB server was not accessible resulting in transactions not coming in. | Systems Affected: Mobile Money / Vasgate', 'High', 'High', 'external', 'NIB server was not accessible resulting in transactions not coming in.', NULL, 'Continues monitoring and seeking rapid updates until issue is resolved.', NULL, NULL, '2026-02-08 02:00:00', 'resolved', 4, 4, '2026-02-08 07:55:00', '2026-02-08 02:00:00', '2026-04-02 21:55:39', NULL),
(985, 'INC_26_020124', 141, NULL, 1310, 'All services were down because of the maintenance by the network team | Systems Affected: All Systems', 'Medium', 'Medium', 'external', 'Network team had maintenance to perform causing all transactions to fail', NULL, 'Continuous monitoring and asking for updates till issue is resolved', NULL, NULL, '2026-02-08 01:00:00', 'resolved', 3, 3, '2026-02-08 03:00:00', '2026-02-08 01:00:00', '2026-02-08 03:00:00', NULL),
(986, 'INC_26_020123', 141, NULL, 1306, 'Transactions were failing because we could not connect to their host | Systems Affected: All transaction.', 'Medium', 'Medium', 'external', 'We could not connect to their host.', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-02-05 11:13:00', 'resolved', 2, 2, '2026-02-05 13:17:00', '2026-02-05 11:18:00', '2026-02-05 13:17:00', NULL),
(987, 'INC_26_020122', 141, NULL, 1306, 'A limit was made on the NIB mobile transactions which was causing transactions to fail | Systems Affected: NIB mobile App transactions', 'Medium', 'Medium', 'external', 'Limit made on the NIB mobile transaction', NULL, 'Continuously verify service availability and coordinate with all relevant personnel to support the investigation and resolution of the issue.', NULL, NULL, '2026-02-04 11:30:00', 'resolved', 5, 5, '2026-02-04 14:33:00', '2026-02-04 11:30:00', '2026-02-04 14:33:00', NULL),
(988, 'INC_26_020121', 142, NULL, 1306, 'We lost connectivity to their service due to binding issues | Systems Affected: Mobile Money and Vasgate', 'Critical', 'Urgent', 'external', 'Binding issue', NULL, 'Continuous monitoring and asking for update till issue is resolved', NULL, NULL, '2026-02-02 23:16:00', 'resolved', 3, 3, '2026-02-03 12:14:00', '2026-02-02 23:16:00', '2026-04-02 21:55:39', NULL),
(989, 'INC_26_020120', 141, NULL, 1310, 'GHIPSS was down causing transactions to fail, so we switched to OVA for transactions to start processing fine | Systems Affected: All transactions passing GHIPSS', 'Medium', 'Medium', 'external', 'GHIPSS was down causing transactions to fail', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue.', NULL, NULL, '2026-02-02 18:40:00', 'resolved', 5, 5, '2026-02-02 21:15:00', '2026-02-03 13:20:00', '2026-02-02 21:15:00', NULL),
(990, 'INC_26_010118', 141, NULL, 1306, 'We couldn\'t connect to our ISP | Systems Affected: All transactions', 'Medium', 'Medium', 'external', 'Internet issue', NULL, 'We should monitor closely to detect such issues on time', NULL, NULL, '2026-01-31 12:00:00', 'resolved', 6, 6, '2026-01-31 14:00:00', '2026-01-31 12:00:00', '2026-01-31 14:00:00', NULL),
(991, 'INC_26_020119', 141, 8, 1306, 'Transactions were throwing 96 | Systems Affected: Credit and Top up transactions', 'Critical', 'Urgent', 'external', 'Binding', NULL, 'We should monitor closely to detect such issues on time', NULL, NULL, '2026-01-31 04:00:00', 'resolved', 2, 2, '2026-01-31 19:00:00', '2026-01-31 18:00:00', '2026-04-02 21:32:02', NULL),
(992, 'INC_26_010115', 141, NULL, 1306, 'All Transactions were failing because we could not connect to their host. | Systems Affected: All Transactions', 'Medium', 'Medium', 'external', 'We could not connect to their Host', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-01-27 04:00:00', 'resolved', 2, 2, '2026-01-27 05:00:00', '2026-01-26 04:00:00', '2026-01-27 05:00:00', NULL),
(993, 'INC_26_010117', 141, NULL, 1306, 'It was observed that transactions from Multichoice were no longer being received, while transactions from other merchants continued to process successfully through the same endpoint. This indicated that the issue was merchant-specific rather than a platform-wide outage. | Systems Affected: All Multichoice transactions', 'Critical', 'Urgent', 'external', 'Cloudflare security rules automatically blocked Multichoice IP addresses, preventing their requests from reaching the proxy server.  As a result, Cloudflare returned HTTP 403 responses to Multichoice, causing transaction failures.', NULL, 'We should monitor continuously to detect such issues on time', NULL, NULL, '2026-01-26 14:56:00', 'resolved', 6, 6, '2026-01-28 11:57:00', '2026-01-26 14:58:00', '2026-01-28 11:57:00', NULL),
(994, 'INC_26_010116', 141, 6, 1311, 'There were delays on debit transactions. | Systems Affected: DEBIT TANSACTIONS', 'High', 'High', 'external', 'There were delays on debit transactions.', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-01-25 17:37:00', 'resolved', 2, 2, '2026-01-26 00:00:00', '2026-01-25 18:50:00', '2026-04-02 21:32:02', NULL),
(995, 'INC_26_010114', 141, NULL, 1306, 'MTN Mobile money transactions were failing intermitently | Systems Affected: MTN Mobile money transactions', 'High', 'High', 'external', 'MTN were experiencing technical issues from their end causing MoMo transactions to fail intermitently', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue.', NULL, NULL, '2026-01-24 19:43:00', 'resolved', 5, 5, '2026-01-25 01:31:00', '2026-01-24 19:43:00', '2026-01-25 01:31:00', NULL),
(996, 'INC_26_010113', 141, 4, 1306, 'Credit transactions were failing intermittently | Systems Affected: All Credit transactions (NIB)', 'High', 'High', 'external', 'We could not connect to their host.', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue.', NULL, NULL, '2026-01-23 11:50:00', 'resolved', 5, 5, '2026-01-23 17:45:00', '2026-01-23 11:50:00', '2026-04-02 21:32:02', NULL),
(997, 'INC_26_010112', 141, NULL, 1308, 'Their USSD went down | Systems Affected: Mobile Money', 'Low', 'Low', 'external', 'The USSD service went down', NULL, 'Continuous monitoring and asking for updates till issue is resolved', NULL, NULL, '2026-01-22 13:39:00', 'resolved', 3, 3, '2026-01-22 13:54:00', '2026-01-22 13:39:00', '2026-01-22 13:54:00', NULL),
(998, 'INC_26_010111', 142, NULL, 1306, 'Transactions were failing because we could not connect to their host | Systems Affected: Mobile Money and Vasgate', 'Medium', 'Medium', 'external', 'Their host went down', NULL, 'Continuous monitoring and asking for updates till issue is resolved', NULL, NULL, '2026-01-20 21:00:00', 'resolved', 3, 3, '2026-01-20 23:25:00', '2026-01-20 21:00:00', '2026-04-02 21:55:39', NULL),
(999, 'INC_26_010108', 141, NULL, 1310, 'MTN Planned Maintenance | Systems Affected: All Transactions', 'Critical', 'Urgent', 'external', 'Planned Maintenance', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-01-19 11:00:00', 'resolved', 2, 2, '2026-01-20 05:00:00', '2026-01-19 11:00:00', '2026-01-20 05:00:00', NULL),
(1000, 'INC_26_010109', 141, NULL, 1306, 'PBL had a middleware API downtime | Systems Affected: All PBL transactions', 'Critical', 'Urgent', 'external', 'API downtime', NULL, 'We should monitor transactions closely to detect such issues on time', NULL, NULL, '2026-01-19 09:00:00', 'resolved', 6, 6, '2026-01-20 05:00:00', '2026-01-19 09:00:00', '2026-01-20 05:00:00', NULL),
(1001, 'INC_26_010110', 143, 6, 1311, 'All telecel debit on fundgate where failing due to wrong reference being sent to telco. We noticed that \"#0000\" was appended to all the references being sent to the telco causing failures. | Systems Affected: All Telecel debit transactions', 'Critical', 'Urgent', 'external', 'Still under investigation', NULL, 'We should monitor closely to detect such issues on time', NULL, NULL, '2026-01-18 09:37:00', 'resolved', 6, 6, '2026-01-20 10:00:00', '2026-01-18 09:40:00', '2026-04-02 21:55:39', NULL),
(1002, 'INC_26_010107', 141, NULL, 1306, 'Transactions were failing because we could not connect to their host | Systems Affected: All Transactions', 'Medium', 'Medium', 'external', 'We could not connect to their host', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-01-17 05:32:00', 'resolved', 2, 2, '2026-01-17 06:42:00', '2026-01-17 05:32:00', '2026-01-17 06:42:00', NULL),
(1003, 'INC_26_010106', 141, NULL, 1310, 'Transactions were not coming because the was a planned maintenance | Systems Affected: All transactions', 'Medium', 'Medium', 'external', 'Planned maintenance', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-01-17 00:00:00', 'resolved', 2, 2, '2026-01-17 02:00:00', '2026-01-17 00:00:00', '2026-01-17 02:00:00', NULL),
(1004, 'INC_26_010105', 142, NULL, 1306, 'We were not receiving transaction request and traffick as at 12:45 am, Fundgate could not connect, AWS my sql could not connect, Vasgate could not connect, Virtual bank could not connect. | Systems Affected: All clients and Fundgate clients', 'Medium', 'Medium', 'external', 'We were not receiving traffic as we could not connect to fundgate, AWS mysql, VASgate and Virtual Bank', NULL, 'Always monitor all services and reach out to the appropraite teams involved', NULL, NULL, '2026-01-13 00:44:00', 'resolved', 5, 5, '2026-01-13 03:40:00', '2026-01-13 01:28:00', '2026-04-02 21:55:39', NULL),
(1005, 'INC_26_010103', 141, NULL, 1306, 'Transactions were failing because we could not connect to their host. | Systems Affected: All transactions', 'Low', 'Low', 'external', 'we could not connect to their host.', NULL, 'There must be a continuous monitoring and asking of update from their team on the issue', NULL, NULL, '2026-01-10 18:26:00', 'resolved', 2, 2, '2026-01-10 19:05:00', '2026-01-10 18:26:00', '2026-01-10 19:05:00', NULL),
(1006, 'INC_26_010102', 142, NULL, 1306, 'We could not connect to their host | Systems Affected: Mobile money and Vasgate', 'High', 'High', 'external', 'We were unable to connect to their host causing their transactions to fail', NULL, 'Continuous monitoring and asking for update till issue has been resolved', NULL, NULL, '2026-01-07 01:32:00', 'resolved', 3, 3, '2026-01-07 07:19:00', '2026-01-07 01:32:00', '2026-04-02 21:55:39', NULL),
(1007, 'ETZ-IN#260401919', 141, 2, 1306, 'JUSTPAY payment processing is unavailable. Customers cannot complete payments through the JUSTPAY gateway.', 'Low', 'Medium', 'external', 'JUSTPAY gateway connectivity failure with the acquiring bank or payment processor.', NULL, '...', NULL, NULL, '2026-04-01 15:30:00', 'resolved', 18, 18, '2026-04-01 22:47:00', '2026-04-01 22:32:10', '2026-04-02 21:32:02', '[\"Mary Asante\"]'),
(1008, 'ETZ-IN#260401195', 141, 6, 1308, '', 'Low', 'Medium', 'external', '.....', NULL, ',,', NULL, NULL, '2026-03-31 22:48:00', 'resolved', 18, 18, '2026-04-01 23:02:00', '2026-04-01 22:49:28', '2026-04-02 21:32:02', '[\"Mary Asante\"]'),
(1009, 'ETZ-IN#260402641', 143, NULL, NULL, '', 'Medium', 'High', 'internal', 'daas', NULL, NULL, NULL, NULL, '2026-04-02 19:12:00', 'pending', 18, NULL, NULL, '2026-04-02 19:14:05', '2026-04-02 19:14:05', NULL),
(1010, 'ETZ-IN#260402613', 145, NULL, NULL, '', 'Medium', 'High', 'internal', 'daas', NULL, NULL, NULL, NULL, '2026-04-02 19:12:00', 'pending', 18, NULL, NULL, '2026-04-02 19:14:05', '2026-04-02 19:14:05', NULL),
(1011, 'ETZ-IN#260402381', 141, NULL, NULL, '', 'Medium', 'High', 'internal', 'daas', NULL, NULL, NULL, NULL, '2026-04-02 19:12:00', 'pending', 18, NULL, NULL, '2026-04-02 19:14:05', '2026-04-02 19:14:05', NULL),
(1012, 'ETZ-IN#260402862', 144, NULL, NULL, '', 'Medium', 'High', 'internal', 'daas', NULL, NULL, NULL, NULL, '2026-04-02 19:12:00', 'pending', 18, NULL, NULL, '2026-04-02 19:14:05', '2026-04-02 19:14:05', NULL),
(1013, 'ETZ-IN#260402707', 146, NULL, NULL, '', 'Medium', 'High', 'internal', 'daas', NULL, NULL, NULL, NULL, '2026-04-02 19:12:00', 'pending', 18, NULL, NULL, '2026-04-02 19:14:05', '2026-04-02 19:14:05', NULL),
(1014, 'ETZ-IN#260402542', 142, NULL, NULL, '', 'Medium', 'High', 'internal', 'daas', NULL, NULL, NULL, NULL, '2026-04-02 19:12:00', 'pending', 18, NULL, NULL, '2026-04-02 19:14:05', '2026-04-02 19:14:05', NULL),
(1015, 'ETZ-IN#260402009', 141, 2, NULL, 'Bank-to-Wallet transfers are failing. Customers cannot move funds from their bank accounts to their Mobile Money wallets.', 'High', 'Medium', 'internal', 'Third-party bank host is unreachable or the B2W processing service has gone down.', NULL, 'Proactive monitoring', NULL, NULL, '2026-04-02 18:29:00', 'resolved', 18, 18, '2026-04-02 19:30:00', '2026-04-02 19:30:28', '2026-04-02 21:32:02', '[\"Mary Asante\"]');

-- --------------------------------------------------------

--
-- Table structure for table `incident_affected_companies`
--

CREATE TABLE `incident_affected_companies` (
  `incident_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incident_affected_companies`
--

INSERT INTO `incident_affected_companies` (`incident_id`, `company_id`) VALUES
(942, 3),
(943, 3),
(944, 3),
(945, 16),
(946, 2),
(947, 9),
(948, 9),
(949, 1),
(950, 17),
(951, 3),
(952, 9),
(953, 9),
(954, 6),
(955, 3),
(956, 13),
(957, 13),
(958, 13),
(959, 17),
(960, 3),
(961, 16),
(962, 4),
(963, 9),
(964, 16),
(965, 9),
(966, 2),
(967, 7),
(968, 9),
(969, 16),
(970, 6),
(971, 6),
(972, 5),
(973, 13),
(974, 13),
(975, 16),
(976, 16),
(977, 9),
(978, 13),
(978, 14),
(979, 11),
(980, 13),
(981, 13),
(982, 11),
(983, 4),
(984, 11),
(985, 3),
(986, 6),
(987, 11),
(988, 4),
(989, 1),
(989, 4),
(989, 5),
(989, 6),
(989, 11),
(989, 13),
(989, 14),
(989, 17),
(990, 3),
(991, 4),
(992, 1),
(993, 10),
(994, 9),
(995, 9),
(996, 11),
(997, 13),
(997, 14),
(998, 11),
(999, 9),
(1000, 13),
(1001, 16),
(1002, 1),
(1003, 3),
(1004, 3),
(1005, 1),
(1006, 11),
(1007, 1),
(1008, 1),
(1009, 4),
(1010, 4),
(1011, 4),
(1012, 4),
(1013, 4),
(1014, 4),
(1015, 1),
(1015, 5);

-- --------------------------------------------------------

--
-- Table structure for table `incident_attachments`
--

CREATE TABLE `incident_attachments` (
  `attachment_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_company_history`
--

CREATE TABLE `incident_company_history` (
  `history_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `action` enum('added','removed') NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_company_history`
--

INSERT INTO `incident_company_history` (`history_id`, `incident_id`, `company_id`, `action`, `changed_by`, `changed_at`) VALUES
(1, 1007, 3, 'removed', 18, '2026-04-01 22:34:01'),
(2, 1007, 1, 'added', 18, '2026-04-01 22:34:01');

-- --------------------------------------------------------

--
-- Table structure for table `incident_templates`
--

CREATE TABLE `incident_templates` (
  `template_id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `component_id` int(11) DEFAULT NULL,
  `incident_type_id` int(11) DEFAULT NULL,
  `impact_level` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `description` text NOT NULL,
  `root_cause` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `usage_count` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_templates`
--

INSERT INTO `incident_templates` (`template_id`, `template_name`, `service_id`, `component_id`, `incident_type_id`, `impact_level`, `description`, `root_cause`, `is_active`, `usage_count`, `created_by`, `created_at`, `updated_at`) VALUES
(61, 'Host Connectivity Issue', NULL, NULL, NULL, 'High', 'Service is unreachable due to a connectivity failure with the host provider. Transactions are failing and customers are unable to complete operations.', 'Connectivity issue with the third-party host provider. Loss of network connectivity between our gateway and the external host.', 1, 1, 1, '2026-02-19 00:50:57', '2026-02-19 01:13:55'),
(62, 'Server Failure / System Down', NULL, NULL, NULL, 'Critical', 'The service server has gone down unexpectedly, causing a complete service outage. All transactions are affected.', 'Server failure due to hardware fault or OS-level crash. Services are completely unavailable.', 1, 0, 1, '2026-02-19 00:50:57', '2026-02-19 00:50:57'),
(63, 'Application Not Responding', NULL, NULL, NULL, 'High', 'The application or service API is not responding to requests. Customers are experiencing timeouts and failed transactions.', 'Application process has hung or crashed. The service endpoint is unresponsive and requires a restart.', 1, 3, 1, '2026-02-19 00:50:57', '2026-02-19 01:14:03'),
(64, 'Insufficient Account Balance', NULL, NULL, NULL, 'Critical', 'Service has been suspended due to insufficient float/account balance. Transaction processing has halted.', 'The float/settlement account balance fell below the required threshold. Transactions cannot be processed until the account is topped up.', 1, 3, 1, '2026-02-19 00:50:57', '2026-03-05 09:45:11'),
(65, 'Scheduled System Maintenance', NULL, NULL, NULL, 'Medium', 'Planned system maintenance is currently underway. Service will be temporarily unavailable during this window.', 'Scheduled maintenance window for system upgrades, patches, or infrastructure improvements.', 1, 0, 1, '2026-02-19 00:50:57', '2026-02-19 00:50:57'),
(66, 'MoMo Credit Transaction Failure', 141, NULL, 1306, 'High', 'Mobile Money credit transactions are failing. Customers are unable to receive funds into their Mobile Money wallets.', 'Connectivity issue or application failure on the Mobile Money credit processing pipeline. Host response timeout on credit requests.', 1, 1, 1, '2026-02-19 00:50:57', '2026-04-01 23:25:49'),
(67, 'MoMo Debit Transaction Failure', 141, NULL, 1306, 'High', 'Mobile Money debit transactions are failing. Customers are unable to send or withdraw funds from their wallets.', 'Debit processing failure due to host connectivity timeout or insufficient settlement balance.', 1, 0, 1, '2026-02-19 00:50:57', '2026-02-19 00:50:57'),
(68, 'Bank-to-Wallet (B2W) Failure', 141, 2, 1308, 'High', 'Bank-to-Wallet transfers are failing. Customers cannot move funds from their bank accounts to their Mobile Money wallets.', 'Third-party bank host is unreachable or the B2W processing service has gone down.', 1, 3, 1, '2026-02-19 00:50:57', '2026-04-02 21:32:02'),
(69, 'Wallet-to-Bank (W2B) Failure', 141, NULL, 1308, 'High', 'Wallet-to-Bank transfers are failing. Customers cannot transfer funds from their Mobile Money wallet to their linked bank accounts.', 'W2B processing service connectivity failure or bank host downtime.', 1, 0, 1, '2026-02-19 00:50:57', '2026-02-19 00:50:57'),
(70, 'Bill Payment Processing Failure', 142, NULL, 1306, 'High', 'Bill payment transactions on VASGATE are failing. Customers are unable to pay utility bills, subscriptions, or service charges.', 'VASGATE bill payment gateway is experiencing connectivity issues with the bill aggregator or biller APIs.', 1, 0, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39'),
(71, 'Airtime Top-Up Failure', 142, NULL, 1308, 'Medium', 'Airtime top-up purchases are failing on VASGATE. Customers are unable to purchase airtime for their mobile networks.', 'Airtime vendor API is unresponsive or the VASGATE airtime processing service is down.', 1, 0, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39'),
(72, 'Data Bundle Purchase Failure', 142, NULL, 1308, 'Medium', 'Data bundle purchases are failing on VASGATE. Customers cannot purchase mobile data bundles.', 'Data bundle vendor API connectivity issue or inventory stock depletion on the aggregator side.', 1, 0, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39'),
(73, 'FUNDGATE Credit Processing Failure', 143, NULL, 1306, 'High', 'FUNDGATE credit transactions are failing. Fund credits into accounts are not being processed.', 'FUNDGATE credit processing engine connectivity failure or timeout with the receiving bank host.', 1, 1, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39'),
(74, 'FUNDGATE B2W Transfer Failure', 143, NULL, 1308, 'High', 'Bank-to-Wallet fund transfers via FUNDGATE are failing. Customers cannot move bank funds to wallets.', 'FUNDGATE B2W pipeline is experiencing host connectivity issues or the processing service is down.', 1, 2, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39'),
(75, 'MPAY Transaction Processing Failure', 144, NULL, 1306, 'High', 'MPAY payment transactions are failing. Customers are unable to complete mobile payments through the MPAY platform.', 'MPAY transaction processing service is down or the payment gateway is experiencing connectivity issues.', 1, 0, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39'),
(76, 'MPAY Server Downtime', 144, NULL, 1307, 'Critical', 'The MPAY server has gone down completely. All MPAY payment processing is unavailable.', 'MPAY server failure due to infrastructure issue or application crash. Full service restoration required.', 1, 0, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39'),
(77, 'JUSTPAY Payment Gateway Failure', 145, NULL, 1306, 'High', 'JUSTPAY payment processing is unavailable. Customers cannot complete payments through the JUSTPAY gateway.', 'JUSTPAY gateway connectivity failure with the acquiring bank or payment processor.', 1, 1, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39'),
(78, 'JUSTPAY Bill Payment Failure', 145, NULL, 1308, 'High', 'Bill payments via JUSTPAY are failing. Customers cannot pay bills using the JUSTPAY platform.', 'Biller API connectivity issue on the JUSTPAY bill payment processing pipeline.', 1, 2, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39'),
(79, 'OVA Wallet Credit Failure', 146, NULL, 1306, 'High', 'OVA wallet credit transactions are failing. Customers cannot receive funds into their OVA wallets.', 'OVA wallet credit processing failure due to host connectivity issue or service downtime.', 1, 0, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39'),
(80, 'OVA Low Float / Insufficient Balance', 146, NULL, 1309, 'Critical', 'OVA service has been halted due to insufficient float balance. Wallet transactions cannot be processed.', 'The OVA settlement account/float balance is insufficient to process transactions. Account top-up required immediately.', 1, 1, 1, '2026-02-19 00:50:57', '2026-04-02 21:55:39');

-- --------------------------------------------------------

--
-- Table structure for table `incident_types`
--

CREATE TABLE `incident_types` (
  `type_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incident_types`
--

INSERT INTO `incident_types` (`type_id`, `service_id`, `name`, `is_active`) VALUES
(1306, NULL, 'Connectivity Issue', 1),
(1307, NULL, 'Server Failure / Shut Down', 1),
(1308, NULL, 'Service / Application not responding', 1),
(1309, NULL, 'Insufficient Account Balance', 1),
(1310, NULL, 'System Maintenance', 1),
(1311, NULL, 'Others', 1);

-- --------------------------------------------------------

--
-- Table structure for table `incident_type_service_map`
--

CREATE TABLE `incident_type_service_map` (
  `service_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incident_type_service_map`
--

INSERT INTO `incident_type_service_map` (`service_id`, `type_id`) VALUES
(0, 1306),
(0, 1307),
(0, 1308),
(0, 1310),
(0, 1311),
(141, 1306),
(141, 1307),
(141, 1308),
(141, 1309),
(141, 1310),
(141, 1311),
(142, 1306),
(142, 1307),
(142, 1308),
(142, 1309),
(142, 1310),
(142, 1311),
(143, 1306),
(143, 1307),
(143, 1308),
(143, 1309),
(143, 1310),
(143, 1311),
(144, 1306),
(144, 1307),
(144, 1308),
(144, 1309),
(144, 1310),
(144, 1311),
(145, 1306),
(145, 1307),
(145, 1308),
(145, 1309),
(145, 1310),
(145, 1311),
(146, 1306),
(146, 1307),
(146, 1308),
(146, 1309),
(146, 1310),
(146, 1311);

-- --------------------------------------------------------

--
-- Table structure for table `incident_updates`
--

CREATE TABLE `incident_updates` (
  `update_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `update_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incident_updates`
--

INSERT INTO `incident_updates` (`update_id`, `incident_id`, `user_id`, `user_name`, `update_text`, `created_at`) VALUES
(1171, 942, 7, 'Takyi Owusu Mensah', 'An email was sent to the ISP.', '2026-03-27 23:18:46'),
(1172, 942, 7, 'Takyi Owusu Mensah', 'An incident form was completed', '2026-03-27 23:18:46'),
(1173, 943, 7, 'Takyi Owusu Mensah', 'Traffic was switched temporary to the other ISP until mainone was stable.', '2026-03-27 23:18:46'),
(1174, 943, 7, 'Takyi Owusu Mensah', 'An incident report was completed.', '2026-03-27 23:18:46'),
(1175, 944, 7, 'Takyi Owusu Mensah', 'An email was sent to mainone informing them of the downtime.', '2026-03-27 23:18:46'),
(1176, 944, 7, 'Takyi Owusu Mensah', 'An incident form was completed.', '2026-03-27 23:18:46'),
(1177, 944, 7, 'Takyi Owusu Mensah', 'A ticket was create on the mainone customer portal', '2026-03-27 23:18:46'),
(1178, 945, 2, 'Harry Opata', 'error was shared with the adsl team via mail', '2026-03-27 23:18:46'),
(1179, 945, 2, 'Harry Opata', 'The issue was resolved by the Adsl team', '2026-03-27 23:18:46'),
(1180, 946, 2, 'Harry Opata', 'Mails were sent to clients', '2026-03-27 23:18:46'),
(1181, 946, 2, 'Harry Opata', 'Issue was resolved by the AT team', '2026-03-27 23:18:46'),
(1182, 947, 2, 'Harry Opata', 'Mails were sent to clients', '2026-03-27 23:18:46'),
(1183, 947, 2, 'Harry Opata', 'issue was resolved by mtn', '2026-03-27 23:18:46'),
(1184, 948, 2, 'Harry Opata', 'Issue was resolved by Team MTN', '2026-03-27 23:18:46'),
(1185, 949, 6, 'Mary Asante', 'The issue was escalated to the Abii National Team and was resolved at their end', '2026-03-27 23:18:46'),
(1186, 950, 6, 'Mary Asante', 'We escalated the issue to the Vision fund team', '2026-03-27 23:18:46'),
(1187, 951, 25, 'Anku Bright', 'The issue was escalated to the DevOps team. And It was solved by them.', '2026-03-27 23:18:46'),
(1188, 952, 2, 'Harry Opata', 'Mails were sent to clients', '2026-03-27 23:18:46'),
(1189, 952, 2, 'Harry Opata', 'issue was resolved from our end', '2026-03-27 23:18:46'),
(1190, 953, 4, 'Eric Fillipe Arthur', 'Clients were informed about the maintenance schedule', '2026-03-27 23:18:46'),
(1191, 953, 4, 'Eric Fillipe Arthur', 'Clients were informed when the maintenance was over', '2026-03-27 23:18:46'),
(1192, 954, 4, 'Eric Fillipe Arthur', 'Issue was escalated to BESTPOINT team', '2026-03-27 23:18:46'),
(1193, 954, 4, 'Eric Fillipe Arthur', 'BESTPOINT team resolved the issue', '2026-03-27 23:18:46'),
(1194, 955, 25, 'Anku Bright', 'The 30.17 server was restarted', '2026-03-27 23:18:46'),
(1195, 956, 3, 'Maxwell Eshun', 'Issue was escalated to PBL team', '2026-03-27 23:18:46'),
(1196, 956, 3, 'Maxwell Eshun', 'PBL tech team resolved the issue from their end', '2026-03-27 23:18:46'),
(1197, 957, 2, 'Harry Opata', 'The Issue was escalated to the PBL team.', '2026-03-27 23:18:46'),
(1198, 957, 2, 'Harry Opata', 'The issue was resolved by the Pbl team', '2026-03-27 23:18:46'),
(1199, 958, 4, 'Eric Fillipe Arthur', 'Issue was escalated to the PBL team', '2026-03-27 23:18:46'),
(1200, 958, 4, 'Eric Fillipe Arthur', 'The PBL team resolved the issue from their end', '2026-03-27 23:18:46'),
(1201, 959, 8, 'Simon Owusu Ansah', 'The issue was escalated to Devops', '2026-03-27 23:18:46'),
(1202, 960, 4, 'Eric Fillipe Arthur', 'Issue was escalated to the network team', '2026-03-27 23:18:47'),
(1203, 961, 2, 'Harry Opata', 'A message was sent to Rancard via teams', '2026-03-27 23:18:47'),
(1204, 961, 2, 'Harry Opata', 'The issue was resolved from their end.', '2026-03-27 23:18:47'),
(1205, 962, 6, 'Mary Asante', 'The issue was esclated to the DevOps team', '2026-03-27 23:18:47'),
(1206, 962, 6, 'Mary Asante', 'AKRB team was engaged and the issue was resolved at their end', '2026-03-27 23:18:47'),
(1207, 963, 4, 'Eric Fillipe Arthur', 'Clients were informed about the maintenance activity', '2026-03-27 23:18:47'),
(1208, 963, 4, 'Eric Fillipe Arthur', 'Clients were informed when the maintenance activity was over', '2026-03-27 23:18:47'),
(1209, 964, 3, 'Maxwell Eshun', 'Isssue was escalated to the DEVOPS team', '2026-03-27 23:18:47'),
(1210, 964, 3, 'Maxwell Eshun', 'DEVOPS team downloaded the latest certificate and replaced it with the old', '2026-03-27 23:18:47'),
(1211, 965, 2, 'Harry Opata', 'We checked the logs to know the root cause', '2026-03-27 23:18:47'),
(1212, 965, 2, 'Harry Opata', 'The issue was resolved at our end', '2026-03-27 23:18:47'),
(1213, 966, 26, 'Israel Opata', 'ISSUE WAS ESCALATED', '2026-03-27 23:18:47'),
(1214, 967, 4, 'Eric Fillipe Arthur', 'Issue was escalated to the finance team', '2026-03-27 23:18:47'),
(1215, 967, 4, 'Eric Fillipe Arthur', 'The finance team resolved the issue', '2026-03-27 23:18:47'),
(1216, 968, 5, 'Fredrick Hanson', 'All clients and Banks were inform via whatsApp and Mail', '2026-03-27 23:18:47'),
(1217, 969, 2, 'Harry Opata', 'www', '2026-03-27 23:18:47'),
(1218, 970, 19, 'Jacob Quarshie Nii Odoi', 'Reached out to the PBL team and waited for further updates', '2026-03-27 23:18:47'),
(1219, 971, 19, 'Jacob Quarshie Nii Odoi', 'Bestpoint Team notified and restored connection', '2026-03-27 23:18:47'),
(1220, 972, 4, 'Eric Fillipe Arthur', 'Issue was escalated to BOA team to restart the server from their end', '2026-03-27 23:18:47'),
(1221, 972, 4, 'Eric Fillipe Arthur', 'BOA team restarted the server and resolved the issue', '2026-03-27 23:18:47'),
(1222, 973, 2, 'Harry Opata', 'Issue was esclated to the pbl team via mail', '2026-03-27 23:18:47'),
(1223, 974, 2, 'Harry Opata', 'A call was made to a member of the pbl team', '2026-03-27 23:18:47'),
(1224, 974, 2, 'Harry Opata', 'A message containg the error was sent to their Teams page', '2026-03-27 23:18:47'),
(1225, 975, 2, 'Harry Opata', 'A mail was sent to all clients', '2026-03-27 23:18:47'),
(1226, 975, 2, 'Harry Opata', 'Telecel restored service from their end', '2026-03-27 23:18:47'),
(1227, 976, 2, 'Harry Opata', 'A message was sent to Rancard', '2026-03-27 23:18:47'),
(1228, 976, 2, 'Harry Opata', 'Rancard resolved the issue from their end', '2026-03-27 23:18:47'),
(1229, 977, 3, 'Maxwell Eshun', 'Connection was restored by MTN tech team', '2026-03-27 23:18:47'),
(1230, 978, 4, 'Eric Fillipe Arthur', 'Banks involved were notified', '2026-03-27 23:18:47'),
(1231, 978, 4, 'Eric Fillipe Arthur', 'The server was restarted to resolve the issue', '2026-03-27 23:18:47'),
(1232, 979, 2, 'Harry Opata', 'A mail was sent to the NIB team', '2026-03-27 23:18:47'),
(1233, 980, 4, 'Eric Fillipe Arthur', 'Issue was escalated to PBL team', '2026-03-27 23:18:47'),
(1234, 980, 4, 'Eric Fillipe Arthur', 'PBL team resolved the issue', '2026-03-27 23:18:47'),
(1235, 981, 5, 'Fredrick Hanson', 'PBL tech team was contacted via Teams & mail', '2026-03-27 23:18:47'),
(1236, 982, 4, 'Eric Fillipe Arthur', 'Issue was escalated to the NIB team', '2026-03-27 23:18:47'),
(1237, 982, 4, 'Eric Fillipe Arthur', 'The NIB team resolved the issue', '2026-03-27 23:18:47'),
(1238, 983, 4, 'Eric Fillipe Arthur', 'We restarted the CBA and the CMS', '2026-03-27 23:18:47'),
(1239, 984, 4, 'Eric Fillipe Arthur', 'Issue was escalated to the NIB team', '2026-03-27 23:18:47'),
(1240, 984, 4, 'Eric Fillipe Arthur', 'Issue was resolved by the NIB team', '2026-03-27 23:18:47'),
(1241, 985, 3, 'Maxwell Eshun', 'An Email was sent to clients regarding the maintenance', '2026-03-27 23:18:47'),
(1242, 986, 2, 'Harry Opata', 'Issue was esclated to the Bestpoint team', '2026-03-27 23:18:47'),
(1243, 986, 2, 'Harry Opata', 'Issue was resolved at their end', '2026-03-27 23:18:47'),
(1244, 987, 5, 'Fredrick Hanson', 'We communicated with NIB via WhatsApp', '2026-03-27 23:18:47'),
(1245, 988, 3, 'Maxwell Eshun', 'Issue was escalated tO technical operations team', '2026-03-27 23:18:47'),
(1246, 988, 3, 'Maxwell Eshun', 'Issue was resolved by the same team', '2026-03-27 23:18:47'),
(1247, 989, 5, 'Fredrick Hanson', 'Transactions were switched to OVA and finance was informed', '2026-03-27 23:18:47'),
(1248, 990, 6, 'Mary Asante', 'The issue was escalated to Comsis', '2026-03-27 23:18:47'),
(1249, 991, 2, 'Harry Opata', 'The issue was resolved by our technical team', '2026-03-27 23:18:47'),
(1250, 992, 2, 'Harry Opata', 'The Issue was escalated to the ABII team via whatsapp.', '2026-03-27 23:18:47'),
(1251, 992, 2, 'Harry Opata', 'The issue was resolved at their end', '2026-03-27 23:18:47'),
(1252, 993, 6, 'Mary Asante', 'Cloudflare Routing / Security Rules were updated to explicitly allow Multichoice IP addresses.', '2026-03-27 23:18:47'),
(1253, 993, 6, 'Mary Asante', 'The rule change was applied successfully.', '2026-03-27 23:18:47'),
(1254, 993, 6, 'Mary Asante', 'Issue was escalated to the Multichoice team', '2026-03-27 23:18:47'),
(1255, 994, 2, 'Harry Opata', 'The Issue was escalated to the MTN team.', '2026-03-27 23:18:47'),
(1256, 994, 2, 'Harry Opata', 'The issue was resolved by the MTN team', '2026-03-27 23:18:47'),
(1257, 995, 5, 'Fredrick Hanson', 'A mail was sent to various clients', '2026-03-27 23:18:47'),
(1258, 995, 5, 'Fredrick Hanson', 'All clients were informed via whatsApp as well', '2026-03-27 23:18:47'),
(1259, 996, 5, 'Fredrick Hanson', 'We contacted the NIB team via WhatsApp', '2026-03-27 23:18:47'),
(1260, 996, 5, 'Fredrick Hanson', 'We contacted the NIB Tech team via mail', '2026-03-27 23:18:47'),
(1261, 997, 3, 'Maxwell Eshun', 'Issue was escalated to the DEVOPS team', '2026-03-27 23:18:47'),
(1262, 997, 3, 'Maxwell Eshun', 'DEVOPS team resolved the issue', '2026-03-27 23:18:47'),
(1263, 998, 3, 'Maxwell Eshun', 'Issue was ecalated to NIB team via Whatsapp and Mail', '2026-03-27 23:18:47'),
(1264, 998, 3, 'Maxwell Eshun', 'NIB team restored conncetion from their end', '2026-03-27 23:18:47'),
(1265, 999, 2, 'Harry Opata', 'A mail was sent to all client', '2026-03-27 23:18:47'),
(1266, 999, 2, 'Harry Opata', 'The issue was resolved by Team Mtn', '2026-03-27 23:18:47'),
(1267, 1000, 6, 'Mary Asante', 'We escalated the issue to the PBL team', '2026-03-27 23:18:47'),
(1268, 1000, 6, 'Mary Asante', 'We blocked all their USSD services', '2026-03-27 23:18:47'),
(1269, 1001, 6, 'Mary Asante', 'A fix was deployed to both fundgate application', '2026-03-27 23:18:47'),
(1270, 1002, 2, 'Harry Opata', 'The Issue was escalated to the ABII team.', '2026-03-27 23:18:47'),
(1271, 1002, 2, 'Harry Opata', 'The issue was resolved at their end', '2026-03-27 23:18:47'),
(1272, 1003, 2, 'Harry Opata', 'Mails were sent to client', '2026-03-27 23:18:47'),
(1273, 1003, 2, 'Harry Opata', 'Issue was resolved by etz team', '2026-03-27 23:18:47'),
(1274, 1004, 5, 'Fredrick Hanson', 'A mail was sent to all clients', '2026-03-27 23:18:47'),
(1275, 1004, 5, 'Fredrick Hanson', 'All Clients were informed on various pages', '2026-03-27 23:18:47'),
(1276, 1005, 2, 'Harry Opata', 'The Issue was escalated to the ABII team.', '2026-03-27 23:18:47'),
(1277, 1005, 2, 'Harry Opata', 'The issue was resolved by the ABII team.', '2026-03-27 23:18:47'),
(1278, 1006, 3, 'Maxwell Eshun', 'Issue was escalated to NIB team', '2026-03-27 23:18:47'),
(1279, 1006, 3, 'Maxwell Eshun', 'NIB team restored connection from their end', '2026-03-27 23:18:47'),
(1280, 1007, 18, 'System', 'Incident details updated by System Administrator', '2026-04-01 22:34:01'),
(1281, 1007, 18, 'System', 'Incident details updated by System Administrator', '2026-04-01 22:36:19'),
(1282, 1007, 18, 'System', 'Incident has been marked as resolved by System Administrator', '2026-04-01 22:37:16'),
(1283, 1007, 18, 'System', 'Incident was reopened by System Administrator', '2026-04-01 22:46:38'),
(1284, 1007, 18, 'System', 'Incident details updated by System Administrator', '2026-04-01 22:47:09'),
(1285, 1007, 18, 'System', 'Incident has been marked as resolved by System Administrator', '2026-04-01 22:47:42'),
(1286, 1008, 18, 'System', 'Incident has been marked as resolved by System Administrator', '2026-04-01 23:02:47'),
(1287, 1015, 18, 'System', 'Incident has been marked as resolved by System Administrator', '2026-04-02 19:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `created_at`, `updated_at`) VALUES
(141, 'Mobile Money', '2026-02-18 23:20:11', '2026-02-18 23:20:11'),
(142, 'VASGATE', '2026-02-18 23:54:40', '2026-02-18 23:54:47'),
(143, 'FUNDGATE', '2026-02-18 23:55:01', '2026-02-18 23:55:01'),
(144, 'MPAY', '2026-02-18 23:55:13', '2026-02-18 23:55:13'),
(145, 'JUSTPAY', '2026-02-18 23:55:57', '2026-02-18 23:55:57'),
(146, 'OVA', '2026-02-18 23:56:41', '2026-02-18 23:56:41');

-- --------------------------------------------------------

--
-- Table structure for table `service_component_map`
--

CREATE TABLE `service_component_map` (
  `service_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_component_map`
--

INSERT INTO `service_component_map` (`service_id`, `component_id`) VALUES
(141, 2),
(141, 4),
(141, 6),
(141, 7),
(141, 8),
(141, 9),
(142, 1),
(142, 2),
(142, 3),
(142, 5),
(142, 8),
(142, 9),
(143, 2),
(143, 4),
(143, 6),
(143, 7),
(143, 8),
(143, 9),
(144, 2),
(144, 4),
(144, 6),
(144, 7),
(144, 8),
(144, 9),
(145, 1),
(145, 3),
(145, 4),
(145, 6),
(145, 7),
(145, 8),
(146, 2),
(146, 4),
(146, 6),
(146, 7),
(146, 8),
(146, 9);

-- --------------------------------------------------------

--
-- Table structure for table `sla_targets`
--

CREATE TABLE `sla_targets` (
  `target_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `target_uptime` decimal(5,2) NOT NULL DEFAULT 99.99,
  `business_hours_start` time DEFAULT '09:00:00',
  `business_hours_end` time DEFAULT '17:00:00',
  `business_days` set('Mon','Tue','Wed','Thu','Fri','Sat','Sun') DEFAULT 'Mon,Tue,Wed,Thu,Fri',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sla_targets`
--

INSERT INTO `sla_targets` (`target_id`, `company_id`, `service_id`, `target_uptime`, `business_hours_start`, `business_hours_end`, `business_days`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(2, 2, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(3, 3, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(4, 4, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(5, 5, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(6, 6, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(7, 7, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(8, 8, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(9, 9, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(10, 10, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(11, 11, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(12, 12, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(13, 13, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(14, 14, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(15, 15, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(16, 16, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(17, 17, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00'),
(18, 18, NULL, 99.99, '09:00:00', '17:00:00', 'Mon,Tue,Wed,Thu,Fri', '2026-04-02 19:38:01', '2026-04-02 19:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `changed_password` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `location`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`, `changed_password`) VALUES
(2, 'harry_opata', 'harry_opata@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Harry Opata', '0256591308', '3rd floor Heritage tower', 'user', 0, NULL, '2026-02-17 08:44:05', '2026-03-04 16:07:12', 0),
(3, 'maxwell_eshun', 'maxwell_eshun@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Maxwell Eshun', '533866740', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(4, 'eric_fillipe_arthur', 'eric_fillipe_arthur@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Eric Fillipe Arthur', '201471988', '3rd floor Heritage tower', 'user', 0, NULL, '2026-02-17 08:44:05', '2026-02-19 16:32:32', 0),
(5, 'fredrick_hanson', 'fredrick_hanson@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Fredrick Hanson', '552150123', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(6, 'mary_asante', 'mary_asante@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Mary Asante', '534488985', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(7, 'takyi_owusu_mensah', 'takyi_owusu_mensah@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Takyi Owusu Mensah', '542672470', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(8, 'simon_owusu_ansah', 'simon_owusu_ansah@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Simon Owusu Ansah', '593489937', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(9, 'takyi_owusumensah_', 'takyi_owusumensah_@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Takyi Owusu-Mensah ', '542672470', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(10, 'takyi_owusumensah', 'takyi_owusumensah@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Takyi Owusu-Mensah', '542672470', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(11, 'japheth_boateng', 'japheth_boateng@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Japheth Boateng', '233244571716', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(12, 'prince_brako_ofori', 'prince_brako_ofori@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Prince Brako Ofori', '207812962', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(13, 'japthet_boateng', 'japthet_boateng@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Japthet Boateng', '244571716', '3rd floor Heritage tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(14, 'eshun_maxwell', 'eshun_maxwell@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Eshun Maxwell', '248919669', '3rd Floor Heritage Tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(15, 'philip_larm', 'philip_larm@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Philip Larm', '244418885', '3rd Floor Heritage Tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(16, 'seth_awiakye', 'seth_awiakye@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Seth Awiakye', '541568646', '3rd Floor Heritage Tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(17, 'leonard_l_ninsau', 'leonard_l_ninsau@incident-platform.local', 'e5bf4ea9d3ca8e7b01358689ef7fa132071ecb0adc8fddf8d4b268c868354707', 'Leonard L. Ninsau', '245446733', '3rd Floor Heritage Tower', 'user', 1, NULL, '2026-02-17 08:44:05', '2026-02-17 08:44:05', 0),
(18, 'admin', 'admin@etranzact.com', '$2y$10$B5h1Jr3E5LLwUDzCgRWFIuCtsvArJBGwJe.aO4lX2Voo2ezCEha8i', 'System Administrator', NULL, NULL, 'admin', 1, '2026-04-03 09:38:45', '2026-02-18 23:12:52', '2026-04-03 09:38:45', 1),
(19, 'jacobquarshie', 'jacobquarshie01@gmail.com', '$2y$10$0Xrb1zUbPKhZ/AtN8LQNmu5jSqzQTYspTql/ZsgLVEWW.7rFKIuEu', 'Jacob Quarshie Nii Odoi', NULL, NULL, 'user', 1, '2026-02-19 07:00:41', '2026-02-19 01:17:34', '2026-02-19 07:00:41', 1),
(20, 'harry.opata', 'harry.opata@etz.co', '$2y$10$0AtBnomZiiXBgvg2vkd2Eei6yZz7rj3a5VKBftGO6RT6/gXuga/OW', 'Harry Opata', '0256591308', NULL, 'user', 0, '2026-02-19 08:23:50', '2026-02-19 08:21:02', '2026-03-31 08:02:14', 1),
(21, 'eric.authur', 'eric@test.com', '$2y$10$4ZjIiTXXXDB5Ipe9hv0hCORXnW30kpnW1fpRhnmqYgaKyRSBvan5u', 'Eric Authur', NULL, NULL, 'admin', 0, '2026-02-19 22:58:14', '2026-02-19 16:32:23', '2026-03-31 08:02:18', 1),
(22, 'test.admin', 'test.admin@test', '$2y$10$8Ry4FZx3g7OTV9I/ZI.UAOxFNBDHODTNiozhusr/nlJdiiREiph5q', 'Test Admin', NULL, NULL, 'admin', 1, NULL, '2026-02-19 16:34:00', '2026-02-19 16:34:00', 1),
(23, 'Jacob', 'j@gmail.com', '$2y$10$5NjJ7ck7uMeDKP4bBK4ece0ygQcKgE8UfD1U7gUrAmt9.9Uc8hLNC', 'JNQ', NULL, NULL, 'user', 1, '2026-03-04 14:15:34', '2026-03-04 14:15:17', '2026-03-04 14:16:17', 1),
(24, 'test1', 'test@test.com', '$2y$10$hr6dFwODTe9cAEW3YZjsweYbTvZAzFyY6QEOVQK6tJ3b.q5vHqdEC', 'test1', '0205688585,024322665', NULL, 'user', 1, '2026-03-04 16:08:05', '2026-03-04 16:07:54', '2026-03-04 16:13:59', 1),
(25, 'anku_bright', 'anku.bright@etz.com', '$2b$10$placeholder', 'Anku Bright', NULL, NULL, 'user', 1, NULL, '2026-03-26 15:41:19', '2026-03-26 15:41:19', 0),
(26, 'israel_opata', 'israel.opata@etz.com', '$2b$10$placeholder', 'Israel Opata', NULL, NULL, 'user', 1, NULL, '2026-03-26 15:41:19', '2026-03-26 15:41:19', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `components`
--
ALTER TABLE `components`
  ADD PRIMARY KEY (`component_id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `downtime_incidents`
--
ALTER TABLE `downtime_incidents`
  ADD PRIMARY KEY (`downtime_id`),
  ADD KEY `incident_id` (`incident_id`),
  ADD KEY `idx_start_end` (`actual_start_time`,`actual_end_time`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`incident_id`),
  ADD UNIQUE KEY `unique_incident_ref` (`incident_ref`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `component_id` (`component_id`),
  ADD KEY `incident_type_id` (`incident_type_id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `idx_actual_start_time` (`actual_start_time`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_incident_source` (`incident_source`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_resolved_at` (`resolved_at`);

--
-- Indexes for table `incident_affected_companies`
--
ALTER TABLE `incident_affected_companies`
  ADD PRIMARY KEY (`incident_id`,`company_id`),
  ADD KEY `fk_iac_company` (`company_id`);

--
-- Indexes for table `incident_attachments`
--
ALTER TABLE `incident_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `idx_incident_id` (`incident_id`);

--
-- Indexes for table `incident_company_history`
--
ALTER TABLE `incident_company_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_incident_id` (`incident_id`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Indexes for table `incident_templates`
--
ALTER TABLE `incident_templates`
  ADD PRIMARY KEY (`template_id`),
  ADD UNIQUE KEY `template_name` (`template_name`),
  ADD KEY `component_id` (`component_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_service` (`service_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_usage` (`usage_count`),
  ADD KEY `incident_type_id` (`incident_type_id`);

--
-- Indexes for table `incident_types`
--
ALTER TABLE `incident_types`
  ADD PRIMARY KEY (`type_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `incident_type_service_map`
--
ALTER TABLE `incident_type_service_map`
  ADD PRIMARY KEY (`service_id`,`type_id`),
  ADD KEY `fk_itsm_type` (`type_id`);

--
-- Indexes for table `incident_updates`
--
ALTER TABLE `incident_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_component_map`
--
ALTER TABLE `service_component_map`
  ADD PRIMARY KEY (`service_id`,`component_id`),
  ADD KEY `fk_scm_component` (`component_id`);

--
-- Indexes for table `sla_targets`
--
ALTER TABLE `sla_targets`
  ADD PRIMARY KEY (`target_id`),
  ADD UNIQUE KEY `unique_company_service` (`company_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `components`
--
ALTER TABLE `components`
  MODIFY `component_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `downtime_incidents`
--
ALTER TABLE `downtime_incidents`
  MODIFY `downtime_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=715;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `incident_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1016;

--
-- AUTO_INCREMENT for table `incident_attachments`
--
ALTER TABLE `incident_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `incident_company_history`
--
ALTER TABLE `incident_company_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `incident_templates`
--
ALTER TABLE `incident_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `incident_types`
--
ALTER TABLE `incident_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1362;

--
-- AUTO_INCREMENT for table `incident_updates`
--
ALTER TABLE `incident_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1288;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `sla_targets`
--
ALTER TABLE `sla_targets`
  MODIFY `target_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `downtime_incidents`
--
ALTER TABLE `downtime_incidents`
  ADD CONSTRAINT `fk_downtime_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `fk_incident_component2` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incident_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_incident_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_incident_type` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types` (`type_id`) ON DELETE SET NULL;

--
-- Constraints for table `incident_affected_companies`
--
ALTER TABLE `incident_affected_companies`
  ADD CONSTRAINT `fk_iac_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_iac_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_attachments`
--
ALTER TABLE `incident_attachments`
  ADD CONSTRAINT `fk_attachment_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_company_history`
--
ALTER TABLE `incident_company_history`
  ADD CONSTRAINT `incident_company_history_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incident_company_history_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incident_company_history_ibfk_3` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_templates`
--
ALTER TABLE `incident_templates`
  ADD CONSTRAINT `fk_template_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incident_templates_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incident_templates_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `incident_templates_ibfk_4` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types` (`type_id`) ON DELETE SET NULL;

--
-- Constraints for table `incident_types`
--
ALTER TABLE `incident_types`
  ADD CONSTRAINT `fk_type_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_type_service_map`
--
ALTER TABLE `incident_type_service_map`
  ADD CONSTRAINT `fk_itsm_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_itsm_type` FOREIGN KEY (`type_id`) REFERENCES `incident_types` (`type_id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_updates`
--
ALTER TABLE `incident_updates`
  ADD CONSTRAINT `fk_updates_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `service_component_map`
--
ALTER TABLE `service_component_map`
  ADD CONSTRAINT `fk_scm_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_scm_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `sla_targets`
--
ALTER TABLE `sla_targets`
  ADD CONSTRAINT `fk_sla_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sla_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
