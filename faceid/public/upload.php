<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

try {
    $response = ["debug" => "PHP script started"];

    // ---- PERCORSI ----
    $uploadDir    = __DIR__ . "/../uploads/";
    $pythonScript = __DIR__ . "/../python/process_image.py";
    $pythonBin    = "python"; // Usa python

    // ---- CONTROLLO FILE ----
    if (!isset($_FILES['image'])) {
        $response["result"] = ["known" => false, "error" => "Nessuna immagine ricevuta"];
        echo json_encode($response);
        exit;
    }

    // ---- SALVATAGGIO IMMAGINE ----
    $filename = uniqid() . ".png";
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
        $response["result"] = ["known" => false, "error" => "Errore upload"];
        echo json_encode($response);
        exit;
    }

    $response["debug"] = "Image uploaded successfully";

    // ---- ESECUZIONE PYTHON ----
    $command = $pythonBin . " " . escapeshellarg($pythonScript) . " " . escapeshellarg($filepath) . " 2>&1";
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);

    $rawOutput = trim(implode("", $output));
    $response["debug"] = "Python executed, raw output: " . $rawOutput;
    $decoded = json_decode($rawOutput, true);

    if ($decoded === null || $returnCode !== 0) {
        $response["result"] = [
            "known" => false,
            "error" => "Errore esecuzione Python",
            "raw" => $rawOutput,
            "command" => $command,
            "returnCode" => $returnCode
        ];
        echo json_encode($response);
        exit;
    }

    // ---- RISPOSTA FINALE ----
    $response["result"] = $decoded;
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        "result" => [
            "known" => false,
            "error" => "Errore PHP: " . $e->getMessage()
        ]
    ]);
}
