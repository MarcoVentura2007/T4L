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
                <a href="impostazioni.php">
                    <span class="icon">⚙</span>
                    <span class="text">Impostazioni</span>
                </a>
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
            <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png">
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
                <div class="menu-item" data-link="riconoscimento.php">Riconoscimento facciale</div>
                <div class="menu-item" data-link="gestionale_contabile.php">Gestionale</div>
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

                    <div class="modal-overlay" id="modalOverlay"></div>
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
                                        data-uscita="'.htmlspecialchars($row['uscita']).'""
                                    >
                                        <td><img class="user-avatar" src="'.$row['fotografia'].'"></td>
                                        <td>'.htmlspecialchars($row['nome']).'</td>
                                        <td>'.htmlspecialchars($row['cognome']).'</td>
                                        <td>'.htmlspecialchars($row['ingresso']).'</td>
                                        <td>'.htmlspecialchars($row['uscita']).'</td>
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
                <div class="page-header">
                    <h1>Agenda</h1>
                    <p>Prossimi appuntamenti</p>
                </div>
                <p>Contenuto agenda da implementare...</p>
            </div>






            

        </main>
    </div>

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
            sessionStorage.setItem("activeTab", target);
        });
    });

    window.addEventListener("DOMContentLoaded", () => {
        const savedTab = sessionStorage.getItem("activeTab");
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


// LOGOUT
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

// MODAL UTENTI
const overlay = document.getElementById("modalOverlay");
const viewModal = document.getElementById("viewModal");
function openModal(modal){
    overlay.classList.add("show");
    modal.classList.add("show");
}
function closeModal(){
    overlay.classList.remove("show");
    viewModal.classList.remove("show");
}

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
overlay.onclick = closeModal;


</script>

</body>
</html>
