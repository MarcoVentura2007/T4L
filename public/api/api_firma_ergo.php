<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache");

// --- CONTROLLO METODO E AJAX ---
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest'
) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Accesso non autorizzato']);
    exit;
}

// --- CONTROLLO SESSIONE ---
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

require __DIR__ . '/../../data/db_connection.php';

// --- CONTROLLO RUOLO ---
$connAccount = getDbConnection('time4all');
if ($connAccount->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Connessione DB account fallita']);
    exit;
}

$stmtClasse = $connAccount->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if (!$stmtClasse) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Errore controllo permessi']);
    $connAccount->close();
    exit;
}
$stmtClasse->bind_param("s", $_SESSION['username']);
$stmtClasse->execute();
$stmtClasse->bind_result($userClasse);
if (!$stmtClasse->fetch()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Utente non trovato']);
    $stmtClasse->close();
    $connAccount->close();
    exit;
}

// Solo Contabile e Amministratore
if ($userClasse !== 'Amministratore' && $userClasse !== 'Contabile') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accesso negato: permessi insufficienti']);
    $stmtClasse->close();
    $connAccount->close();
    exit;
}
$stmtClasse->close();
$connAccount->close();
// --- FINE CONTROLLO RUOLO ---

// --- LETTURA DATI JSON ---
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dati non validi o mancanti']);
    exit;
}

// --- VALIDAZIONE CAMPI OBBLIGATORI + VERIFICA FACEID ---
if (
    !isset($input['id_iscritto']) ||
    !isset($input['ora_ingresso']) ||
    !isset($input['ora_uscita']) ||
    !isset($input['face_verified']) ||
    $input['face_verified'] !== true
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dati mancanti o verifica FaceID non completata']);
    exit;
}

// --- ESTRAZIONE ---
$id_iscritto = intval($input['id_iscritto']);
$timeIn      = $input['ora_ingresso']; // H:i
$timeOut     = $input['ora_uscita'];   // H:i
$check_firma = 1; // FaceID verificato lato server → sempre 1

// Validazione formato HH:MM
if (
    !preg_match('/^\d{2}:\d{2}$/', $timeIn) ||
    !preg_match('/^\d{2}:\d{2}$/', $timeOut)
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Formato orario non valido (atteso HH:MM)']);
    exit;
}

if ($id_iscritto <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID iscritto non valido']);
    exit;
}

// --- CONNESSIONE DB ERGO ---
$conn = getDbConnection('time4allergo');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Connessione DB ergo fallita']);
    exit;
}

// --- COSTRUZIONE DATETIME (conforme al tipo datetime del DB) ---
$oggi     = date('Y-m-d');
$ingresso = $oggi . ' ' . $timeIn . ':00';  // → "2026-04-24 08:30:00"
$uscita   = $oggi . ' ' . $timeOut . ':00'; // → "2026-04-24 17:00:00"

// --- INSERT IN presenza ---
// Colonne: Ingresso datetime, Uscita datetime, Check_firma tinyint(1), ID_Iscritto int(11)
// La FK su ID_Iscritto è gestita dal DB con ON DELETE/UPDATE CASCADE
$stmt = $conn->prepare("INSERT INTO presenza (Ingresso, Uscita, Check_firma, ID_Iscritto) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Errore prepare: ' . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("ssii", $ingresso, $uscita, $check_firma, $id_iscritto);

if ($stmt->execute()) {
    echo json_encode([
        'success'     => true,
        'message'     => 'Presenza firmata con successo',
        'id_iscritto' => $id_iscritto,
        'ingresso'    => $ingresso,
        'uscita'      => $uscita,
        'check_firma' => $check_firma
    ]);
} else {
    // errno 1452 = FK violation (ID_Iscritto non esiste in iscritto)
    $errno = $stmt->errno;
    $error = $stmt->error;
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $errno === 1452
            ? 'Iscritto non trovato (ID non valido)'
            : 'Errore inserimento: ' . $error
    ]);
}

$stmt->close();
$conn->close();
exit;
?>