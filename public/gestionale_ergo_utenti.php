<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];

require __DIR__ . '/../data/db_connection.php';
$connAccount = getDbConnection('time4all');

$stmtClasse = $connAccount->prepare("SELECT classe, codice_univoco FROM Account WHERE nome_utente = ?");
$stmtClasse->bind_param("s", $username);
$stmtClasse->execute();
$resultClasse = $stmtClasse->get_result();

if ($resultClasse && $resultClasse->num_rows > 0) {
    $rowClasse = $resultClasse->fetch_assoc();
    $classe = $rowClasse['classe'];
    $codiceUnivoco = $rowClasse['codice_univoco'];
} else {
    $classe = "";
    $codiceUnivoco = "";
}
$stmtClasse->close();

if ($classe !== 'Educatore') {
    $connAccount->close();
    header("Location: index.php");
    exit;
}

$conn = getDbConnection('time4allergo');

$sqlAccount = "SELECT nome_utente, codice_univoco, classe FROM Account ORDER BY nome_utente ASC";
$resultAccount = $connAccount->query($sqlAccount);
$connAccount->close();

$sql = "SELECT id, Nome, Cognome, Fotografia, Data_nascita, Disabilita, Stipendio_Orario,
               Codice_fiscale, Email, Telefono, Note
        FROM iscritto ORDER BY Cognome ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>T4L | Gestionale Ergo Amministratore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_mobile_agenda.css">
    <link rel="icon" href="immagini/Icona.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* ── TEMA BLU ERGOTERAPEUTICA ── */
        .animated-button {
            box-shadow: 0 0 0 2px #0b516c;
        }

        .animated-button .circle {
            background-color: #0b516c;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0b516c, #1085b3);
            box-shadow: 0 4px 12px rgba(11, 81, 108, .3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1085b3, #0b516c);
            box-shadow: 0 6px 20px rgba(9, 41, 77, .4);
        }

        .btn-primary:active {
            background: #073a52;
        }

        .check-check {
            stroke: #0b516c;
        }

        .check-circle {
            stroke: #0b516c;
        }

        .edit-field input:focus,
        .edit-field textarea:focus,
        .edit-field select:focus {
            border-color: #0b516c;
            box-shadow: 0 0 0 3px rgba(11, 81, 108, .12);
        }

        #modalResocontoGiorni .summary-value {
            color: #0b516c;
        }

        .modal-box>h3::before,
        .modal-box .modal-title::before {
            background: #0b516c;
        }

        .modal-box.danger>h3::before {
            background: #b91c1c;
        }

        .presenze-day-nav .week-nav-btn:hover {
            background: #0b516c;
            border-color: #0b516c;
        }

        .presenze-day-nav .week-nav-today {
            border-color: #0b516c;
            color: #0b516c;
        }

        .presenze-day-nav .week-nav-today:hover {
            background: #0b516c;
            color: #fff;
        }

        .presenze-day-nav .week-nav-today.is-today {
            background: #0b516c;
            color: #fff;
            border-color: #0b516c;
        }

        .cal-open-btn {
            border-color: #0b516c;
            color: #0b516c;
        }

        .cal-open-btn:hover {
            background: #0b516c;
            color: #fff;
        }

        .cal-picker-header {
            background: #0b516c;
        }

        .cal-day.cal-today {
            border-color: #0b516c;
            color: #0b516c;
        }

        .cal-day.cal-selected {
            background: #0b516c !important;
        }

        .cal-day:hover:not(.cal-empty):not(.cal-future) {
            background: #d4eaf3;
            color: #0b516c;
        }

        .mese-picker-header {
            background: #0b516c;
        }

        .mese-option:hover {
            background: #d4eaf3;
            color: #0b516c;
        }

        .mese-option.mese-selected {
            background: #0b516c;
            color: #fff;
        }

        .btn-add {
            color: #0b516c;
            border-color: #7ab5cc;
        }

        .btn-add:hover {
            background: #0b516c;
            border-color: #0b516c;
            box-shadow: 0 3px 12px rgba(11, 81, 108, .20);
        }

        .btn-add.btn-add--primary {
            background: #0b516c;
            color: #fff;
            border-color: #0b516c;
        }

        .btn-add.btn-add--primary:hover {
            background: #0d6a8a;
            border-color: #0d6a8a;
        }

        .checkbox-item input[type="checkbox"] {
            accent-color: #0b516c;
        }

        .cell-truncate:hover::after {
            border-left-color: #0b516c;
        }

        .cell-truncate:hover::before {
            border-bottom-color: #0b516c;
        }

        .mc-day.mc-today {
            background: rgba(11, 81, 108, .1);
            border: 2px solid #0b516c;
            color: #0b516c;
        }

        .mc-day.mc-today .mc-day-number {
            color: #0b516c;
            font-weight: 700;
        }

        .mc-day.mc-selected {
            background: #0b516c;
            box-shadow: 0 4px 12px rgba(11, 81, 108, .3);
        }

        .mc-day.mc-selected .mc-activity-dot {
            box-shadow: 0 0 0 2px #0b516c;
        }

        .mc-activities-count {
            background: rgba(11, 81, 108, .1);
            color: #0b516c;
        }

        .mc-activity-item {
            border-left-color: #0b516c;
        }

        .mc-activity-time {
            color: #0b516c;
        }

        .mc-nav-btn {
            color: #0b516c;
        }

        .mc-nav-btn:hover {
            background-color: #0b516c;
        }

        .mobile-nav-item.active {
            background: rgba(11, 81, 108, .10);
            color: #0b516c;
        }

        button.group {
            display: none;
        }

        @media (max-width: 768px) {
            button.group {
                display: flex;
            }

            .tab-actions {
                display: none !important;
            }

            .tab-header-row {
                display: block;
            }

            .footer-bar {
                display: none;
            }

            .tab-header-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }

            .tab-header-row .page-header {
                margin-bottom: 0;
            }

            button.group {
                flex-shrink: 0;
            }
        }

        button.group svg {
            fill: none;
            stroke: #a1a1aa;
        }

        button.group:hover svg {
            fill: #27272a;
            stroke: #27272a;
        }
    </style>
</head>

<body>

    <header class="navbar">
        <div class="user-box" id="userBox">
            <img src="immagini/profilo-ergo.png" alt="Profile">
            <span id="username-nav"><?php echo htmlspecialchars($username); ?></span>
            <div class="user-dropdown" id="userDropdown">
                <a href="#" class="danger" id="logoutBtn"><span class="icon">⏻</span><span class="text">Logout</span></a>
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
        <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
        <div class="dropdown" id="dropdown">
            <div class="menu-group">
                <div class="menu-main" data-target="centroMenu"><img src="immagini/Logo-centrodiurno.png"> Centro Diurno</div>
                <div class="submenu" id="centroMenu">
                    <div class="menu-item" data-link="fogliofirme-centro.php"><img src="immagini/foglio-over.png" alt=""> Foglio firme</div>
                    <?php
                    if ($classe === 'Educatore') $gestionalePage = "gestionale_utenti.php";
                    elseif ($classe === 'Contabile') $gestionalePage = "gestionale_contabile.php";
                    elseif ($classe === 'Amministratore') $gestionalePage = "gestionale_amministratore.php";
                    else $gestionalePage = "#";
                    ?>
                    <div class="menu-item" data-link="<?php echo $gestionalePage; ?>"><img src="immagini/gestionale-over.png" alt=""> Gestionale</div>
                </div>
            </div>
            <div class="menu-group">
                <div class="menu-main" data-target="ergoMenu"><img src="immagini/Logo-Cooperativa-Ergaterapeutica.png"> Ergoterapeutica</div>
                <div class="submenu" id="ergoMenu">
                    <div class="menu-item" data-link="presenze-ergo.php"><img src="immagini/presenze-ergo.png" alt=""> Presenze</div>
                    <?php
                    if ($classe === 'Educatore') $gestionalePageErgo = "gestionale_ergo_utenti.php";
                    elseif ($classe === 'Contabile') $gestionalePageErgo = "gestionale_ergo_contabile.php";
                    elseif ($classe === 'Amministratore') $gestionalePageErgo = "gestionale_ergo_amministratore.php";
                    else $gestionalePageErgo = "#";
                    ?>
                    <div class="menu-item" data-link="<?php echo $gestionalePageErgo; ?>"><img src="immagini/gestionale-ergo.png" alt=""> Gestionale</div>
                </div>
            </div>
        </div>
    </header>

    <div class="app-layout">
        <aside class="vertical-sidebar">
            <input type="checkbox" role="switch" id="checkbox-input" class="checkbox-input" checked />
            <nav class="sidebar-nav">
                <header>
                    <div class="sidebar__toggle-container">
                        <label tabindex="0" for="checkbox-input" class="nav__toggle">
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
                    <figure><img class="sidebar-logo" src="immagini/TIME4ALL_LOGO-removebg-preview.png" alt="Logo" /></figure>
                </header>
                <section class="sidebar__wrapper">
                    <ul class="sidebar__list list--primary">
                        <li class="sidebar__item item--heading">
                            <h2 class="sidebar__item--heading">Pagine</h2>
                        </li>
                        <li class="sidebar__item"><a class="sidebar__link tab-link active" href="#" data-tab="tab-utenti" data-tooltip="Utenti"><span class="sidebar-icon"><img src="immagini/group.png" alt=""></span><span class="text">Utenti</span></a></li>
                        <li class="sidebar__item"><a class="sidebar__link tab-link" href="#" data-tab="tab-presenze" data-tooltip="Presenze"><span class="sidebar-icon"><img src="immagini/attendance.png" alt=""></span><span class="text">Presenze</span></a></li>
                        <li>
                            <hr />
                        </li>
                        <li class="sidebar__item item--heading">
                            <h2 class="sidebar__item--heading">Gestione</h2>
                        </li>
                        <li class="sidebar__item item--heading">
                            <h2 class="sidebar__item--heading">Amministrazione</h2>
                        </li>
                    </ul>
                </section>
            </nav>
        </aside>

        <main class="main-content">
            <div class="main-container">

                <!-- ═══ TAB UTENTI ═══ -->
                <div class="page-tab active" id="tab-utenti">
                    <div class="tab-header-row">
                        <div class="page-header">
                            <h1>Utenti</h1>
                            <p>Elenco iscritti registrati</p>
                        </div>
                        
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
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr '
                                            . 'data-id="' . htmlspecialchars($row['id']) . '" '
                                            . 'data-nome="' . htmlspecialchars($row['Nome']) . '" '
                                            . 'data-cognome="' . htmlspecialchars($row['Cognome']) . '" '
                                            . 'data-nascita="' . htmlspecialchars($row['Data_nascita']) . '" '
                                            . 'data-cf="' . htmlspecialchars($row['Codice_fiscale']) . '" '
                                            . 'data-email="' . htmlspecialchars($row['Email']) . '" '
                                            . 'data-telefono="' . htmlspecialchars($row['Telefono']) . '" '
                                            . 'data-disabilita="' . htmlspecialchars($row['Disabilita']) . '" '
                                            . 'data-prezzo="' . htmlspecialchars($row['Stipendio_Orario']) . '" '
                                            . 'data-note="' . htmlspecialchars($row['Note']) . '">'
                                            . '<td><img class="user-avatar" src="' . $row['Fotografia'] . '"></td>'
                                            . '<td>' . htmlspecialchars($row['Nome']) . '</td>'
                                            . '<td>' . htmlspecialchars($row['Cognome']) . '</td>'
                                            . '<td>' . htmlspecialchars($row['Data_nascita']) . '</td>'
                                            . '<td>' . htmlspecialchars($row['Email']) . '</td>'
                                            . '<td>' . htmlspecialchars($row['Telefono']) . '</td>'
                                            . '<td><span class="cell-truncate cell-truncate--md" data-tooltip="' . htmlspecialchars($row['Disabilita']) . '">' . htmlspecialchars($row['Disabilita']) . '</span></td>'
                                            . '<td>'
                                            . '<button class="view-btn"><img src="immagini/open-eye.png" alt="Visualizza"></button>'
                                            . '</td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="8">Nessun utente registrato.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-box large" id="viewModal">
                        <div class="profile-header"><img id="viewAvatar" class="profile-avatar">
                            <div class="profile-main">
                                <h3 id="viewFullname"></h3><span id="viewBirth"></span>
                            </div>
                        </div>
                        <div class="profile-grid" id="viewContent"></div>
                        <div class="modal-actions"><button class="btn-secondary" onclick="closeModal()">Chiudi</button></div>
                    </div>

                    <div class="modal-box large" id="modalModificaUtente">
                        <h3 class="modal-title">Modifica utente</h3>
                        <form id="formModificaUtente">
                            <input type="hidden" id="editUtenteId">
                            <div class="edit-field"><label>Nome</label><input type="text" id="editUtenteNome" required></div>
                            <div class="edit-field"><label>Cognome</label><input type="text" id="editUtenteCognome" required></div>
                            <div class="edit-field"><label>Data di nascita</label><input type="date" id="editUtenteData" required></div>
                            <div class="edit-field"><label>Codice Fiscale</label><input type="text" id="editUtenteCF" required></div>
                            <div class="edit-field"><label>Email</label><input type="email" id="editUtenteEmail"></div>
                            <div class="edit-field"><label>Telefono</label><input type="tel" id="editUtenteTelefono"></div>
                            <div class="edit-field"><label>Disabilità</label><input type="text" id="editUtenteDisabilita"></div>
                            <div class="edit-field"><label>Stipendio orario (€)</label><input type="number" id="editUtentePrezzo" step="0.01"></div>
                            <div class="edit-field"><label>Note</label><textarea id="editUtenteNote"></textarea></div>
                            <div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button><button type="button" class="btn-primary" id="salvaModificaUtente">Salva</button></div>
                        </form>
                    </div>

                    <div class="modal-box danger" id="modalDeleteUtente">
                        <h3>Elimina utente</h3>
                        <p>Questa azione è definitiva. Vuoi continuare?</p>
                        <div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeModal()">Annulla</button><button type="button" class="btn-danger" id="confirmDeleteUtente">Elimina</button></div>
                    </div>
                </div>

                <!-- ═══ TAB PRESENZE ═══ -->
                <div class="page-tab" id="tab-presenze">
                    <div class="tab-header-row">
                        <div class="page-header">
                            <h1>Presenze</h1>
                            <p>Elenco presenze giornaliere</p>
                        </div>
                        <div class="tab-actions">
                            <button class="btn-add btn-add--primary" id="aggiungi-presenza-btn">
                                <span class="btn-add-icon"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg></span>Aggiungi Presenza
                            </button>
                        </div>
                        <button title="Add New" id="aggiungi-presenza-btn-mobile" class="group cursor-pointer outline-none hover:rotate-90 duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50px" height="50px" viewBox="0 0 24 24" class="stroke-zinc-400 fill-none group-hover:fill-zinc-800 group-active:stroke-zinc-200 group-active:fill-zinc-600 group-active:duration-0 duration-300">
                                <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke-width="1"></path>
                                <path d="M8 12H16" stroke-width="1"></path>
                                <path d="M12 16V8" stroke-width="1"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="presenze-day-nav">
                        <button class="week-nav-btn" id="prevDayBtn" title="Giorno precedente"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 18l-6-6 6-6" />
                            </svg></button>
                        <span class="presenze-day-label" id="presenzeDayLabel"></span>
                        <button class="week-nav-btn" id="nextDayBtn" title="Giorno successivo"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6" />
                            </svg></button>
                        <button class="week-nav-today" id="todayPresenzeBtn" title="Vai ad oggi">Oggi</button>
                        <button class="cal-open-btn" id="calOpenBtn" title="Scegli data"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg></button>
                    </div>

                    <div class="cal-picker-overlay" id="calPickerOverlay">
                        <div class="cal-picker" id="calPicker">
                            <div class="cal-picker-header">
                                <div class="cal-month-row">
                                    <button class="cal-nav-btn" id="calPrevMonth"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M15 18l-6-6 6-6" />
                                        </svg></button>
                                    <span class="cal-month-label" id="calMonthLabel"></span>
                                    <button class="cal-nav-btn" id="calNextMonth"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M9 18l6-6-6-6" />
                                        </svg></button>
                                </div>
                                <div class="cal-weekdays">
                                    <div class="cal-weekday">Lu</div>
                                    <div class="cal-weekday">Ma</div>
                                    <div class="cal-weekday">Me</div>
                                    <div class="cal-weekday">Gi</div>
                                    <div class="cal-weekday">Ve</div>
                                    <div class="cal-weekday">Sa</div>
                                    <div class="cal-weekday">Do</div>
                                </div>
                            </div>
                            <div class="cal-grid" id="calGrid"></div>
                        </div>
                    </div>

                    <div class="modal-box large" id="modalAggiungiPresenza">
                        <h3 class="modal-title">Aggiungi Presenza</h3>
                        <form id="formAggiungiPresenza">
                            <div class="edit-field"><label>Iscritto</label><select id="apIscritto" required>
                                    <option value="">— Seleziona iscritto —</option>
                                </select></div>
                            <div class="edit-field"><label>Data</label><input type="date" id="apData" required></div>
                            <div class="edit-field"><label>Ora ingresso</label><input type="time" id="apIngresso" required></div>
                            <div class="edit-field"><label>Ora uscita</label><input type="time" id="apUscita"></div>
                            <div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button><button type="submit" class="btn-primary">Salva</button></div>
                        </form>
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
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="modal-box large" id="editModal">
                        <h3 class="modal-title" id="modalEditTitle">Modifica Presenza</h3>
                        <div class="edit-grid">
                            <div class="edit-field"><label>Ora ingresso</label><input type="time" id="editIngresso"></div>
                            <div class="edit-field"><label>Ora uscita</label><input type="time" id="editUscita"></div>
                        </div>
                        <div class="modal-actions"><button class="btn-secondary" onclick="closeModal()">Chiudi</button><button class="btn-primary" id="saveEdit">Salva</button></div>
                    </div>
                    <div class="modal-box danger" id="deleteModal">
                        <h3>Elimina presenza</h3>
                        <p>Questa azione è definitiva. Vuoi continuare?</p>
                        <div class="modal-actions"><button class="btn-secondary" onclick="closeModal()">Annulla</button><button class="btn-danger" id="confirmDeletePresenza">Elimina</button></div>
                    </div>
                </div>

            

            </div>
        </main>
    </div>

    <div class="popup success-popup" id="successPopup">
        <div class="success-content">
            <div class="success-icon"><svg viewBox="-2 -2 56 56">
                    <circle class="check-circle" cx="26" cy="26" r="25" fill="none" />
                    <path class="check-check" d="M14 27 L22 35 L38 19" fill="none" />
                </svg></div>
            <p class="success-text" id="success-text">Operazione completata!</p>
        </div>
    </div>

    <footer class="footer-bar" style="bottom:auto;">
        <div class="footer-left">© Time4All • 2026</div>
        <div class="footer-top"><a href="#top" class="footer-image"></a></div>
        <div class="footer-right"><a href="privacy_policy.php" class="hover-underline-animation">PRIVACY POLICY</a></div>
    </footer>

    <nav class="mobile-bottom-nav">
        <a href="#" class="mobile-nav-item active" data-tab="tab-utenti" onclick="switchTab('tab-utenti',this);return false;">
            <div class="mobile-nav-icon"><img src="immagini/group.png" alt="Utenti"></div><span class="mobile-nav-label">Utenti</span>
        </a>
        <a href="#" class="mobile-nav-item" data-tab="tab-presenze" onclick="switchTab('tab-presenze',this);return false;">
            <div class="mobile-nav-icon"><img src="immagini/attendance.png" alt="Presenze"></div><span class="mobile-nav-label">Presenze</span>
        </a>
    </nav>

    <div class="modal-overlay" id="Overlay"></div>
    <script src="js/mobile-calendar.js"></script>
    <script>
        function getLocalDateString(d) {
            const y = d.getFullYear(),
                m = (d.getMonth() + 1).toString().padStart(2, '0'),
                dd = d.getDate().toString().padStart(2, '0');
            return `${y}-${m}-${dd}`;
        }
        document.querySelectorAll('.tab-link').forEach(l => l.addEventListener('click', e => {
            e.preventDefault();
            const t = e.currentTarget.dataset.tab;
            document.querySelectorAll('.tab-link').forEach(x => x.classList.remove('active'));
            document.querySelectorAll('.page-tab').forEach(x => x.classList.remove('active'));
            e.currentTarget.classList.add('active');
            document.getElementById(t).classList.add('active');
            localStorage.setItem('activeTab', t);
        }));

        function switchTab(tabId, navItem) {
            document.querySelectorAll('.mobile-nav-item').forEach(i => i.classList.remove('active'));
            navItem.classList.add('active');
            document.querySelectorAll('.tab-link').forEach(l => {
                l.classList.remove('active');
                if (l.dataset.tab === tabId) l.classList.add('active');
            });
            document.querySelectorAll('.page-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            localStorage.setItem('activeTab', tabId);
        }
        const ham = document.getElementById('hamburger'),
            drop = document.getElementById('dropdown');
        ham.onclick = () => {
            ham.classList.toggle('active');
            drop.classList.toggle('show');
        };
        document.querySelectorAll('.menu-main').forEach(m => m.addEventListener('click', () => {
            const tm = document.getElementById(m.dataset.target);
            document.querySelectorAll('.submenu').forEach(x => {
                if (x !== tm) {
                    x.classList.remove('open');
                    x.previousElementSibling.classList.remove('open');
                }
            });
            tm.classList.toggle('open');
            m.classList.toggle('open');
        }));
        document.querySelectorAll('.menu-item').forEach(i => {
            i.onclick = () => window.location.href = i.dataset.link;
        });
        const userBox = document.getElementById('userBox'),
            userDropdown = document.getElementById('userDropdown');
        userBox.addEventListener('click', e => {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        document.addEventListener('click', e => {
            if (!userBox.contains(e.target)) userDropdown.classList.remove('show');
        });
        const logoutBtn = document.getElementById('logoutBtn'),
            logoutOverlay = document.getElementById('logoutOverlay'),
            logoutModal = document.getElementById('logoutModal');
        logoutBtn.addEventListener('click', e => {
            e.preventDefault();
            logoutOverlay.classList.add('show');
            logoutModal.classList.add('show');
        });
        document.getElementById('cancelLogout').onclick = () => {
            logoutOverlay.classList.remove('show');
            logoutModal.classList.remove('show');
        };
        logoutOverlay.onclick = () => {
            logoutOverlay.classList.remove('show');
            logoutModal.classList.remove('show');
        };
        document.getElementById('confirmLogout').onclick = () => window.location.href = 'logout.php';
        const Overlay = document.getElementById('Overlay'),
            successText = document.getElementById('success-text'),
            successPopup = document.getElementById('successPopup');

        function openModal(m) {
            if (!m) return;
            m.classList.add('show');
            if (Overlay) Overlay.classList.add('show');
        }

        function closeModal() {
            if (Overlay) Overlay.classList.remove('show');
            document.querySelectorAll('.modal-box.show').forEach(e => e.classList.remove('show'));
        }

        function showSuccess(msg) {
            successText.innerText = msg;
            successPopup.classList.add('show');
            if (Overlay) Overlay.classList.add('show');
        }

        function hideSuccess() {
            successPopup.classList.remove('show');
            if (Overlay) Overlay.classList.remove('show');
        }
        if (Overlay) Overlay.onclick = closeModal;
        document.querySelectorAll('.view-btn').forEach(btn => btn.onclick = e => {
            const row = e.target.closest('tr');
            document.getElementById('viewAvatar').src = row.querySelector('img').src;
            document.getElementById('viewFullname').innerText = row.dataset.nome + ' ' + row.dataset.cognome;
            document.getElementById('viewBirth').innerText = 'Nato il ' + row.dataset.nascita;
            document.getElementById('viewContent').innerHTML = `
        <div class="profile-field"><label>Nome</label><span>${row.dataset.nome}</span></div>
        <div class="profile-field"><label>Cognome</label><span>${row.dataset.cognome}</span></div>
        <div class="profile-field"><label>Data di nascita</label><span>${row.dataset.nascita}</span></div>
        <div class="profile-field"><label>Codice Fiscale</label><span>${row.dataset.cf||'—'}</span></div>
        <div class="profile-field"><label>Email</label><span>${row.dataset.email||'—'}</span></div>
        <div class="profile-field"><label>Telefono</label><span>${row.dataset.telefono||'—'}</span></div>
        <div class="profile-field"><label>Disabilità</label><span>${row.dataset.disabilita||'—'}</span></div>
        <div class="profile-field"><label>Stipendio orario</label><span>${row.dataset.prezzo?row.dataset.prezzo+' €':'—'}</span></div>
        <div class="profile-field" style="grid-column:1/-1"><label>Note</label><span>${row.dataset.note||'—'}</span></div>`;
            openModal(document.getElementById('viewModal'));
        });
        // ── PRESENZE navigazione
        let presenzeOffset = parseInt(localStorage.getItem('presenzeOffsetErgo') || '0');

        function getPresenzaDateString(o) {
            const d = new Date();
            d.setDate(d.getDate() + o);
            return getLocalDateString(d);
        }

        function updatePresenzeDayLabel() {
            const d = new Date();
            d.setDate(d.getDate() + presenzeOffset);
            const label = d.toLocaleDateString('it-IT', {
                weekday: 'long',
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
            document.getElementById('presenzeDayLabel').innerText = label.charAt(0).toUpperCase() + label.slice(1);
            const tb = document.getElementById('todayPresenzeBtn');
            if (tb) tb.classList.toggle('is-today', presenzeOffset === 0);
            const nb = document.getElementById('nextDayBtn');
            if (nb) {
                nb.disabled = presenzeOffset >= 0;
                nb.style.opacity = nb.disabled ? '.4' : '1';
            }
        }

        function loadPresenze() {
            const dataStr = getPresenzaDateString(presenzeOffset);
            const tbody = document.querySelector('#presenzeTable tbody');
            if (!tbody) return;
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#888;">Caricamento...</td></tr>';
            updatePresenzeDayLabel();
            fetch(`api/api_get_presenze_ergo.php?data=${dataStr}`).then(r => r.json()).then(data => {
                if (!data.success) {
                    tbody.innerHTML = '<tr><td colspan="6">Errore nel caricamento</td></tr>';
                    return;
                }
                if (data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#888;">Nessuna presenza registrata per questo giorno.</td></tr>';
                    return;
                }
                tbody.innerHTML = data.data.map(row => `<tr data-id="${row.id}" data-nome="${row.nome}" data-cognome="${row.cognome}" data-ingresso="${row.ingresso}" data-uscita="${row.uscita||''}"><td><img class="user-avatar" src="${row.fotografia}"></td><td>${row.nome}</td><td>${row.cognome}</td><td>${row.ingresso}</td><td>${row.uscita||'—'}</td><td><button class="edit-presenza-btn" data-id="${row.id}"><img src="immagini/edit.png" alt="Modifica"></button><button class="delete-presenza-btn" data-id="${row.id}"><img src="immagini/delete.png" alt="Elimina"></button></td></tr>`).join('');
            }).catch(() => {
                tbody.innerHTML = '<tr><td colspan="6">Errore di rete</td></tr>';
            });
        }
        document.getElementById('prevDayBtn').onclick = () => {
            presenzeOffset--;
            localStorage.setItem('presenzeOffsetErgo', presenzeOffset);
            loadPresenze();
        };
        document.getElementById('nextDayBtn').onclick = () => {
            if (presenzeOffset < 0) {
                presenzeOffset++;
                localStorage.setItem('presenzeOffsetErgo', presenzeOffset);
                loadPresenze();
            }
        };
        document.getElementById('todayPresenzeBtn').onclick = () => {
            if (presenzeOffset !== 0) {
                presenzeOffset = 0;
                localStorage.setItem('presenzeOffsetErgo', presenzeOffset);
                loadPresenze();
            }
        };
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-presenza-btn')) {
                const btn = e.target.closest('.edit-presenza-btn'),
                    row = btn.closest('tr');
                const ingresso = row.dataset.ingresso || '',
                    uscita = row.dataset.uscita || '';
                document.getElementById('modalEditTitle').innerText = 'Modifica Presenza — ' + row.dataset.nome + ' ' + row.dataset.cognome;
                const em = document.getElementById('editModal');
                em.dataset.presenzeId = row.dataset.id;
                em.dataset.presenzaData = ingresso.split(' ')[0] || getLocalDateString(new Date());
                document.getElementById('editIngresso').value = (ingresso.split(' ')[1] || '').slice(0, 5);
                document.getElementById('editUscita').value = (uscita.split(' ')[1] || '').slice(0, 5);
                openModal(em);
            }
            if (e.target.closest('.delete-presenza-btn')) {
                const btn = e.target.closest('.delete-presenza-btn'),
                    row = btn.closest('tr');
                const dm = document.getElementById('deleteModal');
                dm.querySelector('h3').innerText = 'Elimina presenza — ' + row.dataset.nome + ' ' + row.dataset.cognome;
                dm.dataset.presenzeId = row.dataset.id;
                openModal(dm);
            }
        });
        document.getElementById('saveEdit').onclick = () => {
            const em = document.getElementById('editModal'),
                id = em.dataset.presenzeId,
                data = em.dataset.presenzaData;
            const ingresso = data + ' ' + document.getElementById('editIngresso').value + ':00';
            const uscita = data + ' ' + document.getElementById('editUscita').value + ':00';
            fetch('api/api_modifica_presenza_ergo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        id,
                        ingresso,
                        uscita
                    })
                })
                .then(r => r.json()).then(d => {
                    if (d.success) {
                        closeModal();
                        showSuccess('Presenza modificata!!');
                        setTimeout(() => {
                            hideSuccess();
                            loadPresenze();
                        }, 1800);
                    } else alert('Errore: ' + d.message);
                });
        };
        document.getElementById('confirmDeletePresenza').onclick = () => {
            const id = document.getElementById('deleteModal').dataset.presenzeId;
            fetch('api/api_elimina_presenza_ergo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        id
                    })
                })
                .then(r => r.json()).then(d => {
                    if (d.success) {
                        closeModal();
                        showSuccess('Presenza eliminata!!');
                        setTimeout(() => {
                            hideSuccess();
                            loadPresenze();
                        }, 1800);
                    } else alert('Errore: ' + d.message);
                });
        };
        // ── CALENDARIO PICKER
        (function() {
            const overlay = document.getElementById('calPickerOverlay'),
                picker = document.getElementById('calPicker'),
                openBtn = document.getElementById('calOpenBtn');
            const grid = document.getElementById('calGrid'),
                monthLbl = document.getElementById('calMonthLabel'),
                prevBtn = document.getElementById('calPrevMonth'),
                nextBtn = document.getElementById('calNextMonth');
            if (!overlay || !openBtn) return;
            let calViewDate = new Date();
            calViewDate.setDate(1);
            const TODAY = new Date();
            TODAY.setHours(0, 0, 0, 0);

            function pad(n) {
                return String(n).padStart(2, '0');
            }

            function toDateStr(d) {
                return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            }

            function getSelectedStr() {
                const d = new Date(TODAY);
                d.setDate(d.getDate() + presenzeOffset);
                return toDateStr(d);
            }

            function renderCalendar() {
                const year = calViewDate.getFullYear(),
                    month = calViewDate.getMonth(),
                    selectedStr = getSelectedStr();
                monthLbl.textContent = new Date(year, month, 1).toLocaleDateString('it-IT', {
                    month: 'long',
                    year: 'numeric'
                });
                nextBtn.disabled = (year > TODAY.getFullYear() || (year === TODAY.getFullYear() && month >= TODAY.getMonth()));
                nextBtn.style.opacity = nextBtn.disabled ? '.4' : '1';
                const firstDay = new Date(year, month, 1).getDay(),
                    offset = (firstDay === 0) ? 6 : firstDay - 1,
                    daysInMonth = new Date(year, month + 1, 0).getDate();
                grid.innerHTML = '';
                for (let i = 0; i < offset; i++) {
                    const el = document.createElement('div');
                    el.className = 'cal-day cal-empty';
                    grid.appendChild(el);
                }
                for (let d = 1; d <= daysInMonth; d++) {
                    const dateObj = new Date(year, month, d),
                        dateStr = toDateStr(dateObj),
                        isFuture = dateObj > TODAY,
                        isToday = dateStr === toDateStr(TODAY),
                        isSelected = dateStr === selectedStr;
                    const el = document.createElement('div');
                    el.className = 'cal-day' + (isFuture ? ' cal-future' : '') + (isToday ? ' cal-today' : '') + (isSelected ? ' cal-selected' : '');
                    el.textContent = d;
                    if (!isFuture) {
                        el.addEventListener('click', () => {
                            const diff = Math.round((dateObj - TODAY) / 86400000);
                            presenzeOffset = diff;
                            localStorage.setItem('presenzeOffsetErgo', presenzeOffset);
                            loadPresenze();
                            closeCalendar();
                        });
                    }
                    grid.appendChild(el);
                }
            }

            function openCalendar() {
                const sel = new Date(TODAY);
                sel.setDate(sel.getDate() + presenzeOffset);
                calViewDate = new Date(sel.getFullYear(), sel.getMonth(), 1);
                renderCalendar();
                overlay.classList.add('open');
                const rect = openBtn.getBoundingClientRect();
                let left = rect.left;
                if (left + 300 > window.innerWidth - 8) left = window.innerWidth - 308;
                picker.style.top = (rect.bottom + window.scrollY + 6) + 'px';
                picker.style.left = left + 'px';
            }

            function closeCalendar() {
                overlay.classList.remove('open');
            }
            openBtn.addEventListener('click', e => {
                e.stopPropagation();
                overlay.classList.contains('open') ? closeCalendar() : openCalendar();
            });
            overlay.addEventListener('click', e => {
                if (!picker.contains(e.target)) closeCalendar();
            });
            prevBtn.addEventListener('click', e => {
                e.stopPropagation();
                calViewDate.setMonth(calViewDate.getMonth() - 1);
                renderCalendar();
            });
            nextBtn.addEventListener('click', e => {
                e.stopPropagation();
                if (!nextBtn.disabled) {
                    calViewDate.setMonth(calViewDate.getMonth() + 1);
                    renderCalendar();
                }
            });
        })();
        // ── AGGIUNGI PRESENZA
        (function() {
            const btnAP = document.getElementById('aggiungi-presenza-btn'),
                btnAPM = document.getElementById('aggiungi-presenza-btn-mobile'),
                modalAP = document.getElementById('modalAggiungiPresenza'),
                formAP = document.getElementById('formAggiungiPresenza'),
                selAP = document.getElementById('apIscritto'),
                inputData = document.getElementById('apData');
            if (!btnAP || !modalAP) return;

            function popolaIscritti() {
                selAP.innerHTML = '<option value="">— Seleziona iscritto —</option>';
                document.querySelectorAll('#tab-utenti .users-table tbody tr[data-id]').forEach(row => {
                    const opt = document.createElement('option');
                    opt.value = row.dataset.id;
                    opt.textContent = (row.dataset.cognome || '') + ' ' + (row.dataset.nome || '');
                    selAP.appendChild(opt);
                });
                inputData.value = getPresenzaDateString(presenzeOffset);
                const today = new Date();
                inputData.max = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;
                inputData.readOnly = true;
            }
            btnAP.addEventListener('click', () => {
                popolaIscritti();
                openModal(modalAP);
            });
            btnAPM.addEventListener('click', () => {
                popolaIscritti();
                openModal(modalAP);
            });
            formAP.addEventListener('submit', function(e) {
                e.preventDefault();
                const id_iscritto = selAP.value,
                    data = inputData.value,
                    ora_ingresso = document.getElementById('apIngresso').value,
                    ora_uscita = document.getElementById('apUscita').value;
                if (!id_iscritto || !data || !ora_ingresso) {
                    alert('Compila i campi obbligatori.');
                    return;
                }
                fetch('api/api_aggiungi_presenza_ergo.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            id_iscritto: parseInt(id_iscritto),
                            data,
                            ora_ingresso,
                            ora_uscita
                        })
                    })
                    .then(r => r.json()).then(res => {
                        if (res.success) {
                            closeModal();
                            showSuccess('Presenza aggiunta!!');
                            setTimeout(() => {
                                hideSuccess();
                                const pd = getPresenzaDateString(presenzeOffset);
                                if (pd === data) loadPresenze();
                            }, 1800);
                        } else alert('Errore: ' + res.message);
                    }).catch(() => alert('Errore di rete'));
            });
        })();
       
        // ── SIDEBAR + SCROLL LOCK + RESTORE
        const checkboxInput = document.getElementById('checkbox-input');
        if (checkboxInput) {
            const s = localStorage.getItem('sidebarOpen');
            if (s !== null) checkboxInput.checked = s === 'true';
            checkboxInput.addEventListener('change', () => localStorage.setItem('sidebarOpen', checkboxInput.checked));
        }

        function syncBodyScrollLock() {
            document.body.classList.toggle('popup-open', Boolean(document.querySelector('.modal-box.show,.popup.show,.logout-modal.show,.success-popup.show,.modal-overlay.show,.logout-overlay.show')));
        }
        new MutationObserver(() => syncBodyScrollLock()).observe(document.body, {
            subtree: true,
            attributes: true,
            attributeFilter: ['class']
        });
        syncBodyScrollLock();
        window.addEventListener('DOMContentLoaded', () => {
            loadPresenze();
            const savedTab = localStorage.getItem('activeTab');
            if (savedTab) {
                document.querySelectorAll('.mobile-nav-item').forEach(i => i.classList.remove('active'));
                const mn = document.querySelector(`.mobile-nav-item[data-tab="${savedTab}"]`);
                if (mn) mn.classList.add('active');
                document.querySelectorAll('.tab-link').forEach(l => {
                    l.classList.remove('active');
                    if (l.dataset.tab === savedTab) l.classList.add('active');
                });
                document.querySelectorAll('.page-tab').forEach(t => t.classList.remove('active'));
                const sc = document.getElementById(savedTab);
                if (sc) sc.classList.add('active');
            }
        });
        document.addEventListener('mousemove', e => {
            document.documentElement.style.setProperty('--tt-y', (e.clientY + 14) + 'px');
            document.documentElement.style.setProperty('--tt-x', (e.clientX - 10) + 'px');
            document.documentElement.style.setProperty('--tt-arrow-y', (e.clientY + 8) + 'px');
            document.documentElement.style.setProperty('--tt-arrow-x', (e.clientX + 4) + 'px');
        });
    </script>
</body>

</html>