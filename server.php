<?php
// ============================================
// CONFIGURAZIONE
// ============================================
session_start();

$DATA_DIR     = __DIR__ . "/data";
$USER_FILE    = $DATA_DIR . "/utenti.txt";
$IP_FILE      = $DATA_DIR . "/ip_attempts.json";
$PUBLIC_DIR   = __DIR__ . "/public";
$LOGIN_PAGE   = $PUBLIC_DIR . "/login.html";

$MAX_ATTEMPTS = 5;           // numero massimo tentativi falliti
$BLOCK_TIME   = 300;         // tempo blocco IP (in secondi) → 300 = 5 minuti

// Creazione cartelle/file se mancanti
if (!file_exists($DATA_DIR)) mkdir($DATA_DIR, 0777, true);
if (!file_exists($USER_FILE)) file_put_contents($USER_FILE, "");
if (!file_exists($IP_FILE)) file_put_contents($IP_FILE, json_encode([]));

// Carica PEPPER
require_once __DIR__ . "/config.php";

// ============================================
// ROUTING
// ============================================
$request_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method      = $_SERVER["REQUEST_METHOD"];

// ============================================
// LOGIN / REGISTRAZIONE
// ============================================
if ($request_uri === "/login" && $method === "POST") {

    $username = $_POST["username"] ?? null;
    $password = $_POST["password"] ?? null;
    $ip       = $_SERVER["REMOTE_ADDR"];

    if (!$username || !$password) respond("❌ Username o Password mancanti", 400);

    // --------------------------------------------------------
    // Verifica blocco IP
    // --------------------------------------------------------
    $ipdb = json_decode(file_get_contents($IP_FILE), true);

    if (isset($ipdb[$ip])) {
        $info = $ipdb[$ip];

        if ($info["attempts"] >= $MAX_ATTEMPTS) {
            $time_passed = time() - $info["last_attempt"];

            if ($time_passed < $BLOCK_TIME) {
                $remaining = $BLOCK_TIME - $time_passed;
                respond("⛔ Troppi tentativi. Riprova tra $remaining secondi.", 429);
            } else {
                // reset dopo il blocco
                $ipdb[$ip] = ["attempts" => 0, "last_attempt" => time()];
                file_put_contents($IP_FILE, json_encode($ipdb, JSON_PRETTY_PRINT));
            }
        }
    }

    // --------------------------------------------------------
    // Pepper: hash iniziale
    // --------------------------------------------------------
    $peppered = hash_hmac("sha256", $password, PEPPER);

    // --------------------------------------------------------
    // Carica utenti
    // --------------------------------------------------------
    $users = [];
    foreach (file($USER_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (str_contains($line, ":")) {
            list($u, $p) = explode(":", $line, 2);
            $users[$u] = $p;
        }
    }

    // --------------------------------------------------------
    // LOGIN
    // --------------------------------------------------------
    if (isset($users[$username])) {

        $stored_hash = $users[$username];

        if (password_verify($peppered, $stored_hash)) {

            // Reset tentativi IP
            $ipdb[$ip] = ["attempts" => 0, "last_attempt" => time()];
            file_put_contents($IP_FILE, json_encode($ipdb, JSON_PRETTY_PRINT));

            // Rehash opzionale
            if (password_needs_rehash($stored_hash, PASSWORD_ARGON2ID)) {
                $new_hash = password_hash($peppered, PASSWORD_ARGON2ID);
                salvaNuovoHash($USER_FILE, $username, $new_hash);
            }

            $_SESSION["username"] = $username;
            respond("✔️ Login avvenuto con successo");
        }

        // tentativo fallito
        incrementaTentativiIP($IP_FILE, $ip);
        respond("❌ Username o password errati", 401);
    }

    // --------------------------------------------------------
    // REGISTRAZIONE NUOVO UTENTE
    // --------------------------------------------------------
    $password_hash = password_hash($peppered, PASSWORD_ARGON2ID);

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

respond("❌ File non trovato", 404);


// ============================================
// FUNZIONI
// ============================================
function respond($text, $code = 200) {
    http_response_code($code);
    echo $text;
    exit;
}

function incrementaTentativiIP($file, $ip) {
    $db = json_decode(file_get_contents($file), true);

    if (!isset($db[$ip])) {
        $db[$ip] = ["attempts" => 1, "last_attempt" => time()];
    } else {
        $db[$ip]["attempts"] += 1;
        $db[$ip]["last_attempt"] = time();
    }

    file_put_contents($file, json_encode($db, JSON_PRETTY_PRINT));
}

function salvaNuovoHash($file, $username, $newhash) {
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    $out = [];

    foreach ($lines as $line) {
        if (str_starts_with($line, $username . ":")) {
            $out[] = "$username:$newhash";
        } else {
            $out[] = $line;
        }
    }

    file_put_contents($file, implode("\n", $out) . "\n");
}
