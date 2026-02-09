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

if($id <= 0){
    echo json_encode(["success" => false, "message" => "ID non valido"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM educatore WHERE id = ?");
$stmt->bind_param("i", $id);

if($stmt->execute()){
    echo json_encode(["success" => true, "message" => "Educatore eliminato"]);
} else {
    echo json_encode(["success" => false, "message" => "Errore: " . $stmt->error]);
}

$stmt->close();
$conn->close();

?>
