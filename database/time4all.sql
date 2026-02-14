-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Feb 14, 2026 alle 11:52
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `time4all`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `account`
--

CREATE TABLE `account` (
  `id` int(11) NOT NULL,
  `nome_utente` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `codice_univoco` varchar(255) NOT NULL,
  `classe` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `account`
--

INSERT INTO `account` (`id`, `nome_utente`, `password`, `codice_univoco`, `classe`) VALUES
(1, 'Admin', '$2y$12$N1MSCjGLw8DYLdv1slag5.K8tBIMdUt591eU9odiJVQ/xPHwQee3S', '1234', 'Amministratore'),
(2, 'User', '$2y$12$c4t7dtu78gMNAda/U8iqs.4qZFs48Z4LLFVzmIKsFH9V84kOO.Xy2', '123', 'Educatore'),
(3, 'Manager', '$2y$12$6KniR9zteBBhDEKrDSqoWOHx.ii6Fk0FWC61oFa6T5DVXlLtmD72e', '12', 'Contabile');

-- --------------------------------------------------------

--
-- Struttura della tabella `attivita`
--

CREATE TABLE `attivita` (
  `id` int(11) NOT NULL,
  `Nome` varchar(255) NOT NULL,
  `Descrizione` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `attivita`
--

INSERT INTO `attivita` (`id`, `Nome`, `Descrizione`) VALUES
(29, 'Maia & Tas', 'Apparecchiare e pulire'),
(30, 'Bowling', 'Attività ricreativa stimolante'),
(32, 'Rifiutando', 'Raccolta rifiuti');

-- --------------------------------------------------------

--
-- Struttura della tabella `educatore`
--

CREATE TABLE `educatore` (
  `id` int(11) NOT NULL,
  `nome` varchar(32) NOT NULL,
  `cognome` varchar(32) NOT NULL,
  `codice_fiscale` varchar(16) NOT NULL,
  `data_nascita` date NOT NULL,
  `telefono` varchar(13) NOT NULL,
  `mail` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `educatore`
--

INSERT INTO `educatore` (`id`, `nome`, `cognome`, `codice_fiscale`, `data_nascita`, `telefono`, `mail`) VALUES
(1, 'Andrea', 'Rossi', 'NDRRSS89E04L400R', '1889-04-20', '1112223334', 'andrea.rossi@underlimits.com'),
(2, 'Marco', 'Ventura', 'NDRRSS89SFEW', '2007-01-09', '3292618521', 'marco.ventura@galileo.galileicrema.it');

-- --------------------------------------------------------

--
-- Struttura della tabella `iscritto`
--

CREATE TABLE `iscritto` (
  `id` int(11) NOT NULL,
  `Nome` varchar(255) NOT NULL,
  `Cognome` varchar(255) NOT NULL,
  `Data_nascita` date NOT NULL,
  `Codice_fiscale` varchar(16) NOT NULL,
  `Contatti` varchar(255) NOT NULL,
  `Disabilita` varchar(255) NOT NULL,
  `Allergie_Intolleranze` varchar(255) NOT NULL,
  `Note` varchar(500) NOT NULL,
  `Prezzo_Orario` decimal(10,2) NOT NULL,
  `Fotografia` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `iscritto`
--

INSERT INTO `iscritto` (`id`, `Nome`, `Cognome`, `Data_nascita`, `Codice_fiscale`, `Contatti`, `Disabilita`, `Allergie_Intolleranze`, `Note`, `Prezzo_Orario`, `Fotografia`) VALUES
(28, 'Jacopo', 'Bertolasi', '2000-01-01', '-------', '-', '-', '-', '-', 7.00, 'immagini/1.jpeg'),
(36, 'Cristian', 'Moretti', '2000-01-01', '---', '---', '-', '-', '-', 10.00, 'immagini/5.jpeg'),
(38, 'Luca', 'Verzeri', '2000-02-01', '-----', '-----', '-', '-', '-', 15.00, 'immagini/4.jpeg'),
(45, 'Giorgia', 'Guerini Rocco', '2000-01-01', '------', '-------', '------', '------', '-', 12.00, 'immagini/3.jpeg'),
(49, 'Gabriele', 'Corona', '2000-10-10', '------------', '2313', '-', '-', '-', 8.00, 'immagini/6.jpeg'),
(50, 'Davide', 'Nicu', '2001-09-09', '- - - -', '-', '-', '-', '-', 9.00, 'immagini/2.jpeg');

-- --------------------------------------------------------

--
-- Struttura della tabella `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `Data` datetime NOT NULL,
  `Descrizione` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `partecipa`
--

CREATE TABLE `partecipa` (
  `id` int(11) NOT NULL,
  `Data` date NOT NULL,
  `Ora_Inizio` time NOT NULL,
  `Ora_Fine` time NOT NULL,
  `ID_Presenza` int(11) DEFAULT NULL,
  `ID_Attivita` int(11) NOT NULL,
  `ID_Educatore` int(11) NOT NULL,
  `presenza_effettiva` tinyint(1) NOT NULL,
  `ID_Ragazzo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `partecipa`
--

INSERT INTO `partecipa` (`id`, `Data`, `Ora_Inizio`, `Ora_Fine`, `ID_Presenza`, `ID_Attivita`, `ID_Educatore`, `presenza_effettiva`, `ID_Ragazzo`) VALUES
(272, '2026-02-10', '10:00:00', '15:30:00', NULL, 30, 1, 0, 28),
(274, '2026-02-10', '10:00:00', '15:30:00', 56, 30, 1, 0, 45),
(275, '2026-02-10', '10:00:00', '15:30:00', NULL, 30, 1, 0, 36),
(277, '2026-02-10', '10:00:00', '15:30:00', NULL, 30, 1, 0, 38),
(278, '2026-02-10', '10:00:00', '15:30:00', NULL, 30, 2, 0, 28),
(280, '2026-02-10', '10:00:00', '15:30:00', 56, 30, 2, 0, 45),
(281, '2026-02-10', '10:00:00', '15:30:00', NULL, 30, 2, 0, 36),
(283, '2026-02-10', '10:00:00', '15:30:00', NULL, 30, 2, 0, 38),
(308, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 28),
(309, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 49),
(310, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 45),
(311, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 36),
(312, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 50),
(313, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 38),
(314, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 2, 0, 28),
(315, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 2, 0, 49),
(316, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 2, 0, 45),
(317, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 2, 0, 36),
(318, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 2, 0, 50),
(319, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 2, 0, 38),
(326, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 1, 0, 28),
(327, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 1, 0, 49),
(328, '2026-02-11', '08:00:00', '09:37:00', 69, 30, 1, 0, 45),
(329, '2026-02-11', '08:00:00', '09:37:00', 67, 30, 1, 0, 36),
(330, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 1, 0, 50),
(331, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 1, 0, 38),
(332, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 2, 0, 28),
(333, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 2, 0, 49),
(334, '2026-02-11', '08:00:00', '09:37:00', 69, 30, 2, 0, 45),
(335, '2026-02-11', '08:00:00', '09:37:00', 67, 30, 2, 0, 36),
(336, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 2, 0, 50),
(337, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 2, 0, 38),
(350, '2026-02-12', '12:00:00', '14:00:00', NULL, 29, 2, 0, 45),
(351, '2026-02-12', '12:00:00', '14:00:00', 70, 29, 2, 0, 36),
(352, '2026-02-12', '12:00:00', '14:00:00', NULL, 29, 2, 0, 50),
(353, '2026-02-12', '12:00:00', '14:00:00', NULL, 29, 2, 0, 38),
(354, '2026-02-12', '14:30:00', '17:00:00', NULL, 30, 1, 0, 28),
(355, '2026-02-12', '14:30:00', '17:00:00', NULL, 30, 1, 0, 49),
(356, '2026-02-12', '14:30:00', '17:00:00', NULL, 30, 1, 0, 45),
(357, '2026-02-12', '14:30:00', '17:00:00', 70, 30, 1, 0, 36),
(358, '2026-02-12', '14:30:00', '17:00:00', NULL, 30, 1, 0, 50),
(359, '2026-02-12', '14:30:00', '17:00:00', NULL, 30, 1, 0, 38),
(360, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 1, 0, 28),
(361, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 1, 0, 49),
(362, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 1, 0, 45),
(363, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 1, 0, 36),
(364, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 1, 0, 50),
(365, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 1, 0, 38),
(366, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 2, 0, 28),
(367, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 2, 0, 49),
(368, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 2, 0, 45),
(369, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 2, 0, 36),
(370, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 2, 0, 50),
(371, '2026-02-13', '10:00:00', '21:00:00', NULL, 32, 2, 0, 38);

-- --------------------------------------------------------

--
-- Struttura della tabella `presenza`
--

CREATE TABLE `presenza` (
  `id` int(11) NOT NULL,
  `Ingresso` datetime NOT NULL,
  `Uscita` datetime NOT NULL,
  `Check_firma` tinyint(1) NOT NULL,
  `ID_Iscritto` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `presenza`
--

INSERT INTO `presenza` (`id`, `Ingresso`, `Uscita`, `Check_firma`, `ID_Iscritto`) VALUES
(49, '2026-02-09 09:00:00', '2026-02-09 16:00:00', 1, 36),
(51, '2026-02-10 10:00:00', '2026-02-10 15:00:00', 1, 28),
(53, '2026-02-10 13:00:00', '2026-02-10 17:00:00', 1, 36),
(54, '2026-02-10 11:37:00', '2026-02-10 16:00:00', 1, 38),
(56, '2026-02-10 10:00:00', '2026-02-10 18:00:00', 1, 45),
(63, '2026-02-11 09:00:00', '2026-02-11 17:00:00', 1, 28),
(64, '2026-02-11 09:00:00', '2026-02-11 16:00:00', 1, 49),
(66, '2026-02-11 10:00:00', '2026-02-11 17:00:00', 1, 50),
(67, '2026-02-11 01:00:00', '2026-02-11 23:00:00', 1, 36),
(68, '2026-02-11 05:00:00', '2026-02-11 09:00:00', 1, 38),
(69, '2026-02-11 08:00:00', '2026-02-11 17:00:00', 1, 45),
(70, '2026-02-12 09:00:00', '2026-02-12 17:00:00', 1, 36),
(71, '2026-02-13 11:00:00', '2026-02-13 16:00:00', 1, 36),
(72, '2026-02-13 16:00:00', '2026-02-13 18:00:00', 1, 49),
(73, '2026-02-13 09:00:00', '2026-02-13 15:00:00', 1, 50),
(74, '2026-02-14 09:00:00', '2026-02-14 15:30:00', 1, 45);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `attivita`
--
ALTER TABLE `attivita`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `educatore`
--
ALTER TABLE `educatore`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `iscritto`
--
ALTER TABLE `iscritto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Codice_fiscale` (`Codice_fiscale`);

--
-- Indici per le tabelle `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `partecipa`
--
ALTER TABLE `partecipa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ID_Presenza` (`ID_Presenza`),
  ADD KEY `ID_Attivita` (`ID_Attivita`),
  ADD KEY `partecipa_ibfk_3` (`ID_Educatore`),
  ADD KEY `partecipa_ibfk_4` (`ID_Ragazzo`);

--
-- Indici per le tabelle `presenza`
--
ALTER TABLE `presenza`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ID_Iscritto` (`ID_Iscritto`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `account`
--
ALTER TABLE `account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `attivita`
--
ALTER TABLE `attivita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT per la tabella `educatore`
--
ALTER TABLE `educatore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `iscritto`
--
ALTER TABLE `iscritto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT per la tabella `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `partecipa`
--
ALTER TABLE `partecipa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=372;

--
-- AUTO_INCREMENT per la tabella `presenza`
--
ALTER TABLE `presenza`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `partecipa`
--
ALTER TABLE `partecipa`
  ADD CONSTRAINT `partecipa_ibfk_1` FOREIGN KEY (`ID_Presenza`) REFERENCES `presenza` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `partecipa_ibfk_2` FOREIGN KEY (`ID_Attivita`) REFERENCES `attivita` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `partecipa_ibfk_3` FOREIGN KEY (`ID_Educatore`) REFERENCES `educatore` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `partecipa_ibfk_4` FOREIGN KEY (`ID_Ragazzo`) REFERENCES `iscritto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `presenza`
--
ALTER TABLE `presenza`
  ADD CONSTRAINT `presenza_ibfk_1` FOREIGN KEY (`ID_Iscritto`) REFERENCES `iscritto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
