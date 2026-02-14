<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache");

if(!isset($_SESSION['username'])){
    echo json_encode(['success'=>false, 'error'=>'Non autorizzato']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if(!isset($data['id']) || !isset($data['mese'])){
    echo json_encode(['success'=>false, 'error'=>'Parametri mancanti']);
    exit;
}

$idIscritto = intval($data['id']);
list($anno, $mese) = explode('-', $data['mese']);

$conn = new mysqli("localhost","root","","time4allergo");
if ($conn->connect_error) {
    echo json_encode(['success'=>false,'error'=>'Connessione DB fallita']);
    exit;
}

// Get hourly rate for the user
$sqlPrezzo = "SELECT Stipendio_Orario FROM iscritto WHERE id = ?";
$stmtPrezzo = $conn->prepare($sqlPrezzo);
$stmtPrezzo->bind_param("i", $idIscritto);
$stmtPrezzo->execute();
$resPrezzo = $stmtPrezzo->get_result();
$prezzoRow = $resPrezzo->fetch_assoc();
$prezzo = $prezzoRow ? floatval($prezzoRow['Stipendio_Orario']) : 0;
$stmtPrezzo->close();

// Get all presences for the user in the specified month
$sql = "
SELECT 
    id,
    Ingresso,
    Uscita
FROM presenza
WHERE ID_Iscritto = ?
    AND MONTH(Ingresso) = ?
    AND YEAR(Ingresso) = ?
    AND Ingresso <= NOW()
ORDER BY Ingresso ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $idIscritto, $mese, $anno);
$stmt->execute();
$res = $stmt->get_result();

$days = [];
while($p = $res->fetch_assoc()){
    $giorno = date('Y-m-d', strtotime($p['Ingresso']));
    
    if(!isset($days[$giorno])){
        $days[$giorno] = [
            'ore' => 0,
            'costo' => 0
        ];
    }
    
    // Calculate hours for this presence
    $ingresso = strtotime($p['Ingresso']);
    $uscita = strtotime($p['Uscita']);
    $ore = ($uscita - $ingresso) / 3600; // Convert seconds to hours
    
    $days[$giorno]['ore'] += $ore;
}

// Round values and calculate costs
foreach($days as $giorno => &$data){
    $data['ore'] = round($data['ore'], 2);
    $data['costo'] = round($data['ore'] * $prezzo, 2);
}

// Format for JSON response
$rows = [];
foreach($days as $giorno => $data){
    $rows[] = [
        'giorno' => $giorno,
        'ore' => $data['ore'],
        'costo' => $data['costo'],
        'attivita' => [] // Empty array for compatibility with existing JS
    ];
}

echo json_encode(['success'=>true,'data'=>$rows]);
$stmt->close();
$conn->close();
?>
