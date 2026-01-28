CREATE database IF NOT EXISTS `Overlimits`;

CREATE TABLE `Iscritto`(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `Nome` VARCHAR(255) NOT NULL,
    `Cognome` VARCHAR(255) NOT NULL,
    `Data_nascita` DATE NOT NULL,
    `Codice_fiscale` VARCHAR(255) NOT NULL UNIQUE,
    `Contatti` VARCHAR(255) NOT NULL,
    `Disabilita` VARCHAR(255) NOT NULL,
    `Fotografia` VARCHAR(255) NOT NULL
);
CREATE TABLE `Presenza`(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `Ingresso` DATETIME NOT NULL,
    `Uscita` DATETIME NOT NULL,
    `Check_firma` BOOLEAN NOT NULL,
    `ID_Iscritto` BIGINT NOT NULL
);
CREATE TABLE `ID_Attivita`(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `Nome` VARCHAR(255) NOT NULL,
    `Retribuita` BOOLEAN NOT NULL
);
CREATE TABLE `Partecipa`(
    `Id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `Data` DATE NOT NULL,
    `ID_Presenza` BIGINT NOT NULL,
    `ID_Attivita` BIGINT NOT NULL
);
CREATE TABLE `Account`(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nome_utente` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `codice_unicovo` VARCHAR(255) NOT NULL
);
ALTER TABLE
    `Partecipa` ADD CONSTRAINT `partecipa_id_presenza_foreign` FOREIGN KEY(`ID_Presenza`) REFERENCES `Presenza`(`id`);
ALTER TABLE
    `Presenza` ADD CONSTRAINT `presenza_id_iscritto_foreign` FOREIGN KEY(`ID_Iscritto`) REFERENCES `Iscritto`(`id`);
ALTER TABLE
    `Partecipa` ADD CONSTRAINT `partecipa_id_attivita_foreign` FOREIGN KEY(`ID_Attivita`) REFERENCES `Attività`(`id`);