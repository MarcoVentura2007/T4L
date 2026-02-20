<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no chache");
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

// Prima recupera il nome della fotografia con prepared statement
$stmtSelect = $conn->prepare("SELECT fotografia FROM iscritto WHERE id = ?");
if (!$stmtSelect) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    exit;
}
$stmtSelect->bind_param("i", $id_iscritto);
$stmtSelect->execute();
$resultSelect = $stmtSelect->get_result();


if ($resultSelect && $resultSelect->num_rows > 0) {
    $row = $resultSelect->fetch_assoc();
    $fotografia = $row['fotografia'];
    
    // Se esiste una fotografia, eliminala dal filesystem
    if ($fotografia && !empty($fotografia)) {
        $fotografia = str_replace("\\", "/", $fotografia);
        // Non eliminare l'immagine di default
        if ($fotografia !== "immagini/default-user.png" && $fotografia !== "default-user.png") {
            if (strpos($fotografia, "immagini/") === 0) {
                $filePath = __DIR__ . '/../' . $fotografia;
            } else {
                $filePath = __DIR__ . '/../immagini/' . $fotografia;
            }
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}

// Ora elimina il record dal database con prepared statement
$stmt = $conn->prepare("DELETE FROM iscritto WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    $stmtSelect->close();
    exit;
}
$stmt->bind_param("i", $id_iscritto);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$stmtSelect->close();
$conn->close();

?>
