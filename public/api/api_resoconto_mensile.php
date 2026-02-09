<?php
session_start();
header('Content-Type: application/json');

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

// Calcola solo le presenze già avvenute (Ingresso <= oggi)
$sql = "
SELECT 
    i.id,
    i.Nome,
    i.Cognome,
    i.Prezzo_Orario,
    i.Fotografia,
    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.Ingresso, p.Uscita)/60),0) AS ore_totali
FROM iscritto i
LEFT JOIN presenza p ON p.ID_Iscritto = i.id 
    AND MONTH(p.Ingresso) = $mese 
    AND YEAR(p.Ingresso) = $anno
    AND p.Ingresso <= NOW()
GROUP BY i.id
ORDER BY i.Nome, i.Cognome
";

$res = $conn->query($sql);
$rows = [];
while($r = $res->fetch_assoc()){
    $r['ore_totali'] = round($r['ore_totali'],2);
    $r['costo'] = round($r['ore_totali'] * $r['Prezzo_Orario'],2);
    $rows[] = $r;
}

echo json_encode(['success'=>true,'data'=>$rows]);
$conn->close();
?>