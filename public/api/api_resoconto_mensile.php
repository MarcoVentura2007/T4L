<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no chache");

if(!isset($_SESSION['username'])){
    echo json_encode(['success'=>false]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
list($anno, $mese) = explode('-', $data['mese']);

// Connessione al DB
require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    echo json_encode(['success'=>false,'error'=>'Connessione DB fallita']);
    exit;
}

// --- CONTROLLO RUOLO: solo Contabile o Amministratore possono visualizzare resoconto ---
$stmtClasse = $conn->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if ($stmtClasse) {
    $stmtClasse->bind_param("s", $_SESSION['username']);
    $stmtClasse->execute();
    $stmtClasse->bind_result($userClasse);
    if ($stmtClasse->fetch()) {
        if ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore') {
            echo json_encode(['success' => false, 'message' => 'Accesso negato. Solo Contabile o Amministratore possono visualizzare resoconti.']);
            $stmtClasse->close();
            $conn->close();
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
        $stmtClasse->close();
        $conn->close();
        exit;
    }
    $stmtClasse->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Errore nel controllo dei permessi']);
    $conn->close();
    exit;
}
// --- FINE CONTROLLO RUOLO ---

// Calcola solo le presenze già avvenute (Ingresso <= oggi) con prepared statement
$stmt = $conn->prepare("
SELECT 
    i.id,
    i.Nome,
    i.Cognome,
    i.Prezzo_Orario,
    i.Fotografia,
    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.Ingresso, p.Uscita)/60),0) AS ore_totali
FROM iscritto i
LEFT JOIN presenza p ON p.ID_Iscritto = i.id 
    AND MONTH(p.Ingresso) = ? 
    AND YEAR(p.Ingresso) = ?
    AND p.Ingresso <= NOW()
GROUP BY i.id
ORDER BY i.Nome, i.Cognome
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $mese, $anno);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while($r = $res->fetch_assoc()){
    $r['ore_totali'] = round($r['ore_totali'],2);
    $r['costo'] = round($r['ore_totali'] * $r['Prezzo_Orario'],2);
    // include every user, even if they have zero hours this month
    $rows[] = $r;
}

echo json_encode(['success'=>true,'data'=>$rows]);
$stmt->close();
$conn->close();

?>
