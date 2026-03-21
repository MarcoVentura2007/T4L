<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
list($anno, $mese) = explode('-', $input['mese']);

require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Connessione DB fallita']);
    exit;
}

// --- CONTROLLO RUOLO ---
$stmtClasse = $conn->prepare("SELECT classe FROM Account WHERE nome_utente = ?");
$stmtClasse->bind_param("s", $_SESSION['username']);
$stmtClasse->execute();
$stmtClasse->bind_result($userClasse);
if (!$stmtClasse->fetch() || ($userClasse !== 'Contabile' && $userClasse !== 'Amministratore')) {
    echo json_encode(['success' => false, 'message' => 'Accesso negato.']);
    $stmtClasse->close();
    $conn->close();
    exit;
}
$stmtClasse->close();

// --- Lista iscritti con ore totali di presenza ---
$stmtIscritti = $conn->prepare("
    SELECT
        i.id,
        i.Nome,
        i.Cognome,
        i.Fotografia,
        i.Prezzo_Orario,
        i.Prezzo_Orario_Gruppo,
        COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.Ingresso, p.Uscita)) / 60, 0) AS ore_totali
    FROM iscritto i
    LEFT JOIN presenza p
        ON p.ID_Iscritto = i.id
        AND MONTH(p.Ingresso) = ?
        AND YEAR(p.Ingresso) = ?
        AND p.Ingresso <= NOW()
        AND p.Uscita IS NOT NULL
    GROUP BY i.id
    ORDER BY i.Cognome, i.Nome
");
$stmtIscritti->bind_param("ii", $mese, $anno);
$stmtIscritti->execute();
$resIscritti = $stmtIscritti->get_result();
$stmtIscritti->close();

$rows = [];

while ($iscritto = $resIscritti->fetch_assoc()) {
    $idIscritto       = intval($iscritto['id']);
    $prezzoIndividuale = floatval($iscritto['Prezzo_Orario'] ?? 0);
    $prezzoGruppo      = floatval($iscritto['Prezzo_Orario_Gruppo'] ?? 0);
    $oreTotali         = floatval($iscritto['ore_totali']);

    // Se non ha ore questo mese, costo = 0
    if ($oreTotali <= 0) {
        $rows[] = [
            'id'          => $idIscritto,
            'Nome'        => $iscritto['Nome'],
            'Cognome'     => $iscritto['Cognome'],
            'Fotografia'  => $iscritto['Fotografia'],
            'Prezzo_Orario' => $prezzoIndividuale,
            'ore_totali'  => 0,
            'costo'       => 0,
        ];
        continue;
    }

    // --- Recupera presenze del mese ---
    $stmtPres = $conn->prepare("
        SELECT Ingresso, Uscita FROM presenza
        WHERE ID_Iscritto = ? AND MONTH(Ingresso) = ? AND YEAR(Ingresso) = ?
          AND Ingresso <= NOW() AND Uscita IS NOT NULL
        ORDER BY Ingresso
    ");
    $stmtPres->bind_param("iii", $idIscritto, $mese, $anno);
    $stmtPres->execute();
    $resPres = $stmtPres->get_result();
    $stmtPres->close();

    // Raggruppa presenze per giorno
    $presenzePerGiorno = [];
    while ($p = $resPres->fetch_assoc()) {
        $ing = $p['Ingresso'] ?? $p['ingresso'];
        $usc = $p['Uscita']   ?? $p['uscita'];
        if (empty($usc)) continue;
        $giorno = date('Y-m-d', strtotime($ing));
        $presenzePerGiorno[$giorno][] = [
            'ingresso' => strtotime($ing),
            'uscita'   => strtotime($usc),
        ];
    }

    // --- Per ogni giorno calcola costo con prezzo gruppo/individuale ---
    $costoTotale = 0.0;

    foreach ($presenzePerGiorno as $giorno => $presList) {
        // Attività del ragazzo in questo giorno (con flag gruppo)
        $stmtAtt = $conn->prepare("
            SELECT p.ID_Attivita, p.Ora_Inizio, p.Ora_Fine, p.ID_Educatore,
                   p.gruppo AS in_gruppo
            FROM partecipa p
            WHERE p.ID_Ragazzo = ? AND p.Data = ?
        ");
        if (!$stmtAtt) continue;
        $stmtAtt->bind_param("is", $idIscritto, $giorno);
        $stmtAtt->execute();
        $resAtt = $stmtAtt->get_result();
        $stmtAtt->close();

        // Raggruppa per attività
        $attivitaData = [];
        while ($a = $resAtt->fetch_assoc()) {
            $id_att = $a['ID_Attivita'];
            if (!isset($attivitaData[$id_att])) {
                $attivitaData[$id_att] = [
                    'in_gruppo'  => intval($a['in_gruppo']),
                    'times'      => [],
                    'educatori'  => [],
                ];
            }
            $attivitaData[$id_att]['times'][] = [
                'inizio' => strtotime($giorno . ' ' . $a['Ora_Inizio']),
                'fine'   => strtotime($giorno . ' ' . $a['Ora_Fine']),
            ];
            $attivitaData[$id_att]['educatori'][$a['ID_Educatore']] = true;
        }

        // Calcola secondi coperti da attività e relativo costo
        $secondiCopertiDaAttivita = 0;
        $costoAttivita = 0.0;

        foreach ($attivitaData as $att) {
            $numEducatori = max(1, count($att['educatori']));
            $isGruppo     = $att['in_gruppo'] === 1;
            $prezzo       = $isGruppo ? $prezzoGruppo : $prezzoIndividuale;

            $overlapSec = 0;
            foreach ($att['times'] as $time) {
                foreach ($presList as $pres) {
                    $start = max($time['inizio'], $pres['ingresso']);
                    $end   = min($time['fine'],   $pres['uscita']);
                    $overlapSec += max(0, $end - $start);
                }
            }
            $secPerRagazzo = $overlapSec / $numEducatori;
            $costoAttivita += ($secPerRagazzo / 3600) * $prezzo;
            $secondiCopertiDaAttivita += $secPerRagazzo;
        }

        // Ore di presenza senza attività → prezzo individuale
        $oreTotaliGiorno = 0;
        foreach ($presList as $pres) {
            $oreTotaliGiorno += ($pres['uscita'] - $pres['ingresso']) / 3600;
        }
        $oreResidueGiorno = max(0, $oreTotaliGiorno - ($secondiCopertiDaAttivita / 3600));
        $costoTotale += $costoAttivita + ($oreResidueGiorno * $prezzoIndividuale);
    }

    $rows[] = [
        'id'           => $idIscritto,
        'Nome'         => $iscritto['Nome'],
        'Cognome'      => $iscritto['Cognome'],
        'Fotografia'   => $iscritto['Fotografia'],
        'Prezzo_Orario' => $prezzoIndividuale,
        'ore_totali'   => round($oreTotali, 2),
        'costo'        => round($costoTotale, 2),
    ];
}

echo json_encode(['success' => true, 'data' => $rows]);
$conn->close();
