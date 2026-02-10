<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no chache");

// Controllo login
if(!isset($_SESSION['username'])){
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Legge dati JSON inviati
$input = json_decode(file_get_contents('php://input'), true);
if(!$input || !isset($input['id'])){
    echo json_encode(['success' => false, 'message' => 'ID mancante']);
    exit;
}

$id = intval($input['id']);

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

// Query elimina
$stmt = $conn->prepare("DELETE FROM presenza WHERE id = ?");
$stmt->bind_param("i", $id);

if($stmt->execute()){
    echo json_encode(['success' => true, 'message' => 'Presenza eliminata']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore: '.$stmt->error]);
}

$stmt->close();
$conn->close();
?>