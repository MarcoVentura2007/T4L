<?php
    header('Content-Type: application/json; charset=utf-8');
    header("Cache-Control: no chache");

    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST' ||
        empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest'
    ) {
        echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
        exit;
    }
    
    // --- LETTURA DATI JSON ---
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Dati non validi']);
        exit;
    }

    // Dati raccolti dal popup
    $timeIn       = $input['ora_ingresso']; // formato H:i
    $timeOut      = $input['ora_uscita'];   // formato H:i
    $check_firma  = 1;
    $id_iscritto  = intval($input['id_iscritto']);

    // --- CONNESSIONE DB ---
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "time4all";

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Connessione fallita']);
        exit;
    }

    // --- COSTRUZIONE DATETIME ---
    $oggi = date('Y-m-d');
    $ingresso = $oggi . ' ' . $timeIn . ':00';
    $uscita   = $oggi . ' ' . $timeOut . ':00';

    // --- QUERY CON PREPARED STATEMENT ---
    $stmt = $conn->prepare("INSERT INTO Presenza (Ingresso, Uscita, Check_firma, ID_Iscritto) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("ssii", $ingresso, $uscita, $check_firma, $id_iscritto);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();

?>
