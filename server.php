<?php
// ============================================
// CONFIGURAZIONE
// ============================================
$DATA_DIR   = __DIR__ . "/data";
$USER_FILE  = $DATA_DIR . "/utenti.txt";
$TOKEN_FILE = $DATA_DIR . "/tokens.txt";
$PUBLIC_DIR = __DIR__ . "/public";
$LOGIN_PAGE = $PUBLIC_DIR . "/login.html";

// Crea cartelle e file se non esistono
if (!file_exists($DATA_DIR)) mkdir($DATA_DIR, 0777, true);
if (!file_exists($USER_FILE)) file_put_contents($USER_FILE, "");
if (!file_exists($TOKEN_FILE)) file_put_contents($TOKEN_FILE, "");

// ============================================
// ROUTING BASE
// ============================================
$request_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method      = $_SERVER["REQUEST_METHOD"];

// ============================================
// 1) LOGIN PAGE (solo GET / o /login.html)
// ============================================
if ($request_uri === "/" || $request_uri === "/login.html") {
    serveFile($LOGIN_PAGE);
}

// ============================================
// 2) LOGIN o REGISTRAZIONE (POST /login)
// ============================================
if ($request_uri === "/login" && $method === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($username === "" || $password === "") {
        respond(["error" => "Missing username or password"], 400);
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

    $newUser = false;

    // L’utente esiste?
    if (isset($users[$username])) {
        if ($users[$username] !== $password_hash) {
            respond(["error" => "Invalid username or password"], 401);
        }
    } else {
        // Registrazione nuovo utente
        file_put_contents($USER_FILE, "$username:$password_hash\n", FILE_APPEND);
        $newUser = true;
    }

    // GENERA TOKEN SICURO
    $token = bin2hex(random_bytes(32));
    $expiry = time() + 3600; // valido 1 ora

    // SALVA TOKEN
    file_put_contents($TOKEN_FILE, "$username:$token:$expiry\n", FILE_APPEND);

    respond([
        "success" => true,
        "newUser" => $newUser,
        "token" => $token,
        "expires" => $expiry
    ]);
}

// ============================================
// 2b) VALIDAZIONE TOKEN (POST /validate-token)
// ============================================
if ($request_uri === "/validate-token" && $method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $token = $input['token'] ?? null;

    $username = validateToken($token);
    if ($username) {
        respond(["username" => $username]);
    } else {
        respond(["error" => "Token non valido"], 401);
    }
}

// ============================================
// 3) TUTTO IL RESTO → ACCESSO PROTETTO
// ============================================
$token = $_GET["token"] ?? ($_SERVER["HTTP_AUTHORIZATION"] ?? null);

// Gestione header Authorization: Bearer xxx
if ($token && str_starts_with($token, "Bearer ")) {
    $token = substr($token, 7);
}

// Validazione token
$username = validateToken($token);
if (!$username) {
    respond(["error" => "Unauthorized"], 401);
}

// Percorso completo del file richiesto
$file = realpath($PUBLIC_DIR . $request_uri);

// Verifica che il file sia dentro /public (no accesso a file esterni)
if ($file === false || !str_starts_with($file, realpath($PUBLIC_DIR))) {
    respond(["error" => "Forbidden"], 403);
}

// Serve il file se esiste
if (file_exists($file) && is_file($file)) {
    serveFile($file);
}

// Se non esiste, torna 404
respond(["error" => "File not found"], 404);

// ============================================
// FUNZIONI
// ============================================

function validateToken($token) {
    global $TOKEN_FILE;

    if (!$token) return false;

    $lines = file($TOKEN_FILE, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        if (!str_contains($line, ":")) continue;

        list($user, $tok, $expiry) = explode(":", $line);

        if ($tok === $token && $expiry >= time()) {
            return $user; // token valido
        }
    }
    return false; // token non valido
}

function serveFile($file) {
    $mime = mime_content_type($file) ?: "application/octet-stream";

    header("Content-Type: $mime");
    readfile($file);
    exit;
}

function respond($data, $code = 200) {
    header("Content-Type: application/json");
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
