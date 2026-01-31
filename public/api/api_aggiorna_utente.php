<?php
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

// Controllo che ci sia l'id
if(empty($data['id'])){
    echo json_encode(['success' => false, 'message' => 'ID mancante']);
    exit;
}

$id = intval($data['id']); // ID dell'iscritto
$nome = $data['nome'] ?? '';
$cognome = $data['cognome'] ?? '';
$data_nascita = $data['data_nascita'] ?? '';
$codice_fiscale = $data['codice_fiscale'] ?? '';
$contatti = $data['contatti'] ?? '';
$disabilita = $data['disabilita'] ?? '';
$intolleranze = $data['intolleranze'] ?? '';
$prezzo_orario = $data['prezzo_orario'] ?? '';
$note = $data['note'] ?? '';

// Connessione al DB
$host = "localhost";    
$user = "root";         
$pass = "";             
$db   = "time4all"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connessione fallita: ' . $conn->connect_error]));
}

// Costruzione query nello stile che vuoi
$sql = "UPDATE iscritto SET 
        nome='$nome',
        cognome='$cognome',
        data_nascita='$data_nascita',
        codice_fiscale='$codice_fiscale',
        contatti='$contatti',
        disabilita='$disabilita',
        allergie_intolleranze='$intolleranze',
        prezzo_orario='$prezzo_orario',
        note='$note'
        WHERE id=$id";

if($conn->query($sql) === TRUE){
    echo json_encode(['success' => true, 'message' => 'Utente aggiornato']);
}else{
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $conn->error]);
}

$conn->close();
?>
