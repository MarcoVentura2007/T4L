<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');
header("Cache-Control: no-cache");
session_start();

function fail($msg, $extra = [])
{
    echo json_encode(array_merge([
        'success' => false,
        'error' => $msg
    ], $extra));
    exit;
}

if (!isset($_SESSION['username'])) {
    fail('Non autorizzato');
}

require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    fail('Connessione DB fallita', ['details' => $conn->connect_error]);
}

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
    echo json_encode(['success' => false, 'message' => 'Errore nel controllo dei permessi']);
    $conn->close();
    exit;
}

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    fail('JSON non valido', [
        'raw'          => $raw,
        'json_error'   => json_last_error_msg(),
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? null
    ]);
}

$data_agenda    = $data['data']        ?? null;
$ora_inizio     = $data['ora_inizio']  ?? null;
$ora_fine       = $data['ora_fine']    ?? null;
$id_attivita    = intval($data['id_attivita'] ?? 0);
$educatori      = $data['educatori']   ?? [];
$ragazzi        = $data['ragazzi']     ?? [];
$ragazzi_gruppo = $data['ragazzi_gruppo'] ?? [];
$note           = trim($data['note']   ?? '');   // <-- NOTE (stringa)

if (!is_array($educatori))      $educatori      = [];
if (!is_array($ragazzi))        $ragazzi        = [];
if (!is_array($ragazzi_gruppo)) $ragazzi_gruppo = [];

$gruppo_map = [];
foreach ($ragazzi_gruppo as $key => $val) {
    $k = intval($key);
    if ($k <= 0) continue;
    $gruppo_map[$k] = (intval($val) === 1) ? 1 : 0;
}

if (!$data_agenda || !$ora_inizio || !$ora_fine || !$id_attivita) {
    fail('Campi obbligatori mancanti');
}
if (empty($educatori) || empty($ragazzi)) {
    fail('Educatori o ragazzi mancanti');
}

$conn->begin_transaction();

try {
    $res = $conn->query("SELECT id FROM attivita WHERE id = $id_attivita");
    if (!$res || $res->num_rows === 0) {
        throw new Exception("Attività non valida");
    }

    $presenze_map = [];
    foreach ($ragazzi as $id_ragazzo) {
        $id_ragazzo = intval($id_ragazzo);
        if ($id_ragazzo <= 0) continue;

        $sql = "
            SELECT id
            FROM presenza
            WHERE ID_Iscritto = $id_ragazzo
              AND DATE(Ingresso) = '$data_agenda'
              AND Ingresso <= '$data_agenda $ora_inizio:00'
              AND Uscita   >= '$data_agenda $ora_fine:00'
            LIMIT 1
        ";
        $res = $conn->query($sql);
        if (!$res) throw new Exception($conn->error);

        $presenze_map[$id_ragazzo] = ($res->num_rows > 0)
            ? $res->fetch_assoc()['id']
            : null;
    }

    if (empty($presenze_map)) {
        throw new Exception("Nessun ragazzo valido trovato");
    }

    $stmtIns = $conn->prepare(
        "INSERT INTO partecipa
         (Data, Ora_Inizio, Ora_Fine, ID_Attivita, ID_Educatore, ID_Presenza,
          presenza_effettiva, ID_Ragazzo, Gruppo, Note)
         VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)"
    );
    if (!$stmtIns) throw new Exception("Prepare INSERT fallito: " . $conn->error);

    $righe_inserite = 0;

    foreach ($educatori as $id_educatore) {
        $id_educatore = intval($id_educatore);

        $chk = $conn->query("SELECT id FROM educatore WHERE id = $id_educatore");
        if (!$chk || $chk->num_rows === 0) {
            throw new Exception("Educatore $id_educatore non valido");
        }

        foreach ($presenze_map as $id_ragazzo => $id_presenza) {
            $id_gruppo     = $gruppo_map[$id_ragazzo] ?? 0;
            $id_pres_bind  = is_null($id_presenza) ? null : intval($id_presenza);

            $stmtIns->bind_param(
                "sssiiiiis",
                $data_agenda,
                $ora_inizio,
                $ora_fine,
                $id_attivita,
                $id_educatore,
                $id_pres_bind,
                $id_ragazzo,
                $id_gruppo,
                $note
            );
            if (!$stmtIns->execute()) {
                throw new Exception($stmtIns->error);
            }
            $righe_inserite++;
        }
    }

    $stmtIns->close();
    $conn->commit();

    echo json_encode([
        'success'         => true,
        'message'         => 'Agenda creata con successo',
        'righe_inserite'  => $righe_inserite
    ]);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    fail('Errore server', ['details' => $e->getMessage()]);
}