<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['id']) || !isset($data['mese'])) {
    echo json_encode(['success' => false, 'error' => 'Parametri mancanti']);
    exit;
}

$idIscritto = intval($data['id']);
list($anno, $mese) = explode('-', $data['mese']);

require __DIR__ . '/../../data/db_connection.php';

// ── Controllo ruolo sul DB degli account (time4all) ──
$connAccount = getDbConnection('time4all');
if ($connAccount->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Connessione DB account fallita']);
    exit;
}

$stmtClasse = $connAccount->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if (!$stmtClasse) {
    echo json_encode(['success' => false, 'message' => 'Errore nel controllo dei permessi']);
    $connAccount->close();
    exit;
}
$stmtClasse->bind_param("s", $_SESSION['username']);
$stmtClasse->execute();
$stmtClasse->bind_result($userClasse);
if ($stmtClasse->fetch()) {
    if ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore') {
        echo json_encode(['success' => false, 'message' => 'Accesso negato.']);
        $stmtClasse->close();
        $connAccount->close();
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
    $stmtClasse->close();
    $connAccount->close();
    exit;
}
$stmtClasse->close();
$connAccount->close();

// ── Query sui dati ergo ──
$conn = getDbConnection('time4allergo');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Connessione DB ergo fallita']);
    exit;
}

// Stipendio orario dell'iscritto
$stmtPrezzo = $conn->prepare("SELECT Stipendio_Orario FROM iscritto WHERE id = ?");
$stmtPrezzo->bind_param("i", $idIscritto);
$stmtPrezzo->execute();
$resPrezzo = $stmtPrezzo->get_result()->fetch_assoc();
$prezzo    = $resPrezzo ? floatval($resPrezzo['Stipendio_Orario']) : 0;
$stmtPrezzo->close();

// Presenze del mese
$sql = "
SELECT id, Ingresso, Uscita
FROM presenza
WHERE ID_Iscritto = ?
  AND MONTH(Ingresso) = ?
  AND YEAR(Ingresso)  = ?
  AND Ingresso <= NOW()
  AND Uscita IS NOT NULL
  AND Uscita != '0000-00-00 00:00:00'
ORDER BY Ingresso ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $idIscritto, $mese, $anno);
$stmt->execute();
$res = $stmt->get_result();

$days = [];
while ($p = $res->fetch_assoc()) {
    $giorno  = date('Y-m-d', strtotime($p['Ingresso']));
    $ore     = (strtotime($p['Uscita']) - strtotime($p['Ingresso'])) / 3600;

    if (!isset($days[$giorno])) {
        $days[$giorno] = ['ore' => 0, 'costo' => 0];
    }
    $days[$giorno]['ore'] += $ore;
}

$rows = [];
foreach ($days as $giorno => $d) {
    $ore   = round($d['ore'], 2);
    $costo = round($ore * $prezzo, 2);
    $rows[] = [
        'giorno'   => $giorno,
        'ore'      => $ore,
        'costo'    => $costo,
        'attivita' => []   // compatibilità con il JS esistente
    ];
}

echo json_encode(['success' => true, 'data' => $rows]);
$stmt->close();
$conn->close();
