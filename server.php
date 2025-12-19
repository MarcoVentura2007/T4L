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
// REQUEST
// ============================================
$request_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method      = $_SERVER["REQUEST_METHOD"];

// ============================================
// 0 - FILE STATICI CHE DEVONO ESSERE ACCESSIBILI
// ============================================
$static_files = ["/sw.js", "/manifest.json", "/style.css"];

if (in_array($request_uri, $static_files)) {
    $file = realpath($PUBLIC_DIR . $request_uri);
    serveFile($file);
}

// ============================================
// 1 - LOGIN PAGE (GET /login.html o /)
// ============================================
if ($request_uri === "/" || $request_uri === "/login.html") {
    serveFile($LOGIN_PAGE);
}

// ============================================
// 2 - LOGIN/REGISTRAZIONE
// ============================================
if ($request_uri === "/login" && $method === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($username === "" || $password === "") {
        respond(["error" => "Missing username or password"], 400);
    }

    $password_hash = hash("sha256", $password);

    $users = [];
    foreach (file($USER_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (str_contains($line, ":")) {
            list($u, $p) = explode(":", $line, 2);
            $users[$u] = $p;
        }
    }

    $newUser = false;

    if (isset($users[$username])) {
        if ($users[$username] !== $password_hash) {
            respond(["error" => "Invalid username or password"], 401);
        }
    } else {
        file_put_contents($USER_FILE, "$username:$password_hash\n", FILE_APPEND);
        $newUser = true;
    }

    $token = bin2hex(random_bytes(32));
    $expiry = time() + 3600;

    file_put_contents($TOKEN_FILE, "$username:$token:$expiry\n", FILE_APPEND);

    respond([
        "success" => true,
        "newUser" => $newUser,
        "token" => $token,
        "username" => $username,
        "expires" => $expiry
    ]);
}

// ============================================
// 3 - VALIDAZIONE TOKEN (POST /validate-token)
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
// 4 - PROTEZIONE (richiede token)
// ============================================
$token = $_GET["token"] ?? ($_SERVER["HTTP_AUTHORIZATION"] ?? null);

if ($token && str_starts_with($token, "Bearer ")) {
    $token = substr($token, 7);
}

$username = validateToken($token);
if (!$username) {
    respond(["error" => "Unauthorized"], 401);
}

// Serve file richiesto
$file = realpath($PUBLIC_DIR . $request_uri);

if ($file === false || !str_starts_with($file, realpath($PUBLIC_DIR))) {
    respond(["error" => "Forbidden"], 403);
}

if (file_exists($file) && is_file($file)) {
    serveFile($file);
}

respond(["error" => "File not found"], 404);


// ============================================
// FUNZIONI
// ============================================
function validateToken($token) {
    global $TOKEN_FILE;

    if (!$token) return false;

    foreach (file($TOKEN_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (!str_contains($line, ":")) continue;

        list($user, $tok, $expiry) = explode(":", $line);

        if ($tok === $token && $expiry >= time()) {
            return $user;
        }
    }

    return false;
}

function serveFile($file) {
    if (!file_exists($file)) return false;

    $ext = pathinfo($file, PATHINFO_EXTENSION);

    $mimes = [
        "html" => "text/html",
        "css"  => "text/css",
        "js"   => "application/javascript",
        "json" => "application/json",
        "png"  => "image/png",
        "jpg"  => "image/jpeg",
        "jpeg" => "image/jpeg",
        "ico"  => "image/x-icon"
    ];

    header("Content-Type: " . ($mimes[$ext] ?? "application/octet-stream"));
    readfile($file);
    exit;
}

function respond($data, $code = 200) {
    header("Content-Type: application/json");
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
