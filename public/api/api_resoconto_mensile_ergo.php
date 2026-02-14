<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if(!isset($_SESSION['username'])){
    echo json_encode(['success'=>false, 'error'=>'Non autorizzato']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if(!isset($data['mese'])){
    echo json_encode(['success'=>false, 'error'=>'Mese non specificato']);
    exit;
}

list($anno, $mese) = explode('-', $data['mese']);

$conn = new mysqli("localhost","root","","time4allergo");
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
    i.Stipendio_Orario,
    i.Fotografia,
    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.Ingresso, p.Uscita)/60),0) AS ore_totali
FROM iscritto i
LEFT JOIN presenza p ON p.ID_Iscritto = i.id 
    AND MONTH(p.Ingresso) = ? 
    AND YEAR(p.Ingresso) = ?
    AND p.Ingresso <= NOW()
GROUP BY i.id
ORDER BY i.Cognome, i.Nome
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $mese, $anno);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while($r = $res->fetch_assoc()){
    $r['ore_totali'] = round($r['ore_totali'], 2);
    $r['costo'] = round($r['ore_totali'] * $r['Stipendio_Orario'], 2);
    // Include all users, even those with 0 hours
    $rows[] = $r;
}

echo json_encode(['success'=>true,'data'=>$rows]);
$stmt->close();
$conn->close();
?>
