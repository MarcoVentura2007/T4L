<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$idIscritto = intval($data['id']);
list($anno, $mese) = explode('-', $data['mese']);

require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Connessione DB fallita']);
    exit;
}

// --- CONTROLLO RUOLO ---
$stmtClasse = $conn->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
if ($stmtClasse) {
    $stmtClasse->bind_param("s", $_SESSION['username']);
    $stmtClasse->execute();
    $stmtClasse->bind_result($userClasse);
    if ($stmtClasse->fetch()) {
        if ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore') {
            echo json_encode(['success' => false, 'message' => 'Accesso negato.']);
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
    echo json_encode(['success' => false, 'message' => 'Errore controllo permessi']);
    $conn->close();
    exit;
}

// --- Preleva prezzi del ragazzo (individuale e gruppo) ---
$stmtPrezzo = $conn->prepare("SELECT Prezzo_Orario, Prezzo_Orario_Gruppo FROM iscritto WHERE id = ?");
if (!$stmtPrezzo) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare prezzi: ' . $conn->error]);
    exit;
}
$stmtPrezzo->bind_param("i", $idIscritto);
$stmtPrezzo->execute();
$resPrezzo = $stmtPrezzo->get_result()->fetch_assoc();
$stmtPrezzo->close();

$prezzoIndividuale = floatval($resPrezzo['Prezzo_Orario'] ?? 0);
$prezzoGruppo      = floatval($resPrezzo['Prezzo_Orario_Gruppo'] ?? 0);

// --- Presenze del mese già avvenute ---
$stmtPres = $conn->prepare("
    SELECT Ingresso, Uscita
    FROM presenza
    WHERE ID_Iscritto = ?
      AND MONTH(Ingresso) = ?
      AND YEAR(Ingresso) = ?
      AND Ingresso <= NOW()
    ORDER BY Ingresso
");
if (!$stmtPres) {
    echo json_encode(['success' => false, 'message' => 'Errore prepare presenze: ' . $conn->error]);
    exit;
}
$stmtPres->bind_param("iii", $idIscritto, $mese, $anno);
$stmtPres->execute();
$resPres = $stmtPres->get_result();
$stmtPres->close();

// Raggruppa presenze per giorno
$presenze = []; // $presenze[$giorno] = [[ingresso_ts, uscita_ts], ...]
while ($p = $resPres->fetch_assoc()) {
    $ingresso = $p['Ingresso'] ?? $p['ingresso'];
    $uscita   = $p['Uscita']   ?? $p['uscita'];
    if (empty($uscita)) continue;
    $giorno = date('Y-m-d', strtotime($ingresso));
    $presenze[$giorno][] = [
        'ingresso' => strtotime($ingresso),
        'uscita'   => strtotime($uscita),
    ];
}

// --- Per ogni giorno calcola ore totali e attività con prezzo corretto ---
$days = [];

foreach ($presenze as $giorno => $pres_list) {
    // Ore totali di presenza del giorno
    $ore_tot = 0;
    foreach ($pres_list as $pres) {
        $ore_tot += ($pres['uscita'] - $pres['ingresso']) / 3600;
    }

    $days[$giorno] = [
        'ore'      => round($ore_tot, 2),
        'costo'    => 0,   // calcolato dopo per tener conto del gruppo
        'attivita' => [],
    ];

    // --- Attività del ragazzo in questo giorno ---
    // Recupera anche p.gruppo per sapere se era individuale o gruppo
    $stmtAtt = $conn->prepare("
        SELECT p.ID_Attivita,
               a.Nome,
               p.Ora_Inizio,
               p.Ora_Fine,
               p.ID_Educatore,
               p.gruppo AS in_gruppo
        FROM partecipa p
        JOIN attivita a ON a.id = p.ID_Attivita
        WHERE p.ID_Ragazzo = ? AND p.Data = ?
    ");
    if (!$stmtAtt) continue;
    $stmtAtt->bind_param("is", $idIscritto, $giorno);
    $stmtAtt->execute();
    $resAtt = $stmtAtt->get_result();
    $stmtAtt->close();

    // Raggruppa per attività
    $attivita_data = [];
    while ($a = $resAtt->fetch_assoc()) {
        $id_att = $a['ID_Attivita'];
        if (!isset($attivita_data[$id_att])) {
            $attivita_data[$id_att] = [
                'nome'       => $a['Nome'],
                'in_gruppo'  => intval($a['in_gruppo']),  // 1=gruppo, 0=individuale
                'times'      => [],
                'educatori'  => [],
            ];
        }
        $attivita_data[$id_att]['times'][] = [
            'inizio' => strtotime($giorno . ' ' . $a['Ora_Inizio']),
            'fine'   => strtotime($giorno . ' ' . $a['Ora_Fine']),
        ];
        $attivita_data[$id_att]['educatori'][$a['ID_Educatore']] = true;
    }

    // Calcola overlap presenza ↔ attività per ogni attività
    $ore_coperte_da_attivita = 0; // secondi totali coperti da attività (per calcolo costo base residuo)

    foreach ($attivita_data as $id_att => $att) {
        $nome         = $att['nome'];
        $num_educatori = max(1, count($att['educatori']));
        $is_gruppo    = $att['in_gruppo'] === 1;
        $prezzo_att   = $is_gruppo ? $prezzoGruppo : $prezzoIndividuale;

        $total_overlap_sec = 0;
        foreach ($att['times'] as $time) {
            foreach ($pres_list as $pres) {
                $overlap_start = max($time['inizio'], $pres['ingresso']);
                $overlap_end   = min($time['fine'],   $pres['uscita']);
                $overlap_sec   = max(0, $overlap_end - $overlap_start);
                $total_overlap_sec += $overlap_sec;
            }
        }

        $ore_att_sec = $total_overlap_sec / $num_educatori;
        $ore_att     = $ore_att_sec / 3600;
        $costo_att   = round($ore_att * $prezzo_att, 2);

        $ore_coperte_da_attivita += $ore_att_sec;

        if ($ore_att > 0) {
            if (!isset($days[$giorno]['attivita'][$nome])) {
                $days[$giorno]['attivita'][$nome] = ['ore' => 0, 'costo' => 0, 'gruppo' => $is_gruppo];
            }
            $days[$giorno]['attivita'][$nome]['ore']   += round($ore_att, 4);
            $days[$giorno]['attivita'][$nome]['costo'] += $costo_att;
        }
    }

    // Costo totale giornaliero:
    // - ore coperte da attività → già calcolate con il prezzo giusto (gruppo/individuale)
    // - ore residue (presenza senza attività) → prezzo individuale
    $costo_da_attivita = 0;
    foreach ($days[$giorno]['attivita'] as $att) {
        $costo_da_attivita += $att['costo'];
    }
    $ore_residue = max(0, $ore_tot - ($ore_coperte_da_attivita / 3600));
    $costo_residuo = round($ore_residue * $prezzoIndividuale, 2);
    $days[$giorno]['costo'] = round($costo_da_attivita + $costo_residuo, 2);
}

// Costruisci risposta
$rows = [];
foreach ($days as $giorno => $data) {
    $attivita = [];
    foreach ($data['attivita'] as $nome => $vals) {
        $attivita[] = [
            'Nome'    => $nome,
            'ore'     => round($vals['ore'], 2),
            'costo'   => $vals['costo'],
            'gruppo'  => $vals['gruppo'],
        ];
    }
    $rows[] = [
        'giorno'   => $giorno,
        'ore'      => $data['ore'],
        'costo'    => $data['costo'],
        'attivita' => $attivita,
    ];
}

echo json_encode(['success' => true, 'data' => $rows]);
$conn->close();
