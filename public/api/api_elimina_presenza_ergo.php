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

if(!isset($input['id'])){
    echo json_encode(['success' => false, 'message' => 'ID presenza mancante']);
    exit;
}

$id = intval($input['id']);

// Delete query
$stmt = $conn->prepare("DELETE FROM presenza WHERE id = ?");
$stmt->bind_param("i", $id);

if($stmt->execute()){
    if($stmt->affected_rows > 0){
        echo json_encode(['success' => true, 'message' => 'Presenza eliminata con successo']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Presenza non trovata']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
