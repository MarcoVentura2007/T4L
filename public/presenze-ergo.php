<?php
session_start();

// Redirect a login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Connessione al DB XAMPP
$host = "localhost";    // server
$user = "root";         // utente XAMPP
$pass = "";             // password di default
$db   = "time4all"; // nome del database

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Prendi la classe dell'utente loggato
$resultClasse = $conn->query("SELECT classe, codice_univoco FROM Account WHERE nome_utente = '$username'");
if($resultClasse && $resultClasse->num_rows > 0){
    $rowClasse = $resultClasse->fetch_assoc();
    $classe = $rowClasse['classe'];
    $codice = $rowClasse['codice_univoco'];
} else {
    $classe = ""; // default se non trovato
}

// Preleva i profili dal DB
$oggi = date('Y-m-d');

$sql = "
    SELECT i.id, i.nome, i.cognome, i.fotografia
    FROM iscritto i
    WHERE NOT EXISTS (
        SELECT 1
        FROM Presenza p
        WHERE p.ID_Iscritto = i.id
        AND DATE(p.Ingresso) = '$oggi'
    )
    ORDER BY i.cognome ASC
";
$result = $conn->query($sql);

// Crea un array per mappare nome completo a ID
$userMap = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $fullName = $row['nome'] . " " . $row['cognome'];
        $userMap[$fullName] = $row['id'];
    }
}

$conn->close();
?>

<script>
var userMap = <?php echo json_encode($userMap); ?>;
</script>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>T4L | Selezione</title>

<link rel="icon" href="immagini/Icona.ico">
<link rel="stylesheet" href="style.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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
                        } elseif($classe === 'Contabile'){
                            $gestionalePage = "gestionale_contabile.php";
                        } else {
                            $gestionalePage = "#"; // default se classe sconosciuta
                        }
                    ?>
                <div class="menu-item" id="cardGestionale" data-link=<?php echo $gestionalePage; ?> >
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

    </header>

    

    <!-- CONTENUTO PRINCIPALE -->
    <main class="carousel-dashboard">

        <h1 class="carousel-title">
            Riconoscimento Facciale per Presenze
        </h1>

        <p class="carousel-subtitle">
            Posizionati davanti alla webcam e scatta per verificare la tua identità
        </p>

        <div style="text-align: center; margin: 20px;">
            <video id="video" width="320" height="240" autoplay style="border: 1px solid #ccc;"></video><br>
            <button id="snap" style="margin-top: 10px; padding: 10px 20px; background-color: aqua; border: none; cursor: pointer;">Scatta e verifica</button>
            <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>
            <pre id="output" style="margin-top: 10px; font-family: monospace;"></pre>
        </div>

        <!-- POPUP OVERLAY -->
        <div class="popup-overlay" id="popupOverlay"></div>

        <!-- ORARI -->
        <div class="popup big" id="timePopup">
            <div class="popup-content">
                <div class="popup-left">
                    <img id="popupUserImg">
                    <h3 id="popupUserName"></h3>
                </div>
                

                <div class="popup-right">
                    <h2 class="popup-title">Inserisci orari</h2>
                    <div class="time-box">
                        <div class="time-row">
                            <label>Ora ingresso</label>
                            <input type="time" id="timeIn">
                        </div>
                        <div class="time-row">
                            <label>Ora uscita</label>
                            <input type="time" id="timeOut">
                        </div>
                    </div>
                    

                    <button class="btn-next" id="goSignature" style="background-color: aqua;">Continua</button>
                </div>
            </div>
        </div>

        <!-- FIRMA -->
        <div class="popup big" id="signaturePopup">
            
            <div class="popup-content">
                <div class="popup-left">
                    
                    <img id="popupUserImg2">
                    <h3 id="popupUserName2"></h3>
                </div>

                <div class="popup-right">
                    
                    <button class="close-popup" id="closeSignaturePopup">✖</button>

                    <button class="button" id="backToTimePopup">
                        <svg class="svgIcon" viewBox="0 0 24 24">
                            <path fill="white" d="M19 11H7.8l4.6-4.6a1 1 0 1 0-1.4-1.4l-6.3 6.3a1 1 0 0 0 0 1.4l6.3 6.3a1 1 0 1 0 1.4-1.4L7.8 13H19a1 1 0 1 0 0-2z"/>
                        </svg>
                    </button>




                    <h2 class="popup-title">Firma nella casella qua sotto</h2>

                    <canvas id="signatureCanvas"></canvas>

                    <div class="sign-actions">
                        <button id="clearSign" style="background-color: aqua;">Pulisci</button>
                        <button id="confirmSign" class="btn-confirm" style="background-color: aqua;">Conferma</button>
                    </div>
                </div>
            </div>
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
                <p class="success-text">Firma completata!!</p>
            </div>
        </div>


        



    </main>

    <div id="code-popup-ergo" class="popupErgo">
                <div class="content">
                    <p class="codice-text">Inserisci il codice di accesso</p>
                   <input 
                        type="password" 
                        placeholder="Codice d'accesso" 
                        id="password-ergo"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        autocomplete="off"
                        required
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    >


                    <button class="learn-more" id="button-gestionale-ergo">
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
    <!-- POPUP CODICE GESTIONALE -->
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


                    <button class="learn-more" id="button-gestionale" style="background-color: aqua;">
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

        // FACEID ELEMENTS
        const video = document.getElementById("video");
        const canvas = document.getElementById("canvas");        
        const snap = document.getElementById("snap");
        const output = document.getElementById("output");

        // WEBCAM ACCESS
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(err => {
                console.error("Errore accesso webcam:", err);
                output.textContent = "Errore accesso webcam";
            });

        // SNAP AND VERIFY
        snap.addEventListener("click", () => {
            const ctx = canvas.getContext("2d");
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(blob => {
                const formData = new FormData();
                formData.append("image", blob, "photo.png");

                fetch("../faceid/public/upload.php", { method: "POST", body: formData })
                    .then(res => {
                        console.log("DEBUG: Response status:", res.status);
                        return res.text();
                    })
                    .then(text => {
                        console.log("DEBUG: Response text:", text);
                        try {
                            const data = JSON.parse(text);
                            console.log("DEBUG: Parsed data:", data);

                            if (data.result && data.result.error) {
                                output.textContent = "Errore: " + data.result.error;
                                console.error("DEBUG: Error in result:", data.result);
                                if (data.result.raw) {
                                    console.log("DEBUG: Raw Python output:", data.result.raw);
                                }
                                return;
                            }
                            if (!data.result) {
                                output.textContent = "Risposta non valida dal server";
                                console.error("DEBUG: No result in response:", data);
                                return;
                            }

                            if (data.result.known) {
                                const recognizedName = data.result.name;
                                output.textContent = "VOLTO RICONOSCIUTO: " + recognizedName;
                                console.log("DEBUG: Face recognized:", recognizedName);

                                // Check if recognized name is in userMap
                                if (userMap[recognizedName]) {
                                    const userId = userMap[recognizedName];
                                    console.log("DEBUG: User authorized, opening popup for ID:", userId);

                                    // For now, set popup with name, and placeholder img
                                    const imgSrc = "immagini/profile-picture.png"; // placeholder
                                    img1.src = imgSrc;
                                    name1.textContent = recognizedName;
                                    img2.src = imgSrc;
                                    name2.textContent = recognizedName;
                                    selectedIdIscritto = userId;

                                    overlay.classList.add("show");
                                    timePopup.classList.add("show");
                                    document.body.classList.add("popup-open");
                                } else {
                                    output.textContent = "Utente riconosciuto ma non autorizzato per oggi.";
                                    console.log("DEBUG: User recognized but not authorized today");
                                }
                            } else {
                                output.textContent = "VOLTO NON RICONOSCIUTO";
                                console.log("DEBUG: Face not recognized");
                            }
                        } catch (e) {
                            console.error("DEBUG: JSON parse error:", e);
                            output.textContent = "Errore parsing JSON";
                        }
                    })
                    .catch(err => {
                        console.error("DEBUG: Fetch error:", err);
                        output.textContent = "Errore comunicazione server";
                    });
            }, "image/png");
        });

        // ELEMENTI
    const cardGestionale = document.getElementById("cardGestionale");
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
    passwordField.focus();
    return false;
});

// CHIUDI POPUP CLICCANDO FUORI
overlay.addEventListener("click", () => {
    overlay.classList.remove("show");
    codePopup.classList.remove("show");
    document.body.classList.remove("popup-open");
});

const passwordFieldErgo = document.getElementById("password-ergo");
const codePopupErgo = document.getElementById("code-popup-ergo");

const buttonGestionaleErgo = document.getElementById("button-gestionale-ergo");
buttonGestionaleErgo.addEventListener("click", verificaCodiceErgo);

// FUNZIONE DI CONTROLLO CODICE
async function verificaCodiceErgo() {
    const codice = passwordFieldErgo.value.trim();

    if (!codice) {
        showNotification(false, "Inserisci il codice");
        return;
    }

    try {
        const response = await fetch("api/api_codice_gestionale_ergo.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `codice=${encodeURIComponent(codice)}`
        });

        const result = await response.json();

        if (result.success) {
            showNotification(true, "Accesso consentito");

            passwordFieldErgo.value = "";

            // Chiudi popup
            overlay.classList.remove("show");
            codePopupErgo.classList.remove("show");
            document.body.classList.remove("popup-open");

            // Redirect alla pagina di gestionale
            setTimeout(() => {
                window.location.href = result.redirect;
            }, 2000);
        } else {
            showNotification(false, result.message);
            passwordFieldErgo.value = ""; // pulisci input se sbagliato
        }

    } catch (err) {
        showNotification(false, "Errore server");
        console.error(err);
    }
}

        overlay.onclick = closePopups;

passwordFieldErgo.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
        e.preventDefault(); // evita submit involontario
        verificaCodiceErgo();
    }
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

          
            overlay.classList.remove("show");
            codePopup.classList.remove("show");
            document.body.classList.remove("popup-open");

            setTimeout(() => {
                window.location.href = result.redirect;
            }, 2000);
        } else {
            showNotification(false, result.message);
            passwordField.value = ""; 
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
            e.preventDefault();
            verificaCodice();
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


        document.querySelectorAll(".menu-item[data-link]").forEach(item => {
            const link = item.dataset.link;
            if(link.includes("gestionale_ergo")) { 
                // Ergoterapeutica Gestionale - usa popup ergo
                item.addEventListener("click", (e) => {
                    e.preventDefault(); // previeni redirect
                    overlay.classList.add("show");
                    codePopupErgo.classList.add("show");
                    document.body.classList.add("popup-open");
                    passwordFieldErgo.focus(); // focus input
                });
            } else if(link.includes("gestionale")) { 
                // Centro Diurno Gestionale - usa popup standard
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



        /* POPUP PROFILI */
        
        const timePopup = document.getElementById("timePopup");
        const signPopup = document.getElementById("signaturePopup");
        const img1 = document.getElementById("popupUserImg");
        const name1 = document.getElementById("popupUserName");
        const img2 = document.getElementById("popupUserImg2");
        const name2 = document.getElementById("popupUserName2");

        let selectedIdIscritto = null;

        document.querySelectorAll(".profile-card").forEach(card => {
            card.onclick = () => {
                const img = card.querySelector("img").src;
                const name = card.querySelector("h3").textContent;

                img1.src = img;
                name1.textContent = name;
                img2.src = img;
                name2.textContent = name;

                // Salvo l'ID per quando confermo la firma
                selectedIdIscritto = card.getAttribute("data-id");

                overlay.classList.add("show");
                timePopup.classList.add("show");
                document.body.classList.add("popup-open");
            };
        });

        document.getElementById("goSignature").onclick = () => {

            const timeIn = document.getElementById("timeIn").value;
            const timeOut = document.getElementById("timeOut").value;

            // CONTROLLO PRIMA DI ANDARE ALLA FIRMA
            if(timeIn === "" || timeOut === ""){
                alert("Inserisci prima l'orario di ingresso e di uscita!");
                return; // blocca il passaggio alla firma
            }

            // se ok → vai alla firma
            timePopup.classList.remove("show");
            signPopup.classList.add("show");
        };





        /* PER USCIRE */
        overlay.onclick = closePopups;
        
         function closePopups(){
            overlay.classList.remove("show");
            codePopup.classList.remove("show");
            codePopupErgo.classList.remove("show");
            document.body.classList.remove("popup-open");
        }


        const backBtn = document.getElementById("backToTimePopup");

        backBtn.onclick = () => {
            signPopup.classList.remove("show");   // chiude firma
            timePopup.classList.add("show");      // riapre orari
        };










        
        



    </script>

</body>
</html>
