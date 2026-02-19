-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Feb 19, 2026 alle 12:43
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
-- Database: `time4allergo`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `allegati`
--

CREATE TABLE `allegati` (
  `id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `ID_Iscritto` int(11) NOT NULL
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
  `Email` varchar(255) NOT NULL,
  `Telefono` varchar(15) NOT NULL,
  `Disabilita` varchar(255) NOT NULL,
  `Note` varchar(500) NOT NULL,
  `Stipendio_Orario` decimal(10,2) NOT NULL,
  `Fotografia` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `iscritto`
--

INSERT INTO `iscritto` (`id`, `Nome`, `Cognome`, `Data_nascita`, `Codice_fiscale`, `Email`, `Telefono`, `Disabilita`, `Note`, `Stipendio_Orario`, `Fotografia`) VALUES
(28, 'Jacopo', 'Bertolasi', '2000-01-01', '-', '-', 'fhe', '-', '-', 7.00, 'immagini/1.jpeg'),
(36, 'Cristian', 'Moretti', '2000-01-01', '---', '---', '', '-', '-', 10.00, 'immagini/5.jpeg'),
(38, 'Luca', 'Verzeri', '2000-02-01', '-----', '---—', '', '-', '-', 15.00, 'immagini/4.jpeg');

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
(49, '2026-02-09 09:00:00', '2026-02-09 16:00:00', 1, 36);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `allegati`
--
ALTER TABLE `allegati`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `iscritto`
--
ALTER TABLE `iscritto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Codice_fiscale` (`Codice_fiscale`);

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
-- AUTO_INCREMENT per la tabella `allegati`
--
ALTER TABLE `allegati`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `iscritto`
--
ALTER TABLE `iscritto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT per la tabella `presenza`
--
ALTER TABLE `presenza`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `presenza`
--
ALTER TABLE `presenza`
  ADD CONSTRAINT `presenza_ibfk_1` FOREIGN KEY (`ID_Iscritto`) REFERENCES `iscritto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
