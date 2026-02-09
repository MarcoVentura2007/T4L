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

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? '';
if(!$id){
    echo json_encode(['success'=>false,'message'=>'ID agenda non valido']);
    exit;
}

// Parse the composite key: attivita_id_data_ora_inizio_ora_fine
$parts = explode('_', $id);
if(count($parts) != 4){
    echo json_encode(['success'=>false,'message'=>'ID non valido']);
    exit;
}
$attivitaId = intval($parts[0]);

// Connessione DB
$host = "localhost";
$user = "root";
$pass = "";
$db   = "time4all";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['success'=>false,'message'=>'Connessione fallita']));
}

$dataDel = $conn->real_escape_string($parts[1]);
$oraInizioDel = $conn->real_escape_string($parts[2]) . ':00';
$oraFineDel = $conn->real_escape_string($parts[3]) . ':00';

// Delete
$sql = "DELETE FROM partecipa WHERE ID_Attivita = $attivitaId AND Data = '$dataDel' AND Ora_Inizio = '$oraInizioDel' AND Ora_Fine = '$oraFineDel'";

if($conn->query($sql)){
    echo json_encode(['success'=>true,'message'=>'Agenda eliminata con successo']);
}else{
    echo json_encode(['success'=>false,'message'=>'Errore: '.$conn->error]);
}

$conn->close();
?>