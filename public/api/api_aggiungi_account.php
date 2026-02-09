<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no chache");
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

if($nome_utente === "" || $password === "" || $classe === "" || $codice_univoco === ""){
    echo json_encode([
        "success" => false,
        "message" => "Compila tutti i campi"
    ]);
    exit;
}

// Controlla se username esiste già
$stmtCheck = $conn->prepare("SELECT nome_utente FROM Account WHERE nome_utente = ?");
$stmtCheck->bind_param("s", $nome_utente);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if($resCheck->num_rows > 0){
    echo json_encode([
        "success" => false,
        "message" => "Username già esistente"
    ]);
    exit;
}

// Hash password (usa PEPPER come in api_login)
$password_hash = password_hash($password . PEPPER, PASSWORD_BCRYPT);

// Inserisci account
$stmtInsert = $conn->prepare(
    "INSERT INTO Account (nome_utente, password, classe, codice_univoco) VALUES (?, ?, ?, ?)"
);
$stmtInsert->bind_param("ssss", $nome_utente, $password_hash, $classe, $codice_univoco);

if($stmtInsert->execute()){
    echo json_encode([
        "success" => true,
        "message" => "Account aggiunto con successo"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Errore nell'inserimento: " . $stmtInsert->error
    ]);
}

$stmtInsert->close();
$stmtCheck->close();
$conn->close();

?>
