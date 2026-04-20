<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Connessione al DB
require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4allergo');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => $conn->connect_error]);
    exit;
}

// --- CONTROLLO RUOLO: solo Contabile o Amministratore possono eliminare utenti ---
$connAccount = getDbConnection('time4all');
if ($connAccount->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione DB account fallita']);
    exit;
}
$stmtClasse = $connAccount->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if ($stmtClasse) {
    $stmtClasse->bind_param("s", $_SESSION['username']);
    $stmtClasse->execute();
    $stmtClasse->bind_result($userClasse);
    if ($stmtClasse->fetch()) {
        if ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore') {
            echo json_encode(['success' => false, 'message' => 'Accesso negato. Solo Contabile o Amministratore possono eliminare utenti.']);
            $stmtClasse->close();
            $connAccount->close();
            $conn->close();
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
        $stmtClasse->close();
        $connAccount->close();
        $conn->close();
        exit;
    }
    $stmtClasse->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nel controllo dei permessi']);
    $connAccount->close();
    $conn->close();
    exit;
}
$connAccount->close();
// --- FINE CONTROLLO RUOLO ---

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? 0;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID mancante']);
    exit;
}

// Reuse existing connection ($conn already established)

// Prima recupera il nome della fotografia
$stmtSelect = $conn->prepare("SELECT fotografia FROM iscritto WHERE id = ?");
if (!$stmtSelect) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    exit;
}
$stmtSelect->bind_param("i", $id);
$stmtSelect->execute();
$resultSelect = $stmtSelect->get_result();
$fotografia = null;
if ($resultSelect && $resultSelect->num_rows > 0) {
    $row = $resultSelect->fetch_assoc();
    $fotografia = $row['fotografia'];
}

$stmt = $conn->prepare("DELETE FROM iscritto WHERE id=?");

$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    // Se esiste una fotografia, eliminala dal filesystem
    if ($fotografia && !empty($fotografia)) {
        $fotografia = str_replace("\\", "/", $fotografia);
        // Non eliminare l'immagine di default
        if ($fotografia !== "immagini/default-user.png" && $fotografia !== "default-user.png") {
            if (strpos($fotografia, "public/immagini/") === 0) {
                // Percorso con "public/" - rimuovi il prefisso
                $filePath = __DIR__ . '/../' . str_replace("public/", "", $fotografia);
            } else {
                // Percorso senza "public/"
                $filePath = __DIR__ . '/../' . $fotografia;
            }
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
    echo json_encode(['success' => true]);
} else echo json_encode(['success' => false, 'message' => $stmt->error]);

$stmt->close();
$stmtSelect->close();
$conn->close();
