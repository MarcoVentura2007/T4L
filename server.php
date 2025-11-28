<?php
// ============================================
// CONFIGURAZIONE
// ============================================
$DATA_DIR = __DIR__ . "/data";
$USER_FILE = $DATA_DIR . "/utenti.txt";
$PUBLIC_DIR = __DIR__ . "/public";
$LOGIN_PAGE = $PUBLIC_DIR . "/login.html";

// Crea cartelle e file se non esistono
if (!file_exists($DATA_DIR)) mkdir($DATA_DIR, 0777, true);
if (!file_exists($USER_FILE)) file_put_contents($USER_FILE, "");

// ============================================
// ROUTING SEMPLICE
// ============================================
$request_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method      = $_SERVER["REQUEST_METHOD"];

// ============================================
// LOGIN OBBLIGATORIO
// ============================================

// Se root, serve sempre login.html
if ($request_uri === "/" || $request_uri === "/login.html") {
    serveFile($LOGIN_PAGE);
}

// POST /login → gestione login/registrazione
if ($request_uri === "/login" && $method === "POST") {
    $username = $_POST["username"] ?? null;
    $password = $_POST["password"] ?? null;

    if (!$username || !$password) {
        respond("❌ Missing username or password", 400);
    }

    // Hash identico a Java
    $password_hash = hash("sha256", $password);

    // Carica utenti
    $users = [];
    foreach (file($USER_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (str_contains($line, ":")) {
            list($u, $p) = explode(":", $line, 2);
            $users[$u] = $p;
        }
    }

    // L’utente esiste?
    if (isset($users[$username])) {
        if ($users[$username] === $password_hash) {
            respond("✔️ Login avvenuto con successo");
        } else {
            respond("❌ Username o Password errati", 401);
        }
    }

    // Nuovo utente → registrazione
    file_put_contents($USER_FILE, "$username:$password_hash\n", FILE_APPEND);
    respond("☑️ Utente registrato con successo");
}

// ============================================
// SERVIZIO FILE DA PUBLIC
// ============================================

// Qualsiasi altra richiesta → serve file dalla cartella public
$file = $PUBLIC_DIR . $request_uri;

// Se esiste il file, servilo
if (file_exists($file) && is_file($file)) {
    serveFile($file);
}

// Se non esiste → blocca tutto e rimanda a login
header("Location: /");
exit;

// ============================================
// FUNZIONI
// ============================================

function serveFile($file) {
    if (!file_exists($file)) {
        header("HTTP/1.1 404 Not Found");
        echo "❌ File non trovato: $file";
        exit;
    }
    $mime = mime_content_type($file) ?: "application/octet-stream";
    header("Content-Type: $mime");
    readfile($file);
    exit;
}

function respond($text, $code = 200) {
    header("Access-Control-Allow-Origin: *");
    http_response_code($code);
    echo $text;
    exit;
}
