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

// Recupero dati
$id            = intval($data['id']);
$dataAgenda    = $data['data'] ?? '';
$oraInizio     = $data['ora_inizio'] ?? '';
$oraFine       = $data['ora_fine'] ?? '';
$idAttivita    = intval($data['id_attivita']);
$idEducatore   = intval($data['id_educatore']);

// Controlli
if(!$id || !$dataAgenda || !$oraInizio || !$oraFine || !$idAttivita || !$idEducatore){
    echo json_encode(['success'=>false, 'message'=>'Dati mancanti o non validi']);
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

// Escape
$dataAgenda  = $conn->real_escape_string($dataAgenda);
$oraInizio   = $conn->real_escape_string($oraInizio);
$oraFine     = $conn->real_escape_string($oraFine);

// Update
$sql = "
UPDATE partecipa 
SET 
    Data = '$dataAgenda',
    Ora_Inizio = '$oraInizio',
    Ora_Fine = '$oraFine',
    ID_Attivita = $idAttivita,
    ID_Educatore = $idEducatore
WHERE id = $id
";

if($conn->query($sql)){
    echo json_encode(['success'=>true,'message'=>'Agenda modificata con successo']);
}else{
    echo json_encode(['success'=>false,'message'=>'Errore: '.$conn->error]);
}

$conn->close();
?>