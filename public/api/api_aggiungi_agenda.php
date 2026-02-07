<?php
header('Content-Type: application/json');
session_start();

// Se l'utente non è loggato
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
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
    echo json_encode(['success' => false, 'error' => 'Connessione fallita']);
    exit;
}

// Leggi i dati inviati
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Dati non validi']);
    exit;
}

$data_agenda = isset($data['data']) ? $data['data'] : null;
$ora_inizio = isset($data['ora_inizio']) ? $data['ora_inizio'] : null;
$ora_fine = isset($data['ora_fine']) ? $data['ora_fine'] : null;
$id_attivita = isset($data['id_attivita']) ? intval($data['id_attivita']) : null;
$educatori_array = isset($data['educatori']) ? $data['educatori'] : [];
$ragazzi_array = isset($data['ragazzi']) ? $data['ragazzi'] : [];

// Validazione base
if (!$data_agenda || !$ora_inizio || !$ora_fine || !$id_attivita) {
    echo json_encode(['success' => false, 'error' => 'Campi obbligatori mancanti (Data, Orari, Attività)']);
    exit;
}

if (empty($educatori_array) || !is_array($educatori_array)) {
    echo json_encode(['success' => false, 'error' => 'Seleziona almeno un educatore']);
    exit;
}

// Validazione formato data (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_agenda)) {
    echo json_encode(['success' => false, 'error' => 'Data non valida']);
    exit;
}

// Validazione formato ora (HH:MM)
if (!preg_match('/^\d{2}:\d{2}$/', $ora_inizio) || !preg_match('/^\d{2}:\d{2}$/', $ora_fine)) {
    echo json_encode(['success' => false, 'error' => 'Orari non validi']);
    exit;
}

// Controlla che attività esista
$sqlCheckAttivita = "SELECT id FROM attivita WHERE id = $id_attivita";
$resultCheck = $conn->query($sqlCheckAttivita);
if (!$resultCheck || $resultCheck->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Attività non valida']);
    exit;
}

// Prova a trovare una presenza per quel giorno per ogni ragazzo
// Se non esiste, creane una per ogni ragazzo selezionato
$ragazzi_presenza_map = []; // Map: ragazzo_id => presenza_id

if (empty($ragazzi_array) || !is_array($ragazzi_array)) {
    echo json_encode(['success' => false, 'error' => 'Seleziona almeno un ragazzo']);
    exit;
}

foreach ($ragazzi_array as $id_ragazzo) {
    $id_ragazzo = intval($id_ragazzo);
    
    if ($id_ragazzo <= 0) continue;
    
    // Controlla se il ragazzo ha già una presenza per quel giorno
    $sqlCheckPresenza = "SELECT id FROM presenza WHERE ID_Iscritto = $id_ragazzo AND DATE(Ingresso) = '$data_agenda' LIMIT 1";
    $resultCheck = $conn->query($sqlCheckPresenza);
    
    if ($resultCheck && $resultCheck->num_rows > 0) {
        $rowCheck = $resultCheck->fetch_assoc();
        $ragazzi_presenza_map[$id_ragazzo] = $rowCheck['id'];
    } else {
        // Crea una nuova presenza per questo ragazzo per quel giorno
        $sqlInsertPresenza = "INSERT INTO presenza (ID_Iscritto, Ingresso, Uscita) 
                             VALUES ($id_ragazzo, '$data_agenda 09:00:00', '$data_agenda 17:00:00')";
        if ($conn->query($sqlInsertPresenza)) {
            $ragazzi_presenza_map[$id_ragazzo] = $conn->insert_id;
        }
    }
}

if (empty($ragazzi_presenza_map)) {
    echo json_encode(['success' => false, 'error' => 'Errore nel registrare i ragazzi']);
    exit;
}

// Inserisci agenda per ogni educatore × ogni ragazzo
$agenda_ids = [];
$errors = [];

foreach ($educatori_array as $id_educatore) {
    $id_educatore = intval($id_educatore);
    
    // Controlla che educatore esista
    $sqlCheckEducatore = "SELECT id FROM educatore WHERE id = $id_educatore";
    $resultCheck = $conn->query($sqlCheckEducatore);
    if (!$resultCheck || $resultCheck->num_rows === 0) {
        $errors[] = "Educatore ID $id_educatore non trovato";
        continue;
    }
    
    // Per ogni ragazzo, crea un partecipa che collega educatore + ragazzo
    foreach ($ragazzi_presenza_map as $id_ragazzo => $presence_id) {
        $sql = "INSERT INTO partecipa (Data, Ora_Inizio, Ora_Fine, ID_Attivita, ID_Educatore, ID_Presenza) 
                VALUES ('$data_agenda', '$ora_inizio:00', '$ora_fine:00', $id_attivita, $id_educatore, $presence_id)";
        
        if ($conn->query($sql)) {
            $agenda_ids[] = $conn->insert_id;
        } else {
            $errors[] = "Errore inserimento educatore $id_educatore con ragazzo $id_ragazzo: " . $conn->error;
        }
    }
}

if (empty($agenda_ids)) {
    $error_msg = !empty($errors) ? implode("; ", $errors) : 'Nessun partecipante valido selezionato';
    echo json_encode(['success' => false, 'error' => $error_msg]);
    exit;
}

// Conta educatori unici e ragazzi
$educatori_count = count($educatori_array);
$ragazzi_count = count($ragazzi_presenza_map);

echo json_encode([
    'success' => true,
    'message' => 'Agenda creata con successo',
    'ids' => $agenda_ids,
    'educatori_count' => $educatori_count,
    'ragazzi_count' => $ragazzi_count
]);

$conn->close();
?>
