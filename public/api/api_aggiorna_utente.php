<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../data/php_errors.log');

session_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit;
}

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Sessione non valida']);
    exit;
}

// Connessione al DB
require __DIR__ . '/../../data/db_connection.php';
$conn = getDbConnection('time4all');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connessione fallita: ' . $conn->connect_error]);
    exit;
}

// Controllo ruolo
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

// ─── Lettura parametri ────────────────────────────────────────────────────────
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

$id                   = 0;
$nome                 = '';
$cognome              = '';
$data_nascita         = '';
$codice_fiscale       = '';
$email                = '';
$telefono             = '';
$disabilita           = '';
$intolleranze         = '';
$prezzo_orario        = 0.0;
$prezzo_orario_gruppo = 0.0;
$note                 = '';
$gruppo               = null;   // null = non fornito dal client
$fotografia           = null;   // null = non cambiare foto

if (strpos($contentType, 'multipart/form-data') !== false) {

    // ── Richiesta con file upload ────────────────────────────────────────────
    $id                   = intval($_POST['id']                    ?? 0);
    $nome                 = trim($_POST['nome']                    ?? '');
    $cognome              = trim($_POST['cognome']                 ?? '');
    $data_nascita         = trim($_POST['data_nascita']            ?? '');
    $codice_fiscale       = trim($_POST['codice_fiscale']          ?? '');
    $email                = trim($_POST['email']                   ?? '');
    $telefono             = trim($_POST['telefono']                ?? '');
    $disabilita           = trim($_POST['disabilita']              ?? '');
    $intolleranze         = trim($_POST['intolleranze']            ?? '');
    $prezzo_orario        = floatval($_POST['prezzo_orario']       ?? 0);
    $prezzo_orario_gruppo = floatval($_POST['prezzo_orario_gruppo'] ?? 0);
    $note                 = trim($_POST['note']                    ?? '');

    if (isset($_POST['gruppo'])) {
        $gruppo = intval($_POST['gruppo']) === 1 ? 1 : 0;
    }

    // Gestione file foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {

        // Recupera foto precedente per eliminarla dopo
        $oldFotografia = null;
        $stmtOldFoto = $conn->prepare("SELECT Fotografia FROM iscritto WHERE id = ?");
        if ($stmtOldFoto) {
            $stmtOldFoto->bind_param("i", $id);
            $stmtOldFoto->execute();
            $stmtOldFoto->bind_result($oldFotografia);
            $stmtOldFoto->fetch();
            $stmtOldFoto->close();
        }

        $uploadDir = '../immagini/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType     = 'application/octet-stream';

        if (function_exists('finfo_file')) {
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $fileType = finfo_file($finfo, $_FILES['foto']['tmp_name']);
            finfo_close($finfo);
        } else {
            $ext      = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $map      = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
                         'gif'=>'image/gif','webp'=>'image/webp'];
            $fileType = $map[$ext] ?? 'application/octet-stream';
        }

        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Tipo file non valido. Solo immagini.']);
            exit;
        }

        $fileName   = time() . '_' . basename($_FILES['foto']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetPath)) {
            $fotografia = 'immagini/' . $fileName;

            // Elimina vecchia foto
            if ($oldFotografia &&
                $oldFotografia !== 'immagini/default-user.png' &&
                $oldFotografia !== 'default-user.png') {
                $oldPath = __DIR__ . '/../' . $oldFotografia;
                if (file_exists($oldPath)) unlink($oldPath);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nel caricamento del file']);
            exit;
        }
    }

} else {

    // ── Richiesta JSON (senza file) ──────────────────────────────────────────
    $data = json_decode(file_get_contents('php://input'), true);

    $id                   = intval($data['id']                    ?? 0);
    $nome                 = trim($data['nome']                    ?? '');
    $cognome              = trim($data['cognome']                 ?? '');
    $data_nascita         = trim($data['data_nascita']            ?? '');
    $codice_fiscale       = trim($data['codice_fiscale']          ?? '');
    $email                = trim($data['email']                   ?? '');
    $telefono             = trim($data['telefono']                ?? '');
    $disabilita           = trim($data['disabilita']              ?? '');
    $intolleranze         = trim($data['intolleranze']            ?? '');
    $prezzo_orario        = floatval($data['prezzo_orario']       ?? 0);
    $prezzo_orario_gruppo = floatval($data['prezzo_orario_gruppo'] ?? 0);
    $note                 = trim($data['note']                    ?? '');

    if (isset($data['gruppo'])) {
        $gruppo = intval($data['gruppo']) === 1 ? 1 : 0;
    }
}

// Controllo id
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'ID mancante']);
    exit;
}

// Se gruppo non è stato fornito, leggo il valore attuale dal DB
if ($gruppo === null) {
    $stmtTmp = $conn->prepare("SELECT Gruppo FROM iscritto WHERE id = ?");
    if ($stmtTmp) {
        $stmtTmp->bind_param("i", $id);
        $stmtTmp->execute();
        $stmtTmp->bind_result($existingGroup);
        $gruppo = $stmtTmp->fetch() ? (intval($existingGroup) === 1 ? 1 : 0) : 0;
        $stmtTmp->close();
    } else {
        $gruppo = 0;
    }
}

// ─── Costruzione query ────────────────────────────────────────────────────────
if ($fotografia !== null) {

    // UPDATE con foto
    // Colonne: Nome, Cognome, Data_nascita, Codice_fiscale, Email, Telefono,
    //          Disabilita, Allergie_Intolleranze,
    //          Prezzo_Orario (d), Prezzo_Orario_Gruppo (d),
    //          Note, Fotografia,
    //          Gruppo (i)
    //          WHERE id (i)
    // Tipi:    s s s s s s s s d d s s i i   → 14 caratteri
    $sql = "UPDATE iscritto SET
                Nome                  = ?,
                Cognome               = ?,
                Data_nascita          = ?,
                Codice_fiscale        = ?,
                Email                 = ?,
                Telefono              = ?,
                Disabilita            = ?,
                Allergie_Intolleranze = ?,
                Prezzo_Orario         = ?,
                Prezzo_Orario_Gruppo  = ?,
                Note                  = ?,
                Fotografia            = ?,
                Gruppo                = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
        exit;
    }

    // 8 stringhe + 2 double + 2 stringhe + 1 int (gruppo) + 1 int (id) = "ssssssssddssii"
    $stmt->bind_param(
        "ssssssssddssii",
        $nome,
        $cognome,
        $data_nascita,
        $codice_fiscale,
        $email,
        $telefono,
        $disabilita,
        $intolleranze,
        $prezzo_orario,
        $prezzo_orario_gruppo,
        $note,
        $fotografia,
        $gruppo,
        $id
    );

} else {

    // UPDATE senza foto
    // Colonne: Nome, Cognome, Data_nascita, Codice_fiscale, Email, Telefono,
    //          Disabilita, Allergie_Intolleranze,
    //          Prezzo_Orario (d), Prezzo_Orario_Gruppo (d),
    //          Note,
    //          Gruppo (i)
    //          WHERE id (i)
    // Tipi:    s s s s s s s s d d s i i   → 13 caratteri
    $sql = "UPDATE iscritto SET
                Nome                  = ?,
                Cognome               = ?,
                Data_nascita          = ?,
                Codice_fiscale        = ?,
                Email                 = ?,
                Telefono              = ?,
                Disabilita            = ?,
                Allergie_Intolleranze = ?,
                Prezzo_Orario         = ?,
                Prezzo_Orario_Gruppo  = ?,
                Note                  = ?,
                Gruppo                = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Errore prepare: ' . $conn->error]);
        exit;
    }

    // 8 stringhe + 2 double + 1 stringa + 1 int (gruppo) + 1 int (id) = "ssssssssddsi i"
    $stmt->bind_param(
        "ssssssssddsii",
        $nome,
        $cognome,
        $data_nascita,
        $codice_fiscale,
        $email,
        $telefono,
        $disabilita,
        $intolleranze,
        $prezzo_orario,
        $prezzo_orario_gruppo,
        $note,
        $gruppo,
        $id
    );
}

// ─── Esecuzione ──────────────────────────────────────────────────────────────
try {
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Utente aggiornato']);
    } else {
        error_log("api_aggiorna_utente error: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Errore: ' . $stmt->error]);
    }
} catch (Exception $e) {
    error_log("api_aggiorna_utente exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();