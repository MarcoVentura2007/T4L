<?php
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

require __DIR__ . '/../../data/db_connection.php';

// --- CONTROLLO RUOLO ---
$connAccount = getDbConnection('time4all');
if ($connAccount->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Connessione DB account fallita']);
    exit;
}

$stmtClasse = $connAccount->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if (!$stmtClasse) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Errore controllo permessi']);
    $connAccount->close();
    exit;
}
$stmtClasse->bind_param("s", $_SESSION['username']);
$stmtClasse->execute();
$stmtClasse->bind_result($userClasse);
if (!$stmtClasse->fetch()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Utente non trovato']);
    $stmtClasse->close();
    $connAccount->close();
    exit;
}
if ($userClasse !== 'Amministratore' && $userClasse !== 'Contabile' && $userClasse !== 'Educatore') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accesso negato']);
    $stmtClasse->close();
    $connAccount->close();
    exit;
}
$stmtClasse->close();
$connAccount->close();
// --- FINE CONTROLLO RUOLO ---

$conn = getDbConnection('time4allergo');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Connessione DB ergo fallita']);
    exit;
}

header('Content-Type: application/json');

$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Validazione formato data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo json_encode(['success' => false, 'error' => 'Formato data non valido']);
    exit;
}

$dataLike = $data . '%';
$stmt = $conn->prepare("
    SELECT i.Fotografia, p.id, i.Nome, i.Cognome, p.Ingresso, p.Uscita
    FROM presenza p
    INNER JOIN iscritto i ON p.ID_Iscritto = i.id
    WHERE p.Ingresso LIKE ?
    ORDER BY p.Ingresso ASC
");
$stmt->bind_param('s', $dataLike);
$stmt->execute();
$result = $stmt->get_result();

$presenze = [];
while ($row = $result->fetch_assoc()) {
    $presenze[] = [
        'id'         => $row['id'],
        'nome'       => $row['Nome'],
        'cognome'    => $row['Cognome'],
        'fotografia' => $row['Fotografia'],
        'ingresso'   => $row['Ingresso'],
        'uscita'     => $row['Uscita'],
    ];
}

echo json_encode(['success' => true, 'data' => $presenze, 'data_richiesta' => $data]);
$stmt->close();
$conn->close();
exit;
?>