<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no chache");
require __DIR__ . '/../config.php';

// --- BLOCCO ACCESSO DIRETTO ---

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


/*
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
*/

// Connessione al DB XAMPP
$host = "localhost";    // server
$user = "root";         // utente XAMPP
$pass = "";             // password di default
$db   = "time4all"; // nome del database

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Preleva i profili dal DB
// sql = "SELECT password FROM Account WHERE nome_utente = '$username'"; sfunzionza
$stmt = $conn->prepare(
    "SELECT password FROM Account WHERE nome_utente = ?"
);

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

$conn->close();



// Login
if ($result->num_rows > 0) {
    if (password_verify($password . PEPPER, $result->fetch_assoc()['password'])) {
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
