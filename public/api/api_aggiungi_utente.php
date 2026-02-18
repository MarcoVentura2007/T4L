<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no chache");
// Verifica se l'utente è loggato
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Verifica che il form sia stato inviato
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida']);
    exit;
}

// Recupera i dati dal POST
$requiredFields = ['nome', 'cognome', 'data_nascita', 'codice_fiscale', 'email', 'telefono', 'disabilita', 'intolleranze', 'prezzo_orario', 'note'];


foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        echo json_encode(['success' => false, 'message' => "Campo mancante: $field"]);
        exit;
    }
}

// Dati puliti
$nome = $_POST['nome'];
$cognome = $_POST['cognome'];
$data_nascita = $_POST['data_nascita'];
$codice_fiscale = $_POST['codice_fiscale'];
$email = $_POST['email'];
$telefono = $_POST['telefono'];
$disabilita = $_POST['disabilita'];

$intolleranze = $_POST['intolleranze'];
$prezzo_orario = $_POST['prezzo_orario'];
$note = $_POST['note'];

// Gestione foto
$fotografia = "immagini/default-user.png"; // default
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/../immagini/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $nomeFile = basename($_FILES['foto']['name']);
    $targetFile = $uploadDir . $nomeFile;

    // Evita sovrascrittura: aggiunge timestamp
    if (file_exists($targetFile)) {
        $nomeFile = time() . "_" . $nomeFile;
        $targetFile = $uploadDir . $nomeFile;
    }

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
        $fotografia = "immagini/" . $nomeFile;
    }
}

// Connessione DB
$host = "localhost";
$user = "root";
$pass = "";
$db   = "time4all";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione DB fallita: ' . $conn->connect_error]);
    exit;
}

// Inserimento utente
$stmt = $conn->prepare("INSERT INTO iscritto (nome, cognome, data_nascita, codice_fiscale, email, telefono, disabilita, allergie_intolleranze, prezzo_orario, note, fotografia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param(
    "ssssssssdss",
    $nome,
    $cognome,
    $data_nascita,
    $codice_fiscale,
    $email,
    $telefono,
    $disabilita,
    $intolleranze,
    $prezzo_orario,
    $note,
    $fotografia
);


if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Utente aggiunto', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore inserimento: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
