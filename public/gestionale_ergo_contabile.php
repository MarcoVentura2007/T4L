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

if ($classe !== 'Contabile') {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>T4L | Gestionale Ergo Contabile</title>
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
        .custom-select-wrapper .cs-trigger.cs-open {
            border-color: #0b516c;
            box-shadow: 0 0 0 3px rgba(11, 81, 108, .10);
            border-bottom-color: transparent;
        }
        .custom-select-wrapper .cs-trigger.cs-open .cs-arrow {
            color: #0b516c;
        }
        .custom-select-wrapper .cs-panel {
            border-color: #0b516c;
            box-shadow: 0 8px 24px rgba(11, 81, 108, .12), 0 2px 8px rgba(0, 0, 0, .06);
        }
        .custom-select-wrapper .cs-header {
            background: #0b516c;
        }
        .custom-select-wrapper .cs-option:hover {
            color: #0b516c;
        }
        .custom-select-wrapper .cs-option.cs-selected {
            background: #0b516c;
            color: #fff;
        }
        .custom-select-wrapper .cs-option.cs-selected:hover {
            background: #0d6a8a;
        }
        .birth-cal-btn {
            background: #f4f4f5;
            border-color: #0b516c;
            color: #0b516c;
        }
        .birth-cal-btn:hover {
            background: #0b516c;
            border-color: #0b516c;
            color: #fff;
        }
        .birth-cal-year-row,
        .birth-cal-month-row .cal-nav-btn {
            background: #0b516c;
            color: #fff;
            border-color: #0b516c;
        }
        .birth-cal-month-row .cal-nav-btn:hover {
            background: #0b516c;
            border-color: #0b516c;
            color: #fff;
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
                        <li class="sidebar__item"><a class="sidebar__link tab-link" href="#" data-tab="tab-resoconti" data-tooltip="Resoconti"><span class="sidebar-icon"><img src="immagini/resoconti.png" alt=""></span><span class="text">Resoconti</span></a></li>
                       
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
                        <div class="tab-actions">
                            <button class="btn-add btn-add--primary" id="aggiungi-utente-btn">
                                <span class="btn-add-icon"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg></span>Aggiungi Utente
                            </button>
                        </div>
                        <button title="Add New" id="aggiungi-utente-btn-mobile" class="group cursor-pointer outline-none hover:rotate-90 duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50px" height="50px" viewBox="0 0 24 24" class="stroke-zinc-400 fill-none group-hover:fill-zinc-800 group-active:stroke-zinc-200 group-active:fill-zinc-600 group-active:duration-0 duration-300">
                                <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke-width="1"></path>
                                <path d="M8 12H16" stroke-width="1"></path>
                                <path d="M12 16V8" stroke-width="1"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="modal-box large" id="modalAggiungiUtente">
                        <h3>Aggiungi nuovo utente</h3>
                        <form id="formAggiungiUtente">
                            <div class="edit-field"><label>Nome</label><input type="text" id="utenteNome" placeholder="Nome" required></div>
                            <div class="edit-field"><label>Cognome</label><input type="text" id="utenteCognome" placeholder="Cognome" required></div>
                            <div class="edit-field">
                                <label>Data di nascita</label>
                                <div class="birth-picker-wrap">
                                    <input type="text" id="utenteData" class="birth-picker-input" placeholder="GG/MM/AAAA" readonly required>
                                    <input type="hidden" id="utenteDataHidden">
                                    <button type="button" class="birth-cal-btn" id="birthdayCalBtnAdd" aria-label="Apri calendario">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                            <line x1="16" y1="2" x2="16" y2="6" />
                                            <line x1="8" y1="2" x2="8" y2="6" />
                                            <line x1="3" y1="10" x2="21" y2="10" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="edit-field"><label>Codice Fiscale</label><input type="text" id="utenteCF" placeholder="Codice Fiscale" required></div>
                            <div class="edit-field"><label>Email</label><input type="email" id="utenteEmail" placeholder="Email"></div>
                            <div class="edit-field"><label>Telefono</label><input type="tel" id="utenteTelefono" placeholder="Telefono"></div>
                            <div class="edit-field"><label>Disabilità</label><input type="text" id="utenteDisabilita" placeholder="Disabilità"></div>
                            <div class="edit-field"><label>Stipendio orario (€)</label><input type="number" id="utentePrezzo" placeholder="Stipendio orario" step="0.01"></div>
                            <div class="edit-field"><label>Note</label><textarea id="utenteNote"></textarea></div>
                            <div class="edit-field" style="grid-column:1/-1">
                                <label>Fotografia</label>
                                <div class="file-inline">
                                    <input type="file" id="utenteFoto" accept="image/*" hidden>
                                    <button type="button" class="file-btn-minimal" onclick="document.getElementById('utenteFoto').click()">Scegli file</button>
                                    <div class="file-preview-container">
                                        <img id="previewFotoMini" class="preview-mini" style="display:none;">
                                        <button type="button" id="clearFileBtn" class="clear-file-btn" title="Rimuovi file">&times;</button>
                                    </div>
                                    <span class="file-name" id="nomeFileFoto">Nessun file</span>
                                </div>
                            </div>
                            <div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button><button type="submit" class="btn-primary">Salva</button></div>
                        </form>
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
                                            . '<button class="edit-utente-btn"><img src="immagini/edit.png" alt="Modifica"></button>'
                                            . '<button class="delete-utente-btn"><img src="immagini/delete.png" alt="Elimina"></button>'
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
                            <div class="edit-field">
                                <label>Data di nascita</label>
                                <div class="birth-picker-wrap">
                                    <input type="text" id="editUtenteData" class="birth-picker-input" placeholder="GG/MM/AAAA" readonly required>
                                    <input type="hidden" id="editUtenteDataHidden">
                                    <button type="button" class="birth-cal-btn" id="birthdayCalBtnEdit" aria-label="Apri calendario">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                            <line x1="16" y1="2" x2="16" y2="6" />
                                            <line x1="8" y1="2" x2="8" y2="6" />
                                            <line x1="3" y1="10" x2="21" y2="10" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
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

                    <div class="birth-cal-overlay" id="birthCalOverlayAdd">
                        <div class="birth-cal-picker" id="birthCalPickerAdd">
                            <div class="birth-cal-header">
                                <div class="birth-cal-month-row">
                                    <button class="cal-nav-btn" id="birthCalPrevMonthAdd" type="button">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M15 18l-6-6 6-6" />
                                        </svg>
                                    </button>
                                    <span class="birth-cal-month-label" id="birthCalMonthLabelAdd"></span>
                                    <button class="cal-nav-btn" id="birthCalNextMonthAdd" type="button">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M9 18l6-6-6-6" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="birth-cal-year-row">
                                    <button class="birth-year-nav" id="birthCalPrevYearAdd" type="button">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M15 18l-6-6 6-6" />
                                            <path d="M9 18l-6-6 6-6" />
                                        </svg>
                                    </button>
                                    <button class="birth-year-nav" id="birthCalPrevDecadeAdd" type="button">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M15 18l-6-6 6-6" />
                                        </svg>
                                    </button>
                                    <select class="birth-year-select" id="birthCalYearSelectAdd"></select>
                                    <button class="birth-year-nav" id="birthCalNextDecadeAdd" type="button">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M9 18l6-6-6-6" />
                                        </svg>
                                    </button>
                                    <button class="birth-year-nav" id="birthCalNextYearAdd" type="button">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M9 18l6-6-6-6" />
                                            <path d="M15 18l6-6-6-6" />
                                        </svg>
                                    </button>
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
                            <div class="cal-grid" id="birthCalGridAdd"></div>
                        </div>
                    </div>

                    <div class="birth-cal-overlay" id="birthCalOverlayEdit">
                        <div class="birth-cal-picker" id="birthCalPickerEdit">
                            <div class="birth-cal-header">
                                <div class="birth-cal-month-row">
                                    <button class="cal-nav-btn" id="birthCalPrevMonthEdit" type="button">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M15 18l-6-6 6-6" />
                                        </svg>
                                    </button>
                                    <span class="birth-cal-month-label" id="birthCalMonthLabelEdit"></span>
                                    <button class="cal-nav-btn" id="birthCalNextMonthEdit" type="button">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M9 18l6-6-6-6" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="birth-cal-year-row">
                                    <button class="birth-year-nav" id="birthCalPrevYearEdit" type="button">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M15 18l-6-6 6-6" />
                                            <path d="M9 18l-6-6 6-6" />
                                        </svg>
                                    </button>
                                    <button class="birth-year-nav" id="birthCalPrevDecadeEdit" type="button">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M15 18l-6-6 6-6" />
                                        </svg>
                                    </button>
                                    <select class="birth-year-select" id="birthCalYearSelectEdit"></select>
                                    <button class="birth-year-nav" id="birthCalNextDecadeEdit" type="button">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M9 18l6-6-6-6" />
                                        </svg>
                                    </button>
                                    <button class="birth-year-nav" id="birthCalNextYearEdit" type="button">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M9 18l6-6-6-6" />
                                            <path d="M15 18l6-6-6-6" />
                                        </svg>
                                    </button>
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
                            <div class="cal-grid" id="birthCalGridEdit"></div>
                        </div>
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

                <!-- ═══ TAB RESOCONTI ═══ -->
                <div class="page-tab" id="tab-resoconti">
                    <div class="page-header" style="margin-bottom:20px;">
                        <h1>Resoconti</h1>
                        <p>Riepilogo mensile iscritti</p>
                    </div>

                    <div class="presenze-day-nav" id="meseNavContainer" style="margin-bottom:18px;">
                        <button class="week-nav-btn" id="mesePrevBtn" title="Mese precedente"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 18l-6-6 6-6" />
                            </svg></button>
                        <span class="presenze-day-label" id="meseLabelSpan" style="min-width:180px;text-align:center;"></span>
                        <button class="week-nav-btn" id="meseNextBtn" title="Mese successivo"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6" />
                            </svg></button>
                        <button class="cal-open-btn" id="meseCalBtn" title="Scegli mese"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg></button>
                    </div>

                    <div class="mese-picker-overlay" id="mesePickerOverlay">
                        <div class="mese-picker" id="mesePicker">
                            <div class="mese-picker-header">
                                <button class="mese-year-btn" id="mesePrevYear"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                        <path d="M15 18l-6-6 6-6" />
                                    </svg></button>
                                <span class="mese-picker-year" id="mesePickerYear"></span>
                                <button class="mese-year-btn" id="meseNextYear"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                        <path d="M9 18l6-6-6-6" />
                                    </svg></button>
                            </div>
                            <div class="mese-grid" id="meseGrid"></div>
                        </div>
                    </div>

                    <input type="hidden" id="resocontiMeseFiltro" value="<?= date('Y-m') ?>">

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
                                <tr>
                                    <td colspan="6">Caricamento...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-box large modal-resoconto" id="modalResocontoGiorni">
                        <h3 class="modal-title" id="resocontoNome"></h3>
                        <div class="resoconto-summary" id="resocontoSummary">
                            <div class="summary-card">
                                <div class="summary-label">Ore Totali</div>
                                <div class="summary-value" id="summaryOre">0</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-label">Stipendio Mensile</div>
                                <div class="summary-value" id="summaryStipendio">0 €</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-label">Giorni di Presenza</div>
                                <div class="summary-value" id="summaryGiorni">0</div>
                            </div>
                        </div>
                        <div class="resoconto-calendar-wrapper">
                            <div class="calendar-section">
                                <div id="resocontoContent" class="mobile-calendar"></div>
                            </div>
                            <div class="activities-section">
                                <div id="mc-activities-panel" class="mc-activities-panel">
                                    <div class="mc-activities-placeholder">Seleziona un giorno per vedere le attività</div>
                                </div>
                            </div>
                        </div>
                        <div class="users-table-box" style="display:none">
                            <table class="users-table">
                                <tbody id="resocontoGiorniBody"></tbody>
                            </table>
                        </div>
                        <div class="modal-actions">
                            <button class="print-btn" id="stampaResocontoErgoBtn" style="margin-right:auto;">
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
                                    <span class="printer-page-wrapper"><span class="printer-page"></span></span>
                                </span>
                                Stampa
                            </button>
                            <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
                        </div>
                    </div>

                    <div id="overlayAnteprimaResocontoErgo" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.3);z-index:9999;"></div>
                    <div class="modal-anteprima-resoconto" id="modalAnteprimaResocontoErgo" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10000;max-width:1000px;width:90%;max-height:90vh;overflow-y:auto;pointer-events:auto;background:#f9fafb;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding:20px;background:linear-gradient(135deg,#f4f6f9 0%,#ffffff 100%);border-bottom:2px solid #e5e7eb;border-radius:8px 8px 0 0;">
                            <h3 class="modal-title" style="margin:0;color:#111827;">Anteprima Resoconto</h3>
                            <label style="display:flex;align-items:center;gap:8px;font-weight:500;color:#4b5563;">
                                Salva come:
                                <select id="formatoDownloadErgo" style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:6px;background-color:white;cursor:pointer;color:#111827;font-weight:500;">
                                    <option value="pdf">PDF</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </label>
                        </div>
                        <div id="anteprimaContenutErgo" style="border:1px solid #e5e7eb;padding:30px;background:white;max-height:600px;overflow-y:auto;margin:20px;border-radius:8px;font-family:'Courier New',monospace;font-size:14px;line-height:1.6;white-space:pre-wrap;word-wrap:break-word;box-shadow:0 2px 8px rgba(0,0,0,0.05);"></div>
                        <div class="modal-actions" style="gap:15px;padding:20px;margin-top:0;background:#f9fafb;border-top:1px solid #e5e7eb;">
                            <button class="print-btn" id="scaricaResocontoErgoBtn" style="flex:1;padding:12px 20px;background:white;color:#333;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-weight:500;">Scarica</button>
                            <button class="btn-secondary" id="chiudiAnteprimaErgoBtn" style="padding:12px 20px;background:#e5e7eb;color:#111827;border:none;border-radius:6px;cursor:pointer;font-weight:500;">Chiudi</button>
                        </div>
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
        <a href="#" class="mobile-nav-item" data-tab="tab-resoconti" onclick="switchTab('tab-resoconti',this);return false;">
            <div class="mobile-nav-icon"><img src="immagini/resoconti.png" alt="Resoconti"></div><span class="mobile-nav-label">Resoconti</span>
        </a>
    </nav>

    <div class="modal-overlay" id="Overlay"></div>
    <script src="js/mobile-calendar.js"></script>
    <script src="js/custom-select.js"></script>
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
        // ── UTENTI CRUD
        const aggiungiUtenteBtn = document.getElementById('aggiungi-utente-btn'),
            aggiungiUtenteBtnMob = document.getElementById('aggiungi-utente-btn-mobile'),
            modalAggiungiUtente = document.getElementById('modalAggiungiUtente'),
            modalModificaUtente = document.getElementById('modalModificaUtente'),
            modalDeleteUtente = document.getElementById('modalDeleteUtente'),
            formAggiungiUtente = document.getElementById('formAggiungiUtente');
        aggiungiUtenteBtn?.addEventListener('click', () => {
            const addCalBtn = document.getElementById('birthdayCalBtnAdd');
            if (addCalBtn && typeof addCalBtn._setBirthDate === 'function') addCalBtn._setBirthDate(null);
            openModal(modalAggiungiUtente);
        });
        aggiungiUtenteBtnMob?.addEventListener('click', () => {
            const addCalBtn = document.getElementById('birthdayCalBtnAdd');
            if (addCalBtn && typeof addCalBtn._setBirthDate === 'function') addCalBtn._setBirthDate(null);
            openModal(modalAggiungiUtente);
        });
        formAggiungiUtente.onsubmit = function(e) {
            e.preventDefault();
            const dataNascita = document.getElementById('utenteDataHidden').value;
            if (!dataNascita) {
                alert('Seleziona la data di nascita dal calendario.');
                return;
            }
            const fd = new FormData();
            fd.append('nome', document.getElementById('utenteNome').value.trim());
            fd.append('cognome', document.getElementById('utenteCognome').value.trim());
            fd.append('data_nascita', dataNascita);
            fd.append('codice_fiscale', document.getElementById('utenteCF').value.trim());
            fd.append('email', document.getElementById('utenteEmail').value.trim());
            fd.append('telefono', document.getElementById('utenteTelefono').value.trim());
            fd.append('disabilita', document.getElementById('utenteDisabilita').value.trim());
            fd.append('prezzo_orario', parseFloat(document.getElementById('utentePrezzo').value) || 0);
            fd.append('note', document.getElementById('utenteNote').value.trim());
            const fi = document.getElementById('utenteFoto');
            if (fi.files.length > 0) fd.append('foto', fi.files[0]);
            fetch('api/api_aggiungi_utente_ergo.php', {
                method: 'POST',
                body: fd
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    closeModal();
                    showSuccess('Utente aggiunto!!');
                    setTimeout(() => {
                        hideSuccess();
                        location.reload();
                    }, 1800);
                } else alert('Errore: ' + data.message);
            });
        };
        document.querySelectorAll('.edit-utente-btn').forEach(btn => btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            document.getElementById('editUtenteId').value = row.dataset.id;
            document.getElementById('editUtenteNome').value = row.dataset.nome;
            document.getElementById('editUtenteCognome').value = row.dataset.cognome;
            document.getElementById('editUtenteCF').value = row.dataset.cf;
            const editCalBtn = document.getElementById('birthdayCalBtnEdit');
            if (editCalBtn && typeof editCalBtn._setBirthDate === 'function') {
                editCalBtn._setBirthDate(row.dataset.nascita);
            } else {
                document.getElementById('editUtenteData').value = row.dataset.nascita;
                document.getElementById('editUtenteDataHidden').value = row.dataset.nascita;
            }
            document.getElementById('editUtenteEmail').value = row.dataset.email;
            document.getElementById('editUtenteTelefono').value = row.dataset.telefono;
            document.getElementById('editUtenteDisabilita').value = row.dataset.disabilita;
            document.getElementById('editUtentePrezzo').value = row.dataset.prezzo;
            document.getElementById('editUtenteNote').value = row.dataset.note;
            openModal(modalModificaUtente);
        }));
        document.getElementById('salvaModificaUtente')?.addEventListener('click', () => {
            fetch('api/api_modifica_utente_ergo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: document.getElementById('editUtenteId').value,
                    nome: document.getElementById('editUtenteNome').value.trim(),
                    cognome: document.getElementById('editUtenteCognome').value.trim(),
                    data_nascita: document.getElementById('editUtenteDataHidden').value || document.getElementById('editUtenteData').value,
                    codice_fiscale: document.getElementById('editUtenteCF').value.trim(),
                    email: document.getElementById('editUtenteEmail').value.trim(),
                    telefono: document.getElementById('editUtenteTelefono').value.trim(),
                    disabilita: document.getElementById('editUtenteDisabilita').value.trim(),
                    prezzo_orario: parseFloat(document.getElementById('editUtentePrezzo').value) || 0,
                    note: document.getElementById('editUtenteNote').value.trim()
                })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    closeModal();
                    showSuccess('Utente modificato!!');
                    setTimeout(() => {
                        hideSuccess();
                        location.reload();
                    }, 1800);
                } else alert('Errore: ' + data.message);
            });
        });
        let rowToDeleteUtente = null;
        document.querySelectorAll('.delete-utente-btn').forEach(btn => btn.addEventListener('click', () => {
            rowToDeleteUtente = btn.closest('tr');
            document.querySelector('#modalDeleteUtente h3').innerText = 'Elimina utente: ' + rowToDeleteUtente.dataset.nome;
            openModal(modalDeleteUtente);
        }));
        document.getElementById('confirmDeleteUtente')?.addEventListener('click', () => {
            if (!rowToDeleteUtente) return;
            fetch('api/api_elimina_utente_ergo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: rowToDeleteUtente.dataset.id
                })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    closeModal();
                    showSuccess('Utente eliminato!!');
                    setTimeout(() => {
                        hideSuccess();
                        location.reload();
                    }, 1800);
                } else alert('Errore: ' + data.message);
            });
        });

        (function() {
            function makeBirthdayCal(cfg) {
                const overlay = document.getElementById(cfg.overlayId);
                const picker = document.getElementById(cfg.pickerId);
                const openBtn = document.getElementById(cfg.openBtnId);
                const displayInput = document.getElementById(cfg.displayInputId);
                const hiddenInput = document.getElementById(cfg.hiddenInputId);
                if (!overlay || !picker || !openBtn || !displayInput || !hiddenInput) return;
                const grid = document.getElementById(cfg.gridId);
                const monthLbl = document.getElementById(cfg.monthLblId);
                const prevMonthBtn = document.getElementById(cfg.prevMonthId);
                const nextMonthBtn = document.getElementById(cfg.nextMonthId);
                const prevYearBtn = document.getElementById(cfg.prevYearId);
                const nextYearBtn = document.getElementById(cfg.nextYearId);
                const prevDecBtn = document.getElementById(cfg.prevDecadeId);
                const nextDecBtn = document.getElementById(cfg.nextDecadeId);
                const yearSelect = document.getElementById(cfg.yearSelectId);
                const TODAY = new Date();
                TODAY.setHours(0, 0, 0, 0);
                const MIN_YEAR = 1900;
                let calViewDate = new Date(TODAY.getFullYear() - 30, 0, 1);
                let selectedDate = null;
                function pad(n) {
                    return String(n).padStart(2, '0');
                }
                function toDateStr(d) {
                    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                }
                function toDisplayStr(d) {
                    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
                }
                function buildYearSelect() {
                    yearSelect.innerHTML = '';
                    const curY = calViewDate.getFullYear();
                    for (let y = TODAY.getFullYear(); y >= MIN_YEAR; y--) {
                        const opt = document.createElement('option');
                        opt.value = y;
                        opt.textContent = y;
                        if (y === curY) opt.selected = true;
                        yearSelect.appendChild(opt);
                    }
                }
                function renderCalendar() {
                    const year = calViewDate.getFullYear();
                    const month = calViewDate.getMonth();
                    const selStr = selectedDate ? toDateStr(selectedDate) : null;
                    monthLbl.textContent = new Date(year, month, 1).toLocaleDateString('it-IT', { month: 'long' }).replace(/^./, c => c.toUpperCase());
                    buildYearSelect();
                    const isMaxMonth = (year === TODAY.getFullYear() && month >= TODAY.getMonth());
                    nextMonthBtn.disabled = isMaxMonth;
                    nextMonthBtn.style.opacity = isMaxMonth ? '.3' : '1';
                    nextYearBtn.disabled = year >= TODAY.getFullYear();
                    nextYearBtn.style.opacity = nextYearBtn.disabled ? '.3' : '1';
                    nextDecBtn.disabled = year + 10 > TODAY.getFullYear();
                    nextDecBtn.style.opacity = nextDecBtn.disabled ? '.3' : '1';
                    prevMonthBtn.disabled = year === MIN_YEAR && month === 0;
                    prevYearBtn.disabled = year <= MIN_YEAR;
                    prevDecBtn.disabled = year - 10 < MIN_YEAR;
                    const firstDay = new Date(year, month, 1).getDay();
                    const offset = firstDay === 0 ? 6 : firstDay - 1;
                    const daysInMonth = new Date(year, month + 1, 0).getDate();
                    grid.innerHTML = '';
                    for (let i = 0; i < offset; i++) {
                        const el = document.createElement('div');
                        el.className = 'cal-day cal-empty';
                        grid.appendChild(el);
                    }
                    for (let d = 1; d <= daysInMonth; d++) {
                        const dateObj = new Date(year, month, d);
                        const dateStr = toDateStr(dateObj);
                        const isFuture = dateObj > TODAY;
                        const isSelected = selStr === dateStr;
                        const el = document.createElement('div');
                        el.className = 'cal-day' + (isFuture ? ' cal-future' : '') + (isSelected ? ' cal-selected' : '');
                        el.textContent = d;
                        if (!isFuture) {
                            el.addEventListener('click', () => {
                                selectedDate = new Date(year, month, d);
                                displayInput.value = toDisplayStr(selectedDate);
                                hiddenInput.value = dateStr;
                                closeCal();
                            });
                        }
                        grid.appendChild(el);
                    }
                }
                function openCal() {
                    if (selectedDate) {
                        calViewDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
                    }
                    renderCalendar();
                    overlay.classList.add('open');
                    const rect = openBtn.getBoundingClientRect();
                    const pickerW = 300;
                    let left = rect.left;
                    if (left + pickerW > window.innerWidth - 8) left = window.innerWidth - pickerW - 8;
                    if (left < 8) left = 8;
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const pickerH = 380;
                    let top = rect.bottom + window.scrollY + 6;
                    if (spaceBelow < pickerH && rect.top > pickerH) {
                        top = rect.top + window.scrollY - pickerH - 6;
                    }
                    picker.style.top = top + 'px';
                    picker.style.left = left + 'px';
                }
                function closeCal() {
                    overlay.classList.remove('open');
                }
                openBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    overlay.classList.contains('open') ? closeCal() : openCal();
                });
                overlay.addEventListener('click', e => {
                    if (!picker.contains(e.target)) closeCal();
                });
                prevMonthBtn.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    calViewDate.setMonth(calViewDate.getMonth() - 1);
                    renderCalendar();
                });
                nextMonthBtn.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!nextMonthBtn.disabled) {
                        calViewDate.setMonth(calViewDate.getMonth() + 1);
                        renderCalendar();
                    }
                });
                prevYearBtn.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (calViewDate.getFullYear() > MIN_YEAR) {
                        calViewDate.setFullYear(calViewDate.getFullYear() - 1);
                        renderCalendar();
                    }
                });
                nextYearBtn.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (calViewDate.getFullYear() < TODAY.getFullYear()) {
                        calViewDate.setFullYear(calViewDate.getFullYear() + 1);
                        renderCalendar();
                    }
                });
                prevDecBtn.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    calViewDate.setFullYear(Math.max(MIN_YEAR, calViewDate.getFullYear() - 10));
                    renderCalendar();
                });
                nextDecBtn.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    calViewDate.setFullYear(Math.min(TODAY.getFullYear(), calViewDate.getFullYear() + 10));
                    renderCalendar();
                });
                yearSelect.addEventListener('change', e => {
                    e.stopPropagation();
                    calViewDate.setFullYear(parseInt(yearSelect.value, 10));
                    renderCalendar();
                });
                openBtn._setBirthDate = function(isoStr) {
                    if (!isoStr) {
                        selectedDate = null;
                        displayInput.value = '';
                        hiddenInput.value = '';
                        return;
                    }
                    const parts = isoStr.split('-');
                    if (parts.length === 3) {
                        const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                        selectedDate = d;
                        displayInput.value = toDisplayStr(d);
                        hiddenInput.value = isoStr;
                        calViewDate = new Date(d.getFullYear(), d.getMonth(), 1);
                    }
                };
            }
            makeBirthdayCal({
                openBtnId: 'birthdayCalBtnAdd',
                overlayId: 'birthCalOverlayAdd',
                pickerId: 'birthCalPickerAdd',
                gridId: 'birthCalGridAdd',
                monthLblId: 'birthCalMonthLabelAdd',
                prevMonthId: 'birthCalPrevMonthAdd',
                nextMonthId: 'birthCalNextMonthAdd',
                prevYearId: 'birthCalPrevYearAdd',
                nextYearId: 'birthCalNextYearAdd',
                prevDecadeId: 'birthCalPrevDecadeAdd',
                nextDecadeId: 'birthCalNextDecadeAdd',
                yearSelectId: 'birthCalYearSelectAdd',
                displayInputId: 'utenteData',
                hiddenInputId: 'utenteDataHidden'
            });
            makeBirthdayCal({
                openBtnId: 'birthdayCalBtnEdit',
                overlayId: 'birthCalOverlayEdit',
                pickerId: 'birthCalPickerEdit',
                gridId: 'birthCalGridEdit',
                monthLblId: 'birthCalMonthLabelEdit',
                prevMonthId: 'birthCalPrevMonthEdit',
                nextMonthId: 'birthCalNextMonthEdit',
                prevYearId: 'birthCalPrevYearEdit',
                nextYearId: 'birthCalNextYearEdit',
                prevDecadeId: 'birthCalPrevDecadeEdit',
                nextDecadeId: 'birthCalNextDecadeEdit',
                yearSelectId: 'birthCalYearSelectEdit',
                displayInputId: 'editUtenteData',
                hiddenInputId: 'editUtenteDataHidden'
            });
        })();
        const utenteFoto = document.getElementById('utenteFoto'),
            preview = document.getElementById('previewFotoMini'),
            fileNameSpan = document.getElementById('nomeFileFoto'),
            clearBtn = document.getElementById('clearFileBtn');
        utenteFoto.addEventListener('change', function() {
            if (!this.files.length) {
                preview.style.display = 'none';
                fileNameSpan.innerText = 'Nessun file';
                clearBtn.style.display = 'none';
                return;
            }
            preview.src = URL.createObjectURL(this.files[0]);
            preview.style.display = 'block';
            fileNameSpan.innerText = this.files[0].name;
            clearBtn.style.display = 'block';
        });
        clearBtn.addEventListener('click', () => {
            utenteFoto.value = '';
            preview.style.display = 'none';
            fileNameSpan.innerText = 'Nessun file';
            clearBtn.style.display = 'none';
        });
        // ── RESOCONTI
        document.addEventListener('DOMContentLoaded', () => {
            const resocontiMeseFiltro = document.getElementById('resocontiMeseFiltro'),
                resocontiMensiliBody = document.getElementById('resocontiMensiliBody'),
                modalResoconto = document.getElementById('modalResocontoGiorni'),
                bodyResoconto = document.getElementById('resocontoGiorniBody'),
                titoloResoconto = document.getElementById('resocontoNome');
            let currentIscritto = null,
                mobileCalendarInstance = null;
            (function() {
                const MESI = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
                const TODAY = new Date();
                let curYear = TODAY.getFullYear(),
                    curMonth = TODAY.getMonth(),
                    pickerYear = curYear;
                const labelSpan = document.getElementById('meseLabelSpan'),
                    prevBtn = document.getElementById('mesePrevBtn'),
                    nextBtn = document.getElementById('meseNextBtn'),
                    calBtn = document.getElementById('meseCalBtn'),
                    overlay = document.getElementById('mesePickerOverlay'),
                    picker = document.getElementById('mesePicker'),
                    yearLbl = document.getElementById('mesePickerYear'),
                    grid = document.getElementById('meseGrid'),
                    prevYearBtn = document.getElementById('mesePrevYear'),
                    nextYearBtn = document.getElementById('meseNextYear'),
                    hidden = resocontiMeseFiltro;
                if (!labelSpan) return;

                function pad(n) {
                    return String(n).padStart(2, '0');
                }

                function getMeseStr(y, m) {
                    return `${y}-${pad(m+1)}`;
                }

                function updateLabel() {
                    labelSpan.textContent = MESI[curMonth] + ' ' + curYear;
                    const atMax = curYear > TODAY.getFullYear() || (curYear === TODAY.getFullYear() && curMonth >= TODAY.getMonth());
                    nextBtn.disabled = atMax;
                    nextBtn.style.opacity = atMax ? '.4' : '1';
                    hidden.value = getMeseStr(curYear, curMonth);
                }

                function doLoad() {
                    updateLabel();
                    caricaResocontiMensili(hidden.value);
                }
                prevBtn.addEventListener('click', () => {
                    if (curMonth === 0) {
                        curMonth = 11;
                        curYear--;
                    } else curMonth--;
                    doLoad();
                });
                nextBtn.addEventListener('click', () => {
                    if (nextBtn.disabled) return;
                    if (curMonth === 11) {
                        curMonth = 0;
                        curYear++;
                    } else curMonth++;
                    doLoad();
                });

                function renderPicker() {
                    yearLbl.textContent = pickerYear;
                    nextYearBtn.disabled = pickerYear >= TODAY.getFullYear();
                    nextYearBtn.style.opacity = pickerYear >= TODAY.getFullYear() ? '.4' : '1';
                    grid.innerHTML = '';
                    MESI.forEach((nome, i) => {
                        const isFuture = pickerYear > TODAY.getFullYear() || (pickerYear === TODAY.getFullYear() && i > TODAY.getMonth()),
                            isSelected = pickerYear === curYear && i === curMonth;
                        const el = document.createElement('div');
                        el.className = 'mese-option' + (isFuture ? ' mese-future' : '') + (isSelected ? ' mese-selected' : '');
                        el.textContent = nome.substring(0, 3);
                        if (!isFuture) {
                            el.addEventListener('click', () => {
                                curYear = pickerYear;
                                curMonth = i;
                                doLoad();
                                closePicker();
                            });
                        }
                        grid.appendChild(el);
                    });
                }

                function openPicker() {
                    pickerYear = curYear;
                    renderPicker();
                    overlay.classList.add('open');
                    const rect = calBtn.getBoundingClientRect();
                    let left = rect.left;
                    if (left + 300 > window.innerWidth - 8) left = window.innerWidth - 308;
                    picker.style.top = (rect.bottom + window.scrollY + 6) + 'px';
                    picker.style.left = left + 'px';
                }

                function closePicker() {
                    overlay.classList.remove('open');
                }
                calBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    overlay.classList.contains('open') ? closePicker() : openPicker();
                });
                overlay.addEventListener('click', e => {
                    if (!picker.contains(e.target)) closePicker();
                });
                prevYearBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    pickerYear--;
                    renderPicker();
                });
                nextYearBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    if (pickerYear < TODAY.getFullYear()) {
                        pickerYear++;
                        renderPicker();
                    }
                });
                doLoad();
            })();
            document.addEventListener('click', e => {
                const btn = e.target.closest('.calendario-btn');
                if (!btn) return;
                currentIscritto = btn.dataset.id;
                if (titoloResoconto) titoloResoconto.textContent = 'Resoconto — ' + ((btn.dataset.cognome || '') + ' ' + (btn.dataset.nome || '')).trim();
                if (bodyResoconto) bodyResoconto.innerHTML = '<tr><td colspan="4">Caricamento...</td></tr>';
                if (modalResoconto) openModal(modalResoconto);
                caricaResocontoGiorni();
            });

            function caricaResocontiMensili(mese) {
                if (!resocontiMensiliBody) return;
                resocontiMensiliBody.innerHTML = '<tr><td colspan="6">Caricamento...</td></tr>';
                fetch('api/api_resoconto_mensile_ergo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        mese
                    })
                }).then(r => r.json()).then(json => {
                    resocontiMensiliBody.innerHTML = '';
                    if (!json.success || json.data.length === 0) {
                        resocontiMensiliBody.innerHTML = '<tr><td colspan="6">Nessun dato disponibile</td></tr>';
                        return;
                    }
                    json.data.forEach(r => {
                        const ore = parseFloat(r.ore_totali).toFixed(2),
                            stipendio = parseFloat(r.ore_totali * r.Stipendio_Orario).toFixed(2);
                        resocontiMensiliBody.innerHTML += `<tr><td><img src="${r.Fotografia}" class="user-avatar"></td><td>${r.Nome}</td><td>${r.Cognome}</td><td>${ore}</td><td>${stipendio} €</td><td><button class="btn-icon calendario-btn" data-id="${r.id}" data-nome="${r.Nome}" data-cognome="${r.Cognome}"><img src="immagini/calendario.png" alt="Calendario"></button></td></tr>`;
                    });
                }).catch(() => {
                    resocontiMensiliBody.innerHTML = '<tr><td colspan="6">Errore nel caricamento</td></tr>';
                });
            }

            function caricaResocontoGiorni(meseForzato = null) {
                if (!currentIscritto) return;
                const meseDaUsare = meseForzato || resocontiMeseFiltro.value;
                fetch('api/api_resoconto_giornaliero_ergo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: currentIscritto,
                        mese: meseDaUsare
                    })
                }).then(r => r.json()).then(json => {
                    if (!bodyResoconto) return;
                    bodyResoconto.innerHTML = '';
                    const resocontoContent = document.getElementById('resocontoContent');
                    if (resocontoContent) resocontoContent.innerHTML = '';
                    let totalOre = 0,
                        totalCosto = 0,
                        giorniPresenza = 0;
                    const summaryOre = document.getElementById('summaryOre'),
                        summaryStipendio = document.getElementById('summaryStipendio'),
                        summaryGiorni = document.getElementById('summaryGiorni');
                    if (!json.success || json.data.length === 0) {
                        bodyResoconto.innerHTML = '<tr><td colspan="4">Nessun dato</td></tr>';
                        if (summaryOre) summaryOre.textContent = '0.00';
                        if (summaryStipendio) summaryStipendio.textContent = '0.00 €';
                        if (summaryGiorni) summaryGiorni.textContent = '0';
                        const [anno, mese] = meseDaUsare.split('-');
                        if (mobileCalendarInstance) mobileCalendarInstance.setActivitiesData({});
                        else if (resocontoContent && window.MobileCalendar) {
                            mobileCalendarInstance = new MobileCalendar('resocontoContent', {
                                selectedDate: new Date(parseInt(anno), parseInt(mese) - 1, 1),
                                activitiesData: {},
                                activitiesPanel: '#mc-activities-panel',
                                onMonthChange: nd => caricaResocontoGiorni(`${nd.getFullYear()}-${String(nd.getMonth()+1).padStart(2,'0')}`)
                            });
                        }
                        return;
                    }
                    const activitiesData = {};
                    json.data.forEach(r => {
                        const giorno = new Date(r.giorno).getDate(),
                            dateStr = r.giorno;
                        giorniPresenza++;
                        totalOre += r.ore;
                        totalCosto += r.costo;
                        bodyResoconto.innerHTML += `<tr><td>${giorno}</td><td>Presenza</td><td>${r.ore.toFixed(2)}</td><td>${r.costo.toFixed(2)} €</td></tr>`;
                        if (!activitiesData[dateStr]) activitiesData[dateStr] = [];
                        activitiesData[dateStr].push({
                            nome: 'Presenza',
                            descrizione: `${r.ore.toFixed(2)} ore — ${r.costo.toFixed(2)}€`,
                            ora_inizio: '',
                            ora_fine: '',
                            educatori: ''
                        });
                    });
                    if (summaryOre) summaryOre.textContent = totalOre.toFixed(2);
                    if (summaryStipendio) summaryStipendio.textContent = totalCosto.toFixed(2) + ' €';
                    if (summaryGiorni) summaryGiorni.textContent = giorniPresenza;
                    resocontoCurrentData = {
                        nome: titoloResoconto.textContent.split(' — ')[1]?.split(' ').slice(-1)[0] || '',
                        cognome: titoloResoconto.textContent.split(' — ')[1]?.split(' ').slice(0, -1).join(' ') || '',
                        mese: meseDaUsare,
                        giorniData: json.data,
                        totalOre,
                        totalCosto,
                        giorniPresenza
                    };
                    const [anno, mese] = meseDaUsare.split('-');
                    if (resocontoContent && window.MobileCalendar) {
                        if (mobileCalendarInstance) {
                            const nd = new Date(parseInt(anno), parseInt(mese) - 1, 1);
                            if (mobileCalendarInstance.currentDate.getFullYear() !== nd.getFullYear() || mobileCalendarInstance.currentDate.getMonth() !== nd.getMonth()) mobileCalendarInstance.setDate(nd);
                            mobileCalendarInstance.setActivitiesData(activitiesData);
                        } else {
                            mobileCalendarInstance = new MobileCalendar('resocontoContent', {
                                selectedDate: new Date(parseInt(anno), parseInt(mese) - 1, 1),
                                activitiesData,
                                activitiesPanel: '#mc-activities-panel',
                                onMonthChange: nd => caricaResocontoGiorni(`${nd.getFullYear()}-${String(nd.getMonth()+1).padStart(2,'0')}`)
                            });
                        }
                    }
                }).catch(() => {
                    if (bodyResoconto) bodyResoconto.innerHTML = '<tr><td colspan="4">Errore nel caricamento</td></tr>';
                });
            }

            let resocontoCurrentData = {};

            function generaAnteprimaPDF() {
                let h = '<div style="font-family:Arial,sans-serif;padding:20px;background:white;color:#333;line-height:1.6;">';
                h += `<h2 style="text-align:center;border-bottom:2px solid #333;padding-bottom:10px;">RESOCONTO MENSILE</h2>`;
                h += `<p style="text-align:center;font-size:14px;"><strong>${resocontoCurrentData.cognome} ${resocontoCurrentData.nome}</strong></p>`;
                h += `<p style="text-align:center;font-size:13px;">Mese: ${resocontoCurrentData.mese}</p>`;
                h += `<p style="text-align:center;font-size:12px;color:#666;">Data Stampa: ${new Date().toLocaleString('it-IT')}</p>`;
                h += '<h3 style="margin-top:20px;border-bottom:1px solid #ddd;padding-bottom:5px;font-size:14px;">DETTAGLIO GIORNALIERO</h3>';
                h += '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12px;">';
                h += '<tr style="background:#f0f0f0;"><th style="padding:8px;border:1px solid #ddd;">Giorno</th><th style="padding:8px;border:1px solid #ddd;">Ore</th><th style="padding:8px;border:1px solid #ddd;text-align:right;">Stipendio</th></tr>';
                resocontoCurrentData.giorniData.forEach(r => {
                    const g = new Date(r.giorno).toLocaleDateString('it-IT');
                    h += `<tr style="border:1px solid #ddd;"><td style="padding:8px;border:1px solid #ddd;">${g}</td><td style="padding:8px;text-align:center;border:1px solid #ddd;">${r.ore.toFixed(2)}h</td><td style="padding:8px;text-align:right;border:1px solid #ddd;">${r.costo.toFixed(2)}€</td></tr>`;
                });
                h += '</table>';
                h += `<h3 style="margin-top:20px;border-bottom:1px solid #ddd;padding-bottom:5px;font-size:14px;">TOTALI</h3>`;
                h += `<div style="font-size:13px;"><p><strong>Ore Totali:</strong> ${resocontoCurrentData.totalOre.toFixed(2)}h</p><p><strong>Stipendio Totale:</strong> ${resocontoCurrentData.totalCosto.toFixed(2)}€</p><p><strong>Giorni di Presenza:</strong> ${resocontoCurrentData.giorniPresenza}</p></div>`;
                h += `<div style="margin-top:40px;border-top:1px solid #333;padding-top:15px;"><p style="font-size:12px;">Firma: ___________________________</p><p style="margin-top:20px;font-size:12px;color:#999;">Data: ${new Date().toLocaleDateString('it-IT')}</p></div>`;
                h += '</div>';
                return h;
            }

            function generaAnteprimaCSV() {
                let h = '<div style="font-family:monospace;font-size:12px;padding:10px;background:white;"><table style="border-collapse:collapse;width:100%;">';
                h += '<tr style="background:#f0f0f0;"><td style="padding:8px;border:1px solid #ccc;font-weight:bold;">Giorno</td><td style="padding:8px;border:1px solid #ccc;text-align:center;font-weight:bold;">Ore</td><td style="padding:8px;border:1px solid #ccc;text-align:right;font-weight:bold;">Stipendio</td></tr>';
                resocontoCurrentData.giorniData.forEach(r => {
                    const g = new Date(r.giorno).toLocaleDateString('it-IT');
                    h += `<tr><td style="padding:6px;border:1px solid #ddd;">${g}</td><td style="padding:6px;border:1px solid #ddd;text-align:center;">${r.ore.toFixed(2)}</td><td style="padding:6px;border:1px solid #ddd;text-align:right;">${r.costo.toFixed(2)}</td></tr>`;
                });
                h += `</table><div style="margin-top:20px;padding:15px;background:#f9f9f9;border:1px solid #ddd;"><p style="font-weight:bold;">TOTALI</p><p>Ore: ${resocontoCurrentData.totalOre.toFixed(2)}</p><p>Stipendio: ${resocontoCurrentData.totalCosto.toFixed(2)}</p><p>Giorni: ${resocontoCurrentData.giorniPresenza}</p></div></div>`;
                return h;
            }

            const stampaResocontoErgoBtn = document.getElementById('stampaResocontoErgoBtn');
            if (stampaResocontoErgoBtn) {
                stampaResocontoErgoBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    document.getElementById('formatoDownloadErgo').value = 'pdf';
                    document.getElementById('anteprimaContenutErgo').innerHTML = generaAnteprimaPDF();
                    document.getElementById('modalAnteprimaResocontoErgo').style.display = 'block';
                    document.getElementById('overlayAnteprimaResocontoErgo').style.display = 'block';
                });
            }

            window.chiudiModalAnteprimaErgo = function() {
                document.getElementById('modalAnteprimaResocontoErgo').style.display = 'none';
                document.getElementById('overlayAnteprimaResocontoErgo').style.display = 'none';
            };
            document.getElementById('chiudiAnteprimaErgoBtn')?.addEventListener('click', () => window.chiudiModalAnteprimaErgo());
            document.getElementById('overlayAnteprimaResocontoErgo')?.addEventListener('click', () => window.chiudiModalAnteprimaErgo());
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && document.getElementById('modalAnteprimaResocontoErgo').style.display === 'block') window.chiudiModalAnteprimaErgo();
            });

            const formatoDownloadErgo = document.getElementById('formatoDownloadErgo');
            if (formatoDownloadErgo) {
                formatoDownloadErgo.addEventListener('change', () => {
                    const f = formatoDownloadErgo.value;
                    document.getElementById('anteprimaContenutErgo').innerHTML = f === 'pdf' ? generaAnteprimaPDF() : generaAnteprimaCSV();
                });
            }

            function generaResocontoCSV() {
                if (!resocontoCurrentData.nome || resocontoCurrentData.giorniData.length === 0) {
                    alert('Nessun dato da scaricare');
                    return;
                }
                let csv = 'Giorno,Ore,Stipendio\n';
                resocontoCurrentData.giorniData.forEach(r => {
                    const g = new Date(r.giorno).toLocaleDateString('it-IT');
                    csv += `${g},${r.ore.toFixed(2)},${r.costo.toFixed(2)}\n`;
                });
                csv += `\n\nTOTALI\nOre Totali,${resocontoCurrentData.totalOre.toFixed(2)}\nStipendio Totale,${resocontoCurrentData.totalCosto.toFixed(2)}\nGiorni di Presenza,${resocontoCurrentData.giorniPresenza}\n`;
                const blob = new Blob([csv], {
                    type: 'text/csv;charset=utf-8;'
                });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `resoconto_ergo_${resocontoCurrentData.cognome}_${resocontoCurrentData.mese}.csv`;
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            function generaResoconsoPDFErgo() {
                if (!resocontoCurrentData.nome || resocontoCurrentData.giorniData.length === 0) {
                    alert('Nessun dato da scaricare');
                    return;
                }
                if (typeof html2pdf === 'undefined') {
                    const s = document.createElement('script');
                    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
                    s.onload = () => generaResoconsoPDFInternoErgo();
                    document.head.appendChild(s);
                } else {
                    generaResoconsoPDFInternoErgo();
                }
            }

            function generaResoconsoPDFInternoErgo() {
                const div = document.createElement('div');
                div.innerHTML = generaAnteprimaPDF();
                div.style.padding = '20px';
                html2pdf().set({
                    margin: 10,
                    filename: `resoconto_ergo_${resocontoCurrentData.cognome}_${resocontoCurrentData.mese}.pdf`,
                    image: {
                        type: 'jpeg',
                        quality: 0.98
                    },
                    html2canvas: {
                        scale: 2
                    },
                    jsPDF: {
                        orientation: 'portrait',
                        unit: 'mm',
                        format: 'a4'
                    }
                }).from(div).save();
            }

            const scaricaResocontoErgoBtn = document.getElementById('scaricaResocontoErgoBtn');
            if (scaricaResocontoErgoBtn) {
                scaricaResocontoErgoBtn.addEventListener('click', () => {
                    const f = document.getElementById('formatoDownloadErgo').value;
                    if (f === 'csv') generaResocontoCSV();
                    else generaResoconsoPDFErgo();
                });
            }
        });
    
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