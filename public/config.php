<?php

// --- BLOCCO ACCESSO DIRETTO ---
// Permetti solo richieste POST AJAX
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit;
}

// --- FINE BLOCCO ---

define('PEPPER_FILE', DATA_DIR . '/pepper.txt');

if (!file_exists(PEPPER_FILE)) {
    file_put_contents(PEPPER_FILE, bin2hex(random_bytes(32)));
}

define('PEPPER', trim(file_get_contents(PEPPER_FILE)));
