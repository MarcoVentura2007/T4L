<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'], $input['ingresso'])) {
    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
    exit;
}

$id       = intval($input['id']);
$ingresso = trim($input['ingresso']);
// Uscita è opzionale: se vuota o assente la settiamo a NULL
$uscita   = (isset($input['uscita']) && trim($input['uscita']) !== '') ? trim($input['uscita']) : null;

if (!$id || !$ingresso) {
    echo json_encode(['success' => false, 'message' => 'ID o ingresso mancante']);
    exit;
}

require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione fallita: ' . $conn->connect_error]);
    exit;
}

$stmt = $conn->prepare("UPDATE presenza SET Ingresso = ?, Uscita = ? WHERE id = ?");
$stmt->bind_param("ssi", $ingresso, $uscita, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Presenza modificata']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
