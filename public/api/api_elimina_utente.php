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

// Prima recupera il nome della fotografia
$sqlSelect = "SELECT fotografia FROM iscritto WHERE id = $id_iscritto";
$resultSelect = $conn->query($sqlSelect);

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

// Ora elimina il record dal database
$sql = "DELETE FROM iscritto WHERE id = $id_iscritto";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$conn->close();
?>
