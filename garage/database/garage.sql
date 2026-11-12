-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 03:29 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `garage`
--

-- --------------------------------------------------------

--
-- Table structure for table `action_log`
--

CREATE TABLE `action_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `action_log`
--

INSERT INTO `action_log` (`id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 'login', '', '2026-11-12 07:55:52'),
(2, 1, 'logout', '', '2026-11-12 08:54:46'),
(3, 1, 'login', '', '2026-11-12 09:18:14'),
(4, 1, 'klant_create', 'Klant #11: TEST Klant', '2026-11-12 09:18:14'),
(5, 1, 'beurt_create', 'Beurt #12: Airco Service op 2026-11-15 (3 auto\'s)', '2026-11-12 09:18:14'),
(6, 1, 'beurt_create', 'Beurt #13: APK-Keuring op 2026-11-15 (1 auto\'s)', '2026-11-12 09:18:15'),
(7, 1, 'afspraak_create', 'Afspraak #17: AB-123-C bij beurt #12', '2026-11-12 09:18:15'),
(8, 1, 'afspraak_create', 'Afspraak #18: AB-124-C bij beurt #12', '2026-11-12 09:18:15'),
(9, 1, 'afspraak_create', 'Afspraak #19: XY-999-Z bij beurt #12', '2026-11-12 09:18:15'),
(10, 1, 'afspraak_cancel', 'Afspraak #17: AB-123-C, Airco Service op 2026-11-15', '2026-11-12 09:18:54'),
(11, 1, 'afspraak_cancel', 'Afspraak #18: AB-124-C, Airco Service op 2026-11-15', '2026-11-12 09:18:54'),
(12, 1, 'afspraak_cancel', 'Afspraak #19: XY-999-Z, Airco Service op 2026-11-15', '2026-11-12 09:18:54'),
(13, 1, 'beurt_delete', 'Beurt #12: Airco Service op 2026-11-15', '2026-11-12 09:18:55'),
(14, 1, 'beurt_delete', 'Beurt #13: APK-Keuring op 2026-11-15', '2026-11-12 09:18:55'),
(15, 1, 'logout', '', '2026-11-12 09:18:55'),
(16, 1, 'login', '', '2026-11-12 09:30:13'),
(17, 1, 'logout', '', '2026-11-12 09:52:48'),
(18, 1, 'login', '', '2026-11-12 10:22:55'),
(19, 1, 'beurt_delete', 'Beurt #14: APK-Keuring op 2026-11-11', '2026-11-12 10:22:57'),
(20, 1, 'login', '', '2026-11-12 10:35:32'),
(21, 1, 'beurt_create', 'Beurt #15: Bandenwissel op 2026-11-16 (2 auto\'s)', '2026-11-12 10:37:28'),
(22, 1, 'klant_create', 'Klant #12: Noordwijk', '2026-11-12 10:43:28'),
(23, 1, 'afspraak_create', 'Afspraak #20: PP-666-C bij beurt #15', '2026-11-12 11:04:35'),
(24, 1, 'afspraak_create', 'Afspraak #21: AP-66-7B bij beurt #15', '2026-11-12 11:05:55'),
(25, 1, 'afspraak_cancel', 'Afspraak #21: AP-66-7B, Bandenwissel op 2026-11-16', '2026-11-12 11:09:44'),
(26, 1, 'afspraak_cancel', 'Afspraak #20: PP-666-C, Bandenwissel op 2026-11-16', '2026-11-12 11:10:09'),
(27, 1, 'beurt_delete', 'Beurt #15: Bandenwissel op 2026-11-16', '2026-11-12 11:11:44'),
(28, 1, 'beurt_create', 'Beurt #16: APK-Keuring op 2026-11-17 (3 auto\'s)', '2026-11-12 11:21:52'),
(29, 1, 'beurt_create', 'Beurt #17: APK-Keuring op 2026-11-17 (1 auto\'s)', '2026-11-12 11:22:26'),
(30, 1, 'beurt_create', 'Beurt #18: Grote Beurt op 2026-11-23 (4 auto\'s)', '2026-11-12 11:22:52'),
(31, 1, 'beurt_create', 'Beurt #19: Airco Service op 2026-11-20 (2 auto\'s)', '2026-11-12 11:23:14'),
(32, 1, 'beurt_create', 'Beurt #20: Airco Service op 2026-11-20 (2 auto\'s)', '2026-11-12 11:23:33'),
(33, 1, 'klant_create', 'Klant #13: Jared', '2026-11-12 11:24:09'),
(34, 1, 'klant_create', 'Klant #14: Baarn', '2026-11-12 11:24:20'),
(35, 1, 'klant_create', 'Klant #15: Jesse', '2026-11-12 11:24:37'),
(36, 1, 'klant_create', 'Klant #16: Paul', '2026-11-12 11:24:50'),
(37, 1, 'afspraak_create', 'Afspraak #22: KP-36-45 bij beurt #16', '2026-11-12 11:25:08'),
(38, 1, 'afspraak_create', 'Afspraak #23: PM-43-90 bij beurt #20', '2026-11-12 11:26:38'),
(39, 1, 'afspraak_create', 'Afspraak #24: WE-10-07 bij beurt #18', '2026-11-12 11:26:56'),
(40, 1, 'afspraak_create', 'Afspraak #25: PM-43-90 bij beurt #18', '2026-11-12 11:27:56'),
(41, 1, 'afspraak_create', 'Afspraak #26: P4-43-90 bij beurt #19', '2026-11-12 11:28:12');

-- --------------------------------------------------------

--
-- Table structure for table `afspraken`
--

CREATE TABLE `afspraken` (
  `id` int(10) UNSIGNED NOT NULL,
  `klant_id` int(10) UNSIGNED NOT NULL,
  `beurt_id` int(10) UNSIGNED NOT NULL,
  `kenteken` varchar(8) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `afspraken`
--

INSERT INTO `afspraken` (`id`, `klant_id`, `beurt_id`, `kenteken`, `created_at`) VALUES
(22, 13, 16, 'KP-36-45', '2026-11-12 11:25:08'),
(23, 14, 20, 'PM-43-90', '2026-11-12 11:26:38'),
(24, 16, 18, 'WE-10-07', '2026-11-12 11:26:56'),
(25, 14, 18, 'PM-43-90', '2026-11-12 11:27:56'),
(26, 15, 19, 'P4-43-90', '2026-11-12 11:28:12');

-- --------------------------------------------------------

--
-- Table structure for table `beurten`
--

CREATE TABLE `beurten` (
  `id` int(10) UNSIGNED NOT NULL,
  `type_onderhoud` enum('APK-Keuring','Grote Beurt','Bandenwissel','Airco Service') NOT NULL,
  `datum` date NOT NULL,
  `tijd` time NOT NULL,
  `max_autos` tinyint(3) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `beurten`
--

INSERT INTO `beurten` (`id`, `type_onderhoud`, `datum`, `tijd`, `max_autos`, `created_at`) VALUES
(16, 'APK-Keuring', '2026-11-17', '14:10:00', 3, '2026-11-12 11:21:52'),
(17, 'APK-Keuring', '2026-11-17', '15:00:00', 1, '2026-11-12 11:22:26'),
(18, 'Grote Beurt', '2026-11-23', '10:30:00', 4, '2026-11-12 11:22:52'),
(19, 'Airco Service', '2026-11-20', '09:40:00', 2, '2026-11-12 11:23:14'),
(20, 'Airco Service', '2026-11-20', '11:50:00', 2, '2026-11-12 11:23:33');

-- --------------------------------------------------------

--
-- Table structure for table `klanten`
--

CREATE TABLE `klanten` (
  `id` int(10) UNSIGNED NOT NULL,
  `naam` varchar(100) NOT NULL,
  `telefoon` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `klanten`
--

INSERT INTO `klanten` (`id`, `naam`, `telefoon`, `email`, `created_at`) VALUES
(12, 'Noordwijk', '666-666', NULL, '2026-11-12 10:43:28'),
(13, 'Jared', '894-7822', NULL, '2026-11-12 11:24:09'),
(14, 'Baarn', '679-5649', NULL, '2026-11-12 11:24:20'),
(15, 'Jesse', '349-8762', NULL, '2026-11-12 11:24:37'),
(16, 'Paul', '234-6543', NULL, '2026-11-12 11:24:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Rico', 'admin@test.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-11-12 07:55:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `action_log`
--
ALTER TABLE `action_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Indexes for table `afspraken`
--
ALTER TABLE `afspraken`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_afspraken_beurt_kenteken` (`beurt_id`,`kenteken`),
  ADD KEY `fk_afspraken_klant` (`klant_id`);

--
-- Indexes for table `beurten`
--
ALTER TABLE `beurten`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `klanten`
--
ALTER TABLE `klanten`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `action_log`
--
ALTER TABLE `action_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `afspraken`
--
ALTER TABLE `afspraken`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `beurten`
--
ALTER TABLE `beurten`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `klanten`
--
ALTER TABLE `klanten`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `action_log`
--
ALTER TABLE `action_log`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `afspraken`
--
ALTER TABLE `afspraken`
  ADD CONSTRAINT `fk_afspraken_beurt` FOREIGN KEY (`beurt_id`) REFERENCES `beurten` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_afspraken_klant` FOREIGN KEY (`klant_id`) REFERENCES `klanten` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
