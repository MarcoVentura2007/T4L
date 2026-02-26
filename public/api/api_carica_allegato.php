<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../data/php_errors.log');

session_start();
header('Content-Type: application/json');

// Verifica autenticazione
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Verifica metodo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non valido']);
    exit;
}

// Recupera id_iscritto
$id_iscritto = isset($_POST['id_iscritto']) ? intval($_POST['id_iscritto']) : 0;
if ($id_iscritto <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID iscritto non valido']);
    exit;
}

// Verifica file caricato
if (!isset($_FILES['allegato']) || $_FILES['allegato']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Nessun file caricato o errore upload']);
    exit;
}

$file = $_FILES['allegato'];

// Tipi di file consentiti
$allowedTypes = [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'text/plain' => 'txt',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
];

// Ottieni MIME type usando finfo_file
$fileType = 'application/octet-stream';
if (function_exists('finfo_file')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo !== false) {
        $fileType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    }
} else {
    // Fallback: usa l'estensione del file
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $extToMime = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'txt' => 'text/plain',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    $fileType = $extToMime[$ext] ?? 'application/octet-stream';
}
if (!array_key_exists($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Tipo di file non consentito. Tipi supportati: PDF, DOC, DOCX, JPG, PNG, GIF, TXT, XLS, XLSX']);
    exit;
}

// Dimensione massima: 10MB
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File troppo grande (max 10MB)']);
    exit;
}

// Directory upload
$uploadDir = __DIR__ . '/../allegati/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Genera nome file univoco
$extension = $allowedTypes[$fileType];
$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
$uniqueName = time() . '_' . $safeName . '.' . $extension;
$targetPath = $uploadDir . $uniqueName;

// Sposta il file
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['success' => false, 'message' => 'Errore nel salvare il file']);
    exit;
}

// Percorso relativo per il database
$percorsoDb = 'allegati/' . $uniqueName;

// Connessione database
$host = "localhost";
$user = "root";
$pass = "";
$db = "time4all";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // Elimina il file se la connessione fallisce
    unlink($targetPath);
    echo json_encode(['success' => false, 'message' => 'Errore connessione database']);
    exit;
}

// Inserisci nel database
$stmt = $conn->prepare("INSERT INTO allegati (file, ID_Iscritto) VALUES (?, ?)");
if (!$stmt) {
    unlink($targetPath);
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param("si", $percorsoDb, $id_iscritto);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Allegato caricato con successo',
        'id' => $stmt->insert_id,
        'file' => $percorsoDb,
        'ID_Iscritto' => $id_iscritto
    ]);
} else {
    unlink($targetPath);
    echo json_encode(['success' => false, 'message' => 'Errore inserimento database: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
