-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Dic 05, 2024 alle 09:36
-- Versione del server: 10.4.28-MariaDB
-- Versione PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spendwise`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `parent_id`, `name`, `created_at`) VALUES
(1, 3, NULL, 'Cibo', '2024-12-05 07:58:14'),
(2, 3, 1, 'Ristoranti', '2024-12-05 07:58:14'),
(3, 3, 1, 'Spesa', '2024-12-05 07:58:14'),
(4, 3, NULL, 'Trasporti', '2024-12-05 07:58:14'),
(5, 3, 4, 'Carburante', '2024-12-05 07:58:14'),
(6, 3, 4, 'Mezzi pubblici', '2024-12-05 07:58:14'),
(7, 3, NULL, 'Intrattenimento', '2024-12-05 07:58:14'),
(8, 3, 7, 'Cinema', '2024-12-05 08:04:55'),
(13, 4, NULL, 'Cibo', '2024-12-05 08:11:05'),
(14, 4, 13, 'Ristoranti', '2024-12-05 08:11:05'),
(15, 4, 13, 'Spesa', '2024-12-05 08:11:05'),
(16, 4, NULL, 'Trasporti', '2024-12-05 08:11:05'),
(17, 4, 16, 'Carburante', '2024-12-05 08:11:05'),
(18, 4, 16, 'Mezzi pubblici', '2024-12-05 08:11:05'),
(19, 4, NULL, 'Intrattenimento', '2024-12-05 08:11:05'),
(20, 4, 19, 'Cinema', '2024-12-05 08:11:05'),
(21, 4, 15, 'Carne', '2024-12-05 08:26:12');

-- --------------------------------------------------------

--
-- Struttura della tabella `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `category_id`, `amount`, `description`, `date`, `created_at`) VALUES
(1, 3, 2, 123.00, 'asd', '0205-02-02', '2024-12-05 08:03:48'),
(2, 4, 20, 20.24, 'Interstellare', '2024-12-05', '2024-12-05 08:11:55'),
(3, 4, 21, 99.00, 'wagyu', '2024-12-05', '2024-12-05 08:28:09');

-- --------------------------------------------------------

--
-- Struttura della tabella `global_categories`
--

CREATE TABLE `global_categories` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `global_categories`
--

INSERT INTO `global_categories` (`id`, `parent_id`, `name`, `created_at`) VALUES
(1, NULL, 'Cibo', '2024-12-05 07:56:51'),
(2, NULL, 'Trasporti', '2024-12-05 07:56:51'),
(3, NULL, 'Intrattenimento', '2024-12-05 07:56:51'),
(4, 1, 'Ristoranti', '2024-12-05 07:56:51'),
(5, 1, 'Spesa', '2024-12-05 07:56:51'),
(6, 2, 'Carburante', '2024-12-05 07:56:51'),
(7, 2, 'Mezzi pubblici', '2024-12-05 07:56:51'),
(8, 3, 'Cinema', '2024-12-05 08:04:32');

-- --------------------------------------------------------

--
-- Struttura della tabella `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'ludovico', 'ludovicomariani2006@gmail.com', '$2y$10$cD.Gq.NxHKc4qAinAv2UTeao./JqL1Oc2UbGLWJNtqFct/ZnAYAb2', '2024-12-05 07:28:24'),
(2, 'riccard', 'masseriniricca@mail.com', '$2y$10$e24D1atGf23p34RoGJyUVuZGtd2Onwggm619kcjobhtkvUjaZ7B3m', '2024-12-05 07:29:21'),
(3, 'simone', 'simonebassa@mail.com', '$2y$10$8XRocbHtLCRCJUBzdf0wluvJacryaCoj1EiXgMdQ95Ii/2oYjE2li', '2024-12-05 07:58:14'),
(4, 'La Iannella', 'iannelli@ans.it', '$2y$10$prHRP3OvqQThWmzcC5QxUu1QPpvf0j6BhC03bLQA7FFfGfceQQ/iy', '2024-12-05 08:11:05');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indici per le tabelle `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indici per le tabelle `global_categories`
--
ALTER TABLE `global_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indici per le tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT per la tabella `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `global_categories`
--
ALTER TABLE `global_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `categories_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `global_categories`
--
ALTER TABLE `global_categories`
  ADD CONSTRAINT `global_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `global_categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
