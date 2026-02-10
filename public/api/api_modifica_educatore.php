<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no chache");
require __DIR__ . '/../config.php';

if(!isset($_SESSION['username'])){
    echo json_encode(["success" => false, "message" => "Sessione non valida"]);
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "time4all";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    echo json_encode(["success" => false, "message" => "Errore connessione database"]);
    exit;
}


$data = json_decode(file_get_contents("php://input"), true);

$id = isset($data['id']) ? intval($data['id']) : 0;
$nome = isset($data['nome']) ? trim($data['nome']) : '';
$cognome = isset($data['cognome']) ? trim($data['cognome']) : '';
$data_nascita = isset($data['data_nascita']) ? trim($data['data_nascita']) : '';
$codice_fiscale = isset($data['codice_fiscale']) ? trim($data['codice_fiscale']) : '';
$telefono = isset($data['telefono']) ? trim($data['telefono']) : '';
$mail = isset($data['mail']) ? trim($data['mail']) : '';

if($id <= 0 || $nome === '' || $cognome === ''){
    echo json_encode(["success" => false, "message" => "Dati non validi"]);
    exit;
}

// Aggiorna la tabella `educatore` con i campi corretti
$stmt = $conn->prepare("UPDATE educatore SET nome = ?, cognome = ?, codice_fiscale = ?, data_nascita = ?, telefono = ?, mail = ? WHERE id = ?");
if(!$stmt){
    echo json_encode(["success" => false, "message" => "Errore prepare: " . $conn->error]);
    $conn->close();
    exit;
}
$stmt->bind_param("ssssssi", $nome, $cognome, $codice_fiscale, $data_nascita, $telefono, $mail, $id);

if($stmt->execute()){
    echo json_encode(["success" => true, "message" => "Educatore modificato"]);
} else {
    echo json_encode(["success" => false, "message" => "Errore: " . $stmt->error]);
}

$stmt->close();
$conn->close();

?>
