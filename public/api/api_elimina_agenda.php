<?php
session_start();
header('Content-Type: application/json');

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

$data = json_decode(file_get_contents('php://input'), true);

$id = intval($data['id']);
if(!$id){
    echo json_encode(['success'=>false,'message'=>'ID agenda non valido']);
    exit;
}

// Connessione DB
$host = "localhost";
$user = "root";
$pass = "";
$db   = "time4all";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['success'=>false,'message'=>'Connessione fallita']));
}

// Delete
$sql = "DELETE FROM partecipa WHERE id = $id";

if($conn->query($sql)){
    echo json_encode(['success'=>true,'message'=>'Agenda eliminata con successo']);
}else{
    echo json_encode(['success'=>false,'message'=>'Errore: '.$conn->error]);
}

$conn->close();
?>