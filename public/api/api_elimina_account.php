<?php

session_start();
header('Content-Type: application/json');
header("Cache-Control: no chache");

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

if($nome_utente === ""){
    echo json_encode([
        "success" => false,
        "message" => "Nome utente non valido"
    ]);
    exit;
}

// Impedisci di eliminare l'account loggato
if($nome_utente === $_SESSION['username']){
    echo json_encode([
        "success" => false,
        "message" => "Non puoi eliminare il tuo account"
    ]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM Account WHERE nome_utente = ?");
$stmt->bind_param("s", $nome_utente);

if($stmt->execute()){
    if($stmt->affected_rows > 0){
        echo json_encode([
            "success" => true,
            "message" => "Account eliminato con successo"
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
        "message" => "Errore nell'eliminazione: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();

?>
