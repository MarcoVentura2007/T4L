<?php
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

// Recupera dati JSON
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if ($id <= 0) {

    echo json_encode(['success' => false, 'message' => 'ID allegato non valido']);
    exit;
}

// Connessione database
$host = "localhost";
$user = "root";
$pass = "";
$db = "time4all";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Errore connessione database']);
    exit;
}

// Recupera percorso file prima di eliminare
$stmt = $conn->prepare("SELECT percorso_file FROM allegati WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Allegato non trovato']);
    $stmt->close();
    $conn->close();
    exit;
}

$row = $result->fetch_assoc();
$percorso = $row['percorso_file'];
$stmt->close();

// Elimina dal database
$stmt = $conn->prepare("DELETE FROM allegati WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Elimina file fisico
    $filePath = __DIR__ . '/../' . $percorso;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    echo json_encode(['success' => true, 'message' => 'Allegato eliminato con successo']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore eliminazione: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
