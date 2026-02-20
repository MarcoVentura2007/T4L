<?php
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

    $prezzo_orario = $_POST['prezzo_orario'] ?? '';
    $note = $_POST['note'] ?? '';
    
    // Gestione upload file
    $fotografia = null;
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
        $fileType = mime_content_type($_FILES['foto']['tmp_name']);
        
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

    $prezzo_orario = $data['prezzo_orario'] ?? '';
    $note = $data['note'] ?? '';
    $fotografia = null;
}

// Controllo che ci sia l'id
if(empty($id)){
    echo json_encode(['success' => false, 'message' => 'ID mancante']);
    exit;
}

// Costruzione query SQL con prepared statement
if ($fotografia !== null) {
    // Aggiorna anche la fotografia
    $sql = "UPDATE iscritto SET 
            nome = ?,
            cognome = ?,
            data_nascita = ?,
            codice_fiscale = ?,
            email = ?,
            telefono = ?,
            disabilita = ?,
            allergie_intolleranze = ?,
            prezzo_orario = ?,
            note = ?,
            fotografia = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Errore nella preparazione della query: ' . $conn->error]);
        exit;
    }
    
    // bind_param: s=string, d=double, i=integer
    // 11 parametri: 10 stringhe + 1 intero per l'ID
    $stmt->bind_param(
        "ssssssssdssi",
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
        $id
    );
} else {
    // Non aggiornare la fotografia
    $sql = "UPDATE iscritto SET 
            nome = ?,
            cognome = ?,
            data_nascita = ?,
            codice_fiscale = ?,
            email = ?,
            telefono = ?,
            disabilita = ?,
            allergie_intolleranze = ?,
            prezzo_orario = ?,
            note = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Errore nella preparazione della query: ' . $conn->error]);
        exit;
    }
    
    // 10 stringhe + 1 intero per l'ID
    $stmt->bind_param(
        "ssssssssdssi",
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
        $id
    );
}

if($stmt->execute()){
    echo json_encode(['success' => true, 'message' => 'Utente aggiornato']);
}else{
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
