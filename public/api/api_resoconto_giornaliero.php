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

// Query principale: ore totali per giorno
$sql = "
SELECT 
    DATE(p.Ingresso) AS giorno,
    SUM(TIMESTAMPDIFF(MINUTE, p.Ingresso, p.Uscita))/60 AS ore,
    i.Prezzo_Orario,
    p.id AS presenza_id
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
    $giorno = $r['giorno'];
    $ore = round($r['ore'], 2);
    $costo = round($ore * $r['Prezzo_Orario'], 2);

    // ATTIVITÀ DEL GIORNO
    $sqlAtt = "
    SELECT a.Nome, SUM(TIMESTAMPDIFF(MINUTE, p.Ora_Inizio, p.Ora_Fine))/60 AS ore
    FROM partecipa p
    JOIN attivita a ON a.id = p.ID_Attivita
    WHERE p.ID_Presenza = ".$r['presenza_id']."
    GROUP BY a.id
    ";
    $resAtt = $conn->query($sqlAtt);
    $attivita = [];
    while($a = $resAtt->fetch_assoc()){
        $attivita[] = [
            'Nome' => $a['Nome'],
            'ore' => round($a['ore'], 2),
            'costo' => round($a['ore'] * $r['Prezzo_Orario'], 2)
        ];
    }

    $rows[] = [
        'giorno' => $giorno,
        'ore' => $ore,
        'costo' => $costo,
        'attivita' => $attivita
    ];
}

echo json_encode(['success'=>true,'data'=>$rows]);
