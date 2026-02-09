<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no chache");

if(!isset($_SESSION['username'])){
    echo json_encode(['success'=>false]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$idIscritto = intval($data['id']);
list($anno, $mese) = explode('-', $data['mese']);

$conn = new mysqli("localhost","root","","time4all");

// 1️⃣ prendo tutte le presenze già avvenute
$sql = "
SELECT * 
FROM presenza 
WHERE ID_Iscritto = $idIscritto
AND MONTH(Ingresso) = $mese
AND YEAR(Ingresso) = $anno
AND Ingresso <= NOW()
ORDER BY Ingresso
";
$res = $conn->query($sql);
$rows = [];

while($p = $res->fetch_assoc()){
    $giorno = date('Y-m-d', strtotime($p['Ingresso']));
    $ore_presenza = round((strtotime($p['Uscita']) - strtotime($p['Ingresso']))/3600,2);
    
    // costo giornata dalla presenza
    $sqlPrezzo = "SELECT Prezzo_Orario FROM iscritto WHERE id = $idIscritto";
    $resPrezzo = $conn->query($sqlPrezzo);
    $prezzo = $resPrezzo->fetch_assoc()['Prezzo_Orario'];
    $costo_presenza = round($ore_presenza * $prezzo,2);

    // 2️⃣ prendo le attività collegate a questa presenza
    $sqlAtt = "
    SELECT a.Nome, 
           ROUND(TIMESTAMPDIFF(MINUTE, p.Ora_Inizio, p.Ora_Fine)/60,2) AS ore_att,
           ROUND(TIMESTAMPDIFF(MINUTE, p.Ora_Inizio, p.Ora_Fine)/60 * $prezzo,2) AS costo_att
    FROM partecipa p
    JOIN attivita a ON a.id = p.ID_Attivita
    WHERE p.ID_Presenza = ".$p['id']."
    ";
    $resAtt = $conn->query($sqlAtt);
    $attivita = [];
    while($a = $resAtt->fetch_assoc()){
        $attivita[] = [
            'Nome' => $a['Nome'],
            'ore' => floatval($a['ore_att']),
            'costo' => floatval($a['costo_att'])
        ];
    }

    $rows[] = [
        'giorno' => $giorno,
        'ore' => $ore_presenza,        // totale dalla presenza
        'costo' => $costo_presenza,    // totale dalla presenza
        'attivita' => $attivita        // dettagli delle attività
    ];
}

echo json_encode(['success'=>true,'data'=>$rows]);
$conn->close();
?>
