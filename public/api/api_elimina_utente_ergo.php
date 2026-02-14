<?php
session_start(); header('Content-Type: application/json');
if(!isset($_SESSION['username'])){ echo json_encode(['success'=>false,'message'=>'Non autorizzato']); exit; }

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? 0; if(!$id){ echo json_encode(['success'=>false,'message'=>'ID mancante']); exit; }

$host="localhost"; $user="root"; $pass=""; $db="time4allergo";
$conn = new mysqli($host,$user,$pass,$db); if($conn->connect_error){ echo json_encode(['success'=>false,'message'=>$conn->connect_error]); exit; }

$stmt = $conn->prepare("DELETE FROM iscritto WHERE id=?");
$stmt->bind_param("i",$id);
if($stmt->execute()) echo json_encode(['success'=>true]);
else echo json_encode(['success'=>false,'message'=>$stmt->error]);

$stmt->close(); $conn->close();
