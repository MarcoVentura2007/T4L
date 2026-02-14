<?php
session_start();

// Se l'utente non è loggato → redirect a login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Reset flag codice per sicurezza
unset($_SESSION['codice_verificato']);
unset($_SESSION['codice_verificato_time']);

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


?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>T4L | Dashboard</title>

<link rel="stylesheet" href="style.css">
<link rel="icon" href="immagini/Icona.ico">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body>

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
                        } else if($classe === 'Contabile'){
                            $gestionalePage = "gestionale_contabile.php";
                        } else if($classe === 'Amministratore') {
                            $gestionalePage = "gestionale_amministratore.php"; 
                        } else {
                            $gestionalePage = "#"; 
                        }
                    ?>
                <div class="menu-item" data-link=<?php echo $gestionalePage; ?> >
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


<main class="dashboard">

    <h1>Benvenuto, <span id="nomeutente"> <?php echo htmlspecialchars($username); ?> </span></h1>
    <p class="subtitle">Cosa desideri fare oggi?</p>

    <section class="cards">

        <a href="fogliofirme-centro.php" class="card">
            <img src="immagini/foglio-over.png">
            <h3>Foglio Firme</h3>
        </a>

        <?php
        if($classe === 'Educatore'){
            $gestionalePage = "gestionale_utenti.php";
        } elseif($classe === 'Contabile'){
            $gestionalePage = "gestionale_contabile.php";
        } elseif($classe === 'Amministratore') {
            $gestionalePage = "gestionale_amministratore.php"; 
        }
        ?>

        <a href="#" class="card" id="card-gestionale">
            <img src="immagini/gestionale-over.png" style="height: 140px;">
            <h3>Gestionale</h3>
        </a>

        <div class="popup-overlay" id="popupOverlay"></div>

        <div id="code-popup" class="popup">
                <div class="content">
                    <p class="codice-text">Inserisci il codice di accesso</p>
                   <input 
                        type="password" 
                        placeholder="Codice d'accesso" 
                        id="password"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        autocomplete="off"
                        required
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    >


                    <button class="learn-more" id="button-gestionale">
                        <span class="circle" aria-hidden="true">
                        <span class="icon arrow"></span>
                        </span>
                        <span class="button-text">Continua</span>
                    </button>

                    <div id="notify" class="notify hidden">
                        <div class="icon" id="notify-icon"></div>
                        <div class="text" id="notify-text"></div>
                    </div>
            </div>
        </div>

    </section>

</main>

<footer class="footer-bar">
        <div class="footer-left">© Time4All • 2026</div>
         <div class="footer-top">
            <a href="#top" class="footer-image"></a>
        </div>
        <div class="footer-right">
            <a href="privacy_policy.php" class="hover-underline-animation">PRIVACY POLICY</a>
        </div>
    </footer>


<script>
    // ELEMENTI
    const cardGestionale = document.getElementById("card-gestionale");
    const overlay = document.getElementById("popupOverlay");
    const codePopup = document.getElementById("code-popup");
    const buttonGestionale = document.getElementById("button-gestionale");
    const passwordField = document.getElementById("password");
    const hamGestionale = document.getElementById("ham-gestionale");




// FUNZIONE NOTIFICATION
function showNotification(success = true, message = "Messaggio") {
    const notify = document.createElement('div');
    notify.classList.add('notify');
    notify.classList.add(success ? 'success' : 'error');

    const iconWrapper = document.createElement('div');
    iconWrapper.classList.add('icon-wrapper');

    const circle = document.createElement('div');
    circle.classList.add('circle');
    iconWrapper.appendChild(circle);

    const icon = document.createElement('span');
    icon.classList.add('icon');
    icon.textContent = success ? "✔" : "✖";
    iconWrapper.appendChild(icon);

    notify.appendChild(iconWrapper);

    const text = document.createElement('span');
    text.textContent = message;
    notify.appendChild(text);

    document.body.appendChild(notify);

    // Mostra con animazione
    setTimeout(() => notify.classList.add('show'), 10);

    // Nascondi dopo 3 secondi con animazione uscita
    setTimeout(() => {
        notify.classList.remove('show');
        notify.classList.add('hide');
        notify.addEventListener('animationend', () => notify.remove());
    }, 2000);
}

cardGestionale.addEventListener("click", (e) => {
    e.preventDefault();
    overlay.classList.add("show");
    codePopup.classList.add("show");
    document.body.classList.add("popup-open");
    passwordField.focus(); // focus automatico sull'input
});

// CHIUDI POPUP CLICCANDO FUORI
overlay.addEventListener("click", () => {
    overlay.classList.remove("show");
    codePopup.classList.remove("show");
    document.body.classList.remove("popup-open");
});

// FUNZIONE DI CONTROLLO CODICE
async function verificaCodice() {
    const codice = passwordField.value.trim();

    if (!codice) {
        showNotification(false, "Inserisci il codice");
        return;
    }

    try {
        const response = await fetch("api/api_codice_gestionale.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `codice=${encodeURIComponent(codice)}`
        });

        const result = await response.json();

        if (result.success) {
            showNotification(true, "Accesso consentito");

            passwordField.value = "";

            // Chiudi popup
            overlay.classList.remove("show");
            codePopup.classList.remove("show");
            document.body.classList.remove("popup-open");

            // Redirect alla pagina di gestionale
            setTimeout(() => {
                window.location.href = result.redirect;
            }, 2000);
        } else {
            showNotification(false, result.message);
            passwordField.value = ""; // pulisci input se sbagliato
        }

    } catch (err) {
        showNotification(false, "Errore server");
        console.error(err);
    }
}

// BOTTONE CONTINUA
buttonGestionale.addEventListener("click", verificaCodice);

// INVIO DALL'INPUT
passwordField.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
        e.preventDefault(); // evita submit involontario
        verificaCodice();
    }
});
        // CHIUDI POPUP CLICCANDO FUORI
        overlay.onclick = closePopups;

        function closePopups(){
            overlay.classList.remove("show");
            codePopup.classList.remove("show");
            document.body.classList.remove("popup-open");
        }
        

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


        // Prendi tutti i link "menu-item" con data-link
        document.querySelectorAll(".menu-item[data-link]").forEach(item => {
            const link = item.dataset.link;
            if(link.includes("gestionale")) { // intercetta solo link gestionale
                item.addEventListener("click", (e) => {
                    e.preventDefault(); // previeni redirect
                    overlay.classList.add("show");
                    codePopup.classList.add("show");
                    document.body.classList.add("popup-open");
                    passwordField.focus(); // focus input
                });
            } else {
                // link normali
                item.addEventListener("click", () => {
                    window.location.href = link;
                });
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


</script>

</body>
</html>
