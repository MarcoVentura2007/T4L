-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Feb 26, 2026 alle 08:18
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
(1, 'Admin', '$2y$10$u61ohHf9mipYTIv13USZkuLpbFpmXzNI8XZkq97SV2I97Eyjo/zoW', '1234', 'Amministratore'),
(2, 'User', '$2y$12$c4t7dtu78gMNAda/U8iqs.4qZFs48Z4LLFVzmIKsFH9V84kOO.Xy2', '123', 'Educatore'),
(3, 'Manager', '$2y$12$6KniR9zteBBhDEKrDSqoWOHx.ii6Fk0FWC61oFa6T5DVXlLtmD72e', '12', 'Contabile');

-- --------------------------------------------------------

--
-- Struttura della tabella `allegati`
--

CREATE TABLE `allegati` (
  `id` int(11) NOT NULL,
  `percorso_file` varchar(255) NOT NULL,
  `ID_Iscritto` int(11) NOT NULL,
  `data_upload` datetime NOT NULL,
  `nome_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `allegati`
--

INSERT INTO `allegati` (`id`, `percorso_file`, `ID_Iscritto`, `data_upload`, `nome_file`) VALUES
(2, 'allegati/1771797086_Agenda_over.pdf', 28, '2026-02-22 22:51:26', 'Agenda over.pdf'),
(4, 'allegati/1771832634_sql-basics-cheat-sheet-a4.pdf', 28, '2026-02-23 08:43:54', 'sql-basics-cheat-sheet-a4.pdf'),
(5, 'allegati/1771832666_VERIFICA_DOPOGUERRA_29_FASCISMO.pdf', 28, '2026-02-23 08:44:26', 'VERIFICA_DOPOGUERRA_29_FASCISMO.pdf');

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
(3, 'Marco', 'Ventura', 'cneiunfrijen', '2007-01-09', '347943', 'marco.ventura@galileo.galileicrema.it');

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
  `Email` varchar(255) NOT NULL,
  `Telefono` varchar(15) NOT NULL,
  `Disabilita` varchar(255) NOT NULL,
  `Allergie_Intolleranze` varchar(255) NOT NULL,
  `Note` varchar(500) NOT NULL,
  `Prezzo_Orario` decimal(10,2) NOT NULL,
  `Fotografia` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `iscritto`
--

INSERT INTO `iscritto` (`id`, `Nome`, `Cognome`, `Data_nascita`, `Codice_fiscale`, `Email`, `Telefono`, `Disabilita`, `Allergie_Intolleranze`, `Note`, `Prezzo_Orario`, `Fotografia`) VALUES
(28, 'Jacopo', 'Bertolasi', '2000-01-01', '-------', '-', '3292618521', '-', 'Glutine', '', 9.00, 'immagini/1771404276_1.jpeg'),
(36, 'Cristian', 'Moretti', '2000-01-01', '---', '---', '', '-', '-', '-', 10.00, 'immagini/5.jpeg'),
(38, 'Luca', 'Verzeri', '2000-02-01', '-----', '-----', '', '-', '-', '-', 15.00, 'immagini/4.jpeg'),
(45, 'Giorgia', 'Guerini Rocco', '2000-01-01', '------', '-----—', '', '-', '------', '-', 12.00, 'immagini/3.jpeg'),
(49, 'Gabriele', 'Corona', '2000-10-10', '------------', '231', '', '-', '-', '-', 8.00, 'immagini/6.jpeg'),
(50, 'Davide', 'Nicu', '2001-09-15', '- - - -', '-', '', '-', '-', '-', 9.00, 'immagini/2.jpeg');

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
(308, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 28),
(309, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 49),
(310, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 45),
(311, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 36),
(312, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 50),
(313, '2026-02-11', '10:00:00', '18:11:00', NULL, 29, 1, 0, 38),
(326, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 1, 0, 28),
(327, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 1, 0, 49),
(328, '2026-02-11', '08:00:00', '09:37:00', 69, 30, 1, 0, 45),
(329, '2026-02-11', '08:00:00', '09:37:00', 67, 30, 1, 0, 36),
(330, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 1, 0, 50),
(331, '2026-02-11', '08:00:00', '09:37:00', NULL, 30, 1, 0, 38),
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
(372, '2026-02-16', '12:00:00', '13:30:00', NULL, 29, 1, 0, 28),
(373, '2026-02-16', '12:00:00', '13:30:00', NULL, 29, 1, 0, 49),
(374, '2026-02-16', '12:00:00', '13:30:00', NULL, 29, 1, 0, 36),
(399, '2026-02-16', '08:00:00', '11:00:00', NULL, 32, 1, 0, 49),
(400, '2026-02-16', '08:00:00', '11:00:00', 78, 32, 1, 0, 36),
(427, '2026-02-17', '12:00:00', '14:00:00', 82, 29, 1, 0, 28),
(428, '2026-02-17', '12:00:00', '14:00:00', 80, 29, 1, 0, 49),
(429, '2026-02-17', '12:00:00', '14:00:00', NULL, 29, 1, 0, 45),
(430, '2026-02-17', '12:00:00', '14:00:00', 81, 29, 1, 0, 36),
(450, '2026-02-18', '15:00:00', '17:30:00', NULL, 32, 1, 0, 28),
(451, '2026-02-18', '15:00:00', '17:30:00', NULL, 32, 1, 0, 45),
(452, '2026-02-18', '15:00:00', '17:30:00', 83, 32, 1, 0, 36),
(460, '2026-02-18', '09:11:00', '10:05:00', NULL, 30, 1, 0, 45),
(461, '2026-02-18', '09:11:00', '10:05:00', 83, 30, 1, 0, 36),
(462, '2026-02-18', '09:11:00', '10:05:00', NULL, 30, 3, 0, 45),
(463, '2026-02-18', '09:11:00', '10:05:00', 83, 30, 3, 0, 36),
(464, '2026-02-19', '17:04:00', '19:04:00', NULL, 32, 1, 0, 49),
(465, '2026-02-19', '17:04:00', '19:04:00', NULL, 32, 1, 0, 45),
(466, '2026-02-19', '17:04:00', '19:04:00', NULL, 32, 1, 0, 36),
(467, '2026-02-19', '17:04:00', '19:04:00', NULL, 32, 1, 0, 50),
(468, '2026-02-20', '10:00:00', '14:00:00', NULL, 29, 3, 0, 28),
(469, '2026-02-20', '10:00:00', '14:00:00', NULL, 29, 3, 0, 49),
(470, '2026-02-20', '10:00:00', '14:00:00', NULL, 29, 3, 0, 45),
(471, '2026-02-20', '10:00:00', '14:00:00', NULL, 29, 3, 0, 36),
(477, '2026-02-20', '11:30:00', '14:30:00', NULL, 32, 1, 0, 49),
(478, '2026-02-20', '11:30:00', '14:30:00', NULL, 32, 1, 0, 45),
(479, '2026-02-20', '11:30:00', '14:30:00', NULL, 32, 1, 0, 36),
(480, '2026-02-20', '12:30:00', '15:00:00', NULL, 30, 1, 0, 49),
(481, '2026-02-20', '12:30:00', '15:00:00', NULL, 30, 1, 0, 45),
(482, '2026-02-20', '12:30:00', '15:00:00', 90, 30, 1, 0, 36),
(483, '2026-02-20', '12:30:00', '15:00:00', 91, 30, 1, 0, 50),
(484, '2026-02-20', '12:30:00', '15:00:00', NULL, 30, 1, 0, 38),
(485, '2026-02-16', '19:00:00', '22:00:00', NULL, 29, 1, 0, 49),
(486, '2026-02-16', '19:00:00', '22:00:00', NULL, 29, 1, 0, 45),
(487, '2026-02-16', '19:00:00', '22:00:00', NULL, 29, 1, 0, 36),
(489, '2026-02-23', '13:00:00', '14:30:00', NULL, 32, 3, 0, 28),
(490, '2026-02-23', '13:00:00', '14:30:00', NULL, 32, 3, 0, 49),
(491, '2026-02-23', '13:00:00', '14:30:00', NULL, 32, 3, 0, 45),
(492, '2026-02-23', '13:00:00', '14:30:00', 95, 32, 3, 0, 36),
(493, '2026-02-23', '13:00:00', '14:30:00', NULL, 32, 3, 0, 50),
(494, '2026-02-23', '13:00:00', '14:30:00', NULL, 32, 3, 0, 38),
(495, '2026-02-23', '15:00:00', '18:00:00', NULL, 30, 3, 0, 49),
(496, '2026-02-23', '15:00:00', '18:00:00', NULL, 30, 3, 0, 45),
(497, '2026-02-23', '15:00:00', '18:00:00', NULL, 30, 3, 0, 36);

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
(74, '2026-02-14 09:00:00', '2026-02-14 15:30:00', 1, 45),
(75, '2026-02-16 08:00:00', '2026-02-16 18:00:00', 1, 28),
(78, '2026-02-16 08:00:00', '2026-02-16 21:02:00', 1, 36),
(79, '2026-02-16 06:00:00', '2026-02-16 16:00:00', 1, 49),
(80, '2026-02-17 07:00:00', '2026-02-17 18:00:00', 1, 49),
(81, '2026-02-17 08:00:00', '2026-02-17 17:00:00', 1, 36),
(82, '2026-02-17 10:00:00', '2026-02-17 16:00:00', 1, 28),
(83, '2026-02-18 08:00:00', '2026-02-18 20:00:00', 1, 36),
(84, '2026-02-19 07:00:00', '2026-02-19 17:00:00', 1, 36),
(85, '2026-02-19 07:00:00', '2026-02-19 17:00:00', 1, 49),
(86, '2026-02-19 08:00:00', '2026-02-19 16:00:00', 1, 28),
(87, '2026-02-19 09:00:00', '2026-02-19 16:00:00', 1, 50),
(89, '2026-02-19 08:00:00', '2026-02-19 16:00:00', 1, 38),
(90, '2026-02-20 08:00:00', '2026-02-20 16:00:00', 1, 36),
(91, '2026-02-20 09:00:00', '2026-02-20 15:00:00', 1, 50),
(92, '2026-02-20 10:00:00', '2026-02-20 14:30:00', 1, 49),
(93, '2026-02-20 10:00:00', '2026-02-20 16:00:00', 1, 28),
(94, '2026-02-20 09:00:00', '2026-02-20 15:00:00', 1, 45),
(95, '2026-02-23 10:00:00', '2026-02-23 16:00:00', 1, 36),
(96, '2026-02-24 08:00:00', '2026-02-24 14:00:00', 1, 36);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `allegati`
--
ALTER TABLE `allegati`
  ADD PRIMARY KEY (`id`),
  ADD KEY `allegati_ibfk_1` (`ID_Iscritto`);

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
-- AUTO_INCREMENT per la tabella `allegati`
--
ALTER TABLE `allegati`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT per la tabella `attivita`
--
ALTER TABLE `attivita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT per la tabella `educatore`
--
ALTER TABLE `educatore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `iscritto`
--
ALTER TABLE `iscritto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT per la tabella `partecipa`
--
ALTER TABLE `partecipa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=498;

--
-- AUTO_INCREMENT per la tabella `presenza`
--
ALTER TABLE `presenza`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `allegati`
--
ALTER TABLE `allegati`
  ADD CONSTRAINT `allegati_ibfk_1` FOREIGN KEY (`ID_Iscritto`) REFERENCES `iscritto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
