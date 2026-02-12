<?php
session_start();

// Se l'utente non e' loggato viene redirect a login.php
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
$sql = "SELECT id, nome, cognome, fotografia, data_nascita, disabilita, prezzo_orario, codice_fiscale, contatti, allergie_intolleranze, note 
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


//impedisci di entrare alla pagina tramite url se non si è Educatore
if($classe !== 'Educatore'){
    header("Location: index.php");
    exit;
}






?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>T4L | Gestionale utenti</title>

<link rel="stylesheet" href="style.css">
<link rel="icon" href="immagini/Icona.ico">
<script src="https://cdn.tailwindcss.com"></script>

</head>
<body onload="selezionata()">
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
                <div class="menu-item" data-link="gestionale_contabile.php">
                    <img src="immagini/gestionale-ergo.png" alt="">
                    Gestionale
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


        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- TAB UTENTI -->
            <div class="page-tab active" id="tab-utenti">
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
                                        data-contatti="'.htmlspecialchars($row['contatti']).'" 
                                        data-disabilita="'.htmlspecialchars($row['disabilita']).'" 
                                        data-intolleranze="'.htmlspecialchars($row['allergie_intolleranze']).'" 
                                        data-prezzo="'.htmlspecialchars($row['prezzo_orario']).'" 
                                    >
                                        <td><img class="user-avatar" src="'.$row['fotografia'].'"></td>
                                        <td>'.htmlspecialchars($row['nome']).'</td>
                                        <td>'.htmlspecialchars($row['cognome']).'</td>
                                        <td>'.htmlspecialchars($row['data_nascita']).'</td>
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
                        <div class="modal-actions">
                            <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB PRESENZE -->
            <div class="page-tab" id="tab-presenze">
                <div class="page-header">
                    <h1>Presenze</h1>
                    <p>Elenco presenze registrate</p>
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

                    <!-- POPUP SUCCESSO -->
                    <div class="popup success-popup" id="successPopup">
                        <div class="success-content">
                            <div class="success-icon">
                            <svg viewBox="-2 -2 56 56">
                                <circle class="check-circle" cx="26" cy="26" r="25" fill="none"/>
                                <path class="check-check" d="M14 27 L22 35 L38 19" fill="none"/>
                            </svg>
                            </div>
                            <p class="success-text" id="success-text">Operazione completata!!</p>
                        </div>
                    </div>
                    
                    <div class="modal-box large" id="modalPresenze">
                        <h3 id="presenzeModalTitle">Presenze</h3>
                        <form id="formModificaPresenza">
                            <div class="edit-field">
                                <label>Ingresso (ora)</label>
                                <input type="time" id="presenzeIngresso" required>
                            </div>
                            <div class="edit-field">
                                <label>Uscita (ora)</label>
                                <input type="time" id="presenzeUscita" required>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="closeModal()">Chiudi</button>
                                <button type="submit" class="btn-primary">Salva</button>
                            </div>
                        </form>

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






            <!-- TAB AGENDA -->
            <div class="page-tab" id="tab-agenda">
                <div class="page-header">
                    <h1>Agenda</h1>
                    <p>Attività della settimana (Lunedì - Venerdì)</p>
                </div>

                <div class="agenda-container">
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

                    <div class="agenda-content" id="agendaContent">
                        <div class="loading">Caricamento attività...</div>
                    </div>
                </div>

        </main>
    </div>


    <div class="modal-overlay" id="modalOverlay"></div>

    <footer class="footer-bar" style="bottom: auto;">
        <div class="footer-left">© Time4All • 2026</div>
         <div class="footer-top">
            <a href="#top" class="footer-image"></a>
        </div>
        <div class="footer-right">
            <a href="privacy_policy.php" class="hover-underline-animation">PRIVACY POLICY</a>
        </div>
    </footer>
<script>
    // Cambia tab
    // Cambia tab e salva stato
    document.querySelectorAll(".tab-link").forEach(link => {
        link.addEventListener("click", e => {
            e.preventDefault();
            const target = e.currentTarget.dataset.tab;

            document.querySelectorAll(".tab-link").forEach(l => l.classList.remove("active"));
            document.querySelectorAll(".page-tab").forEach(tab => tab.classList.remove("active"));

            // Attiva link e tab cliccati
            e.currentTarget.classList.add("active");
            document.getElementById(target).classList.add("active");

            // Salva il tab attivo in localStorage
            localStorage.setItem("activeTab", target);
        });
    });

    window.addEventListener("DOMContentLoaded", () => {
        const savedTab = localStorage.getItem("activeTab");
        if (savedTab) {
            // Rimuovi 'active' da tutti
            document.querySelectorAll(".tab-link").forEach(l => l.classList.remove("active"));
            document.querySelectorAll(".page-tab").forEach(tab => tab.classList.remove("active"));

            // Attiva quello salvato
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

            // chiudi tutti gli altri submenu
            document.querySelectorAll(".submenu").forEach(menu => {
                if(menu !== targetMenu){
                    menu.classList.remove("open");
                    menu.previousElementSibling.classList.remove("open"); // reset freccetta
                }
            });

            // toggle quello cliccato
            targetMenu.classList.toggle("open");
            main.classList.toggle("open"); // per la freccetta
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


// MODALI E OVERLAY
const modalOverlay = document.getElementById("modalOverlay");
const viewModal = document.getElementById("viewModal");

function openModal(modal){
    if(modal) modal.classList.add("show");
    if(modalOverlay) modalOverlay.classList.add("show");
}

function closeModal(){
    if(modalOverlay) modalOverlay.classList.remove("show");
    document.querySelectorAll(".modal-box.show").forEach(el => el.classList.remove("show"));
    const logoutModal = document.getElementById("logoutModal");
    if(logoutModal) logoutModal.classList.remove("show");
}

if(modalOverlay) modalOverlay.onclick = closeModal;

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
// VIEW BTN UTENTI
document.querySelectorAll(".view-btn").forEach(btn=>{
    btn.onclick = e=>{
        const row = e.target.closest("tr");

        const avatar = row.querySelector("img").src;
        const nome = row.dataset.nome;
        const cognome = row.dataset.cognome;
        const data = row.dataset.nascita;
        const note = row.dataset.note;
        const cf = row.dataset.cf;
        const contatti = row.dataset.contatti;
        const disabilita = row.dataset.disabilita;
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
            <div class="profile-field"><label>Contatti</label><span>${contatti || "—"}</span></div>
            <div class="profile-field"><label>Disabilità</label><span>${disabilita || "—"}</span></div>
            <div class="profile-field"><label style="font-weight: bold;">Intolleranze ⚠️</label><span style="font-weight: bold;">${intolleranze || "—"}</span></div>
            <div class="profile-field"><label>Prezzo orario</label><span>${prezzo || "—"} €</span></div>
            <div class="profile-field" style="grid-column:1 / -1;"><label>Note</label><span>${note || "—"}</span></div>
        `;

        openModal(viewModal);
    }
});
// overlay click already handled above

// ========== MODAL PRESENZE ==========
const modalPresenze = document.getElementById("modalPresenze");
const deletePresenzaBox = document.getElementById("deletePresenzaBox");
const formPresenze = document.getElementById("formModificaPresenza");
let currentPresenzeId = null;
let isDeleteMode = false;

function openPresenzeModal(isDelete = false){
    isDeleteMode = isDelete;
    if(isDelete){
        formPresenze.style.display = "none";
        deletePresenzaBox.style.display = "block";
        document.getElementById("presenzeModalTitle").innerText = "Elimina presenza";
    } else {
        formPresenze.style.display = "block";
        deletePresenzaBox.style.display = "none";
        document.getElementById("presenzeModalTitle").innerText = "Modifica presenza";
    }
    openModal(modalPresenze);
}

formPresenze.addEventListener("submit", (e) => {
    e.preventDefault();
    const newIngresso = document.getElementById("presenzeIngresso").value;  // es: "14:30"
    const newUscita = document.getElementById("presenzeUscita").value;      // es: "15:45"
    
    // Ottenere la data di oggi in formato YYYY-MM-DD
    const today = new Date().toISOString().split('T')[0];
    
    // Combinare data e ora nel formato DB (YYYY-MM-DD HH:MM:SS)
    const ingressoDb = today + ' ' + newIngresso + ':00';
    const uscitaDb = today + ' ' + newUscita + ':00';
    
    fetch('api/api_modifica_presenza.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({id: currentPresenzeId, ingresso: ingressoDb, uscita: uscitaDb})
    }).then(r => r.json()).then(data => {
        if(data.success){
            closeModal();
            document.getElementById("success-text").innerText = "Presenza modificata!";
            document.getElementById("successPopup").classList.add("show");
            setTimeout(()=>{
                document.getElementById("successPopup").classList.remove("show");
                location.reload();
            }, 1800);
        } else {
            alert("Errore: " + (data.error || 'Noto'));
        }
    });
});

document.getElementById("confirmDeletePresenza").addEventListener("click", () => {
    fetch('api/api_elimina_presenza.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({id: currentPresenzeId})
    }).then(r => r.json()).then(data => {
        if(data.success){
            closeModal();
            document.getElementById("success-text").innerText = "Presenza eliminata!";
            document.getElementById("successPopup").classList.add("show");
            setTimeout(()=>{
                document.getElementById("successPopup").classList.remove("show");
                location.reload();
            }, 1800);
        } else {
            alert("Errore: " + (data.error || 'Noto'));
        }
    });
});

// Presenze: click handler (delegation)
document.addEventListener('click', (e) => {
    const editBtn = e.target.closest('.edit-presenza-btn');
    if(editBtn){
        const tr = editBtn.closest('tr');
        currentPresenzeId = editBtn.dataset.id;
        const ingresso = tr.dataset.ingresso || '';  // es: "2026-02-07 14:30:00"
        const uscita = tr.dataset.uscita || '';      // es: "2026-02-07 15:45:00"
        
        // Estrarre solo l'ora (HH:MM)
        const ingressoTime = ingresso.split(' ')[1]?.slice(0, 5) || '';
        const uscitaTime = uscita.split(' ')[1]?.slice(0, 5) || '';
        
        document.getElementById("presenzeIngresso").value = ingressoTime;
        document.getElementById("presenzeUscita").value = uscitaTime;
        openPresenzeModal(false);
        return;
    }

    const delBtn = e.target.closest('.delete-presenza-btn');
    if(delBtn){
        currentPresenzeId = delBtn.dataset.id;
        openPresenzeModal(true);
        return;
    }
});

  // ================= AGENDA =================
let agendaData = [];
let agendaWeekStart = null;
let selectedDayIndex = 0;

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
                let defaultDayIndex = new Date(); // 0 for Monday, 1 for Tuesday, etc.
                if (defaultDayIndex < 0) defaultDayIndex = 6; // Sunday becomes 6
                const savedDayIndex = parseInt(localStorage.getItem("selectedDayIndex")) || defaultDayIndex;
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


    const contentDiv = document.getElementById('agendaContent');

    if(!agendaData || agendaData.length === 0){
        contentDiv.innerHTML = '<div class="no-activities">Nessuna attività disponibile</div>';
        return;
    }

    // calcola lunedì della settimana
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
        const ragazziText = Array.from(
            new Map(att.ragazzi.map(r=>[r.id,r])).values()
        ).map(r=>`${r.nome} ${r.cognome}`).join(', ') || '—';

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
                    <span>${ragazziText}</span>
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
        // Animazione di scorrimento per centrare il tab attivo
        tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
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



        // Event listeners per i tab dei giorni
        document.querySelectorAll('.day-tab').forEach((tab, index) => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                displayAgenda(index);
            });
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
o</script>

</body>
</html>
