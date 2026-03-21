<?php
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');

header('Content-Type: application/json');

$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Validazione formato data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo json_encode(['success' => false, 'error' => 'Formato data non valido']);
    exit;
}

$dataLike = $data . '%';
$stmt = $conn->prepare("
    SELECT i.fotografia, p.id, i.nome, i.cognome, p.ingresso, p.uscita
    FROM presenza p
    INNER JOIN iscritto i ON p.ID_Iscritto = i.id
    WHERE p.ingresso LIKE ?
    ORDER BY p.ingresso ASC
");
$stmt->bind_param('s', $dataLike);
$stmt->execute();
$result = $stmt->get_result();

$presenze = [];
while ($row = $result->fetch_assoc()) {
    $presenze[] = [
        'id'         => $row['id'],
        'nome'       => $row['nome'],
        'cognome'    => $row['cognome'],
        'fotografia' => $row['fotografia'],
        'ingresso'   => $row['ingresso'],
        'uscita'     => $row['uscita'],
    ];
}

echo json_encode(['success' => true, 'data' => $presenze, 'data_richiesta' => $data]);
