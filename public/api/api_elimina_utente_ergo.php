<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

// --- BLOCCO ACCESSO DIRETTO ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit;
}
// --- FINE BLOCCO ---

if(!isset($_SESSION['username'])){ 
    echo json_encode(['success'=>false,'message'=>'Non autorizzato']); 
    exit; 
}

// Connessione al DB
$host="localhost"; $user="root"; $pass=""; $db="time4allergo";
$conn = new mysqli($host,$user,$pass,$db); if($conn->connect_error){ echo json_encode(['success'=>false,'message'=>$conn->connect_error]); exit; }

// --- CONTROLLO RUOLO: solo Contabile o Amministratore possono eliminare utenti ---
$stmtClasse = $conn->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if ($stmtClasse) {
    $stmtClasse->bind_param("s", $_SESSION['username']);
    $stmtClasse->execute();
    $stmtClasse->bind_result($userClasse);
    if ($stmtClasse->fetch()) {
        if ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore') {
            echo json_encode(['success' => false, 'message' => 'Accesso negato. Solo Contabile o Amministratore possono eliminare utenti.']);
            $stmtClasse->close();
            $conn->close();
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
        $stmtClasse->close();
        $conn->close();
        exit;
    }
    $stmtClasse->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nel controllo dei permessi']);
    $conn->close();
    exit;
}
// --- FINE CONTROLLO RUOLO ---

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? 0; if(!$id){ echo json_encode(['success'=>false,'message'=>'ID mancante']); exit; }

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


$stmt->bind_param("i",$id);
if($stmt->execute()) {
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
    echo json_encode(['success'=>true]);
}
else echo json_encode(['success'=>false,'message'=>$stmt->error]);

$stmt->close();
$stmtSelect->close();
$conn->close();
