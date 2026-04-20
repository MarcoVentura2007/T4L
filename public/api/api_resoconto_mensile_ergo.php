<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['mese'])) {
    echo json_encode(['success' => false, 'error' => 'Mese non specificato']);
    exit;
}

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

$sql = "
SELECT 
    i.id,
    i.Nome,
    i.Cognome,
    i.Stipendio_Orario,
    i.Fotografia,
    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.Ingresso, p.Uscita) / 60), 0) AS ore_totali
FROM iscritto i
LEFT JOIN presenza p 
    ON p.ID_Iscritto = i.id 
    AND MONTH(p.Ingresso) = ? 
    AND YEAR(p.Ingresso) = ?
    AND p.Ingresso <= NOW()
    AND p.Uscita IS NOT NULL
    AND p.Uscita != '0000-00-00 00:00:00'
GROUP BY i.id
ORDER BY i.Cognome, i.Nome
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $mese, $anno);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $r['ore_totali'] = round(floatval($r['ore_totali']), 2);
    $r['costo']      = round($r['ore_totali'] * floatval($r['Stipendio_Orario']), 2);
    $rows[] = $r;
}

echo json_encode(['success' => true, 'data' => $rows]);
$stmt->close();
$conn->close();
