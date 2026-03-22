<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$idIscritto = intval($data['id_iscritto'] ?? 0);
$dataPresenza = trim($data['data'] ?? '');
$oraIngresso = trim($data['ora_ingresso'] ?? '');
$oraUscita = trim($data['ora_uscita'] ?? '');

if (!$idIscritto || !$dataPresenza || !$oraIngresso) {
    echo json_encode(['success' => false, 'message' => 'Dati mancanti (id_iscritto, data, ora_ingresso obbligatori)']);
    exit;
}

// Valida formato data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataPresenza)) {
    echo json_encode(['success' => false, 'message' => 'Formato data non valido (YYYY-MM-DD)']);
    exit;
}
// Valida ora ingresso
if (!preg_match('/^\d{2}:\d{2}$/', $oraIngresso)) {
    echo json_encode(['success' => false, 'message' => 'Formato ora ingresso non valido (HH:MM)']);
    exit;
}

$ingresso = $dataPresenza . ' ' . $oraIngresso . ':00';
$uscita = null;
if ($oraUscita !== '' && preg_match('/^\d{2}:\d{2}$/', $oraUscita)) {
    $uscita = $dataPresenza . ' ' . $oraUscita . ':00';
    // Controlla che uscita > ingresso
    if (strtotime($uscita) <= strtotime($ingresso)) {
        echo json_encode(['success' => false, 'message' => "L'ora di uscita deve essere successiva all'ingresso"]);
        exit;
    }
}

require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione DB fallita']);
    exit;
}

// Verifica che l'iscritto esista
$stmtCheck = $conn->prepare("SELECT id FROM iscritto WHERE id = ?");
$stmtCheck->bind_param("i", $idIscritto);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Iscritto non trovato']);
    $stmtCheck->close(); $conn->close(); exit;
}
$stmtCheck->close();

if ($uscita) {
    $stmt = $conn->prepare("INSERT INTO presenza (ID_Iscritto, Ingresso, Uscita) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $idIscritto, $ingresso, $uscita);
} else {
    $stmt = $conn->prepare("INSERT INTO presenza (ID_Iscritto, Ingresso) VALUES (?, ?)");
    $stmt->bind_param("is", $idIscritto, $ingresso);
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    $conn->close(); exit;
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore inserimento: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
