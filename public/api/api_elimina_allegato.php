<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "time4all";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    echo json_encode([
        "success" => false,
        "message" => "Errore connessione database"
    ]);
    exit;
}

// Verifica autenticazione
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Verifica metodo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non valido']);
    exit;
}

// --- CONTROLLO RUOLO: solo Contabile o Amministratore possono eliminare allegati ---
$stmtClasse = $conn->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if ($stmtClasse) {
    $stmtClasse->bind_param("s", $_SESSION['username']);
    $stmtClasse->execute();
    $stmtClasse->bind_result($userClasse);
    if ($stmtClasse->fetch()) {
        if ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore') {
            echo json_encode(['success' => false, 'message' => 'Accesso negato. Solo Contabile o Amministratore possono eliminare allegati.']);
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

// Recupera dati JSON
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID allegato non valido']);
    exit;
}

// Recupera percorso file prima di eliminare
$stmt = $conn->prepare("SELECT file FROM allegati WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Allegato non trovato']);
    $stmt->close();
    $conn->close();
    exit;
}

$row = $result->fetch_assoc();
$percorso = $row['file'];
$stmt->close();

// Elimina dal database
$stmt = $conn->prepare("DELETE FROM allegati WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Elimina file fisico
    $filePath = __DIR__ . '/../' . $percorso;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    echo json_encode(['success' => true, 'message' => 'Allegato eliminato con successo']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore eliminazione: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
