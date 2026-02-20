<?php
header('Content-Type: application/json');
header("Cache-Control: no chache");
session_start();

// Controlla login
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

// Connessione DB
$host = "localhost";    
$user = "root";         
$pass = "";             
$db   = "time4all"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Connessione fallita: ' . $conn->connect_error]);
    exit;
}

// Calcola lunedì e venerdì della settimana corrente
$today = new DateTime();
// Se oggi è domenica, sposta a domani
if ($today->format('N') == 7) $today->modify('+1 day');

$monday = clone $today;
$monday->modify('Monday this week');
$friday = clone $today;
$friday->modify('Friday this week');

$mondayStr = $monday->format('Y-m-d');
$fridayStr = $friday->format('Y-m-d');

// --- Query attività + educatori con prepared statement ---
$stmt = $conn->prepare("SELECT 
            p.id AS partecipa_id,
            p.Data,
            p.Ora_Inizio,
            p.Ora_Fine,
            a.id AS attivita_id,
            a.Nome AS attivita_nome,
            a.Descrizione,
            e.id AS educatore_id,
            e.nome AS educatore_nome,
            e.cognome AS educatore_cognome
        FROM partecipa p
        INNER JOIN attivita a ON p.ID_Attivita = a.id
        INNER JOIN educatore e ON p.ID_Educatore = e.id
        WHERE p.Data BETWEEN ? AND ?
        ORDER BY p.Data ASC, p.Ora_Inizio ASC");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare fallito: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ss", $mondayStr, $fridayStr);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query attività fallita: ' . $conn->error]);
    exit;
}


// Organizza attività ed educatori
$attivita_map = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['attivita_id'] . '_' . $row['Data'] . '_' . substr($row['Ora_Inizio'], 0, 5) . '_' . substr($row['Ora_Fine'], 0, 5);
    if (!isset($attivita_map[$key])) {
        $attivita_map[$key] = [
            'id' => $key,
            'data' => $row['Data'],
            'ora_inizio' => $row['Ora_Inizio'],
            'ora_fine' => $row['Ora_Fine'],
            'attivita_id' => $row['attivita_id'],
            'attivita_nome' => $row['attivita_nome'],
            'descrizione' => $row['Descrizione'],
            'educatori' => [],
            'ragazzi' => [] // array vuoto da riempire
        ];
    }

    // Evita duplicati educatori
    $exists = false;
    foreach ($attivita_map[$key]['educatori'] as $ed) {
        if ($ed['id'] == $row['educatore_id']) { $exists = true; break; }
    }
    if (!$exists) {
        $attivita_map[$key]['educatori'][] = [
            'id' => $row['educatore_id'],
            'nome' => $row['educatore_nome'],
            'cognome' => $row['educatore_cognome']
        ];
    }
}

// --- Query ragazzi con prepared statement ---
$stmt_ragazzi = $conn->prepare("SELECT
                    p.ID_Attivita,
                    p.Data,
                    p.Ora_Inizio,
                    p.Ora_Fine,
                    i.id AS ragazzo_id,
                    i.nome AS ragazzo_nome,
                    i.cognome AS ragazzo_cognome,
                    i.fotografia AS ragazzo_fotografia,
                    p.presenza_effettiva AS effettiva_presenza
                FROM partecipa p
                INNER JOIN iscritto i ON p.ID_Ragazzo = i.id
                WHERE p.Data BETWEEN ? AND ?
                ORDER BY p.Data ASC, p.Ora_Inizio ASC");

if (!$stmt_ragazzi) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare fallito: ' . $conn->error]);
    exit;
}

$stmt_ragazzi->bind_param("ss", $mondayStr, $fridayStr);
$stmt_ragazzi->execute();
$result_ragazzi = $stmt_ragazzi->get_result();

$ragazzi_per_attivita = [];

if ($result_ragazzi) {
    while ($row = $result_ragazzi->fetch_assoc()) {
        $key = $row['ID_Attivita'] . '_' . $row['Data'] . '_' . substr($row['Ora_Inizio'], 0, 5) . '_' . substr($row['Ora_Fine'], 0, 5);
        if (!isset($ragazzi_per_attivita[$key])) $ragazzi_per_attivita[$key] = [];

        $ragazzi_per_attivita[$key][] = [
            'id' => $row['ragazzo_id'],
            'nome' => $row['ragazzo_nome'],
            'cognome' => $row['ragazzo_cognome'],
            'fotografia' => $row['ragazzo_fotografia'],
            'effettiva_presenza' => (bool)$row['effettiva_presenza']
        ];
    }
}

// Make ragazzi unique per activity
foreach ($ragazzi_per_attivita as $key => &$ragazzi) {
    $unique = [];
    foreach ($ragazzi as $r) {
        $unique[$r['id']] = $r;
    }
    $ragazzi = array_values($unique);
}

// Aggiungi ragazzi alle attività
$agenda = [];
foreach ($attivita_map as $key => $att) {
    $att['ragazzi'] = $ragazzi_per_attivita[$key] ?? [];
    $agenda[] = $att;
}

// Restituisci JSON
echo json_encode([
    'success' => true,
    'data' => $agenda,
    'monday' => $mondayStr,
    'friday' => $fridayStr
]);

$stmt->close();
$stmt_ragazzi->close();
$conn->close();
?>
