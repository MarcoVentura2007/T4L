<?php
session_start();

// Se l'utente non è loggato → redirect a login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Connessione al DB
$host = "localhost";    
$user = "root";         
$pass = "";             
$db   = "time4all"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Prendi la classe dell'utente loggato
$resultClasse = $conn->query("SELECT classe FROM Account WHERE nome_utente = '$username'");
if($resultClasse && $resultClasse->num_rows > 0){
    $rowClasse = $resultClasse->fetch_assoc();
    $classe = $rowClasse['classe'];
} else {
    $classe = ""; // default se non trovato
}

// Controlla se il codice è stato verificato (timeout 30 minuti)
if(
    !isset($_SESSION['codice_verificato']) || 
    $_SESSION['codice_verificato'] !== true ||
    !isset($_SESSION['codice_verificato_time']) ||
    (time() - $_SESSION['codice_verificato_time']) > 1800
){
    header("Location: index.php");
    exit;
}

// Preleva i profili dal DB
$sql = "SELECT id, nome, cognome, fotografia, data_nascita, disabilita, prezzo_orario, codice_fiscale, email, telefono, allergie_intolleranze, note 
        FROM iscritto ORDER BY cognome ASC";

$result = $conn->query($sql);

// Presenze giornaliere di default
$oggi = date('Y-m-d')."%";
$sqlPresenze = "SELECT i.fotografia, p.id, i.nome, i.cognome, p.ingresso, p.uscita 
                FROM presenza p 
                INNER JOIN iscritto i ON p.ID_Iscritto = i.id 
                WHERE p.ingresso LIKE '$oggi'
                
                ORDER BY p.ingresso ASC";
$resultPresenze = $conn->query($sqlPresenze);

//se la classe non è Amministratore, redirect a index.php
if($classe !== 'Amministratore'){
    header("Location: index.php");
    exit;
}

// Preleva gli account dal DB
$sqlAccount = "SELECT nome_utente, codice_univoco, classe FROM Account ORDER BY nome_utente ASC";
$resultAccount = $conn->query($sqlAccount);

// Preleva gli educatori dal DB
$sqlEducatori = "SELECT id, nome, cognome, codice_fiscale, data_nascita, telefono, mail FROM educatore ORDER BY cognome ASC";
$resultEducatori = $conn->query($sqlEducatori);

// Query per attività (per combobox nella modal agenda)
$sqlAttivitaCombo = "SELECT id, Nome FROM attivita ORDER BY Nome ASC";
$resultAttivitaCombo = $conn->query($sqlAttivitaCombo);

// Query per educatori (per combobox nella modal agenda)
$sqlEducatoriAgenda = "SELECT id, nome, cognome FROM educatore ORDER BY cognome ASC, nome ASC";
$resultEducatoriAgenda = $conn->query($sqlEducatoriAgenda);

// Query per ragazzi (per checkbox nella modal agenda)
$sqlRagazzi = "SELECT id, nome, cognome FROM iscritto ORDER BY cognome ASC, nome ASC";
$resultRagazzi = $conn->query($sqlRagazzi);




$mese = date('m'); // mese corrente
$anno = date('Y');

$sqlResoconti = "
SELECT 
    i.id,
    i.Nome,
    i.Cognome,
    i.Fotografia,
    i.Prezzo_Orario,
    SUM(TIMESTAMPDIFF(MINUTE, p.Ingresso, p.Uscita)) / 60 AS ore_totali
FROM iscritto i
LEFT JOIN presenza p 
    ON p.ID_Iscritto = i.id
    AND MONTH(p.Ingresso) = $mese
    AND YEAR(p.Ingresso) = $anno
GROUP BY i.id
ORDER BY i.Cognome
";

$resultResoconti = $conn->query($sqlResoconti);




?>

<!DOCTYPE html>

<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>T4L | Gestionale utenti</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="style_mobile_agenda.css">
<link rel="stylesheet" href="stylr_mobile_no_zoom.css">
<link rel="icon" href="immagini/Icona.ico">
<script src="https://cdn.tailwindcss.com"></script>


<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">

<style>
     @media (max-width: 768px){
        .footer-bar{
            display: none;
        }
    }

</style>



</head>
<body>
<!--

<div id="page-loader" class="show">
<div class="logo-pulse-loader">
    <div class="logo-pulse-ring"></div>
    <div class="logo-pulse-ring"></div>
    <img src="immagini/TIME4ALL_LOGO-removebg-preview.png" alt="Time4All">
</div>

    <p style="margin-top: 30px; color: #640a35; font-size: 0.9rem; font-weight: 500; letter-spacing: 1px;">Caricamento...</p>
</div>

-->

 <script src="js/loader.js"></script>


<script src="js/loader.js"></script>
    <!-- NAVBAR -->
    <header class="navbar">

        <div class="user-box" id="userBox">
            <img src="immagini/profile-picture.png" alt="Profile">
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
                            $gestionalePage = "gestionale_utenti.php";
                        } elseif($classe === 'Contabile'){
                            $gestionalePage = "gestionale_contabile.php";
                        } elseif($classe === 'Amministratore') {
                            $gestionalePage = "gestionale_amministratore.php";
                        } else {
                            $gestionalePage = "#"; // default se classe sconosciuta
                        }
                    ?>
                <div class="menu-item" data-link=<?php echo $gestionalePage; ?>>
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
                            $gestionalePageErgo = "gestionale_ergo_utenti.php";

                        } else if($classe === 'Contabile'){
                            $gestionalePageErgo = "gestionale_ergo_contabile.php";
                        } else if($classe === 'Amministratore') {
                            $gestionalePageErgo = "gestionale_ergo_amministratore.php"; 
                        } else {
                            $gestionalePageErgo = "#"; 
                        }
                    ?>
                
                <div class="menu-item" data-link=<?php echo $gestionalePageErgo; ?>>
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
                                <!-- Icone di toggle, puoi lasciare SVG o cambiare con immagini se vuoi -->
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
                        <li class="sidebar__item">
                            <a class="sidebar__link tab-link" href="#" data-tab="tab-agenda" data-tooltip="Agenda">
                                <span class="sidebar-icon"><img src="immagini/book.png" alt=""></span>
                                <span class="text">Agenda</span>
                            </a>
                        </li>
                        <li>
                            <hr/>
                        </li>
                        <li class="sidebar__item item--heading">
                            <h2 class="sidebar__item--heading">Gestione</h2>
                        </li>
                        
                        <li class="sidebar__item">
                            <a class="sidebar__link tab-link" href="#" data-tab="tab-attivita" data-tooltip="Attivita">
                                <span class="sidebar-icon"><img src="immagini/attivita.png" alt=""></span>
                                <span class="text">Attività</span>
                            </a>
                        </li>
                        <li class="sidebar__item">
                            <a class="sidebar__link tab-link" href="#" data-tab="tab-resoconti" data-tooltip="Resoconti">
                                <span class="sidebar-icon"><img src="immagini/resoconti.png" alt=""></span>
                                <span class="text">Resoconti</span>
                            </a>
                        </li>


                        <!-- Solo per amministratore -->
                        <li>
                            <hr/>
                        </li>
                        <li class="sidebar__item item--heading">
                            <h2 class="sidebar__item--heading">AMMINISTRAZIONE</h2>
                        </li>
                        
                        <li class="sidebar__item">
                            <a class="sidebar__link tab-link" href="#" data-tab="tab-educatori" data-tooltip="Educatori">
                                <span class="sidebar-icon"><img src="immagini/educatore.png" alt=""></span>
                                <span class="text">Educatori</span>
                            </a>
                        </li>
                        <li class="sidebar__item">
                            <a class="sidebar__link tab-link" href="#" data-tab="tab-account" data-tooltip="Account">
                                <span class="sidebar-icon"><img src="immagini/account.png" alt=""></span>
                                <span class="text">Account</span>
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
                            <label>Email</label>
                            <input type="email" id="utenteEmail" placeholder="Email">
                        </div>
                        <div class="edit-field">
                            <label>Telefono</label>
                            <input type="tel" id="utenteTelefono" placeholder="Telefono">
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
                            <label>Disabilità</label>

                            <input type="text" id="utenteDisabilita">
                        </div>
                        <div class="edit-field">
                            <label>Intolleranze / Allergie</label>
                            <input type="text" id="utenteIntolleranze">
                        </div>
                        <div class="edit-field">
                            <label>Prezzo orario (€)</label>
                            <input type="number" id="utentePrezzo" placeholder="Prezzo orario" step="0.01">
                        </div>
                        <div class="edit-field">
                            <label>Note</label>
                            <textarea id="utenteNote"></textarea>
                        </div>

                        <!-- SEZIONE ALLEGATI -->
                        <div class="edit-field">
                            <label>Allegati</label>
                            <div class="allegati-upload-container" id="allegatiContainer">
                                <input type="file" id="utenteAllegati" multiple 
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.xls,.xlsx" hidden>
                                
                                <div class="allegati-drop-zone" id="allegatiDropZone">
                                    <div class="allegati-icon"><img src="immagini/paperclip.png" alt="Graffetta"></div>
                                    <p class="allegati-text">Trascina i file qui o clicca per selezionare</p>
                                    <p class="allegati-hint">PDF, DOC, DOCX, JPG, PNG, GIF, TXT, XLS, XLSX (max 10MB)</p>
                                    <button type="button" class="file-btn-minimal" 
                                        onclick="document.getElementById('utenteAllegati').click()">
                                        Seleziona file
                                    </button>
                                </div>

                                <div class="allegati-list" id="allegatiList" style="display:none;">
                                    <div class="allegati-list-header">
                                        <span>File selezionati</span>
                                        <button type="button" class="allegati-clear-all" id="clearAllAllegati">Rimuovi tutti</button>
                                    </div>
                                    <ul id="allegatiItems"></ul>
                                </div>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                            <button type="submit" class="btn-primary">Salva</button>
                        </div>

                    </form>
                </div>
                <div class="header-mobile">
                    <div class="page-header">
                        <h1>Utenti</h1>
                        <p>Elenco iscritti registrati</p>
                    </div>

                    <button
                        title="Add New" id="aggiungi-utente-btn-mobile"
                        class="group cursor-pointer outline-none hover:rotate-90 duration-300 "
                        >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="50px"
                            height="50px"
                            viewBox="0 0 24 24"
                            class="stroke-zinc-400 fill-none group-hover:fill-zinc-800 group-active:stroke-zinc-200 group-active:fill-zinc-600 group-active:duration-0 duration-300"
                        >
                            <path
                            d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z"
                            stroke-width="1"
                            ></path>
                            <path d="M8 12H16" stroke-width="1"></path>
                            <path d="M12 16V8" stroke-width="1"></path>
                        </svg>
                    </button>
                </div>

                <div class="users-table-box">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Fotografia</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Data di nascita</th>
                                <th>Disabilità</th>
                                <th>Note</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if($result && $result->num_rows > 0){
                            while($row = $result->fetch_assoc()){
                                echo '
                                    <tr
                                        data-id="'.htmlspecialchars($row['id']).'"
                                        data-nome="'.htmlspecialchars($row['nome']).'" 
                                        data-cognome="'.htmlspecialchars($row['cognome']).'" 
                                        data-nascita="'.htmlspecialchars($row['data_nascita']).'" 
                                        data-note="'.htmlspecialchars($row['note']).'" 
                                        data-cf="'.htmlspecialchars($row['codice_fiscale']).'" 
                                        data-email="'.htmlspecialchars($row['email']).'" 
                                        data-telefono="'.htmlspecialchars($row['telefono']).'" 
                                        data-disabilita="'.htmlspecialchars($row['disabilita']).'" 
                                        data-intolleranze="'.htmlspecialchars($row['allergie_intolleranze']).'" 
                                        data-prezzo="'.htmlspecialchars($row['prezzo_orario']).'" 
                                    >

                                        <td><img class="user-avatar" src="'.$row['fotografia'].'"></td>
                                        <td>'.htmlspecialchars($row['nome']).'</td>
                                        <td>'.htmlspecialchars($row['cognome']).'</td>
                                        <td>'.htmlspecialchars($row['data_nascita']).'</td>
                                        <td>'.htmlspecialchars($row['disabilita']).'</td>
                                        <td>'.htmlspecialchars($row['note']).'</td>
                                        <td>
                                            <button class="view-btn"><img src="immagini/open-eye.png"></button>
                                            <button class="edit-btn"><img src="immagini/edit.png"></button>
                                            <button class="delete-btn"><img src="immagini/delete.png"></button>
                                        </td>
                                    </tr>
                                ';
                            }
                        }
                        ?>
                        </tbody>
                    </table>

                
                    <div class="modal-box large" id="viewModal">
                        <div class="profile-header">
                            <img id="viewAvatar" class="profile-avatar">
                            <div class="profile-main">
                                <h3 id="viewFullname"></h3>
                                <span id="viewBirth"></span>
                            </div>
                        </div>
                        <div class="profile-grid" id="viewContent"></div>
                        
                        <!-- SEZIONE ALLEGATI -->
                        <div class="allegati-section" id="viewAllegatiSection" style="margin-top: 20px; border-top: 1px solid #e0e0e0; padding-top: 15px;">
                            <h4 style="margin-bottom: 15px; color: #2b2b2b; display: flex; align-items: center; gap: 8px;">
                                <img src="immagini/paperclip.png" alt="Allegati" style="width: 20px; height: 20px;">
                                Allegati
                            </h4>
                            <div id="viewAllegatiList" class="allegati-list-view">
                                <p style="color: #888; font-style: italic;">Caricamento allegati...</p>
                            </div>
                        </div>
                        
                        <div class="modal-actions">
                            <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
                        </div>


                        <!-- Presenze form (hidden by default) -->
                        <div id="presenzeFormBox" style="display:none;">
                            <form id="formModificaPresenza">
                                <div class="edit-field">
                                    <label>Ingresso (YYYY-MM-DD HH:MM:SS)</label>
                                    <input type="text" id="presenzeIngresso" required>
                                </div>
                                <div class="edit-field">
                                    <label>Uscita (YYYY-MM-DD HH:MM:SS)</label>
                                    <input type="text" id="presenzeUscita" required>
                                </div>
                                <div class="modal-actions">
                                    <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                                    <button type="submit" class="btn-primary">Salva</button>
                                </div>
                            </form>
                        </div>

                        <!-- Presenze delete box (hidden by default) -->
                        <div id="deletePresenzaBox" style="display:none;">
                            <p>Questa azione è definitiva. Vuoi continuare?</p>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="closeModal()">Annulla</button>
                                <button class="btn-danger" id="confirmDeletePresenza">Elimina</button>
                            </div>
                        </div>
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
                                        data-nome="'.htmlspecialchars($row['nome']).'"
                                        data-cognome="'.htmlspecialchars($row['cognome']).'"
                                        data-ingresso="'.htmlspecialchars($row['ingresso']).'"
                                        data-uscita="'.htmlspecialchars($row['uscita']).'"
                                    >
                                        <td><img class="user-avatar" src="'.$row['fotografia'].'"></td>
                                        <td>'.htmlspecialchars($row['nome']).'</td>
                                        <td>'.htmlspecialchars($row['cognome']).'</td>
                                        <td>'.htmlspecialchars($row['ingresso']).'</td>
                                        <td>'.htmlspecialchars($row['uscita']).'</td>
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





            <!-- TAB AGENDA -->
            <div class="page-tab" id="tab-agenda">
                <div class="header-mobile">
                    <div class="page-header">
                        <h1>Agenda</h1>
                        <p>Attività della settimana</p>
                    </div>

                    <button
                        title="Add New" id="aggiungi-agenda-btn-mobile"
                        class="group cursor-pointer outline-none hover:rotate-90 duration-300 "
                        >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="50px"
                            height="50px"
                            viewBox="0 0 24 24"
                            class="stroke-zinc-400 fill-none group-hover:fill-zinc-800 group-active:stroke-zinc-200 group-active:fill-zinc-600 group-active:duration-0 duration-300"
                        >
                            <path
                            d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z"
                            stroke-width="1"
                            ></path>
                            <path d="M8 12H16" stroke-width="1"></path>
                            <path d="M12 16V8" stroke-width="1"></path>
                        </svg>
                    </button>
                </div>

                <button class="animated-button" id="creaAgendaBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-2" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>

                    <span class="text">Crea nuova Agenda</span>
                    <span class="circle"></span>

                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-1" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="black" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                </button>

                

                <div class="agenda-container" style="margin: 0 auto;">
                    <div class="header-agenda">
                    <div class="days-tabs">
                        <button class="day-tab active" data-day="0">
                            <span class="day-name">Lunedì</span>
                            <span class="day-date" id="date-monday"></span>
                        </button>
                        <button class="day-tab" data-day="1">
                            <span class="day-name">Martedì</span>
                            <span class="day-date" id="date-tuesday"></span>
                        </button>
                        <button class="day-tab" data-day="2">
                            <span class="day-name">Mercoledì</span>
                            <span class="day-date" id="date-wednesday"></span>
                        </button>
                        <button class="day-tab" data-day="3">
                            <span class="day-name">Giovedì</span>
                            <span class="day-date" id="date-thursday"></span>
                        </button>
                        <button class="day-tab" data-day="4">
                            <span class="day-name">Venerdì</span>
                            <span class="day-date" id="date-friday"></span>
                        </button>
                    </div>


                    <button class="print-btn" id="stampaAgendaBtn">
                    <span class="printer-wrapper">
                        <span class="printer-container">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 92 75">
                            <path
                            stroke-width="5"
                            stroke="black"
                            d="M12 37.5H80C85.2467 37.5 89.5 41.7533 89.5 47V69C89.5 70.933 87.933 72.5 86 72.5H6C4.067 72.5 2.5 70.933 2.5 69V47C2.5 41.7533 6.75329 37.5 12 37.5Z"
                            ></path>
                            <mask fill="white" id="path-2-inside-1_30_7">
                            <path
                                d="M12 12C12 5.37258 17.3726 0 24 0H57C70.2548 0 81 10.7452 81 24V29H12V12Z"
                            ></path>
                            </mask>
                            <path
                            mask="url(#path-2-inside-1_30_7)"
                            fill="black"
                            d="M7 12C7 2.61116 14.6112 -5 24 -5H57C73.0163 -5 86 7.98374 86 24H76C76 13.5066 67.4934 5 57 5H24C20.134 5 17 8.13401 17 12H7ZM81 29H12H81ZM7 29V12C7 2.61116 14.6112 -5 24 -5V5C20.134 5 17 8.13401 17 12V29H7ZM57 -5C73.0163 -5 86 7.98374 86 24V29H76V24C76 13.5066 67.4934 5 57 5V-5Z"
                            ></path>
                            <circle fill="black" r="3" cy="49" cx="78"></circle>
                        </svg>
                        </span>

                        <span class="printer-page-wrapper">
                        <span class="printer-page"></span>
                        </span>
                    </span>
                    Stampa
                    </button>

                    </div>

                    <div class="agenda-content" id="agendaContent" style="touch-action: pan-y;">
                        <div class="loading">Caricamento attività...</div>
                    </div>

                </div>

                <!-- MODAL CREA AGENDA -->  
                <div class="modal-box large" id="modalCreaAgenda">
                    <h3 class="modal-title">Crea nuova Agenda</h3>

                    <form id="formCreaAgenda">
                        <div class="edit-field">
                            <label>Data</label>
                            <select id="agendaData" required>
                                <option value="">-- Seleziona data --</option>
                            </select>
                        </div>


                        <div class="edit-field">
                            <label>Ora inizio</label>
                            <input type="time"  id="agendaOraInizio" required>
                        </div>

                        <div class="edit-field">
                            <label>Ora fine</label>
                            <input type="time"  id="agendaOraFine" required>
                        </div>

                        <div class="edit-field">
                            <label>Attività</label>
                            <select id="agendaAttivita" required>
                                <option value="">-- Seleziona attività --</option>
                                <?php
                                if($resultAttivitaCombo && $resultAttivitaCombo->num_rows > 0){
                                    while($row = $resultAttivitaCombo->fetch_assoc()){
                                        echo '<option value="'.htmlspecialchars($row['id']).'">'.htmlspecialchars($row['Nome']).'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="edit-field">
                            <label>Educatori</label>

                            <div class="checkbox-group" id="educatoriCheckboxes">
                                <?php
                                if($resultEducatoriAgenda && $resultEducatoriAgenda->num_rows > 0){
                                    while($row = $resultEducatoriAgenda->fetch_assoc()){
                                        echo '<label class="checkbox-item">';
                                        echo '<input type="checkbox" class="educatore-checkbox" value="'.htmlspecialchars($row['id']).'"> ';
                                        echo '<span>'.htmlspecialchars($row['nome'].' '.$row['cognome']).'</span>';
                                        echo '</label>';
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <div class="edit-field">
                            <label>Ragazzi partecipanti</label>
                            <div class="checkbox-group" id="ragazziCheckboxes">
                                <?php
                                if($resultRagazzi && $resultRagazzi->num_rows > 0){
                                    while($row = $resultRagazzi->fetch_assoc()){
                                        echo '<label class="checkbox-item">';
                                        echo '<input type="checkbox" class="ragazzo-checkbox" value="'.htmlspecialchars($row['id']).'"> ';
                                        echo '<span>'.htmlspecialchars($row['nome'].' '.$row['cognome']).'</span>';
                                        echo '</label>';
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                            <button type="submit" class="btn-primary">Salva</button>
                        </div>
                    </form>
                </div>

                <!-- POPUP AGENDA CREATA -->
                <div class="popup success-popup" id="successPopupAgenda">
                    <div class="success-content">
                        <div class="success-icon">
                        <svg viewBox="-2 -2 56 56">
                            <circle class="check-circle" cx="26" cy="26" r="25" fill="none"/>
                            <path class="check-check" d="M14 27 L22 35 L38 19" fill="none"/>
                        </svg>
                        </div>
                        <p class="success-text" id="success-text-agenda">Agenda creata!</p>
                    </div>
                </div>


                <div class="modal-box danger" id="modalDeleteAgenda">
                    <h3>Elimina Agenda</h3>
                    <p>Questa azione è definitiva. Vuoi continuare?</p>

                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal()">Annulla</button>
                        <button class="btn-danger" id="confirmDeleteAgenda">Elimina</button>
                    </div>
                </div>
            </div>





            <!-- TAB ATTIVITA -->
            <div class="page-tab" id="tab-attivita">
                <div class="header-mobile">
                    <div class="page-header">
                        <h1>Attività</h1>
                        <p>Gestione delle attività</p>
                    </div>

                    <button
                        title="Add New" id="aggiungi-attivita-btn-mobile"
                        class="group cursor-pointer outline-none hover:rotate-90 duration-300 "
                        >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="50px"
                            height="50px"
                            viewBox="0 0 24 24"
                            class="stroke-zinc-400 fill-none group-hover:fill-zinc-800 group-active:stroke-zinc-200 group-active:fill-zinc-600 group-active:duration-0 duration-300"
                        >
                            <path
                            d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z"
                            stroke-width="1"
                            ></path>
                            <path d="M8 12H16" stroke-width="1"></path>
                            <path d="M12 16V8" stroke-width="1"></path>
                        </svg>
                    </button>
                </div>

                <button class="animated-button" id="aggiungiAttivitaBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-2" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>

                    <span class="text">Aggiungi Attività</span>
                    <span class="circle"></span>

                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-1" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="black" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                </button>

                <div class="users-table-box">
                    <table class="users-table" id="attivitaTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Descrizione</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sqlAttivita = "SELECT id, Nome, Descrizione FROM attivita ORDER BY Nome ASC";
                        $resultAttivita = $conn->query($sqlAttivita);
                        if($resultAttivita && $resultAttivita->num_rows > 0){
                            while($row = $resultAttivita->fetch_assoc()){
                                echo '<tr data-id="'.htmlspecialchars($row['id']).'">
                                        <td>'.htmlspecialchars($row['Nome']).'</td>
                                        <td>'.htmlspecialchars($row['Descrizione']).'</td>
                                        <td>
                                            <button class="edit-attivita-btn"><img src="immagini/edit.png" alt="Modifica"></button>
                                            <button class="delete-attivita-btn"><img src="immagini/delete.png" alt="Elimina"></button>
                                        </td>
                                    </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3">Nessuna attività registrata.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <!-- MODAL AGGIUNGI ATTIVITA -->
                <div class="modal-box large" id="modalAggiungiAttivita">
                    <h3 class="modal-title">Aggiungi nuova attività</h3>

                    <form id="formAttivita">
                        <div class="edit-field">
                            <label>Nome</label>
                            <input type="text" id="attivitaNome" placeholder="Nome attività" required>
                        </div>
                        <div class="edit-field">
                            <label>Descrizione</label>
                            <textarea id="attivitaDescrizione" placeholder="Descrizione" required></textarea>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" id="closeAddAttivita" onclick="closeModal()">Chiudi</button>
                            <button class="btn-primary" id="salvaAttivita">Salva</button>
                        </div>
                    </form>
                </div>





                <!-- MODAL MODIFICA ATTIVITA -->
                <div class="modal-box large" id="modalModificaAttivita">
                    <h3 class="modal-title">Modifica attività</h3>

                    <form id="formModificaAttivita">
                        <input type="hidden" id="editAttivitaId">

                        <div class="edit-field">
                            <label>Nome</label>
                            <input type="text" id="editAttivitaNome" placeholder="Nome attività" required>
                        </div>
                        <div class="edit-field">
                            <label>Descrizione</label>
                            <textarea id="editAttivitaDescrizione" placeholder="Descrizione" required></textarea>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                            <button class="btn-primary" id="salvaModificaAttivita">Salva</button>
                        </div>
                    </form>
                </div>

                <div class="modal-box danger" id="modalDeleteAttivita">
                    <h3>Elimina attività</h3>
                    <p>Questa azione è definitiva. Vuoi continuare?</p>

                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal()">Annulla</button>
                        <button class="btn-danger" id="confirmDeleteAttivita">Elimina</button>
                    </div>
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
                                <th>Costo totale (€)</th>
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

                    <!-- RIEPILOGO TOTALI -->


                    <div class="resoconto-summary" id="resocontoSummary">
                        <div class="summary-card">
                            <div class="summary-label">Ore Totali</div>
                            <div class="summary-value" id="summaryOre">0</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">Costo Totale</div>
                            <div class="summary-value" id="summaryCosto">0 €</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-label">Giorni di Presenza</div>
                            <div class="summary-value" id="summaryGiorni">0</div>
                        </div>
                    </div>

                    <!-- LAYOUT A DUE COLONNE: CALENDARIO + ATTIVITÀ -->
                    <div class="resoconto-calendar-wrapper">
                        <!-- CALENDARIO A SINISTRA -->
                        <div class="calendar-section">
                            <div id="mobileCalendarContainer"></div>
                        </div>
                        
                        <!-- ATTIVITÀ A DESTRA (pannello esterno) -->
                        <div class="activities-section">
                            <div class="mc-activities-panel" id="mc-activities-panel">
                                <div class="mc-activities-placeholder">
                                    Seleziona un giorno per vedere le attività
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- TABELLA RIASSUNTIVA ATTIVITÀ MENSILI -->
                    <div class="users-table-box" style="margin-top: 30px;">
                        <h4 style="margin-bottom: 15px; color: #2b2b2b; font-weight: 600;">Riepilogo Attività Mensile</h4>
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Attività Svolta</th>
                                    <th>Ore Totali</th>
                                </tr>
                            </thead>
                            <tbody id="attivitaMensiliBody"></tbody>
                        </table>
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










            <!-- TAB EDUCATORI -->
            <div class="page-tab" id="tab-educatori">
                <button class="animated-button" id="aggiungi-educatore-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-2" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>

                    <span class="text">Aggiungi Educatore</span>
                    <span class="circle"></span>

                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-1" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="black" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                </button>

                <!-- Modal Aggiungi Educatore -->
                <div class="modal-box large" id="modalAggiungiEducatore">
                    <h3>Aggiungi nuovo educatore</h3>
                    <form id="formAggiungiEducatore">
                        <div class="edit-field">
                            <label>Nome</label>
                            <input type="text" id="educatoreNome" placeholder="Nome" required>
                        </div>
                        <div class="edit-field">
                            <label>Cognome</label>
                            <input type="text" id="educatoreCognome" placeholder="Cognome" required>
                        </div>
                        <div class="edit-field">
                            <label>Data di nascita</label>
                            <input type="date" id="educatoreData" required>
                        </div>
                        <div class="edit-field">
                            <label>Codice Fiscale</label>
                            <input type="text" id="educatoreCF" placeholder="Codice Fiscale" required>
                        </div>
                        <div class="edit-field">
                            <label>Telefono</label>
                            <input type="text" id="educatoreTelefono" placeholder="Telefono">
                        </div>
                        <div class="edit-field">
                            <label>Email</label>
                            <input type="email" id="educatoreMail" placeholder="Email">
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                            <button type="submit" class="btn-primary">Salva</button>
                        </div>
                    </form>
                </div>
                <div class="header-mobile">
                    <div class="page-header">
                        <h1>Educatori</h1>
                        <p>Personale educativo</p>
                    </div>

                    <button
                        title="Add New" id="aggiungi-educatore-btn-mobile"
                        class="group cursor-pointer outline-none hover:rotate-90 duration-300 "
                        >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="50px"
                            height="50px"
                            viewBox="0 0 24 24"
                            class="stroke-zinc-400 fill-none group-hover:fill-zinc-800 group-active:stroke-zinc-200 group-active:fill-zinc-600 group-active:duration-0 duration-300"
                        >
                            <path
                            d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z"
                            stroke-width="1"
                            ></path>
                            <path d="M8 12H16" stroke-width="1"></path>
                            <path d="M12 16V8" stroke-width="1"></path>
                        </svg>
                    </button>
                </div>


                <div class="users-table-box">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Data di nascita</th>
                                <th>Codice Fiscale</th>
                                <th>Telefono</th>
                                <th>Email</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if($resultEducatori && $resultEducatori->num_rows > 0){
                            while($row = $resultEducatori->fetch_assoc()){
                                echo '<tr '
                                    . 'data-id="'.htmlspecialchars($row['id']).'" '
                                    . 'data-nome="'.htmlspecialchars($row['nome']).'" '
                                    . 'data-cognome="'.htmlspecialchars($row['cognome']).'" '
                                    . 'data-nascita="'.htmlspecialchars($row['data_nascita']).'" '
                                    . 'data-cf="'.htmlspecialchars($row['codice_fiscale']).'" '
                                    . 'data-telefono="'.htmlspecialchars($row['telefono']).'" '
                                    . 'data-mail="'.htmlspecialchars($row['mail']).'">'
                                    . '<td>'.htmlspecialchars($row['nome']).'</td>'
                                    . '<td>'.htmlspecialchars($row['cognome']).'</td>'
                                    . '<td>'.htmlspecialchars($row['data_nascita']).'</td>'
                                    . '<td>'.htmlspecialchars($row['codice_fiscale']).'</td>'
                                    . '<td>'.htmlspecialchars($row['telefono']).'</td>'
                                    . '<td>'.htmlspecialchars($row['mail']).'</td>'
                                    . '<td>'
                                        . '<button class="edit-educatore-btn"><img src="immagini/edit.png" alt="Modifica"></button>'
                                        . '<button class="delete-educatore-btn"><img src="immagini/delete.png" alt="Elimina"></button>'
                                    . '</td>'
                                . '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7">Nessun educatore registrato.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

                <!-- MODAL MODIFICA EDUCATORI -->
                <div class="modal-box large" id="modalModificaEducatore">
                    <h3 class="modal-title">Modifica educatore</h3>

                    <form id="formModificaEducatore">
                        <input type="hidden" id="editEducatoreId">

                        <div class="edit-field">
                            <label>Nome</label>
                            <input type="text" id="editEducatoreNome" placeholder="Nome" required>
                        </div>
                        <div class="edit-field">
                            <label>Cognome</label>
                            <input type="text" id="editEducatoreCognome" placeholder="Cognome" required>
                        </div>
                        <div class="edit-field">
                            <label>Data di nascita</label>
                            <input type="date" id="editEducatoreData" required>
                        </div>
                        <div class="edit-field">
                            <label>Codice Fiscale</label>
                            <input type="text" id="editEducatoreCF" placeholder="Codice Fiscale" required>
                        </div>
                        <div class="edit-field">
                            <label>Telefono</label>
                            <input type="text" id="editEducatoreTelefono" placeholder="Telefono">
                        </div>
                        <div class="edit-field">
                            <label>Email</label>
                            <input type="email" id="editEducatoreMail" placeholder="Email">
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                            <button class="btn-primary" id="salvaModificaEducatore">Salva</button>
                        </div>
                    </form>
                </div>

                <div class="modal-box danger" id="modalDeleteEducatore">
                    <h3>Elimina educatore</h3>
                    <p>Questa azione è definitiva. Vuoi continuare?</p>

                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal()">Annulla</button>
                        <button class="btn-danger" id="confirmDeleteEducatore">Elimina</button>
                    </div>
                </div>
            </div>


            <!-- TAB ACCOUNT -->
            <div class="page-tab" id="tab-account">
                <button class="animated-button" id="aggiungi-account-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-2" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>

                    <span class="text">Aggiungi Account</span>
                    <span class="circle"></span>

                    <svg xmlns="http://www.w3.org/2000/svg" class="arr-1" viewBox="0 0 24 24" width="14" height="14">
                        <path d="M12 5v14M5 12h14"
                            stroke="black" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                </button>

                <!-- Modal Aggiungi Account -->
                <div class="modal-box large" id="modalAggiungiAccount">
                    <h3>Aggiungi nuovo account</h3>
                    <form id="formAggiungiAccount">
                        <div class="edit-field">
                            <label>Nome Utente</label>
                            <input type="text" id="accountNomeUtente" placeholder="Nome utente" required>
                        </div>
                        <div class="edit-field">
                            <label>Password</label>
                            <input type="password" id="accountPassword" placeholder="Password" autocomplete="off" autocapitalize="off" autocorrect="off" required>


                        </div>
                        <div class="edit-field">
                            <label>Classe</label>
                            <select id="accountClasse" required>
                                <option value="">Seleziona una classe</option>
                                <option value="Educatore">Educatore</option>
                                <option value="Contabile">Contabile</option>
                                <option value="Amministratore">Amministratore</option>
                            </select>
                        </div>
                        <div class="edit-field">
                            <label>Codice Univoco</label>
                            <input type="text" id="accountCodice" placeholder="Codice univoco" required>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                            <bu pe="submit" class="btn-primary">Salva</button>
                        </div>
                    </form>
                </div>

                <div class="header-mobile">
                    <div class="page-header">
                        <h1>Account</h1>
                        <p>Gestione account Overlimits</p>
                    </div>

                    <button
                        title="Add New" id="aggiungi-account-btn-mobile"
                        class="group cursor-pointer outline-none hover:rotate-90 duration-300 "
                        >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="50px"
                            height="50px"
                            viewBox="0 0 24 24"
                            class="stroke-zinc-400 fill-none group-hover:fill-zinc-800 group-active:stroke-zinc-200 group-active:fill-zinc-600 group-active:duration-0 duration-300"
                        >
                            <path
                            d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z"
                            stroke-width="1"
                            ></path>
                            <path d="M8 12H16" stroke-width="1"></path>
                            <path d="M12 16V8" stroke-width="1"></path>
                        </svg>
                    </button>
                </div>
                <div class="users-table-box">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Nome Utente</th>
                                <th>Password</th>
                                <th>Codice Univoco</th>
                                <th>Classe</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if($resultAccount && $resultAccount->num_rows > 0){
                            while($row = $resultAccount->fetch_assoc()){
                                echo '
                                    <tr
                                        data-nome_utente="'.htmlspecialchars($row['nome_utente']).'" 
                                        data-codice="'.htmlspecialchars($row['codice_univoco']).'" 
                                        data-classe="'.htmlspecialchars($row['classe']).'"
                                    >
                                        <td>'.htmlspecialchars($row['nome_utente']).'</td>
                                        <td>••••••••</td>
                                        <td>'.htmlspecialchars($row['codice_univoco']).'</td>
                                        <td>'.htmlspecialchars($row['classe']).'</td>
                                        <td>
                                            <button class="edit-account-btn"><img src="immagini/edit.png" alt="Modifica"></button>
                                            <button class="delete-account-btn"><img src="immagini/delete.png" alt="Elimina"></button>
                                        </td>
                                    </tr>
                                ';
                            }
                        } else {
                            echo '<tr><td colspan="5">Nessun account registrato.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

                <!-- MODAL MODIFICA ACCOUNT -->
                <div class="modal-box large" id="modalModificaAccount">
                    <h3 class="modal-title">Modifica account</h3>

                    <form id="formModificaAccount">
                        <input type="hidden" id="editAccountNomeUtente">

                        <div class="edit-field">
                            <label>Nome Utente</label>
                            <input type="text" id="editAccountNomeUtenteDisplay" placeholder="Nome utente" disabled>
                        </div>
                        <div class="edit-field">
                            <label>Password (lascia vuoto per non modificare)</label>
                            <input type="password" id="editAccountPassword" placeholder="Password" autocomplete="off" autocapitalize="off" autocorrect="off">


                        </div>
                        <div class="edit-field">
                            <label>Classe</label>
                            <select id="editAccountClasse" required>
                                <option value="">Seleziona una classe</option>
                                <option value="Educatore">Educatore</option>
                                <option value="Contabile">Contabile</option>
                                <option value="Amministratore">Amministratore</option>
                            </select>
                        </div>
                        <div class="edit-field">
                            <label>Codice Univoco</label>
                            <input type="text" id="editAccountCodice" placeholder="Codice univoco" required>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                            <button class="btn-primary" id="salvaModificaAccount">Salva</button>
                        </div>
                    </form>
                </div>

                <div class="modal-box danger" id="modalDeleteAccount">
                    <h3>Elimina account</h3>
                    <p>Questa azione è definitiva. Vuoi continuare?</p>

                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal()">Annulla</button>
                        <button class="btn-danger" id="confirmDeleteAccount">Elimina</button>
                    </div>
                </div>
            </div>



            






            </div>
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
                            <div class="edit-field" id="fieldFotografia">
                                <label>Fotografia</label>
                                <div class="file-inline" id="editFileContainer">
                                    <input type="file" id="editFoto" accept="image/*" hidden>
                                    <button type="button" class="file-btn-minimal" onclick="document.getElementById('editFoto').click()">
                                        Scegli file
                                    </button>
                                    <div class="file-preview-container">
                                        <img id="editPreviewFotoMini" class="preview-mini" style="display:none;">
                                        <button type="button" id="editClearFileBtn" class="clear-file-btn" title="Rimuovi file" style="display:none;">&times;</button>
                                    </div>
                                    <span class="file-name" id="editNomeFileFoto">Nessun file</span>
                                </div>
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
            <a href="#" class="mobile-nav-item" data-tab="tab-agenda" onclick="switchTab('tab-agenda', this); return false;">
                <div class="mobile-nav-icon">
                    <img src="immagini/book.png" alt="Agenda">
                </div>
                <span class="mobile-nav-label">Agenda</span>
            </a>

            <a href="#" class="mobile-nav-item" data-tab="tab-attivita" onclick="switchTab('tab-attivita', this); return false;">
                <div class="mobile-nav-icon">
                    <img src="immagini/attivita.png" alt="attivita">
                </div>
                <span class="mobile-nav-label">Attività</span>
            </a>

            <a href="#" class="mobile-nav-item" data-tab="tab-resoconti" onclick="switchTab('tab-resoconti', this); return false;">
                <div class="mobile-nav-icon">
                    <img src="immagini/resoconti.png" alt="resoconti">
                </div>
                <span class="mobile-nav-label">Resoconti</span>
            </a>

            <a href="#" class="mobile-nav-item" data-tab="tab-educatori" onclick="switchTab('tab-educatori', this); return false;">
                <div class="mobile-nav-icon">
                    <img src="immagini/educatore.png" alt="educatori">
                </div>
                <span class="mobile-nav-label">Educatori</span>
            </a>

            <a href="#" class="mobile-nav-item" data-tab="tab-account" onclick="switchTab('tab-account', this); return false;">
                <div class="mobile-nav-icon">
                    <img src="immagini/account.png" alt="account">
                </div>
                <span class="mobile-nav-label">Account</span>
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

    window.addEventListener("DOMContentLoaded", () => {
        const savedTab = localStorage.getItem("activeTab");

        if (savedTab) {
            document.querySelectorAll(".tab-link").forEach(l => l.classList.remove("active"));
            document.querySelectorAll(".page-tab").forEach(tab => tab.classList.remove("active"));

            const link = document.querySelector(`.tab-link[data-tab="${savedTab}"]`);
            const page = document.getElementById(savedTab);
            if (link && page) {
                link.classList.add("active");
                page.classList.add("active");
            }
        }
    });


            /* HAMBURGER */
        const ham = document.getElementById("hamburger");
        const drop = document.getElementById("dropdown");
        ham.onclick = () => {
            ham.classList.toggle("active");
            drop.classList.toggle("show");
        };
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

            // toggle quello cliccato
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
    const Overlay = document.getElementById("Overlay") || document.getElementById("modalOverlay");
    const viewModal = document.getElementById("viewModal");
    const editModal = document.getElementById("editModal");
    const deleteModal = document.getElementById("deleteModal");
    const modalAggiungiAttivita = document.getElementById("modalAggiungiAttivita");
    const successText = document.getElementById("success-text");

    function openModal(modal){
        modal.classList.add("show");
        if(Overlay) Overlay.classList.add("show");
    }
    function closeModal(){
        if(Overlay) Overlay.classList.remove("show");
        document.querySelectorAll(".modal-box.show").forEach(el => el.classList.remove("show"));
        const attivitaOverlay = document.getElementById("attivitaOverlay");
        if(attivitaOverlay) attivitaOverlay.classList.remove("show");
        const agendaOverlay = document.getElementById("agendaOverlay");
        if(agendaOverlay) agendaOverlay.classList.remove("show");
    }

    function showSuccess(popup, overlay) {
        if (popup) popup.classList.add("show");
        if (overlay) overlay.classList.add("show");
    }
    function hideSuccess(popup, overlay) {
        if (popup) popup.classList.remove("show");
        if (overlay) overlay.classList.remove("show");
    }


    // Popup view
    document.querySelectorAll(".view-btn").forEach(btn=>{
        btn.onclick = async e=>{
            const row = e.target.closest("tr");

            const avatar = row.querySelector("img").src;
            const nome = row.dataset.nome;
            const cognome = row.dataset.cognome;
            const data = row.dataset.nascita;
            const note = row.dataset.note;
            const cf = row.dataset.cf;
            const email = row.dataset.email;
            const telefono = row.dataset.telefono;
            const disabilita = row.dataset.disabilita;
            const idIscritto = row.dataset.id;

            const intolleranze = row.dataset.intolleranze;
            const prezzo = row.dataset.prezzo;

            document.getElementById("viewAvatar").src = avatar;
            document.getElementById("viewFullname").innerText = nome + " " + cognome;
            document.getElementById("viewBirth").innerText = "Nato il " + data;

            document.getElementById("viewContent").innerHTML = `
                <div class="profile-field"><label>Nome</label><span>${nome}</span></div>
                <div class="profile-field"><label>Cognome</label><span>${cognome}</span></div>
                <div class="profile-field"><label>Data di nascita</label><span>${data}</span></div>
                <div class="profile-field"><label>Codice Fiscale</label><span>${cf || "—"}</span></div>
                <div class="profile-field"><label>Email</label><span>${email || "—"}</span></div>
                <div class="profile-field"><label>Telefono</label><span>${telefono || "—"}</span></div>
                <div class="profile-field"><label>Disabilità</label><span>${disabilita || "—"}</span></div>

                <div class="profile-field"><label style="font-weight: bold;">Intolleranze ⚠️</label><span style="font-weight: bold;">${intolleranze || "—"}</span></div>
                <div class="profile-field"><label>Prezzo orario</label><span>${prezzo || "—"} €</span></div>
                <div class="profile-field" style="grid-column:1 / -1;"><label>Note</label><span>${note || "—"}</span></div>
            `;

            // Carica allegati
            await caricaAllegatiUtente(idIscritto);

            openModal(viewModal);
        }
    });

    // Funzione per caricare gli allegati di un utente
    async function caricaAllegatiUtente(idIscritto) {
        const allegatiListDiv = document.getElementById("viewAllegatiList");
        allegatiListDiv.innerHTML = '<p style="color: #888; font-style: italic;">Caricamento allegati...</p>';
        
        try {
            const response = await fetch(`api/api_get_allegati.php?id_iscritto=${idIscritto}`);
            const data = await response.json();
            
            if (!data.success || !data.allegati || data.allegati.length === 0) {
                allegatiListDiv.innerHTML = '<p style="color: #888; font-style: italic;">Nessun allegato presente</p>';
                return;
            }
            
            let html = '<div class="allegati-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">';
            
            data.allegati.forEach(allegato => {
                const icon = getAllegatoIcon(allegato.tipo);
                const dataFormattata = new Date(allegato.data_upload).toLocaleDateString('it-IT');
                
                html += `
                    <div class="allegato-card" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; display: flex; align-items: center; gap: 10px; background: #f9f9f9;">
                        <div class="allegato-icon-big" style="font-size: 32px;">${icon}</div>
                        <div class="allegato-info" style="flex: 1; min-width: 0;">
                            <div class="allegato-name" style="font-weight: 500; color: #2b2b2b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${allegato.nome_file}">${allegato.nome_file}</div>
                            <div class="allegato-date" style="font-size: 12px; color: #888;">${dataFormattata}</div>
                        </div>
                        <a href="${allegato.percorso}" target="_blank" class="allegato-download" style="color: #640a35; text-decoration: none; font-size: 20px;" title="Scarica">⬇️</a>
                    </div>
                `;
            });
            
            html += '</div>';
            allegatiListDiv.innerHTML = html;
            
        } catch (error) {
            console.error("Errore caricamento allegati:", error);
            allegatiListDiv.innerHTML = '<p style="color: #d32f2f; font-style: italic;">Errore nel caricamento degli allegati</p>';
        }
    }
    
    // Funzione per ottenere l'icona in base al tipo di file
    function getAllegatoIcon(tipo) {
        const icons = {
            'pdf': '📄',
            'doc': '📝',
            'docx': '📝',
            'image': '🖼️',
            'xls': '📊',
            'xlsx': '📊',
            'txt': '📃',
            'file': '📎'
        };
        return icons[tipo] || icons['file'];
    }

    if(Overlay) Overlay.onclick = closeModal;

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
            const avatar = row.querySelector('img').src;

            // Nascondi i campi utente e mostra quelli presenza
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

            // Estrai solo l'ora dal formato DB (YYYY-MM-DD HH:MM:SS)
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

            // Imposta il listener sul bottone "Elimina" nella modale
            const confirmDelete = deleteModal.querySelector('.btn-danger');
            confirmDelete.onclick = () => {
                fetch('api/api_elimina_presenza.php', {
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

        // File input handlers for edit modal
        const editFoto = document.getElementById("editFoto");
        const editPreview = document.getElementById("editPreviewFotoMini");
        const editFileNameSpan = document.getElementById("editNomeFileFoto");
        const editClearBtn = document.getElementById("editClearFileBtn");

        if(editFoto) {
            editFoto.addEventListener("change", function(){
                if(!this.files.length){
                    editPreview.style.display = "none";
                    editFileNameSpan.innerText = "Nessun file";
                    editClearBtn.style.display = "none"; 
                    return;
                }

                const file = this.files[0];

                editPreview.src = URL.createObjectURL(file);
                editPreview.style.display = "block";

                editFileNameSpan.innerText = file.name;

                editClearBtn.style.display = "block"; 
            });

            // rimuove file selezionato
            editClearBtn.addEventListener("click", function(){
                editFoto.value = ""; // reset input
                editPreview.style.display = "none";
                editFileNameSpan.innerText = "Nessun file";
                editClearBtn.style.display = "none"; 
            });
        }

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
                document.getElementById('fieldFotografia').style.display = 'block';
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

                // Reset file input
                if(editFoto) editFoto.value = "";
                if(editPreview) editPreview.style.display = "none";
                if(editFileNameSpan) editFileNameSpan.innerText = "Nessun file";
                if(editClearBtn) editClearBtn.style.display = "none";

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

                fetch('api/api_modifica_presenza.php', {
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
                const fotoInput = document.getElementById("editFoto");

                // Se c'è un file selezionato, usa FormData
                if(fotoInput && fotoInput.files.length > 0) {
                    const formData = new FormData();
                    formData.append("id", id);
                    formData.append("nome", document.getElementById("editNome").value);
                    formData.append("cognome", document.getElementById("editCognome").value);
                    formData.append("data_nascita", document.getElementById("editData").value);
                    formData.append("codice_fiscale", document.getElementById("editCF").value);
                    formData.append("email", document.getElementById("editEmail").value);
                    formData.append("telefono", document.getElementById("editTelefono").value);

                    formData.append("disabilita", document.getElementById("editDisabilita").value);
                    formData.append("intolleranze", document.getElementById("editIntolleranze").value);
                    formData.append("prezzo_orario", document.getElementById("editPrezzo").value);
                    formData.append("note", document.getElementById("editNote").value);
                    formData.append("foto", fotoInput.files[0]);

                    fetch('api/api_aggiorna_utente.php', {
                        method: 'POST',
                        body: formData
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
                } else {
                    // Nessun file, usa JSON come prima
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
            }

        };







        // SEZIONE ATTIVITA !!!!!!!!!!!!!!!

        // MODAL AGGIUNGI ATTIVITA
        const aggiungiAttivitaBtn = document.getElementById("aggiungiAttivitaBtn");
        // point attivita overlay to the single global overlay if present
        const attivitaOverlay = document.getElementById("attivitaOverlay") || Overlay;

        aggiungiAttivitaBtn.onclick = () => {
            openModal(modalAggiungiAttivita);
        } 

        attivitaOverlay.onclick = () => {
            closeModal();
        };

        const salvaAttivitaBtn = document.getElementById("salvaAttivita");
        const formAttivita = document.getElementById("formAttivita");
        salvaAttivitaBtn.onclick = function(e){
            e.preventDefault();
            if(!formAttivita.reportValidity()) return;

            const nome = document.getElementById("attivitaNome").value.trim();
            const descrizione = document.getElementById("attivitaDescrizione").value.trim();

            fetch("api/api_aggiungi_attivita.php", {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({ nome: nome, descrizione: descrizione })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){       
                    modalAggiungiAttivita.classList.remove("show");
                    successText.innerText = "Attività Aggiunta!!";
                    showSuccess(successPopup, Overlay);
                    

                    setTimeout(() => {
                        hideSuccess(successPopup, Overlay);
                        attivitaOverlay.classList.remove("show");
                        location.reload();
                    }, 1800);

                } else {
                    alert("Errore: " + data.message);
                }
            });
        };








        // MODIFICA E ELIMINA ATTIVITA

        const modalModificaAttivita = document.getElementById("modalModificaAttivita");
        const deleteAttivitaModal = document.getElementById("modalDeleteAttivita");
        const successPopupModificaAttivita = document.getElementById("successPopupModificaAttivita");

        // MODIFICA
        document.querySelectorAll(".edit-attivita-btn").forEach(btn => {
            btn.onclick = e => {
                const row = btn.closest("tr");
                const id = row.dataset.id;
                const nome = row.children[0].innerText;
                const descr = row.children[1].innerText;

                document.getElementById("editAttivitaId").value = id;
                document.getElementById("editAttivitaNome").value = nome;
                document.getElementById("editAttivitaDescrizione").value = descr;

                openModal(modalModificaAttivita);
                attivitaOverlay.classList.add("show");
            }
        });

        // Salva modifica
        document.getElementById("salvaModificaAttivita").onclick = e => {
            e.preventDefault();
            const id = document.getElementById("editAttivitaId").value;
            const nome = document.getElementById("editAttivitaNome").value.trim();
            const descr = document.getElementById("editAttivitaDescrizione").value.trim();

            fetch("api/api_modifica_attivita.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({id, nome, descrizione: descr})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){       
                    modalModificaAttivita.classList.remove("show");
                    successText.innerText = "Attività Modificata!!";
                    showSuccess(successPopup, Overlay);
                    


                    setTimeout(() => {
                        hideSuccess(successPopup, Overlay);
                        attivitaOverlay.classList.remove("show");
                        location.reload();
                    }, 1800);

                } else {
                    alert("Errore: " + data.message);
                }
            });
        };

        // ELIMINA
        let rowToDelete = null;
        document.querySelectorAll(".delete-attivita-btn").forEach(btn => {
            btn.onclick = e => {
                rowToDelete = btn.closest("tr");
                openModal(deleteAttivitaModal);
                attivitaOverlay.classList.add("show");
            }
        });

        document.getElementById("confirmDeleteAttivita").onclick = () => {
            if(!rowToDelete) return;
            const id = rowToDelete.dataset.id;

            fetch("api/api_elimina_attivita.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({id})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){       
                    deleteAttivitaModal.classList.remove("show");
                    successText.innerText = "Attività Eliminata!!";
                    showSuccess(successPopup, Overlay);
                    


                    setTimeout(() => {
                        hideSuccess(successPopup, Overlay);
                        attivitaOverlay.classList.remove("show");
                        location.reload();
                    }, 1800);

                } else {
                    alert("Errore: " + data.message);
                }
            });
        };







        

        const aggiungiUtenteBtn = document.getElementById("aggiungi-utente-btn");
        const aggiungiUtenteBtnMobile = document.getElementById("aggiungi-utente-btn-mobile");
        const modalAggiungiUtente = document.getElementById("modalAggiungiUtente");
        const formAggiungiUtente = document.getElementById("formAggiungiUtente");

        // ===== GESTIONE ALLEGATI =====
        const allegatiInput = document.getElementById("utenteAllegati");
        const allegatiDropZone = document.getElementById("allegatiDropZone");
        const allegatiList = document.getElementById("allegatiList");
        const allegatiItems = document.getElementById("allegatiItems");
        const clearAllAllegati = document.getElementById("clearAllAllegati");
        
        let selectedAllegati = [];

        // Formatta dimensione file
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Ottieni icona per tipo file
        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            const icons = {
                'pdf': '📄',
                'doc': '📝', 'docx': '📝',
                'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️',
                'txt': '📃',
                'xls': '📊', 'xlsx': '📊'
            };
            return icons[ext] || '📎';
        }

        // Aggiorna lista allegati
        function updateAllegatiList() {
            if (selectedAllegati.length === 0) {
                allegatiList.style.display = 'none';
                return;
            }

            allegatiList.style.display = 'block';
            allegatiItems.innerHTML = '';

            selectedAllegati.forEach((file, index) => {
                const li = document.createElement('li');
                li.className = 'allegato-item';
                li.innerHTML = `
                    <div class="allegato-info">
                        <span class="allegato-icon">${getFileIcon(file.name)}</span>
                        <div class="allegato-details">
                            <span class="allegato-name" title="${file.name}">${file.name}</span>
                            <span class="allegato-size">${formatFileSize(file.size)}</span>
                            <div class="allegato-progress" id="progress-${index}" style="display:none;">
                                <div class="allegato-progress-bar" id="progress-bar-${index}"></div>
                            </div>
                            <div class="allegato-status" id="status-${index}"></div>
                        </div>
                    </div>
                    <button type="button" class="allegato-remove" data-index="${index}" title="Rimuovi">×</button>
                `;
                allegatiItems.appendChild(li);
            });

            // Aggiungi event listener per rimuovere
            document.querySelectorAll('.allegato-remove').forEach(btn => {
                btn.onclick = function() {
                    const idx = parseInt(this.dataset.index);
                    selectedAllegati.splice(idx, 1);
                    updateAllegatiList();
                };
            });
        }

        // Gestione selezione file
        if (allegatiInput) {
            allegatiInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    // Validazione dimensione (max 10MB)
                    const maxSize = 10 * 1024 * 1024;
                    const validFiles = Array.from(this.files).filter(file => {
                        if (file.size > maxSize) {
                            alert(`File "${file.name}" troppo grande (max 10MB)`);
                            return false;
                        }
                        return true;
                    });
                    
                    selectedAllegati = [...selectedAllegati, ...validFiles];
                    updateAllegatiList();
                }
            });
        }

        // Drag and drop
        if (allegatiDropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                allegatiDropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                allegatiDropZone.addEventListener(eventName, () => {
                    allegatiDropZone.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                allegatiDropZone.addEventListener(eventName, () => {
                    allegatiDropZone.classList.remove('dragover');
                }, false);
            });

            allegatiDropZone.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                const maxSize = 10 * 1024 * 1024;
                const validFiles = Array.from(files).filter(file => {
                    if (file.size > maxSize) {
                        alert(`File "${file.name}" troppo grande (max 10MB)`);
                        return false;
                    }
                    return true;
                });
                
                selectedAllegati = [...selectedAllegati, ...validFiles];
                updateAllegatiList();
            });

            allegatiDropZone.addEventListener('click', function(e) {
                if (e.target !== allegatiInput) {
                    allegatiInput.click();
                }
            });
        }

        // Rimuovi tutti gli allegati
        if (clearAllAllegati) {
            clearAllAllegati.onclick = function() {
                selectedAllegati = [];
                updateAllegatiList();
            };
        }

        // Upload allegati per un utente
        async function uploadAllegati(idIscritto) {
            if (selectedAllegati.length === 0) return { success: true };

            const results = [];
            
            for (let i = 0; i < selectedAllegati.length; i++) {
                const file = selectedAllegati[i];
                const progressBar = document.getElementById(`progress-bar-${i}`);
                const progressContainer = document.getElementById(`progress-${i}`);
                const statusDiv = document.getElementById(`status-${i}`);

                if (progressContainer) progressContainer.style.display = 'block';
                if (statusDiv) statusDiv.textContent = 'Caricamento...';

                const formData = new FormData();
                formData.append('id_iscritto', idIscritto);
                formData.append('allegato', file);

                try {
                    const response = await fetch('api/api_carica_allegato.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'include'
                    });

                    const data = await response.json();

                    if (data.success) {
                        if (progressBar) {
                            progressBar.style.width = '100%';
                            progressBar.classList.add('complete');
                        }
                        if (statusDiv) {
                            statusDiv.textContent = '✓ Caricato';
                            statusDiv.classList.add('complete');
                        }
                        results.push({ success: true, file: file.name });
                    } else {
                        if (progressBar) progressBar.classList.add('error');
                        if (statusDiv) {
                            statusDiv.textContent = '✗ Errore: ' + data.message;
                            statusDiv.classList.add('error');
                        }
                        results.push({ success: false, file: file.name, error: data.message });
                    }
                } catch (error) {
                    if (progressBar) progressBar.classList.add('error');
                    if (statusDiv) {
                        statusDiv.textContent = '✗ Errore di rete';
                        statusDiv.classList.add('error');
                    }
                    results.push({ success: false, file: file.name, error: error.message });
                }
            }

            return results;
        }

        const aggiungiAgendaBtnMobile = document.getElementById("aggiungi-agenda-btn-mobile");
        const aggiungiAttivitaBtnMobile = document.getElementById("aggiungi-attivita-btn-mobile");
        const aggiungiEducatoreBtnMobile = document.getElementById("aggiungi-educatore-btn-mobile");
        const aggiungiAccountBtnMobile = document.getElementById("aggiungi-account-btn-mobile");


        // Apri modal (desktop)
        if(aggiungiUtenteBtn) {
            aggiungiUtenteBtn.onclick = () => {
                openModal(modalAggiungiUtente);
            };
        }

        // Apri modal (mobile)
        if(aggiungiUtenteBtnMobile) {
            aggiungiUtenteBtnMobile.onclick = () => {
                openModal(modalAggiungiUtente);
            };
        }

        if(aggiungiAgendaBtnMobile) {
            aggiungiAgendaBtnMobile.onclick = () => {
                openModal(modalCreaAgenda);
            };
        }

        if(aggiungiAttivitaBtnMobile) {
            aggiungiAttivitaBtnMobile.onclick = () => {
                openModal(modalAggiungiAttivita);
            };
        }

        if(aggiungiEducatoreBtnMobile) {
            aggiungiEducatoreBtnMobile.onclick = () => {
                openModal(modalAggiungiEducatore);
            };
        }

        if(aggiungiAccountBtnMobile) {
            aggiungiAccountBtnMobile.onclick = () => {
                openModal(modalAggiungiAccount);
            };
        }


        // Submit form
        formAggiungiUtente.onsubmit = function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append("nome", document.getElementById("utenteNome").value.trim());
            formData.append("cognome", document.getElementById("utenteCognome").value.trim());
            formData.append("data_nascita", document.getElementById("utenteData").value);
            formData.append("codice_fiscale", document.getElementById("utenteCF").value.trim());
            formData.append("email", document.getElementById("utenteEmail").value.trim());
            formData.append("telefono", document.getElementById("utenteTelefono").value.trim());

            formData.append("disabilita", document.getElementById("utenteDisabilita").value.trim());
            formData.append("intolleranze", document.getElementById("utenteIntolleranze").value.trim());
            formData.append("prezzo_orario", parseFloat(document.getElementById("utentePrezzo").value));
            formData.append("note", document.getElementById("utenteNote").value.trim());

            const fotoInput = document.getElementById("utenteFoto");
            if(fotoInput.files.length > 0){
                formData.append("foto", fotoInput.files[0]); 
            }

            fetch("api/api_aggiungi_utente.php", {
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
            utenteFoto.value = ""; // reset input
            preview.style.display = "none";
            fileNameSpan.innerText = "Nessun file";
            clearBtn.style.display = "none"; 
        });




        // SEZIONE ACCOUNT !!!!!!!!!!!!!!!

        const aggiungiAccountBtn = document.getElementById("aggiungi-account-btn");
        const modalAggiungiAccount = document.getElementById("modalAggiungiAccount");
        const formAggiungiAccount = document.getElementById("formAggiungiAccount");
        const modalModificaAccount = document.getElementById("modalModificaAccount");
        const modalDeleteAccount = document.getElementById("modalDeleteAccount");

        // Apri modal
        aggiungiAccountBtn.onclick = () => {
            openModal(modalAggiungiAccount);
        };

        // Submit form
        formAggiungiAccount.onsubmit = function(e) {
            e.preventDefault();

            const nomeUtente = document.getElementById("accountNomeUtente").value.trim();
            const password = document.getElementById("accountPassword").value.trim();
            const classe = document.getElementById("accountClasse").value;
            const codice = document.getElementById("accountCodice").value.trim();

            if(!nomeUtente || !password || !classe || !codice){
                alert("Compila tutti i campi!");
                return;
            }

            fetch("api/api_aggiungi_account.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({ 
                    nome_utente: nomeUtente, 
                    password: password,
                    classe: classe,
                    codice_univoco: codice
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){       
                    modalAggiungiAccount.classList.remove("show");
                    successText.innerText = "Account Aggiunto!!";
                    showSuccess(successPopup, Overlay);

                    setTimeout(() => {
                        hideSuccess(successPopup, Overlay);
                        if(Overlay) Overlay.classList.remove("show");
                        location.reload();
                    }, 1800);

                } else {
                    alert("Errore: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Errore nel caricamento!");
            });
        };


        // MODIFICA ACCOUNT
        document.querySelectorAll(".edit-account-btn").forEach(btn => {
            btn.onclick = e => {
                const row = btn.closest("tr");
                const nomeUtente = row.dataset.nome_utente;
                const classe = row.dataset.classe;
                const codice = row.dataset.codice;

                document.getElementById("editAccountNomeUtente").value = nomeUtente;
                document.getElementById("editAccountNomeUtenteDisplay").value = nomeUtente;
                document.getElementById("editAccountClasse").value = classe;
                document.getElementById("editAccountCodice").value = codice;
                document.getElementById("editAccountPassword").value = "";

                openModal(modalModificaAccount);
            }
        });

        // Salva modifica account
        document.getElementById("salvaModificaAccount").onclick = e => {
            e.preventDefault();
            const nomeUtente = document.getElementById("editAccountNomeUtente").value;
            const password = document.getElementById("editAccountPassword").value.trim();
            const classe = document.getElementById("editAccountClasse").value;
            const codice = document.getElementById("editAccountCodice").value.trim();

            if(!classe || !codice){
                alert("Compila tutti i campi obbligatori!");
                return;
            }

            fetch("api/api_modifica_account.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({
                    nome_utente: nomeUtente,
                    password: password,
                    classe: classe,
                    codice_univoco: codice
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){       
                    modalModificaAccount.classList.remove("show");
                    successText.innerText = "Account Modificato!!";
                    showSuccess(successPopup, Overlay);

                    setTimeout(() => {
                        hideSuccess(successPopup, Overlay);
                        closeModal();
                        location.reload();
                    }, 1800);

                } else {
                    alert("Errore: " + data.message);
                }
            });
        };

        // ELIMINA ACCOUNT
        let rowToDeleteAccount = null;
        document.querySelectorAll(".delete-account-btn").forEach(btn => {
            btn.onclick = e => {
                rowToDeleteAccount = btn.closest("tr");
                const nomeUtente = rowToDeleteAccount.dataset.nome_utente;
                
                openModal(modalDeleteAccount);
                
                // Aggiorna il titolo del modal
                document.querySelector("#modalDeleteAccount h3").innerText = "Elimina account: " + nomeUtente;
            }
        });

        document.getElementById("confirmDeleteAccount").onclick = () => {
            if(!rowToDeleteAccount) return;
            const nomeUtente = rowToDeleteAccount.dataset.nome_utente;

            fetch("api/api_elimina_account.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({nome_utente: nomeUtente})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){       
                    modalDeleteAccount.classList.remove("show");
                    successText.innerText = "Account Eliminato!!";
                    showSuccess(successPopup, Overlay);

                    setTimeout(() => {
                        hideSuccess(successPopup, Overlay);
                        if(Overlay) Overlay.classList.remove("show");
                        location.reload();
                    }, 1800);

                } else {
                    alert("Errore: " + data.message);
                }
            });
        };


        // ---------- SEZIONE EDUCATORI (JS handlers) ----------
        const aggiungiEducatoreBtn = document.getElementById("aggiungi-educatore-btn");
        const modalAggiungiEducatore = document.getElementById("modalAggiungiEducatore");
        const formAggiungiEducatore = document.getElementById("formAggiungiEducatore");
        const modalModificaEducatore = document.getElementById("modalModificaEducatore");
        const modalDeleteEducatore = document.getElementById("modalDeleteEducatore");

        if(aggiungiEducatoreBtn){
            aggiungiEducatoreBtn.onclick = () => {
                openModal(modalAggiungiEducatore);
            };
        }

        if(formAggiungiEducatore){
            formAggiungiEducatore.onsubmit = function(e){
                e.preventDefault();

                const nome = document.getElementById("educatoreNome").value.trim();
                const cognome = document.getElementById("educatoreCognome").value.trim();
                const data_nascita = document.getElementById("educatoreData").value;
                const codice_fiscale = document.getElementById("educatoreCF").value.trim();
                const telefono = document.getElementById("educatoreTelefono").value.trim();
                const mail = document.getElementById("educatoreMail").value.trim();

                if(!nome || !cognome){
                    alert("Compila tutti i campi obbligatori!");
                    return;
                }

                fetch("api/api_aggiungi_educatore.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({ nome, cognome, data_nascita, codice_fiscale, telefono, mail })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success){
                        modalAggiungiEducatore.classList.remove("show");
                        successText.innerText = "Educatore Aggiunto!!";
                        showSuccess(successPopup, Overlay);

                        setTimeout(() => {
                            hideSuccess(successPopup, Overlay);
                            if(Overlay) Overlay.classList.remove("show");
                            location.reload();
                        }, 1400);
                    } else {
                        alert("Errore: " + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Errore nel caricamento!");
                });
            };
        }

        // Edit Educatore
        document.querySelectorAll(".edit-educatore-btn").forEach(btn => {
            btn.onclick = e => {
                const row = btn.closest("tr");
                const id = row.dataset.id;
                const nome = row.children[0].innerText;
                const cognome = row.children[1].innerText;
                const data_nascita = row.children[2] ? row.children[2].innerText : "";
                const codice_fiscale = row.children[3] ? row.children[3].innerText : "";
                const telefono = row.children[4] ? row.children[4].innerText : "";
                const mail = row.children[5] ? row.children[5].innerText : "";

                document.getElementById("editEducatoreId").value = id;
                document.getElementById("editEducatoreNome").value = nome;
                document.getElementById("editEducatoreCognome").value = cognome;
                document.getElementById("editEducatoreData").value = data_nascita;
                document.getElementById("editEducatoreCF").value = codice_fiscale;
                document.getElementById("editEducatoreTelefono").value = telefono;
                document.getElementById("editEducatoreMail").value = mail;

                openModal(modalModificaEducatore);
            };
        });

        // Save edit educatore
        const salvaModificaEducatoreBtn = document.getElementById("salvaModificaEducatore");
        if(salvaModificaEducatoreBtn){
            salvaModificaEducatoreBtn.onclick = e => {
                e.preventDefault();
                const id = document.getElementById("editEducatoreId").value;
                const nome = document.getElementById("editEducatoreNome").value.trim();
                const cognome = document.getElementById("editEducatoreCognome").value.trim();
                const data_nascita = document.getElementById("editEducatoreData").value;
                const codice_fiscale = document.getElementById("editEducatoreCF").value.trim();
                const telefono = document.getElementById("editEducatoreTelefono").value.trim();
                const mail = document.getElementById("editEducatoreMail").value.trim();

                if(!nome || !cognome){
                    alert("Compila tutti i campi obbligatori!");
                    return;
                }

                fetch("api/api_modifica_educatore.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({ id, nome, cognome, data_nascita, codice_fiscale, telefono, mail })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success){
                        modalModificaEducatore.classList.remove("show");
                        successText.innerText = "Educatore Modificato!!";
                        showSuccess(successPopup, Overlay);

                        setTimeout(() => {
                            hideSuccess(successPopup, Overlay);
                            if(Overlay) Overlay.classList.remove("show");
                            location.reload();
                        }, 1400);
                    } else {
                        alert("Errore: " + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Errore nella modifica!");
                });
            };
        }

        // Delete Educatore
        let rowToDeleteEducatore = null;
        document.querySelectorAll(".delete-educatore-btn").forEach(btn => {
            btn.onclick = e => {
                rowToDeleteEducatore = btn.closest("tr");
                const nome = rowToDeleteEducatore.children[0].innerText;
                document.querySelector("#modalDeleteEducatore h3").innerText = "Elimina educatore: " + nome;
                modalDeleteEducatore.classList.add("show");
                if(Overlay) Overlay.classList.add("show");
            };
        });

        document.getElementById("confirmDeleteEducatore")?.addEventListener("click", () => {
            if(!rowToDeleteEducatore) return;
            const id = rowToDeleteEducatore.dataset.id;

            fetch("api/api_elimina_educatore.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({ id })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    modalDeleteEducatore.classList.remove("show");
                    successText.innerText = "Educatore Eliminato!!";
                    showSuccess(successPopup, Overlay);

                    setTimeout(() => {
                        hideSuccess(successPopup, Overlay);
                        if(Overlay) Overlay.classList.remove("show");
                        location.reload();
                    }, 1800);
                } else {
                    alert("Errore: " + data.message);
                }
            });
        });

    
        
       // ================= AGENDA =================
let agendaData = [];
let agendaWeekStart = null;
let selectedDayIndex = 0;
let currentMonday = null;

// utilità: YYYY-MM-DD locale
function getLocalDateString(date) {
    const y = date.getFullYear();
    const m = (date.getMonth() + 1).toString().padStart(2,'0');
    const d = date.getDate().toString().padStart(2,'0');
    return `${y}-${m}-${d}`;
}

// calcola le date della settimana e aggiorna le label
function calculateWeekDates(weekStartStr) {
    const today = new Date();
    let monday;

    if (weekStartStr) {
        const parts = weekStartStr.split('-'); // "YYYY-MM-DD"
        monday = new Date(parts[0], parts[1]-1, parts[2]);
    } else {
        monday = new Date(today);
        monday.setDate(today.getDate() - today.getDay() + 1); // lunedì
    }

    currentMonday = monday;

    const dates = [];
    const dateLabels = ['date-monday','date-tuesday','date-wednesday','date-thursday','date-friday'];

    for (let i=0; i<5; i++) {
        const date = new Date(monday);
        date.setDate(monday.getDate() + i);
        dates.push(getLocalDateString(date));

        const label = document.getElementById(dateLabels[i]);
        if (label) {
            const dateStr = date.toLocaleDateString('it-IT',{day:'2-digit',month:'2-digit'});
            label.innerText = dateStr;
        }
    }

    return dates;
}

// carica agenda dal server
function loadAgenda() {
    const contentDiv = document.getElementById('agendaContent');
    contentDiv.innerHTML = '<div class="loading">Caricamento attività...</div>';

    fetch('api/api_get_agenda.php')
        .then(res => res.json())
        .then(data => {
            if(data.success){
                agendaData = data.data || [];
                agendaWeekStart = data.monday || null;
                calculateWeekDates(agendaWeekStart);
                let defaultDayIndex = new Date().getDay() - 1; // 0 lunedi
                if (defaultDayIndex < 0 || defaultDayIndex > 4) defaultDayIndex = 0; //weekend forza a lunedi


                const savedDayIndex = parseInt(localStorage.getItem("selectedDayIndex")) ?? defaultDayIndex;
                displayAgenda(savedDayIndex);
            } else {
                contentDiv.innerHTML = '<div class="error-message">Errore: ' + (data.error || 'Sconosciuto') + '</div>';
            }
        })
        .catch(err => {
            console.error(err);
            contentDiv.innerHTML = '<div class="error-message">Errore nel caricamento dell\'agenda</div>';
        });
}

// mostra attività per il giorno selezionato
function displayAgenda(dayIndex){
    selectedDayIndex = dayIndex;
    localStorage.setItem("selectedDayIndex", dayIndex);

    // Aggiorna l'aspetto dei tab dei giorni
    document.querySelectorAll('.day-tab').forEach((tab, index) => {
        if (index === dayIndex) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });

    document.querySelector('.days-tabs').style.setProperty('--active-index', dayIndex);

    // Scroll del tab attivo in vista su mobile al caricamento
    if(window.innerWidth <= 768) {
        const activeTab = document.querySelector('.day-tab.active');
        if(activeTab) {
            activeTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }

    const contentDiv = document.getElementById('agendaContent');


    if(!agendaData || agendaData.length === 0){
        contentDiv.innerHTML = '<div class="no-activities">Nessuna attività disponibile</div>';
        return;
    }

    

    const selectedDate = new Date(currentMonday);
    selectedDate.setDate(currentMonday.getDate() + dayIndex);
    const selectedDateStr = getLocalDateString(selectedDate);

    // filtra attività per giorno
    const dayActivities = agendaData.filter(att => att.data === selectedDateStr);

    if(dayActivities.length === 0){
        contentDiv.innerHTML = '<div class="no-activities">Nessuna attività per questo giorno</div>';
        return;
    }

    // ordina per ora_inizio
    dayActivities.sort((a,b)=> a.ora_inizio.localeCompare(b.ora_inizio));

    let html = '<div class="activities-list">';

    dayActivities.forEach(att => {
        // orari
        const inizio_no_seconds = att.ora_inizio.substring(0,5);
        const fine_no_seconds   = att.ora_fine.substring(0,5);

        // educatori unici
        const educatoriText = Array.from(
            new Map(att.educatori.map(e=>[e.id,e])).values()
        ).map(e=>`${e.nome} ${e.cognome}`).join(', ');

        // ragazzi unici
        const ragazziPhotos = Array.from(
            new Map(att.ragazzi.map(r=>[r.id,r])).values()
               ).map(r=>`<div class="ragazzo-item">
            <img src="${r.fotografia}" alt="${r.nome} ${r.cognome}" class="ragazzo-avatar">
            <span class="ragazzo-cognome">${r.cognome}</span>
</div>`).join('') || '—';

        html += `
        <div class="activity-card" data-id="${att.id}">
            <div class="activity-header">
                <h3>${att.attivita_nome}</h3>
                <span class="activity-time"><img class="resoconti-icon" src="immagini/rescheduling.png" style="width:22px; height:22px; margin-right:8px;"> ${inizio_no_seconds} - ${fine_no_seconds}</span>
            </div>
            <div class="activity-description">${att.descrizione}</div>
            <div class="activity-participants">
                <div class="participant-group">
                    <label>Educatori:</label>
                    <span>${educatoriText}</span>
                </div>
                <div class="participant-group">
                    <label>Ragazzi:</label>
                    <span class="ragazzi-photos">${ragazziPhotos}</span>
                </div>
            </div>
            <div class="activity-actions">
                <button class="delete-agenda-btn" data-id="${att.id}" title="Elimina">
                    <img src="immagini/delete.png" alt="Elimina">
                </button>
            </div>
        </div>
        `;
    });

    html += '</div>';
    contentDiv.innerHTML = html;
}

// event listeners per i tab dei giorni
document.querySelectorAll('.day-tab').forEach((tab,index)=>{
    tab.addEventListener('click',()=>{
        document.querySelectorAll('.day-tab').forEach(t=>t.classList.remove('active'));
        tab.classList.add('active');
        displayAgenda(index);

        // Scroll del tab in vista su mobile
        if(window.innerWidth <= 768) {
            tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    });
});



// carica agenda al load
window.addEventListener('DOMContentLoaded',()=>{ loadAgenda(); });

// ========== MODAL MODIFICA AGENDA ==========

const modalDeleteAgenda = document.getElementById("modalDeleteAgenda");
const modalModificaAgenda = document.getElementById("modalModificaAgenda");



// Delete Agenda
let agendaToDelete = null;
document.addEventListener('click', function(e){
    if(e.target.closest('.delete-agenda-btn')){
        const btn = e.target.closest('.delete-agenda-btn');
        agendaToDelete = btn.dataset.id;
        openModal(modalDeleteAgenda);
    }
});

document.getElementById("confirmDeleteAgenda").onclick = () => {
    if(!agendaToDelete) return;

    fetch("api/api_elimina_agenda.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({ id: agendaToDelete })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            modalDeleteAgenda.classList.remove("show");
            if(Overlay) Overlay.classList.remove("show");
            successText.innerText = "Agenda Eliminata!!";
            showSuccess(successPopup, Overlay);
            setTimeout(() => {
                hideSuccess(successPopup, Overlay);
                location.reload();
            }, 1800);
        } else {
            alert("Errore: " + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Errore di comunicazione con il server");
    });
};




        // ========== MODAL CREA AGENDA ==========
        const creaAgendaBtn = document.getElementById("creaAgendaBtn");
        const formCreaAgenda = document.getElementById("formCreaAgenda");
        // agendaOverlay should reuse single global overlay when available
        const agendaOverlay = document.getElementById("agendaOverlay") || Overlay;
        const successPopupAgenda = document.getElementById("successPopupAgenda");

        // Funzione per popolare la select delle date
        function popolaSelectDate() {
            const today = new Date();
            const monday = new Date(today);
            monday.setDate(today.getDate() - today.getDay() + 1);

            const dataSelect = document.getElementById("agendaData");
            const giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì'];
            
            // Pulisci le opzioni mantenendo la prima (placeholder)
            while (dataSelect.options.length > 1) {
                dataSelect.remove(1);
            }

            for (let i = 0; i < 5; i++) {
                const date = new Date(monday);
                date.setDate(monday.getDate() + i);
                const dateStr = getLocalDateString(date);
                const dateFormatted = date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });

                const option = document.createElement("option");
                option.value = dateStr;
                option.text = `${giorni[i]} ${dateFormatted}`;
                dataSelect.appendChild(option);
            }

            // Pre-seleziona il giorno corrente se valido
            if (selectedDayIndex >= 0 && selectedDayIndex < 5) {
                dataSelect.selectedIndex = selectedDayIndex + 1; // +1 per saltare il placeholder
            }
        }

        if(creaAgendaBtn) {
            creaAgendaBtn.onclick = () => {
                popolaSelectDate();
                openModal(modalCreaAgenda);
            };
        }

        if(aggiungiAgendaBtnMobile) {
            aggiungiAgendaBtnMobile.onclick = () => {
                popolaSelectDate();
                openModal(modalCreaAgenda);
            };
        }


        if(agendaOverlay) {
            agendaOverlay.onclick = () => {
                closeModal();
            };
        }

        // Submit form
        if(formCreaAgenda) {
            formCreaAgenda.onsubmit = function(e) {
                e.preventDefault();
                console.log("Form submit triggered");

                const data = document.getElementById("agendaData").value;
                const ora_inizio = document.getElementById("agendaOraInizio").value;
                const ora_fine = document.getElementById("agendaOraFine").value;
                const id_attivita = document.getElementById("agendaAttivita").value;
                
                console.log("Data:", data, "Ora inizio:", ora_inizio, "Ora fine:", ora_fine, "Attività:", id_attivita);

                const educatoriCheckboxes = document.querySelectorAll(".educatore-checkbox:checked");
                const ragazziCheckboxes = document.querySelectorAll(".ragazzo-checkbox:checked");
                
                const educatori = Array.from(educatoriCheckboxes)
                    .map(cb => {
                        const val = parseInt(cb.value, 10);
                        console.log("Educatore checkbox value:", cb.value, "parsed:", val);
                        return val;
                    })
                    .filter(id => !isNaN(id) && id > 0);
                    
                const ragazzi = Array.from(ragazziCheckboxes)
                    .map(cb => {
                        const val = parseInt(cb.value, 10);
                        console.log("Ragazzo checkbox value:", cb.value, "parsed:", val);
                        return val;
                    })
                    .filter(id => !isNaN(id) && id > 0);

                console.log("Educatori selezionati:", educatori);
                console.log("Ragazzi selezionati:", ragazzi);

                if (!data || !ora_inizio || !ora_fine || !id_attivita || educatori.length === 0) {
                    alert("Completa i campi obbligatori:\n- Data\n- Orari\n- Attivita\n- Educatori (almeno 1)\n\nEducatori selezionati: " + educatori.length);
                    return;
                }

                if (ragazzi.length === 0) {
                    alert("Seleziona almeno un ragazzo!");
                    return;
                }

                console.log("Invio fetch all'API");
                const payload = {
                    data: data,
                    ora_inizio: ora_inizio,
                    ora_fine: ora_fine,
                    id_attivita: parseInt(id_attivita),
                    educatori: educatori,
                    ragazzi: ragazzi
                };
                console.log("Payload:", payload);

                fetch("api/api_aggiungi_agenda.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => {
                    console.log("Response status:", res.status);
                    return res.json();
                })
                .then(data => {
                    console.log("Risposta API:", data);
                    if(data.success) {
                        console.log("Successo! Chiudo modal e reload");
                        if (modalCreaAgenda) modalCreaAgenda.classList.remove("show");
                        if (agendaOverlay) agendaOverlay.classList.remove("show");
                        if (successPopupAgenda) {
                            showSuccess(successPopupAgenda, agendaOverlay);
                            setTimeout(() => {
                                if (successPopupAgenda) hideSuccess(successPopupAgenda, agendaOverlay);
                                location.reload();
                            }, 2500);
                        } else {
                            location.reload();
                        }
                    } else {
                        alert("Errore API: " + (data.error || data.message));
                    }
                })
                .catch(err => {
                    console.error("Fetch error completo:", err);
                    console.error("Stack:", err.stack);
                    alert("Errore di comunicazione con il server: " + err.message);
                });
            };
        }
        // Carica l'agenda al caricamento della pagina
        window.addEventListener('DOMContentLoaded', () => {
            loadAgenda();
        });



// ========== STAMPA AGENDA ==========
        const stampaAgendaBtn = document.getElementById("stampaAgendaBtn");
        if(stampaAgendaBtn) {
            stampaAgendaBtn.onclick = () => {
                const printWindow = window.open('', '_blank', 'width=800,height=600');

                const timeSlots = [
                    { start: '08:00', end: '10:00', label: '08:00 - 10:00', bg: '#e6f7ff' },
                    { start: '10:00', end: '12:00', label: '10:00 - 12:00', bg: '#fff7e6' },
                    { start: '12:00', end: '14:00', label: '12:00 - 14:00', bg: '#f6ffed' },
                    { start: '14:00', end: '16:00', label: '14:00 - 16:00', bg: '#fff2f0' },
                    { start: '16:00', end: '18:00', label: '16:00 - 18:00', bg: '#f9f0ff' }
                ];

                const groupedActivities = {};
                const giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì'];
                const dayIndices = [0, 1, 2, 3, 4];

                timeSlots.forEach(slot => {
                    groupedActivities[slot.label] = {};
                    dayIndices.forEach((dayIndex, idx) => {
                        const dayName = giorni[idx];
                        groupedActivities[slot.label][dayName] = [];

                        const dayActivities = agendaData.filter(att => {
                            let monday;
                            if(agendaWeekStart){
                                const parts = agendaWeekStart.split('-');
                                monday = new Date(parts[0], parts[1]-1, parts[2]);
                            } else {
                                monday = new Date();
                                monday.setDate(monday.getDate() - monday.getDay() + 1);
                            }
                            const selectedDate = new Date(monday);
                            selectedDate.setDate(monday.getDate() + dayIndex);
                            const selectedDateStr = getLocalDateString(selectedDate);
                            return att.data === selectedDateStr && att.ora_inizio >= slot.start && att.ora_inizio < slot.end;
                        });

                        dayActivities.sort((a,b)=> a.ora_inizio.localeCompare(b.ora_inizio)).forEach(att => {
                            const educatori = Array.from(new Map(att.educatori.map(e=>[e.id,e])).values()).map(e=>`${e.nome} ${e.cognome}`).join(', ');
                            const ragazziSurnames = Array.from(new Map(att.ragazzi.map(r=>[r.id,r])).values()).map(r=>r.cognome).join(', ');

                            groupedActivities[slot.label][dayName].push(`
                                <div class="activity">
                                    <strong>${att.attivita_nome} (${educatori})</strong><br>
                                    <span class="ragazzi-surnames">${ragazziSurnames}</span>
                                </div>
                            `);
                        });
                    });
                });

                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Stampa Agenda Settimanale</title>
                        <style>
                            @page { size: A4 landscape; }
                            body { font-family: Arial, sans-serif; margin: 3px; width: 297mm; }
                            table { width: 100%; border-collapse: collapse; font-size: 14px; table-layout: fixed; }
                            th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; width: 20%; max-width: 20%; word-wrap: break-word; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                            th { background: #f0f0f0; font-weight: bold; }
                            .activity { margin-bottom: 6px; page-break-inside: avoid; }
                            .participants { font-size: 12px; }
                            .ragazzi-photos { display: flex; flex-wrap: wrap; gap: 2px; align-items: center; }
                            .ragazzo-photo { width: 20px; height: 20px; object-fit: cover; border-radius: 50%; border: 1px solid #ccc; }
                            @media print { body { margin: 0; } table { width: 100%; } }
                        </style>
                    </head>
                    <body>
                        <h2 style="text-align: center;">Agenda Settimanale - ${new Date().toLocaleDateString('it-IT')}</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Lunedì</th>
                                    <th>Martedì</th>
                                    <th>Mercoledì</th>
                                    <th>Giovedì</th>
                                    <th>Venerdì</th>
                                </tr>
                            </thead>
                            <tbody>
                `);

                timeSlots.forEach(slot => {
                    printWindow.document.write(`<tr>`);
                    dayIndices.forEach((dayIndex, idx) => {
                        const dayName = giorni[idx];
                        const activities = groupedActivities[slot.label][dayName];
                        printWindow.document.write(`<td style="background-color: ${slot.bg};">`);
                        if(activities.length === 0) {
                            printWindow.document.write(`Nessuna attività`);
                        } else {
                            activities.forEach(act => printWindow.document.write(act));
                        }
                        printWindow.document.write(`</td>`);
                    });
                    printWindow.document.write(`</tr>`);
                });

                printWindow.document.write(`
                            </tbody>
                        </table>
                    </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            };
        }





   document.addEventListener("DOMContentLoaded", () => {
    const resocontiMeseFiltro = document.getElementById("resocontiMeseFiltro");
    const resocontiMensiliBody = document.getElementById("resocontiMensiliBody");
    const modalResoconto = document.getElementById("modalResocontoGiorni");
    const bodyResoconto = document.getElementById("resocontoGiorniBody");
    const titoloResoconto = document.getElementById("resocontoNome");

    let currentIscritto = null;
    let mobileCalendarInstance = null;


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

        if(bodyResoconto) bodyResoconto.innerHTML = `<tr><td colspan="4">Caricamento...</td></tr>`;


        if(modalResoconto && typeof openModal === "function") openModal(modalResoconto);

        caricaResocontoGiorni();
    });

    // FUNZIONE CARICA RESOCONTI MENSILI

    function caricaResocontiMensili(mese){
        if(!resocontiMensiliBody) return;
        resocontiMensiliBody.innerHTML = `<tr><td colspan="6">Caricamento...</td></tr>`;

        fetch("api/api_resoconto_mensile.php", {
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
                const costo = parseFloat(r.ore_totali * r.Prezzo_Orario).toFixed(2);

                resocontiMensiliBody.innerHTML += `
                    <tr>
                        <td><img src="${r.Fotografia}" class="user-avatar"></td>
                        <td>${r.Nome}</td>
                        <td>${r.Cognome}</td>
                        <td>${ore}</td>
                        <td>${costo} €</td>
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

    // Variabile per tracciare il mese corrente nel modal
    let currentModalMese = null;

    // FUNZIONE CARICA RESOCONTO GIORNI CON ATTIVITÀ E CALENDARIO MOBILE

    function caricaResocontoGiorni(meseForzato = null){
        if(!currentIscritto) return;
        
        // Usa il mese forzato (dal calendario) o quello del filtro globale
        const meseDaUsare = meseForzato || resocontiMeseFiltro.value;
        currentModalMese = meseDaUsare;

        fetch("api/api_resoconto_giornaliero.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                id: currentIscritto,
                mese: meseDaUsare
            })
        })
        .then(r => r.json())
        .then(json => {
            if(!bodyResoconto || !document.getElementById("mobileCalendarContainer") || !document.getElementById("attivitaMensiliBody")) return;
            bodyResoconto.innerHTML = "";
            const attivitaMensiliBody = document.getElementById("attivitaMensiliBody");
            attivitaMensiliBody.innerHTML = "";

            // Reset totali
            let totalOre = 0;
            let totalCosto = 0;
            let giorniPresenza = 0;

            if(!json.success || json.data.length === 0){
                bodyResoconto.innerHTML = `<tr><td colspan="4">Nessun dato</td></tr>`;
                attivitaMensiliBody.innerHTML = `<tr><td colspan="2">Nessuna attività</td></tr>`;
                
                // Aggiorna sommario a zero
                const summaryOre = document.getElementById('summaryOre');
                const summaryCosto = document.getElementById('summaryCosto');
                const summaryGiorni = document.getElementById('summaryGiorni');
                
                if(summaryOre) summaryOre.textContent = '0.00';
                if(summaryCosto) summaryCosto.textContent = '0.00 €';
                if(summaryGiorni) summaryGiorni.textContent = '0';
                
                // Aggiorna calendario esistente con dati vuoti
                if(mobileCalendarInstance) {
                    mobileCalendarInstance.setActivitiesData({});
                } else {
                    // Inizializza calendario vuoto solo se non esiste
                    const calendarContainer = document.getElementById("mobileCalendarContainer");
                    if(calendarContainer && window.MobileCalendar) {
                        const [anno, mese] = meseDaUsare.split('-');
                        mobileCalendarInstance = new MobileCalendar('mobileCalendarContainer', {
                            selectedDate: new Date(parseInt(anno), parseInt(mese) - 1, 1),
                            activitiesData: {},
                            activitiesPanel: '#mc-activities-panel',
                            onDayClick: function(dateStr, activities) {
                                console.log('Giorno selezionato:', dateStr, activities);
                            },
                            onMonthChange: function(nuovaData) {
                                const nuovoAnno = nuovaData.getFullYear();
                                const nuovoMese = nuovaData.getMonth() + 1;
                                const nuovoMeseStr = `${nuovoAnno}-${String(nuovoMese).padStart(2, '0')}`;
                                caricaResocontoGiorni(nuovoMeseStr);
                            }
                        });
                    }
                }
                return;
            }

            const daysMap = new Map();
            const attivitaMap = new Map(); 
            
            // Prepara dati per il calendario mobile
            const activitiesData = {};

            json.data.forEach(r => {
                const giorno = new Date(r.giorno).getDate();
                const dateStr = r.giorno; // YYYY-MM-DD
                
                if(!daysMap.has(giorno)) {
                    daysMap.set(giorno, {attivita: [], ore:0, costo:0});
                    giorniPresenza++; // Conta i giorni di presenza
                }
                const day = daysMap.get(giorno);

                r.attivita.forEach(a => {
                    day.attivita.push(a);
                    if(!attivitaMap.has(a.Nome)) attivitaMap.set(a.Nome, 0);
                    attivitaMap.set(a.Nome, attivitaMap.get(a.Nome) + a.ore);
                });
                day.ore += r.ore;
                day.costo += r.costo;

                totalOre += r.ore;
                totalCosto += r.costo;

                let attHTML = r.attivita.map(a => `${a.Nome} (${a.ore.toFixed(2)}h, ${a.costo.toFixed(2)}€)`).join('<br>');
                bodyResoconto.innerHTML += `
                    <tr>
                        <td>${giorno}</td>
                        <td>${attHTML}</td>
                        <td>${r.ore.toFixed(2)}</td>
                        <td>${r.costo.toFixed(2)} €</td>
                    </tr>
                `;
                
                // Prepara dati attività per calendario
                if (!activitiesData[dateStr]) {
                    activitiesData[dateStr] = [];
                }
                
                // Se ci sono attività specifiche, aggiungile
                if (r.attivita && r.attivita.length > 0) {
                    r.attivita.forEach(a => {
                        activitiesData[dateStr].push({
                            nome: a.Nome,
                            descrizione: `${a.ore.toFixed(2)} ore - ${a.costo.toFixed(2)}€`,
                            ora_inizio: '',
                            ora_fine: '',
                            educatori: ''
                        });
                    });
                } else {
                    // Se non ci sono attività ma l'iscritto era presente, aggiungi un marker di presenza
                    activitiesData[dateStr].push({
                        nome: 'Presente',
                        descrizione: `${r.ore.toFixed(2)} ore - ${r.costo.toFixed(2)}€`,
                        ora_inizio: '',
                        ora_fine: '',
                        educatori: ''
                    });
                }

            });

            // Popola tabella attività mensili - ORDINATE PER ORE DECRESCENTE
            const attivitaArray = Array.from(attivitaMap.entries());
            attivitaArray.sort((a, b) => b[1] - a[1]);

            attivitaArray.forEach(([nome, ore]) => {
                attivitaMensiliBody.innerHTML += `
                    <tr>
                        <td>${nome}</td>
                        <td>${ore.toFixed(2)} ore</td>
                    </tr>
                `;
            });

            // Aggiorna i riepiloghi totali
            const summaryOre = document.getElementById('summaryOre');
            const summaryCosto = document.getElementById('summaryCosto');
            const summaryGiorni = document.getElementById('summaryGiorni');
            
            if(summaryOre) summaryOre.textContent = totalOre.toFixed(2);
            if(summaryCosto) summaryCosto.textContent = totalCosto.toFixed(2) + ' €';
            if(summaryGiorni) summaryGiorni.textContent = giorniPresenza;

            // Aggiorna o crea il calendario mobile
            const calendarContainer = document.getElementById("mobileCalendarContainer");
            if(calendarContainer && window.MobileCalendar) {
                const [anno, mese] = meseDaUsare.split('-');
                
                if(mobileCalendarInstance) {
                    // Aggiorna calendario esistente
                    mobileCalendarInstance.setActivitiesData(activitiesData);
                } else {
                    // Crea nuovo calendario
                    mobileCalendarInstance = new MobileCalendar('mobileCalendarContainer', {
                        selectedDate: new Date(parseInt(anno), parseInt(mese) - 1, 1),
                        activitiesData: activitiesData,
                        activitiesPanel: '#mc-activities-panel',
                        onDayClick: function(dateStr, activities) {
                            console.log('Giorno selezionato:', dateStr, activities);
                        },
                        onMonthChange: function(nuovaData) {
                            const nuovoAnno = nuovaData.getFullYear();
                            const nuovoMese = nuovaData.getMonth() + 1;
                            const nuovoMeseStr = `${nuovoAnno}-${String(nuovoMese).padStart(2, '0')}`;
                            caricaResocontoGiorni(nuovoMeseStr);
                        }
                    });
                }
            }
        })
        .catch(err => {
            console.error(err);
            if(bodyResoconto) bodyResoconto.innerHTML = `<tr><td colspan="4">Errore nel caricamento</td></tr>`;
            const calendarContainer = document.getElementById("mobileCalendarContainer");
            if(calendarContainer) calendarContainer.innerHTML = `<p style="text-align:center;margin-top:12px;">❌ Errore nel caricamento</p>`;
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



        // Salva stato sidebar
        const checkboxInput = document.getElementById('checkbox-input');
        if (checkboxInput) {
            // Ripristina stato al caricamento
            const sidebarState = localStorage.getItem('sidebarOpen');
            if (sidebarState !== null) {
                checkboxInput.checked = sidebarState === 'true';
            }

            // Salva stato al cambio
            checkboxInput.addEventListener('change', () => {
                localStorage.setItem('sidebarOpen', checkboxInput.checked);
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




    </script>

<script src="js/mobile-calendar.js"></script>

</script>

</body>

</html>
