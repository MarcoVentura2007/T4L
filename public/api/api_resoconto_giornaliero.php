<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['username'])){
    echo json_encode(['success'=>false]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$idIscritto = intval($data['id']);
list($anno, $mese) = explode('-', $data['mese']);

$conn = new mysqli("localhost","root","","time4all");

$sql = "
SELECT 
    DATE(p.Ingresso) AS giorno,
    SUM(TIMESTAMPDIFF(MINUTE, p.Ingresso, p.Uscita)) / 60 AS ore,
    i.Prezzo_Orario
FROM presenza p
JOIN iscritto i ON i.id = p.ID_Iscritto
WHERE p.ID_Iscritto = $idIscritto
AND MONTH(p.Ingresso) = $mese
AND YEAR(p.Ingresso) = $anno
GROUP BY DATE(p.Ingresso)
ORDER BY giorno
";

$res = $conn->query($sql);

$rows = [];
while($r = $res->fetch_assoc()){
    $ore = round($r['ore'], 2);
    $rows[] = [
        'giorno' => $r['giorno'],
        'ore' => $ore,
        'costo' => round($ore * $r['Prezzo_Orario'], 2)
    ];
}

echo json_encode(['success'=>true,'data'=>$rows]);