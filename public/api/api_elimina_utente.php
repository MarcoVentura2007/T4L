<?php
// --- BLOCCO ACCESSO DIRETTO ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit;
}

// Recupera JSON inviato
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id_iscritto'])) {
    echo json_encode(['success' => false, 'message' => 'ID mancante']);
    exit;
}

$id_iscritto = intval($data['id_iscritto']);

// Connessione al DB
$host = "localhost";
$user = "root";
$pass = "";
$db   = "time4all";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione fallita']);
    exit;
}

// Query per eliminare l'utente
$sql = "DELETE FROM iscritto WHERE id = $id_iscritto";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$conn->close();