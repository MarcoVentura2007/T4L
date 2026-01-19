<?php
// ============================================
// CONFIGURAZIONE
// ============================================
$DATA_DIR   = __DIR__ . "/data";
$USER_FILE  = $DATA_DIR . "/utenti.txt";
$PUBLIC_DIR = __DIR__ . "/public";
$LOGIN_PAGE = $PUBLIC_DIR . "/login.html";

// Crea cartelle e file se non esistono
if (!file_exists($DATA_DIR)) mkdir($DATA_DIR, 0777, true);
if (!file_exists($USER_FILE)) file_put_contents($USER_FILE, "");

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


    respond([
        "success" => true,
        "newUser" => $newUser,
        "token" => $token,
        "expires" => $expiry
    ]);
}


// ============================================
// 3) TUTTO IL RESTO → ACCESSO PROTETTO
// ============================================


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
