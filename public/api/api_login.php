<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../config.php';

// --- BLOCCO ACCESSO DIRETTO ---
// Permetti solo richieste POST AJAX
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit;
}

// --- FINE BLOCCO ---
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['success'=>false, 'message'=>'Inserisci username e password']);
    exit;
}

// Leggi utenti
$users = [];
if (file_exists(USER_FILE)) {
    foreach (file(USER_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (str_contains($line, ":")) {
            [$u, $p] = explode(":", $line, 2);
            $users[$u] = $p;
        }
    }
}

// Login
if (isset($users[$username])) {
    if (password_verify($password . PEPPER, $users[$username])) {
        $_SESSION['username'] = $username;
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Username o password errati']);
    }
} 
else {
    echo json_encode(['success'=>false, 'message'=>'Username o password errati']);
}
exit;
