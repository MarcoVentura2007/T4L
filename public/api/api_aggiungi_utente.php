<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida']);
    exit;
}

// Connessione DB
require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione DB fallita: ' . $conn->connect_error]);
    exit;
}

// Controllo ruolo
$stmtClasse = $conn->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if ($stmtClasse) {
    $stmtClasse->bind_param("s", $_SESSION['username']);
    $stmtClasse->execute();
    $stmtClasse->bind_result($userClasse);
    if ($stmtClasse->fetch()) {
        if ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore') {
            echo json_encode(['success' => false, 'message' => 'Accesso negato.']);
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

// Recupera campi obbligatori
$requiredFields = ['nome', 'cognome', 'data_nascita', 'codice_fiscale'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        echo json_encode(['success' => false, 'message' => "Campo mancante: $field"]);
        exit;
    }
}

$nome               = trim($_POST['nome']);
$cognome            = trim($_POST['cognome']);
$data_nascita       = trim($_POST['data_nascita']);
$codice_fiscale     = trim($_POST['codice_fiscale']);
$email              = trim($_POST['email']              ?? '');
$telefono           = trim($_POST['telefono']           ?? '');
$disabilita         = trim($_POST['disabilita']         ?? '');
$intolleranze       = trim($_POST['intolleranze']       ?? '');
$prezzo_orario      = floatval($_POST['prezzo_orario']       ?? 0);
$prezzo_orario_gruppo = floatval($_POST['prezzo_orario_gruppo'] ?? 0);
$note               = trim($_POST['note']               ?? '');
$gruppo             = intval($_POST['gruppo']            ?? 0) === 1 ? 1 : 0;

// Gestione foto
$fotografia = "immagini/default-user.png";
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/../immagini/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $nomeFile  = basename($_FILES['foto']['name']);
    $targetFile = $uploadDir . $nomeFile;

    if (file_exists($targetFile)) {
        $nomeFile   = time() . "_" . $nomeFile;
        $targetFile = $uploadDir . $nomeFile;
    }

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
        $fotografia = "immagini/" . $nomeFile;
    }
}

// INSERT
// Tipi: s s s s s s s s d d s s i
//        n c d cf em tel dis int pr prg not foto grp
$stmt = $conn->prepare(
    "INSERT INTO iscritto
        (Nome, Cognome, Data_nascita, Codice_fiscale, Email, Telefono,
         Disabilita, Allergie_Intolleranze, Prezzo_Orario, Prezzo_Orario_Gruppo,
         Note, Fotografia, Gruppo)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param(
    "ssssssssddssi",
    $nome,
    $cognome,
    $data_nascita,
    $codice_fiscale,
    $email,
    $telefono,
    $disabilita,
    $intolleranze,
    $prezzo_orario,
    $prezzo_orario_gruppo,
    $note,
    $fotografia,
    $gruppo
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Utente aggiunto', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore inserimento: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
