-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2020 at 04:25 AM
-- Server version: 10.1.40-MariaDB
-- PHP Version: 7.1.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `demo_webhook`
--

-- --------------------------------------------------------

--
-- Table structure for table `t_log`
--

CREATE TABLE `t_log` (
  `cloud_id` varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `original_data` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `t_log`
--

INSERT INTO `t_log` (`cloud_id`, `type`, `created_at`, `original_data`) VALUES
('XXXXXX', 'attlog', '2020-07-24 21:43:16', '{\"type\":\"attlog\",\"cloud_id\":\"XXXXXX\",\"data\":{\"pin\":\"1\",\"scan\":\"2020-07-21 10:11\",\"verify\":\"finger\",\"status_scan\":\"scan in\"}}'),
('XXXXXX', 'attlog', '2020-07-24 21:49:16', '{\"type\":\"attlog\",\"cloud_id\":\"XXXXXX\",\"data\":{\"pin\":\"1\",\"scan\":\"2020-07-21 10:11\",\"verify\":\"finger\",\"status_scan\":\"scan in\"}}'),
('XXXXXX', 'attlog', '2020-07-24 21:49:17', '{\"type\":\"attlog\",\"cloud_id\":\"XXXXXX\",\"data\":{\"pin\":\"1\",\"scan\":\"2020-07-21 10:11\",\"verify\":\"finger\",\"status_scan\":\"scan in\"}}'),
('XXXXXX', 'attlog', '2020-07-24 21:49:18', '{\"type\":\"attlog\",\"cloud_id\":\"XXXXXX\",\"data\":{\"pin\":\"1\",\"scan\":\"2020-07-21 10:11\",\"verify\":\"finger\",\"status_scan\":\"scan in\"}}');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `t_log`
--
ALTER TABLE `t_log`
  ADD PRIMARY KEY (`cloud_id`,`type`,`created_at`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
