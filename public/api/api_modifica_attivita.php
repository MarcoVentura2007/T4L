<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no chache");

if(!isset($_SESSION['username'])){
    echo json_encode(["success"=>false, "message"=>"Sessione non valida"]);
    exit;
}

// --- BLOCCO ACCESSO DIRETTO ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit;
}
// --- FINE BLOCCO ---

// Connessione al DB per controllo ruolo
$host = "localhost";    
$user = "root";         
$pass = "";             
$db   = "time4all"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connessione fallita: ' . $conn->connect_error]));
}

// --- CONTROLLO RUOLO: solo Contabile o Amministratore possono modificare attività ---
$stmtClasse = $conn->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if ($stmtClasse) {
    $stmtClasse->bind_param("s", $_SESSION['username']);
    $stmtClasse->execute();
    $stmtClasse->bind_result($userClasse);
    if ($stmtClasse->fetch()) {
        if ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore') {
            echo json_encode(['success' => false, 'message' => 'Accesso negato. Solo Contabile o Amministratore possono modificare attività.']);
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

// Legge il JSON inviato dal fetch
$data = json_decode(file_get_contents('php://input'), true);

// Recupero dati
$id = intval($data['id']);
$nome = trim($data['nome']);
$descrizione = trim($data['descrizione']);

// Controllo dati essenziali
if(!$id || $nome === '' || $descrizione === ''){
    echo json_encode(['success'=>false, 'message'=>'Dati mancanti o non validi']);
    exit;
}

// Reuse existing connection ($conn already established)

// Aggiornamento con prepared statement
$stmt = $conn->prepare("UPDATE attivita SET Nome = ?, Descrizione = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssi", $nome, $descrizione, $id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'message'=>'Attività modificata con successo']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Errore: ' . $stmt->error]);
}

$stmt->close();
$conn->close();

?>
