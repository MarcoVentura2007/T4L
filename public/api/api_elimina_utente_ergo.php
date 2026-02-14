<?php
session_start(); header('Content-Type: application/json');
if(!isset($_SESSION['username'])){ echo json_encode(['success'=>false,'message'=>'Non autorizzato']); exit; }

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? 0; if(!$id){ echo json_encode(['success'=>false,'message'=>'ID mancante']); exit; }

$host="localhost"; $user="root"; $pass=""; $db="time4allergo";
$conn = new mysqli($host,$user,$pass,$db); if($conn->connect_error){ echo json_encode(['success'=>false,'message'=>$conn->connect_error]); exit; }

// Prima recupera il nome della fotografia
$sqlSelect = "SELECT fotografia FROM iscritto WHERE id = $id";
$resultSelect = $conn->query($sqlSelect);
$fotografia = null;
if ($resultSelect && $resultSelect->num_rows > 0) {
    $row = $resultSelect->fetch_assoc();
    $fotografia = $row['fotografia'];
}

$stmt = $conn->prepare("DELETE FROM iscritto WHERE id=?");

$stmt->bind_param("i",$id);
if($stmt->execute()) {
    // Se esiste una fotografia, eliminala dal filesystem
    if ($fotografia && !empty($fotografia)) {
        $fotografia = str_replace("\\", "/", $fotografia);
        // Non eliminare l'immagine di default
        if ($fotografia !== "immagini/default-user.png" && $fotografia !== "default-user.png") {
            if (strpos($fotografia, "immagini/") === 0) {
                $filePath = __DIR__ . '/../' . $fotografia;
            } else {
                $filePath = __DIR__ . '/../immagini/' . $fotografia;
            }
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
    echo json_encode(['success'=>true]);
}
else echo json_encode(['success'=>false,'message'=>$stmt->error]);

$stmt->close(); $conn->close();
