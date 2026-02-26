<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../data/php_errors.log');

session_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache");

// --- BLOCCO ACCESSO DIRETTO ---
// Permetti solo richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit;
}

if(!isset($_SESSION['username'])){
    echo json_encode(['success'=>false, 'message' => 'Sessione non valida']);
    exit;
}
// --- FINE BLOCCO ---

// Connessione al DB
$host = "localhost";    
$user = "root";         
$pass = "";             
$db   = "time4all"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connessione fallita: ' . $conn->connect_error]));
}

// Determina se è una richiesta con file o JSON
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

// inizializziamo tutte le variabili comuni
$id = 0;
$nome = $cognome = $data_nascita = $codice_fiscale = $email = $telefono = $disabilita = '';
$intolleranze = '';
$prezzo_orario = 0;
$note = '';
$gruppo = null; // null indica che il client non ha fornito il campo
$fotografia = null;

if (strpos($contentType, 'multipart/form-data') !== false) {
    // Richiesta con file upload
    $id = intval($_POST['id'] ?? 0);
    $nome = $_POST['nome'] ?? '';
    $cognome = $_POST['cognome'] ?? '';
    $data_nascita = $_POST['data_nascita'] ?? '';
    $codice_fiscale = $_POST['codice_fiscale'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $disabilita = $_POST['disabilita'] ?? '';
    $intolleranze = $_POST['intolleranze'] ?? '';
    $prezzo_orario = floatval($_POST['prezzo_orario'] ?? 0);
    $note = $_POST['note'] ?? '';
    // normalizza il valore di gruppo se inviato
    if (isset($_POST['gruppo'])) {
        $gruppo = intval($_POST['gruppo']) === 1 ? 1 : 0;
    }

    // log per debug
    error_log("api_aggiorna_utente received gruppo (multipart)=" . var_export($gruppo, true));

    // Gestione upload file
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../immagini/';
        
        // Crea la directory se non esiste
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Genera nome file univoco
        $fileName = time() . '_' . basename($_FILES['foto']['name']);
        $targetPath = $uploadDir . $fileName;
        
        // Verifica che sia un'immagine
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        // Ottieni MIME type usando finfo_file
        $fileType = 'application/octet-stream';
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $fileType = finfo_file($finfo, $_FILES['foto']['tmp_name']);
                finfo_close($finfo);
            }
        } else {
            // Fallback: usa l'estensione del file
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $extToMime = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            $fileType = $extToMime[$ext] ?? 'application/octet-stream';
        }
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Tipo di file non valido. Solo immagini sono permesse.']);
            exit;
        }
        
        // Sposta il file
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetPath)) {
            $fotografia = 'immagini/' . $fileName;
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nel caricamento del file']);
            exit;
        }
    }
} else {
    // Richiesta JSON (senza file)
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($data['id'] ?? 0);
    $nome = $data['nome'] ?? '';
    $cognome = $data['cognome'] ?? '';
    $data_nascita = $data['data_nascita'] ?? '';
    $codice_fiscale = $data['codice_fiscale'] ?? '';
    $email = $data['email'] ?? '';
    $telefono = $data['telefono'] ?? '';
    $disabilita = $data['disabilita'] ?? '';
    $intolleranze = $data['intolleranze'] ?? '';
    $prezzo_orario = floatval($data['prezzo_orario'] ?? 0);
    $note = $data['note'] ?? '';
    if (isset($data['gruppo'])) {
        $gruppo = intval($data['gruppo']) === 1 ? 1 : 0;
    }
    $fotografia = null;
}

// Controllo che ci sia l'id
if(empty($id)){
    echo json_encode(['success' => false, 'message' => 'ID mancante']);
    exit;
}

// se non è stato inviato il campo gruppo, recupero il valore corrente dal DB
if ($gruppo === null) {
    $stmtTmp = $conn->prepare("SELECT Gruppo FROM iscritto WHERE id = ?");
    if ($stmtTmp) {
        $stmtTmp->bind_param("i", $id);
        $stmtTmp->execute();
        $stmtTmp->bind_result($existingGroup);
        if ($stmtTmp->fetch()) {
            $gruppo = intval($existingGroup) === 1 ? 1 : 0;
        } else {
            $gruppo = 0;
        }
        $stmtTmp->close();
    } else {
        // se la query fallisce, default a 0 per sicurezza
        $gruppo = 0;
    }
    error_log("api_aggiorna_utente gruppo non fornito, usato valore DB={$gruppo}");
}

// Costruzione query SQL con prepared statement
if ($fotografia !== null) {
    // Aggiorna anche la fotografia sempre (e Gruppo)
    $sql = "UPDATE iscritto SET 
            Nome = ?,
            Cognome = ?,
            Data_nascita = ?,
            Codice_fiscale = ?,
            Email = ?,
            Telefono = ?,
            Disabilita = ?,
            Allergie_Intolleranze = ?,
            Prezzo_Orario = ?,
            Note = ?,
            Fotografia = ?,
            Gruppo = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Errore nella preparazione della query: ' . $conn->error]);
        exit;
    }
    // 13 parameters: 8 strings, 1 double, 1 string, 2 integers (gruppo,id)
    $stmt->bind_param(
        "ssssssssdssii",
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
        $fotografia,
        $gruppo,
        $id
    );
} else {
    // Non aggiornare la fotografia, ma includo sempre gruppo
    $sql = "UPDATE iscritto SET 
            Nome = ?,
            Cognome = ?,
            Data_nascita = ?,
            Codice_fiscale = ?,
            Email = ?,
            Telefono = ?,
            Disabilita = ?,
            Allergie_Intolleranze = ?,
            Prezzo_Orario = ?,
            Note = ?,
            Gruppo = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Errore nella preparazione della query: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param(
        "ssssssssdsii",
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
        $gruppo,
        $id
    );
}

try {
    if($stmt->execute()){
        echo json_encode(['success' => true, 'message' => 'Utente aggiornato']);
    } else {
        $errorMsg = 'Errore: ' . $stmt->error;
        error_log("api_aggiorna_utente error: " . $errorMsg);
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    }
} catch (Exception $e) {
    error_log("api_aggiorna_utente exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
