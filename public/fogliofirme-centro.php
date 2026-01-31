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

// Preleva i profili dal DB
$sql = "SELECT id, nome, cognome, fotografia FROM iscritto ORDER BY cognome ASC";
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
            <div data-link="centrodiurno.php" class="data-link-centro">
                <img src="immagini/Logo-centrodiurno.png"> Centro Diurno
            </div>
            <div data-link="#" class="data-link-ergo">
                <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png"> Ergoterapeutica
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
                    echo '<p>Nessun utente trovato.</p>';
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

    <script>

        
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
        drop.querySelectorAll("div").forEach(item => {
            item.onclick = () => {
                window.location.href = item.dataset.link;
            }
        });
        document.addEventListener("click", e => {
            if(!ham.contains(e.target) && !drop.contains(e.target)){
                ham.classList.remove("active");
                drop.classList.remove("show");
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
        const overlay = document.getElementById("popupOverlay");
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
            },1800); 
        })
        .catch(error => {
            console.error("Errore:", error);
            alert("Errore nella richiesta");
        });
    };

    </script>

</body>
</html>
