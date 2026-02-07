<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../config.php';

if(!isset($_SESSION['username'])){
    echo json_encode([
        "success" => false,
        "message" => "Sessione non valida"
    ]);
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "time4all";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    echo json_encode([
        "success" => false,
        "message" => "Errore connessione database"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$nome_utente = isset($data['nome_utente']) ? trim($data['nome_utente']) : "";
$password = isset($data['password']) ? trim($data['password']) : "";
$classe = isset($data['classe']) ? trim($data['classe']) : "";
$codice_univoco = isset($data['codice_univoco']) ? trim($data['codice_univoco']) : "";

if($nome_utente === "" || $classe === "" || $codice_univoco === ""){
    echo json_encode([
        "success" => false,
        "message" => "Compila tutti i campi obbligatori"
    ]);
    exit;
}

// Se password è fornita, fai l'hash, altrimenti non modificarla
if($password !== ""){
    // Usa PEPPER come in api_login
    $password_hash = password_hash($password . PEPPER, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare(
        "UPDATE Account SET password = ?, classe = ?, codice_univoco = ? WHERE nome_utente = ?"
    );
    $stmt->bind_param("ssss", $password_hash, $classe, $codice_univoco, $nome_utente);
} else {
    $stmt = $conn->prepare(
        "UPDATE Account SET classe = ?, codice_univoco = ? WHERE nome_utente = ?"
    );
    $stmt->bind_param("sss", $classe, $codice_univoco, $nome_utente);
}

if($stmt->execute()){
    if($stmt->affected_rows > 0){
        echo json_encode([
            "success" => true,
            "message" => "Account modificato con successo"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Nessun account trovato"
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Errore nell'aggiornamento: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();

?>
