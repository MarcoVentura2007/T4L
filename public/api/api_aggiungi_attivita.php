<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no chache");

if(!isset($_SESSION['username'])){
    echo json_encode([
        "success"=>false,
        "message"=>"Sessione non valida"
    ]);
    exit;
}

// --- BLOCCO ACCESSO DIRETTO ---
// Permetti solo richieste POST AJAX
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
$nome = trim($data['nome']);
$descrizione = trim($data['descrizione']);

// Connessione al DB
$host = "localhost";    
$user = "root";         
$pass = "";             
$db   = "time4all"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Connessione fallita: ' . $conn->connect_error
    ]));
}

// Escape dati
$nome = $conn->real_escape_string($nome);
$descrizione = $conn->real_escape_string($descrizione);

// Inserimento
$sql = "INSERT INTO attivita (Nome, Descrizione)
        VALUES ('$nome', '$descrizione')";

if ($conn->query($sql) === TRUE) {
    echo json_encode([
        'success' => true,
        'message' => 'Attività aggiunta con successo'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Errore: ' . $conn->error
    ]);
}

$conn->close();
?>