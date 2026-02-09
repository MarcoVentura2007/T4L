<?php

session_start();
header('Content-Type: application/json');
header("Cache-Control: no chache");




if(!isset($_SESSION['username'])){
    echo json_encode([
        "success"=>false,
        "message"=>"Sessione non valida"
    ]);
    exit;
}


$host="localhost";
$user="root";
$pass="";
$db="time4all";

$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error){
    echo json_encode([
        "success"=>false,
        "message"=>"Errore connessione database"
    ]);
    exit;
}

$codice = isset($_POST['codice']) ? trim($_POST['codice']) : "";

if($codice === ""){
    echo json_encode([
        "success"=>false,
        "message"=>"Inserisci il codice"
    ]);
    exit;
}

/* PRENDO CLASSE UTENTE */

$username = $_SESSION['username'];

$stmtUser = $conn->prepare(
    "SELECT classe FROM Account WHERE nome_utente = ?"
);
$stmtUser->bind_param("s",$username);
$stmtUser->execute();
$resUser = $stmtUser->get_result();

if($resUser->num_rows === 0){
    echo json_encode([
        "success"=>false,
        "message"=>"Utente non trovato"
    ]);
    exit;
}

$userClasse = $resUser->fetch_assoc()['classe'];

/* PRENDO CODICE */

$stmtCode = $conn->prepare(
    "SELECT classe FROM Account WHERE nome_utente = ? AND codice_univoco = ?"
);
$stmtCode->bind_param("ss", $username, $codice);
$stmtCode->execute();
$resCode = $stmtCode->get_result();

if($resCode->num_rows === 0){
    echo json_encode([
        "success"=>false,
        "message"=>"Codice errato"
    ]);
    exit;
}

$classeCodice = $resCode->fetch_assoc()['classe'];

/* CONTROLLO */
if($classeCodice !== $userClasse){
    echo json_encode([
        "success"=>false,
        "message"=>"Non autorizzato"
    ]);
    exit;
}

/* SALVA FLAG DI CODICE VERIFICATO IN SESSIONE */
$_SESSION['codice_verificato'] = true;
$_SESSION['codice_verificato_time'] = time();

/* REDIRECT */
$redirect = "#";

if($userClasse === "Educatore"){
    $redirect = "gestionale_utenti.php";
}
elseif($userClasse === "Contabile"){
    $redirect = "gestionale_contabile.php";
} elseif($userClasse === "Amministratore") {
    $redirect = "gestionale_amministratore.php";
}

echo json_encode([
    "success"=>true,
    "redirect"=>$redirect
]);
