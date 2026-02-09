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

// Recupero dati
$id            = $data['id'] ?? '';
$dataAgenda    = $data['data'] ?? '';
$oraInizio     = $data['ora_inizio'] ?? '';
$oraFine       = $data['ora_fine'] ?? '';
$idAttivita    = intval($data['id_attivita']);
$educatori     = $data['educatori'] ?? [];
$ragazzi       = $data['ragazzi'] ?? [];

// Controlli
if(!$id || !$dataAgenda || !$oraInizio || !$oraFine || !$idAttivita || empty($educatori) || empty($ragazzi)){
    echo json_encode(['success'=>false, 'message'=>'Dati mancanti o non validi']);
    exit;
}

// Parse the composite key: attivita_id_data_ora_inizio_ora_fine
$parts = explode('_', $id);
if(count($parts) != 4){
    echo json_encode(['success'=>false,'message'=>'ID non valido']);
    exit;
}
$oldAttivitaId = intval($parts[0]);
$oldData = $parts[1];
$oldOraInizio = $parts[2] . ':00';
$oldOraFine = $parts[3] . ':00';

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
$oldData     = $conn->real_escape_string($oldData);
$oldOraInizio= $conn->real_escape_string($oldOraInizio);
$oldOraFine  = $conn->real_escape_string($oldOraFine);

// Start transaction
$conn->begin_transaction();

try {
    // Delete old records
    $deleteSql = "DELETE FROM partecipa WHERE ID_Attivita = $oldAttivitaId AND Data = '$oldData' AND Ora_Inizio = '$oldOraInizio' AND Ora_Fine = '$oldOraFine'";
    if(!$conn->query($deleteSql)){
        throw new Exception('Errore nella cancellazione: ' . $conn->error);
    }

    // Insert new records
    foreach($educatori as $educatoreId){
        foreach($ragazzi as $ragazzoId){
            $insertSql = "INSERT INTO partecipa (ID_Attivita, Data, Ora_Inizio, Ora_Fine, ID_Educatore, ID_Ragazzo, presenza_effettiva) VALUES ($idAttivita, '$dataAgenda', '$oraInizio', '$oraFine', $educatoreId, $ragazzoId, 0)";
            if(!$conn->query($insertSql)){
                throw new Exception('Errore nell\'inserimento: ' . $conn->error);
            }
        }
    }

    $conn->commit();
    echo json_encode(['success'=>true,'message'=>'Agenda modificata con successo']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

$conn->close();
?>