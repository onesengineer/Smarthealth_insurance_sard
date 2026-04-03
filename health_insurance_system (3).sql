-- phpMyAdmin SQL Dump
-- version 4.8.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2025 at 11:17 AM
-- Server version: 10.1.37-MariaDB
-- PHP Version: 7.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `health_insurance_system`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_card_recharge` (IN `p_card_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_cashier_id` INT, IN `p_payment_method` ENUM('cash','mobile_money','bank_transfer'), IN `p_reference_number` VARCHAR(50), IN `p_receipt_number` VARCHAR(50))  BEGIN
    -- Declare a variable to store user type
    DECLARE user_role ENUM('admin', 'cashier', 'doctor', 'patient');

    -- Get the user type from the users table
    SELECT user_type INTO user_role
    FROM users
    WHERE user_id = p_cashier_id;

    -- Check if the user is either admin or cashier
    IF user_role IN ('admin', 'cashier') THEN
        -- Proceed with the insert if the user is allowed
        INSERT INTO card_recharges (card_id, amount, recharge_date, cashier_id, payment_method, reference_number, receipt_number, status) 
        VALUES (p_card_id, p_amount, NOW(), p_cashier_id, p_payment_method, p_reference_number, p_receipt_number, 'completed');
    ELSE
        -- Deny access if the user is not admin or cashier
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Access Denied: Only Cashier and Admin can perform this action';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_card_rechargee` (IN `p_card_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_user_id` INT, IN `p_payment_method` ENUM('cash','mobile_money','bank_transfer'), IN `p_reference_number` VARCHAR(50), IN `p_receipt_number` VARCHAR(50))  BEGIN
    -- Declare variables
    DECLARE user_role ENUM('admin', 'cashier', 'doctor', 'patient');
    DECLARE v_cashier_id INT;
    
    -- Get the user type from the users table
    SELECT user_type INTO user_role
    FROM users
    WHERE user_id = p_user_id;
    
    -- Check if the user is either admin or cashier
    IF user_role IN ('admin', 'cashier') THEN
        -- For cashiers, use their actual cashier_id
        IF user_role = 'cashier' THEN
            SELECT cashier_id INTO v_cashier_id
            FROM cashiers
            WHERE user_id = p_user_id;
        -- For admins, use a default cashier ID
        ELSE
            -- Option: Get the first available cashier ID
            SELECT cashier_id INTO v_cashier_id
            FROM cashiers
            LIMIT 1;
        END IF;
        
        -- Insert the record with the appropriate cashier_id
        INSERT INTO card_recharges (card_id, amount, recharge_date, cashier_id, payment_method, reference_number, receipt_number, status) 
        VALUES (p_card_id, p_amount, NOW(), v_cashier_id, p_payment_method, p_reference_number, p_receipt_number, 'completed');
    ELSE
        -- Deny access if the user is not admin or cashier
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Access Denied: Only Cashier and Admin can perform this action';
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `access_logs`
--

CREATE TABLE `access_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `action_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(50) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `access_logs`
--

INSERT INTO `access_logs` (`log_id`, `user_id`, `action`, `action_time`, `ip_address`, `device_info`) VALUES
(1, 1, 'User login', '2025-03-14 19:08:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(2, 1, 'User logout', '2025-03-14 20:15:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(3, 1, 'User login', '2025-03-14 20:15:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(4, 1, 'User logout', '2025-03-14 20:18:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(5, 1, 'User login', '2025-03-14 20:18:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(6, 1, 'User logout', '2025-03-14 20:22:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(7, 1, 'User login', '2025-03-14 20:22:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(8, 1, 'User login', '2025-03-15 13:18:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(9, 1, 'User logout', '2025-03-15 15:33:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(10, 1, 'User login', '2025-03-15 15:34:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(11, 1, 'User logout', '2025-03-15 15:49:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(12, 1, 'User login', '2025-03-15 15:49:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(13, 1, 'User logout', '2025-03-15 15:50:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(14, 1, 'User login', '2025-03-15 15:50:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(15, 1, 'User logout', '2025-03-15 15:51:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(16, 1, 'User login', '2025-03-15 15:51:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(17, 1, 'User logout', '2025-03-15 15:53:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(18, 1, 'User login', '2025-03-15 15:53:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(19, 1, 'User logout', '2025-03-15 16:40:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(20, 1, 'User login', '2025-03-15 16:40:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(21, 1, 'Added new doctor', '2025-03-15 17:41:01', '::1', NULL),
(22, 1, 'Added new cashier', '2025-03-15 17:41:43', '::1', NULL),
(23, 1, 'Added new cashier', '2025-03-15 17:42:09', '::1', NULL),
(24, 1, 'Added new doctor', '2025-03-15 17:42:57', '::1', NULL),
(25, 19, 'User login', '2025-03-18 15:02:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(26, 19, 'User logout', '2025-03-18 15:04:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(27, 19, 'User login', '2025-03-18 15:04:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(28, 19, 'User login', '2025-03-18 15:24:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(29, 19, 'User logout', '2025-03-18 17:37:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(30, 19, 'User login', '2025-03-18 17:37:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(31, 19, 'User logout', '2025-03-18 17:39:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(32, 19, 'User login', '2025-03-18 17:39:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(33, 19, 'User logout', '2025-03-18 17:40:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(34, 19, 'User login', '2025-03-18 17:40:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(35, 19, 'User logout', '2025-03-18 17:56:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(36, 19, 'User login', '2025-03-18 17:56:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(37, 1, 'User logout', '2025-03-19 10:13:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(38, 1, 'User login', '2025-03-19 10:13:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(39, 1, 'User logout', '2025-03-19 10:30:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(40, 1, 'User login', '2025-03-19 10:36:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(41, 1, 'Added new cashier', '2025-03-19 10:37:16', '::1', NULL),
(42, 1, 'Added new cashier', '2025-03-19 10:37:57', '::1', NULL),
(43, 1, 'Added new cashier', '2025-03-19 10:38:44', '::1', NULL),
(44, 1, 'User logout', '2025-03-19 10:38:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(45, 19, 'User login', '2025-03-19 10:39:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(46, 19, 'User logout', '2025-03-19 10:40:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(47, 1, 'User login', '2025-03-19 10:41:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(48, 1, 'User logout', '2025-03-19 10:42:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(49, 19, 'User login', '2025-03-19 10:42:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(50, 19, 'User logout', '2025-03-19 10:43:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(51, 1, 'User login', '2025-03-19 10:44:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(52, 1, 'Recharge card # with amount 6000', '2025-03-19 10:48:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(53, 1, 'Recharge card # with amount 6000', '2025-03-19 10:49:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(54, 1, 'Recharge card # with amount 6000', '2025-03-19 10:51:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(55, 1, 'Recharge card # with amount 6000', '2025-03-19 10:51:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(56, 1, 'Recharge card # with amount 6000', '2025-03-19 10:51:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(57, 1, 'User login', '2025-03-19 20:23:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(58, 1, 'User logout', '2025-03-19 20:35:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0'),
(59, 1, 'User login', '2025-03-19 20:35:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0');

-- --------------------------------------------------------

--
-- Table structure for table `administrators`
--

CREATE TABLE `administrators` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `permissions` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `purpose`, `status`, `notes`, `created_at`) VALUES
(1, 12, 3, '2025-03-21', '23:53:00', 'check', 'completed', 'ttt', '2025-03-20 21:51:57'),
(2, 12, 4, '2025-03-06', '00:14:00', 'check', 'completed', 'gg', '2025-03-20 22:12:41');

-- --------------------------------------------------------

--
-- Table structure for table `audit_trails`
--

CREATE TABLE `audit_trails` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `old_values` text,
  `new_values` text,
  `audit_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `audit_trails`
--

INSERT INTO `audit_trails` (`audit_id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `audit_time`) VALUES
(1, 1, 'create', 'appointment', 1, NULL, '{\"patient_id\":12,\"doctor_id\":3,\"appointment_date\":\"2025-03-20\",\"appointment_time\":\"23:53\",\"purpose\":\"check\",\"status\":\"completed\",\"notes\":\"ttt\"}', '2025-03-20 21:51:57'),
(2, 1, 'create', 'appointment', 2, NULL, '{\"patient_id\":12,\"doctor_id\":4,\"appointment_date\":\"2025-03-06\",\"appointment_time\":\"00:14\",\"purpose\":\"check\",\"status\":\"completed\",\"notes\":\"gg\"}', '2025-03-20 22:12:41'),
(3, 1, 'create', 'medical_services', 11, NULL, '{\"service_name\":\"6\",\"service_category\":\"consultation\",\"base_price\":55,\"description\":\"77\",\"status\":\"active\"}', '2025-03-20 23:19:57'),
(4, 1, 'update', 'appointment', 1, '{\"appointment_id\":\"1\",\"patient_id\":\"12\",\"doctor_id\":\"3\",\"appointment_date\":\"2025-03-20\",\"appointment_time\":\"23:53:00\",\"purpose\":\"check\",\"status\":\"completed\",\"notes\":\"ttt\",\"created_at\":\"2025-03-20 23:51:57\"}', '{\"patient_id\":12,\"doctor_id\":3,\"appointment_date\":\"2025-03-21\",\"appointment_time\":\"23:53:00\",\"purpose\":\"check\",\"status\":\"completed\",\"notes\":\"ttt\",\"appointment_id\":1}', '2025-03-20 23:40:32'),
(5, 1, 'create', 'smart_card', 6, NULL, '{\"rfid_number\":\"66666\",\"patient_id\":\"1\",\"issue_date\":\"2025-03-21\",\"expiry_date\":\"2026-03-21\",\"current_balance\":\"0.00\"}', '2025-03-21 09:56:50');

-- --------------------------------------------------------

--
-- Table structure for table `card_recharges`
--

CREATE TABLE `card_recharges` (
  `recharge_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `recharge_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `performed_by` int(11) NOT NULL,
  `payment_method` enum('card','cash','mobile_money','bank_transfer') NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `status` enum('completed','pending','failed','cancelled') DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `card_recharges`
--

INSERT INTO `card_recharges` (`recharge_id`, `card_id`, `amount`, `recharge_date`, `performed_by`, `payment_method`, `reference_number`, `receipt_number`, `status`) VALUES
(1, 5, '600.00', '2025-03-21 09:52:01', 1, 'cash', '5rhrr5y', 'RCH-20250321105201-640', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `cashiers`
--

CREATE TABLE `cashiers` (
  `cashier_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `counter_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cashiers`
--

INSERT INTO `cashiers` (`cashier_id`, `user_id`, `counter_number`) VALUES
(2, 1, '787879'),
(4, 21, '787879');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `description` text,
  `location` varchar(100) DEFAULT NULL,
  `head_doctor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `description`, `location`, `head_doctor_id`) VALUES
(1, 'Eyes', 'cure yes', 'kigali', 1);

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `consultation_fee` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `user_id`, `specialization`, `license_number`, `department`, `consultation_fee`) VALUES
(1, 3, 'gg', '9', 'h', '0.00'),
(3, 19, 'eyesd', '7778787', 'eyess', '78977.00'),
(4, 29, '676', '67t6', 'Eyes', '686.00'),
(6, 31, '676', '67t6j', 'Eyes', '6866.00');

-- --------------------------------------------------------

--
-- Table structure for table `medical_services`
--

CREATE TABLE `medical_services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `service_category` enum('consultation','laboratory','radiology','medication','surgery','other') NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `description` text,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `medical_services`
--

INSERT INTO `medical_services` (`service_id`, `service_name`, `service_category`, `base_price`, `description`, `status`) VALUES
(1, 'General Consultation', 'consultation', '3500.00', 'Basic medical consultation with a doctor', 'active'),
(2, 'Specialist Consultation', 'consultation', '5000.00', 'Consultation with a specialist doctor', 'active'),
(3, 'Complete Blood Count', 'laboratory', '7000.00', 'Complete blood count test', 'active'),
(4, 'Blood Glucose Test', 'laboratory', '3000.00', 'Blood glucose level test', 'active'),
(5, 'Chest X-Ray', 'radiology', '15000.00', 'Chest X-ray imaging', 'active'),
(6, 'Ultrasound', 'radiology', '20000.00', 'Ultrasound imaging', 'active'),
(7, 'Paracetamol 500mg', 'medication', '1000.00', 'Pain relief medication', 'active'),
(8, 'Amoxicillin 250mg', 'medication', '2500.00', 'Antibiotic medication', 'active'),
(9, 'Minor Surgery', 'surgery', '50000.00', 'Minor surgical procedure', 'active'),
(10, 'Wound Dressing', 'other', '2000.00', 'Wound cleaning and dressing', 'active'),
(11, '6', 'consultation', '55.00', '77', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `medication_records`
--

CREATE TABLE `medication_records` (
  `medication_id` int(11) NOT NULL,
  `treatment_id` int(11) NOT NULL,
  `medication_name` varchar(100) NOT NULL,
  `dosage` varchar(50) NOT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `instructions` text,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `dispensed` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `medication_records`
--

INSERT INTO `medication_records` (`medication_id`, `treatment_id`, `medication_name`, `dosage`, `frequency`, `duration`, `instructions`, `price`, `quantity`, `dispensed`) VALUES
(1, 1, '7', '9', '7', '7', '7', '79.00', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `money`
--

CREATE TABLE `money` (
  `money_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `money`
--

INSERT INTO `money` (`money_id`, `amount`, `created_at`) VALUES
(1, '6.00', '2025-03-20 22:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('system','payment','appointment','reminder','other') NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `date_of_birth` date NOT NULL,
  `address` text NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `fingerprint_data` longblob,
  `insurance_category` enum('basic','standard','premium') NOT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `allergies` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `user_id`, `gender`, `date_of_birth`, `address`, `photo_path`, `fingerprint_data`, `insurance_category`, `emergency_contact_name`, `emergency_contact_phone`, `blood_group`, `allergies`) VALUES
(1, 19, 'female', '2025-03-15', 'kigali', NULL, NULL, '', '', '', '', ''),
(12, 4, 'male', '2025-03-15', 'musanze', NULL, NULL, 'basic', 'UMUTONI Nadia', '0780779770', 'O+', 'GRIPW'),
(13, 21, 'female', '2025-03-05', 'FF', NULL, NULL, 'basic', 'UMUTONI Nadia', '0780779770', 'O-', '');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `report_name` varchar(100) NOT NULL,
  `report_type` enum('financial','patient','service','doctor','custom') NOT NULL,
  `report_parameters` text,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `report_name`, `report_type`, `report_parameters`, `created_by`, `created_at`) VALUES
(1, '47', '', '7', 15, '2025-03-11 22:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `smart_cards`
--

CREATE TABLE `smart_cards` (
  `card_id` int(11) NOT NULL,
  `rfid_number` varchar(50) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `current_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `card_status` enum('active','inactive','lost','expired') DEFAULT 'active',
  `pin_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `smart_cards`
--

INSERT INTO `smart_cards` (`card_id`, `rfid_number`, `patient_id`, `issue_date`, `expiry_date`, `current_balance`, `card_status`, `pin_code`) VALUES
(1, 'FR76R776', 1, '2025-03-15', '2026-03-15', '888.00', 'active', NULL),
(2, 'FG766755', 1, '2025-03-15', '2026-03-15', '888.00', 'active', NULL),
(3, '88H800J0', 1, '2025-03-15', '2026-03-15', '888.00', 'active', NULL),
(5, '6667', 1, '2025-03-01', '2025-03-05', '600.00', 'active', '1234'),
(6, '66666', 1, '2025-03-21', '2026-03-21', '0.00', 'active', '$2y$10$JiKtsJ0Blabsav9dtkBYWuJvCyI/kgz7JxwF8.yjS1o8Vqeh/TlOK');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','cashier') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_name`, `setting_value`, `description`, `updated_at`, `updated_by`) VALUES
(1, 'hospital_name', 'RSSB Muhima Hospital', 'The name of the hospital', '2025-03-14 17:47:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `transaction_type` enum('payment','recharge','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` text,
  `performed_by` int(11) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `payment_method` enum('card','cash','mobile_money','bank_transfer') NOT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `card_id`, `transaction_type`, `amount`, `transaction_date`, `description`, `performed_by`, `reference_number`, `payment_method`, `status`) VALUES
(1, 1, 'recharge', '888.00', '2025-03-15 17:07:24', 'Initial card balance', 1, NULL, 'cash', 'completed'),
(2, 2, 'recharge', '888.00', '2025-03-15 17:07:53', 'Initial card balance', 1, NULL, 'cash', 'completed'),
(3, 3, 'recharge', '888.00', '2025-03-15 17:08:35', 'Initial card balance', 1, NULL, 'cash', 'completed'),
(4, 5, 'recharge', '600.00', '2025-03-21 09:52:01', 'Card recharge', 1, 'RCH-20250321105201-640', 'cash', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_records`
--

CREATE TABLE `treatment_records` (
  `treatment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `diagnosis` text NOT NULL,
  `treatment_notes` text,
  `treatment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `follow_up_date` date DEFAULT NULL,
  `status` enum('open','closed','follow_up') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `treatment_records`
--

INSERT INTO `treatment_records` (`treatment_id`, `patient_id`, `doctor_id`, `diagnosis`, `treatment_notes`, `treatment_date`, `follow_up_date`, `status`) VALUES
(1, 13, 4, 'y', 'u', '2025-03-30 22:00:00', '2025-03-11', 'open');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_services`
--

CREATE TABLE `treatment_services` (
  `treatment_service_id` int(11) NOT NULL,
  `treatment_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `price` decimal(10,2) NOT NULL,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `user_type` enum('admin','doctor','cashier','patient') NOT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `first_name`, `last_name`, `email`, `phone_number`, `user_type`, `national_id`, `created_at`, `updated_at`, `status`) VALUES
(1, 'admin', '$2y$10$epf6.a1nASWAwawZvix70u6vOWH5QQjaRpSNpp/A1IwLaKWOPHC.C', 'admin', 'admin', 'admin@gmail.com', '0780777770', 'admin', '11997', '2025-03-14 18:55:32', '2025-03-21 09:35:03', 'active'),
(3, 'ad', '$2y$10$HCD3c05Xg1uWvykKUz0n0.IifqimF4FLQga1pqnF.mwhuM1v5g6dC', 'admin', 'admin', 'ad@gmail.com', '07807770770', 'doctor', '119977', '2025-03-14 19:07:42', '2025-03-15 15:14:00', 'active'),
(4, 'heric', '$2y$10$2rjard3xVFOSG7BDgdq/OuTmeJL0QwYE2Cf3ksKYMtMeTg4o0Mx9i', 'HARERIMANA', 'ERIC', 'eric@gmail.com', '0780777770', 'patient', '1199780050003158', '2025-03-15 16:57:13', '2025-03-19 20:39:26', 'active'),
(15, 'HARERIMANA ERIC', '$2y$10$mBB9ZYAsYnEEVUaiWc6KZO1t03UTUmSmSMelR0.PQr51yFP7MEidW', 'HARERIMANA', 'ERIC', 'hellysonmiljler.harelimana@gmail.com', '0780777770', 'patient', '1199780050003153', '2025-03-15 17:25:32', '2025-03-19 20:38:50', 'active'),
(18, 'HAGENIMANAggf', '$2y$10$ifanrdKvarDfTupSFIv4SeCQgomOtAKizxFRRHAMHiiTubG0gvYiS', 'HARERIMANA66', 'ERIC', 'hellysonmill77er.harelimana@gmail.com', '07807788770', 'cashier', '1199780050003179', '2025-03-15 17:42:09', '2025-03-15 17:42:09', 'active'),
(19, 'HAGENIMANA', '$2y$10$hzkshkXTNVIb7Kzhs4FLe.1bXN1.jLXdjG.0ORXCznPqNSyxnutLy', 'UMUTONI', 'ALINE', 'hellysonmi77ller.harelimana@gmail.com', '0780778770', 'doctor', '1199780050003159', '2025-03-15 17:42:57', '2025-03-19 20:34:01', 'active'),
(21, 'UMUTONI', '$2y$10$kvzZJZwhwPd51HnG5vhRVOmbqisjZXwtgEImVMnCzFrPF6Qf4m0la', 'UMUTONIA', 'NadiaA', 'claude@gmail.com', '0780779070', 'cashier', '1199780050003150', '2025-03-19 10:37:57', '2025-03-19 10:37:57', 'active'),
(23, 'ERIC', '$2y$10$bXRdAGWtQcsVnI/rOumopummESoCApAujPSuClULQNtcisL9hvITa', 'HARERIMANAE', 'ERIC', 'hellysonmiller.hEarelimanda@gmail.com', '0780707770', 'patient', '1199780050003133', '2025-03-19 10:41:52', '2025-03-19 10:41:52', 'active'),
(27, 'ericccc', '$2y$10$5f6vkRgZtAMqoOUgGwBgtu0x4/EWFJouwzxsIZ3zvRrMPwF9unA1m', 'HARERIMANA', 'ERICG', 'hellysonmiller.harelimanaa@gmail.com', '0780977070', 'patient', NULL, '2025-03-20 19:21:57', '2025-03-20 19:21:57', 'active'),
(29, 'ram@gmail.com', '$2y$10$.DRQaeKnNx88vRGjrxreUuKgtBDT9AN6UykobRWjJcgl5kd/siD2e', 'HARERIMANA', 'ERIC', 'hellysonmiller.harelimana@gmail.com', '0780777770', 'doctor', '11997800500083159', '2025-03-20 20:57:09', '2025-03-20 20:57:09', 'active'),
(31, 'gram@gmail.com', '$2y$10$0Znc66y/RmH8FYbD5HfHNuNakE9eXym.pjfa9FpzUH5OQbOZPR4j.', 'HARERIMANAy', 'ERIhC', 'hellysonhmiller.harelimana@gmail.com', '0780779770', 'doctor', '11997809500083159', '2025-03-20 21:37:26', '2025-03-20 21:37:26', 'inactive');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_card_transactions`
-- (See below for the actual view)
--
CREATE TABLE `view_card_transactions` (
`transaction_id` int(11)
,`rfid_number` varchar(50)
,`patient_name` varchar(101)
,`transaction_type` enum('payment','recharge','refund')
,`amount` decimal(10,2)
,`transaction_date` timestamp
,`description` text
,`performed_by_name` varchar(101)
,`performed_by_type` enum('admin','doctor','cashier','patient')
,`payment_method` enum('card','cash','mobile_money','bank_transfer')
,`status` enum('pending','completed','failed','cancelled')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_patient_treatment_history`
-- (See below for the actual view)
--
CREATE TABLE `view_patient_treatment_history` (
`treatment_id` int(11)
,`patient_name` varchar(101)
,`national_id` varchar(20)
,`doctor_name` varchar(101)
,`diagnosis` text
,`treatment_notes` text
,`treatment_date` timestamp
,`follow_up_date` date
,`status` enum('open','closed','follow_up')
,`total_service_cost` decimal(42,2)
,`total_medication_cost` decimal(42,2)
);

-- --------------------------------------------------------

--
-- Structure for view `view_card_transactions`
--
DROP TABLE IF EXISTS `view_card_transactions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_card_transactions`  AS  select `t`.`transaction_id` AS `transaction_id`,`sc`.`rfid_number` AS `rfid_number`,concat(`p_user`.`first_name`,' ',`p_user`.`last_name`) AS `patient_name`,`t`.`transaction_type` AS `transaction_type`,`t`.`amount` AS `amount`,`t`.`transaction_date` AS `transaction_date`,`t`.`description` AS `description`,concat(`u_user`.`first_name`,' ',`u_user`.`last_name`) AS `performed_by_name`,`u_user`.`user_type` AS `performed_by_type`,`t`.`payment_method` AS `payment_method`,`t`.`status` AS `status` from ((((`transactions` `t` join `smart_cards` `sc` on((`t`.`card_id` = `sc`.`card_id`))) join `patients` `p` on((`sc`.`patient_id` = `p`.`patient_id`))) join `users` `p_user` on((`p`.`user_id` = `p_user`.`user_id`))) join `users` `u_user` on((`t`.`performed_by` = `u_user`.`user_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `view_patient_treatment_history`
--
DROP TABLE IF EXISTS `view_patient_treatment_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_patient_treatment_history`  AS  select `tr`.`treatment_id` AS `treatment_id`,concat(`p_user`.`first_name`,' ',`p_user`.`last_name`) AS `patient_name`,`p_user`.`national_id` AS `national_id`,concat(`d_user`.`first_name`,' ',`d_user`.`last_name`) AS `doctor_name`,`tr`.`diagnosis` AS `diagnosis`,`tr`.`treatment_notes` AS `treatment_notes`,`tr`.`treatment_date` AS `treatment_date`,`tr`.`follow_up_date` AS `follow_up_date`,`tr`.`status` AS `status`,sum((`ts`.`price` * `ts`.`quantity`)) AS `total_service_cost`,sum((`mr`.`price` * `mr`.`quantity`)) AS `total_medication_cost` from ((((((`treatment_records` `tr` join `patients` `p` on((`tr`.`patient_id` = `p`.`patient_id`))) join `users` `p_user` on((`p`.`user_id` = `p_user`.`user_id`))) join `doctors` `d` on((`tr`.`doctor_id` = `d`.`doctor_id`))) join `users` `d_user` on((`d`.`user_id` = `d_user`.`user_id`))) left join `treatment_services` `ts` on((`tr`.`treatment_id` = `ts`.`treatment_id`))) left join `medication_records` `mr` on((`tr`.`treatment_id` = `mr`.`treatment_id`))) group by `tr`.`treatment_id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `administrators`
--
ALTER TABLE `administrators`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `audit_trails`
--
ALTER TABLE `audit_trails`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `card_recharges`
--
ALTER TABLE `card_recharges`
  ADD PRIMARY KEY (`recharge_id`),
  ADD KEY `card_id` (`card_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `cashiers`
--
ALTER TABLE `cashiers`
  ADD PRIMARY KEY (`cashier_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD KEY `head_doctor_id` (`head_doctor_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medical_services`
--
ALTER TABLE `medical_services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `medication_records`
--
ALTER TABLE `medication_records`
  ADD PRIMARY KEY (`medication_id`),
  ADD KEY `treatment_id` (`treatment_id`);

--
-- Indexes for table `money`
--
ALTER TABLE `money`
  ADD UNIQUE KEY `money_id` (`money_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `smart_cards`
--
ALTER TABLE `smart_cards`
  ADD PRIMARY KEY (`card_id`),
  ADD UNIQUE KEY `rfid_number` (`rfid_number`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `card_id` (`card_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `treatment_records`
--
ALTER TABLE `treatment_records`
  ADD PRIMARY KEY (`treatment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `treatment_services`
--
ALTER TABLE `treatment_services`
  ADD PRIMARY KEY (`treatment_service_id`),
  ADD KEY `treatment_id` (`treatment_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `national_id` (`national_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `administrators`
--
ALTER TABLE `administrators`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_trails`
--
ALTER TABLE `audit_trails`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `card_recharges`
--
ALTER TABLE `card_recharges`
  MODIFY `recharge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cashiers`
--
ALTER TABLE `cashiers`
  MODIFY `cashier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `medical_services`
--
ALTER TABLE `medical_services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `medication_records`
--
ALTER TABLE `medication_records`
  MODIFY `medication_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `money`
--
ALTER TABLE `money`
  MODIFY `money_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `smart_cards`
--
ALTER TABLE `smart_cards`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `treatment_records`
--
ALTER TABLE `treatment_records`
  MODIFY `treatment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `treatment_services`
--
ALTER TABLE `treatment_services`
  MODIFY `treatment_service_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_logs`
--
ALTER TABLE `access_logs`
  ADD CONSTRAINT `access_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `administrators`
--
ALTER TABLE `administrators`
  ADD CONSTRAINT `administrators_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`);

--
-- Constraints for table `audit_trails`
--
ALTER TABLE `audit_trails`
  ADD CONSTRAINT `audit_trails_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `card_recharges`
--
ALTER TABLE `card_recharges`
  ADD CONSTRAINT `card_recharges_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `smart_cards` (`card_id`),
  ADD CONSTRAINT `card_recharges_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cashiers`
--
ALTER TABLE `cashiers`
  ADD CONSTRAINT `cashiers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_doctor_id`) REFERENCES `doctors` (`doctor_id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `medication_records`
--
ALTER TABLE `medication_records`
  ADD CONSTRAINT `medication_records_ibfk_1` FOREIGN KEY (`treatment_id`) REFERENCES `treatment_records` (`treatment_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `smart_cards`
--
ALTER TABLE `smart_cards`
  ADD CONSTRAINT `smart_cards_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `smart_cards` (`card_id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `treatment_records`
--
ALTER TABLE `treatment_records`
  ADD CONSTRAINT `treatment_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `treatment_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`);

--
-- Constraints for table `treatment_services`
--
ALTER TABLE `treatment_services`
  ADD CONSTRAINT `treatment_services_ibfk_1` FOREIGN KEY (`treatment_id`) REFERENCES `treatment_records` (`treatment_id`),
  ADD CONSTRAINT `treatment_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `medical_services` (`service_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
