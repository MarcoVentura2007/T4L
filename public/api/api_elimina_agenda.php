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

// Connessione DB per controllo ruolo
require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    die(json_encode(['success'=>false,'message'=>'Connessione fallita']));
}

// --- CONTROLLO RUOLO: solo Contabile o Amministratore possono eliminare agenda ---
$stmtClasse = $conn->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if ($stmtClasse) {
    $stmtClasse->bind_param("s", $_SESSION['username']);
    $stmtClasse->execute();
    $stmtClasse->bind_result($userClasse);
    if ($stmtClasse->fetch()) {
        if ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore') {
            echo json_encode(['success' => false, 'message' => 'Accesso negato. Solo Contabile o Amministratore possono eliminare agenda.']);
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

// Reuse existing connection ($conn already established)

$dataDel = $parts[1];
$oraInizioDel = $parts[2] . ':00';
$oraFineDel = $parts[3] . ':00';

// Delete con prepared statement
$stmt = $conn->prepare("DELETE FROM partecipa WHERE ID_Attivita = ? AND Data = ? AND Ora_Inizio = ? AND Ora_Fine = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param("isss", $attivitaId, $dataDel, $oraInizioDel, $oraFineDel);

if($stmt->execute()){
    echo json_encode(['success'=>true,'message'=>'Agenda eliminata con successo']);
}else{
    echo json_encode(['success'=>false,'message'=>'Errore: '.$stmt->error]);
}

$stmt->close();
$conn->close();

?>
