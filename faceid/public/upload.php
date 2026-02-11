<?php
header("Content-Type: application/json");

// ---- PERCORSI ----
$uploadDir    = __DIR__ . "/../uploads/";
$pythonScript = __DIR__ . "/../python/process_image.py";
$pythonBin    = __DIR__ . "/../venv/bin/python";

// ---- CONTROLLO FILE ----
if (!isset($_FILES['image'])) {
    echo json_encode(["error" => "Nessuna immagine ricevuta"]);
    exit;
}

// ---- SALVATAGGIO IMMAGINE ----
$filename = uniqid() . ".png";
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
    echo json_encode(["error" => "Errore upload"]);
    exit;
}

// ---- ESECUZIONE PYTHON ----
$command = $pythonBin . " " . escapeshellarg($pythonScript) . " " . escapeshellarg($filepath);
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

$rawOutput = trim(implode("", $output));
$decoded = json_decode($rawOutput, true);

if ($decoded === null) {
    echo json_encode([
        "error" => "Output Python non valido",
        "raw"   => $rawOutput
    ]);
    exit;
}

// ---- RISPOSTA FINALE ----
echo json_encode([
    "result" => $decoded
]);
