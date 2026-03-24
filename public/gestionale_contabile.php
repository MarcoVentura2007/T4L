<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

require __DIR__ . '/../data/db_connection.php';
$conn = getDbConnection('time4all');

$resultClasse = $conn->query("SELECT classe FROM Account WHERE nome_utente = '$username'");
if ($resultClasse && $resultClasse->num_rows > 0) {
    $rowClasse = $resultClasse->fetch_assoc();
    $classe = $rowClasse['classe'];
} else {
    $classe = "";
}

if (
    !isset($_SESSION['codice_verificato']) ||
    $_SESSION['codice_verificato'] !== true ||
    !isset($_SESSION['codice_verificato_time']) ||
    (time() - $_SESSION['codice_verificato_time']) > 1800
) {
    header("Location: index.php");
    exit;
}

$sql = "SELECT id, nome, cognome, fotografia, data_nascita, disabilita, prezzo_orario, prezzo_orario_gruppo, codice_fiscale, email, telefono, allergie_intolleranze, note, Gruppo 
        FROM iscritto ORDER BY cognome ASC";
$result = $conn->query($sql);

$oggi = date('Y-m-d') . "%";
$sqlPresenze = "SELECT i.fotografia, p.id, i.nome, i.cognome, p.ingresso, p.uscita 
                FROM presenza p 
                INNER JOIN iscritto i ON p.ID_Iscritto = i.id 
                WHERE p.ingresso LIKE '$oggi'
                ORDER BY p.ingresso ASC";
$resultPresenze = $conn->query($sqlPresenze);

if ($classe !== 'Contabile') {
    header("Location: index.php");
    exit;
}

$sqlAttivitaCombo = "SELECT id, Nome FROM attivita ORDER BY Nome ASC";
$resultAttivitaCombo = $conn->query($sqlAttivitaCombo);

$sqlEducatoriAgenda = "SELECT id, nome, cognome FROM educatore ORDER BY cognome ASC, nome ASC";
$resultEducatoriAgenda = $conn->query($sqlEducatoriAgenda);

$sqlRagazzi = "SELECT id, nome, cognome FROM iscritto ORDER BY cognome ASC, nome ASC";
$resultRagazzi = $conn->query($sqlRagazzi);

$mese = date('m');
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
    <script src="https://cdn.tailwindcss.com">
        // Tooltip celle — appare solo se il testo è troncato
        (function() {
            function checkTruncation() {
                document.querySelectorAll('.cell-truncate').forEach(el => {
                    el.classList.toggle('is-truncated', el.scrollWidth > el.clientWidth);
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', checkTruncation);
            } else {
                checkTruncation();
            }
            window.addEventListener('resize', checkTruncation);

            document.addEventListener('mousemove', e => {
                const r = document.documentElement;
                r.style.setProperty('--tt-y', (e.clientY + 16) + 'px');
                r.style.setProperty('--tt-x', (e.clientX - 6) + 'px');
                r.style.setProperty('--tt-arrow-y', (e.clientY + 10) + 'px');
                r.style.setProperty('--tt-arrow-x', (e.clientX + 4) + 'px');
            });
        })();
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <style>
        @media (max-width: 768px) {
            .footer-bar {
                display: none;
            }
        }



        /* Bottoni cerchio + : solo mobile */
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
        }


        button.group svg {
            fill: none;
            stroke: #a1a1aa;
        }

        button.group:hover svg {
            fill: #27272a;
            stroke: #27272a;
        }


        /* ── Bottone mobile allineato al titolo ── */
        @media (max-width: 768px) {
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


        /* ── Navigatore mese resoconti ── */
        .mese-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .mese-nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            background: #fff;
            color: #444;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.15s, border-color 0.15s, color 0.15s, transform 0.1s;
        }

        .mese-nav-btn:hover {
            background: #640a35;
            border-color: #640a35;
            color: #fff;
            transform: scale(1.05);
        }

        .mese-nav-btn:active {
            transform: scale(0.97);
        }

        .mese-label-btn {
            height: 34px;
            padding: 0 16px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            background: #fff;
            color: #333;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            min-width: 160px;
            text-align: center;
            transition: border-color 0.15s, color 0.15s;
        }

        .mese-label-btn:hover {
            border-color: #640a35;
            color: #640a35;
        }

        .mese-label-btn.is-current {
            background: #640a35;
            color: #fff;
            border-color: #640a35;
        }

        /* Picker griglia mesi */
        .mese-picker-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 8000;
            background: rgba(0, 0, 0, 0.20);
        }

        .mese-picker-overlay.open {
            display: block;
        }

        .mese-picker {
            position: absolute;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(100, 10, 53, 0.16), 0 2px 8px rgba(0, 0, 0, 0.08);
            width: 300px;
            overflow: hidden;
            z-index: 8001;
            animation: calPop .18s ease;
        }

        .mese-picker-header {
            background: #640a35;
            color: #fff;
            padding: 14px 16px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mese-picker-year {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .mese-year-btn {
            background: rgba(255, 255, 255, 0.18);
            border: none;
            border-radius: 8px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            cursor: pointer;
            transition: background 0.15s;
        }

        .mese-year-btn:hover {
            background: rgba(255, 255, 255, 0.32);
        }

        .mese-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            padding: 14px;
        }

        .mese-option {
            padding: 9px 4px;
            text-align: center;
            border-radius: 8px;
            font-size: 0.84rem;
            font-weight: 500;
            color: #333;
            cursor: pointer;
            transition: background 0.12s, color 0.12s;
            user-select: none;
        }

        .mese-option:hover {
            background: #f4e0ea;
            color: #640a35;
        }

        .mese-option.mese-selected {
            background: #640a35;
            color: #fff;
            font-weight: 700;
        }

        .mese-option.mese-future {
            color: #ccc;
            cursor: default;
            pointer-events: none;
        }
    </style>
</head>

<body>

    <script src="js/loader.js"></script>

    <script src="js/custom-select.js"></script>

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
            <span></span><span></span><span></span>
        </div>
        <div class="dropdown" id="dropdown">
            <div class="menu-group">
                <div class="menu-main" data-target="centroMenu">
                    <img src="immagini/Logo-centrodiurno.png"> Centro Diurno
                </div>
                <div class="submenu" id="centroMenu">
                    <div class="menu-item" data-link="fogliofirme-centro.php">
                        <img src="immagini/foglio-over.png" alt=""> Foglio firme
                    </div>
                    <?php
                    if ($classe === 'Educatore') $gestionalePage = "gestionale_utenti.php";
                    elseif ($classe === 'Contabile') $gestionalePage = "gestionale_contabile.php";
                    elseif ($classe === 'Amministratore') $gestionalePage = "gestionale_amministratore.php";
                    else $gestionalePage = "#";
                    ?>
                    <div class="menu-item" data-link=<?php echo $gestionalePage; ?>>
                        <img src="immagini/gestionale-over.png" alt=""> Gestionale
                    </div>
                </div>
            </div>
            <div class="menu-group">
                <div class="menu-main" data-target="ergoMenu">
                    <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png"> Ergoterapeutica
                </div>
                <div class="submenu" id="ergoMenu">
                    <div class="menu-item" data-link="presenze-ergo.php">
                        <img src="immagini/presenze-ergo.png" alt=""> Presenze
                    </div>
                    <?php
                    if ($classe === 'Educatore') $gestionalePageErgo = "gestionale_ergo_utenti.php";
                    elseif ($classe === 'Contabile') $gestionalePageErgo = "gestionale_ergo_contabile.php";
                    elseif ($classe === 'Amministratore') $gestionalePageErgo = "gestionale_ergo_amministratore.php";
                    else $gestionalePageErgo = "#";
                    ?>
                    <div class="menu-item" data-link=<?php echo $gestionalePageErgo; ?>>
                        <img src="immagini/gestionale-ergo.png" alt=""> Gestionale
                    </div>
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
                        <li class="sidebar__item">
                            <a class="sidebar__link tab-link" href="#" data-tab="tab-agenda" data-tooltip="Agenda">
                                <span class="sidebar-icon"><img src="immagini/book.png" alt=""></span>
                                <span class="text">Agenda</span>
                            </a>
                        </li>
                        <li>
                            <hr />
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
                    </ul>
                </section>
            </nav>
        </aside>

        <main class="main-content">
            <div class="main-container">

                <!-- TAB UTENTI -->
                <div class="page-tab active" id="tab-utenti">
                    <div class="tab-header-row">
                        <div class="page-header">
                            <h1>Utenti</h1>
                            <p>Elenco iscritti registrati</p>
                        </div>
                        <div class="tab-actions">
                            <button class="btn-add btn-add--primary" id="aggiungi-utente-btn"><span class="btn-add-icon"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg></span>Aggiungi Utente</button>
                        </div>
                        <button title="Add New" id="aggiungi-utente-btn-mobile" class="group cursor-pointer outline-none hover:rotate-90 duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50px" height="50px" viewBox="0 0 24 24" class="stroke-zinc-400 fill-none group-hover:fill-zinc-800 group-active:stroke-zinc-200 group-active:fill-zinc-600 group-active:duration-0 duration-300">
                                <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke-width="1"></path>
                                <path d="M8 12H16" stroke-width="1"></path>
                                <path d="M12 16V8" stroke-width="1"></path>
                            </svg>
                        </button>
                    </div>
                    <!-- Modal Aggiungi Utente -->
                    <div class="modal-box large" id="modalAggiungiUtente">
                        <h3>Aggiungi nuovo utente</h3>
                        <form id="formAggiungiUtente">
                            <div class="edit-field"><label>Nome</label><input type="text" id="utenteNome" placeholder="Nome" required></div>
                            <div class="edit-field"><label>Cognome</label><input type="text" id="utenteCognome" placeholder="Cognome" required></div>
                            <div class="edit-field">
                                <label>Data di nascita</label>
                                <div class="birth-picker-wrap">
                                    <input type="text" id="utenteData" class="birth-picker-input" placeholder="GG/MM/AAAA" required>
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
                            <div class="edit-field"><label>Intolleranze / Allergie</label><input type="text" id="utenteIntolleranze"></div>
                            <div class="edit-field">
                                <label>Tipo di lavoro</label>
                                <select id="utenteGruppo">
                                    <option value="0">Individuale</option>
                                    <option value="1">Gruppo</option>
                                </select>
                            </div>
                            <div class="edit-field"><label>Prezzo orario (€)</label><input type="number" id="utentePrezzo" placeholder="Prezzo orario" step="0.1"></div>
                            <div class="edit-field"><label>Prezzo orario Gruppo (€)</label><input type="number" id="utentePrezzoGruppo" placeholder="Prezzo orario gruppo" step="0.1"></div>
                            <div class="edit-field"><label>Disabilità</label><textarea id="utenteDisabilita"></textarea></div>
                            <div class="edit-field"><label>Note</label><textarea id="utenteNote"></textarea></div>
                            <div class="edit-field" id="fieldFotografiaAdd">
                                <label>Fotografia</label>
                                <div class="file-inline" id="fileContainer">
                                    <input type="file" id="utenteFoto" accept="image/*" hidden>
                                    <button type="button" class="file-btn-minimal" onclick="document.getElementById('utenteFoto').click()">Scegli file</button>
                                    <div class="file-preview-container">
                                        <img id="previewFotoMini" class="preview-mini" style="display:none;">
                                        <button type="button" id="clearFileBtn" class="clear-file-btn" title="Rimuovi file">&times;</button>
                                    </div>
                                    <span class="file-name" id="nomeFileFoto">Nessun file</span>
                                </div>
                            </div>
                            <div class="edit-field" id="fieldAllegati">
                                <label>Allegati</label>
                                <div class="allegati-upload-container" id="allegatiContainer">
                                    <input type="file" id="utenteAllegati" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.xls,.xlsx" hidden>
                                    <div class="allegati-drop-zone" id="allegatiDropZone">
                                        <div class="allegati-icon"><img src="immagini/paperclip.png" alt="Graffetta"></div>
                                        <p class="allegati-text">Trascina i file qui o clicca per selezionare</p>
                                        <p class="allegati-hint">PDF, DOC, DOCX, JPG, PNG, GIF, TXT, XLS, XLSX (max 10MB)</p>
                                        <button type="button" class="file-btn-minimal" onclick="document.getElementById('utenteAllegati').click()">Seleziona file</button>
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
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '
                                <tr
                                    data-id="' . htmlspecialchars($row['id']) . '"
                                    data-nome="' . htmlspecialchars($row['nome']) . '"
                                    data-cognome="' . htmlspecialchars($row['cognome']) . '"
                                    data-nascita="' . htmlspecialchars($row['data_nascita']) . '"
                                    data-note="' . htmlspecialchars($row['note']) . '"
                                    data-cf="' . htmlspecialchars($row['codice_fiscale']) . '"
                                    data-email="' . htmlspecialchars($row['email']) . '"
                                    data-telefono="' . htmlspecialchars($row['telefono']) . '"
                                    data-disabilita="' . htmlspecialchars($row['disabilita']) . '"
                                    data-intolleranze="' . htmlspecialchars($row['allergie_intolleranze']) . '"
                                    data-prezzo="' . htmlspecialchars($row['prezzo_orario']) . '"
                                    data-prezzo-gruppo="' . htmlspecialchars($row['prezzo_orario_gruppo']) . '"
                                    data-gruppo="' . htmlspecialchars($row['Gruppo']) . '"
                                >
                                    <td><img class="user-avatar" src="' . $row['fotografia'] . '"></td>
                                    <td>' . htmlspecialchars($row['nome']) . '</td>
                                    <td>' . htmlspecialchars($row['cognome']) . '</td>
                                    <td>' . htmlspecialchars($row['data_nascita']) . '</td>
                                    <td><span class="cell-truncate cell-truncate--lg" data-tooltip="' . htmlspecialchars($row['disabilita']) . '">' . htmlspecialchars($row['disabilita']) . '</span></td>
                                    <td><span class="cell-truncate cell-truncate--md" data-tooltip="' . htmlspecialchars($row['note']) . '">' . htmlspecialchars($row['note']) . '</span></td>
                                    <td>
                                        <button class="view-btn"><img src="immagini/open-eye.png"></button>
                                        <button class="edit-btn"><img src="immagini/edit.png"></button>
                                        <button class="delete-btn"><img src="immagini/delete.png"></button>
                                    </td>
                                </tr>';
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
                            <div class="allegati-section" id="viewAllegatiSection" style="margin-top:20px;border-top:1px solid #e0e0e0;padding-top:15px;">
                                <h4 style="margin-bottom:15px;color:#2b2b2b;display:flex;align-items:center;gap:8px;">
                                    <img src="immagini/paperclip.png" alt="Allegati" style="width:20px;height:20px;"> Allegati
                                </h4>
                                <div id="viewAllegatiList" class="allegati-list-view">
                                    <p style="color:#888;font-style:italic;">Caricamento allegati...</p>
                                </div>
                            </div>
                            <div class="modal-actions">
                                <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
                            </div>
                            <div id="presenzeFormBox" style="display:none;">
                                <form id="formModificaPresenza">
                                    <div class="edit-field"><label>Ingresso</label><input type="text" id="presenzeIngresso" required></div>
                                    <div class="edit-field"><label>Uscita</label><input type="text" id="presenzeUscita" required></div>
                                    <div class="modal-actions">
                                        <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                                        <button type="submit" class="btn-primary">Salva</button>
                                    </div>
                                </form>
                            </div>
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


                <!-- CALENDARIO NASCITA - AGGIUNGI UTENTE -->
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


                <!-- CALENDARIO NASCITA - MODIFICA UTENTE -->
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

                <!-- TAB PRESENZE -->
                <div class="page-tab" id="tab-presenze">
                    <div class="tab-header-row">
                        <div class="page-header">
                            <h1>Presenze</h1>
                            <p>Elenco presenze giornaliere</p>
                        </div>
                        <div class="tab-actions">
                            <button class="btn-add btn-add--primary" id="aggiungi-presenza-btn"><span class="btn-add-icon"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg></span>Aggiungi Presenza</button>
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
                        <button class="week-nav-btn" id="prevDayBtn" title="Giorno precedente">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 18l-6-6 6-6" />
                            </svg>
                        </button>
                        <span class="presenze-day-label" id="presenzeDayLabel"></span>
                        <button class="week-nav-btn" id="nextDayBtn" title="Giorno successivo">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </button>
                        <button class="week-nav-today" id="todayPresenzeBtn" title="Vai ad oggi">Oggi</button>
                        <button class="cal-open-btn" id="calOpenBtn" title="Scegli data">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                        </button>
                    </div>
                    <!-- Calendario picker -->
                    <div class="cal-picker-overlay" id="calPickerOverlay">
                        <div class="cal-picker" id="calPicker">
                            <div class="cal-picker-header">
                                <div class="cal-month-row">
                                    <button class="cal-nav-btn" id="calPrevMonth">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M15 18l-6-6 6-6" />
                                        </svg>
                                    </button>
                                    <span class="cal-month-label" id="calMonthLabel"></span>
                                    <button class="cal-nav-btn" id="calNextMonth">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <path d="M9 18l6-6-6-6" />
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
                            <div class="cal-grid" id="calGrid"></div>
                        </div>
                    </div>
                    <!-- Modal Aggiungi Presenza -->
                    <div class="modal-box large" id="modalAggiungiPresenza">
                        <h3 class="modal-title">Aggiungi Presenza</h3>
                        <form id="formAggiungiPresenza">
                            <div class="edit-field">
                                <label>Iscritto</label>
                                <select id="apIscritto" required>
                                    <option value="">— Seleziona iscritto —</option>
                                </select>
                            </div>
                            <div class="edit-field">
                                <label>Data</label>
                                <input type="date" id="apData" required>
                            </div>
                            <div class="edit-field">
                                <label>Ora ingresso</label>
                                <input type="time" id="apIngresso" required>
                            </div>
                            <div class="edit-field">
                                <label>Ora uscita <span style="color:#888;font-weight:400;font-size:0.8rem;"></span></label>
                                <input type="time" id="apUscita">
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                                <button type="submit" class="btn-primary">Salva</button>
                            </div>
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
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB AGENDA -->
                <div class="page-tab" id="tab-agenda">
                    <div class="tab-header-row">
                        <div class="page-header">
                            <h1>Agenda</h1>
                            <p>Attività della settimana</p>
                        </div>
                        <div class="tab-actions">
                            <button class="btn-add btn-add--primary" id="creaAgendaBtn"><span class="btn-add-icon"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg></span>Crea nuova Agenda</button>
                        </div>
                        <button title="Add New" id="aggiungi-agenda-btn-mobile" class="group cursor-pointer outline-none hover:rotate-90 duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50px" height="50px" viewBox="0 0 24 24" class="stroke-zinc-400 fill-none group-hover:fill-zinc-800 group-active:stroke-zinc-200 group-active:fill-zinc-600 group-active:duration-0 duration-300">
                                <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke-width="1"></path>
                                <path d="M8 12H16" stroke-width="1"></path>
                                <path d="M12 16V8" stroke-width="1"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="agenda-container" style="margin:0 auto;">
                        <div class="agenda-week-nav">
                            <button class="week-nav-btn" id="prevWeekBtn" title="Settimana precedente">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 18l-6-6 6-6" />
                                </svg>
                            </button>
                            <span class="week-label" id="weekLabel"></span>
                            <button class="week-nav-btn" id="nextWeekBtn" title="Settimana successiva">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 18l6-6-6-6" />
                                </svg>
                            </button>
                            <button class="week-nav-today" id="todayBtn" title="Vai alla settimana corrente">Oggi</button>
                        </div>
                        <div class="header-agenda">
                            <div class="days-tabs">
                                <button class="day-tab active" data-day="0"><span class="day-name">Lunedì</span><span class="day-date" id="date-monday"></span></button>
                                <button class="day-tab" data-day="1"><span class="day-name">Martedì</span><span class="day-date" id="date-tuesday"></span></button>
                                <button class="day-tab" data-day="2"><span class="day-name">Mercoledì</span><span class="day-date" id="date-wednesday"></span></button>
                                <button class="day-tab" data-day="3"><span class="day-name">Giovedì</span><span class="day-date" id="date-thursday"></span></button>
                                <button class="day-tab" data-day="4"><span class="day-name">Venerdì</span><span class="day-date" id="date-friday"></span></button>
                            </div>
                            <button class="print-btn" id="stampaAgendaBtn">
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
                        </div>
                        <div class="agenda-content" id="agendaContent" style="touch-action:pan-y;">
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
                            <div class="edit-field"><label>Ora inizio</label><input type="time" id="agendaOraInizio" required></div>
                            <div class="edit-field"><label>Ora fine</label><input type="time" id="agendaOraFine" required></div>
                            <div class="edit-field">
                                <label>Attività</label>
                                <select id="agendaAttivita" required>
                                    <option value="">-- Seleziona attività --</option>
                                    <?php
                                    if ($resultAttivitaCombo && $resultAttivitaCombo->num_rows > 0) {
                                        while ($row = $resultAttivitaCombo->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['Nome']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="edit-field">
                                <label>Educatori</label>
                                <div class="checkbox-group" id="educatoriCheckboxes">
                                    <?php
                                    if ($resultEducatoriAgenda && $resultEducatoriAgenda->num_rows > 0) {
                                        while ($row = $resultEducatoriAgenda->fetch_assoc()) {
                                            echo '<label class="checkbox-item">';
                                            echo '<input type="checkbox" class="educatore-checkbox" value="' . htmlspecialchars($row['id']) . '"> ';
                                            echo '<span>' . htmlspecialchars($row['nome'] . ' ' . $row['cognome']) . '</span>';
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
                                    if ($resultRagazzi && $resultRagazzi->num_rows > 0) {
                                        while ($row = $resultRagazzi->fetch_assoc()) {
                                            echo '<label class="checkbox-item">';
                                            echo '<input type="checkbox" class="ragazzo-checkbox" value="' . htmlspecialchars($row['id']) . '"> ';
                                            echo '<span>' . htmlspecialchars($row['nome'] . ' ' . $row['cognome']) . '</span>';
                                            echo '<select class="ragazzo-gruppo" style="margin-left:8px;display:none;">
                                                <option value="0" selected>Individuale</option>
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

                    <div class="popup success-popup" id="successPopupAgenda">
                        <div class="success-content">
                            <div class="success-icon">
                                <svg viewBox="-2 -2 56 56">
                                    <circle class="check-circle" cx="26" cy="26" r="25" fill="none" />
                                    <path class="check-check" d="M14 27 L22 35 L38 19" fill="none" />
                                </svg>
                            </div>
                            <p class="success-text" id="success-text-agenda">Agenda creata!</p>
                        </div>
                    </div>


                    <!-- MODAL MODIFICA AGENDA -->
                    <div class="modal-box large" id="modalModificaAgenda">
                        <h3 class="modal-title">Modifica Agenda</h3>
                        <form id="formModificaAgenda">
                            <div class="edit-field">
                                <label>Ora inizio</label>
                                <input type="time" id="modAgendaOraInizio" required>
                            </div>
                            <div class="edit-field">
                                <label>Ora fine</label>
                                <input type="time" id="modAgendaOraFine" required>
                            </div>
                            <div class="edit-field">
                                <label>Attività</label>
                                <select id="modAgendaAttivita" required>
                                    <option value="">-- Seleziona attività --</option>
                                    <?php
                                    if ($resultAttivitaCombo) $resultAttivitaCombo->data_seek(0);
                                    if ($resultAttivitaCombo && $resultAttivitaCombo->num_rows > 0) {
                                        while ($row = $resultAttivitaCombo->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['Nome']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="edit-field">
                                <label>Educatori</label>
                                <div class="checkbox-group" id="modEducatoriCheckboxes">
                                    <?php
                                    if ($resultEducatoriAgenda) $resultEducatoriAgenda->data_seek(0);
                                    if ($resultEducatoriAgenda && $resultEducatoriAgenda->num_rows > 0) {
                                        while ($row = $resultEducatoriAgenda->fetch_assoc()) {
                                            echo '<label class="checkbox-item">';
                                            echo '<input type="checkbox" class="mod-educatore-checkbox" value="' . htmlspecialchars($row['id']) . '"> ';
                                            echo '<span>' . htmlspecialchars($row['nome'] . ' ' . $row['cognome']) . '</span>';
                                            echo '</label>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="edit-field">
                                <label>Ragazzi partecipanti</label>
                                <div class="checkbox-group" id="modRagazziCheckboxes">
                                    <?php
                                    if ($resultRagazzi) $resultRagazzi->data_seek(0);
                                    if ($resultRagazzi && $resultRagazzi->num_rows > 0) {
                                        while ($row = $resultRagazzi->fetch_assoc()) {
                                            echo '<label class="checkbox-item">';
                                            echo '<input type="checkbox" class="mod-ragazzo-checkbox" value="' . htmlspecialchars($row['id']) . '"> ';
                                            echo '<span>' . htmlspecialchars($row['nome'] . ' ' . $row['cognome']) . '</span>';
                                            echo '<select class="ragazzo-gruppo mod-ragazzo-gruppo" style="margin-left:8px;display:none;">
                                                <option value="0" selected>Individuale</option>
                                                <option value="1">Gruppo</option>
                                              </select>';
                                            echo '</label>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="closeModal()">Annulla</button>
                                <button type="submit" class="btn-primary">Salva</button>
                            </div>
                        </form>
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
                    <div class="tab-header-row">
                        <div class="page-header">
                            <h1>Attività</h1>
                            <p>Gestione delle attività</p>
                        </div>
                        <div class="tab-actions">
                            <button class="btn-add btn-add--primary" id="aggiungiAttivitaBtn"><span class="btn-add-icon"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg></span>Aggiungi Attività</button>
                        </div>
                        <button title="Add New" id="aggiungi-attivita-btn-mobile" class="group cursor-pointer outline-none hover:rotate-90 duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50px" height="50px" viewBox="0 0 24 24" class="stroke-zinc-400 fill-none group-hover:fill-zinc-800 group-active:stroke-zinc-200 group-active:fill-zinc-600 group-active:duration-0 duration-300">
                                <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke-width="1"></path>
                                <path d="M8 12H16" stroke-width="1"></path>
                                <path d="M12 16V8" stroke-width="1"></path>
                            </svg>
                        </button>
                    </div>
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
                                if ($resultAttivita && $resultAttivita->num_rows > 0) {
                                    while ($row = $resultAttivita->fetch_assoc()) {
                                        echo '<tr data-id="' . htmlspecialchars($row['id']) . '">
                                    <td>' . htmlspecialchars($row['Nome']) . '</td>
                                    <td><span class="cell-truncate cell-truncate--sm" data-tooltip="' . htmlspecialchars($row['Descrizione']) . '">' . htmlspecialchars($row['Descrizione']) . '</span></td>
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
                    <div class="modal-box large" id="modalAggiungiAttivita">
                        <h3 class="modal-title">Aggiungi nuova attività</h3>
                        <form id="formAttivita">
                            <div class="edit-field"><label>Nome</label><input type="text" id="attivitaNome" placeholder="Nome attività" required></div>
                            <div class="edit-field"><label>Descrizione</label><textarea id="attivitaDescrizione" placeholder="Descrizione" required></textarea></div>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                                <button class="btn-primary" id="salvaAttivita">Salva</button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-box large" id="modalModificaAttivita">
                        <h3 class="modal-title">Modifica attività</h3>
                        <form id="formModificaAttivita">
                            <input type="hidden" id="editAttivitaId">
                            <div class="edit-field"><label>Nome</label><input type="text" id="editAttivitaNome" required></div>
                            <div class="edit-field"><label>Descrizione</label><textarea id="editAttivitaDescrizione" required></textarea></div>
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
                    <div class="page-header" style="margin-bottom:20px;">
                        <h1>Resoconti</h1>
                        <p>Riepilogo mensile iscritti</p>
                    </div>
                    <div class="presenze-day-nav" id="meseNavContainer" style="margin-bottom:18px;">
                        <button class="week-nav-btn" id="mesePrevBtn" title="Mese precedente">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 18l-6-6 6-6" />
                            </svg>
                        </button>
                        <span class="presenze-day-label" id="meseLabelSpan" style="min-width:180px;text-align:center;"></span>
                        <button class="week-nav-btn" id="meseNextBtn" title="Mese successivo">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </button>
                        <button class="cal-open-btn" id="meseCalBtn" title="Scegli mese">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                        </button>
                    </div>
                    <!-- Picker mesi -->
                    <div class="mese-picker-overlay" id="mesePickerOverlay">
                        <div class="mese-picker" id="mesePicker">
                            <div class="mese-picker-header">
                                <button class="mese-year-btn" id="mesePrevYear">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                        <path d="M15 18l-6-6 6-6" />
                                    </svg>
                                </button>
                                <span class="mese-picker-year" id="mesePickerYear"></span>
                                <button class="mese-year-btn" id="meseNextYear">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                        <path d="M9 18l6-6-6-6" />
                                    </svg>
                                </button>
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
                                    <th>Costo totale (€)</th>
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
                        <div style="display:flex;justify-content:flex-start;align-items:center;">
                            <h3 class="modal-title" id="resocontoNome"></h3>
                        </div>
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
                        <div class="resoconto-calendar-wrapper">
                            <div class="calendar-section">
                                <div id="mobileCalendarContainer"></div>
                            </div>
                            <div class="activities-section">
                                <div class="mc-activities-panel" id="mc-activities-panel">
                                    <div class="mc-activities-placeholder">Seleziona un giorno per vedere le attività</div>
                                </div>
                            </div>
                        </div>
                        <div class="users-table-box" style="margin-top:30px;">
                            <h4 style="margin-bottom:15px;color:#2b2b2b;font-weight:600;">Riepilogo Attività Mensile</h4>
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
                            <button class="print-btn" id="stampaResocontoBtn" style="margin-right:auto;">
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

                    <div id="overlayAnteprimaResoconto" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.3);z-index:9999;"></div>
                    <div class="modal-anteprima-resoconto" id="modalAnteprimaResoconto" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10000;max-width:1000px;width:90%;max-height:90vh;overflow-y:auto;pointer-events:auto;background:#f9fafb;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding:20px;background:linear-gradient(135deg,#f4f6f9 0%,#ffffff 100%);border-bottom:2px solid #e5e7eb;border-radius:8px 8px 0 0;">
                            <h3 class="modal-title" style="margin:0;color:#111827;">Anteprima Resoconto</h3>
                            <label style="display:flex;align-items:center;gap:8px;font-weight:500;color:#4b5563;">
                                Salva come:
                                <select id="formatoDownload" style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:6px;background-color:white;cursor:pointer;color:#111827;font-weight:500;">
                                    <option value="pdf">PDF</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </label>
                        </div>
                        <div id="anteprimaContenuto" style="border:1px solid #e5e7eb;padding:30px;background:white;max-height:600px;overflow-y:auto;margin:20px;border-radius:8px;font-family:'Courier New',monospace;font-size:14px;line-height:1.6;white-space:pre-wrap;word-wrap:break-word;box-shadow:0 2px 8px rgba(0,0,0,0.05);"></div>
                        <div class="modal-actions" style="gap:15px;padding:20px;margin-top:0;background:#f9fafb;border-top:1px solid #e5e7eb;">
                            <button class="print-btn" id="scaricaResocontoBtn" style="flex:1;padding:12px 20px;background:white;color:#333;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-weight:500;">Scarica</button>
                            <button class="btn-secondary" id="chiudiAnteprimaBtn" style="padding:12px 20px;background:#e5e7eb;color:#111827;border:none;border-radius:6px;cursor:pointer;font-weight:500;">Chiudi</button>
                        </div>
                    </div>
                </div>

            </div><!-- fine main-container -->
        </main>
    </div><!-- fine app-layout -->

    <!-- POPUP SUCCESSO -->
    <div class="popup success-popup" id="successPopup">
        <div class="success-content">
            <div class="success-icon">
                <svg viewBox="-2 -2 56 56">
                    <circle class="check-circle" cx="26" cy="26" r="25" fill="none" />
                    <path class="check-check" d="M14 27 L22 35 L38 19" fill="none" />
                </svg>
            </div>
            <p class="success-text" id="success-text">Utente modificato!!</p>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal-box large" id="editModal">
        <h3 class="modal-title" id="modalEditTitle">Modifica utente</h3>
        <div class="profile-header" id="profileHeader" style="display:none;">
            <img id="viewAvatar-mod" class="profile-avatar">
            <div class="profile-main">
                <h3 id="viewFullname-mod"></h3>
                <span id="viewBirth-mod"></span>
            </div>
        </div>
        <div class="edit-grid" id="editContent">
            <div class="edit-field" id="fieldNome"><label>Nome</label><input type="text" id="editNome" placeholder="Nome"></div>
            <div class="edit-field" id="fieldCognome"><label>Cognome</label><input type="text" id="editCognome" placeholder="Cognome"></div>
            <div class="edit-field" id="fieldData">
                <label>Data di nascita</label>
                <div class="birth-picker-wrap">
                    <input type="text" id="editData" class="birth-picker-input" placeholder="GG/MM/AAAA">
                    <input type="hidden" id="editDataHidden">
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
            <div class="edit-field" id="fieldCF"><label>Codice Fiscale</label><input type="text" id="editCF" placeholder="Codice Fiscale"></div>
            <div class="edit-field" id="fieldEmail"><label>Email</label><input type="email" id="editEmail" placeholder="Email"></div>
            <div class="edit-field" id="fieldTelefono"><label>Telefono</label><input type="tel" id="editTelefono" placeholder="Telefono"></div>
            <div class="edit-field" id="fieldIntolleranze"><label>Intolleranze</label><input type="text" id="editIntolleranze" placeholder="Intolleranze"></div>
            <div class="edit-field" id="fieldGruppo">
                <label>Tipo di lavoro</label>
                <select id="editGruppo">
                    <option value="0">Individuale</option>
                    <option value="1">Gruppo</option>
                </select>
            </div>
            <div class="edit-field" id="fieldPrezzo"><label>Prezzo orario</label><input type="number" id="editPrezzo" placeholder="Prezzo in €" step="0.1"></div>
            <div class="edit-field" id="fieldPrezzoGruppo"><label>Prezzo orario Gruppo</label><input type="number" id="editPrezzoGruppo" placeholder="Prezzo Gruppo in €" step="0.1"></div>
            <div class="edit-field" id="fieldDisabilita"><label>Disabilità</label><textarea id="editDisabilita" placeholder="Disabilità"></textarea></div>
            <div class="edit-field" id="fieldNote"><label>Note</label><textarea id="editNote" placeholder="Note"></textarea></div>
            <div class="edit-field" id="fieldFotografia">
                <label>Fotografia</label>
                <div class="file-inline" id="editFileContainer">
                    <input type="file" id="editFoto" accept="image/*" hidden>
                    <button type="button" class="file-btn-minimal" onclick="document.getElementById('editFoto').click()">Scegli file</button>
                    <div class="file-preview-container">
                        <img id="editPreviewFotoMini" class="preview-mini" style="display:none;">
                        <button type="button" id="editClearFileBtn" class="clear-file-btn" title="Rimuovi file" style="display:none;">&times;</button>
                    </div>
                    <span class="file-name" id="editNomeFileFoto">Nessun file</span>
                </div>
            </div>
            <div class="edit-field" id="fieldAllegatiEdit">
                <label>Allegati</label>
                <div id="allegatiEsistentiContainer" style="margin-bottom:15px;">
                    <div id="allegatiEsistentiList" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
                </div>
                <div class="allegati-upload-container" id="allegatiEditContainer">
                    <input type="file" id="editAllegati" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.xls,.xlsx" hidden>
                    <div class="allegati-drop-zone" id="allegatiEditDropZone" style="padding:15px;">
                        <div class="allegati-icon"><img src="immagini/paperclip.png" alt="Graffetta" style="width:24px;height:24px;"></div>
                        <p class="allegati-text" style="font-size:13px;">Trascina i file qui o clicca per selezionare</p>
                        <p class="allegati-hint" style="font-size:11px;">PDF, DOC, DOCX, JPG, PNG, GIF, TXT, XLS, XLSX (max 10MB)</p>
                        <button type="button" class="file-btn-minimal" onclick="document.getElementById('editAllegati').click()">Seleziona file</button>
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
            <div class="edit-field" id="fieldIngresso" style="display:none;"><label>Ora ingresso</label><input type="time" id="editIngresso" placeholder="Ingresso"></div>
            <div class="edit-field" id="fieldUscita" style="display:none;"><label>Ora uscita</label><input type="time" id="editUscita" placeholder="Uscita"></div>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
            <button class="btn-primary" id="saveEdit">Salva</button>
        </div>
    </div>

    <!-- DELETE USER -->
    <div class="modal-box danger" id="deleteModal">
        <h3>Elimina utente</h3>
        <p>Questa azione è definitiva. Vuoi continuare?</p>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="closeModal()">Annulla</button>
            <button class="btn-danger" id="confirmDeleteUser">Elimina</button>
        </div>
    </div>

    <footer class="footer-bar" style="bottom:auto;">
        <div class="footer-left">© Time4All • 2026</div>
        <div class="footer-top"><a href="#top" class="footer-image"></a></div>
        <div class="footer-right"><a href="privacy_policy.php" class="hover-underline-animation">PRIVACY POLICY</a></div>
    </footer>

    <nav class="mobile-bottom-nav">
        <a href="#" class="mobile-nav-item active" data-tab="tab-utenti" onclick="switchTab('tab-utenti',this);return false;">
            <div class="mobile-nav-icon"><img src="immagini/group.png" alt="Utenti"></div>
            <span class="mobile-nav-label">Utenti</span>
        </a>
        <a href="#" class="mobile-nav-item" data-tab="tab-presenze" onclick="switchTab('tab-presenze',this);return false;">
            <div class="mobile-nav-icon"><img src="immagini/attendance.png" alt="Presenze"></div>
            <span class="mobile-nav-label">Presenze</span>
        </a>
        <a href="#" class="mobile-nav-item" data-tab="tab-agenda" onclick="switchTab('tab-agenda',this);return false;">
            <div class="mobile-nav-icon"><img src="immagini/book.png" alt="Agenda"></div>
            <span class="mobile-nav-label">Agenda</span>
        </a>
        <a href="#" class="mobile-nav-item" data-tab="tab-attivita" onclick="switchTab('tab-attivita',this);return false;">
            <div class="mobile-nav-icon"><img src="immagini/attivita.png" alt="attivita"></div>
            <span class="mobile-nav-label">Attività</span>
        </a>
        <a href="#" class="mobile-nav-item" data-tab="tab-resoconti" onclick="switchTab('tab-resoconti',this);return false;">
            <div class="mobile-nav-icon"><img src="immagini/resoconti.png" alt="resoconti"></div>
            <span class="mobile-nav-label">Resoconti</span>
        </a>
    </nav>

    <div class="modal-overlay" id="Overlay"></div>

    <script>
        // =====================================================================
        // UTILITY: YYYY-MM-DD locale
        // =====================================================================
        function getLocalDateString(date) {
            const y = date.getFullYear();
            const m = (date.getMonth() + 1).toString().padStart(2, '0');
            const d = date.getDate().toString().padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        // =====================================================================
        // UTILITY: icone file (UNICA definizione)
        // =====================================================================
        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            const icons = {
                'pdf': '<img src="immagini/pdf.png"  alt="PDF"  style="width:20px;height:20px;">',
                'doc': '<img src="immagini/docx.png" alt="DOC"  style="width:20px;height:20px;">',
                'docx': '<img src="immagini/docx.png" alt="DOCX" style="width:20px;height:20px;">',
                'jpg': '<img src="immagini/img.png"  alt="JPG"  style="width:20px;height:20px;">',
                'jpeg': '<img src="immagini/img.png"  alt="JPEG" style="width:20px;height:20px;">',
                'png': '<img src="immagini/img.png"  alt="PNG"  style="width:20px;height:20px;">',
                'gif': '<img src="immagini/img.png"  alt="GIF"  style="width:20px;height:20px;">',
                'txt': '<img src="immagini/txt.png"  alt="TXT"  style="width:20px;height:20px;">',
                'xls': '<img src="immagini/xls.png"  alt="XLS"  style="width:20px;height:20px;">',
                'xlsx': '<img src="immagini/xls.png"  alt="XLSX" style="width:20px;height:20px;">'
            };
            return icons[ext] || '<img src="immagini/paperclip.png" alt="File" style="width:20px;height:20px;">';
        }

        function getAllegatoIcon(tipo) {
            const icons = {
                'pdf': '<img src="immagini/pdf.png"      alt="PDF"  style="width:26px;height:26px;">',
                'doc': '<img src="immagini/docx.png"     alt="DOC"  style="width:26px;height:26px;">',
                'docx': '<img src="immagini/docx.png"     alt="DOCX" style="width:26px;height:26px;">',
                'image': '<img src="immagini/img.png"      alt="IMG"  style="width:26px;height:26px;">',
                'xls': '<img src="immagini/xls.png"      alt="XLS"  style="width:26px;height:26px;">',
                'xlsx': '<img src="immagini/xls.png"      alt="XLSX" style="width:26px;height:26px;">',
                'txt': '<img src="immagini/txt.png"      alt="TXT"  style="width:26px;height:26px;">',
                'file': '<img src="immagini/paperclip.png" alt="File" style="width:26px;height:26px;">'
            };
            return icons[tipo] || icons['file'];
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024,
                sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // =====================================================================
        // TABS SIDEBAR
        // =====================================================================
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

        // =====================================================================
        // HAMBURGER
        // =====================================================================
        const ham = document.getElementById("hamburger");
        const drop = document.getElementById("dropdown");
        ham.onclick = () => {
            ham.classList.toggle("active");
            drop.classList.toggle("show");
        };
        document.querySelectorAll(".menu-main").forEach(main => {
            main.addEventListener("click", () => {
                const targetMenu = document.getElementById(main.dataset.target);
                document.querySelectorAll(".submenu").forEach(menu => {
                    if (menu !== targetMenu) {
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
            };
        });

        // =====================================================================
        // USER DROPDOWN
        // =====================================================================
        const userBox = document.getElementById("userBox");
        const userDropdown = document.getElementById("userDropdown");
        userBox.addEventListener("click", e => {
            e.stopPropagation();
            userDropdown.classList.toggle("show");
        });
        document.addEventListener("click", e => {
            if (!userBox.contains(e.target)) userDropdown.classList.remove("show");
        });

        // =====================================================================
        // LOGOUT
        // =====================================================================
        const logoutBtn = document.getElementById("logoutBtn");
        const logoutOverlay = document.getElementById("logoutOverlay");
        const logoutModal = document.getElementById("logoutModal");
        const cancelLogout = document.getElementById("cancelLogout");
        const confirmLogout = document.getElementById("confirmLogout");
        logoutBtn.addEventListener("click", e => {
            e.preventDefault();
            logoutOverlay.classList.add("show");
            logoutModal.classList.add("show");
        });
        cancelLogout.onclick = () => {
            logoutOverlay.classList.remove("show");
            logoutModal.classList.remove("show");
        };
        logoutOverlay.onclick = () => {
            logoutOverlay.classList.remove("show");
            logoutModal.classList.remove("show");
        };
        confirmLogout.onclick = () => {
            window.location.href = "logout.php";
        };

        // =====================================================================
        // MODAL HELPERS
        // =====================================================================
        const Overlay = document.getElementById("Overlay");
        const viewModal = document.getElementById("viewModal");
        const editModal = document.getElementById("editModal");
        const deleteModal = document.getElementById("deleteModal");
        const modalAggiungiAttivita = document.getElementById("modalAggiungiAttivita");
        const modalCreaAgenda = document.getElementById("modalCreaAgenda"); // ← DICHIARATA QUI
        const modalAggiungiUtente = document.getElementById("modalAggiungiUtente");
        const successText = document.getElementById("success-text");
        const successPopup = document.getElementById("successPopup");

        function openModal(modal) {
            if (!modal) return;
            modal.classList.add("show");
            if (Overlay) Overlay.classList.add("show");
        }

        function closeModal() {
            if (Overlay) Overlay.classList.remove("show");
            document.querySelectorAll(".modal-box.show").forEach(el => el.classList.remove("show"));
        }

        function showSuccess(popup, overlay) {
            if (popup) popup.classList.add("show");
            if (overlay) overlay.classList.add("show");
        }

        function hideSuccess(popup, overlay) {
            if (popup) popup.classList.remove("show");
            if (overlay) {
                const anyVisible = document.querySelector(".modal-box.show,.popup.show,.logout-modal.show,.success-popup.show");
                if (!anyVisible) overlay.classList.remove("show");
            }
        }
        if (Overlay) Overlay.onclick = closeModal;

        // =====================================================================
        // VIEW UTENTE
        // =====================================================================
        document.querySelectorAll(".view-btn").forEach(btn => {
            btn.onclick = async e => {
                const row = e.target.closest("tr");
                const idIscritto = row.dataset.id;
                document.getElementById("viewAvatar").src = row.querySelector("img").src;
                document.getElementById("viewFullname").innerText = row.dataset.nome + " " + row.dataset.cognome;
                document.getElementById("viewBirth").innerText = "Nato il " + row.dataset.nascita;
                document.getElementById("viewContent").innerHTML = `
            <div class="profile-field"><label>Nome</label><span>${row.dataset.nome}</span></div>
            <div class="profile-field"><label>Cognome</label><span>${row.dataset.cognome}</span></div>
            <div class="profile-field"><label>Data di nascita</label><span>${row.dataset.nascita}</span></div>
            <div class="profile-field"><label>Codice Fiscale</label><span>${row.dataset.cf||"—"}</span></div>
            <div class="profile-field"><label>Email</label><span>${row.dataset.email||"—"}</span></div>
            <div class="profile-field"><label>Telefono</label><span>${row.dataset.telefono||"—"}</span></div>
            <div class="profile-field"><label style="font-weight:bold;">Intolleranze ⚠️</label><span style="font-weight:bold;">${row.dataset.intolleranze||"—"}</span></div>
            <div class="profile-field"><label>Tipo di lavoro</label><span>${row.dataset.gruppo==='1'||row.dataset.gruppo==='on'?'Gruppo':'Individuale'}</span></div>
            <div class="profile-field"><label>Prezzo orario</label><span>${row.dataset.prezzo||"—"} €</span></div>
            <div class="profile-field"><label>Prezzo orario Gruppo</label><span>${row.dataset.prezzoGruppo||"—"} €</span></div>
            <div class="profile-field" style="grid-column:1/-1;"><label>Disabilità</label><span>${row.dataset.disabilita||"—"}</span></div>
            <div class="profile-field" style="grid-column:1/-1;"><label>Note</label><span>${row.dataset.note||"—"}</span></div>
        `;
                await caricaAllegatiUtente(idIscritto);
                openModal(viewModal);
            };
        });

        async function caricaAllegatiUtente(idIscritto) {
            const div = document.getElementById("viewAllegatiList");
            div.innerHTML = '<p style="color:#888;font-style:italic;">Caricamento allegati...</p>';
            try {
                const res = await fetch(`api/api_get_allegati.php?id_iscritto=${idIscritto}`);
                const data = await res.json();
                if (!data.success || !data.allegati || data.allegati.length === 0) {
                    div.innerHTML = '<p style="color:#888;font-style:italic;">Nessun allegato presente</p>';
                    return;
                }
                let html = '<div class="allegati-grid">';
                data.allegati.forEach(a => {
                    const icon = getAllegatoIcon(a.tipo);
                    const dataF = new Date(a.data_upload).toLocaleDateString('it-IT');
                    html += `<div class="allegato-card" style="border:1px solid #e0e0e0;border-radius:8px;padding:12px;display:flex;align-items:center;gap:10px;background:#f9f9f9;">
                <div>${icon}</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:500;color:#2b2b2b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${a.nome_file}">${a.nome_file}</div>
                    <div style="font-size:12px;color:#888;">${dataF}</div>
                </div>
                <a href="${a.percorso}" target="_blank" title="Scarica">
                    <button class="bg-white w-6 h-6 flex justify-center items-center rounded text-black border border-black hover:bg-black hover:text-white transition-all duration-200">
                        <svg class="w-4 h-4" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" fill="none">
                            <path d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" stroke-linejoin="round" stroke-linecap="round"></path>
                        </svg>
                    </button>
                </a>
            </div>`;
                });
                div.innerHTML = html + '</div>';
            } catch (err) {
                div.innerHTML = '<p style="color:#d32f2f;font-style:italic;">Errore nel caricamento degli allegati</p>';
            }
        }

        // =====================================================================
        // PRESENZE: edit / delete
        // =====================================================================
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-presenza-btn')) {
                const btn = e.target.closest('.edit-presenza-btn');
                const row = btn.closest('tr');
                const ingresso = row.dataset.ingresso || '';
                const uscita = row.dataset.uscita || '';
                document.getElementById('profileHeader').style.display = 'flex';
                document.getElementById('viewAvatar-mod').src = row.querySelector('img').src;
                document.getElementById('viewFullname-mod').innerText = row.dataset.nome + ' ' + row.dataset.cognome;
                document.getElementById('viewBirth-mod').innerText = '';
                ['fieldNome', 'fieldCognome', 'fieldData', 'fieldCF', 'fieldEmail', 'fieldTelefono',
                    'fieldDisabilita', 'fieldIntolleranze', 'fieldPrezzo', 'fieldPrezzoGruppo',
                    'fieldNote', 'fieldFotografia', 'fieldAllegatiEdit', 'fieldGruppo'
                ].forEach(id => {
                    document.getElementById(id).style.display = 'none';
                });
                document.getElementById('fieldIngresso').style.display = 'block';
                document.getElementById('fieldUscita').style.display = 'block';
                document.getElementById('modalEditTitle').innerText = 'Modifica Presenza - ' + row.dataset.nome + ' ' + row.dataset.cognome;
                editModal.dataset.editType = 'presenza';
                editModal.dataset.presenzeId = row.dataset.id;
                editModal.dataset.presenzaData = ingresso.split(' ')[0] || ''; // data originale della presenza
                document.getElementById('editIngresso').value = (ingresso.split(' ')[1] || '').slice(0, 5);
                document.getElementById('editUscita').value = (uscita.split(' ')[1] || '').slice(0, 5);
                openModal(editModal);
            }

            if (e.target.closest('.delete-presenza-btn')) {
                const btn = e.target.closest('.delete-presenza-btn');
                const row = btn.closest('tr');
                deleteModal.querySelector('h3').innerText = 'Eliminazione Presenza - ' + row.dataset.nome + ' ' + row.dataset.cognome;
                deleteModal.dataset.deleteType = 'presenza';
                deleteModal.dataset.presenzeId = row.dataset.id;
                openModal(deleteModal);
                document.getElementById('confirmDeleteUser').onclick = () => {
                    fetch('api/api_elimina_presenza.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            id: row.dataset.id
                        })
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            deleteModal.classList.remove('show');
                            successText.innerText = 'Presenza Eliminata!!';
                            showSuccess(successPopup, Overlay);
                            setTimeout(() => {
                                closeModal();
                                hideSuccess(successPopup, Overlay);
                                location.reload();
                            }, 1800);
                        } else {
                            alert('Errore: ' + data.message);
                        }
                    });
                };
            }
        });

        // =====================================================================
        // ALLEGATI EDIT
        // =====================================================================
        const editAllegatiInput = document.getElementById("editAllegati");
        const allegatiEditDropZone = document.getElementById("allegatiEditDropZone");
        const allegatiEditList = document.getElementById("allegatiEditList");
        const allegatiEditItems = document.getElementById("allegatiEditItems");
        const clearAllEditAllegati = document.getElementById("clearAllEditAllegati");
        const allegatiEsistentiList = document.getElementById("allegatiEsistentiList");
        let selectedAllegatiEdit = [];

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
                li.innerHTML = `<div class="allegato-info">
            <span class="allegato-icon">${getFileIcon(file.name)}</span>
            <div class="allegato-details">
                <span class="allegato-name" title="${file.name}">${file.name}</span>
                <span class="allegato-size">${formatFileSize(file.size)}</span>
            </div></div>
            <button type="button" class="allegato-remove" data-index="${index}" title="Rimuovi">×</button>`;
                allegatiEditItems.appendChild(li);
            });
            document.querySelectorAll('#allegatiEditItems .allegato-remove').forEach(btn => {
                btn.onclick = function() {
                    selectedAllegatiEdit.splice(parseInt(this.dataset.index), 1);
                    updateAllegatiEditList();
                };
            });
        }

        if (editAllegatiInput) {
            editAllegatiInput.addEventListener('change', function() {
                const maxSize = 10 * 1024 * 1024;
                const valid = Array.from(this.files).filter(f => {
                    if (f.size > maxSize) {
                        alert(`File "${f.name}" troppo grande`);
                        return false;
                    }
                    return true;
                });
                selectedAllegatiEdit = [...selectedAllegatiEdit, ...valid];
                updateAllegatiEditList();
            });
        }

        if (allegatiEditDropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => allegatiEditDropZone.addEventListener(ev, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false));
            ['dragenter', 'dragover'].forEach(ev => allegatiEditDropZone.addEventListener(ev, () => allegatiEditDropZone.classList.add('dragover'), false));
            ['dragleave', 'drop'].forEach(ev => allegatiEditDropZone.addEventListener(ev, () => allegatiEditDropZone.classList.remove('dragover'), false));
            allegatiEditDropZone.addEventListener('drop', function(e) {
                const maxSize = 10 * 1024 * 1024;
                const valid = Array.from(e.dataTransfer.files).filter(f => {
                    if (f.size > maxSize) {
                        alert(`File "${f.name}" troppo grande`);
                        return false;
                    }
                    return true;
                });
                selectedAllegatiEdit = [...selectedAllegatiEdit, ...valid];
                updateAllegatiEditList();
            });
        }
        if (clearAllEditAllegati) {
            clearAllEditAllegati.onclick = () => {
                selectedAllegatiEdit = [];
                updateAllegatiEditList();
            };
        }

        async function uploadAllegatiEdit(idIscritto) {
            for (const file of selectedAllegatiEdit) {
                const fd = new FormData();
                fd.append('id_iscritto', idIscritto);
                fd.append('allegato', file);
                try {
                    await fetch('api/api_carica_allegato.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'include'
                    });
                } catch (err) {}
            }
        }

        async function caricaAllegatiEsistenti(idIscritto) {
            allegatiEsistentiList.innerHTML = '<p style="color:#888;font-style:italic;width:100%;">Caricamento...</p>';
            try {
                const res = await fetch(`api/api_get_allegati.php?id_iscritto=${idIscritto}`);
                const data = await res.json();
                if (!data.success || !data.allegati || data.allegati.length === 0) {
                    allegatiEsistentiList.innerHTML = '<p style="color:#888;font-style:italic;width:100%;">Nessun allegato presente</p>';
                    return;
                }
                let html = '';
                data.allegati.forEach(a => {
                    const icon = getAllegatoIcon(a.tipo);
                    const nomeEsc = a.nome_file.replace(/'/g, "\\'");
                    html += `<div class="allegato-esistente" style="border:1px solid #e0e0e0;border-radius:6px;padding:8px 12px;display:flex;align-items:center;gap:8px;background:#f9f9f9;font-size:13px;">
                ${icon}
                <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;" title="${a.nome_file}">${a.nome_file}</span>
                <button type="button" onclick="eliminaAllegato(${a.id},${idIscritto},'${nomeEsc}')" style="color:#d32f2f;background:none;border:none;cursor:pointer;padding:4px;display:flex;align-items:center;border-radius:4px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                </button>
            </div>`;
                });
                allegatiEsistentiList.innerHTML = html;
            } catch (err) {
                allegatiEsistentiList.innerHTML = '<p style="color:#d32f2f;font-style:italic;width:100%;">Errore nel caricamento</p>';
            }
        }

        // Modal eliminazione allegato
        let allegatoToDelete = null,
            iscrittoAllegatoToDelete = null;

        function eliminaAllegato(idAllegato, idIscritto, nomeFile) {
            allegatoToDelete = idAllegato;
            iscrittoAllegatoToDelete = idIscritto;
            let ov = document.getElementById('allegatoOverlay');
            if (!ov) {
                ov = document.createElement('div');
                ov.id = 'allegatoOverlay';
                ov.className = 'modal-overlay';
                ov.style.zIndex = '10001';
                document.body.appendChild(ov);
                ov.onclick = chiudiEliminaAllegato;
            }
            let modal = document.getElementById('modalDeleteAllegato');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalDeleteAllegato';
                modal.className = 'modal-box danger';
                modal.style.zIndex = '10002';
                document.body.appendChild(modal);
            }
            modal.innerHTML = `<h3>Elimina allegato</h3>
        <p style="color:#d32f2f;font-size:14px;"><strong>Attenzione:</strong> Questa azione non può essere annullata.<br><em>${nomeFile}</em></p>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="chiudiEliminaAllegato()">Annulla</button>
            <button class="btn-danger" id="confirmDeleteAllegatoBtn">Elimina</button>
        </div>`;
            document.getElementById('confirmDeleteAllegatoBtn').onclick = confermaEliminaAllegato;
            ov.classList.add('show');
            modal.classList.add('show');
        }

        function chiudiEliminaAllegato() {
            const modal = document.getElementById('modalDeleteAllegato');
            const ov = document.getElementById('allegatoOverlay');
            if (modal) modal.classList.remove('show');
            if (ov) ov.classList.remove('show');
            allegatoToDelete = null;
            iscrittoAllegatoToDelete = null;
        }
        async function confermaEliminaAllegato() {
            if (!allegatoToDelete) return;
            const btn = document.getElementById('confirmDeleteAllegatoBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'Eliminazione...';
            }
            try {
                const res = await fetch('api/api_elimina_allegato.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        id: allegatoToDelete
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    const reloadId = iscrittoAllegatoToDelete;
                    chiudiEliminaAllegato();
                    await caricaAllegatiEsistenti(reloadId);
                    successText.innerText = "Allegato eliminato!!";
                    showSuccess(successPopup, Overlay);
                    setTimeout(() => hideSuccess(successPopup, Overlay), 1600);
                } else {
                    alert('Errore: ' + (data.message || 'Impossibile eliminare'));
                    if (btn) {
                        btn.disabled = false;
                        btn.innerText = 'Elimina';
                    }
                }
            } catch (err) {
                alert('Errore: ' + err.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'Elimina';
                }
            }
        }

        // =====================================================================
        // EDIT FOTO
        // =====================================================================
        const editFoto = document.getElementById("editFoto");
        const editPreview = document.getElementById("editPreviewFotoMini");
        const editFileNameSpan = document.getElementById("editNomeFileFoto");
        const editClearBtn = document.getElementById("editClearFileBtn");
        if (editFoto) {
            editFoto.addEventListener("change", function() {
                if (!this.files.length) {
                    editPreview.style.display = "none";
                    editFileNameSpan.innerText = "Nessun file";
                    editClearBtn.style.display = "none";
                    return;
                }
                editPreview.src = URL.createObjectURL(this.files[0]);
                editPreview.style.display = "block";
                editFileNameSpan.innerText = this.files[0].name;
                editClearBtn.style.display = "block";
            });
            editClearBtn.addEventListener("click", () => {
                editFoto.value = "";
                editPreview.style.display = "none";
                editFileNameSpan.innerText = "Nessun file";
                editClearBtn.style.display = "none";
            });
        }

        // =====================================================================
        // EDIT UTENTE - apri modal
        // =====================================================================
        let currentEditUserId = null;
        document.querySelectorAll(".edit-btn").forEach(btn => {
            btn.onclick = async e => {
                const row = e.target.closest("tr");
                const idIscritto = row.dataset.id;
                editModal.dataset.userId = idIscritto;
                editModal.dataset.editType = 'utente';
                currentEditUserId = idIscritto;
                document.getElementById('profileHeader').style.display = 'block';
                ['fieldNome', 'fieldCognome', 'fieldData', 'fieldCF', 'fieldEmail', 'fieldTelefono',
                    'fieldDisabilita', 'fieldIntolleranze', 'fieldPrezzo', 'fieldPrezzoGruppo',
                    'fieldNote', 'fieldFotografia', 'fieldAllegatiEdit', 'fieldGruppo'
                ].forEach(id => {
                    document.getElementById(id).style.display = 'block';
                });
                document.getElementById('fieldIngresso').style.display = 'none';
                document.getElementById('fieldUscita').style.display = 'none';
                document.getElementById('modalEditTitle').innerText = 'Modifica utente';
                document.getElementById("viewAvatar-mod").src = row.querySelector("img").src;
                document.getElementById("viewFullname-mod").innerText = row.dataset.nome + " " + row.dataset.cognome;
                document.getElementById("viewBirth-mod").innerText = "Nato il " + row.dataset.nascita;
                document.getElementById("editNome").value = row.dataset.nome;
                document.getElementById("editCognome").value = row.dataset.cognome;
                const editCalBtn = document.getElementById('birthdayCalBtnEdit');
                if (editCalBtn && editCalBtn._setBirthDate) editCalBtn._setBirthDate(row.dataset.nascita);
                else {
                    document.getElementById("editData").value = row.dataset.nascita;
                    document.getElementById("editDataHidden").value = row.dataset.nascita;
                }
                document.getElementById("editCF").value = row.dataset.cf;
                document.getElementById("editEmail").value = row.dataset.email;
                document.getElementById("editTelefono").value = row.dataset.telefono;
                document.getElementById("editDisabilita").value = row.dataset.disabilita;
                document.getElementById("editIntolleranze").value = row.dataset.intolleranze;
                document.getElementById("editPrezzo").value = row.dataset.prezzo;
                document.getElementById("editPrezzoGruppo").value = row.dataset.prezzoGruppo;
                document.getElementById("editNote").value = row.dataset.note;
                document.getElementById("editGruppo").value = row.dataset.gruppo || 0;
                if (editFoto) editFoto.value = "";
                if (editPreview) editPreview.style.display = "none";
                if (editFileNameSpan) editFileNameSpan.innerText = "Nessun file";
                if (editClearBtn) editClearBtn.style.display = "none";
                selectedAllegatiEdit = [];
                updateAllegatiEditList();
                await caricaAllegatiEsistenti(idIscritto);
                openModal(editModal);
            };
        });

        // SAVE EDIT
        document.getElementById("saveEdit").onclick = () => {
            const editType = editModal.dataset.editType || 'utente';
            if (editType === 'presenza') {
                const id = editModal.dataset.presenzeId;
                const presenzaData = editModal.dataset.presenzaData || new Date().toISOString().split('T')[0];
                const ingresso = presenzaData + ' ' + document.getElementById("editIngresso").value + ':00';
                const uscita = presenzaData + ' ' + document.getElementById("editUscita").value + ':00';
                fetch('api/api_modifica_presenza.php', {
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
                    .then(r => r.json()).then(data => {
                        if (data.success) {
                            editModal.classList.remove("show");
                            if (Overlay) Overlay.classList.remove("show");
                            successText.innerText = "Presenza modificata!!";
                            showSuccess(successPopup, Overlay);
                            setTimeout(() => {
                                hideSuccess(successPopup, Overlay);
                                location.reload();
                            }, 1800);
                        } else {
                            alert("Errore: " + data.message);
                        }
                    });
            } else {
                const id = editModal.dataset.userId;
                const fotoInput = document.getElementById("editFoto");
                if (fotoInput && fotoInput.files.length > 0) {
                    const fd = new FormData();
                    fd.append("id", id);
                    fd.append("nome", document.getElementById("editNome").value);
                    fd.append("cognome", document.getElementById("editCognome").value);
                    fd.append("data_nascita", document.getElementById("editDataHidden").value || document.getElementById("editData").value);
                    fd.append("codice_fiscale", document.getElementById("editCF").value);
                    fd.append("email", document.getElementById("editEmail").value);
                    fd.append("telefono", document.getElementById("editTelefono").value);
                    fd.append("disabilita", document.getElementById("editDisabilita").value);
                    fd.append("intolleranze", document.getElementById("editIntolleranze").value);
                    fd.append("prezzo_orario", document.getElementById("editPrezzo").value);
                    fd.append("prezzo_orario_gruppo", document.getElementById("editPrezzoGruppo").value);
                    fd.append("note", document.getElementById("editNote").value);
                    fd.append("gruppo", document.getElementById("editGruppo").value);
                    fd.append("foto", fotoInput.files[0]);
                    fetch('api/api_aggiorna_utente.php', {
                        method: 'POST',
                        body: fd
                    }).then(r => r.json()).then(async data => {
                        if (data.success) {
                            if (selectedAllegatiEdit.length > 0) await uploadAllegatiEdit(id);
                            editModal.classList.remove("show");
                            if (Overlay) Overlay.classList.remove("show");
                            successText.innerText = "Utente modificato!!";
                            showSuccess(successPopup, Overlay);
                            setTimeout(() => {
                                hideSuccess(successPopup, Overlay);
                                location.reload();
                            }, 1800);
                        } else {
                            alert("Errore: " + data.message);
                        }
                    });
                } else {
                    fetch('api/api_aggiorna_utente.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            id,
                            nome: document.getElementById("editNome").value,
                            cognome: document.getElementById("editCognome").value,
                            data_nascita: document.getElementById("editDataHidden").value || document.getElementById("editData").value,
                            codice_fiscale: document.getElementById("editCF").value,
                            email: document.getElementById("editEmail").value,
                            telefono: document.getElementById("editTelefono").value,
                            disabilita: document.getElementById("editDisabilita").value,
                            intolleranze: document.getElementById("editIntolleranze").value,
                            prezzo_orario: document.getElementById("editPrezzo").value,
                            prezzo_orario_gruppo: document.getElementById("editPrezzoGruppo").value,
                            note: document.getElementById("editNote").value,
                            gruppo: document.getElementById("editGruppo").value
                        })
                    }).then(r => r.json()).then(async data => {
                        if (data.success) {
                            if (selectedAllegatiEdit.length > 0) await uploadAllegatiEdit(id);
                            editModal.classList.remove("show");
                            if (Overlay) Overlay.classList.remove("show");
                            successText.innerText = "Utente modificato!!";
                            showSuccess(successPopup, Overlay);
                            setTimeout(() => {
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

        // =====================================================================
        // DELETE UTENTE
        // =====================================================================
        document.querySelectorAll(".delete-btn").forEach(btn => {
            btn.onclick = () => {
                const row = btn.closest("tr");
                deleteModal.querySelector("h3").innerText = "Eliminazione " + row.dataset.nome + " " + row.dataset.cognome;
                deleteModal.dataset.deleteType = 'utente';
                deleteModal.dataset.userId = row.dataset.id;
                openModal(deleteModal);
                document.getElementById('confirmDeleteUser').onclick = () => {
                    fetch("api/api_elimina_utente.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-Requested-With": "XMLHttpRequest"
                            },
                            body: JSON.stringify({
                                id_iscritto: row.dataset.id
                            })
                        })
                        .then(r => r.json()).then(data => {
                            if (data.success) {
                                deleteModal.classList.remove("show");
                                successText.innerText = "Utente Eliminato!!";
                                showSuccess(successPopup, Overlay);
                                setTimeout(() => {
                                    closeModal();
                                    hideSuccess(successPopup, Overlay);
                                    location.reload();
                                }, 1800);
                            } else {
                                alert("Errore: " + (data.message || "Errore sconosciuto"));
                            }
                        });
                };
            };
        });

        // =====================================================================
        // ATTIVITA
        // =====================================================================
        const aggiungiAttivitaBtn = document.getElementById("aggiungiAttivitaBtn");
        const aggiungiAttivitaBtnMobile = document.getElementById("aggiungi-attivita-btn-mobile");

        aggiungiAttivitaBtn.onclick = () => openModal(modalAggiungiAttivita);
        aggiungiAttivitaBtnMobile.onclick = () => openModal(modalAggiungiAttivita);

        document.getElementById("salvaAttivita").onclick = function(e) {
            e.preventDefault();
            const form = document.getElementById("formAttivita");
            if (!form.reportValidity()) return;
            fetch("api/api_aggiungi_attivita.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({
                        nome: document.getElementById("attivitaNome").value.trim(),
                        descrizione: document.getElementById("attivitaDescrizione").value.trim()
                    })
                })
                .then(r => r.json()).then(data => {
                    if (data.success) {
                        modalAggiungiAttivita.classList.remove("show");
                        successText.innerText = "Attività Aggiunta!!";
                        showSuccess(successPopup, Overlay);
                        setTimeout(() => {
                            hideSuccess(successPopup, Overlay);
                            location.reload();
                        }, 1800);
                    } else {
                        alert("Errore: " + data.message);
                    }
                });
        };

        const modalModificaAttivita = document.getElementById("modalModificaAttivita");
        const modalDeleteAttivita = document.getElementById("modalDeleteAttivita");

        document.querySelectorAll(".edit-attivita-btn").forEach(btn => {
            btn.onclick = e => {
                const row = btn.closest("tr");
                document.getElementById("editAttivitaId").value = row.dataset.id;
                document.getElementById("editAttivitaNome").value = row.children[0].innerText;
                document.getElementById("editAttivitaDescrizione").value = row.children[1].innerText;
                openModal(modalModificaAttivita);
            };
        });

        document.getElementById("salvaModificaAttivita").onclick = e => {
            e.preventDefault();
            fetch("api/api_modifica_attivita.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({
                        id: document.getElementById("editAttivitaId").value,
                        nome: document.getElementById("editAttivitaNome").value.trim(),
                        descrizione: document.getElementById("editAttivitaDescrizione").value.trim()
                    })
                })
                .then(r => r.json()).then(data => {
                    if (data.success) {
                        modalModificaAttivita.classList.remove("show");
                        successText.innerText = "Attività Modificata!!";
                        showSuccess(successPopup, Overlay);
                        setTimeout(() => {
                            hideSuccess(successPopup, Overlay);
                            location.reload();
                        }, 1800);
                    } else {
                        alert("Errore: " + data.message);
                    }
                });
        };

        let rowToDeleteAttivita = null;
        document.querySelectorAll(".delete-attivita-btn").forEach(btn => {
            btn.onclick = e => {
                rowToDeleteAttivita = btn.closest("tr");
                openModal(modalDeleteAttivita);
            };
        });
        document.getElementById("confirmDeleteAttivita").onclick = () => {
            if (!rowToDeleteAttivita) return;
            fetch("api/api_elimina_attivita.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({
                        id: rowToDeleteAttivita.dataset.id
                    })
                })
                .then(r => r.json()).then(data => {
                    if (data.success) {
                        modalDeleteAttivita.classList.remove("show");
                        successText.innerText = "Attività Eliminata!!";
                        showSuccess(successPopup, Overlay);
                        setTimeout(() => {
                            hideSuccess(successPopup, Overlay);
                            location.reload();
                        }, 1800);
                    } else {
                        alert("Errore: " + data.message);
                    }
                });
        };

        // =====================================================================
        // AGGIUNGI UTENTE
        // =====================================================================
        const formAggiungiUtente = document.getElementById("formAggiungiUtente");
        const aggiungiUtenteBtn = document.getElementById("aggiungi-utente-btn");
        const aggiungiUtenteBtnMobile = document.getElementById("aggiungi-utente-btn-mobile");
        const allegatiInput = document.getElementById("utenteAllegati");
        const allegatiDropZone = document.getElementById("allegatiDropZone");
        const allegatiList = document.getElementById("allegatiList");
        const allegatiItems = document.getElementById("allegatiItems");
        const clearAllAllegati = document.getElementById("clearAllAllegati");
        let selectedAllegati = [];

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
                li.innerHTML = `<div class="allegato-info">
            <span class="allegato-icon">${getFileIcon(file.name)}</span>
            <div class="allegato-details">
                <span class="allegato-name" title="${file.name}">${file.name}</span>
                <span class="allegato-size">${formatFileSize(file.size)}</span>
                <div class="allegato-progress" id="progress-${index}" style="display:none;"><div class="allegato-progress-bar" id="progress-bar-${index}"></div></div>
                <div class="allegato-status" id="status-${index}"></div>
            </div></div>
            <button type="button" class="allegato-remove" data-index="${index}" title="Rimuovi">×</button>`;
                allegatiItems.appendChild(li);
            });
            document.querySelectorAll('.allegato-remove').forEach(btn => {
                btn.onclick = function() {
                    selectedAllegati.splice(parseInt(this.dataset.index), 1);
                    updateAllegatiList();
                };
            });
        }

        if (allegatiInput) {
            allegatiInput.addEventListener('change', function() {
                const maxSize = 10 * 1024 * 1024;
                const valid = Array.from(this.files).filter(f => {
                    if (f.size > maxSize) {
                        alert(`File "${f.name}" troppo grande`);
                        return false;
                    }
                    return true;
                });
                selectedAllegati = [...selectedAllegati, ...valid];
                updateAllegatiList();
            });
        }
        if (allegatiDropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => allegatiDropZone.addEventListener(ev, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false));
            ['dragenter', 'dragover'].forEach(ev => allegatiDropZone.addEventListener(ev, () => allegatiDropZone.classList.add('dragover'), false));
            ['dragleave', 'drop'].forEach(ev => allegatiDropZone.addEventListener(ev, () => allegatiDropZone.classList.remove('dragover'), false));
            allegatiDropZone.addEventListener('drop', function(e) {
                const maxSize = 10 * 1024 * 1024;
                const valid = Array.from(e.dataTransfer.files).filter(f => {
                    if (f.size > maxSize) {
                        alert(`File "${f.name}" troppo grande`);
                        return false;
                    }
                    return true;
                });
                selectedAllegati = [...selectedAllegati, ...valid];
                updateAllegatiList();
            });
        }
        if (clearAllAllegati) {
            clearAllAllegati.onclick = () => {
                selectedAllegati = [];
                updateAllegatiList();
            };
        }

        async function uploadAllegati(idIscritto) {
            for (let i = 0; i < selectedAllegati.length; i++) {
                const file = selectedAllegati[i];
                const progressContainer = document.getElementById(`progress-${i}`);
                const statusDiv = document.getElementById(`status-${i}`);
                if (progressContainer) progressContainer.style.display = 'block';
                if (statusDiv) statusDiv.textContent = 'Caricamento...';
                const fd = new FormData();
                fd.append('id_iscritto', idIscritto);
                fd.append('allegato', file);
                try {
                    const res = await fetch('api/api_carica_allegato.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'include'
                    });
                    const data = await res.json();
                    if (data.success && statusDiv) {
                        statusDiv.textContent = '✓ Caricato';
                        statusDiv.classList.add('complete');
                    } else if (statusDiv) {
                        statusDiv.textContent = '✗ Errore: ' + (data.message || '');
                        statusDiv.classList.add('error');
                    }
                } catch (err) {
                    if (statusDiv) {
                        statusDiv.textContent = '✗ Errore di rete';
                        statusDiv.classList.add('error');
                    }
                }
            }
        }

        if (aggiungiUtenteBtn) aggiungiUtenteBtn.onclick = () => {
            const addCalBtn = document.getElementById('birthdayCalBtnAdd');
            if (addCalBtn && addCalBtn._setBirthDate) addCalBtn._setBirthDate(null);
            openModal(modalAggiungiUtente);
        };
        if (aggiungiUtenteBtnMobile) aggiungiUtenteBtnMobile.onclick = () => {
            const addCalBtn = document.getElementById('birthdayCalBtnAdd');
            if (addCalBtn && addCalBtn._setBirthDate) addCalBtn._setBirthDate(null);
            openModal(modalAggiungiUtente);
        };

        formAggiungiUtente.onsubmit = async function(e) {
            e.preventDefault();
            const dataNascita = document.getElementById("utenteDataHidden").value;
            if (!dataNascita) {
                alert('Seleziona la data di nascita dal calendario.');
                return;
            }
            const fd = new FormData();
            fd.append("nome", document.getElementById("utenteNome").value.trim());
            fd.append("cognome", document.getElementById("utenteCognome").value.trim());
            fd.append("data_nascita", dataNascita);
            fd.append("codice_fiscale", document.getElementById("utenteCF").value.trim());
            fd.append("email", document.getElementById("utenteEmail").value.trim());
            fd.append("telefono", document.getElementById("utenteTelefono").value.trim());
            fd.append("disabilita", document.getElementById("utenteDisabilita").value.trim());
            fd.append("intolleranze", document.getElementById("utenteIntolleranze").value.trim());
            fd.append("prezzo_orario", parseFloat(document.getElementById("utentePrezzo").value) || 0);
            fd.append("prezzo_orario_gruppo", parseFloat(document.getElementById("utentePrezzoGruppo").value) || 0);
            fd.append("note", document.getElementById("utenteNote").value.trim());
            fd.append("gruppo", document.getElementById("utenteGruppo").value);
            const fotoInput = document.getElementById("utenteFoto");
            if (fotoInput.files.length > 0) fd.append("foto", fotoInput.files[0]);
            const res = await fetch("api/api_aggiungi_utente.php", {
                method: "POST",
                body: fd
            });
            const data = await res.json();
            if (data.success) {
                await uploadAllegati(data.id);
                modalAggiungiUtente.classList.remove("show");
                successText.innerText = "Utente Aggiunto!!";
                showSuccess(successPopup, Overlay);
                setTimeout(() => {
                    hideSuccess(successPopup, Overlay);
                    if (Overlay) Overlay.classList.remove("show");
                    location.reload();
                }, 1800);
            } else {
                alert("Errore: " + data.message);
            }
        };

        const utenteFoto = document.getElementById("utenteFoto");
        const preview = document.getElementById("previewFotoMini");
        const fileNameSpan = document.getElementById("nomeFileFoto");
        const clearBtn = document.getElementById("clearFileBtn");
        utenteFoto.addEventListener("change", function() {
            if (!this.files.length) {
                preview.style.display = "none";
                fileNameSpan.innerText = "Nessun file";
                clearBtn.style.display = "none";
                return;
            }
            preview.src = URL.createObjectURL(this.files[0]);
            preview.style.display = "block";
            fileNameSpan.innerText = this.files[0].name;
            clearBtn.style.display = "block";
        });
        clearBtn.addEventListener("click", () => {
            utenteFoto.value = "";
            preview.style.display = "none";
            fileNameSpan.innerText = "Nessun file";
            clearBtn.style.display = "none";
        });

        // =====================================================================
        // AGENDA
        // =====================================================================
        let agendaData = [],
            agendaWeekStart = null,
            selectedDayIndex = 0,
            currentMonday = null;

        // ── offset in settimane rispetto alla settimana corrente (0 = oggi)
        let weekOffset = parseInt(localStorage.getItem('weekOffset') || '0');

        function getMondayOfWeek(offset) {
            const today = new Date();
            const day = today.getDay(); // 0=dom, 1=lun...
            const diff = (day === 0 ? -6 : 1 - day); // giorni a ritroso fino a lunedì
            const monday = new Date(today);
            monday.setDate(today.getDate() + diff + offset * 7);
            monday.setHours(0, 0, 0, 0);
            return monday;
        }

        function updateWeekLabel() {
            const monday = getMondayOfWeek(weekOffset);
            const friday = new Date(monday);
            friday.setDate(monday.getDate() + 4);
            const fmt = d => d.toLocaleDateString('it-IT', {
                day: '2-digit',
                month: 'short'
            });
            document.getElementById('weekLabel').innerText = `${fmt(monday)} – ${fmt(friday)}`;

            // evidenzia "Oggi" se siamo sulla settimana corrente
            const todayBtn = document.getElementById('todayBtn');
            if (todayBtn) todayBtn.classList.toggle('is-current-week', weekOffset === 0);
        }

        function calculateWeekDates() {
            const monday = getMondayOfWeek(weekOffset);
            currentMonday = monday;
            const labels = ['date-monday', 'date-tuesday', 'date-wednesday', 'date-thursday', 'date-friday'];
            for (let i = 0; i < 5; i++) {
                const d = new Date(monday);
                d.setDate(monday.getDate() + i);
                const lbl = document.getElementById(labels[i]);
                if (lbl) lbl.innerText = d.toLocaleDateString('it-IT', {
                    day: '2-digit',
                    month: '2-digit'
                });
            }
            updateWeekLabel();
        }

        function loadAgenda() {
            const div = document.getElementById('agendaContent');
            div.innerHTML = '<div class="loading">Caricamento attività...</div>';

            const monday = getMondayOfWeek(weekOffset);
            const mondayStr = getLocalDateString(monday);

            fetch(`api/api_get_agenda.php?week=${mondayStr}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        agendaData = data.data || [];
                        agendaWeekStart = mondayStr;
                        calculateWeekDates();
                        let def = new Date().getDay() - 1;
                        if (def < 0 || def > 4) def = 0;
                        // se siamo sulla settimana corrente ripristina il giorno salvato, altrimenti lunedì
                        const saved = parseInt(localStorage.getItem("selectedDayIndex"));
                        displayAgenda(!isNaN(saved) && saved >= 0 && saved <= 4 ? saved : def);
                    } else {
                        div.innerHTML = '<div class="error-message">Errore: ' + (data.error || 'Sconosciuto') + '</div>';
                    }
                })
                .catch(() => {
                    div.innerHTML = '<div class="error-message">Errore nel caricamento dell\'agenda</div>';
                });
        }

        function displayAgenda(dayIndex) {
            selectedDayIndex = dayIndex;
            localStorage.setItem("selectedDayIndex", dayIndex);
            document.querySelectorAll('.day-tab').forEach((tab, i) => tab.classList.toggle('active', i === dayIndex));
            document.querySelector('.days-tabs').style.setProperty('--active-index', dayIndex);
            if (window.innerWidth <= 768) {
                const t = document.querySelector('.day-tab.active');
                if (t) t.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }
            const div = document.getElementById('agendaContent');
            if (!agendaData || agendaData.length === 0) {
                div.innerHTML = '<div class="no-activities">Nessuna attività disponibile</div>';
                return;
            }
            const selDate = new Date(currentMonday);
            selDate.setDate(currentMonday.getDate() + dayIndex);
            const selStr = getLocalDateString(selDate);
            const dayActs = agendaData.filter(a => a.data === selStr);
            if (dayActs.length === 0) {
                div.innerHTML = '<div class="no-activities">Nessuna attività per questo giorno</div>';
                return;
            }
            dayActs.sort((a, b) => a.ora_inizio.localeCompare(b.ora_inizio));
            let html = '<div class="activities-list">';
            dayActs.forEach(att => {
                const inizio = att.ora_inizio.substring(0, 5),
                    fine = att.ora_fine.substring(0, 5);
                const edTxt = Array.from(new Map(att.educatori.map(e => [e.id, e])).values()).map(e => `${e.nome} ${e.cognome}`).join(', ');
                const ragFotos = Array.from(new Map(att.ragazzi.map(r => [r.id, r])).values()).map(r => `<div class="ragazzo-item"><img src="${r.fotografia}" class="ragazzo-avatar"><span class="ragazzo-cognome">${r.cognome}</span><span style="display:block;font-size:0.85em;color:#666;">${r.gruppo==1?'(Gruppo)':'(Individuale)'}</span></div>`).join('') || '—';
                html += `<div class="activity-card" data-id="${att.id}">
            <div class="activity-header"><h3>${att.attivita_nome}</h3><span class="activity-time"><img class="resoconti-icon" src="immagini/rescheduling.png" style="width:22px;height:22px;margin-right:8px;"> ${inizio} - ${fine}</span></div>
            <div class="activity-description">${att.descrizione}</div>
            <div class="activity-participants">
                <div class="participant-group"><label>Educatori:</label><span>${edTxt}</span></div>
                <div class="participant-group"><label>Ragazzi:</label><span class="ragazzi-photos">${ragFotos}</span></div>
            </div>
            <div class="activity-actions"><button class="edit-agenda-btn" data-id="${att.id}" title="Modifica"><img src="immagini/edit.png" alt="Modifica"></button><button class="delete-agenda-btn" data-id="${att.id}" title="Elimina"><img src="immagini/delete.png" alt="Elimina"></button></div>
        </div>`;
            });
            div.innerHTML = html + '</div>';
        }

        document.querySelectorAll('.day-tab').forEach((tab, i) => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                displayAgenda(i);
                if (window.innerWidth <= 768) tab.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            });
        });

        // ── Bottoni navigazione settimane
        document.getElementById('prevWeekBtn').onclick = () => {
            weekOffset--;
            localStorage.setItem('weekOffset', weekOffset);
            loadAgenda();
        };
        document.getElementById('nextWeekBtn').onclick = () => {
            weekOffset++;
            localStorage.setItem('weekOffset', weekOffset);
            loadAgenda();
        };
        document.getElementById('todayBtn').onclick = () => {
            if (weekOffset !== 0) {
                weekOffset = 0;
                localStorage.setItem('weekOffset', weekOffset);
                loadAgenda();
            }
        };

        document.querySelectorAll('.ragazzo-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const sel = this.closest('label').querySelector('.ragazzo-gruppo');
                if (sel) sel.style.display = this.checked ? 'inline-block' : 'none';
            });
        });


        // ── Modifica Agenda ──────────────────────────────────────────────────
        const modalModificaAgenda = document.getElementById('modalModificaAgenda');
        const formModificaAgenda = document.getElementById('formModificaAgenda');
        let agendaToEdit = null;

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.edit-agenda-btn')) return;
            const btn = e.target.closest('.edit-agenda-btn');
            const cardId = btn.dataset.id;
            const att = agendaData.find(a => String(a.id) === String(cardId));
            if (!att) return;
            agendaToEdit = att;

            document.getElementById('modAgendaOraInizio').value = att.ora_inizio.substring(0, 5);
            document.getElementById('modAgendaOraFine').value = att.ora_fine.substring(0, 5);

            const selAtt = document.getElementById('modAgendaAttivita');
            selAtt.value = att.attivita_id;

            document.querySelectorAll('.mod-educatore-checkbox').forEach(cb => {
                cb.checked = att.educatori.some(e => String(e.id) === cb.value);
            });

            document.querySelectorAll('.mod-ragazzo-checkbox').forEach(cb => {
                const rag = att.ragazzi.find(r => String(r.id) === cb.value);
                cb.checked = !!rag;
                const sel = cb.closest('label').querySelector('.mod-ragazzo-gruppo');
                if (sel) {
                    sel.style.display = cb.checked ? 'inline-block' : 'none';
                    if (rag) sel.value = rag.gruppo == 1 ? '1' : '0';
                }
            });

            openModal(modalModificaAgenda);
        });

        document.querySelectorAll('.mod-ragazzo-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const sel = this.closest('label').querySelector('.mod-ragazzo-gruppo');
                if (sel) sel.style.display = this.checked ? 'inline-block' : 'none';
            });
        });

        if (formModificaAgenda) {
            formModificaAgenda.onsubmit = function(e) {
                e.preventDefault();
                if (!agendaToEdit) return;

                const ora_inizio = document.getElementById('modAgendaOraInizio').value;
                const ora_fine = document.getElementById('modAgendaOraFine').value;
                const id_attivita = parseInt(document.getElementById('modAgendaAttivita').value);

                const educatori = Array.from(
                    document.querySelectorAll('.mod-educatore-checkbox:checked')
                ).map(cb => parseInt(cb.value)).filter(v => !isNaN(v) && v > 0);

                const ragazziCbs = document.querySelectorAll('.mod-ragazzo-checkbox:checked');
                const ragazzi = Array.from(ragazziCbs)
                    .map(cb => parseInt(cb.value)).filter(v => !isNaN(v) && v > 0);

                const ragazzi_gruppo = {};
                ragazziCbs.forEach(cb => {
                    const id = parseInt(cb.value);
                    if (isNaN(id) || id <= 0) return;
                    const sel = cb.closest('label').querySelector('.mod-ragazzo-gruppo');
                    ragazzi_gruppo[id] = sel && parseInt(sel.value) === 1 ? 1 : 0;
                });

                if (!ora_inizio || !ora_fine || !id_attivita || educatori.length === 0 || ragazzi.length === 0) {
                    alert('Completa tutti i campi obbligatori.');
                    return;
                }

                fetch('api/api_modifica_agenda.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            data: agendaToEdit.data,
                            orig_attivita: agendaToEdit.attivita_id,
                            orig_ora_inizio: agendaToEdit.ora_inizio.substring(0, 5),
                            orig_ora_fine: agendaToEdit.ora_fine.substring(0, 5),
                            ora_inizio,
                            ora_fine,
                            id_attivita,
                            educatori,
                            ragazzi,
                            ragazzi_gruppo
                        })
                    })
                    .then(r => r.json()).then(data => {
                        if (data.success) {
                            closeModal();
                            successText.innerText = 'Agenda modificata!';
                            showSuccess(successPopup, Overlay);
                            setTimeout(() => {
                                hideSuccess(successPopup, Overlay);
                                loadAgenda();
                            }, 1800);
                        } else {
                            alert('Errore: ' + (data.message || 'Sconosciuto'));
                        }
                    }).catch(() => alert('Errore di rete'));
            };
        }

        // Delete Agenda
        const modalDeleteAgenda = document.getElementById("modalDeleteAgenda");
        let agendaToDelete = null;
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-agenda-btn')) {
                agendaToDelete = e.target.closest('.delete-agenda-btn').dataset.id;
                openModal(modalDeleteAgenda);
            }
        });
        document.getElementById("confirmDeleteAgenda").onclick = () => {
            if (!agendaToDelete) return;
            fetch("api/api_elimina_agenda.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({
                        id: agendaToDelete
                    })
                })
                .then(r => r.json()).then(data => {
                    if (data.success) {
                        modalDeleteAgenda.classList.remove("show");
                        if (Overlay) Overlay.classList.remove("show");
                        successText.innerText = "Agenda Eliminata!!";
                        showSuccess(successPopup, Overlay);
                        setTimeout(() => {
                            hideSuccess(successPopup, Overlay);
                            loadAgenda();
                        }, 1800);
                    } else {
                        alert("Errore: " + data.message);
                    }
                });
        };

        // Crea Agenda
        const creaAgendaBtn = document.getElementById("creaAgendaBtn");
        const formCreaAgenda = document.getElementById("formCreaAgenda");
        const successPopupAgenda = document.getElementById("successPopupAgenda");
        const aggiungiAgendaBtnMobile = document.getElementById("aggiungi-agenda-btn-mobile");

        function popolaSelectDate() {
            const monday = getMondayOfWeek(weekOffset);
            const sel = document.getElementById("agendaData");
            const giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì'];
            while (sel.options.length > 1) sel.remove(1);
            for (let i = 0; i < 5; i++) {
                const d = new Date(monday);
                d.setDate(monday.getDate() + i);
                const opt = document.createElement("option");
                opt.value = getLocalDateString(d);
                opt.text = `${giorni[i]} ${d.toLocaleDateString('it-IT',{day:'2-digit',month:'2-digit',year:'numeric'})}`;
                sel.appendChild(opt);
            }
            if (selectedDayIndex >= 0 && selectedDayIndex < 5) sel.selectedIndex = selectedDayIndex + 1;
        }

        if (creaAgendaBtn) creaAgendaBtn.onclick = () => {
            popolaSelectDate();
            openModal(modalCreaAgenda);
        };
        if (aggiungiAgendaBtnMobile) aggiungiAgendaBtnMobile.onclick = () => {
            popolaSelectDate();
            openModal(modalCreaAgenda);
        };

        if (formCreaAgenda) {
            formCreaAgenda.onsubmit = function(e) {
                e.preventDefault();
                const data = document.getElementById("agendaData").value;
                const ora_inizio = document.getElementById("agendaOraInizio").value;
                const ora_fine = document.getElementById("agendaOraFine").value;
                const id_attivita = document.getElementById("agendaAttivita").value;
                const educatori = Array.from(document.querySelectorAll(".educatore-checkbox:checked")).map(cb => parseInt(cb.value, 10)).filter(id => !isNaN(id) && id > 0);
                const ragazziCbs = document.querySelectorAll(".ragazzo-checkbox:checked");
                const ragazzi = Array.from(ragazziCbs).map(cb => parseInt(cb.value, 10)).filter(id => !isNaN(id) && id > 0);
                const ragazzi_gruppo = {};
                Array.from(ragazziCbs).forEach(cb => {
                    const id = parseInt(cb.value, 10);
                    if (isNaN(id) || id <= 0) return;
                    const s = cb.closest('label').querySelector('.ragazzo-gruppo');
                    ragazzi_gruppo[id] = s && parseInt(s.value, 10) === 1 ? 1 : 0;
                });
                if (!data || !ora_inizio || !ora_fine || !id_attivita || educatori.length === 0) {
                    alert("Completa tutti i campi obbligatori (data, orari, attività, almeno un educatore)");
                    return;
                }
                if (ragazzi.length === 0) {
                    alert("Seleziona almeno un ragazzo!");
                    return;
                }
                fetch("api/api_aggiungi_agenda.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        body: JSON.stringify({
                            data,
                            ora_inizio,
                            ora_fine,
                            id_attivita: parseInt(id_attivita),
                            educatori,
                            ragazzi,
                            ragazzi_gruppo
                        })
                    })
                    .then(r => r.json()).then(data => {
                        if (data.success) {
                            closeModal();
                            showSuccess(successPopupAgenda, Overlay);
                            setTimeout(() => {
                                hideSuccess(successPopupAgenda, Overlay);
                                formCreaAgenda.reset();
                                document.querySelectorAll('.educatore-checkbox, .ragazzo-checkbox').forEach(cb => cb.checked = false);
                                loadAgenda(); // ricarica l'agenda restando sulla settimana corrente navigata
                            }, 2000);
                        } else {
                            alert("Errore: " + (data.error || data.message));
                        }
                    }).catch(err => {
                        alert("Errore di comunicazione: " + err.message);
                    });
            };
        }

        // Stampa Agenda
        const stampaAgendaBtn = document.getElementById("stampaAgendaBtn");
        if (stampaAgendaBtn) {
            stampaAgendaBtn.onclick = () => {
                const pw = window.open('', '_blank', 'width=800,height=600');
                const timeSlots = [{
                        start: '08:00',
                        end: '10:00',
                        bg: '#e6f7ff'
                    }, {
                        start: '10:00',
                        end: '12:00',
                        bg: '#fff7e6'
                    },
                    {
                        start: '12:00',
                        end: '14:00',
                        bg: '#f6ffed'
                    }, {
                        start: '14:00',
                        end: '16:00',
                        bg: '#fff2f0'
                    },
                    {
                        start: '16:00',
                        end: '18:00',
                        bg: '#f9f0ff'
                    }
                ];
                const giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì'];
                pw.document.write(`<html><head><title>Agenda Settimanale</title><style>@page{size:A4 landscape;}body{font-family:Arial,sans-serif;margin:3px;width:297mm;}table{width:100%;border-collapse:collapse;font-size:14px;table-layout:fixed;}th,td{border:1px solid #000;padding:8px;text-align:left;vertical-align:top;width:20%;word-wrap:break-word;-webkit-print-color-adjust:exact;print-color-adjust:exact;}th{background:#f0f0f0;font-weight:bold;}.activity{margin-bottom:6px;}
        /* Navigazione giorni — Presenze */
        .presenze-day-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 0 12px;
        }
        .presenze-day-nav .week-nav-btn {
            display: flex; align-items: center; justify-content: center;
            width: 34px; height: 34px;
            border: 1.5px solid #e0e0e0; border-radius: 8px;
            background: #fff; color: #444; cursor: pointer; flex-shrink: 0;
            transition: background 0.15s, border-color 0.15s, color 0.15s, transform 0.1s;
        }
        .presenze-day-nav .week-nav-btn:hover { background: #640a35; border-color: #640a35; color: #fff; transform: scale(1.05); }
        .presenze-day-nav .week-nav-btn:active { transform: scale(0.97); }
        .presenze-day-label {
            font-size: 0.85rem; font-weight: 600; color: #444;
            min-width: 200px; text-align: center; letter-spacing: 0.01em;
        }
        .presenze-day-nav .week-nav-today {
            font-size: 0.75rem; font-weight: 600; padding: 6px 12px;
            border: 1.5px solid #640a35; border-radius: 8px;
            background: transparent; color: #640a35; cursor: pointer;
            white-space: nowrap; transition: background 0.15s, color 0.15s;
        }
        .presenze-day-nav .week-nav-today:hover { background: #640a35; color: #fff; }
        .presenze-day-nav .week-nav-today.is-today { background: #640a35; color: #fff; border-color: #640a35; }
        @media (max-width: 768px) {
            .presenze-day-label { min-width: 140px; font-size: 0.78rem; }
            .presenze-day-nav .week-nav-today { font-size: 0.7rem; padding: 5px 9px; }
        }

    </style></head><body><h2 style="text-align:center;">Agenda Settimanale - ${new Date().toLocaleDateString('it-IT')}</h2><table><thead><tr><th>Lunedì</th><th>Martedì</th><th>Mercoledì</th><th>Giovedì</th><th>Venerdì</th></tr></thead><tbody>`);
                timeSlots.forEach(slot => {
                    pw.document.write('<tr>');
                    for (let idx = 0; idx < 5; idx++) {
                        let monday2;
                        if (agendaWeekStart) {
                            monday2 = getMondayOfWeek(weekOffset);
                        } else {
                            monday2 = new Date();
                            monday2.setDate(monday2.getDate() - monday2.getDay() + 1);
                        }
                        const d2 = new Date(monday2);
                        d2.setDate(monday2.getDate() + idx);
                        const dStr = getLocalDateString(d2);
                        const acts = agendaData.filter(a => a.data === dStr && a.ora_inizio >= slot.start && a.ora_inizio < slot.end);
                        pw.document.write(`<td style="background-color:${slot.bg};">`);
                        if (acts.length === 0) {
                            pw.document.write('Nessuna attività');
                        } else {
                            acts.sort((a, b) => a.ora_inizio.localeCompare(b.ora_inizio)).forEach(att => {
                                const ed = Array.from(new Map(att.educatori.map(e => [e.id, e])).values()).map(e => `${e.nome} ${e.cognome}`).join(', ');
                                const rag = Array.from(new Map(att.ragazzi.map(r => [r.id, r])).values()).map(r => r.cognome).join(', ');
                                pw.document.write(`<div class="activity"><strong>${att.attivita_nome} (${ed})</strong><br><span>${rag}</span></div>`);
                            });
                        }
                        pw.document.write('</td>');
                    }
                    pw.document.write('</tr>');
                });
                pw.document.write('</tbody></table></body></html>');
                pw.document.close();
                pw.print();
            };
        }


        // =====================================================================
        // RESOCONTI
        // =====================================================================
        document.addEventListener("DOMContentLoaded", () => {
            const resocontiMeseFiltro = document.getElementById("resocontiMeseFiltro");
            const resocontiMensiliBody = document.getElementById("resocontiMensiliBody");
            const modalResoconto = document.getElementById("modalResocontoGiorni");
            const bodyResoconto = document.getElementById("resocontoGiorniBody");
            const titoloResoconto = document.getElementById("resocontoNome");
            let currentIscritto = null,
                mobileCalendarInstance = null;
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

            // ── Navigatore mese resoconti ────────────────────────────
            (function() {
                const MESI = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
                    'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
                ];
                const TODAY = new Date();
                let curYear = TODAY.getFullYear();
                let curMonth = TODAY.getMonth();
                let pickerYear = curYear;

                const labelSpan = document.getElementById('meseLabelSpan');
                const prevBtn = document.getElementById('mesePrevBtn');
                const nextBtn = document.getElementById('meseNextBtn');
                const calBtn = document.getElementById('meseCalBtn');
                const overlay = document.getElementById('mesePickerOverlay');
                const picker = document.getElementById('mesePicker');
                const yearLbl = document.getElementById('mesePickerYear');
                const grid = document.getElementById('meseGrid');
                const prevYearBtn = document.getElementById('mesePrevYear');
                const nextYearBtn = document.getElementById('meseNextYear');
                const hidden = document.getElementById('resocontiMeseFiltro');

                if (!labelSpan) return;

                function pad(n) {
                    return String(n).padStart(2, '0');
                }

                function getMeseStr(y, m) {
                    return `${y}-${pad(m+1)}`;
                }

                function updateLabel() {
                    labelSpan.textContent = MESI[curMonth] + ' ' + curYear;
                    const atMax = curYear > TODAY.getFullYear() ||
                        (curYear === TODAY.getFullYear() && curMonth >= TODAY.getMonth());
                    nextBtn.disabled = atMax;
                    nextBtn.style.opacity = atMax ? '0.4' : '1';
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
                    nextYearBtn.style.opacity = pickerYear >= TODAY.getFullYear() ? '0.4' : '1';
                    grid.innerHTML = '';
                    MESI.forEach((nome, i) => {
                        const isFuture = pickerYear > TODAY.getFullYear() ||
                            (pickerYear === TODAY.getFullYear() && i > TODAY.getMonth());
                        const isSelected = pickerYear === curYear && i === curMonth;
                        const el = document.createElement('div');
                        el.className = 'mese-option' +
                            (isFuture ? ' mese-future' : '') +
                            (isSelected ? ' mese-selected' : '');
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

            document.addEventListener("click", e => {
                const btn = e.target.closest(".resoconto-btn,.calendario-btn");
                if (!btn) return;
                currentIscritto = btn.dataset.id;
                resocontoCurrentData.nome = btn.dataset.nome || "";
                resocontoCurrentData.cognome = btn.dataset.cognome || "";
                if (titoloResoconto) titoloResoconto.textContent = "Resoconto - " + (resocontoCurrentData.cognome + " " + resocontoCurrentData.nome).trim();
                if (bodyResoconto) bodyResoconto.innerHTML = `<tr><td colspan="4">Caricamento...</td></tr>`;
                if (modalResoconto && typeof openModal === "function") openModal(modalResoconto);
                caricaResocontoGiorni();
            });

            function caricaResocontiMensili(mese) {
                if (!resocontiMensiliBody) return;
                resocontiMensiliBody.innerHTML = `<tr><td colspan="6">Caricamento...</td></tr>`;
                fetch("api/api_resoconto_mensile.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            mese
                        })
                    })
                    .then(r => r.json()).then(json => {
                        resocontiMensiliBody.innerHTML = "";
                        if (!json.success || json.data.length === 0) {
                            resocontiMensiliBody.innerHTML = `<tr><td colspan="6">Nessun dato disponibile</td></tr>`;
                            return;
                        }
                        json.data.forEach(r => {
                            const ore = parseFloat(r.ore_totali).toFixed(2);
                            const costo = parseFloat(r.costo ?? r.ore_totali * r.Prezzo_Orario).toFixed(2);
                            resocontiMensiliBody.innerHTML += `<tr>
                    <td><img src="${r.Fotografia}" class="user-avatar"></td>
                    <td>${r.Nome}</td><td>${r.Cognome}</td>
                    <td>${ore}</td><td>${costo} €</td>
                    <td><button class="btn-icon calendario-btn" data-id="${r.id}" data-nome="${r.Nome}" data-cognome="${r.Cognome}"><img src="immagini/calendario.png" alt="Calendario"></button></td>
                </tr>`;
                        });
                    }).catch(err => {
                        resocontiMensiliBody.innerHTML = `<tr><td colspan="6">Errore nel caricamento</td></tr>`;
                    });
            }

            let currentModalMese = null;

            function caricaResocontoGiorni(meseForzato = null) {
                if (!currentIscritto) return;
                const meseDaUsare = meseForzato || resocontiMeseFiltro.value;
                currentModalMese = meseDaUsare;
                fetch("api/api_resoconto_giornaliero.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            id: currentIscritto,
                            mese: meseDaUsare
                        })
                    })
                    .then(r => r.json()).then(json => {
                        if (!bodyResoconto) return;
                        bodyResoconto.innerHTML = "";
                        const attivitaMensiliBody = document.getElementById("attivitaMensiliBody");
                        if (attivitaMensiliBody) attivitaMensiliBody.innerHTML = "";
                        let totalOre = 0,
                            totalCosto = 0,
                            giorniPresenza = 0;
                        const summaryOre = document.getElementById('summaryOre');
                        const summaryCosto = document.getElementById('summaryCosto');
                        const summaryGiorni = document.getElementById('summaryGiorni');
                        if (!json.success || json.data.length === 0) {
                            bodyResoconto.innerHTML = `<tr><td colspan="4">Nessun dato</td></tr>`;
                            if (attivitaMensiliBody) attivitaMensiliBody.innerHTML = `<tr><td colspan="2">Nessuna attività</td></tr>`;
                            if (summaryOre) summaryOre.textContent = '0.00';
                            if (summaryCosto) summaryCosto.textContent = '0.00 €';
                            if (summaryGiorni) summaryGiorni.textContent = '0';
                            if (mobileCalendarInstance) {
                                const [anno, mese] = meseDaUsare.split('-');
                                const nd = new Date(parseInt(anno), parseInt(mese) - 1, 1);
                                if (mobileCalendarInstance.currentDate.getFullYear() !== nd.getFullYear() || mobileCalendarInstance.currentDate.getMonth() !== nd.getMonth()) mobileCalendarInstance.setDate(nd);
                                mobileCalendarInstance.setActivitiesData({});
                            } else {
                                const cc = document.getElementById("mobileCalendarContainer");
                                if (cc && window.MobileCalendar) {
                                    const [anno, mese] = meseDaUsare.split('-');
                                    mobileCalendarInstance = new MobileCalendar('mobileCalendarContainer', {
                                        selectedDate: new Date(parseInt(anno), parseInt(mese) - 1, 1),
                                        activitiesData: {},
                                        activitiesPanel: '#mc-activities-panel',
                                        onMonthChange: function(nd) {
                                            caricaResocontoGiorni(`${nd.getFullYear()}-${String(nd.getMonth()+1).padStart(2,'0')}`);
                                        }
                                    });
                                }
                            }
                            return;
                        }
                        const daysMap = new Map(),
                            attivitaMap = new Map(),
                            activitiesData = {};
                        json.data.forEach(r => {
                            const giorno = new Date(r.giorno).getDate(),
                                dateStr = r.giorno;
                            if (!daysMap.has(giorno)) {
                                daysMap.set(giorno, {
                                    attivita: [],
                                    ore: 0,
                                    costo: 0
                                });
                                giorniPresenza++;
                            }
                            const day = daysMap.get(giorno);
                            r.attivita.forEach(a => {
                                day.attivita.push(a);
                                attivitaMap.set(a.Nome, (attivitaMap.get(a.Nome) || 0) + a.ore);
                            });
                            day.ore += r.ore;
                            day.costo += r.costo;
                            totalOre += r.ore;
                            totalCosto += r.costo;
                            bodyResoconto.innerHTML += `<tr><td>${giorno}</td><td>${r.attivita.map(a=>`${a.Nome} (${a.ore.toFixed(2)}h, ${a.costo.toFixed(2)}€)`).join('<br>')}</td><td>${r.ore.toFixed(2)}</td><td>${r.costo.toFixed(2)} €</td></tr>`;
                            if (!activitiesData[dateStr]) activitiesData[dateStr] = [];
                            if (r.attivita && r.attivita.length > 0) {
                                r.attivita.forEach(a => activitiesData[dateStr].push({
                                    nome: a.Nome,
                                    descrizione: `${a.ore.toFixed(2)} ore - ${a.costo.toFixed(2)}€`,
                                    ora_inizio: '',
                                    ora_fine: '',
                                    educatori: ''
                                }));
                            } else {
                                activitiesData[dateStr].push({
                                    nome: 'Presente',
                                    descrizione: `${r.ore.toFixed(2)} ore - ${r.costo.toFixed(2)}€`,
                                    ora_inizio: '',
                                    ora_fine: '',
                                    educatori: ''
                                });
                            }
                        });
                        Array.from(attivitaMap.entries()).sort((a, b) => b[1] - a[1]).forEach(([nome, ore]) => {
                            if (attivitaMensiliBody) attivitaMensiliBody.innerHTML += `<tr><td>${nome}</td><td>${ore.toFixed(2)} ore</td></tr>`;
                        });
                        if (summaryOre) summaryOre.textContent = totalOre.toFixed(2);
                        if (summaryCosto) summaryCosto.textContent = totalCosto.toFixed(2) + ' €';
                        if (summaryGiorni) summaryGiorni.textContent = giorniPresenza;
                        resocontoCurrentData = {
                            ...resocontoCurrentData,
                            mese: meseDaUsare,
                            giorniData: json.data,
                            attivitaMensili: Array.from(attivitaMap.entries()),
                            totalOre,
                            totalCosto,
                            giorniPresenza
                        };
                        const cc = document.getElementById("mobileCalendarContainer");
                        if (cc && window.MobileCalendar) {
                            const [anno, mese] = meseDaUsare.split('-');
                            if (mobileCalendarInstance) {
                                const nd = new Date(parseInt(anno), parseInt(mese) - 1, 1);
                                if (mobileCalendarInstance.currentDate.getFullYear() !== nd.getFullYear() || mobileCalendarInstance.currentDate.getMonth() !== nd.getMonth()) mobileCalendarInstance.setDate(nd);
                                mobileCalendarInstance.setActivitiesData(activitiesData);
                            } else {
                                mobileCalendarInstance = new MobileCalendar('mobileCalendarContainer', {
                                    selectedDate: new Date(parseInt(anno), parseInt(mese) - 1, 1),
                                    activitiesData,
                                    activitiesPanel: '#mc-activities-panel',
                                    onMonthChange: function(nd) {
                                        caricaResocontoGiorni(`${nd.getFullYear()}-${String(nd.getMonth()+1).padStart(2,'0')}`);
                                    }
                                });
                            }
                        }
                    }).catch(err => {
                        if (bodyResoconto) bodyResoconto.innerHTML = `<tr><td colspan="4">Errore nel caricamento</td></tr>`;
                    });
            }

            function generaAnteprimaPDF() {
                let h = '<div style="font-family:Arial,sans-serif;padding:20px;background:white;color:#333;line-height:1.6;">';
                h += `<h2 style="text-align:center;border-bottom:2px solid #333;padding-bottom:10px;">RESOCONTO MENSILE</h2>`;
                h += `<p style="text-align:center;font-size:14px;"><strong>${resocontoCurrentData.cognome} ${resocontoCurrentData.nome}</strong></p>`;
                h += `<p style="text-align:center;font-size:13px;">Mese: ${resocontoCurrentData.mese}</p>`;
                h += `<p style="text-align:center;font-size:12px;color:#666;">Data Stampa: ${new Date().toLocaleString('it-IT')}</p>`;
                h += '<h3 style="margin-top:20px;border-bottom:1px solid #ddd;padding-bottom:5px;font-size:14px;">DETTAGLIO GIORNALIERO</h3>';
                h += '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12px;">';
                h += '<tr style="background:#f0f0f0;"><th style="padding:8px;border:1px solid #ddd;">Giorno</th><th style="padding:8px;border:1px solid #ddd;">Attività</th><th style="padding:8px;border:1px solid #ddd;text-align:center;">Ore</th><th style="padding:8px;border:1px solid #ddd;text-align:right;">Costo</th></tr>';
                resocontoCurrentData.giorniData.forEach(r => {
                    const g = new Date(r.giorno).toLocaleDateString('it-IT');
                    if (r.attivita && r.attivita.length > 0) {
                        r.attivita.forEach((a, idx) => {
                            h += `<tr style="border:1px solid #ddd;"><td style="padding:8px;border:1px solid #ddd;">${idx===0?g:''}</td><td style="padding:8px;border:1px solid #ddd;">${a.Nome}</td><td style="padding:8px;text-align:center;border:1px solid #ddd;">${a.ore.toFixed(2)}h</td><td style="padding:8px;text-align:right;border:1px solid #ddd;">${a.costo.toFixed(2)}€</td></tr>`;
                        });
                    } else {
                        h += `<tr style="border:1px solid #ddd;"><td style="padding:8px;border:1px solid #ddd;">${g}</td><td style="padding:8px;border:1px solid #ddd;">Presenza</td><td style="padding:8px;text-align:center;border:1px solid #ddd;">${r.ore.toFixed(2)}h</td><td style="padding:8px;text-align:right;border:1px solid #ddd;">${r.costo.toFixed(2)}€</td></tr>`;
                    }
                });
                h += '</table>';
                h += '<h3 style="margin-top:20px;border-bottom:1px solid #ddd;padding-bottom:5px;font-size:14px;">RIEPILOGO ATTIVITÀ</h3>';
                h += '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12px;">';
                resocontoCurrentData.attivitaMensili.forEach(([nome, ore]) => {
                    h += `<tr style="border:1px solid #ddd;"><td style="padding:8px;border:1px solid #ddd;">${nome}</td><td style="padding:8px;text-align:right;border:1px solid #ddd;">${ore.toFixed(2)}h</td></tr>`;
                });
                h += '</table>';
                h += `<h3 style="margin-top:20px;border-bottom:1px solid #ddd;padding-bottom:5px;font-size:14px;">TOTALI</h3>`;
                h += `<div style="font-size:13px;"><p><strong>Ore Totali:</strong> ${resocontoCurrentData.totalOre.toFixed(2)}h</p><p><strong>Costo Totale:</strong> ${resocontoCurrentData.totalCosto.toFixed(2)}€</p><p><strong>Giorni di Presenza:</strong> ${resocontoCurrentData.giorniPresenza}</p></div>`;
                h += `<div style="margin-top:40px;border-top:1px solid #333;padding-top:15px;"><p style="font-size:12px;">Firma: ___________________________</p><p style="margin-top:20px;font-size:12px;color:#999;">Data: ${new Date().toLocaleDateString('it-IT')}</p></div>`;
                h += '</div>';
                return h;
            }

            function generaAnteprimaCSV() {
                let h = '<div style="font-family:monospace;font-size:12px;padding:10px;background:white;"><table style="border-collapse:collapse;width:100%;">';
                h += '<tr style="background:#f0f0f0;"><td style="padding:8px;border:1px solid #ccc;font-weight:bold;">Giorno</td><td style="padding:8px;border:1px solid #ccc;font-weight:bold;">Attività</td><td style="padding:8px;border:1px solid #ccc;text-align:center;font-weight:bold;">Ore</td><td style="padding:8px;border:1px solid #ccc;text-align:right;font-weight:bold;">Costo</td></tr>';
                resocontoCurrentData.giorniData.forEach(r => {
                    const g = new Date(r.giorno).toLocaleDateString('it-IT');
                    if (r.attivita && r.attivita.length > 0) {
                        r.attivita.forEach(a => {
                            h += `<tr><td style="padding:6px;border:1px solid #ddd;">${g}</td><td style="padding:6px;border:1px solid #ddd;">${a.Nome}</td><td style="padding:6px;border:1px solid #ddd;text-align:center;">${a.ore.toFixed(2)}</td><td style="padding:6px;border:1px solid #ddd;text-align:right;">${a.costo.toFixed(2)}</td></tr>`;
                        });
                    } else {
                        h += `<tr><td style="padding:6px;border:1px solid #ddd;">${g}</td><td style="padding:6px;border:1px solid #ddd;">Presenza</td><td style="padding:6px;border:1px solid #ddd;text-align:center;">${r.ore.toFixed(2)}</td><td style="padding:6px;border:1px solid #ddd;text-align:right;">${r.costo.toFixed(2)}</td></tr>`;
                    }
                });
                h += `</table><div style="margin-top:20px;padding:15px;background:#f9f9f9;border:1px solid #ddd;"><p style="font-weight:bold;">TOTALI</p><p>Ore: ${resocontoCurrentData.totalOre.toFixed(2)}</p><p>Costo: ${resocontoCurrentData.totalCosto.toFixed(2)}</p><p>Giorni: ${resocontoCurrentData.giorniPresenza}</p></div></div>`;
                return h;
            }

            const stampaResocontoBtn = document.getElementById('stampaResocontoBtn');
            if (stampaResocontoBtn) {
                stampaResocontoBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    document.getElementById('formatoDownload').value = 'pdf';
                    document.getElementById('anteprimaContenuto').innerHTML = generaAnteprimaPDF();
                    document.getElementById('modalAnteprimaResoconto').style.display = 'block';
                    document.getElementById('overlayAnteprimaResoconto').style.display = 'block';
                });
            }

            window.chiudiModalAnteprima = function() {
                document.getElementById('modalAnteprimaResoconto').style.display = 'none';
                document.getElementById('overlayAnteprimaResoconto').style.display = 'none';
            };
            document.getElementById('chiudiAnteprimaBtn')?.addEventListener('click', () => window.chiudiModalAnteprima());
            document.getElementById('overlayAnteprimaResoconto')?.addEventListener('click', () => window.chiudiModalAnteprima());
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && document.getElementById('modalAnteprimaResoconto').style.display === 'block') window.chiudiModalAnteprima();
            });

            const formatoDownload = document.getElementById('formatoDownload');
            if (formatoDownload) {
                formatoDownload.addEventListener('change', () => {
                    const f = formatoDownload.value;
                    document.getElementById('anteprimaContenuto').innerHTML = f === 'pdf' ? generaAnteprimaPDF() : generaAnteprimaCSV();
                });
            }

            function generaResocontoCSV() {
                if (!resocontoCurrentData.nome || resocontoCurrentData.giorniData.length === 0) {
                    alert('Nessun dato da scaricare');
                    return;
                }
                let csv = 'Giorno,Attività,Ore,Costo\n';
                resocontoCurrentData.giorniData.forEach(r => {
                    const g = new Date(r.giorno).toLocaleDateString('it-IT');
                    if (r.attivita && r.attivita.length > 0) {
                        r.attivita.forEach(a => {
                            csv += `${g},${a.Nome},${a.ore.toFixed(2)},${a.costo.toFixed(2)}\n`;
                        });
                    } else {
                        csv += `${g},Presenza,${r.ore.toFixed(2)},${r.costo.toFixed(2)}\n`;
                    }
                });
                csv += '\n\nRiepilogo Attività,Ore Totali\n';
                resocontoCurrentData.attivitaMensili.forEach(([nome, ore]) => {
                    csv += `${nome},${ore.toFixed(2)}\n`;
                });
                csv += `\n\nTOTALI\nOre Totali,${resocontoCurrentData.totalOre.toFixed(2)}\nCosto Totale,${resocontoCurrentData.totalCosto.toFixed(2)}\nGiorni di Presenza,${resocontoCurrentData.giorniPresenza}\n`;
                const blob = new Blob([csv], {
                    type: 'text/csv;charset=utf-8;'
                });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `resoconto_${resocontoCurrentData.cognome}_${resocontoCurrentData.mese}.csv`;
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            function generaResoconsoPDF() {
                if (!resocontoCurrentData.nome || resocontoCurrentData.giorniData.length === 0) {
                    alert('Nessun dato da scaricare');
                    return;
                }
                if (typeof html2pdf === 'undefined') {
                    const s = document.createElement('script');
                    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
                    s.onload = () => generaResoconsoPDFInternal();
                    document.head.appendChild(s);
                } else {
                    generaResoconsoPDFInternal();
                }
            }

            function generaResoconsoPDFInternal() {
                const div = document.createElement('div');
                div.innerHTML = generaAnteprimaPDF();
                div.style.padding = '20px';
                html2pdf().set({
                    margin: 10,
                    filename: `resoconto_${resocontoCurrentData.cognome}_${resocontoCurrentData.mese}.pdf`,
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

            const scaricaResocontoBtn = document.getElementById('scaricaResocontoBtn');
            if (scaricaResocontoBtn) {
                scaricaResocontoBtn.addEventListener('click', () => {
                    const f = document.getElementById('formatoDownload').value;
                    if (f === 'csv') generaResocontoCSV();
                    else generaResoconsoPDF();
                });
            }
        });

        // =====================================================================
        // FLATPICKR
        // =====================================================================
        // =====================================================================

        // =====================================================================
        // CALENDARIO PICKER PRESENZE
        // =====================================================================
        (function() {
            const overlay = document.getElementById('calPickerOverlay');
            const picker = document.getElementById('calPicker');
            const openBtn = document.getElementById('calOpenBtn');
            const grid = document.getElementById('calGrid');
            const monthLbl = document.getElementById('calMonthLabel');
            const prevBtn = document.getElementById('calPrevMonth');
            const nextBtn = document.getElementById('calNextMonth');

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
                const year = calViewDate.getFullYear();
                const month = calViewDate.getMonth();
                const selectedStr = getSelectedStr();

                monthLbl.textContent = new Date(year, month, 1)
                    .toLocaleDateString('it-IT', {
                        month: 'long',
                        year: 'numeric'
                    });

                nextBtn.disabled = (year > TODAY.getFullYear() ||
                    (year === TODAY.getFullYear() && month >= TODAY.getMonth()));
                nextBtn.style.opacity = nextBtn.disabled ? '.4' : '1';

                const firstDay = new Date(year, month, 1).getDay();
                const offset = (firstDay === 0) ? 6 : firstDay - 1;
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
                    const isToday = dateStr === toDateStr(TODAY);
                    const isSelected = dateStr === selectedStr;

                    const el = document.createElement('div');
                    el.className = 'cal-day' +
                        (isFuture ? ' cal-future' : '') +
                        (isToday ? ' cal-today' : '') +
                        (isSelected ? ' cal-selected' : '');
                    el.textContent = d;

                    if (!isFuture) {
                        el.addEventListener('click', () => {
                            const diff = Math.round((dateObj - TODAY) / 86400000);
                            presenzeOffset = diff;
                            localStorage.setItem('presenzeOffset', presenzeOffset);
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
                const pickerW = 300;
                let left = rect.left;
                if (left + pickerW > window.innerWidth - 8) left = window.innerWidth - pickerW - 8;
                picker.style.top = (rect.bottom + window.scrollY + 6) + 'px';
                picker.style.left = left + 'px';
            }

            function closeCalendar() {
                overlay.classList.remove('open');
            }

            openBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                overlay.classList.contains('open') ? closeCalendar() : openCalendar();
            });
            overlay.addEventListener('click', (e) => {
                if (!picker.contains(e.target)) closeCalendar();
            });
            prevBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                calViewDate.setMonth(calViewDate.getMonth() - 1);
                renderCalendar();
            });
            nextBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (!nextBtn.disabled) {
                    calViewDate.setMonth(calViewDate.getMonth() + 1);
                    renderCalendar();
                }
            });
        })();


        // =====================================================================
        // AGGIUNGI PRESENZA
        // =====================================================================
        (function() {
            const btnAP = document.getElementById('aggiungi-presenza-btn');
            const btnAPM = document.getElementById('aggiungi-presenza-btn-mobile');
            const modalAP = document.getElementById('modalAggiungiPresenza');
            const formAP = document.getElementById('formAggiungiPresenza');
            const selAP = document.getElementById('apIscritto');
            const inputData = document.getElementById('apData');
            if (!btnAP || !modalAP) return;
            if (!btnAPM || !modalAP) return;

            function popolaIscritti() {
                selAP.innerHTML = '<option value="">— Seleziona iscritto —</option>';
                document.querySelectorAll('#tab-utenti .users-table tbody tr[data-id]').forEach(row => {
                    const opt = document.createElement('option');
                    opt.value = row.dataset.id;
                    opt.textContent = (row.dataset.cognome || '') + ' ' + (row.dataset.nome || '');
                    selAP.appendChild(opt);
                });
                // Usa la data già selezionata nel navigatore presenze
                inputData.value = getPresenzaDateString(presenzeOffset);
                // Blocca solo le date future
                const today = new Date();
                const yy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                inputData.max = `${yy}-${mm}-${dd}`;
                // Campo data in sola lettura (cambia solo con il navigatore)
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
                const id_iscritto = selAP.value;
                const data = document.getElementById('apData').value;
                const ora_ingresso = document.getElementById('apIngresso').value;
                const ora_uscita = document.getElementById('apUscita').value;
                if (!id_iscritto || !data || !ora_ingresso) {
                    alert('Compila i campi obbligatori: iscritto, data e ora ingresso.');
                    return;
                }
                fetch('api/api_aggiungi_presenza.php', {
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
                            const st = document.getElementById('success-text');
                            if (st) st.innerText = 'Presenza aggiunta!!';
                            const sp = document.getElementById('successPopup');
                            if (sp) showSuccess(sp, Overlay);
                            setTimeout(() => {
                                if (sp) hideSuccess(sp, Overlay);
                                const presDate = getPresenzaDateString(presenzeOffset);
                                if (presDate === data) loadPresenze();
                            }, 1800);
                        } else {
                            alert('Errore: ' + res.message);
                        }
                    }).catch(() => alert('Errore di rete'));
            });
        })();

        // SIDEBAR STATE
        // =====================================================================
        const checkboxInput = document.getElementById('checkbox-input');
        if (checkboxInput) {
            const s = localStorage.getItem('sidebarOpen');
            if (s !== null) checkboxInput.checked = s === 'true';
            checkboxInput.addEventListener('change', () => localStorage.setItem('sidebarOpen', checkboxInput.checked));
        }

        // =====================================================================
        // SCROLL LOCK
        // =====================================================================
        function syncBodyScrollLock() {
            document.body.classList.toggle("popup-open", Boolean(document.querySelector(".modal-box.show,.popup.show,.logout-modal.show,.success-popup.show,.modal-overlay.show,.logout-overlay.show")));
        }
        new MutationObserver(() => syncBodyScrollLock()).observe(document.body, {
            subtree: true,
            attributes: true,
            attributeFilter: ["class"]
        });
        syncBodyScrollLock();

        // =====================================================================
        // MOBILE TAB SWITCH + RESTORE
        // =====================================================================
        function switchTab(tabId, navItem) {
            document.querySelectorAll('.mobile-nav-item').forEach(i => i.classList.remove('active'));
            navItem.classList.add('active');
            document.querySelectorAll('.tab-link').forEach(l => {
                l.classList.remove('active');
                if (l.dataset.tab === tabId) l.classList.add('active');
            });
            document.querySelectorAll('.page-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            localStorage.setItem("activeTab", tabId);
        }

        // =====================================================================
        // PRESENZE — navigazione per data
        // =====================================================================
        let presenzeOffset = parseInt(localStorage.getItem('presenzeOffset') || '0');

        function getPresenzaDateString(offset) {
            const d = new Date();
            d.setDate(d.getDate() + offset);
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
            const todayBtn = document.getElementById('todayPresenzeBtn');
            if (todayBtn) todayBtn.classList.toggle('is-today', presenzeOffset === 0);
            const nextBtn = document.getElementById('nextDayBtn');
            if (nextBtn) nextBtn.disabled = presenzeOffset >= 0;
        }

        function loadPresenze() {
            const dataStr = getPresenzaDateString(presenzeOffset);
            const tbody = document.querySelector('#presenzeTable tbody');
            if (!tbody) return;
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#888;">Caricamento...</td></tr>';
            updatePresenzeDayLabel();
            fetch(`api/api_get_presenze.php?data=${dataStr}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        tbody.innerHTML = '<tr><td colspan="6">Errore nel caricamento</td></tr>';
                        return;
                    }
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#888;">Nessuna presenza registrata per questo giorno.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.data.map(row => `<tr data-id="${row.id}" data-nome="${row.nome}" data-cognome="${row.cognome}" data-ingresso="${row.ingresso}" data-uscita="${row.uscita || ''}"><td><img class="user-avatar" src="${row.fotografia}"></td><td>${row.nome}</td><td>${row.cognome}</td><td>${row.ingresso}</td><td>${row.uscita || '—'}</td><td><button class="edit-presenza-btn" data-id="${row.id}"><img src="immagini/edit.png" alt="Modifica"></button><button class="delete-presenza-btn" data-id="${row.id}"><img src="immagini/delete.png" alt="Elimina"></button></td></tr>`).join('');
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="6">Errore di rete</td></tr>';
                });
        }

        document.getElementById('prevDayBtn').onclick = () => {
            presenzeOffset--;
            localStorage.setItem('presenzeOffset', presenzeOffset);
            loadPresenze();
        };
        document.getElementById('nextDayBtn').onclick = () => {
            if (presenzeOffset < 0) {
                presenzeOffset++;
                localStorage.setItem('presenzeOffset', presenzeOffset);
                loadPresenze();
            }
        };
        document.getElementById('todayPresenzeBtn').onclick = () => {
            if (presenzeOffset !== 0) {
                presenzeOffset = 0;
                localStorage.setItem('presenzeOffset', presenzeOffset);
                loadPresenze();
            }
        };

        window.addEventListener("DOMContentLoaded", () => {
            loadPresenze();
            // Carica agenda
            loadAgenda();

            // Ripristina tab
            const savedTab = localStorage.getItem("activeTab");
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
    </script>

    <script>
        // =====================================================================
        // CALENDARIO NASCITA — factory riutilizzabile
        // =====================================================================
        (function() {
            /**
             * Crea un calendar picker per la data di nascita.
             * @param {object} cfg
             *   cfg.openBtnId   – id del bottone che apre il calendario
             *   cfg.overlayId   – id dell'overlay
             *   cfg.pickerId    – id del contenitore del picker
             *   cfg.gridId      – id della griglia giorni
             *   cfg.monthLblId  – id dello span mese/anno
             *   cfg.prevMonthId, nextMonthId
             *   cfg.prevYearId, nextYearId
             *   cfg.prevDecadeId, nextDecadeId
             *   cfg.yearSelectId
             *   cfg.displayInputId  – input text visibile (GG/MM/AAAA)
             *   cfg.hiddenInputId   – input hidden (YYYY-MM-DD) usato per il submit
             */
            function makeBirthdayCal(cfg) {
                const overlay = document.getElementById(cfg.overlayId);
                const picker = document.getElementById(cfg.pickerId);
                const openBtn = document.getElementById(cfg.openBtnId);
                const displayInput = document.getElementById(cfg.displayInputId);
                const hiddenInput = document.getElementById(cfg.hiddenInputId);

                if (!overlay || !openBtn || !picker) return;

                // Usa querySelector dentro il picker per evitare conflitti di ID
                const grid = document.getElementById(cfg.gridId);
                const monthLbl = document.getElementById(cfg.monthLblId);
                const prevMonthBtn = document.getElementById(cfg.prevMonthId);
                const nextMonthBtn = document.getElementById(cfg.nextMonthId);
                const prevYearBtn = picker.querySelector('.birth-year-nav:nth-child(1)');
                const prevDecBtn = picker.querySelector('.birth-year-nav:nth-child(2)');
                const nextDecBtn = picker.querySelector('.birth-year-nav:nth-child(4)');
                const nextYearBtn = picker.querySelector('.birth-year-nav:nth-child(5)');
                const yearSelect = picker.querySelector('.birth-year-select');

                const TODAY = new Date();
                TODAY.setHours(0, 0, 0, 0);
                const MIN_YEAR = 1900;

                let calViewDate = new Date(TODAY.getFullYear() - 30, 0, 1);
                let selectedDate = null;

                function pad(n) {
                    return String(n).padStart(2, '0');
                }

                function toDateStr(d) {
                    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
                }

                function toDisplayStr(d) {
                    return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()}`;
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

                    monthLbl.textContent = new Date(year, month, 1)
                        .toLocaleDateString('it-IT', {
                            month: 'long'
                        })
                        .replace(/^./, c => c.toUpperCase());

                    buildYearSelect();

                    // disabilita navigazione futura
                    const isMaxMonth = (year === TODAY.getFullYear() && month >= TODAY.getMonth());
                    nextMonthBtn.disabled = isMaxMonth;
                    nextMonthBtn.style.opacity = isMaxMonth ? '.3' : '1';
                    nextYearBtn.disabled = (year >= TODAY.getFullYear());
                    nextYearBtn.style.opacity = nextYearBtn.disabled ? '.3' : '1';
                    nextDecBtn.disabled = (year + 10 > TODAY.getFullYear());
                    nextDecBtn.style.opacity = nextDecBtn.disabled ? '.3' : '1';

                    prevMonthBtn.disabled = (year === MIN_YEAR && month === 0);
                    prevYearBtn.disabled = (year <= MIN_YEAR);
                    prevDecBtn.disabled = (year - 10 < MIN_YEAR);

                    const firstDay = new Date(year, month, 1).getDay();
                    const offset = (firstDay === 0) ? 6 : firstDay - 1;
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
                        const isSelected = (selStr === dateStr);

                        const el = document.createElement('div');
                        el.className = 'cal-day' +
                            (isFuture ? ' cal-future' : '') +
                            (isSelected ? ' cal-selected' : '');
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
                    // se c'è già un valore selezionato, vai a quel mese
                    if (selectedDate) {
                        calViewDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
                    }
                    renderCalendar();
                    overlay.classList.add('open');

                    const rect = openBtn.getBoundingClientRect();
                    const pickerW = 300;
                    // Cerca di aprire sotto il bottone, ma verifica spazio
                    let left = rect.left;
                    if (left + pickerW > window.innerWidth - 8) left = window.innerWidth - pickerW - 8;
                    if (left < 8) left = 8;

                    // Verifica se c'è spazio sotto, altrimenti apri sopra
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const pickerH = 380;
                    let top;
                    if (spaceBelow >= pickerH || spaceBelow > window.innerHeight - rect.top) {
                        top = rect.bottom + window.scrollY + 6;
                    } else {
                        top = rect.top + window.scrollY - pickerH - 6;
                    }
                    picker.style.top = top + 'px';
                    picker.style.left = left + 'px';
                }

                function closeCal() {
                    overlay.classList.remove('open');
                }

                // Apre/chiude
                openBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    overlay.classList.contains('open') ? closeCal() : openCal();
                });
                overlay.addEventListener('click', e => {
                    if (!picker.contains(e.target)) closeCal();
                });

                // Navigazione mese
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

                // Navigazione anno
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

                // Navigazione decennio
                prevDecBtn.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    const ny = Math.max(MIN_YEAR, calViewDate.getFullYear() - 10);
                    calViewDate.setFullYear(ny);
                    renderCalendar();
                });
                nextDecBtn.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    const ny = Math.min(TODAY.getFullYear(), calViewDate.getFullYear() + 10);
                    calViewDate.setFullYear(ny);
                    renderCalendar();
                });

                // Selezione anno da dropdown
                yearSelect.addEventListener('change', e => {
                    e.stopPropagation();
                    calViewDate.setFullYear(parseInt(yearSelect.value));
                    renderCalendar();
                });

                // Esponi metodo per pre-caricare un valore (es. in modifica utente)
                openBtn._setBirthDate = function(isoStr) {
                    if (!isoStr) {
                        selectedDate = null;
                        displayInput.value = '';
                        hiddenInput.value = '';
                        return;
                    }
                    const parts = isoStr.split('-');
                    if (parts.length === 3) {
                        const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                        selectedDate = d;
                        displayInput.value = toDisplayStr(d);
                        hiddenInput.value = isoStr;
                        calViewDate = new Date(d.getFullYear(), d.getMonth(), 1);
                    }
                };
            }

            // Istanzia calendario per AGGIUNGI UTENTE
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

            // Istanzia calendario per MODIFICA UTENTE
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
                displayInputId: 'editData',
                hiddenInputId: 'editDataHidden'
            });
        })();

        document.addEventListener('mousemove', e => {
            document.documentElement.style.setProperty('--tt-y', (e.clientY + 14) + 'px');
            document.documentElement.style.setProperty('--tt-x', (e.clientX - 10) + 'px');
            document.documentElement.style.setProperty('--tt-arrow-y', (e.clientY + 8) + 'px');
            document.documentElement.style.setProperty('--tt-arrow-x', (e.clientX + 4) + 'px');
        });
    </script>

    <script src="js/mobile-calendar.js"></script>

    <script src="js/custom-select.js"></script>

</body>

</html>