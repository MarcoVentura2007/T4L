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

// 1️⃣ prendo tutte le presenze già avvenute con prepared statement
$stmt = $conn->prepare("
SELECT *
FROM presenza
WHERE ID_Iscritto = ?
AND MONTH(Ingresso) = ?
AND YEAR(Ingresso) = ?
AND Ingresso <= NOW()
ORDER BY Ingresso
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    exit;
}
$stmt->bind_param("iii", $idIscritto, $mese, $anno);
$stmt->execute();
$res = $stmt->get_result();
$presenze = [];
$days = [];

// costo orario del ragazzo con prepared statement
$stmtPrezzo = $conn->prepare("SELECT Prezzo_Orario FROM iscritto WHERE id = ?");
if (!$stmtPrezzo) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
    exit;
}
$stmtPrezzo->bind_param("i", $idIscritto);
$stmtPrezzo->execute();
$resPrezzo = $stmtPrezzo->get_result();
$prezzo = $resPrezzo->fetch_assoc()['Prezzo_Orario'];


while($p = $res->fetch_assoc()){
    // Handle both uppercase and lowercase column names
    $ingresso = isset($p['Ingresso']) ? $p['Ingresso'] : (isset($p['ingresso']) ? $p['ingresso'] : null);
    $uscita = isset($p['Uscita']) ? $p['Uscita'] : (isset($p['uscita']) ? $p['uscita'] : null);
    
    // Skip if uscita is NULL or empty - presence not yet completed

    if(empty($uscita)) continue;
    
    $giorno = date('Y-m-d', strtotime($ingresso));
    
    if(!isset($presenze[$giorno])){
        $presenze[$giorno] = [];
    }
    $presenze[$giorno][] = ['ingresso' => strtotime($ingresso), 'uscita' => strtotime($uscita)];
}






foreach($presenze as $giorno => $pres_list){
    $days[$giorno] = ['ore' => 0, 'costo' => 0, 'attivita' => []];
    $ore_tot = 0;
    foreach($pres_list as $pres){
        $ore_tot += ($pres['uscita'] - $pres['ingresso']) / 3600;
    }


    $days[$giorno]['ore'] = round($ore_tot, 2);
    $days[$giorno]['costo'] = round($ore_tot * $prezzo, 2);

    // 2️⃣ prendo le attività per la giornata con prepared statement
    $stmtAtt = $conn->prepare("
    SELECT p.ID_Attivita, a.Nome, p.Ora_Inizio, p.Ora_Fine, p.ID_Educatore
    FROM partecipa p
    JOIN attivita a ON a.id = p.ID_Attivita
    WHERE p.ID_Ragazzo = ? AND p.Data = ?
    ");
    if (!$stmtAtt) {
        continue;
    }
    $stmtAtt->bind_param("is", $idIscritto, $giorno);
    $stmtAtt->execute();
    $resAtt = $stmtAtt->get_result();

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
$stmt->close();
$stmtPrezzo->close();
if (isset($stmtAtt)) {
    $stmtAtt->close();
}
$conn->close();

?>
