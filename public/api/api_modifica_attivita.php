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

// Connessione al DB
$host = "localhost";    
$user = "root";         
$pass = "";             
$db   = "time4all"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connessione fallita: ' . $conn->connect_error]));
}

// Escape dati
$nome = $conn->real_escape_string($nome);
$descrizione = $conn->real_escape_string($descrizione);

// Aggiornamento
$sql = "UPDATE attivita SET Nome='$nome', Descrizione='$descrizione' WHERE id=$id";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success'=>true, 'message'=>'Attività modificata con successo']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Errore: ' . $conn->error]);
}

$conn->close();
?>
