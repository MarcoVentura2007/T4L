<?php
header('Content-Type: application/json');

// Connessione al DB time4allergo
$host = "localhost";
$user = "root";
$pass = "";
$db   = "time4allergo";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    echo json_encode(['success' => false, 'message' => 'Connessione fallita: ' . $conn->connect_error]);
    exit;
}

// Leggi input JSON
$input = json_decode(file_get_contents('php://input'), true);

if(!isset($input['id']) || !isset($input['ingresso']) || !isset($input['uscita'])){
    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
    exit;
}

$id = intval($input['id']);
$ingresso = $conn->real_escape_string($input['ingresso']);
$uscita = $conn->real_escape_string($input['uscita']);

// Validazione formato datetime
if(!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $ingresso) || 
   !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $uscita)){
    echo json_encode(['success' => false, 'message' => 'Formato data non valido. Usa YYYY-MM-DD HH:MM:SS']);
    exit;
}

// Update query
$stmt = $conn->prepare("UPDATE presenza SET Ingresso = ?, Uscita = ? WHERE id = ?");
$stmt->bind_param("ssi", $ingresso, $uscita, $id);

if($stmt->execute()){
    if($stmt->affected_rows > 0){
        echo json_encode(['success' => true, 'message' => 'Presenza aggiornata con successo']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Nessuna modifica effettuata']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
