<?php
session_start();

// Prendi info account dell'utente loggato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];

// ===============================
//  1️⃣ Connessione al DB Account (vecchio DB)
// ===============================
$hostAccount = "localhost";
$userAccount = "root";
$passAccount = "";
$dbAccount   = "time4all"; // DB dove c'è Account

$connAccount = new mysqli($hostAccount, $userAccount, $passAccount, $dbAccount);
if($connAccount->connect_error){
    die("Connessione DB Account fallita: " . $connAccount->connect_error);
}



$stmtClasse = $connAccount->prepare("SELECT classe, codice_univoco FROM Account WHERE nome_utente = ?");
$stmtClasse->bind_param("s", $username);
$stmtClasse->execute();
$resultClasse = $stmtClasse->get_result();

if($resultClasse && $resultClasse->num_rows > 0){
    $rowClasse = $resultClasse->fetch_assoc();
    $classe = $rowClasse['classe'];
    $codiceUnivoco = $rowClasse['codice_univoco'];
} else {
    $classe = "";
    $codiceUnivoco = "";
}

$stmtClasse->close();
$connAccount->close();

// Se non amministratore → redirect
if($classe !== 'Contabile'){
    header("Location: index.php");
    exit;
}

// ===============================
//  2️⃣ Connessione al nuovo DB time4allergo
// ===============================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "time4allergo";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    die("Connessione DB time4allergo fallita: " . $conn->connect_error);
}

// Preleva iscritti

$sql = "
SELECT 
    id,
    Nome,
    Cognome,
    Fotografia,
    Data_nascita,
    Disabilita,
    Stipendio_Orario,
    Codice_fiscale,
    Email,
    Telefono,
    Note

FROM iscritto
ORDER BY Cognome ASC
";
$result = $conn->query($sql);


// Presenze giornaliere
$stmtPresenze = $conn->prepare("
SELECT 
    i.Fotografia, 
    p.id, 
    i.Nome, 
    i.Cognome, 
    p.Ingresso, 
    p.Uscita 
FROM presenza p 
INNER JOIN iscritto i ON p.ID_Iscritto = i.id 
WHERE DATE(p.Ingresso) = CURDATE()
ORDER BY p.Ingresso ASC
");

$stmtPresenze->execute();
$resultPresenze = $stmtPresenze->get_result();
$stmtPresenze->close();

// Resoconto mensile
$mese = date('m');
$anno = date('Y');
$stmtResoconti = $conn->prepare("
SELECT 
    i.id,
    i.Nome,
    i.Cognome,
    i.Fotografia,
    i.Stipendio_Orario,
    SUM(TIMESTAMPDIFF(MINUTE, p.Ingresso, p.Uscita)) / 60 AS ore_totali
FROM iscritto i
LEFT JOIN presenza p 
    ON p.ID_Iscritto = i.id
    AND MONTH(p.Ingresso) = ?
    AND YEAR(p.Ingresso) = ?
GROUP BY i.id
ORDER BY i.Cognome
");
$stmtResoconti->bind_param("ii", $mese, $anno);
$stmtResoconti->execute();
$resultResoconti = $stmtResoconti->get_result();
$stmtResoconti->close();



// Connessione al nuovo DB rimane aperta per le future operazioni CRUD
?>




<!DOCTYPE html>

<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>T4L | Gestionale utenti</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_mobile_agenda.css">
    <link rel="icon" href="immagini/Icona.ico">
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
<style>
    .animated-button{
        box-shadow: 0 0 0 2px #0b516c;
    }
    .animated-button .circle{
        background-color: #0b516c;
    }

    .btn-primary{
        background: linear-gradient(135deg, #0b516c, #1085b3);
    }
    .btn-primary:hover{
        background: linear-gradient(135deg, #1085b3, #0b516c);
        box-shadow: 0 6px 20px rgba(9, 41, 77, 0.4);
    }
    .btn-primary:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(14, 55, 92, 0.3);
    }

    .check-check{
        stroke: #0b516c;
    }
    .check-circle{
        stroke: #0b516c;
    }

    .edit-field input:focus,
    .edit-field textarea:focus {
        border-color: #0b516c;
        box-shadow: 0 0 0 3px rgba(1, 30, 64, 0.2);
    }

    @media (max-width: 768px){
        .footer-bar{
            display: none;
        }
    }
</style>
</head>

<body>

<!-- LOADER TIKTOK-STYLE - Time4All Branded -->
<div id="page-loader" class="show">
<div class="logo-pulse-loader">
    <div class="logo-pulse-ring"></div>
    <div class="logo-pulse-ring"></div>
    <img src="immagini/TIME4ALL_LOGO-removebg-preview.png" alt="Time4All">
</div>

    <p style="margin-top: 30px; color: #640a35; font-size: 0.9rem; font-weight: 500; letter-spacing: 1px;">Caricamento...</p>
</div>

 

<script src="js/loader.js"></script>

<script src="js/loader.js"></script>


<script src="js/loader.js"></script>

    <!-- NAVBAR -->
    <header class="navbar">

        <div class="user-box" id="userBox">
            <img src="immagini/profilo-ergo.png" alt="Profile">
            <span id="username-nav"><?php echo htmlspecialchars($username); ?></span>

            <div class="user-dropdown" id="userDropdown">
                <a href="#" class="danger" id="logoutBtn">
                    <span class="icon">⏻</span>
                    <span class="text">Logout</span>
                </a>
            </div>
        </div>

        <div class="logout-overlay" id="logoutOverlay"></div>

        

        <div class="logout-modal" id="logoutModal">
            <h3>Conferma logout</h3>
            <p>Sei sicuro di voler uscire dal tuo account?</p>

            <div class="logout-actions">
                <button class="btn-cancel" id="cancelLogout">Annulla</button>
                <button class="btn-logout" id="confirmLogout">Logout</button>
            </div>
        </div>

        <div class="logo-area">
            <a href="centrodiurno.php"><img src="immagini/Logo-centrodiurno.png"></a>
            <a href="index.php"><img src="immagini/TIME4ALL_LOGO-removebg-preview.png"></a>
            <a href="ergoterapeutica.php"><img src="immagini/Logo-Cooperativa-Ergaterapeutica.png"></a>
        </div>

        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="dropdown" id="dropdown">

        <div class="menu-group">

            <div class="menu-main" data-target="centroMenu">
                <img src="immagini/Logo-centrodiurno.png">
                Centro Diurno
            </div>

            <div class="submenu" id="centroMenu">
                <div class="menu-item" data-link="fogliofirme-centro.php">
                    <img src="immagini/foglio-over.png" alt="">
                    Foglio firme
                </div>
                <?php
                        if($classe === 'Educatore'){
                            $gestionalePage = "gestional_utenti.php";
                        } elseif($classe === 'Contabile'){
                            $gestionalePage = "gestionale_contabile.php";
                        } elseif($classe === 'Amministratore') {
                            $gestionalePage = "gestionale_amministratore.php";
                        } else {
                            $gestionalePage = "#"; 
                        }
                    ?>
                <div class="menu-item" data-link="<?php echo $gestionalePage; ?>">
                    <img src="immagini/gestionale-over.png" alt="">
                    Gestionale
                </div>
            </div>

        </div>


        <div class="menu-group">

            <div class="menu-main" data-target="ergoMenu">
                <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png">
                Ergoterapeutica
            </div>

            <div class="submenu" id="ergoMenu">
                <div class="menu-item" data-link="presenze-ergo.php">
                    <img src="immagini/presenze-ergo.png" alt="">
                    Presenze
                </div>

                <?php
                        if($classe === 'Educatore'){
                            $gestionalePageErgo = "gestional_ergo_utenti.php";
                        } elseif($classe === 'Contabile'){
                            $gestionalePageErgo = "gestionale_ergo_contabile.php";
                        } elseif($classe === 'Amministratore') {
                            $gestionalePageErgo = "gestionale_ergo_amministratore.php";
                        } else {
                            $gestionalePageErgo = "#"; 
                        }
                    ?>

                <div class="menu-item" data-link="<?php echo $gestionalePageErgo; ?>">
                    <img src="immagini/gestionale-ergo.png" alt="">
                    Gestionale
                </div>
            </div>

        </div>

    </div>

    </div>
    </header>

    <div class="app-layout">
 
        <!-- SIDEBAR -->
        <aside class="vertical-sidebar">
            <input type="checkbox" role="switch" id="checkbox-input" class="checkbox-input" checked />
            <nav class="sidebar-nav">
                <header>
                    <div class="sidebar__toggle-container">
                        <label tabindex="0" for="checkbox-input" id="label-for-checkbox-input" class="nav__toggle">
                            <span class="toggle--icons" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" class="toggle-svg-icon toggle--open">
                                    <path d="M3 5a1 1 0 1 0 0 2h18a1 1 0 1 0 0-2zM2 12a1 1 0 0 1 1-1h18a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1M2 18a1 1 0 0 1 1-1h18a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1"></path>
                                </svg>
                                <svg width="24" height="24" viewBox="0 0 24 24" class="toggle-svg-icon toggle--close">
                                    <path d="M18.707 6.707a1 1 0 0 0-1.414-1.414L12 10.586 6.707 5.293a1 1 0 0 0-1.414 1.414L10.586 12l-5.293 5.293a1 1 0 1 0 1.414 1.414L12 13.414l5.293 5.293a1 1 0 0 0 1.414-1.414L13.414 12z"></path>
                                </svg>
                            </span>
                        </label>
                    </div>
                    <figure>
                        <img class="sidebar-logo" src="immagini/TIME4ALL_LOGO-removebg-preview.png" alt="Logo" />
                    </figure>
                </header>
                <section class="sidebar__wrapper">
                    <ul class="sidebar__list list--primary">
                        <li class="sidebar__item item--heading">
                            <h2 class="sidebar__item--heading">Pagine</h2>
                        </li>
                        <li class="sidebar__item">
                            <a class="sidebar__link tab-link active" href="#" data-tab="tab-utenti" data-tooltip="Utenti">
                                <span class="sidebar-icon"><img src="immagini/group.png" alt=""></span>
                                <span class="text">Utenti</span>
                            </a>
                        </li>
                        <li class="sidebar__item">
                            <a class="sidebar__link tab-link" href="#" data-tab="tab-presenze" data-tooltip="Presenze">
                                <span class="sidebar-icon"><img src="immagini/attendance.png" alt=""></span>
                                <span class="text">Presenze</span>
                            </a>
                        </li>
                        <li>
                            <hr/>
                        </li>
                        <li class="sidebar__item item--heading">
                            <h2 class="sidebar__item--heading">Gestione</h2>
                        </li>
                        

                        <li class="sidebar__item">
                            <a class="sidebar__link tab-link" href="#" data-tab="tab-resoconti" data-tooltip="Resoconti">
                                <span class="sidebar-icon"><img src="immagini/resoconti.png" alt=""></span>
                                <span class="text">Resoconti</span>
                            </a>
                        </li>




                    </ul>

                </section>
            </nav>
        </aside>

        



        <main class="main-content">
            <div class="main-container">
            <!-- TAB UTENTI -->
            <div class="page-tab active" id="tab-utenti">
                <button class="animated-button" id="aggiungi-utente-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-2" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>

                    <span class="text">Aggiungi Utente</span>
                    <span class="circle"></span>

                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-1" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="black" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                </button>

                <!-- Modal Aggiungi Utente -->
                <div class="modal-box large" id="modalAggiungiUtente">
                    <h3>Aggiungi nuovo utente</h3>
                    <form id="formAggiungiUtente">
                        <div class="edit-field">
                            <label>Nome</label>
                            <input type="text" id="utenteNome" placeholder="Nome" required>
                        </div>
                        <div class="edit-field">
                            <label>Cognome</label>
                            <input type="text" id="utenteCognome" placeholder="Cognome" required>
                        </div>
                        <div class="edit-field">
                            <label>Data di nascita</label>
                            <input type="date" id="utenteData" required>
                        </div>
                        <div class="edit-field">
                            <label>Codice Fiscale</label>
                            <input type="text" id="utenteCF" placeholder="Codice Fiscale" required>
                        </div>
                        <div class="edit-field">
                            <label>Fotografia</label>

                            <div class="file-inline" id="fileContainer">

                                <input type="file" id="utenteFoto" accept="image/*" hidden>

                                <button type="button" class="file-btn-minimal"
                                    onclick="document.getElementById('utenteFoto').click()">
                                    Scegli file
                                </button>

                                <div class="file-preview-container">
                                    <img id="previewFotoMini" class="preview-mini" style="display:none;">
                                    <button type="button" id="clearFileBtn" class="clear-file-btn" title="Rimuovi file">&times;</button>
                                </div>

                                <span class="file-name" id="nomeFileFoto">Nessun file</span>

                            </div>
                        </div>
                        <div class="edit-field">
                            <label>Email</label>
                            <input type="email" id="utenteEmail" placeholder="Email">
                        </div>
                        <div class="edit-field">
                            <label>Telefono</label>
                            <input type="tel" id="utenteTelefono" placeholder="Telefono">
                        </div>

                        <div class="edit-field">
                            <label>Disabilità</label>
                            <input type="text" id="utenteDisabilita" placeholder="Disabilità">
                        </div>
                        <div class="edit-field">
                            <label>Intolleranze / Allergie</label>
                            <input type="text" id="utenteIntolleranze" placeholder="Intolleranze / Allergie">
                        </div>
                        <div class="edit-field">
                            <label>Stipendio orario (€)</label>
                            <input type="number" id="utentePrezzo" placeholder="Stipendio orario" step="0.01">
                        </div>
                        <div class="edit-field">
                            <label>Note</label>
                            <textarea id="utenteNote"></textarea>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal(document.getElementById('modalAggiungiUtente'))">Chiudi</button>
                            <button type="button" class="btn-primary" id="salvaNuovoUtente">Salva</button>
                        </div>
                    </form>
                </div>

                <div class="page-header">
                    <h1>Utenti</h1>
                    <p>Elenco iscritti registrati</p>
                </div>

                <div class="users-table-box">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Fotografia</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Data di nascita</th>
                                <th>Email</th>
                                <th>Telefono</th>
                                <th>Disabilità</th>

                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if($result && $result->num_rows > 0){
                            while($row = $result->fetch_assoc()){
                                echo '<tr '
                                    .'data-id="'.htmlspecialchars($row['id']).'" '
                                    .'data-nome="'.htmlspecialchars($row['Nome']).'" '
                                    .'data-cognome="'.htmlspecialchars($row['Cognome']).'" '
                                    .'data-nascita="'.htmlspecialchars($row['Data_nascita']).'" '
                                    .'data-cf="'.htmlspecialchars($row['Codice_fiscale']).'" '
                                    .'data-email="'.htmlspecialchars($row['Email']).'" '
                                    .'data-telefono="'.htmlspecialchars($row['Telefono']).'" '
                                    .'data-disabilita="'.htmlspecialchars($row['Disabilita']).'" '

                                    .'data-intolleranze="'.htmlspecialchars($row['Allergie_intolleranze'] ?? '').'" '
                                    .'data-prezzo="'.htmlspecialchars($row['Stipendio_Orario']).'" '
                                    .'data-note="'.htmlspecialchars($row['Note']).'"
                                >
                                    <td><img class="user-avatar" src="'.$row['Fotografia'].'"></td>
                                    <td>'.htmlspecialchars($row['Nome']).'</td>
                                    <td>'.htmlspecialchars($row['Cognome']).'</td>
                                    <td>'.htmlspecialchars($row['Data_nascita']).'</td>
                                    <td>'.htmlspecialchars($row['Email']).'</td>
                                    <td>'.htmlspecialchars($row['Telefono']).'</td>
                                    <td>'.htmlspecialchars($row['Disabilita']).'</td>

                                    <td>'
                                        .'<button class="view-btn"><img src="immagini/open-eye.png" alt="Visualizza"></button>'
                                        .'<button class="edit-utente-btn"><img src="immagini/edit.png" alt="Modifica"></button>'
                                        .'<button class="delete-utente-btn"><img src="immagini/delete.png" alt="Elimina"></button>'
                                    .'</td>'

                                .'</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="10">Nessun utente registrato.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Add Button -->
                <button class="animated-button mobile-add-btn" id="aggiungi-utente-btn-mobile">
                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-2" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                    <span class="text">Aggiungi Utente</span>
                    <span class="circle"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-1" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="black" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                </button>

                <!-- Modal Modifica Utente -->

                <div class="modal-box large" id="modalModificaUtente">
                    <h3 class="modal-title">Modifica utente</h3>
                    <form id="formModificaUtente">
                        <input type="hidden" id="editUtenteId">
                        <div class="edit-field">
                            <label>Nome</label>
                            <input type="text" id="editUtenteNome" required>
                        </div>
                        <div class="edit-field">
                            <label>Cognome</label>
                            <input type="text" id="editUtenteCognome" required>
                        </div>
                        <div class="edit-field">
                            <label>Data di nascita</label>
                            <input type="date" id="editUtenteData" required>
                        </div>
                        <div class="edit-field">
                            <label>Codice Fiscale</label>
                            <input type="text" id="editUtenteCF" required>
                        </div>
                        <div class="edit-field">
                            <label>Email</label>
                            <input type="email" id="editUtenteEmail">
                        </div>
                        <div class="edit-field">
                            <label>Telefono</label>
                            <input type="tel" id="editUtenteTelefono">
                        </div>

                        <div class="edit-field">
                            <label>Disabilità</label>
                            <input type="text" id="editUtenteDisabilita">
                        </div>
                        <div class="edit-field">
                            <label>Intolleranze / Allergie</label>
                            <input type="text" id="editUtenteIntolleranze">
                        </div>
                        <div class="edit-field">
                            <label>Stipendio orario (€)</label>
                            <input type="number" id="editUtentePrezzo" step="0.01">
                        </div>
                        <div class="edit-field">
                            <label>Note</label>
                            <textarea id="editUtenteNote"></textarea>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal(document.getElementById('modalModificaUtente'))">Chiudi</button>
                            <button type="button" class="btn-primary" id="salvaModificaUtente">Salva</button>
                        </div>
                    </form>
                </div>

                <!-- Modal Delete Utente -->
                <div class="modal-box danger" id="modalDeleteUtente">
                    <h3>Elimina utente</h3>
                    <p>Questa azione è definitiva. Vuoi continuare?</p>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal(document.getElementById('modalDeleteUtente'))">Annulla</button>
                        <button type="button" class="btn-danger" id="confirmDeleteUtente">Elimina</button>
                    </div>
                </div>

                <!-- Modal Visualizza Utente -->
                <div class="modal-box large" id="viewModal">
                    <div class="profile-header">
                        <img id="viewAvatar" class="profile-avatar">
                        <div class="profile-main">
                            <h3 id="viewFullname"></h3>
                            <span id="viewBirth"></span>
                        </div>
                    </div>
                    <div class="profile-grid" id="viewContent"></div>
                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal(document.getElementById('viewModal'))">Chiudi</button>
                    </div>
                </div>
            </div>








            <!-- TAB PRESENZE -->
            <div class="page-tab" id="tab-presenze">
                <div class="page-header">
                    <h1>Presenze</h1>
                    <p>Elenco presenze giornaliere</p>
                </div>

                <div class="presenze-controls">
                    
                </div>

                <div class="users-table-box">
                    <table class="users-table" id="presenzeTable">
                        <thead>
                            <tr>
                                <th>Fotografia</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Ingresso</th>
                                <th>Uscita</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if($resultPresenze && $resultPresenze->num_rows > 0){
                            while($row = $resultPresenze->fetch_assoc()){
                                echo '
                                    <tr
                                        data-id="'.htmlspecialchars($row['id']).'"
                                        data-nome="'.htmlspecialchars($row['Nome']).'"
                                        data-cognome="'.htmlspecialchars($row['Cognome']).'"
                                        data-ingresso="'.htmlspecialchars($row['Ingresso']).'"
                                        data-uscita="'.htmlspecialchars($row['Uscita']).'"
                                    >
                                        <td><img class="user-avatar" src="'.$row['Fotografia'].'"></td>
                                        <td>'.htmlspecialchars($row['Nome']).'</td>
                                        <td>'.htmlspecialchars($row['Cognome']).'</td>
                                        <td>'.htmlspecialchars($row['Ingresso']).'</td>
                                        <td>'.htmlspecialchars($row['Uscita']).'</td>
                                        <td>
                                            <button class="edit-presenza-btn" data-id="'.htmlspecialchars($row['id']).'"><img src="immagini/edit.png" alt="Modifica"></button>
                                            <button class="delete-presenza-btn" data-id="'.htmlspecialchars($row['id']).'"><img src="immagini/delete.png" alt="Elimina"></button>
                                        </td>
                                    </tr>
                                ';
                            }
                        } else {
                            echo '<tr><td colspan="6">Nessuna presenza registrata oggi.</td></tr>';
                        }

                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

















            


            


            <!-- TAB RESOCONTI -->
            <div class="page-tab" id="tab-resoconti">

                <div class="page-header" style="margin-bottom: 20px;">
                    <h1>Resoconti</h1>
                    <p>Riepilogo mensile iscritti</p>
                </div>

                <div class="resoconti-mese-label">
                    <label>Seleziona mese: </label>
                    <input type="month" id="resocontiMeseFiltro" value="<?= date('Y-m') ?>">
                </div>

                

                <div class="users-table-box">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Ore totali</th>
                                <th>Stipendio totale (€)</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>

                        <tbody id="resocontiMensiliBody">
                            <tr><td colspan="6">Caricamento...</td></tr>
                        </tbody>
                    </table>
                </div>
                

                <div class="modal-box large modal-resoconto" id="modalResocontoGiorni">

                    <h3 class="modal-title" id="resocontoNome"></h3>

                    <div class="edit-field">
                        <label>Mese</label>
                        <input type="text" id="resocontoMese">
                    </div>

                    <!-- NUOVO LAYOUT CALENDARIO + ATTIVITÀ -->
                    <div class="resoconto-calendar-wrapper">
                        <div class="calendar-section">
                            <div id="resocontoContent" class="mobile-calendar"></div>
                        </div>
                        <div class="activities-section">
                            <div id="mc-activities-panel" class="mc-activities-panel">
                                <div class="mc-activities-placeholder">
                                    Seleziona un giorno per vedere le attività
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="users-table-box" style="display:none">
                        <table class="users-table">
                            <tbody id="resocontoGiorniBody"></tbody>
                        </table>
                    </div>

                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
                    </div>
                </div>

            </div>
















            






            <div class="main-container">
        </main>
    </div>
    
            <!-- POPUP CONFERMA FIRMA -->
                <div class="popup success-popup" id="successPopup">
                    <div class="success-content">
                        <div class="success-icon">
                        <svg viewBox="-2 -2 56 56">
                            <circle class="check-circle" cx="26" cy="26" r="25" fill="none"/>
                            <path class="check-check" d="M14 27 L22 35 L38 19" fill="none"/>
                        </svg>
                        </div>
                        <p class="success-text" id="success-text">Utente modificato!!</p>
                    </div>
                </div>

                <!-- EDIT MODAL -->
                <div class="modal-box large" id="editModal">
                        <h3 class="modal-title" id="modalEditTitle">Modifica utente</h3>

                        <div class="profile-header" id="profileHeader" style="display: none;">
                            <img id="viewAvatar-mod" class="profile-avatar">
                            <div class="profile-main">
                                <h3 id="viewFullname-mod"></h3>
                                <span id="viewBirth-mod"></span>
                            </div>
                        </div>

                        <div class="edit-grid" id="editContent">
                            <!-- Riempito da JS -->
                            <div class="edit-field" id="fieldNome">
                                <label>Nome</label>
                                <input type="text" id="editNome" placeholder="Nome">
                            </div>
                            <div class="edit-field" id="fieldCognome">
                                <label>Cognome</label>
                                <input type="text" id="editCognome" placeholder="Cognome">
                            </div>
                            <div class="edit-field" id="fieldData">
                                <label>Data di nascita</label>
                                <input type="date" id="editData">
                            </div>
                            <div class="edit-field" id="fieldCF">
                                <label>Codice Fiscale</label>
                                <input type="text" id="editCF" placeholder="Codice Fiscale">
                            </div>
                            <div class="edit-field" id="fieldEmail">
                                <label>Email</label>
                                <input type="email" id="editEmail" placeholder="Email">
                            </div>
                            <div class="edit-field" id="fieldTelefono">
                                <label>Telefono</label>
                                <input type="tel" id="editTelefono" placeholder="Telefono">
                            </div>

                            <div class="edit-field" id="fieldDisabilita">
                                <label>Disabilità</label>
                                <input type="text" id="editDisabilita" placeholder="Disabilità">
                            </div>
                            <div class="edit-field" id="fieldIntolleranze">
                                <label>Intolleranze</label>
                                <input type="text" id="editIntolleranze" placeholder="Intolleranze">
                            </div>
                            <div class="edit-field" id="fieldPrezzo">
                                <label>Prezzo orario</label>
                                <input type="number" id="editPrezzo" placeholder="Prezzo in €" step="0.01">
                            </div>
                            <div class="edit-field" id="fieldNote">
                                <label>Note</label>
                                <textarea id="editNote" placeholder="Note"></textarea>
                            </div>
                            <div class="edit-field" id="fieldIngresso" style="display: none;">
                                <label>Ingresso (ora)</label>
                                <input type="time" id="editIngresso" placeholder="Ingresso">
                            </div>
                            <div class="edit-field" id="fieldUscita" style="display: none;">
                                <label>Uscita (ora)</label>
                                <input type="time" id="editUscita" placeholder="Uscita">
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
                            <button class="btn-primary" id="saveEdit">Salva</button>
                        </div>
                    
                </div>


                <!-- DELETE USER -->
                <div class="modal-box danger" id="deleteModal">
                    <h3>Elimina utente</h3>
                    <h3></h3>
                    <p>Questa azione è definitiva. Vuoi continuare?</p>

                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal()">Annulla</button>
                        <button class="btn-danger">Elimina</button>
                    </div>
                </div>

           
            </div>

        </main>
        
 <footer class="footer-bar" style="bottom: auto;">
                <div class="footer-left" >© Time4All • 2026</div>
                <div class="footer-top">
                    <a href="#top" class="footer-image"></a>
                </div>
                <div class="footer-right">
                    <a href="privacy_policy.php" class="hover-underline-animation">PRIVACY POLICY</a>
                </div>
            </footer>

    <!-- MOBILE BOTTOM NAVIGATION -->
    <nav class="mobile-bottom-nav">
        <a href="#" class="mobile-nav-item active" data-tab="tab-utenti" onclick="switchTab('tab-utenti', this); return false;">
            <div class="mobile-nav-icon">
                <img src="immagini/group.png" alt="Utenti">
            </div>
            <span class="mobile-nav-label">Utenti</span>
        </a>
        <a href="#" class="mobile-nav-item" data-tab="tab-presenze" onclick="switchTab('tab-presenze', this); return false;">
            <div class="mobile-nav-icon">
                <img src="immagini/attendance.png" alt="Presenze">
            </div>
            <span class="mobile-nav-label">Presenze</span>
        </a>
        <a href="#" class="mobile-nav-item" data-tab="tab-resoconti" onclick="switchTab('tab-resoconti', this); return false;">
            <div class="mobile-nav-icon">
                <img src="immagini/resoconti.png" alt="Resoconti">
            </div>
            <span class="mobile-nav-label">Resoconti</span>
        </a>
    </nav>

    <!-- OVERLAY PRINCIPALE PER MODALI -->
    <div class="modal-overlay" id="Overlay"></div>
    

    <script>
    document.querySelectorAll(".tab-link").forEach(link => {
        link.addEventListener("click", e => {
            e.preventDefault();
            const target = e.currentTarget.dataset.tab;

            document.querySelectorAll(".tab-link").forEach(l => l.classList.remove("active"));
            document.querySelectorAll(".page-tab").forEach(tab => tab.classList.remove("active"));

            e.currentTarget.classList.add("active");
            document.getElementById(target).classList.add("active");

            localStorage.setItem("activeTab", target);
        });
    });

    // Mobile tab switching function
    function switchTab(tabId, navItem) {
        // Update active states on mobile nav
        document.querySelectorAll('.mobile-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        navItem.classList.add('active');

        // Update desktop sidebar active states
        document.querySelectorAll('.tab-link').forEach(link => {
            link.classList.remove('active');
            if(link.dataset.tab === tabId) {
                link.classList.add('active');
            }
        });

        // Switch tab content
        document.querySelectorAll('.page-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.getElementById(tabId).classList.add('active');

        // Save to localStorage
        localStorage.setItem("activeTab", tabId);
    }

    // Sync mobile nav with desktop on load and restore active tab
    window.addEventListener("DOMContentLoaded", () => {
        const savedTab = localStorage.getItem("activeTab");
        if (savedTab) {
            // Update mobile nav active state
            const mobileNavItem = document.querySelector(`.mobile-nav-item[data-tab="${savedTab}"]`);
            if (mobileNavItem) {
                document.querySelectorAll('.mobile-nav-item').forEach(item => item.classList.remove('active'));
                mobileNavItem.classList.add('active');
            }
            
            // Update desktop sidebar active state
            document.querySelectorAll('.tab-link').forEach(link => {
                link.classList.remove('active');
                if(link.dataset.tab === savedTab) {
                    link.classList.add('active');
                }
            });
            
            // Show the saved tab content
            document.querySelectorAll('.page-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            const savedTabContent = document.getElementById(savedTab);
            if (savedTabContent) {
                savedTabContent.classList.add('active');
            }
        }
    });



            /* HAMBURGER */
        const ham = document.getElementById("hamburger");
        const drop = document.getElementById("dropdown");
        
        if (ham) {
            ham.onclick = () => {
                ham.classList.toggle("active");
                drop.classList.toggle("show");
            };
        }
        
        document.querySelectorAll(".menu-main").forEach(main => {
            main.addEventListener("click", () => {

                const targetId = main.dataset.target;
                const targetMenu = document.getElementById(targetId);

                document.querySelectorAll(".submenu").forEach(menu => {
                    if(menu !== targetMenu){
                        menu.classList.remove("open");
                        menu.previousElementSibling.classList.remove("open"); 
                    }
                });

                targetMenu.classList.toggle("open");
                main.classList.toggle("open"); 
            });
        });

    document.querySelectorAll(".menu-item").forEach(item => {
        item.onclick = () => {
            window.location.href = item.dataset.link;
        }
    });

    


        /* USER DROPDOWN */
        const userBox = document.getElementById("userBox");
        const userDropdown = document.getElementById("userDropdown");
        userBox.addEventListener("click", (e)=>{
            e.stopPropagation();
            userDropdown.classList.toggle("show");
        });
        document.addEventListener("click",(e)=>{
            if(!userBox.contains(e.target)){
                userDropdown.classList.remove("show");
            }
        });




        /* LOGOUT */
        const logoutBtn = document.getElementById("logoutBtn");
        const logoutOverlay = document.getElementById("logoutOverlay");
        const logoutModal = document.getElementById("logoutModal");
        const cancelLogout = document.getElementById("cancelLogout");
        const confirmLogout = document.getElementById("confirmLogout");

        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            logoutOverlay.classList.add("show");
            logoutModal.classList.add("show");
        });

        cancelLogout.onclick = closeLogout;
        logoutOverlay.onclick = closeLogout;
        function closeLogout(){
            logoutOverlay.classList.remove("show");
            logoutModal.classList.remove("show");
        }

        confirmLogout.onclick = () => {
            window.location.href = "logout.php";
        };

    // Modal (use the global `Overlay` element for modal backdrop)
    const Overlay = document.getElementById("Overlay");
    const viewModal = document.getElementById("viewModal");
    const editModal = document.getElementById("editModal");
    const deleteModal = document.getElementById("deleteModal");

    const successText = document.getElementById("success-text");
    const successPopup = document.getElementById("successPopup");

    function openModal(modal){
        if(!modal) return;
        modal.classList.add("show");
        if(Overlay) Overlay.classList.add("show");
    }
    
    function closeModal(modal){
        if(Overlay) Overlay.classList.remove("show");
        if(modal){
            modal.classList.remove("show");
        } else {
            document.querySelectorAll(".modal-box.show").forEach(el => el.classList.remove("show"));
        }
    }


    function showSuccess(popup, overlay) {
        if (popup) popup.classList.add("show");
        if (overlay) overlay.classList.add("show");
    }
    function hideSuccess(popup, overlay) {
        if (popup) popup.classList.remove("show");
        if (overlay) overlay.classList.remove("show");
    }


    if(Overlay) Overlay.onclick = () => closeModal();

    // Popup view
    document.querySelectorAll(".view-btn").forEach(btn=>{
        btn.onclick = e=>{
            const row = e.target.closest("tr");
            const avatar = row.querySelector("img").src;
            const nome = row.dataset.nome;
            const cognome = row.dataset.cognome;
            const data = row.dataset.nascita;
            const cf = row.dataset.cf;
            const email = row.dataset.email;
            const telefono = row.dataset.telefono;
            const disabilita = row.dataset.disabilita;

            const intolleranze = row.dataset.intolleranze;
            const prezzo = row.dataset.prezzo;
            const note = row.dataset.note;

            document.getElementById("viewAvatar").src = avatar;
            document.getElementById("viewFullname").innerText = nome + " " + cognome;
            document.getElementById("viewBirth").innerText = "Nato il " + data;

            document.getElementById("viewContent").innerHTML = `
                <div class="profile-field"><label>Nome</label><span>${nome}</span></div>
                <div class="profile-field"><label>Cognome</label><span>${cognome}</span></div>
                <div class="profile-field"><label>Data di nascita</label><span>${data}</span></div>
                <div class="profile-field"><label>Codice Fiscale</label><span>${cf}</span></div>
                <div class="profile-field"><label>Email</label><span>${email || '-'}</span></div>
                <div class="profile-field"><label>Telefono</label><span>${telefono || '-'}</span></div>

                <div class="profile-field"><label>Disabilità</label><span>${disabilita || '-'}</span></div>
                <div class="profile-field"><label>Intolleranze / Allergie</label><span>${intolleranze || '-'}</span></div>
                <div class="profile-field"><label>Stipendio orario</label><span>${prezzo ? prezzo + ' €' : '-'}</span></div>
                <div class="profile-field"><label>Note</label><span>${note || '-'}</span></div>
            `;
            openModal(viewModal);
        }
    });

    // Presenze: edit/delete handlers
    document.addEventListener('click', function(e){
        // Edit presenza
        if(e.target.closest && e.target.closest('.edit-presenza-btn')){
            const btn = e.target.closest('.edit-presenza-btn');
            const row = btn.closest('tr');
            const id = row.dataset.id;
            const nome = row.dataset.nome;
            const cognome = row.dataset.cognome;
            const ingresso = row.dataset.ingresso || '';
            const uscita = row.dataset.uscita || '';

            // Nasconde i campi utente e mostra quelli presenza
            document.getElementById('profileHeader').style.display = 'none';
            document.getElementById('fieldNome').style.display = 'none';
            document.getElementById('fieldCognome').style.display = 'none';
            document.getElementById('fieldData').style.display = 'none';
            document.getElementById('fieldCF').style.display = 'none';
            document.getElementById('fieldEmail').style.display = 'none';
            document.getElementById('fieldTelefono').style.display = 'none';

            document.getElementById('fieldDisabilita').style.display = 'none';
            document.getElementById('fieldIntolleranze').style.display = 'none';
            document.getElementById('fieldPrezzo').style.display = 'none';
            document.getElementById('fieldNote').style.display = 'none';
            document.getElementById('fieldIngresso').style.display = 'block';
            document.getElementById('fieldUscita').style.display = 'block';

            // Set modal title
            document.getElementById('modalEditTitle').innerText = 'Modifica Presenza - ' + nome + ' ' + cognome;

            // Set modal data
            editModal.dataset.editType = 'presenza';
            editModal.dataset.presenzeId = id;

            // Estrae solo l'ora dal formato DB (YYYY-MM-DD HH:MM:SS)
            const ingressoTime = ingresso.split(' ')[1]?.slice(0, 5) || '';
            const uscitaTime = uscita.split(' ')[1]?.slice(0, 5) || '';

            document.getElementById('editIngresso').value = ingressoTime;
            document.getElementById('editUscita').value = uscitaTime;

            openModal(editModal);
        }

        // Delete presenza
        if(e.target.closest && e.target.closest('.delete-presenza-btn')){
            const btn = e.target.closest('.delete-presenza-btn');
            const row = btn.closest('tr');
            const id = row.dataset.id;
            const nome = row.dataset.nome;
            const cognome = row.dataset.cognome;

            document.getElementById('deleteModal').querySelector('h3').innerText = 'Eliminazione Presenza - ' + nome + ' ' + cognome;
            deleteModal.dataset.deleteType = 'presenza';
            deleteModal.dataset.presenzeId = id;
            openModal(deleteModal);

            const confirmDelete = deleteModal.querySelector('.btn-danger');
            confirmDelete.onclick = () => {
                fetch('api/api_elimina_presenza_ergo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ id: id })
                }).then(r => r.json()).then(data => {
                    if(data.success){
                        deleteModal.classList.remove('show');
                        successText.innerText = 'Presenza Eliminata!!';
                        showSuccess(successPopup, Overlay);
                        setTimeout(()=>{
                            closeModal();
                            hideSuccess(successPopup, Overlay);
                            location.reload();
                        }, 1800);
                    } else {
                        alert('Errore: ' + data.message);
                    }
                }).catch(err => { console.error(err); alert('Errore richiesta'); });
            };
        }
    });


        document.querySelectorAll(".edit-btn").forEach(btn=>{
            btn.onclick = e=>{
                const row = e.target.closest("tr");

                const avatar = row.querySelector("img").src;
                const nome = row.dataset.nome;
                const cognome = row.dataset.cognome;
                const data = row.dataset.nascita;
                const idIscritto = row.dataset.id;

                editModal.dataset.userId = idIscritto;
                editModal.dataset.editType = 'utente';

                // Mostra i campi utente e nascondi quelli presenza
                document.getElementById('profileHeader').style.display = 'block';
                document.getElementById('fieldNome').style.display = 'block';
                document.getElementById('fieldCognome').style.display = 'block';
                document.getElementById('fieldData').style.display = 'block';
                document.getElementById('fieldCF').style.display = 'block';
                document.getElementById('fieldEmail').style.display = 'block';
                document.getElementById('fieldTelefono').style.display = 'block';

                document.getElementById('fieldDisabilita').style.display = 'block';
                document.getElementById('fieldIntolleranze').style.display = 'block';
                document.getElementById('fieldPrezzo').style.display = 'block';
                document.getElementById('fieldNote').style.display = 'block';
                document.getElementById('fieldIngresso').style.display = 'none';
                document.getElementById('fieldUscita').style.display = 'none';

                // Set modal title
                document.getElementById('modalEditTitle').innerText = 'Modifica utente';

                document.getElementById("viewAvatar-mod").src = avatar;
                document.getElementById("viewFullname-mod").innerText = nome + " " + cognome;
                document.getElementById("viewBirth-mod").innerText = "Nato il " + data;

                document.getElementById("editNome").value = row.dataset.nome;
                document.getElementById("editCognome").value = row.dataset.cognome;
                document.getElementById("editData").value = row.dataset.nascita;
                document.getElementById("editCF").value = row.dataset.cf;
                document.getElementById("editEmail").value = row.dataset.email;
                document.getElementById("editTelefono").value = row.dataset.telefono;

                document.getElementById("editDisabilita").value = row.dataset.disabilita;
                document.getElementById("editIntolleranze").value = row.dataset.intolleranze;
                document.getElementById("editPrezzo").value = row.dataset.prezzo;
                document.getElementById("editNote").value = row.dataset.note;

                openModal(editModal);
            }
        });
        succesPopupDelete = document.getElementById("successPopupDelete");

        document.querySelectorAll(".delete-btn").forEach(btn=>{
            btn.onclick = ()=>{
                const row = btn.closest("tr");
                const nomeCompleto = row.dataset.nome + " " + row.dataset.cognome;

                document.getElementById("deleteModal").querySelector("h3").innerText = "Eliminazione " + btn.closest("tr").dataset.nome + " " + btn.closest("tr").dataset.cognome;
                deleteModal.dataset.deleteType = 'utente';
                deleteModal.dataset.userId = row.dataset.id;
                openModal(deleteModal);

                // Imposta il listener sul bottone "Elimina" nella modale
                const confirmDelete = deleteModal.querySelector(".btn-danger");
                confirmDelete.onclick = () => {
                    fetch("api/api_elimina_utente.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        body: JSON.stringify({ id_iscritto: row.dataset.id })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            deleteModal.classList.remove("show");
                            successText.innerText = "Utente Eliminato!! ";
                            showSuccess(successPopup, Overlay);

                            setTimeout(()=>{
                                closeModal();
                                hideSuccess(successPopup, Overlay);
                                row.remove();
                                location.reload();
                            },1800); 
                        }

                    
                    });
                };
            }
        });

        
        const modalBoxEdit = document.getElementById("editModal");
        
        document.getElementById("saveEdit").onclick = () => {
            const editType = editModal.dataset.editType || 'utente';

            if(editType === 'presenza'){
                const id = editModal.dataset.presenzeId;
                const ingresso = document.getElementById("editIngresso").value;  
                const uscita = document.getElementById("editUscita").value;    

                // Ottenere la data di oggi in formato YYYY-MM-DD
                const today = new Date().toISOString().split('T')[0];

                // Combinare data e ora nel formato DB (YYYY-MM-DD HH:MM:SS)
                const ingressoDb = today + ' ' + ingresso + ':00';
                const uscitaDb = today + ' ' + uscita + ':00';

                fetch('api/api_modifica_presenza_ergo.php', {

                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ id: id, ingresso: ingressoDb, uscita: uscitaDb })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success){
                        editModal.classList.remove("show");
                        if(Overlay) Overlay.classList.remove("show");
                        successText.innerText = "Presenza modificata!!";
                        showSuccess(successPopup, Overlay);

                        setTimeout(()=>{
                            hideSuccess(successPopup, Overlay);
                            location.reload();
                        }, 1800);
                    } else {
                        alert("Errore: " + data.message);
                    }
                });
            } else {
                // Salva un utente
                const id = editModal.dataset.userId;

                const payload = {
                    id: id,
                    nome: document.getElementById("editNome").value,
                    cognome: document.getElementById("editCognome").value,
                    data_nascita: document.getElementById("editData").value,
                    codice_fiscale: document.getElementById("editCF").value,
                    email: document.getElementById("editEmail").value,
                    telefono: document.getElementById("editTelefono").value,

                    disabilita: document.getElementById("editDisabilita").value,
                    intolleranze: document.getElementById("editIntolleranze").value,
                    prezzo_orario: document.getElementById("editPrezzo").value,
                    note: document.getElementById("editNote").value
                };

                fetch('api/api_aggiorna_utente.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success){
                        editModal.classList.remove("show");
                        if(Overlay) Overlay.classList.remove("show");
                        successText.innerText = "Utente modificato!!";
                        showSuccess(successPopup, Overlay);

                        setTimeout(()=>{
                            hideSuccess(successPopup, Overlay);
                            location.reload();
                        }, 1800);
                    } else {
                        alert("Errore: " + data.message);
                    }
                });
            }
        };














        




    
        





       document.addEventListener("DOMContentLoaded", () => {
    const resocontiMeseFiltro = document.getElementById("resocontiMeseFiltro");
    const resocontiMensiliBody = document.getElementById("resocontiMensiliBody");
    const modalResoconto = document.getElementById("modalResocontoGiorni");
    const bodyResoconto = document.getElementById("resocontoGiorniBody");
    const meseInput = document.getElementById("resocontoMese");
    const titoloResoconto = document.getElementById("resocontoNome");

    let currentIscritto = null;

    // CARICAMENTO INIZIALE MENSILE
    if(resocontiMeseFiltro) caricaResocontiMensili(resocontiMeseFiltro.value);

    // CAMBIO MESE GLOBALE
    if(resocontiMeseFiltro) {
        resocontiMeseFiltro.addEventListener("change", () => {
            caricaResocontiMensili(resocontiMeseFiltro.value);
        });
    }

    // CLICK PULSANTE DETTAGLI GIORNALIERI
    document.addEventListener("click", e => {
        const btn = e.target.closest(".resoconto-btn, .calendario-btn");
        if(!btn) return;

        currentIscritto = btn.dataset.id;
        const nome = btn.dataset.nome || "";
        const cognome = btn.dataset.cognome || "";

        if(!titoloResoconto) return;
        titoloResoconto.textContent = "Resoconto - " + (cognome + " " + nome).trim();

        if(!meseInput) return;
        meseInput.value = resocontiMeseFiltro.value;

        if(bodyResoconto) bodyResoconto.innerHTML = `<tr><td colspan="4">Caricamento...</td></tr>`;

        if(modalResoconto && typeof openModal === "function") openModal(modalResoconto);

        caricaResocontoGiorni();
    });

    // CAMBIO MESE NEL MODAL
    if(meseInput) meseInput.addEventListener("change", caricaResocontoGiorni);

    // FUNZIONE CARICA RESOCONTI MENSILI
    function caricaResocontiMensili(mese){
        if(!resocontiMensiliBody) return;
        resocontiMensiliBody.innerHTML = `<tr><td colspan="6">Caricamento...</td></tr>`;

        fetch("api/api_resoconto_mensile_ergo.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({mese})
        })
        .then(r => r.json())
        .then(json => {
            resocontiMensiliBody.innerHTML = "";

            if(!json.success || json.data.length === 0){
                resocontiMensiliBody.innerHTML = `<tr><td colspan="6">Nessun dato disponibile</td></tr>`;
                return;
            }

            json.data.forEach(r => {
                const ore = parseFloat(r.ore_totali).toFixed(2);
                const stipendio = parseFloat(r.ore_totali * r.Stipendio_Orario).toFixed(2);

                resocontiMensiliBody.innerHTML += `
                    <tr>
                        <td><img src="${r.Fotografia}" class="user-avatar"></td>
                        <td>${r.Nome}</td>
                        <td>${r.Cognome}</td>
                        <td>${ore}</td>
                        <td>${stipendio} €</td>

                        <td>
                            <button class="btn-icon calendario-btn" data-id="${r.id}" data-nome="${r.Nome}" data-cognome="${r.Cognome}">
                                <img src="immagini/calendario.png" alt="Calendario">
                            </button>
                        </td>
                    </tr>
                `;
            });
        })
        .catch(err => {
            console.error(err);
            resocontiMensiliBody.innerHTML = `<tr><td colspan="6">Errore nel caricamento</td></tr>`;
        });
    }


    // FUNZIONE CARICA RESOCONTO GIORNI CON CALENDARIO MOBILE
    let mobileCalendarInstance = null;
    
    function caricaResocontoGiorni(){
        if(!currentIscritto || !meseInput) return;

        fetch("api/api_resoconto_giornaliero_ergo.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                id: currentIscritto,
                mese: meseInput.value
            })
        })
        .then(r => r.json())
        .then(json => {
            if(!bodyResoconto || !document.getElementById("resocontoContent")) return;
            bodyResoconto.innerHTML = "";
            const resocontoContent = document.getElementById("resocontoContent");
            resocontoContent.innerHTML = "";

            if(!json.success || json.data.length === 0){
                bodyResoconto.innerHTML = `<tr><td colspan="4">Nessun dato</td></tr>`;
                
                // Inizializza calendario vuoto
                if (mobileCalendarInstance) {
                    mobileCalendarInstance.destroy();
                }
                
                const [anno, mese] = meseInput.value.split('-');
                mobileCalendarInstance = new MobileCalendar('resocontoContent', {
                    selectedDate: new Date(parseInt(anno), parseInt(mese) - 1, 1),
                    activitiesData: {},
                    activitiesPanel: '#mc-activities-panel',
                    onDayClick: (date, activities) => {
                        console.log('Giorno selezionato:', date, 'Attività:', activities);
                    }
                });
                return;
            }

            const daysMap = new Map();
            let totalOre = 0;
            let totalCosto = 0;
            
            // Prepara dati per il calendario mobile
            const activitiesData = {};
            let giorniPresenza = 0;

            json.data.forEach(r => {
                const giorno = new Date(r.giorno).getDate();
                const dateStr = r.giorno; // YYYY-MM-DD
                
                if(!daysMap.has(giorno)) {
                    daysMap.set(giorno, {ore:0, costo:0});
                    giorniPresenza++;
                }
                const day = daysMap.get(giorno);

                day.ore += r.ore;
                day.costo += r.costo;

                totalOre += r.ore;
                totalCosto += r.costo;

                bodyResoconto.innerHTML += `
                    <tr>
                        <td>${giorno}</td>
                        <td>Presenza</td>
                        <td>${r.ore.toFixed(2)}</td>
                        <td>${r.costo.toFixed(2)} €</td>
                    </tr>
                `;
                
                // Prepara dati attività per calendario
                if (!activitiesData[dateStr]) {
                    activitiesData[dateStr] = [];
                }
                activitiesData[dateStr].push({
                    nome: 'Presenza',
                    descrizione: `${day.ore.toFixed(2)} ore - ${day.costo.toFixed(2)}€`,
                    ora_inizio: '',
                    ora_fine: '',
                    educatori: ''
                });
            });

            // Inizializza o aggiorna il calendario mobile
            if (mobileCalendarInstance) {
                mobileCalendarInstance.destroy();
            }

            const [anno, mese] = meseInput.value.split('-');
            
            mobileCalendarInstance = new MobileCalendar('resocontoContent', {
                selectedDate: new Date(parseInt(anno), parseInt(mese) - 1, 1),
                activitiesData: activitiesData,
                activitiesPanel: '#mc-activities-panel',
                onDayClick: (date, activities) => {
                    console.log('Giorno selezionato:', date, 'Attività:', activities);
                }
            });

            // Totali - AGGIORNA I TOTALI ESISTENTI
            const updateOrCreateTotals = () => {
                const modal = document.getElementById('modalResocontoGiorni');
                if (!modal) return;
                
                const existingTotals = modal.querySelector('.resoconto-totals');
                if (existingTotals) {
                    existingTotals.remove();
                }
                
                const totalsHTML = `<div class="resoconto-totals">
                    <div class="total-card"><div class="total-label"><img class="resoconti-icon" src="immagini/timing.png">Ore Totali</div><div class="total-value hours">${totalOre.toFixed(1)}</div></div>
                    <div class="total-card"><div class="total-label"><img class="resoconti-icon" src="immagini/money.png">Stipendio Mensile</div><div class="total-value currency">${totalCosto.toFixed(2)}€</div></div>
                    <div class="total-card"><div class="total-label"><img class="resoconti-icon" src="immagini/appointment.png">Giorni Presenza</div><div class="total-value">${daysMap.size}</div></div>
                </div>`;
                
                const calendarWrapper = modal.querySelector('.resoconto-calendar-wrapper');
                if (calendarWrapper) {
                    calendarWrapper.insertAdjacentHTML('afterend', totalsHTML);
                }
            };
            
            updateOrCreateTotals();
        })
        .catch(err => {
            console.error(err);
            if(bodyResoconto) bodyResoconto.innerHTML = `<tr><td colspan="4">Errore nel caricamento</td></tr>`;
            const resocontoContent = document.getElementById("resocontoContent");
            if(resocontoContent) resocontoContent.innerHTML = `<p style="text-align:center;margin-top:12px;">❌ Errore nel caricamento</p>`;
        });
    }


});


        flatpickr("#resocontiMeseFiltro", {
            plugins: [
                new monthSelectPlugin({ 
                    shorthand: false, 
                    dateFormat: "Y-m", 
                    altFormat: "F Y"   
                })
            ],
            defaultDate: new Date(),
            altInput: true
        });

        flatpickr("#resocontoMese", {
            plugins: [
                new monthSelectPlugin({ 
                    shorthand: false,
                    dateFormat: "Y-m",
                    altFormat: "F Y"
                })
            ],
            defaultDate: new Date(),
            altInput: true
        });

        // Salva stato sidebar e toggle visibilità
        const checkboxInput = document.getElementById('checkbox-input');
        const sidebar = document.querySelector('.vertical-sidebar');
        if (checkboxInput && sidebar) {
            const sidebarState = localStorage.getItem('sidebarOpen');
            if (sidebarState !== null) {
                checkboxInput.checked = sidebarState === 'true';
                if (sidebarState === 'true') {
                    sidebar.classList.add('open');
                } else {
                    sidebar.classList.remove('open');
                }
            }

            checkboxInput.addEventListener('change', () => {
                localStorage.setItem('sidebarOpen', checkboxInput.checked);
                if (checkboxInput.checked) {
                    sidebar.classList.add('open');
                } else {
                    sidebar.classList.remove('open');
                }
            });
        }

        // Blocca scroll del body quando un popup è aperto
        const popupTargetsSelector = ".modal-box, .popup, .logout-modal, .success-popup, .modal-overlay, .popup-overlay, .logout-overlay";
        const popupShowSelector = ".modal-box.show, .popup.show, .logout-modal.show, .success-popup.show, .modal-overlay.show, .popup-overlay.show, .logout-overlay.show";

        function syncBodyScrollLock() {
            const anyOpen = document.querySelector(popupShowSelector);
            document.body.classList.toggle("popup-open", Boolean(anyOpen));
        }

        const popupObserver = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                const target = mutation.target;
                if (target === document.body || (target instanceof Element && target.matches(popupTargetsSelector))) {
                    syncBodyScrollLock();
                    break;
                }
            }
        });

        popupObserver.observe(document.body, { subtree: true, attributes: true, attributeFilter: ["class"] });
        syncBodyScrollLock();

        // utilità: YYYY-MM-DD locale
        function getLocalDateString(date) {
            const y = date.getFullYear();
            const m = (date.getMonth() + 1).toString().padStart(2,'0');
            const d = date.getDate().toString().padStart(2,'0');
            return `${y}-${m}-${d}`;
        }








        const aggiungiUtenteBtn = document.getElementById("aggiungi-utente-btn");
        const aggiungiUtenteBtnMobile = document.getElementById("aggiungi-utente-btn-mobile");
        const modalAggiungiUtente = document.getElementById("modalAggiungiUtente");

        const modalModificaUtente = document.getElementById("modalModificaUtente");
        const modalDeleteUtente = document.getElementById("modalDeleteUtente");
        const formAggiungiUtente = document.getElementById("formAggiungiUtente");

        aggiungiUtenteBtn?.addEventListener("click", () => openModal(modalAggiungiUtente));
        aggiungiUtenteBtnMobile?.addEventListener("click", () => openModal(modalAggiungiUtente));

        // Click handler for Salva button - triggers form submission

        document.getElementById("salvaNuovoUtente")?.addEventListener("click", function() {
            formAggiungiUtente.dispatchEvent(new Event('submit'));
        });

        // Submit form
        formAggiungiUtente.onsubmit = function(e) {
            e.preventDefault();

            // Validazione client-side
            const nome = document.getElementById("utenteNome").value.trim();
            const cognome = document.getElementById("utenteCognome").value.trim();
            const data = document.getElementById("utenteData").value;
            const cf = document.getElementById("utenteCF").value.trim();
            const email = document.getElementById("utenteEmail").value.trim();
            const telefono = document.getElementById("utenteTelefono").value.trim();


            const formData = new FormData();
            formData.append("nome", document.getElementById("utenteNome").value.trim());
            formData.append("cognome", document.getElementById("utenteCognome").value.trim());
            formData.append("data_nascita", document.getElementById("utenteData").value);
            formData.append("codice_fiscale", document.getElementById("utenteCF").value.trim());
            formData.append("email", document.getElementById("utenteEmail").value.trim());
            formData.append("telefono", document.getElementById("utenteTelefono").value.trim());

            formData.append("disabilita", document.getElementById("utenteDisabilita").value.trim());
            formData.append("intolleranze", document.getElementById("utenteIntolleranze").value.trim());
            const prezzoValue = document.getElementById("utentePrezzo").value;
            formData.append("prezzo_orario", prezzoValue ? parseFloat(prezzoValue) : 0);
            formData.append("note", document.getElementById("utenteNote").value.trim());

            const fotoInput = document.getElementById("utenteFotoFile");
            if(fotoInput.files.length > 0){
                formData.append("foto", fotoInput.files[0]);
            }

            fetch("api/api_aggiungi_utente_ergo.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    modalAggiungiUtente.classList.remove("show");
                    successText.innerText = "Utente Aggiunto!!";
                    showSuccess(successPopup, Overlay);

                    setTimeout(() => {
                        hideSuccess(successPopup, Overlay);
                        if(Overlay) Overlay.classList.remove("show");
                        location.reload();
                    },1800);

                } else {
                    alert("Errore: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Errore nel caricamento!");
            });
        };


        // Edit utente
        document.querySelectorAll(".edit-utente-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const row = btn.closest("tr");
                document.getElementById("editUtenteId").value = row.dataset.id;
                document.getElementById("editUtenteNome").value = row.dataset.nome;
                document.getElementById("editUtenteCognome").value = row.dataset.cognome;
                document.getElementById("editUtenteData").value = row.dataset.nascita;
                document.getElementById("editUtenteCF").value = row.dataset.cf;
                document.getElementById("editUtenteEmail").value = row.dataset.email;
                document.getElementById("editUtenteTelefono").value = row.dataset.telefono;

                document.getElementById("editUtenteDisabilita").value = row.dataset.disabilita;
                document.getElementById("editUtenteIntolleranze").value = row.dataset.intolleranze;
                document.getElementById("editUtentePrezzo").value = row.dataset.prezzo;
                document.getElementById("editUtenteNote").value = row.dataset.note;
                openModal(modalModificaUtente);
            });
        });

        document.getElementById("salvaModificaUtente")?.addEventListener("click", () => {
            const id = document.getElementById("editUtenteId").value;
            const nome = document.getElementById("editUtenteNome").value.trim();
            const cognome = document.getElementById("editUtenteCognome").value.trim();
            const data_nascita = document.getElementById("editUtenteData").value;
            const cf = document.getElementById("editUtenteCF").value.trim();
            const email = document.getElementById("editUtenteEmail").value.trim();
            const telefono = document.getElementById("editUtenteTelefono").value.trim();

            const disabilita = document.getElementById("editUtenteDisabilita").value.trim();
            const intolleranze = document.getElementById("editUtenteIntolleranze").value.trim();
            const prezzo = parseFloat(document.getElementById("editUtentePrezzo").value) || 0;
            const note = document.getElementById("editUtenteNote").value.trim();

            fetch("api/api_modifica_utente_ergo.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id, nome, cognome, data_nascita, codice_fiscale: cf, email, telefono, disabilita, intolleranze, prezzo_orario: prezzo, note })

            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    modalModificaUtente.classList.remove("show");
                    successText.innerText = "Utente modificato!!";
                    showSuccess(successPopup, Overlay);

                    setTimeout(() => {
                        hideSuccess(successPopup, Overlay);
                        if(Overlay) Overlay.classList.remove("show");
                        location.reload();
                    }, 1800);
                } else alert("Errore: " + data.message);
            })
            .catch(err => alert("Errore: " + err));
        });

        // Delete utente
        let rowToDelete = null;
        document.querySelectorAll(".delete-utente-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                rowToDelete = btn.closest("tr");
                document.querySelector("#modalDeleteUtente h3").innerText = "Elimina utente: " + rowToDelete.dataset.nome;
                openModal(modalDeleteUtente);
            });
        });

        document.getElementById("confirmDeleteUtente")?.addEventListener("click", () => {
            if(!rowToDelete) return;
            fetch("api/api_elimina_utente_ergo.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: rowToDelete.dataset.id })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    closeModal(modalDeleteUtente);
                    successText.innerText = "Utente Eliminato!!";
                    showSuccess(successPopup, Overlay);

                    setTimeout(() => {
                        hideSuccess(successPopup, Overlay);
                        if(Overlay) Overlay.classList.remove("show");
                        location.reload();
                    }, 1800);
                } else alert("Errore: " + data.message);
            })
            .catch(err => alert("Errore: " + err));
        });




         const utenteFoto = document.getElementById("utenteFoto");
        const preview = document.getElementById("previewFotoMini");
        const fileNameSpan = document.getElementById("nomeFileFoto");
        const clearBtn = document.getElementById("clearFileBtn");

        utenteFoto.addEventListener("change", function(){

            if(!this.files.length){
                preview.style.display = "none";
                fileNameSpan.innerText = "Nessun file";
                clearBtn.style.display = "none"; 
                return;
            }

            const file = this.files[0];

            preview.src = URL.createObjectURL(file);
            preview.style.display = "block";

            fileNameSpan.innerText = file.name;

            clearBtn.style.display = "block";
        });

        // rimuove file selezionato
        clearBtn.addEventListener("click", function(){
            utenteFoto.value = ""; 
            preview.style.display = "none";
            fileNameSpan.innerText = "Nessun file";
            clearBtn.style.display = "none"; 
        });





    </script>

    <script src="js/mobile-calendar.js"></script>
</body>

</html>
