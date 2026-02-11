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
$presenze = [];
$days = [];

// costo orario del ragazzo
$sqlPrezzo = "SELECT Prezzo_Orario FROM iscritto WHERE id = $idIscritto";
$resPrezzo = $conn->query($sqlPrezzo);
$prezzo = $resPrezzo->fetch_assoc()['Prezzo_Orario'];

while($p = $res->fetch_assoc()){
    $giorno = date('Y-m-d', strtotime($p['Ingresso']));
    if(!isset($presenze[$giorno])){
        $presenze[$giorno] = [];
    }
    $presenze[$giorno][] = ['ingresso' => strtotime($p['Ingresso']), 'uscita' => strtotime($p['Uscita'])];
}

foreach($presenze as $giorno => $pres_list){
    $days[$giorno] = ['ore' => 0, 'costo' => 0, 'attivita' => []];
    $ore_tot = 0;
    foreach($pres_list as $pres){
        $ore_tot += ($pres['uscita'] - $pres['ingresso']) / 3600;
    }
    $days[$giorno]['ore'] = round($ore_tot, 2);
    $days[$giorno]['costo'] = round($ore_tot * $prezzo, 2);

    // 2️⃣ prendo le attività per la giornata
    $sqlAtt = "
    SELECT p.ID_Attivita, a.Nome, p.Ora_Inizio, p.Ora_Fine, p.ID_Educatore
    FROM partecipa p
    JOIN attivita a ON a.id = p.ID_Attivita
    WHERE p.ID_Ragazzo = $idIscritto AND p.Data = '$giorno'
    ";
    $resAtt = $conn->query($sqlAtt);
    $attivita_data = [];
    while($a = $resAtt->fetch_assoc()){
        $id_att = $a['ID_Attivita'];
        if(!isset($attivita_data[$id_att])){
            $attivita_data[$id_att] = ['nome' => $a['Nome'], 'times' => [], 'educatori' => []];
        }
        $attivita_data[$id_att]['times'][] = ['inizio' => strtotime($giorno . ' ' . $a['Ora_Inizio']), 'fine' => strtotime($giorno . ' ' . $a['Ora_Fine'])];
        $attivita_data[$id_att]['educatori'][$a['ID_Educatore']] = true;
    }
    foreach($attivita_data as $id_att => $data){
        $nome = $data['nome'];
        $num_educatori = count($data['educatori']);
        $total_overlap_seconds = 0;
        foreach($data['times'] as $time){
            foreach($pres_list as $pres){
                $overlap_start = max($time['inizio'], $pres['ingresso']);
                $overlap_end = min($time['fine'], $pres['uscita']);
                $overlap_seconds = max(0, $overlap_end - $overlap_start);
                $total_overlap_seconds += $overlap_seconds;
            }
        }
        $total_overlap_minutes = $total_overlap_seconds / 60;
        if ($num_educatori == 0) {
            $effective_minutes = $total_overlap_minutes;
        } else {
            $effective_minutes = $total_overlap_minutes / $num_educatori;
        }
        $hours = floor($effective_minutes / 60);
        $minutes = $effective_minutes % 60;
        $ore_att = $hours + $minutes / 100;
        $costo_att = round(($effective_minutes / 60) * $prezzo, 2);
        if($ore_att > 0){
            if(!isset($days[$giorno]['attivita'][$nome])){
                $days[$giorno]['attivita'][$nome] = ['ore' => 0, 'costo' => 0];
            }
            $days[$giorno]['attivita'][$nome]['ore'] += $ore_att;
            $days[$giorno]['attivita'][$nome]['costo'] += $costo_att;
        }
    }
}

$rows = [];
foreach($days as $giorno => $data){
    $attivita = [];
    foreach($data['attivita'] as $nome => $vals){
        $attivita[] = [
            'Nome' => $nome,
            'ore' => $vals['ore'],
            'costo' => $vals['costo']
        ];
    }
    $rows[] = [
        'giorno' => $giorno,
        'ore' => $data['ore'],
        'costo' => $data['costo'],
        'attivita' => $attivita
    ];
}

echo json_encode(['success'=>true,'data'=>$rows]);
$conn->close();
?>
