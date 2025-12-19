<?php
// ============================================
// CONFIGURAZIONE
// ============================================
$DATA_DIR   = __DIR__ . "/data";
$USER_FILE  = $DATA_DIR . "/utenti.txt";
$TOKEN_FILE = $DATA_DIR . "/tokens.txt";
$PUBLIC_DIR = __DIR__ . "/public";
$LOGIN_PAGE = $PUBLIC_DIR . "/login.html";

if (!file_exists($DATA_DIR)) mkdir($DATA_DIR, 0777, true);
if (!file_exists($USER_FILE)) file_put_contents($USER_FILE, "");
if (!file_exists($TOKEN_FILE)) file_put_contents($TOKEN_FILE, "");

// ============================================
// ROUTING BASE
// ============================================
$request_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method      = $_SERVER["REQUEST_METHOD"];

// ============================================
// PAGINA DI LOGIN
// ============================================
if ($request_uri === "/" || $request_uri === "/login.html") {
    serveFile($LOGIN_PAGE);
}

// ============================================
// LOGIN / REGISTRAZIONE
// ============================================
if ($request_uri === "/login" && $method === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($username === "" || $password === "") {
        respond(["error" => "Missing credentials"], 400);
    }

    $users = loadUsers();
    $newUser = false;

    if (isset($users[$username])) {
        if (!password_verify($password, $users[$username])) {
            respond(["error" => "Invalid credentials"], 401);
        }
    } else {
        // registrazione
        $hash = password_hash($password, PASSWORD_DEFAULT);
        file_put_contents($USER_FILE, "$username:$hash\n", FILE_APPEND);
        $newUser = true;
    }

    // invalida eventuali token precedenti
    removeUserTokens($username);

    // genera token
    $token  = bin2hex(random_bytes(32));
    $expiry = time() + 3600;

    file_put_contents($TOKEN_FILE, "$username:$token:$expiry\n", FILE_APPEND);

    respond([
        "success" => true,
        "newUser" => $newUser,
        "token"   => $token,
        "expires" => $expiry
    ]);
}

// ============================================
// LOGOUT
// ============================================
if ($request_uri === "/logout" && $method === "POST") {
    $token = getBearerToken();
    if (!$token) respond(["error" => "Missing token"], 401);

    revokeToken($token);
    respond(["success" => true]);
}

// ============================================
// VALIDAZIONE TOKEN (DEBUG / CLIENT)
// ============================================
if ($request_uri === "/validate-token" && $method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    $token = $input["token"] ?? null;

    $username = validateToken($token);
    if ($username) {
        respond(["username" => $username]);
    } else {
        respond(["error" => "Invalid token"], 401);
    }
}

// ============================================
// ACCESSO PROTETTO
// ============================================
$token = getBearerToken();
$username = validateToken($token);

if (!$username) {
    respond(["error" => "Unauthorized"], 401);
}

// ============================================
// FILE PUBBLICI
// ============================================
$file = realpath($PUBLIC_DIR . $request_uri);

if ($file === false || !str_starts_with($file, realpath($PUBLIC_DIR))) {
    respond(["error" => "Forbidden"], 403);
}

if (file_exists($file) && is_file($file)) {
    serveFile($file);
}

respond(["error" => "Not found"], 404);

// ============================================
// FUNZIONI
// ============================================

function loadUsers() {
    global $USER_FILE;
    $users = [];
    foreach (file($USER_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (str_contains($line, ":")) {
            [$u, $p] = explode(":", $line, 2);
            $users[$u] = $p;
        }
    }
    return $users;
}

function validateToken($token) {
    global $TOKEN_FILE;

    if (!$token) return false;

    $validLines = [];
    $userFound = false;

    foreach (file($TOKEN_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (!str_contains($line, ":")) continue;

        [$user, $tok, $expiry] = explode(":", $line);

        if ($expiry < time()) continue; // scaduto

        if ($tok === $token) {
            $userFound = $user;
        }

        $validLines[] = "$user:$tok:$expiry";
    }

    // pulizia token scaduti
    file_put_contents($TOKEN_FILE, implode("\n", $validLines) . "\n");

    return $userFound;
}

function removeUserTokens($username) {
    global $TOKEN_FILE;
    $lines = file($TOKEN_FILE, FILE_IGNORE_NEW_LINES);
    $out = [];

    foreach ($lines as $line) {
        if (!str_starts_with($line, "$username:")) {
            $out[] = $line;
        }
    }

    file_put_contents($TOKEN_FILE, implode("\n", $out) . "\n");
}

function revokeToken($token) {
    global $TOKEN_FILE;
    $lines = file($TOKEN_FILE, FILE_IGNORE_NEW_LINES);
    $out = [];

    foreach ($lines as $line) {
        if (!str_contains($line, ":")) continue;
        [, $tok] = explode(":", $line, 3);
        if ($tok !== $token) $out[] = $line;
    }

    file_put_contents($TOKEN_FILE, implode("\n", $out) . "\n");
}

function getBearerToken() {
    $header = $_SERVER["HTTP_AUTHORIZATION"] ?? null;
    if ($header && str_starts_with($header, "Bearer ")) {
        return substr($header, 7);
    }
    return null;
}

function serveFile($file) {
    header("Content-Type: " . (mime_content_type($file) ?: "application/octet-stream"));
    readfile($file);
    exit;
}

function respond($data, $code = 200) {
    http_response_code($code);
    header("Content-Type: application/json");
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}