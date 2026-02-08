-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Feb 08, 2026 alle 03:04
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
(24, 'Maia e Tas', 'Apparecchiare la tavola e aiuto in cucina');

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
(1, 'Cristian', 'Moretti', '2001-01-01', 'CRTMRT00E04L400T', '-', '-', '-', '-', 0.00, 'immagini/Cristian_Moretti.png'),
(2, 'Gabriele', 'Corona', '2002-01-01', 'GBRCRN01E04L600T', '-', '-', '-', '-', 0.00, 'immagini/Gabriele_Corona.png'),
(3, 'Jacopo', 'Bertolasi', '2003-01-01', 'JCPBRT03E04L400Y', '-', '-', 'la', '-', 0.00, 'immagini/Jacopo_Bertolasi.png');

-- --------------------------------------------------------

--
-- Struttura della tabella `partecipa`
--

CREATE TABLE `partecipa` (
  `id` int(11) NOT NULL,
  `Data` date NOT NULL,
  `Ora_Inizio` time NOT NULL,
  `Ora_Fine` time NOT NULL,
  `ID_Presenza` int(11) NOT NULL,
  `ID_Attivita` int(11) NOT NULL,
  `ID_Educatore` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, '2026-02-06 07:00:00', '2026-02-06 17:00:00', 1, 3),
(3, '2026-02-07 09:00:00', '2026-02-07 16:00:00', 1, 2);

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
-- Indici per le tabelle `partecipa`
--
ALTER TABLE `partecipa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ID_Presenza` (`ID_Presenza`),
  ADD KEY `ID_Attivita` (`ID_Attivita`),
  ADD KEY `partecipa_ibfk_3` (`ID_Educatore`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `attivita`
--
ALTER TABLE `attivita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT per la tabella `educatore`
--
ALTER TABLE `educatore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `iscritto`
--
ALTER TABLE `iscritto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT per la tabella `partecipa`
--
ALTER TABLE `partecipa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `presenza`
--
ALTER TABLE `presenza`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `partecipa`
--
ALTER TABLE `partecipa`
  ADD CONSTRAINT `partecipa_ibfk_1` FOREIGN KEY (`ID_Presenza`) REFERENCES `presenza` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `partecipa_ibfk_2` FOREIGN KEY (`ID_Attivita`) REFERENCES `attivita` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `partecipa_ibfk_3` FOREIGN KEY (`ID_Educatore`) REFERENCES `educatore` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `presenza`
--
ALTER TABLE `presenza`
  ADD CONSTRAINT `presenza_ibfk_1` FOREIGN KEY (`ID_Iscritto`) REFERENCES `iscritto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
