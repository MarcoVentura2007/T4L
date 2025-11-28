<?php
// ============================================
// CONFIGURAZIONE
// ============================================
session_start(); // apre una sessione per ogni utente

$DATA_DIR = __DIR__ . "/data";
$USER_FILE = $DATA_DIR . "/utenti.txt";
$PUBLIC_DIR = __DIR__ . "/public";
$LOGIN_PAGE = $PUBLIC_DIR . "/login.html";

// Creazione cartelle/file se mancanti
if (!file_exists($DATA_DIR)) mkdir($DATA_DIR, 0777, true);
if (!file_exists($USER_FILE)) file_put_contents($USER_FILE, "");

// ============================================
// ROUTING
// ============================================
$request_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method      = $_SERVER["REQUEST_METHOD"];

// ============================================
// LOGIN / REGISTRAZIONE (gestiti da te)
// ============================================
if ($request_uri === "/login" && $method === "POST") {

    $username = $_POST["username"] ?? null;
    $password = $_POST["password"] ?? null;

    if (!$username || !$password) respond("❌ Username o Password Mancanti", 400);

    // Hash identico al tuo Java
    $password_hash = hash("sha256", $password);

    // Carica utenti da file
    $users = [];
    foreach (file($USER_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (str_contains($line, ":")) {
            list($u, $p) = explode(":", $line, 2);
            $users[$u] = $p;
        }
    }

    // L'utente esiste?
    if (isset($users[$username])) {
        if ($users[$username] === $password_hash) {
            // LOGIN OK
            $_SESSION["username"] = $username;
            respond("✔️ Login avvenuto con successo");
        } else {
            respond("❌ Username o Password errati", 401);
        }
    }

    // Registrazione nuovo utente
    file_put_contents($USER_FILE, "$username:$password_hash\n", FILE_APPEND);
    $_SESSION["username"] = $username;
    respond("☑️ Utente registrato con successo");
}

// ============================================
// LOGOUT
// ============================================
if ($request_uri === "/logout") {
    $_SESSION = [];
    session_destroy();
    respond("✔️ Logout completato");
}

// ============================================
// SERVIZIO FILE STATICI
// ============================================
$file = realpath($PUBLIC_DIR . $request_uri);

if ($file && is_file($file) && str_starts_with($file, realpath($PUBLIC_DIR))) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = match($ext) {
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'html','htm' => 'text/html',
        default => 'application/octet-stream',
    };
    header("Content-Type: $mime");
    readfile($file);
    exit;
}

// Se non esiste → rispondi 404
respond("❌ File non trovato", 404);

// ============================================
// FUNZIONI
// ============================================
function respond($text, $code = 200) {
    http_response_code($code);
    echo $text;
    exit;
}
