<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['username'])) { echo json_encode(['success'=>false,'message'=>'Non autorizzato']); exit; }

$host = "localhost"; $user="root"; $pass=""; $db="time4allergo";
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error){ echo json_encode(['success'=>false,'message'=>$conn->connect_error]); exit; }

$required = ['nome','cognome','data_nascita','codice_fiscale','contatti'];
foreach($required as $f) if(empty($_POST[$f])){ echo json_encode(['success'=>false,'message'=>"Campo $f mancante"]); exit; }

$nome = $_POST['nome']; $cognome=$_POST['cognome']; $data=$_POST['data_nascita']; $cf=$_POST['codice_fiscale'];
$contatti = $_POST['contatti']; $dis=$_POST['disabilita'] ?? '';
$intol = $_POST['intolleranze'] ?? ''; $prezzo = $_POST['prezzo_orario'] ?? 0; $note = $_POST['note'] ?? '';

$fotografia = "immagini/default-user.png";
if(isset($_FILES['foto']) && $_FILES['foto']['error']==0){
    $uploadDir = __DIR__."/../immagini/";
    if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
    $name = time()."_".basename($_FILES['foto']['name']);
    move_uploaded_file($_FILES['foto']['tmp_name'],$uploadDir.$name);
    $fotografia="immagini/".$name;
}

$stmt = $conn->prepare("INSERT INTO iscritto (Nome,Cognome,Data_nascita,Codice_fiscale,Contatti,Disabilita,Note,Stipendio_Orario,Fotografia) VALUES (?,?,?,?,?,?,?,?,?)");
$stmt->bind_param("sssssssds",$nome,$cognome,$data,$cf,$contatti,$dis,$note,$prezzo,$fotografia);

if($stmt->execute()) echo json_encode(['success'=>true,'id'=>$stmt->insert_id]);
else echo json_encode(['success'=>false,'message'=>$stmt->error]);

$stmt->close(); $conn->close();
