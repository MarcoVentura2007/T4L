<?php
// ------------------------------
// CARTELLA DATI
// ------------------------------
define('DATA_DIR', __DIR__ . '/data');

// crea la cartella se non esiste
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

// ------------------------------
// FILE UTENTI
// ------------------------------
define('USER_FILE', DATA_DIR . '/utenti.txt');

// crea il file se non esiste
if (!file_exists(USER_FILE)) {
    file_put_contents(USER_FILE, '');
}

// ------------------------------
// PEPPER (generazione automatica)
// ------------------------------
define('PEPPER_FILE', DATA_DIR . '/pepper.txt');

if (!file_exists(PEPPER_FILE)) {
    // genera una stringa random lunga 64 caratteri
    $random_pepper = bin2hex(random_bytes(32));
    file_put_contents(PEPPER_FILE, $random_pepper);
}

// legge il pepper dal file
define('PEPPER', trim(file_get_contents(PEPPER_FILE)));
