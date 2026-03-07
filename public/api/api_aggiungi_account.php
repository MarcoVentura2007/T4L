<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no chache");
require __DIR__ . '/../config.php';

if(!isset($_SESSION['username'])){
    echo json_encode([
        "success" => false,
        "message" => "Sessione non valida"
    ]);
    exit;
}

require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if($conn->connect_error){
    echo json_encode([
        "success" => false,
        "message" => "Errore connessione database"
    ]);
    exit;
}

// --- CONTROLLO RUOLO: solo Amministratore possono accedere ---
$stmtClasse = $conn->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if ($stmtClasse) {
    $stmtClasse->bind_param("s", $_SESSION['username']);
    $stmtClasse->execute();
    $stmtClasse->bind_result($userClasse);
    if ($stmtClasse->fetch()) {
        if ($userClasse !== 'Amministratore') {
            echo json_encode(['success' => false, 'message' => 'Accesso negato. Solo Amministratore possono aggiornare gli utenti.']);
            $stmtClasse->close();
            $conn->close();
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
        $stmtClasse->close();
        $conn->close();
        exit;
    }
    $stmtClasse->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nel controllo dei permessi']);
    $conn->close();
    exit;
}
// --- FINE CONTROLLO RUOLO ---

$data = json_decode(file_get_contents("php://input"), true);

$nome_utente = isset($data['nome_utente']) ? trim($data['nome_utente']) : "";
$password = isset($data['password']) ? trim($data['password']) : "";
$classe = isset($data['classe']) ? trim($data['classe']) : "";
$codice_univoco = isset($data['codice_univoco']) ? trim($data['codice_univoco']) : "";

if($nome_utente === "" || $password === "" || $classe === "" || $codice_univoco === ""){
    echo json_encode([
        "success" => false,
        "message" => "Compila tutti i campi"
    ]);
    exit;
}

// Controlla se username esiste già
$stmtCheck = $conn->prepare("SELECT nome_utente FROM Account WHERE nome_utente = ?");
$stmtCheck->bind_param("s", $nome_utente);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if($resCheck->num_rows > 0){
    echo json_encode([
        "success" => false,
        "message" => "Username già esistente"
    ]);
    exit;
}

// Hash password (usa PEPPER come in api_login)
$password_hash = password_hash($password . PEPPER, PASSWORD_BCRYPT);

// Inserisci account
$stmtInsert = $conn->prepare(
    "INSERT INTO Account (nome_utente, password, classe, codice_univoco) VALUES (?, ?, ?, ?)"
);
$stmtInsert->bind_param("ssss", $nome_utente, $password_hash, $classe, $codice_univoco);

if($stmtInsert->execute()){
    echo json_encode([
        "success" => true,
        "message" => "Account aggiunto con successo"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Errore nell'inserimento: " . $stmtInsert->error
    ]);
}

$stmtInsert->close();
$stmtCheck->close();
$conn->close();

?>
