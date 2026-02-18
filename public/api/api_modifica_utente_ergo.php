<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

// Controllo login
if(!isset($_SESSION['username'])){
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Legge dati JSON inviati
$input = json_decode(file_get_contents('php://input'), true);

if(!$input || !isset($input['id'])){
    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "time4allergo";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    echo json_encode(['success' => false, 'message' => 'Connessione fallita']);
    exit;
}

$id = intval($input['id']);

// Campi validi per la tabella iscritto (senza intolleranze che non esiste nel DB)
$fields = [
    'nome' => $input['nome'] ?? '',
    'cognome' => $input['cognome'] ?? '',
    'data_nascita' => $input['data_nascita'] ?? '',
    'codice_fiscale' => $input['codice_fiscale'] ?? '',
    'email' => $input['email'] ?? '',
    'telefono' => $input['telefono'] ?? '',
    'disabilita' => $input['disabilita'] ?? '',
    'note' => $input['note'] ?? '',
    'prezzo_orario' => floatval($input['prezzo_orario'] ?? 0)
];


$sql = "UPDATE iscritto SET 
    Nome = ?, 
    Cognome = ?, 
    Data_nascita = ?, 
    Codice_fiscale = ?, 
    Email = ?, 
    Telefono = ?, 
    Disabilita = ?, 
    Note = ?, 
    Stipendio_Orario = ? 
WHERE id = ?";


$params = [
    $fields['nome'],
    $fields['cognome'],
    $fields['data_nascita'],
    $fields['codice_fiscale'],
    $fields['email'],
    $fields['telefono'],
    $fields['disabilita'],
    $fields['note'],
    $fields['prezzo_orario'],
    $id
];
$types = "ssssssssdi";


$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if($stmt->execute()){
    echo json_encode(['success' => true, 'message' => 'Utente modificato con successo']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
