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

    // Elimina token server-side se esiste
    if (isset($_SESSION['token'])) {
        $token_file = __DIR__ . "/data/tokens/" . $_SESSION['token'] . ".token";
        if (file_exists($token_file)) unlink($token_file);
    }

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
$TOKEN_DIR = $DATA_DIR . "/tokens";
$USER_FILE = $DATA_DIR . "/utenti.txt";
$LOGIN_PAGE = $PUBLIC_DIR . "/login.html";

// Crea cartelle e file se non esistono
if (!file_exists($DATA_DIR)) mkdir($DATA_DIR, 0777, true);
if (!file_exists($TOKEN_DIR)) mkdir($TOKEN_DIR, 0777, true);
if (!file_exists($USER_FILE)) file_put_contents($USER_FILE, "");

// ============================================
// FUNZIONI
// ============================================
function generate_token() {
    return bin2hex(random_bytes(32));
}

function respond($text, $code = 200) {
    header("Access-Control-Allow-Origin: *");
    http_response_code($code);
    echo $text;
    exit;
}

// ============================================
// OTTIENI URI E METODO
// ============================================
$request_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$request_uri = str_replace('\\', '/', $request_uri);
$method = $_SERVER["REQUEST_METHOD"];

// ============================================
// ENDPOINT DI VERIFICA TOKEN
// ============================================
if ($request_uri === "/verify-token") {
    $clientToken = $_GET["token"] ?? null;

    if (!$clientToken) {
        respond("missing token", 401);
    }

    $token_path = "$TOKEN_DIR/$clientToken.token";

    if (!file_exists($token_path)) {
        respond("invalid token", 401);
    }

    $owner = trim(file_get_contents($token_path));

    if ($owner !== ($_SESSION['username'] ?? '')) {
        respond("token mismatch", 401);
    }

    respond("ok");
}

// ============================================
// LOGIN / REGISTRAZIONE (VERSIONE SICURA)
// ============================================
if ($request_uri === "/login" && $method === "POST") {
    $username = $_POST["username"] ?? null;
    $password = $_POST["password"] ?? null;

    if (!$username || !$password)
        respond("❌ Username o Password Mancanti", 400);

    // Carica utenti
    $users = [];
    foreach (file($USER_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (str_contains($line, ":")) {
            list($u, $hash) = explode(":", $line, 2);
            $users[$u] = $hash;
        }
    }

    // LOGIN
    if (isset($users[$username])) {
        if (password_verify($password, $users[$username])) {

            session_regenerate_id(true);

            $token = generate_token();
            $token_file = "$TOKEN_DIR/$token.token";
            file_put_contents($token_file, $username);

            $_SESSION['username'] = $username;
            $_SESSION['token'] = $token;
            $_SESSION['last_activity'] = time();

            respond(json_encode([
                "status" => "ok",
                "token" => $token
            ]));
        } else {
            respond("❌ Username o Password errati", 401);
        }
    }

    // REGISTRAZIONE
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    file_put_contents($USER_FILE, "$username:$password_hash\n", FILE_APPEND);

    session_regenerate_id(true);

    $token = generate_token();
    $token_file = "$TOKEN_DIR/$token.token";
    file_put_contents($token_file, $username);

    $_SESSION['username'] = $username;
    $_SESSION['token'] = $token;
    $_SESSION['last_activity'] = time();

    respond(json_encode([
        "status" => "registered",
        "token" => $token
    ]));
}

// ============================================
// LOGOUT
// ============================================
if ($request_uri === "/logout") {

    // cancella token dal server
    if (isset($_SESSION['token'])) {
        $token_path = $TOKEN_DIR . "/" . $_SESSION['token'] . ".token";
        if (file_exists($token_path)) unlink($token_path);
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: /login.html");
    exit;
}

// ============================================
// SERVE FILE STATICI PUBBLICI
// ============================================
$file_path = realpath($PUBLIC_DIR . $request_uri);

if ($file_path && is_file($file_path) && str_starts_with($file_path, realpath($PUBLIC_DIR))) {
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
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
// (il front-end controlla il token, ma il server protegge)
// ============================================
if (!isset($_SESSION['username']) && $request_uri !== "/login.html" && $request_uri !== "/login") {
    header("Location: /login.html");
    exit;
}

// ============================================
// REDIRECT FORZATO A LOGIN.HTML
// ============================================
if ($request_uri === "/" || $request_uri === "") {
    header("Location: /login.html");
    exit;
}

// ============================================
// SERVE LOGIN.HTML
// ============================================
if ($request_uri === "/login.html") {
    if (!file_exists($LOGIN_PAGE)) {
        header("HTTP/1.1 404 Not Found");
        echo "❌ File non trovato: $LOGIN_PAGE";
        exit;
    }
    header("Content-Type: text/html");
    readfile($LOGIN_PAGE);
    exit;
}

?>
