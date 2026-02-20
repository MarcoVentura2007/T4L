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

$conn = new mysqli("localhost","root","","time4all");
if ($conn->connect_error) {
    echo json_encode(['success'=>false,'error'=>'Connessione DB fallita']);
    exit;
}

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
    if($r['ore_totali'] > 0){
        $rows[] = $r;
    }
}

echo json_encode(['success'=>true,'data'=>$rows]);
$stmt->close();
$conn->close();

?>
