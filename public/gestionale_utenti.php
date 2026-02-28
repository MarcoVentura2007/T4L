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
$sql = "SELECT id, nome, cognome, fotografia, data_nascita, disabilita, prezzo_orario, codice_fiscale, email, telefono, allergie_intolleranze, note, Gruppo 
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

//se la classe non è Educatore, redirect a index.php
if($classe !== 'Educatore'){
    header("Location: index.php");
    exit;
}

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
                        
                        
                        


                        


                    </ul>

                </section>
            </nav>
        </aside>

        



        <main class="main-content">
            <div class="main-container">
            <!-- TAB UTENTI -->
            <div class="page-tab active" id="tab-utenti">
                
                
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
                        <div class="edit-field">
                            <label>Tipo di lavoro</label>
                            <select id="utenteGruppo">
                                <option value="0">Individuale</option>
                                <option value="1">Gruppo</option>
                            </select>
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
                                        data-gruppo="'.htmlspecialchars($row['Gruppo']).'"
                                    >

                                        <td><img class="user-avatar" src="'.$row['fotografia'].'"></td>
                                        <td>'.htmlspecialchars($row['nome']).'</td>
                                        <td>'.htmlspecialchars($row['cognome']).'</td>
                                        <td>'.htmlspecialchars($row['data_nascita']).'</td>
                                        <td>'.htmlspecialchars($row['disabilita']).'</td>
                                        <td>'.htmlspecialchars($row['note']).'</td>
                                        <td>
                                            <button class="view-btn"><img src="immagini/open-eye.png"></button>
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
                                        echo '<select class="ragazzo-gruppo" style="margin-left:8px;">
                                                <option value="0" selected>Ind</option>
                                                <option value="1">Gruppo</option>
                                            </select>';
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

                    <div style="display: flex; justify-content: flex-start; align-items: center;">
                        <h3 class="modal-title" id="resocontoNome"></h3>
                    </div>

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
                        <button class="print-btn" id="stampaResocontoBtn" style="margin-right: auto;">
                            <span class="printer-wrapper">
                                <span class="printer-container">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 92 75">
                                        <path stroke-width="5" stroke="black" d="M12 37.5H80C85.2467 37.5 89.5 41.7533 89.5 47V69C89.5 70.933 87.933 72.5 86 72.5H6C4.067 72.5 2.5 70.933 2.5 69V47C2.5 41.7533 6.75329 37.5 12 37.5Z"></path>
                                        <mask fill="white" id="path-2-inside-1_30_7">
                                            <path d="M12 12C12 5.37258 17.3726 0 24 0H57C70.2548 0 81 10.7452 81 24V29H12V12Z"></path>
                                        </mask>
                                        <path mask="url(#path-2-inside-1_30_7)" fill="black" d="M7 12C7 2.61116 14.6112 -5 24 -5H57C73.0163 -5 86 7.98374 86 24H76C76 13.5066 67.4934 5 57 5H24C20.134 5 17 8.13401 17 12H7ZM81 29H12H81ZM7 29V12C7 2.61116 14.6112 -5 24 -5V5C20.134 5 17 8.13401 17 12V29H7ZM57 -5C73.0163 -5 86 7.98374 86 24V29H76V24C76 13.5066 67.4934 5 57 5V-5Z"></path>
                                        <circle fill="black" r="3" cy="49" cx="78"></circle>
                                    </svg>
                                </span>
                                <span class="printer-page-wrapper">
                                    <span class="printer-page"></span>
                                </span>
                            </span>
                            Stampa
                        </button>
                        <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
                    </div>
                </div>

                <!-- OVERLAY PER MODAL PREVIEW -->
                <div id="overlayAnteprimaResoconto" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.3); z-index: 9999;"></div>

                <!-- MODAL PREVIEW STAMPA RESOCONTO -->
                <div class="modal-box large" id="modalAnteprimaResoconto" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; max-width: 1000px; width: 90%; max-height: 90vh; overflow-y: auto; pointer-events: auto; background: #f9fafb; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 20px; background: linear-gradient(135deg, #f4f6f9 0%, #ffffff 100%); border-bottom: 2px solid #e5e7eb; border-radius: 8px 8px 0 0;">
                        <h3 class="modal-title" style="margin: 0; color: #111827;">Anteprima Resoconto</h3>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; color: #4b5563;">
                                Salva come:
                                <select id="formatoDownload" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: white; cursor: pointer; color: #111827; font-weight: 500;">
                                    <option value="pdf">PDF</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <!-- AREA PREVIEW -->
                    <div id="anteprimaContenuto" style="border: 1px solid #e5e7eb; padding: 30px; background: white; max-height: 600px; overflow-y: auto; margin: 20px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word; box-shadow: 0 2px 8px rgba(0,0,0,0.05);"></div>

                    <!-- PULSANTI AZIONI -->
                    <div class="modal-actions" style="gap: 15px; padding: 20px; margin-top: 0; background: #f9fafb; border-top: 1px solid #e5e7eb;">
                        <button class="print-btn" id="scaricaResocontoBtn" style="flex: 1; padding: 12px 20px; background: #ffffff; color: #111827; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s ease;" onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db'" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#e5e7eb'">
                            <span class="printer-wrapper download-icon-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"></path>
                                </svg>
                            </span>
                            Scarica
                        </button>

                        <button class="btn-secondary" id="chiudiAnteprimaBtn" style="padding: 12px 20px; background: #e5e7eb; color: #111827; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Chiudi</button>
                    </div>

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
                            <div class="edit-field" id="fieldGruppo">
                                <label>Tipo di lavoro</label>
                                <select id="editGruppo">
                                    <option value="0">Individuale</option>
                                    <option value="1">Gruppo</option>
                                </select>
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

                            <!-- SEZIONE ALLEGATI EDIT -->
                            <div class="edit-field" id="fieldAllegatiEdit">
                                <label>Allegati</label>
                                
                                <!-- Allegati esistenti -->
                                <div id="allegatiEsistentiContainer" style="margin-bottom: 15px;">                                    <div id="allegatiEsistentiList" style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <!-- Popolato da JS -->
                                    </div>
                                </div>

                                <!-- Upload nuovi allegati -->
                                <div class="allegati-upload-container" id="allegatiEditContainer">
                                    <input type="file" id="editAllegati" multiple 
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.xls,.xlsx" hidden>
                                    
                                    <div class="allegati-drop-zone" id="allegatiEditDropZone" style="padding: 15px;">
                                        <div class="allegati-icon"><img src="immagini/paperclip.png" alt="Graffetta" style="width: 24px; height: 24px;"></div>
                                        <p class="allegati-text" style="font-size: 13px;">Trascina i file qui o clicca per selezionare</p>
                                        <p class="allegati-hint" style="font-size: 11px;">PDF, DOC, DOCX, JPG, PNG, GIF, TXT, XLS, XLSX (max 10MB)</p>
                                        <button type="button" class="file-btn-minimal" 
                                            onclick="document.getElementById('editAllegati').click()">
                                            Seleziona file
                                        </button>
                                    </div>

                                    <div class="allegati-list" id="allegatiEditList" style="display:none;">
                                        <div class="allegati-list-header">
                                            <span>Nuovi file selezionati</span>
                                            <button type="button" class="allegati-clear-all" id="clearAllEditAllegati">Rimuovi tutti</button>
                                        </div>
                                        <ul id="allegatiEditItems"></ul>
                                    </div>
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
        if (overlay) {
            const anyVisible = document.querySelector(
                ".modal-box.show, .popup.show, .logout-modal.show, .success-popup.show, .popup-overlay.show, .logout-overlay.show"
            );
            if (!anyVisible) {
                overlay.classList.remove("show");
            }
        }
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
            const gruppo = row.dataset.gruppo;

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
                <div class="profile-field"><label>Tipo di lavoro</label><span>${gruppo === 'on' || gruppo === '1' ? 'Gruppo' : 'Individuale'}</span></div>
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
            
            let html = '<div class="allegati-grid">';
            
            data.allegati.forEach(allegato => {
                const icon = getAllegatoIcon(allegato.tipo);
                const dataFormattata = new Date(allegato.data_upload).toLocaleDateString('it-IT');
                
                html += `
                    <div class="allegato-card" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; display: flex; align-items: center; gap: 10px; background: #f9f9f9;">
                        <div class="allegato-icon-big" style="font-size: 26px;">${icon}</div>
                        <div class="allegato-info" style="flex: 1; min-width: 0;">
                            <div class="allegato-name" style="font-weight: 500; color: #2b2b2b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${allegato.nome_file}">${allegato.nome_file}</div>
                            <div class="allegato-date" style="font-size: 12px; color: #888;">${dataFormattata}</div>
                        </div>
                        <a href="${allegato.percorso}" target="_blank" class="allegato-download" style="color: #640a35; text-decoration: none; font-size: 20px;" title="Scarica">
                            <div class="group relative">
                                <button
                                    class="bg-white w-6 h-6 flex justify-center items-center rounded 
                                        text-black border border-black
                                        hover:bg-black hover:text-white hover:translate-y-0.5
                                        transition-all duration-200"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"
                                            stroke-linejoin="round"
                                            stroke-linecap="round"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                        </a>
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

            // mostra solo intestazione con foto/nome e i campi orari
            const header = document.getElementById('profileHeader');
            header.style.display = 'flex';
            document.getElementById('viewAvatar-mod').src = avatar;
            document.getElementById('viewFullname-mod').innerText = nome + ' ' + cognome;
            document.getElementById('viewBirth-mod').innerText = '';
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
            // hide photo/attachments and job-type when only editing presence
            document.getElementById('fieldFotografia').style.display = 'none';
            document.getElementById('fieldAllegatiEdit').style.display = 'none';
            document.getElementById('fieldGruppo').style.display = 'none';
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

        // ===== GESTIONE ALLEGATI EDIT =====
        const editAllegatiInput = document.getElementById("editAllegati");
        const allegatiEditDropZone = document.getElementById("allegatiEditDropZone");
        const allegatiEditList = document.getElementById("allegatiEditList");
        const allegatiEditItems = document.getElementById("allegatiEditItems");
        const clearAllEditAllegati = document.getElementById("clearAllEditAllegati");
        const allegatiEsistentiList = document.getElementById("allegatiEsistentiList");
        
        let selectedAllegatiEdit = [];
        let currentEditUserId = null;

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
                'pdf': '<img src="immagini/pdf.png" alt="PDF" style="width: 20px; height: 20px;">',
                'doc': '<img src="immagini/docx.png" alt="DOC" style="width: 20px; height: 20px;">',
                'docx': '<img src="immagini/docx.png" alt="DOCX" style="width: 20px; height: 20px;">',
                'jpg': '<img src="immagini/img.png" alt="JPG" style="width: 20px; height: 20px;">',
                'jpeg': '<img src="immagini/img.png" alt="JPEG" style="width: 20px; height: 20px;">',
                'png': '<img src="immagini/img.png" alt="PNG" style="width: 20px; height: 20px;">',
                'gif': '<img src="immagini/img.png" alt="GIF" style="width: 20px; height: 20px;">',
                'txt': '<img src="immagini/txt.png" alt="TXT" style="width: 20px; height: 20px;">',
                'xls': '<img src="immagini/xls.png" alt="XLS" style="width: 20px; height: 20px;">',
                'xlsx': '<img src="immagini/xls.png" alt="XLSX" style="width: 20px; height: 20px;">'
            };
            return icons[ext] || '<img src="immagini/paperclip.png" alt="File" style="width: 20px; height: 20px;">';
        }

        // Variabili per il modal di eliminazione allegato
        let allegatoToDelete = null;
        let iscrittoAllegatoToDelete = null;
        let allegatoNomeToDelete = null;

        // Funzione per creare l'overlay dedicato all'eliminazione allegato
        function getAllegatoOverlay() {
            let overlay = document.getElementById('allegatoOverlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'allegatoOverlay';
                overlay.className = 'modal-overlay';
                overlay.style.zIndex = '10001'; // Sopra tutti gli altri elementi (sopra modal edit z-index 10000)

                document.body.appendChild(overlay);
                
                // Chiudi il modal quando si clicca sull'overlay
                overlay.addEventListener('click', closeDeleteAllegatoModal);
            }
            return overlay;
        }

        // Funzione per mostrare il modal di conferma eliminazione allegato
        function showDeleteAllegatoModal(idAllegato, idIscritto, nomeFile) {
            allegatoToDelete = idAllegato;
            iscrittoAllegatoToDelete = idIscritto;
            allegatoNomeToDelete = nomeFile;
            
            // Crea il modal se non esiste
            let modal = document.getElementById('modalDeleteAllegato');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalDeleteAllegato';
                modal.className = 'modal-box danger';
                modal.style.zIndex = '10002'; // Sopra l'overlay dedicato

                document.body.appendChild(modal);
            }
            
            // Ottieni l'icona in base all'estensione del file
            const fileIcon = getFileIcon(nomeFile);
            
            // Aggiorna il contenuto del modal con il nome del file
            modal.innerHTML = `
                <h3>Elimina allegato</h3>
                <div style="display: flex; align-items: center; gap: 12px; margin: 15px 0; padding: 12px; background: #f5f5f5; border-radius: 8px; border: 1px solid #e0e0e0;">
                    <div style="font-size: 24px;">${fileIcon}</div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 500; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${nomeFile}">${nomeFile}</div>
                        <div style="font-size: 12px; color: #888; margin-top: 2px;">Verrà eliminato definitivamente</div>
                    </div>
                </div>
                <p style="color: #d32f2f; font-size: 14px; margin-bottom: 15px;">
                    <strong>Attenzione:</strong> Questa azione non può essere annullata.
                </p>
                <div class="modal-actions">
                    <button class="btn-secondary" onclick="closeDeleteAllegatoModal()">Annulla</button>
                    <button class="btn-danger" id="confirmDeleteAllegatoBtn">Elimina</button>
                </div>
            `;
            
            // Aggiungi event listener al bottone di conferma
            document.getElementById('confirmDeleteAllegatoBtn').addEventListener('click', confirmDeleteAllegato);
            
            const allegatoOverlay = getAllegatoOverlay();
            allegatoOverlay.classList.add('show');
            modal.classList.add('show');
            
            // Aggiungi listener per chiusura con tasto ESC
            document.addEventListener('keydown', handleEscKey);
        }

        // Gestione tasto ESC per chiudere il modal
        function handleEscKey(e) {
            if (e.key === 'Escape') {
                closeDeleteAllegatoModal();
            }
        }

        // Funzione per chiudere il modal
        function closeDeleteAllegatoModal() {
            const modal = document.getElementById('modalDeleteAllegato');
            const allegatoOverlay = document.getElementById('allegatoOverlay');
            if (modal) modal.classList.remove('show');
            if (allegatoOverlay) allegatoOverlay.classList.remove('show');
            allegatoToDelete = null;
            iscrittoAllegatoToDelete = null;
            allegatoNomeToDelete = null;
            
            // Rimuovi listener per tasto ESC
            document.removeEventListener('keydown', handleEscKey);
        }

        // Funzione per confermare l'eliminazione
        async function confirmDeleteAllegato() {
            if (!allegatoToDelete || !iscrittoAllegatoToDelete) return;
            
            // Disabilita il bottone durante l'eliminazione
            const btn = document.getElementById('confirmDeleteAllegatoBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'Eliminazione...';
            }
            
            try {
                const response = await fetch('api/api_elimina_allegato.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                body: JSON.stringify({ id: allegatoToDelete })

                });
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    throw new Error('Risposta non valida dal server: ' + text);
                }
                
                if (response.ok && data.success) {
                    // conserva l'id dell'iscritto prima di resettare le variabili
                    const reloadId = iscrittoAllegatoToDelete;
                    closeDeleteAllegatoModal();
                    // Ricarica la lista degli allegati usando l'id memorizzato
                    await caricaAllegatiEsistenti(reloadId);
                    // popup successo
                    successText.innerText = "Allegato eliminato!!";
                    showSuccess(successPopup, Overlay);
                    setTimeout(()=>{ hideSuccess(successPopup, Overlay); }, 1600);
                } else {
                    const msg = data.message || text || 'Impossibile eliminare l\'allegato';
                    alert('Errore: ' + msg);
                    if (btn) {
                        btn.disabled = false;
                        btn.innerText = 'Elimina';
                    }
                }
            } catch (error) {
                console.error("Errore eliminazione allegato:", error);
                alert('Errore durante l\'eliminazione: ' + error.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'Elimina';
                }
            }
        }

        // Funzione per eliminare un allegato (mostra il modal)
        function eliminaAllegato(idAllegato, idIscritto, nomeFile) {
            showDeleteAllegatoModal(idAllegato, idIscritto, nomeFile);
        }



        // Carica allegati esistenti per l'utente in modifica
        async function caricaAllegatiEsistenti(idIscritto) {
            allegatiEsistentiList.innerHTML = '<p style="color: #888; font-style: italic; width: 100%;">Caricamento...</p>';
            
            try {
                const response = await fetch(`api/api_get_allegati.php?id_iscritto=${idIscritto}`);
                const data = await response.json();
                
                if (!data.success || !data.allegati || data.allegati.length === 0) {
                    allegatiEsistentiList.innerHTML = '<p style="color: #888; font-style: italic; width: 100%;">Nessun allegato presente</p>';
                    return;
                }
                
                let html = '';
                data.allegati.forEach(allegato => {
                    const icon = getAllegatoIcon(allegato.tipo);
                    // Escapa il nome del file per l'uso in onclick
                    const nomeFileEscaped = allegato.nome_file.replace(/'/g, "\\'").replace(/"/g, '"');
                    html += `
                        <div class="allegato-esistente" style="border: 1px solid #e0e0e0; border-radius: 6px; padding: 8px 12px; display: flex; align-items: center; gap: 8px; background: #f9f9f9; font-size: 13px;">
                            ${icon}
                            <span style="flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" title="${allegato.nome_file}">${allegato.nome_file}</span>
                            <button type="button" onclick="eliminaAllegato(${allegato.id}, ${idIscritto}, '${nomeFileEscaped}')" title="Elimina allegato" style="color: #d32f2f; background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='#ffebee'" onmouseout="this.style.background='none'">

                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </div>
                    `;
                });

                
                allegatiEsistentiList.innerHTML = html;
                
            } catch (error) {
                console.error("Errore caricamento allegati esistenti:", error);
                allegatiEsistentiList.innerHTML = '<p style="color: #d32f2f; font-style: italic; width: 100%;">Errore nel caricamento</p>';
            }
        }


        // Aggiorna lista allegati edit
        function updateAllegatiEditList() {
            if (selectedAllegatiEdit.length === 0) {
                allegatiEditList.style.display = 'none';
                return;
            }

            allegatiEditList.style.display = 'block';
            allegatiEditItems.innerHTML = '';

            selectedAllegatiEdit.forEach((file, index) => {
                const li = document.createElement('li');
                li.className = 'allegato-item';
                li.innerHTML = `
                    <div class="allegato-info">
                        <span class="allegato-icon">${getFileIcon(file.name)}</span>
                        <div class="allegato-details">
                            <span class="allegato-name" title="${file.name}">${file.name}</span>
                            <span class="allegato-size">${formatFileSize(file.size)}</span>
                        </div>
                    </div>
                    <button type="button" class="allegato-remove" data-index="${index}" title="Rimuovi">×</button>
                `;
                allegatiEditItems.appendChild(li);
            });

            // Event listener per rimuovere
            document.querySelectorAll('#allegatiEditItems .allegato-remove').forEach(btn => {
                btn.onclick = function() {
                    const idx = parseInt(this.dataset.index);
                    selectedAllegatiEdit.splice(idx, 1);
                    updateAllegatiEditList();
                };
            });
        }

        // Gestione selezione file edit
        if (editAllegatiInput) {
            editAllegatiInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const maxSize = 10 * 1024 * 1024;
                    const validFiles = Array.from(this.files).filter(file => {
                        if (file.size > maxSize) {
                            alert(`File "${file.name}" troppo grande (max 10MB)`);
                            return false;
                        }
                        return true;
                    });
                    
                    selectedAllegatiEdit = [...selectedAllegatiEdit, ...validFiles];
                    updateAllegatiEditList();
                }
            });
        }

        // Drag and drop edit
        if (allegatiEditDropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                allegatiEditDropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                allegatiEditDropZone.addEventListener(eventName, () => {
                    allegatiEditDropZone.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                allegatiEditDropZone.addEventListener(eventName, () => {
                    allegatiEditDropZone.classList.remove('dragover');
                }, false);
            });

            allegatiEditDropZone.addEventListener('drop', function(e) {
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
                
                selectedAllegatiEdit = [...selectedAllegatiEdit, ...validFiles];
                updateAllegatiEditList();
            });

            allegatiEditDropZone.addEventListener('click', function(e) {
                // Non aprire il file dialog se il click è sul bottone "Seleziona file"
                // (il bottone ha già il suo onclick che apre il dialog)
                if (e.target.closest('.file-btn-minimal')) {
                    return;
                }
                allegatiEditInput.click();
            });

            
        }

        // Rimuovi tutti gli allegati edit
        if (clearAllEditAllegati) {
            clearAllEditAllegati.onclick = function() {
                selectedAllegatiEdit = [];
                updateAllegatiEditList();
            };
        }

        // Upload allegati per utente in modifica
        async function uploadAllegatiEdit(idIscritto) {
            if (selectedAllegatiEdit.length === 0) return { success: true };

            const results = [];
            
            for (let i = 0; i < selectedAllegatiEdit.length; i++) {
                const file = selectedAllegatiEdit[i];
                const formData = new FormData();
                formData.append('id_iscritto', idIscritto);
                formData.append('allegato', file);

                try {
                    const response = await fetch('api/api_carica_allegato.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'include'
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();
                    results.push({ success: data.success, file: file.name, error: data.message });
                } catch (error) {
                    console.error(`Errore upload file ${file.name}:`, error);
                    results.push({ success: false, file: file.name, error: error.message });
                }
            }

            return results;
        }

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
            btn.onclick = async e=>{
                const row = e.target.closest("tr");

                const avatar = row.querySelector("img").src;
                const nome = row.dataset.nome;
                const cognome = row.dataset.cognome;
                const data = row.dataset.nascita;
                const idIscritto = row.dataset.id;

                editModal.dataset.userId = idIscritto;
                editModal.dataset.editType = 'utente';
                currentEditUserId = idIscritto;

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
                document.getElementById('fieldAllegatiEdit').style.display = 'block';
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
                document.getElementById("editGruppo").value = row.dataset.gruppo || 0;

                // Reset file input
                if(editFoto) editFoto.value = "";
                if(editPreview) editPreview.style.display = "none";
                if(editFileNameSpan) editFileNameSpan.innerText = "Nessun file";
                if(editClearBtn) editClearBtn.style.display = "none";

                // Reset allegati edit
                selectedAllegatiEdit = [];
                updateAllegatiEditList();
                
                // Carica allegati esistenti
                await caricaAllegatiEsistenti(idIscritto);

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
                    .then(res => {
                        if (!res.ok) {
                            throw new Error(`HTTP error! status: ${res.status}`);
                        }
                        return res.json();
                    })
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
                        } else {
                            alert("Errore: " + (data.message || "Errore sconosciuto"));
                        }
                    })
                    .catch(err => {
                        console.error("Errore eliminazione utente:", err);
                        alert("Errore durante l'eliminazione: " + err.message);
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

                // Se c'è un file selezionato o allegati, usa FormData
                if(fotoInput && fotoInput.files.length > 0) {
                    console.log("File found, sending FormData with photo");
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
                    // include gruppo flag so it actually gets saved
                    formData.append("gruppo", document.getElementById("editGruppo").value);
                    formData.append("foto", fotoInput.files[0]);

                    fetch('api/api_aggiorna_utente.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(async data => {
                        console.log("API response:", data);
                        if(data.success){
                            // Upload allegati se presenti
                            if(selectedAllegatiEdit.length > 0) {
                                await uploadAllegatiEdit(id);
                            }
                            
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
                    })
                    .catch(err => {
                        console.error("Fetch error:", err);
                    });
                } else {
                    console.log("No file, sending JSON update");
                    // Nessun file foto, ma potrebbero esserci allegati
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
                        note: document.getElementById("editNote").value,
                        // make sure group setting is always included in JSON payload
                        gruppo: document.getElementById("editGruppo").value
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
                    .then(async data => {
                        console.log("API response:", data);
                        if(data.success){
                            // Upload allegati se presenti
                            if(selectedAllegatiEdit.length > 0) {
                                await uploadAllegatiEdit(id);
                            }
                            
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



// ==========================================
// GESTIONE ICONE FILE/ALLEGATI
// ==========================================

/**
 * Ottiene l'icona PNG in base al tipo di file (per allegati salvati)
 */
function getAllegatoIcon(tipo) {
    const icons = {
        'pdf': '<img src="immagini/pdf.png" alt="PDF" style="width: 26px; height: 26px;">',
        'doc': '<img src="immagini/docx.png" alt="DOC" style="width: 26px; height: 26px;">',
        'docx': '<img src="immagini/docx.png" alt="DOCX" style="width: 26px; height: 26px;">',
        'image': '<img src="immagini/img.png" alt="Immagine" style="width: 26px; height: 26px;">',
        'xls': '<img src="immagini/xls.png" alt="XLS" style="width: 26px; height: 26px;">',
        'xlsx': '<img src="immagini/xls.png" alt="XLSX" style="width: 26px; height: 26px;">',
        'txt': '<img src="immagini/txt.png" alt="TXT" style="width: 26px; height: 26px;">',
        'file': '<img src="immagini/paperclip.png" alt="File" style="width: 26px; height: 26px;">'
    };
    return icons[tipo] || icons['file'];
}

/**
 * Ottiene l'icona PNG in base all'estensione del filename (per upload)
 */
function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const icons = {
        'pdf': '<img src="immagini/pdf.png" alt="PDF" style="width: 20px; height: 20px;">',
        'doc': '<img src="immagini/docx.png" alt="DOC" style="width: 20px; height: 20px;">',
        'docx': '<img src="immagini/docx.png" alt="DOCX" style="width: 20px; height: 20px;">',
        'jpg': '<img src="immagini/img.png" alt="JPG" style="width: 20px; height: 20px;">',
        'jpeg': '<img src="immagini/img.png" alt="JPEG" style="width: 20px; height: 20px;">',
        'png': '<img src="immagini/img.png" alt="PNG" style="width: 20px; height: 20px;">',
        'gif': '<img src="immagini/img.png" alt="GIF" style="width: 20px; height: 20px;">',
        'txt': '<img src="immagini/txt.png" alt="TXT" style="width: 20px; height: 20px;">',
        'xls': '<img src="immagini/xls.png" alt="XLS" style="width: 20px; height: 20px;">',
        'xlsx': '<img src="immagini/xls.png" alt="XLSX" style="width: 20px; height: 20px;">'
    };
    return icons[ext] || '<img src="immagini/paperclip.png" alt="File" style="width: 20px; height: 20px;">';
}




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
                'pdf': '<img src="immagini/pdf.png" alt="PDF" style="width: 20px; height: 20px;">',
                'doc': '<img src="immagini/docx.png" alt="DOC" style="width: 20px; height: 20px;">',
                'docx': '<img src="immagini/docx.png" alt="DOCX" style="width: 20px; height: 20px;">',
                'jpg': '<img src="immagini/img.png" alt="JPG" style="width: 20px; height: 20px;">',
                'jpeg': '<img src="immagini/img.png" alt="JPEG" style="width: 20px; height: 20px;">',
                'png': '<img src="immagini/img.png" alt="PNG" style="width: 20px; height: 20px;">',
                'gif': '<img src="immagini/img.png" alt="GIF" style="width: 20px; height: 20px;">',
                'txt': '<img src="immagini/txt.png" alt="TXT" style="width: 20px; height: 20px;">',
                'xls': '<img src="immagini/xls.png" alt="XLS" style="width: 20px; height: 20px;">',
                'xlsx': '<img src="immagini/xls.png" alt="XLSX" style="width: 20px; height: 20px;">'
            };
            return icons[ext] || '<img src="immagini/paperclip.png" alt="File" style="width: 20px; height: 20px;">';
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
                // Non aprire il file dialog se il click è sul bottone "Seleziona file"
                // (il bottone ha già il suo onclick che apre il dialog)
                if (e.target.closest('.file-btn-minimal')) {
                    return;
                }
                allegatiInput.click();
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


        // Submit form
            formAggiungiUtente.onsubmit = async function(e) {
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
        formData.append("gruppo", document.getElementById("utenteGruppo").value);

        const fotoInput = document.getElementById("utenteFoto");
        if(fotoInput.files.length > 0){
            formData.append("foto", fotoInput.files[0]); 
        }

        const res = await fetch("api/api_aggiungi_utente.php", {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        if(data.success){


            await uploadAllegati(data.id);

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

                const ragazzi_gruppo = {};
                Array.from(ragazziCheckboxes).forEach(cb => {
                    const id = parseInt(cb.value, 10);
                    if(isNaN(id) || id <= 0) return;
                    const sel = cb.closest('label').querySelector('.ragazzo-gruppo');
                    if(sel){
                        ragazzi_gruppo[id] = parseInt(sel.value, 10) === 1 ? 1 : 0;
                    } else {
                        ragazzi_gruppo[id] = 0;
                    }
                });

                console.log("Educatori selezionati:", educatori);
                console.log("Ragazzi selezionati:", ragazzi);
                console.log("Gruppo map:", ragazzi_gruppo);

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
                    ragazzi: ragazzi,
                    ragazzi_gruppo: ragazzi_gruppo
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
     @media (max-width: 768px){
        .footer-bar{
            display: none;
        }
    }

    /* Download button bounce animation */
    @keyframes bounce-download {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-4px);
        }
    }

    #scaricaResocontoBtn:hover .download-icon-wrapper svg {
        animation: bounce-download 0.6s ease-in-out infinite;
    }
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
    let resocontoCurrentData = {
        nome: '',
        cognome: '',
        mese: '',
        giorniData: [],
        attivitaMensili: [],
        totalOre: 0,
        totalCosto: 0,
        giorniPresenza: 0
    };


    // CARICAMENTO INIZIALE MENSILE
    if(resocontiMeseFiltro) caricaResocontiMensili(resocontiMeseFiltro.value);

    // CAMBIO MESE GLOBALE
    if(resocontiMeseFiltro) {
        resocontiMeseFiltro.addEventListener("change", () => {
            caricaResocontiMensili(resocontiMeseFiltro.value);
            // se il modal è aperto, aggiorna anche la vista giornaliera
            if(modalResoconto && modalResoconto.classList.contains('open')) {
                caricaResocontoGiorni();
            }
        });
    }

    // CLICK PULSANTE DETTAGLI GIORNALIERI
    document.addEventListener("click", e => {
        const btn = e.target.closest(".resoconto-btn, .calendario-btn");
        if(!btn) return;

        currentIscritto = btn.dataset.id;
        const nome = btn.dataset.nome || "";
        const cognome = btn.dataset.cognome || "";

        // Salva dati per il CSV
        resocontoCurrentData.nome = nome;
        resocontoCurrentData.cognome = cognome;

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
                
                // Aggiorna calendario esistente con dati vuoti (sincronizza mese)
                if(mobileCalendarInstance) {
                    const [anno, mese] = meseDaUsare.split('-');
                    const newDate = new Date(parseInt(anno), parseInt(mese) - 1, 1);
                    const currentYear = mobileCalendarInstance.currentDate.getFullYear();
                    const currentMonth = mobileCalendarInstance.currentDate.getMonth();
                    if (currentYear !== newDate.getFullYear() || currentMonth !== newDate.getMonth()) {
                        mobileCalendarInstance.setDate(newDate);
                    }
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

            // Salva dati per il CSV
            resocontoCurrentData.mese = meseDaUsare;
            resocontoCurrentData.giorniData = json.data;
            resocontoCurrentData.attivitaMensili = Array.from(attivitaMap.entries());
            resocontoCurrentData.totalOre = totalOre;
            resocontoCurrentData.totalCosto = totalCosto;
            resocontoCurrentData.giorniPresenza = giorniPresenza;

            // Aggiorna o crea il calendario mobile
            const calendarContainer = document.getElementById("mobileCalendarContainer");
            if(calendarContainer && window.MobileCalendar) {
                const [anno, mese] = meseDaUsare.split('-');
                
                if(mobileCalendarInstance) {
                    // Aggiorna calendario esistente (e sincronizza mese)
                    const newDate = new Date(parseInt(anno), parseInt(mese) - 1, 1);
                    if (mobileCalendarInstance) {
                        const currentYear = mobileCalendarInstance.currentDate.getFullYear();
                        const currentMonth = mobileCalendarInstance.currentDate.getMonth();
                        if (currentYear !== newDate.getFullYear() || currentMonth !== newDate.getMonth()) {
                            mobileCalendarInstance.setDate(newDate);
                        }
                    }
                    mobileCalendarInstance.setActivitiesData(activitiesData);
                } else {
                    // Crea nuovo calendario
                    mobileCalendarInstance = new MobileCalendar('mobileCalendarContainer', {
                        selectedDate: new Date(parseInt(anno), parseInt(mese) - 1, 1),
                        activitiesData: activitiesData,
                        activitiesPanel: '#mc-activities-panel',
                        onDayClick: function(dateStr, activities) {
                            console.log('Giorno selezionato:', dateStr, attività);
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

    // FUNZIONE PER GENERARE TESTO ANTEPRIMA
    function generaTestoAnteprima() {
        if (!resocontoCurrentData.nome || resocontoCurrentData.giorniData.length === 0) {
            return 'Nessun dato disponibile';
        }

        let testo = 'RESOCONTO MENSILE\n';
        testo += '═══════════════════════════════════════\n\n';
        testo += `Nome: ${resocontoCurrentData.cognome} ${resocontoCurrentData.nome}\n`;
        testo += `Mese: ${resocontoCurrentData.mese}\n`;
        testo += `Data Stampa: ${new Date().toLocaleString('it-IT')}\n\n`;
        
        testo += 'DETTAGLIO GIORNALIERO\n';
        testo += '───────────────────────────────────────\n';
        
        resocontoCurrentData.giorniData.forEach(r => {
            const giorno = new Date(r.giorno).toLocaleDateString('it-IT');
            testo += `\n${giorno}:\n`;
            if (r.attivita && r.attivita.length > 0) {
                r.attivita.forEach(a => {
                    testo += `  • ${a.Nome}: ${a.ore.toFixed(2)}h - ${a.costo.toFixed(2)}€\n`;
                });
            } else {
                testo += `  • Presenza: ${r.ore.toFixed(2)}h - ${r.costo.toFixed(2)}€\n`;
            }
        });
        
        testo += '\n\nRIEPILOGO ATTIVITÀ MENSILE\n';
        testo += '───────────────────────────────────────\n';
        resocontoCurrentData.attivitaMensili.forEach(([nome, ore]) => {
            testo += `${nome}: ${ore.toFixed(2)}h\n`;
        });
        
        testo += '\n\nTOTALI\n';
        testo += '───────────────────────────────────────\n';
        testo += `Ore Totali: ${resocontoCurrentData.totalOre.toFixed(2)}\n`;
        testo += `Costo Totale: ${resocontoCurrentData.totalCosto.toFixed(2)}€\n`;
        testo += `Giorni di Presenza: ${resocontoCurrentData.giorniPresenza}\n`;
        
        return testo;
    }

    // PULSANTE STAMPA PRINCIPALE (PICCOLO IN ALTO)
    const stampaResocontoBtn = document.getElementById('stampaResocontoBtn');
    if (stampaResocontoBtn) {
        stampaResocontoBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('formatoDownload').value = 'pdf';
            document.getElementById('anteprimaContenuto').innerHTML = generaAnteprimaPDF();
            document.getElementById('modalAnteprimaResoconto').style.display = 'block';
            document.getElementById('overlayAnteprimaResoconto').style.display = 'block';
        });
    }

    // FUNZIONE PER CHIUDERE IL MODAL ANTEPRIMA
    window.chiudiModalAnteprima = function() {
        document.getElementById('modalAnteprimaResoconto').style.display = 'none';
        document.getElementById('overlayAnteprimaResoconto').style.display = 'none';
    }

    // BOTTONE CHIUDI ANTEPRIMA
    const chiudiAnteprimaBtn = document.getElementById('chiudiAnteprimaBtn');
    if (chiudiAnteprimaBtn) {
        chiudiAnteprimaBtn.addEventListener('click', () => {
            window.chiudiModalAnteprima();
        });
    }

    // FUNZIONE ANTEPRIMA PDF (HTML FORMATTATO)
    function generaAnteprimaPDF() {
        let html = '<div style="font-family: Arial, sans-serif; padding: 20px; background: white; color: #333; line-height: 1.6;">';
        html += '<h2 style="text-align: center; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 10px;">RESOCONTO MENSILE</h2>';
        html += '<p style="text-align: center; margin: 5px 0; font-size: 14px;"><strong>' + resocontoCurrentData.cognome + ' ' + resocontoCurrentData.nome + '</strong></p>';
        html += '<p style="text-align: center; margin: 5px 0; font-size: 13px;">Mese: ' + resocontoCurrentData.mese + '</p>';
        html += '<p style="text-align: center; margin: 5px 0; font-size: 12px; color: #666;">Data Stampa: ' + new Date().toLocaleString('it-IT') + '</p>';
        
        html += '<h3 style="margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; font-size: 14px;">DETTAGLIO GIORNALIERO</h3>';
        html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px;">';
        html += '<tr style="background: #f0f0f0; border: 1px solid #ddd;"><th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Giorno</th><th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Attività</th><th style="padding: 8px; text-align: center; border: 1px solid #ddd;">Ore</th><th style="padding: 8px; text-align: right; border: 1px solid #ddd;">Costo</th></tr>';
        
        resocontoCurrentData.giorniData.forEach(r => {
            const giorno = new Date(r.giorno).toLocaleDateString('it-IT');
            if (r.attivita && r.attivita.length > 0) {
                r.attivita.forEach((a, idx) => {
                    html += '<tr style="border: 1px solid #ddd;">';
                    if (idx === 0) html += '<td style="padding: 8px; border: 1px solid #ddd;">' + giorno + '</td>';
                    else html += '<td style="padding: 8px; border: 1px solid #ddd;"></td>';
                    html += '<td style="padding: 8px; border: 1px solid #ddd;">' + a.Nome + '</td>';
                    html += '<td style="padding: 8px; text-align: center; border: 1px solid #ddd;">' + a.ore.toFixed(2) + 'h</td>';
                    html += '<td style="padding: 8px; text-align: right; border: 1px solid #ddd;">' + a.costo.toFixed(2) + '€</td>';
                    html += '</tr>';
                });
            } else {
                html += '<tr style="border: 1px solid #ddd;">';
                html += '<td style="padding: 8px; border: 1px solid #ddd;">' + giorno + '</td>';
                html += '<td style="padding: 8px; border: 1px solid #ddd;">Presenza</td>';
                html += '<td style="padding: 8px; text-align: center; border: 1px solid #ddd;">' + r.ore.toFixed(2) + 'h</td>';
                html += '<td style="padding: 8px; text-align: right; border: 1px solid #ddd;">' + r.costo.toFixed(2) + '€</td>';
                html += '</tr>';
            }
        });
        html += '</table>';
        
        html += '<h3 style="margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; font-size: 14px;">RIEPILOGO ATTIVITÀ</h3>';
        html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px;">';
        resocontoCurrentData.attivitaMensili.forEach(([nome, ore]) => {
            html += '<tr style="border: 1px solid #ddd;"><td style="padding: 8px; border: 1px solid #ddd;">' + nome + '</td><td style="padding: 8px; text-align: right; border: 1px solid #ddd;">' + ore.toFixed(2) + 'h</td></tr>';
        });
        html += '</table>';
        
        html += '<h3 style="margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; font-size: 14px;">TOTALI</h3>';
        html += '<div style="font-size: 13px; margin-bottom: 20px;">';
        html += '<p style="margin: 5px 0;"><strong>Ore Totali:</strong> ' + resocontoCurrentData.totalOre.toFixed(2) + 'h</p>';
        html += '<p style="margin: 5px 0;"><strong>Costo Totale:</strong> ' + resocontoCurrentData.totalCosto.toFixed(2) + '€</p>';
        html += '<p style="margin: 5px 0;"><strong>Giorni di Presenza:</strong> ' + resocontoCurrentData.giorniPresenza + '</p>';
        html += '</div>';
        
        html += '<div style="margin-top: 40px; border-top: 1px solid #333; padding-top: 15px;">';
        html += '<p style="margin: 10px 0 0 0; font-size: 12px;">Firma: ___________________________</p>';
        html += '<p style="margin: 20px 0 0 0; font-size: 12px; color: #999;">Data: ' + new Date().toLocaleDateString('it-IT') + '</p>';
        html += '</div>';
        html += '</div>';
        
        return html;
    }

    // FUNZIONE ANTEPRIMA CSV (TABELLA SEMPLICE)
    function generaAnteprimaCSV() {
        let html = '<div style="font-family: monospace; font-size: 12px; padding: 10px; background: white;">';
        html += '<table style="border-collapse: collapse; width: 100%;">';
        html += '<tr style="background: #f0f0f0;"><td style="padding: 8px; border: 1px solid #ccc; font-weight: bold;">Giorno</td><td style="padding: 8px; border: 1px solid #ccc; font-weight: bold;">Attività</td><td style="padding: 8px; border: 1px solid #ccc; text-align: center; font-weight: bold;">Ore</td><td style="padding: 8px; border: 1px solid #ccc; text-align: right; font-weight: bold;">Costo</td></tr>';
        
        resocontoCurrentData.giorniData.forEach(r => {
            const giorno = new Date(r.giorno).toLocaleDateString('it-IT');
            if (r.attivita && r.attivita.length > 0) {
                r.attivita.forEach(a => {
                    html += '<tr><td style="padding: 6px; border: 1px solid #ddd;">' + giorno + '</td><td style="padding: 6px; border: 1px solid #ddd;">' + a.Nome + '</td><td style="padding: 6px; border: 1px solid #ddd; text-align: center;">' + a.ore.toFixed(2) + '</td><td style="padding: 6px; border: 1px solid #ddd; text-align: right;">' + a.costo.toFixed(2) + '</td></tr>';
                });
            } else {
                html += '<tr><td style="padding: 6px; border: 1px solid #ddd;">' + giorno + '</td><td style="padding: 6px; border: 1px solid #ddd;">Presenza</td><td style="padding: 6px; border: 1px solid #ddd; text-align: center;">' + r.ore.toFixed(2) + '</td><td style="padding: 6px; border: 1px solid #ddd; text-align: right;">' + r.costo.toFixed(2) + '</td></tr>';
            }
        });
        html += '</table>';
        
        html += '<div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">';
        html += '<p style="margin: 5px 0; font-weight: bold;">TOTALI</p>';
        html += '<p style="margin: 3px 0;">Ore: ' + resocontoCurrentData.totalOre.toFixed(2) + '</p>';
        html += '<p style="margin: 3px 0;">Costo: ' + resocontoCurrentData.totalCosto.toFixed(2) + '</p>';
        html += '<p style="margin: 3px 0;">Giorni: ' + resocontoCurrentData.giorniPresenza + '</p>';
        html += '</div>';
        html += '</div>';
        
        return html;
    }

    // AGGIORNAMENTO ANTEPRIMA AL CAMBIO FORMATO
    const formatoDownload = document.getElementById('formatoDownload');
    if (formatoDownload) {
        formatoDownload.addEventListener('change', () => {
            const formato = formatoDownload.value;
            const anteprimaElement = document.getElementById('anteprimaContenuto');
            
            if (formato === 'pdf') {
                anteprimaElement.innerHTML = generaAnteprimaPDF();
            } else if (formato === 'csv') {
                anteprimaElement.innerHTML = generaAnteprimaCSV();
            }
        });
    }

    // CHIUDI MODAL ANTEPRIMA CON ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.getElementById('modalAnteprimaResoconto').style.display === 'block') {
            window.chiudiModalAnteprima();
        }
    });

    // OVERLAY CLICK HANDLER
    const overlayAnteprimaResoconto = document.getElementById('overlayAnteprimaResoconto');
    if (overlayAnteprimaResoconto) {
        overlayAnteprimaResoconto.addEventListener('click', () => {
            window.chiudiModalAnteprima();
        });
    }

    // DOWNLOAD CSV RESOCONTO
    function generaResocontoCSV() {
        if (!resocontoCurrentData.nome || resocontoCurrentData.giorniData.length === 0) {
            alert('Nessun dato da scaricare');
            return;
        }

        let csv = 'Giorno,Attività,Ore,Costo\n';
        
        resocontoCurrentData.giorniData.forEach(r => {
            const giorno = new Date(r.giorno).toLocaleDateString('it-IT');
            if (r.attivita && r.attivita.length > 0) {
                r.attivita.forEach(a => {
                    csv += `${giorno},${a.Nome},${a.ore.toFixed(2)},${a.costo.toFixed(2)}\n`;
                });
            } else {
                csv += `${giorno},Presenza,${r.ore.toFixed(2)},${r.costo.toFixed(2)}\n`;
            }
        });
        
        csv += '\n\nRiepilogo Attività,Ore Totali\n';
        resocontoCurrentData.attivitaMensili.forEach(([nome, ore]) => {
            csv += `${nome},${ore.toFixed(2)}\n`;
        });
        
        csv += '\n\nTOTALI\n';
        csv += `Ore Totali,${resocontoCurrentData.totalOre.toFixed(2)}\n`;
        csv += `Costo Totale,${resocontoCurrentData.totalCosto.toFixed(2)}\n`;
        csv += `Giorni di Presenza,${resocontoCurrentData.giorniPresenza}\n`;
        
        // Crea il blob e scarica il file
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `resoconto_${resocontoCurrentData.cognome}_${resocontoCurrentData.mese}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // DOWNLOAD PDF RESOCONTO
    function generaResoconsoPDF() {
        if (!resocontoCurrentData.nome || resocontoCurrentData.giorniData.length === 0) {
            alert('Nessun dato da scaricare');
            return;
        }

        // Carica le librerie da CDN se non esistono
        if (typeof jsPDF === 'undefined' || typeof html2pdf === 'undefined') {
            // Carica jsPDF
            if (typeof jsPDF === 'undefined') {
                const scriptJsPDF = document.createElement('script');
                scriptJsPDF.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                document.head.appendChild(scriptJsPDF);
            }
            // Carica html2pdf
            if (typeof html2pdf === 'undefined') {
                const scriptHtml2pdf = document.createElement('script');
                scriptHtml2pdf.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
                scriptHtml2pdf.onload = () => generaResoconsoPDFInternal();
                document.head.appendChild(scriptHtml2pdf);
            } else {
                generaResoconsoPDFInternal();
            }
        } else {
            generaResoconsoPDFInternal();
        }
    }

    function generaResoconsoPDFInternal() {
        // Crea un elemento temporaneo con l'HTML dell'anteprima
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = generaAnteprimaPDF();
        tempDiv.style.padding = '20px';
        
        const opt = {
            margin: 10,
            filename: `resoconto_${resocontoCurrentData.cognome}_${resocontoCurrentData.mese}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
        };
        
        html2pdf().set(opt).from(tempDiv).save();
    }

    // PULSANTE SCARICA (NEL MODAL DI PREVIEW)
    const scaricaResocontoBtn = document.getElementById('scaricaResocontoBtn');
    if (scaricaResocontoBtn) {
        scaricaResocontoBtn.addEventListener('click', () => {
            const formato = document.getElementById('formatoDownload').value;
            if (formato === 'csv') {
                generaResocontoCSV();
            } else if (formato === 'pdf') {
                generaResoconsoPDF();
            }
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

</body>


</html>
