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

// Recupero ID
$id = intval($data['id']);
if(!$id){
    echo json_encode(['success'=>false, 'message'=>'ID attività non valido']);
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

// Eliminazione
$sql = "DELETE FROM attivita WHERE id=$id";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success'=>true, 'message'=>'Attività eliminata con successo']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Errore: ' . $conn->error]);
}

$conn->close();
?>
