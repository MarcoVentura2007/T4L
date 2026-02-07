<?php
header('Content-Type: application/json');
session_start();

// Se l'utente non è loggato
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

// Connessione al DB
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

// Calcola il lunedì della settimana corrente
$today = new DateTime();
$monday = clone $today;
$monday->modify('Monday this week');
$mondayStr = $monday->format('Y-m-d');

// Calcola il venerdì della settimana corrente
$friday = clone $today;
$friday->modify('Friday this week');
$fridayStr = $friday->format('Y-m-d');

// Query per ottenere le attività della settimana
$sql = "SELECT 
            p.id as partecipa_id,
            p.Data,
            p.Ora_Inizio,
            p.Ora_Fine,
            a.id as attivita_id,
            a.Nome as attivita_nome,
            a.Descrizione,
            e.id as educatore_id,
            e.nome as educatore_nome,
            e.cognome as educatore_cognome
        FROM partecipa p
        INNER JOIN attivita a ON p.ID_Attivita = a.id
        INNER JOIN educatore e ON p.ID_Educatore = e.id
        WHERE p.Data BETWEEN '$mondayStr' AND '$fridayStr'
        ORDER BY p.Data ASC, p.Ora_Inizio ASC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query fallita: ' . $conn->error]);
    exit;
}

// Organizza i dati per attività
$attivita_map = [];

while ($row = $result->fetch_assoc()) {
    $key = $row['attivita_id'] . '_' . $row['Data'] . '_' . $row['Ora_Inizio'] . '_' . $row['Ora_Fine'];
    
    if (!isset($attivita_map[$key])) {
        $attivita_map[$key] = [
            'partecipa_id' => $row['partecipa_id'],
            'data' => $row['Data'],
            'ora_inizio' => $row['Ora_Inizio'],
            'ora_fine' => $row['Ora_Fine'],
            'attivita_id' => $row['attivita_id'],
            'attivita_nome' => $row['attivita_nome'],
            'descrizione' => $row['Descrizione'],
            'educatori' => []
        ];
    }
    
    // Evita duplicati educatori - controlla se già presente
    $educatore_exists = false;
    foreach ($attivita_map[$key]['educatori'] as $ed) {
        if ($ed['id'] == $row['educatore_id']) {
            $educatore_exists = true;
            break;
        }
    }
    
    if (!$educatore_exists) {
        $attivita_map[$key]['educatori'][] = [
            'id' => $row['educatore_id'],
            'nome' => $row['educatore_nome'],
            'cognome' => $row['educatore_cognome']
        ];
    }
}

// Query per ottenere gli iscritti collegati a ogni agenda (tramite ID_Presenza)
$iscritti_per_attivita = [];
$sql_iscritti = "SELECT 
                    p.ID_Attivita,
                    p.Data,
                    p.Ora_Inizio,
                    p.Ora_Fine,
                    i.id,
                    i.nome,
                    i.cognome
                FROM partecipa p
                INNER JOIN presenza pr ON p.ID_Presenza = pr.id
                INNER JOIN iscritto i ON pr.ID_Iscritto = i.id
                WHERE p.Data BETWEEN '$mondayStr' AND '$fridayStr'
                ORDER BY p.Data ASC, p.Ora_Inizio ASC";

$result_iscritti = $conn->query($sql_iscritti);

if ($result_iscritti) {
    while ($row = $result_iscritti->fetch_assoc()) {
        $attivita_key = $row['ID_Attivita'] . '_' . $row['Data'] . '_' . $row['Ora_Inizio'] . '_' . $row['Ora_Fine'];
        if (!isset($iscritti_per_attivita[$attivita_key])) {
            $iscritti_per_attivita[$attivita_key] = [];
        }
        $iscritti_per_attivita[$attivita_key][] = [
            'id' => $row['id'],
            'Nome' => $row['nome'],
            'Cognome' => $row['cognome']
        ];
    }
}

// Aggiungi gli iscritti a ogni attività
$agenda = [];
foreach ($attivita_map as $key => $att) {
    $att['iscritti'] = $iscritti_per_attivita[$key] ?? [];
    $agenda[] = $att;
}

echo json_encode([
    'success' => true,
    'data' => $agenda,
    'monday' => $mondayStr,
    'friday' => $fridayStr
]);

$conn->close();
?>
