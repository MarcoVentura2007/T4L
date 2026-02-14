<?php
session_start(); header('Content-Type: application/json');
if(!isset($_SESSION['username'])){ echo json_encode(['success'=>false,'message'=>'Non autorizzato']); exit; }

$host="localhost"; $user="root"; $pass=""; $db="time4allergo";
$conn = new mysqli($host,$user,$pass,$db); if($conn->connect_error){ echo json_encode(['success'=>false,'message'=>$conn->connect_error]); exit; }

$id=$_POST['id']??0; if(!$id){ echo json_encode(['success'=>false,'message'=>'ID mancante']); exit; }

$fields=['nome','cognome','data_nascita','codice_fiscale','contatti','disabilita','note','prezzo_orario'];
$params=[];
$types="";

foreach($fields as $f){
    $params[]=$_POST[$f] ?? '';
    $types.="s";
}

$fotografia=null;
if(isset($_FILES['foto']) && $_FILES['foto']['error']==0){
    $uploadDir=__DIR__."/../immagini/";
    if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
    $name=time()."_".basename($_FILES['foto']['name']);
    move_uploaded_file($_FILES['foto']['tmp_name'],$uploadDir.$name);
    $fotografia="immagini/".$name;
}

$sql="UPDATE iscritto SET Nome=?,Cognome=?,Data_nascita=?,Codice_fiscale=?,Contatti=?,Disabilita=?,Note=?,Stipendio_Orario=?";
if($fotografia) { $sql.=",Fotografia=?"; $params[]=$fotografia; $types.="s"; }
$sql.=" WHERE id=?";
$params[]=$id; $types.="i";

$stmt=$conn->prepare($sql);
$stmt->bind_param($types,...$params);
if($stmt->execute()) echo json_encode(['success'=>true]);
else echo json_encode(['success'=>false,'message'=>$stmt->error]);

$stmt->close(); $conn->close();
