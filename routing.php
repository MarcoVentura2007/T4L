<?php
// ============================================
// CONFIGURAZIONE
// ============================================
$PUBLIC_DIR = __DIR__ . "/public";
$LOGIN_PAGE = $PUBLIC_DIR . "/login.html";

// ============================================
// OTTIENI LA URI DELLA RICHIESTA
// ============================================
$request_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Normalizza le slash per Windows
$request_uri = str_replace('\\', '/', $request_uri);

// ============================================
// SERVE FILE STATICI SE ESISTONO
// ============================================
$file_path = realpath($PUBLIC_DIR . $request_uri);

// Controlla che il file esista e sia dentro la cartella public
if ($file_path && is_file($file_path) && str_starts_with($file_path, realpath($PUBLIC_DIR))) {
    // Determina il tipo MIME in base all'estensione
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
// REDIRECT FORZATO A LOGIN.HTML
// ============================================
// Evita loop: se siamo già su login.html, servilo direttamente
if ($request_uri !== "/login.html") {
    header("Location: /login.html");
    exit;
}

// ============================================
// SERVE LOGIN.HTML
// ============================================
if (!file_exists($LOGIN_PAGE)) {
    header("HTTP/1.1 404 Not Found");
    echo "❌ File non trovato: $LOGIN_PAGE";
    exit;
}

header("Content-Type: text/html");
readfile($LOGIN_PAGE);
exit;
