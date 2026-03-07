<?php

session_start();
header('Content-Type: application/json');
header("Cache-Control: no chache");

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
            echo json_encode(['success' => false, 'message' => 'Accesso negato. Solo Amministratore può eliminare gli utenti.']);
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

if($nome_utente === ""){
    echo json_encode([
        "success" => false,
        "message" => "Nome utente non valido"
    ]);
    exit;
}

// Impedisci di eliminare l'account loggato
if($nome_utente === $_SESSION['username']){
    echo json_encode([
        "success" => false,
        "message" => "Non puoi eliminare il tuo account"
    ]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM Account WHERE nome_utente = ?");
$stmt->bind_param("s", $nome_utente);

if($stmt->execute()){
    if($stmt->affected_rows > 0){
        echo json_encode([
            "success" => true,
            "message" => "Account eliminato con successo"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Nessun account trovato"
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Errore nell'eliminazione: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();

?>
