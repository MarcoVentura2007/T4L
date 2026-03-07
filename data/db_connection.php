<?php
/**
 * Helper per la connessione al database
 * Legge le credenziali da db_credentials.json
 */

function getDbConnection(string $dbName): mysqli {
    $jsonPath = dirname(__FILE__) . '/db_credentials.json';
    
    if (!file_exists($jsonPath)) {
        throw new Exception("File credenziali non trovato: $jsonPath");
    }
    
    $credentials = json_decode(file_get_contents($jsonPath), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Errore nel parsing del file credenziali: " . json_last_error_msg());
    }
    
    if (!isset($credentials[$dbName])) {
        throw new Exception("Database non trovato: $dbName");
    }
    
    $creds = $credentials[$dbName];
    
    $conn = new mysqli(
        $creds['host'],
        $creds['username'],
        $creds['password'],
        $creds['database']
    );
    
    if ($conn->connect_error) {
        throw new Exception("Connessione fallita: " . $conn->connect_error);
    }
    
    return $conn;
}

