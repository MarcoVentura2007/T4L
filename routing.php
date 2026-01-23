<?php
// ============================================
// CONFIGURAZIONE E SESSIONE SICURA
// ============================================

// Parametri della sessione
$session_lifetime = 1800; // 30 minuti
session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path' => '/',
    'secure' => false,   // true se usi HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Timeout automatico
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_lifetime) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
$_SESSION['last_activity'] = time();

// ============================================
// CONFIGURAZIONE CARTELLE / FILE
// ============================================
$PUBLIC_DIR = __DIR__ . "/public";
$DATA_DIR = __DIR__ . "/data";
$USER_FILE = $DATA_DIR . "/utenti.txt";
$LOGIN_PAGE = $PUBLIC_DIR . "/login.php";

// Crea cartelle e file se non esistono
if (!file_exists($DATA_DIR)) mkdir($DATA_DIR, 0777, true);
if (!file_exists($USER_FILE)) file_put_contents($USER_FILE, "");

// ============================================
// OTTIENI URI E METODO
// ============================================
$request_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$request_uri = str_replace('\\', '/', $request_uri);
$method = $_SERVER["REQUEST_METHOD"];

// ============================================
// LOGIN / REGISTRAZIONE
// ============================================
if ($request_uri === "/login" && $method === "POST") {
    $username = $_POST["username"] ?? null;
    $password = $_POST["password"] ?? null;

    if (!$username || !$password) respond("❌ Username o Password Mancanti", 400);

    $password_hash = hash("sha256", $password);

    // Carica utenti
    $users = [];
    foreach (file($USER_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (str_contains($line, ":")) {
            list($u, $p) = explode(":", $line, 2);
            $users[$u] = $p;
        }
    }

    if (isset($users[$username])) {
        if ($users[$username] === $password_hash) {
            session_regenerate_id(true);
            $_SESSION['username'] = $username;
            $_SESSION['last_activity'] = time();
            respond("✔️ Login avvenuto con successo");
        } else {
            respond("❌ Username o Password errati", 401);
        }
    }

    // Nuovo utente → registrazione
    file_put_contents($USER_FILE, "$username:$password_hash\n", FILE_APPEND);
    session_regenerate_id(true);
    $_SESSION['username'] = $username;
    $_SESSION['last_activity'] = time();
    respond("☑️ Utente registrato con successo");
}

// ============================================
// LOGOUT
// ============================================
if ($request_uri === "/logout") {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: /login.php");
    exit;
}

// ============================================
// SERVE FILE STATICI O PHP
// ============================================
$file_path = realpath($PUBLIC_DIR . $request_uri);

if ($file_path && is_file($file_path) && str_starts_with($file_path, realpath($PUBLIC_DIR))) {
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

    // Se PHP → esegui
    if ($ext === 'php') {
        require $file_path;
        exit;
    }

    // File statici → CSS, JS, immagini, HTML
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
    readfile($file_path);
    exit;
}

// ============================================
// BLOCCO ACCESSO PAGINE PRIVATE
// ============================================
// Definisci le cartelle/file pubblici che NON richiedono login
$public_pages = [
    '/login.php',
    '/login.html',
    '/login',
    '/css',    // cartella CSS
    '/js',     // cartella JS
    '/images', // cartella immagini
];

// Controlla se la richiesta inizia con un percorso pubblico
$allowed = false;
foreach ($public_pages as $pp) {
    if (str_starts_with($request_uri, $pp)) {
        $allowed = true;
        break;
    }
}

// Se non loggato e non è pubblico → redirect login
if (!isset($_SESSION['username']) && !$allowed) {
    header("Location: /login.php");
    exit;
}

// ============================================
// REDIRECT ROOT
// ============================================
if ($request_uri === "/" || $request_uri === "") {
    header("Location: /login.php");
    exit;
}

// ============================================
// 404
// ============================================
header("HTTP/1.1 404 Not Found");
echo "❌ File non trovato: $request_uri";
exit;

// ============================================
// FUNZIONE RESPOND
// ============================================
function respond($text, $code = 200) {
    header("Access-Control-Allow-Origin: *");
    http_response_code($code);
    echo $text;
    exit;
}
