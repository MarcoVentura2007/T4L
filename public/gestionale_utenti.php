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

if ($classe !== 'Educatore') {
    header("Location: index.php");
    exit;
}

// Utenti
$sql = "SELECT id, nome, cognome, fotografia, data_nascita, disabilita, prezzo_orario, prezzo_orario_gruppo, codice_fiscale, email, telefono, allergie_intolleranze, note, Gruppo
        FROM iscritto ORDER BY cognome ASC";
$result = $conn->query($sql);

// Presenze giornaliere
$oggi = date('Y-m-d') . "%";
$sqlPresenze = "SELECT i.fotografia, p.id, i.nome, i.cognome, p.ingresso, p.uscita
                FROM presenza p
                INNER JOIN iscritto i ON p.ID_Iscritto = i.id
                WHERE p.ingresso LIKE '$oggi'
                ORDER BY p.ingresso ASC";
$resultPresenze = $conn->query($sqlPresenze);

// Agenda
$sqlAttivitaCombo = "SELECT id, Nome FROM attivita ORDER BY Nome ASC";
$resultAttivitaCombo = $conn->query($sqlAttivitaCombo);

$sqlEducatoriAgenda = "SELECT id, nome, cognome FROM educatore ORDER BY cognome ASC, nome ASC";
$resultEducatoriAgenda = $conn->query($sqlEducatoriAgenda);

$sqlRagazzi = "SELECT id, nome, cognome FROM iscritto ORDER BY cognome ASC, nome ASC";
$resultRagazzi = $conn->query($sqlRagazzi);
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>T4L | Gestionale Educatore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_mobile_agenda.css">
    <link rel="icon" href="immagini/Icona.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (max-width: 768px) {
            .footer-bar {
                display: none;
            }
        }
    </style>
</head>

<body>

    <script src="js/loader.js"></script>

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
                    <div class="menu-item" data-link="gestionale_utenti.php">
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
                    <div class="menu-item" data-link="gestionale_ergo_utenti.php">
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
                    </ul>
                </section>
            </nav>
        </aside>

        <main class="main-content">
            <div class="main-container">

                <!-- TAB UTENTI (solo visualizzazione) -->
                <div class="page-tab active" id="tab-utenti">
                    <div class="header-mobile">
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
                                    <td>' . htmlspecialchars($row['disabilita']) . '</td>
                                    <td>' . htmlspecialchars($row['note']) . '</td>
                                    <td>
                                        <button class="view-btn"><img src="immagini/open-eye.png"></button>
                                    </td>
                                </tr>';
                                    }
                                }
                                ?>
                            </tbody>
                        </table>

                        <!-- MODAL VIEW UTENTE -->
                        <div class="modal-box large" id="viewModal">
                            <div class="profile-header">
                                <img id="viewAvatar" class="profile-avatar">
                                <div class="profile-main">
                                    <h3 id="viewFullname"></h3>
                                    <span id="viewBirth"></span>
                                </div>
                            </div>
                            <div class="profile-grid" id="viewContent"></div>
                            <div class="allegati-section" style="margin-top:20px;border-top:1px solid #e0e0e0;padding-top:15px;">
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
                        </div>
                    </div>
                </div>

                <!-- TAB PRESENZE (edit/delete) -->
                <div class="page-tab" id="tab-presenze">
                    <div class="page-header">
                        <h1>Presenze</h1>
                        <p>Elenco presenze giornaliere</p>
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
                                if ($resultPresenze && $resultPresenze->num_rows > 0) {
                                    while ($row = $resultPresenze->fetch_assoc()) {
                                        echo '
                                <tr
                                    data-id="' . htmlspecialchars($row['id']) . '"
                                    data-nome="' . htmlspecialchars($row['nome']) . '"
                                    data-cognome="' . htmlspecialchars($row['cognome']) . '"
                                    data-ingresso="' . htmlspecialchars($row['ingresso']) . '"
                                    data-uscita="' . htmlspecialchars($row['uscita']) . '"
                                >
                                    <td><img class="user-avatar" src="' . $row['fotografia'] . '"></td>
                                    <td>' . htmlspecialchars($row['nome']) . '</td>
                                    <td>' . htmlspecialchars($row['cognome']) . '</td>
                                    <td>' . htmlspecialchars($row['ingresso']) . '</td>
                                    <td>' . htmlspecialchars($row['uscita']) . '</td>
                                    <td>
                                        <button class="edit-presenza-btn" data-id="' . htmlspecialchars($row['id']) . '"><img src="immagini/edit.png" alt="Modifica"></button>
                                        <button class="delete-presenza-btn" data-id="' . htmlspecialchars($row['id']) . '"><img src="immagini/delete.png" alt="Elimina"></button>
                                    </td>
                                </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="6">Nessuna presenza registrata oggi.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB AGENDA (solo visualizzazione + stampa) -->
                <div class="page-tab" id="tab-agenda">
                    <div class="header-mobile">
                        <div class="page-header">
                            <h1>Agenda</h1>
                            <p>Attività della settimana</p>
                        </div>
                    </div>
                    <div class="agenda-container" style="margin:0 auto;">
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
            <p class="success-text" id="success-text">Operazione completata!</p>
        </div>
    </div>

    <!-- EDIT MODAL PRESENZE -->
    <div class="modal-box large" id="editModal">
        <h3 class="modal-title" id="modalEditTitle">Modifica Presenza</h3>
        <div class="profile-header" id="profileHeader" style="display:none;">
            <img id="viewAvatar-mod" class="profile-avatar">
            <div class="profile-main">
                <h3 id="viewFullname-mod"></h3>
                <span id="viewBirth-mod"></span>
            </div>
        </div>
        <div class="edit-grid">
            <div class="edit-field" id="fieldIngresso"><label>Ingresso (ora)</label><input type="time" id="editIngresso"></div>
            <div class="edit-field" id="fieldUscita"><label>Uscita (ora)</label><input type="time" id="editUscita"></div>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
            <button class="btn-primary" id="saveEdit">Salva</button>
        </div>
    </div>

    <!-- DELETE MODAL PRESENZE -->
    <div class="modal-box danger" id="deleteModal">
        <h3 id="deleteModalTitle">Elimina Presenza</h3>
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
    </nav>

    <div class="modal-overlay" id="Overlay"></div>

    <script>
        // =====================================================================
        // UTILITY
        // =====================================================================
        function getLocalDateString(date) {
            const y = date.getFullYear();
            const m = (date.getMonth() + 1).toString().padStart(2, '0');
            const d = date.getDate().toString().padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function getAllegatoIcon(tipo) {
            const icons = {
                'pdf': '<img src="immagini/pdf.png"       style="width:26px;height:26px;">',
                'doc': '<img src="immagini/docx.png"      style="width:26px;height:26px;">',
                'docx': '<img src="immagini/docx.png"      style="width:26px;height:26px;">',
                'image': '<img src="immagini/img.png"       style="width:26px;height:26px;">',
                'xls': '<img src="immagini/xls.png"       style="width:26px;height:26px;">',
                'xlsx': '<img src="immagini/xls.png"       style="width:26px;height:26px;">',
                'txt': '<img src="immagini/txt.png"       style="width:26px;height:26px;">',
                'file': '<img src="immagini/paperclip.png" style="width:26px;height:26px;">'
            };
            return icons[tipo] || icons['file'];
        }

        // =====================================================================
        // TABS
        // =====================================================================
        document.querySelectorAll(".tab-link").forEach(link => {
            link.addEventListener("click", e => {
                e.preventDefault();
                const target = e.currentTarget.dataset.tab;
                document.querySelectorAll(".tab-link").forEach(l => l.classList.remove("active"));
                document.querySelectorAll(".page-tab").forEach(t => t.classList.remove("active"));
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
        logoutBtn.addEventListener("click", e => {
            e.preventDefault();
            logoutOverlay.classList.add("show");
            logoutModal.classList.add("show");
        });
        document.getElementById("cancelLogout").onclick = () => {
            logoutOverlay.classList.remove("show");
            logoutModal.classList.remove("show");
        };
        logoutOverlay.onclick = () => {
            logoutOverlay.classList.remove("show");
            logoutModal.classList.remove("show");
        };
        document.getElementById("confirmLogout").onclick = () => {
            window.location.href = "logout.php";
        };

        // =====================================================================
        // MODAL HELPERS
        // =====================================================================
        const Overlay = document.getElementById("Overlay");
        const viewModal = document.getElementById("viewModal");
        const editModal = document.getElementById("editModal");
        const deleteModal = document.getElementById("deleteModal");
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
                const any = document.querySelector(".modal-box.show,.popup.show,.logout-modal.show,.success-popup.show");
                if (!any) overlay.classList.remove("show");
            }
        }
        if (Overlay) Overlay.onclick = closeModal;

        // =====================================================================
        // VIEW UTENTE (solo visualizzazione)
        // =====================================================================
        document.querySelectorAll(".view-btn").forEach(btn => {
            btn.onclick = async e => {
                const row = e.target.closest("tr");
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
            <div class="profile-field"><label>Disabilità</label><span>${row.dataset.disabilita||"—"}</span></div>
            <div class="profile-field"><label style="font-weight:bold;">Intolleranze ⚠️</label><span style="font-weight:bold;">${row.dataset.intolleranze||"—"}</span></div>
            <div class="profile-field"><label>Prezzo orario</label><span>${row.dataset.prezzo||"—"} €</span></div>
            <div class="profile-field"><label>Prezzo orario Gruppo</label><span>${row.dataset.prezzoGruppo||"—"} €</span></div>
            <div class="profile-field"><label>Tipo di lavoro</label><span>${row.dataset.gruppo==='1'||row.dataset.gruppo==='on'?'Gruppo':'Individuale'}</span></div>
            <div class="profile-field" style="grid-column:1/-1;"><label>Note</label><span>${row.dataset.note||"—"}</span></div>
        `;
                // Carica allegati
                const div = document.getElementById("viewAllegatiList");
                div.innerHTML = '<p style="color:#888;font-style:italic;">Caricamento allegati...</p>';
                try {
                    const res = await fetch(`api/api_get_allegati.php?id_iscritto=${row.dataset.id}`);
                    const data = await res.json();
                    if (!data.success || !data.allegati || data.allegati.length === 0) {
                        div.innerHTML = '<p style="color:#888;font-style:italic;">Nessun allegato presente</p>';
                    } else {
                        let html = '<div class="allegati-grid">';
                        data.allegati.forEach(a => {
                            const icon = getAllegatoIcon(a.tipo);
                            const dataF = new Date(a.data_upload).toLocaleDateString('it-IT');
                            html += `<div class="allegato-card" style="border:1px solid #e0e0e0;border-radius:8px;padding:12px;display:flex;align-items:center;gap:10px;background:#f9f9f9;">
                        <div>${icon}</div>
                        <div style="flex:1;min-width:0;"><div style="font-weight:500;color:#2b2b2b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${a.nome_file}">${a.nome_file}</div><div style="font-size:12px;color:#888;">${dataF}</div></div>
                        <a href="${a.percorso}" target="_blank" title="Scarica">
                            <button class="bg-white w-6 h-6 flex justify-center items-center rounded text-black border border-black hover:bg-black hover:text-white transition-all duration-200">
                                <svg class="w-4 h-4" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" fill="none"><path d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" stroke-linejoin="round" stroke-linecap="round"></path></svg>
                            </button>
                        </a>
                    </div>`;
                        });
                        div.innerHTML = html + '</div>';
                    }
                } catch (err) {
                    div.innerHTML = '<p style="color:#d32f2f;font-style:italic;">Errore nel caricamento degli allegati</p>';
                }
                openModal(viewModal);
            };
        });

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
                document.getElementById('modalEditTitle').innerText = 'Modifica Presenza - ' + row.dataset.nome + ' ' + row.dataset.cognome;
                editModal.dataset.editType = 'presenza';
                editModal.dataset.presenzeId = row.dataset.id;
                document.getElementById('editIngresso').value = (ingresso.split(' ')[1] || '').slice(0, 5);
                document.getElementById('editUscita').value = (uscita.split(' ')[1] || '').slice(0, 5);
                openModal(editModal);
            }

            if (e.target.closest('.delete-presenza-btn')) {
                const btn = e.target.closest('.delete-presenza-btn');
                const row = btn.closest('tr');
                document.getElementById('deleteModalTitle').innerText = 'Eliminazione Presenza - ' + row.dataset.nome + ' ' + row.dataset.cognome;
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

        document.getElementById("saveEdit").onclick = () => {
            const id = editModal.dataset.presenzeId;
            const today = new Date().toISOString().split('T')[0];
            const ingresso = today + ' ' + document.getElementById("editIngresso").value + ':00';
            const uscita = today + ' ' + document.getElementById("editUscita").value + ':00';
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
            }).then(r => r.json()).then(data => {
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
        };

        // =====================================================================
        // AGENDA (solo visualizzazione)
        // =====================================================================
        let agendaData = [],
            agendaWeekStart = null,
            selectedDayIndex = 0,
            currentMonday = null;

        function calculateWeekDates(weekStartStr) {
            const today = new Date();
            let monday;
            if (weekStartStr) {
                const p = weekStartStr.split('-');
                monday = new Date(p[0], p[1] - 1, p[2]);
            } else {
                monday = new Date(today);
                monday.setDate(today.getDate() - today.getDay() + 1);
            }
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
        }

        function loadAgenda() {
            const div = document.getElementById('agendaContent');
            div.innerHTML = '<div class="loading">Caricamento attività...</div>';
            fetch('api/api_get_agenda.php').then(r => r.json()).then(data => {
                if (data.success) {
                    agendaData = data.data || [];
                    agendaWeekStart = data.monday || null;
                    calculateWeekDates(agendaWeekStart);
                    let def = new Date().getDay() - 1;
                    if (def < 0 || def > 4) def = 0;
                    const saved = parseInt(localStorage.getItem("selectedDayIndex"));
                    displayAgenda(isNaN(saved) ? def : saved);
                } else {
                    div.innerHTML = '<div class="error-message">Errore: ' + (data.error || 'Sconosciuto') + '</div>';
                }
            }).catch(() => {
                document.getElementById('agendaContent').innerHTML = '<div class="error-message">Errore nel caricamento</div>';
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
                }, {
                    start: '12:00',
                    end: '14:00',
                    bg: '#f6ffed'
                }, {
                    start: '14:00',
                    end: '16:00',
                    bg: '#fff2f0'
                }, {
                    start: '16:00',
                    end: '18:00',
                    bg: '#f9f0ff'
                }];
                const giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì'];
                pw.document.write(`<html><head><title>Agenda Settimanale</title><style>@page{size:A4 landscape;}body{font-family:Arial,sans-serif;margin:3px;width:297mm;}table{width:100%;border-collapse:collapse;font-size:14px;table-layout:fixed;}th,td{border:1px solid #000;padding:8px;text-align:left;vertical-align:top;width:20%;word-wrap:break-word;-webkit-print-color-adjust:exact;print-color-adjust:exact;}th{background:#f0f0f0;font-weight:bold;}.activity{margin-bottom:6px;}</style></head><body><h2 style="text-align:center;">Agenda Settimanale - ${new Date().toLocaleDateString('it-IT')}</h2><table><thead><tr><th>Lunedì</th><th>Martedì</th><th>Mercoledì</th><th>Giovedì</th><th>Venerdì</th></tr></thead><tbody>`);
                timeSlots.forEach(slot => {
                    pw.document.write('<tr>');
                    for (let idx = 0; idx < 5; idx++) {
                        let monday2;
                        if (agendaWeekStart) {
                            const p = agendaWeekStart.split('-');
                            monday2 = new Date(p[0], p[1] - 1, p[2]);
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

        window.addEventListener("DOMContentLoaded", () => {
            loadAgenda();
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

    <script src="js/mobile-calendar.js"></script>
</body>

</html>