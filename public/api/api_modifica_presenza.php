<?php
session_start();
header('Content-Type: application/json');

// Controllo login
if(!isset($_SESSION['username'])){
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Legge dati JSON inviati
$input = json_decode(file_get_contents('php://input'), true);
if(!$input || !isset($input['id'], $input['ingresso'], $input['uscita'])){
    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
    exit;
}

$id = intval($input['id']);
$ingresso = $input['ingresso'];
$uscita = $input['uscita'];

// Connessione DB
$host = "localhost";
$user = "root";
$pass = "";
$db   = "time4all";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione fallita: '.$conn->connect_error]);
    exit;
}

// Query modifica
$stmt = $conn->prepare("UPDATE presenza SET ingresso = ?, uscita = ? WHERE id = ?");
$stmt->bind_param("ssi", $ingresso, $uscita, $id);

if($stmt->execute()){
    echo json_encode(['success' => true, 'message' => 'Presenza modificata']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore: '.$stmt->error]);
}

$stmt->close();
$conn->close();