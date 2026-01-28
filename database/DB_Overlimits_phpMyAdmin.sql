CREATE DATABASE IF NOT EXISTS `Overlimits`;
USE `Overlimits`;

CREATE TABLE `Iscritto` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `Nome` VARCHAR(255) NOT NULL,
    `Cognome` VARCHAR(255) NOT NULL,
    `Data_nascita` DATE NOT NULL,
    `Codice_fiscale` VARCHAR(16) NOT NULL UNIQUE,
    `Contatti` VARCHAR(255) NOT NULL,
    `Disabilita` VARCHAR(255) NOT NULL,
    `Allergie_Intolleranze` VARCHAR(255) NOT NULL,
    `Note` VARCHAR(500) NOT NULL,
    `Prezzo_Orario` DECIMAL(10,2) NOT NULL,
    `Fotografia` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
);

CREATE TABLE `Presenza` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `Ingresso` DATETIME NOT NULL,
    `Uscita` DATETIME NOT NULL,
    `Check_firma` TINYINT(1) NOT NULL,
    `ID_Iscritto` INT NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`ID_Iscritto`) REFERENCES `Iscritto`(`id`)
);

CREATE TABLE `Attivita` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `Nome` VARCHAR(255) NOT NULL,
    `Descrizione` VARCHAR(500) NOT NULL,
    `Retribuita` TINYINT(1) NOT NULL,
    PRIMARY KEY (`id`)
);

CREATE TABLE `Partecipa` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `Data` DATE NOT NULL,
    `ID_Presenza` INT NOT NULL,
    `ID_Attivita` INT NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`ID_Presenza`) REFERENCES `Presenza`(`id`),
    FOREIGN KEY (`ID_Attivita`) REFERENCES `Attivita`(`id`)
);

CREATE TABLE `Account` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `nome_utente` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `codice_univoco` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
);