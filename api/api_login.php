<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../config.php';

// dati POST
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['success'=>false, 'message'=>'Inserisci username e password']);
    exit;
}

// crea file utenti se non esiste
if (!file_exists(USER_FILE)) {
    if (!is_dir(dirname(USER_FILE))) mkdir(dirname(USER_FILE), 0777, true);
    file_put_contents(USER_FILE, '');
}

// leggi utenti
$users = [];
foreach(file(USER_FILE, FILE_IGNORE_NEW_LINES) as $line) {
    if (str_contains($line, ":")) {
        [$u, $p] = explode(":", $line, 2);
        $users[$u] = $p;
    }
}

// login
if (isset($users[$username])) {
    if (password_verify($password . PEPPER, $users[$username])) {
        $_SESSION['username'] = $username;
        echo json_encode(['success'=>true, 'message'=>'Login riuscito!']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Password errata']);
    }
} else {
    // registrazione automatica
    $hash = password_hash($password . PEPPER, PASSWORD_DEFAULT);
    file_put_contents(USER_FILE, "$username:$hash\n", FILE_APPEND);
    $_SESSION['username'] = $username;
    echo json_encode(['success'=>true, 'message'=>'Utente registrato e loggato!']);
}
    