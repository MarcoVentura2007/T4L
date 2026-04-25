<?php
header('Content-Type: application/json');
header("Cache-Control: no-cache");
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Connessione fallita: ' . $conn->connect_error]);
    exit;
}

if (!empty($_GET['week'])) {
    $requestedDate = DateTime::createFromFormat('Y-m-d', $_GET['week']);
    if ($requestedDate === false) $requestedDate = new DateTime();
} else {
    $requestedDate = new DateTime();
}

$dow = (int)$requestedDate->format('N');
if ($dow !== 1) $requestedDate->modify('Monday this week');

$monday = clone $requestedDate;
$friday = clone $requestedDate;
$friday->modify('+4 days');

$mondayStr = $monday->format('Y-m-d');
$fridayStr  = $friday->format('Y-m-d');

// ── Query attività + educatori + note ────────────────────────────────────
$stmt = $conn->prepare("
    SELECT
        p.id           AS partecipa_id,
        p.Data,
        p.Ora_Inizio,
        p.Ora_Fine,
        p.Note,
        a.id           AS attivita_id,
        a.Nome         AS attivita_nome,
        a.Descrizione,
        e.id           AS educatore_id,
        e.nome         AS educatore_nome,
        e.cognome      AS educatore_cognome
    FROM partecipa p
    INNER JOIN attivita a ON p.ID_Attivita = a.id
    INNER JOIN educatore e ON p.ID_Educatore = e.id
    WHERE p.Data BETWEEN ? AND ?
    ORDER BY p.Data ASC, p.Ora_Inizio ASC
");

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

$attivita_map = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['attivita_id'] . '_' . $row['Data'] . '_'
         . substr($row['Ora_Inizio'], 0, 5) . '_' . substr($row['Ora_Fine'], 0, 5);

    if (!isset($attivita_map[$key])) {
        $attivita_map[$key] = [
            'id'            => $key,
            'data'          => $row['Data'],
            'ora_inizio'    => $row['Ora_Inizio'],
            'ora_fine'      => $row['Ora_Fine'],
            'note'          => $row['Note'] ?? '',   // <-- NOTE
            'attivita_id'   => $row['attivita_id'],
            'attivita_nome' => $row['attivita_nome'],
            'descrizione'   => $row['Descrizione'],
            'educatori'     => [],
            'ragazzi'       => []
        ];
    }

    $exists = false;
    foreach ($attivita_map[$key]['educatori'] as $ed) {
        if ($ed['id'] == $row['educatore_id']) { $exists = true; break; }
    }
    if (!$exists) {
        $attivita_map[$key]['educatori'][] = [
            'id'      => $row['educatore_id'],
            'nome'    => $row['educatore_nome'],
            'cognome' => $row['educatore_cognome']
        ];
    }
}

// ── Query ragazzi ─────────────────────────────────────────────────────────
$stmt_ragazzi = $conn->prepare("
    SELECT
        p.ID_Attivita,
        p.Data,
        p.Ora_Inizio,
        p.Ora_Fine,
        i.id           AS ragazzo_id,
        i.nome         AS ragazzo_nome,
        i.cognome      AS ragazzo_cognome,
        i.fotografia   AS ragazzo_fotografia,
        i.Gruppo       AS ragazzo_gruppo_default,
        p.presenza_effettiva AS effettiva_presenza,
        p.Gruppo       AS ragazzo_gruppo
    FROM partecipa p
    INNER JOIN iscritto i ON p.ID_Ragazzo = i.id
    WHERE p.Data BETWEEN ? AND ?
    ORDER BY p.Data ASC, p.Ora_Inizio ASC
");

if (!$stmt_ragazzi) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare ragazzi fallito: ' . $conn->error]);
    exit;
}

$stmt_ragazzi->bind_param("ss", $mondayStr, $fridayStr);
$stmt_ragazzi->execute();
$result_ragazzi = $stmt_ragazzi->get_result();

$ragazzi_per_attivita = [];
if ($result_ragazzi) {
    while ($row = $result_ragazzi->fetch_assoc()) {
        $key = $row['ID_Attivita'] . '_' . $row['Data'] . '_'
             . substr($row['Ora_Inizio'], 0, 5) . '_' . substr($row['Ora_Fine'], 0, 5);

        if (!isset($ragazzi_per_attivita[$key])) $ragazzi_per_attivita[$key] = [];

        $gruppo_val = $row['ragazzo_gruppo'];
        if ($gruppo_val === null || $gruppo_val === '') {
            $gruppo_val = $row['ragazzo_gruppo_default'];
        }

        $ragazzi_per_attivita[$key][] = [
            'id'                 => $row['ragazzo_id'],
            'nome'               => $row['ragazzo_nome'],
            'cognome'            => $row['ragazzo_cognome'],
            'fotografia'         => $row['ragazzo_fotografia'],
            'effettiva_presenza' => (bool)$row['effettiva_presenza'],
            'gruppo'             => $gruppo_val
        ];
    }
}

foreach ($ragazzi_per_attivita as $key => &$ragazzi) {
    $unique = [];
    foreach ($ragazzi as $r) { $unique[$r['id']] = $r; }
    $ragazzi = array_values($unique);
}
unset($ragazzi);

$agenda = [];
foreach ($attivita_map as $key => $att) {
    $att['ragazzi'] = $ragazzi_per_attivita[$key] ?? [];
    $agenda[] = $att;
}

echo json_encode([
    'success' => true,
    'data'    => $agenda,
    'monday'  => $mondayStr,
    'friday'  => $fridayStr
]);

$stmt->close();
$stmt_ragazzi->close();
$conn->close();