<?php
session_start();
header('Content-Type: application/json');

// Verifica autenticazione
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Recupera id_iscritto
$id_iscritto = isset($_GET['id_iscritto']) ? intval($_GET['id_iscritto']) : 0;

if ($id_iscritto <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID iscritto non valido']);
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

// Recupera allegati
$stmt = $conn->prepare("SELECT id, percorso, nome_file, data_upload FROM allegati WHERE id_iscritto = ? ORDER BY data_upload DESC");
$stmt->bind_param("i", $id_iscritto);
$stmt->execute();
$result = $stmt->get_result();

$allegati = [];
while ($row = $result->fetch_assoc()) {
    // Determina tipo file per icona
    $extension = pathinfo($row['percorso'], PATHINFO_EXTENSION);
    $tipo = 'file';
    
    switch (strtolower($extension)) {
        case 'pdf':
            $tipo = 'pdf';
            break;
        case 'doc':
        case 'docx':
            $tipo = 'doc';
            break;
        case 'xls':
        case 'xlsx':
            $tipo = 'xls';
            break;
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            $tipo = 'image';
            break;
        case 'txt':
            $tipo = 'txt';
            break;
    }
    
    $allegati[] = [
        'id' => $row['id'],
        'percorso' => $row['percorso'],
        'nome_file' => $row['nome_file'],
        'data_upload' => $row['data_upload'],
        'tipo' => $tipo
    ];
}

echo json_encode(['success' => true, 'allegati' => $allegati]);

$stmt->close();
$conn->close();
