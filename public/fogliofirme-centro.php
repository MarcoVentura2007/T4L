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

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>T4L | Selezione</title>

<link rel="icon" href="immagini/Icona.ico">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
<link rel="stylesheet" href="style.css">

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body style="overflow:hidden;">

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
                <div class="menu-item" data-link="riconoscimento.php">Riconoscimento facciale</div>
                <div class="menu-item" data-link="gestionale_contabile.php">Gestionale</div>
            </div>

        </div>

    </div>

    </header>

    

    <!-- CONTENUTO PRINCIPALE -->
    <main class="carousel-dashboard">

        <h1 class="carousel-title">
            Selezionati sullo schermo per firmare
        </h1>

        <p class="carousel-subtitle">
            Scorri le schede e clicca sul profilo desiderato
        </p>

        <div class="swiper mySwiper">

            <div class="swiper-wrapper">
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $nome = htmlspecialchars($row['nome']);
                        $cognome = htmlspecialchars($row['cognome']);
                        $img = htmlspecialchars($row['fotografia']);
                        echo '
                        <div class="swiper-slide profile-card" data-id="' . $row['id'] . '">
                            <a href="#">
                                <img src="' . $img . '">
                                <h3>' . $nome . " " . $cognome . '</h3>
                            </a>
                        </div>
                        ';
                    }
                } else {
                    echo '<p>Tutti gli utenti hanno già effettuato la firma!!</p>';
                }
                ?>
            </div>

            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>

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
                    

                    <button class="btn-next" id="goSignature">Continua</button>
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
                        <button id="clearSign">Pulisci</button>
                        <button id="confirmSign" class="btn-confirm">Conferma</button>
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
                        requi
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

    <script>

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


        
        /* SWIPER */
        const swiper = new Swiper(".mySwiper", {
            slidesPerView: 3,
            spaceBetween: 80,
            centeredSlides: true,
            grabCursor: true,
            loop: false,
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            breakpoints: {
                0: { slidesPerView: 1.2 },
                700: { slidesPerView: 2 },
                1100: { slidesPerView: 3 }
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
            timePopup.classList.remove("show");
            signPopup.classList.remove("show");
            document.body.classList.remove("popup-open");
        }


        const backBtn = document.getElementById("backToTimePopup");

        backBtn.onclick = () => {
            signPopup.classList.remove("show");   // chiude firma
            timePopup.classList.add("show");      // riapre orari
        };





        /* DISEGNO */
        const canvas = document.getElementById("signatureCanvas");
        const ctx = canvas.getContext("2d");
        function resizeCanvas(){
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
        }
        resizeCanvas();
        window.addEventListener("resize", resizeCanvas);

        let drawing = false;
        canvas.addEventListener("pointerdown", e=>{
            drawing = true;
            ctx.beginPath();
            ctx.moveTo(e.offsetX, e.offsetY);
        });
        canvas.addEventListener("pointermove", e=>{
            if(!drawing) return;
            ctx.lineTo(e.offsetX, e.offsetY);
            ctx.stroke();
        });
        canvas.addEventListener("pointerup", ()=> drawing=false);
        canvas.addEventListener("pointerleave", ()=> drawing=false);

        /* PULISCI */
        document.getElementById("clearSign").onclick = ()=>{
            ctx.clearRect(0,0,canvas.width,canvas.height);
        };




        /* CHIUDI POPUP FIRMA CON X */
        const closeSignBtn = document.getElementById("closeSignaturePopup");
        closeSignBtn.onclick = () => {
            signPopup.classList.remove("show"); 
            overlay.classList.remove("show");
            document.body.classList.remove("popup-open");
        };


        





        /* INSERIMENTO ORA */
        const timeInPicker = flatpickr("#timeIn", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            minuteIncrement: 15,

            onChange: function(selectedDates, dateStr) {
                timeOutPicker.set("minTime", dateStr);

                if(timeOutPicker.input.value && timeOutPicker.input.value < dateStr){
                    timeOutPicker.clear();
                }
            }
        });

        const timeOutPicker = flatpickr("#timeOut", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            minuteIncrement: 15
        });





        /* POPUP SUCCESSO */
        document.querySelector(".btn-confirm").onclick = () => {

            const timeIn = document.getElementById("timeIn").value;
            const timeOut = document.getElementById("timeOut").value;
            const check_firma = 1;
            const idIscritto = selectedIdIscritto;

            // CONTROLLO CAMPI VUOTI
            if(timeIn === "" || timeOut === ""){
                alert("Inserisci sia l'orario di ingresso che quello di uscita!");
                return; // BLOCCA L'INVIO
            }

            // INVIO DATI A api_firma.php
            fetch("api/api_firma.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({
                    id_iscritto: idIscritto,
                    ora_ingresso: timeIn,
                    ora_uscita: timeOut,
                    check_firma: check_firma
                })
            })
            .then(res => res.json())
            .then(data => {

                if (!data.success) {
                    alert("Errore nel salvataggio firma");
                    return;
                }

                signPopup.classList.remove("show");
                successPopup.classList.add("show");

                setTimeout(()=>{
                    successPopup.classList.remove("show");
                    overlay.classList.remove("show");
                    document.body.classList.remove("popup-open");
                    location.reload();
                },1800);

            });
        }



    </script>

</body>
</html>
