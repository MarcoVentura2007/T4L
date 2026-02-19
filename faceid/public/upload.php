<?php
session_start();

// Directory upload immagini
$uploadDir = __DIR__ . '/../uploads/';

// Controllo se è stata inviata un'immagine
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'result' => [
            'known' => false,
            'error' => 'Nessuna immagine inviata o errore upload'
        ]
    ]);
    exit;
}

// Salvataggio file temporaneo
$tmpName = $_FILES['image']['tmp_name'];
$fileName = basename($_FILES['image']['name']);
$targetFile = $uploadDir . $fileName;

// Crea la cartella uploads se non esiste
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!move_uploaded_file($tmpName, $targetFile)) {
    echo json_encode([
        'result' => [
            'known' => false,
            'error' => 'Errore salvataggio file'
        ]
    ]);
    exit;
}

// ---- IMPORTANTE: PYTHON DEL VENV ----
$python = "C:\\xampp\\htdocs\\T4L\\venv\\Scripts\\python.exe";
$script = "C:\\xampp\\htdocs\\T4L\\faceid\\python\\process_image.py";

// Comando completo, con doppie backslash
$command = "\"$python\" \"$script\" \"$targetFile\" 2>&1";

// Esecuzione del comando
$output = shell_exec($command);

// Verifica se l’output è JSON valido
$json = json_decode($output, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    // Se non valido, restituisco errore
    echo json_encode([
        'result' => [
            'known' => false,
            'error' => 'Errore esecuzione Python',
            'raw' => $output
        ]
    ]);
    exit;
}

// Tutto ok, restituisco il JSON di Python al JS
echo json_encode(['result' => $json]);
exit;
