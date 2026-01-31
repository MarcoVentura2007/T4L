-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Gen 30, 2026 alle 14:58
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
-- Database: `time4all`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `Account`
--

CREATE TABLE `Account` (
  `id` int(11) NOT NULL,
  `nome_utente` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `codice_univoco` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `Account`
--

INSERT INTO `Account` (`id`, `nome_utente`, `password`, `codice_univoco`) VALUES
(1, 'Admin', '$2y$12$N1MSCjGLw8DYLdv1slag5.K8tBIMdUt591eU9odiJVQ/xPHwQee3S', '1234'),
(2, 'User', '$2y$12$c4t7dtu78gMNAda/U8iqs.4qZFs48Z4LLFVzmIKsFH9V84kOO.Xy2', '123'),
(3, 'Manager', '$2y$12$6KniR9zteBBhDEKrDSqoWOHx.ii6Fk0FWC61oFa6T5DVXlLtmD72e', '12');

-- --------------------------------------------------------

--
-- Struttura della tabella `Attivita`
--

CREATE TABLE `Attivita` (
  `id` int(11) NOT NULL,
  `Nome` varchar(255) NOT NULL,
  `Descrizione` varchar(500) NOT NULL,
  `Retribuita` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `Iscritto`
--

CREATE TABLE `Iscritto` (
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
-- Dump dei dati per la tabella `Iscritto`
--

INSERT INTO `Iscritto` (`id`, `Nome`, `Cognome`, `Data_nascita`, `Codice_fiscale`, `Contatti`, `Disabilita`, `Allergie_Intolleranze`, `Note`, `Prezzo_Orario`, `Fotografia`) VALUES
(1, 'Cristian', 'Moretti', '2001-01-01', 'CRTMRT00E04L400T', '3926473720', 'Mentale', 'Lattosio', 'Giocherellone ma facilmente suscettibile ', 20.00, 'immagini/Cristian_Moretti.png'),
(2, 'Gabriele', 'Corona', '2002-01-01', 'GBRCRN01E04L600T', '3409168873', 'Cromosoma in più', 'Glutine', 'Non sa nuotare, non so cosa mettere', 25.00, 'immagini/Gabriele_Corona.png'),
(3, 'Jacopo', 'Bertolasi', '2003-01-01', 'JCPBRT03E04L400Y', '3484816230', 'Cecità', 'Nichel', 'Manesco', 30.50, 'immagini/Jacopo_Bertolasi.png');

-- --------------------------------------------------------

--
-- Struttura della tabella `Partecipa`
--

CREATE TABLE `Partecipa` (
  `id` int(11) NOT NULL,
  `Data` date NOT NULL,
  `ID_Presenza` int(11) NOT NULL,
  `ID_Attivita` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `Presenza`
--

CREATE TABLE `Presenza` (
  `id` int(11) NOT NULL,
  `Ingresso` datetime NOT NULL,
  `Uscita` datetime NOT NULL,
  `Check_firma` tinyint(1) NOT NULL,
  `ID_Iscritto` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `Account`
--
ALTER TABLE `Account`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `Attivita`
--
ALTER TABLE `Attivita`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `Iscritto`
--
ALTER TABLE `Iscritto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Codice_fiscale` (`Codice_fiscale`);

--
-- Indici per le tabelle `Partecipa`
--
ALTER TABLE `Partecipa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ID_Presenza` (`ID_Presenza`),
  ADD KEY `ID_Attivita` (`ID_Attivita`);

--
-- Indici per le tabelle `Presenza`
--
ALTER TABLE `Presenza`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ID_Iscritto` (`ID_Iscritto`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `Account`
--
ALTER TABLE `Account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `Attivita`
--
ALTER TABLE `Attivita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `Iscritto`
--
ALTER TABLE `Iscritto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `Partecipa`
--
ALTER TABLE `Partecipa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `Presenza`
--
ALTER TABLE `Presenza`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `Partecipa`
--
ALTER TABLE `Partecipa`
  ADD CONSTRAINT `partecipa_ibfk_1` FOREIGN KEY (`ID_Presenza`) REFERENCES `Presenza` (`id`),
  ADD CONSTRAINT `partecipa_ibfk_2` FOREIGN KEY (`ID_Attivita`) REFERENCES `Attivita` (`id`);

--
-- Limiti per la tabella `Presenza`
--
ALTER TABLE `Presenza`
  ADD CONSTRAINT `presenza_ibfk_1` FOREIGN KEY (`ID_Iscritto`) REFERENCES `Iscritto` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
