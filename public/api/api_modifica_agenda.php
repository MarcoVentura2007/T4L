<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Chiave composita per identificare lo slot originale
$data            = trim($input['data']            ?? '');
$orig_attivita   = intval($input['orig_attivita'] ?? 0);
$orig_ora_inizio = trim($input['orig_ora_inizio'] ?? '');
$orig_ora_fine   = trim($input['orig_ora_fine']   ?? '');

// Nuovi valori
$ora_inizio      = trim($input['ora_inizio']      ?? '');
$ora_fine        = trim($input['ora_fine']         ?? '');
$id_attivita     = intval($input['id_attivita']   ?? 0);
$educatori       = $input['educatori']             ?? [];
$ragazzi         = $input['ragazzi']               ?? [];
$ragazzi_gruppo  = $input['ragazzi_gruppo']        ?? [];

if (
    !$data || !$orig_attivita || !$orig_ora_inizio || !$orig_ora_fine
    || !$ora_inizio || !$ora_fine || !$id_attivita
    || empty($educatori) || empty($ragazzi)
) {
    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
    exit;
}

require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione DB fallita']);
    exit;
}

// Verifica che lo slot esista
$stmtCheck = $conn->prepare(
    "SELECT COUNT(*) as cnt FROM partecipa
     WHERE Data = ? AND ID_Attivita = ? AND Ora_Inizio = ? AND Ora_Fine = ?"
);
$stmtCheck->bind_param("siss", $data, $orig_attivita, $orig_ora_inizio, $orig_ora_fine);
$stmtCheck->execute();
$cnt = $stmtCheck->get_result()->fetch_assoc()['cnt'];
$stmtCheck->close();

if ($cnt == 0) {
    echo json_encode(['success' => false, 'message' => 'Evento non trovato nel database']);
    $conn->close();
    exit;
}

// Elimina tutte le righe del vecchio slot
$stmtDel = $conn->prepare(
    "DELETE FROM partecipa
     WHERE Data = ? AND ID_Attivita = ? AND Ora_Inizio = ? AND Ora_Fine = ?"
);
$stmtDel->bind_param("siss", $data, $orig_attivita, $orig_ora_inizio, $orig_ora_fine);
$stmtDel->execute();
$stmtDel->close();

// Reinserisce con i nuovi valori (ogni educatore × ogni ragazzo)
$stmtIns = $conn->prepare(
    "INSERT INTO partecipa
     (Data, Ora_Inizio, Ora_Fine, ID_Attivita, ID_Educatore, ID_Ragazzo, gruppo)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

foreach ($educatori as $id_edu) {
    $id_edu = intval($id_edu);
    if ($id_edu <= 0) continue;
    foreach ($ragazzi as $id_rag) {
        $id_rag = intval($id_rag);
        if ($id_rag <= 0) continue;
        $gruppo = intval($ragazzi_gruppo[$id_rag] ?? 0);
        $stmtIns->bind_param(
            "sssiiii",
            $data,
            $ora_inizio,
            $ora_fine,
            $id_attivita,
            $id_edu,
            $id_rag,
            $gruppo
        );
        if (!$stmtIns->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore inserimento: ' . $stmtIns->error]);
            $stmtIns->close();
            $conn->close();
            exit;
        }
    }
}

$stmtIns->close();
echo json_encode(['success' => true]);
$conn->close();
