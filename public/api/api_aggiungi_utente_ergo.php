<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../data/php_errors.log');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

require __DIR__ . '/../../data/db_connection.php';
require_once __DIR__ . '/../../data/image_utils.php';

$conn = getDbConnection('time4allergo');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => $conn->connect_error]);
    exit;
}

// Controllo ruolo
$connAccount = getDbConnection('time4all');
if ($connAccount->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione DB account fallita']);
    exit;
}
$stmtClasse = $connAccount->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if ($stmtClasse) {
    $stmtClasse->bind_param("s", $_SESSION['username']);
    $stmtClasse->execute();
    $stmtClasse->bind_result($userClasse);
    if ($stmtClasse->fetch()) {
        if ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore') {
            echo json_encode(['success' => false, 'message' => 'Accesso negato.']);
            $stmtClasse->close();
            $connAccount->close();
            $conn->close();
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
        $stmtClasse->close();
        $connAccount->close();
        $conn->close();
        exit;
    }
    $stmtClasse->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nel controllo dei permessi']);
    $connAccount->close();
    $conn->close();
    exit;
}
$connAccount->close();

$required = ['nome', 'cognome', 'data_nascita', 'codice_fiscale', 'email', 'telefono'];
foreach ($required as $f) {
    if (empty($_POST[$f])) {
        echo json_encode(['success' => false, 'message' => "Campo $f mancante"]);
        exit;
    }
}

$nome     = $_POST['nome'];
$cognome  = $_POST['cognome'];
$data     = $_POST['data_nascita'];
$cf       = $_POST['codice_fiscale'];
$email    = $_POST['email'];
$telefono = $_POST['telefono'];
$dis      = $_POST['disabilita'] ?? '';
$prezzo   = floatval($_POST['prezzo_orario'] ?? 0);
$note     = $_POST['note'] ?? '';

// ── Gestione foto ──────────────────────────────────────────────────────────
$fotografia = "immagini/default-user.png";

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {


    $uploadDir = __DIR__ . "/../immagini/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Leggi MIME reale
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $fileType = finfo_file($finfo, $_FILES['foto']['tmp_name']);
    finfo_close($finfo);

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($fileType, $allowedTypes, true)) {
        echo json_encode(['success' => false, 'message' => 'Tipo file non valido. Solo immagini.']);
        exit;
    }

    $nomeFile   = time() . "_" . basename($_FILES['foto']['name']);
    $targetFile = $uploadDir . $nomeFile;

    $savedPath = compressAndSaveImage($_FILES['foto']['tmp_name'], $targetFile, $fileType);
    if ($savedPath !== false) {
        $fotografia = "immagini/" . basename($savedPath);
    }
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO iscritto
            (Nome, Cognome, Data_nascita, Codice_fiscale, Email, Telefono, Disabilita, Note, Stipendio_Orario, Fotografia)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $stmt->bind_param("ssssssssds", $nome, $cognome, $data, $cf, $email, $telefono, $dis, $note, $prezzo, $fotografia);
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);

    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("api_aggiungi_utente_ergo error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
