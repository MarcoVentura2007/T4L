<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../data/php_errors.log');
session_start();
header('Content-Type: application/json');




if(!isset($_SESSION['username'])) { 
    echo json_encode(['success'=>false,'message'=>'Non autorizzato']); 
    exit; 
}

$host = "localhost"; 
$user="root"; 
$pass=""; 
$db="time4allergo";

$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error){ 
    echo json_encode(['success'=>false,'message'=>$conn->connect_error]); 
    exit; 
}

$required = ['nome','cognome','data_nascita','codice_fiscale','contatti'];
foreach($required as $f) {
    if(empty($_POST[$f])){ 
        echo json_encode(['success'=>false,'message'=>"Campo $f mancante"]); 
        exit; 
    }
}

$nome = $_POST['nome']; 
$cognome = $_POST['cognome']; 
$data = $_POST['data_nascita']; 
$cf = $_POST['codice_fiscale'];
$contatti = $_POST['contatti']; 
$dis = isset($_POST['disabilita']) ? $_POST['disabilita'] : ''; 
$prezzo = isset($_POST['prezzo_orario']) ? floatval($_POST['prezzo_orario']) : 0; 
$note = isset($_POST['note']) ? $_POST['note'] : '';

$fotografia = "immagini/default-user.png";
if(isset($_FILES['foto']) && $_FILES['foto']['error']==0){
    // Verifica errori upload
    if($_FILES['foto']['error'] !== UPLOAD_ERR_OK){
        echo json_encode(['success'=>false,'message'=>'Errore upload file: '.$_FILES['foto']['error']]);
        exit;
    }
    // Verifica dimensione
    if($_FILES['foto']['size'] > 5*1024*1024){ // 5MB max
        echo json_encode(['success'=>false,'message'=>'File troppo grande (max 5MB)']);
        exit;
    }
    $uploadDir = __DIR__."/../immagini/";
    if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
    $name = time()."_".basename($_FILES['foto']['name']);
    if(!move_uploaded_file($_FILES['foto']['tmp_name'],$uploadDir.$name)){
        echo json_encode(['success'=>false,'message'=>'Errore nel salvare il file']);
        exit;
    }
    $fotografia = "immagini/".$name;
}


try {
    $stmt = $conn->prepare("INSERT INTO iscritto (Nome,Cognome,Data_nascita,Codice_fiscale,Contatti,Disabilita,Note,Stipendio_Orario,Fotografia) VALUES (?,?,?,?,?,?,?,?,?)");
    if(!$stmt){
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sssssssds",$nome,$cognome,$data,$cf,$contatti,$dis,$note,$prezzo,$fotografia);
    if(!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    echo json_encode(['success'=>true,'id'=>$stmt->insert_id]);
    $stmt->close(); 
    $conn->close();
} catch (Exception $e) {
    error_log("api_aggiungi_utente_ergo error: " . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
