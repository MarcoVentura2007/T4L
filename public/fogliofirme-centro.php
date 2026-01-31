<?php
    session_start();

    // Redirect a login.php
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit;
    }

    $username = $_SESSION['username'];

    // Connessione al DB XAMPP
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "time4all";

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }

    // Preleva i profili
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
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body style="overflow:hidden;">

        <header class="navbar">
            <div class="user-box" id="userBox">
                <img src="immagini/profile-picture.png">
                <span><?php echo htmlspecialchars($username); ?></span>
                <div class="user-dropdown" id="userDropdown">
                    <a href="impostazioni.php">⚙ Impostazioni</a>
                    <a href="logout.php" class="danger">⏻ Logout</a>
                </div>
            </div>
        </header>

        <main class="carousel-dashboard">
            <h1 class="carousel-title">Selezionati sullo schermo per firmare</h1>
            <p class="carousel-subtitle">Scorri le schede e clicca sul profilo desiderato</p>

            <div class="swiper mySwiper">
                <div class="swiper-wrapper">
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo '
                            <div class="swiper-slide profile-card" data-id="'.$row['id'].'">
                                <img src="'.htmlspecialchars($row['fotografia']).'">
                                <h3>'.htmlspecialchars($row['nome']).' '.htmlspecialchars($row['cognome']).'</h3>
                            </div>';
                        }
                    }
                    ?>
                </div>

                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>

            <!-- OVERLAY -->
            <div class="popup-overlay" id="popupOverlay"></div>

            <!-- ORARI -->
            <div class="popup big" id="timePopup">
                <div class="popup-content">
                    <div class="popup-left">
                        <img id="popupUserImg">
                        <h3 id="popupUserName"></h3>
                    </div>
                    <div class="popup-right">
                        <h2>Inserisci orari</h2>
                        <label>Ora ingresso</label>
                        <input type="time" id="timeIn">
                        <label>Ora uscita</label>
                        <input type="time" id="timeOut">
                        <button id="goSignature">Continua</button>
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
                        <h2>Firma qui sotto</h2>
                        <canvas id="signatureCanvas"></canvas>
                        <button id="clearSign">Pulisci</button>
                        <button class="btn-confirm">Conferma</button>
                    </div>
                </div>
            </div>

            <!-- SUCCESS -->
            <div class="popup success-popup" id="successPopup">
                <p>Firma completata!</p>
            </div>
        </main>

    <script>
        /* SWIPER */
        new Swiper(".mySwiper", {
            slidesPerView: 3,
            centeredSlides: true,
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev"
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true
            }
        });

        /* POPUP */
        const overlay = document.getElementById("popupOverlay");
        const timePopup = document.getElementById("timePopup");
        const signPopup = document.getElementById("signaturePopup");
        const successPopup = document.getElementById("successPopup");

        let selectedIdIscritto = null;

        document.querySelectorAll(".profile-card").forEach(card => {
            card.onclick = () => {
                selectedIdIscritto = card.dataset.id;
                popupUserImg.src = popupUserImg2.src = card.querySelector("img").src;
                popupUserName.textContent = popupUserName2.textContent = card.querySelector("h3").textContent;

                overlay.classList.add("show");
                timePopup.classList.add("show");
            };
        });

        goSignature.onclick = () => {
            timePopup.classList.remove("show");
            signPopup.classList.add("show");
        };

        /* FIRMA */
        const canvas = document.getElementById("signatureCanvas");
        const ctx = canvas.getContext("2d");
        let drawing = false;

        canvas.onpointerdown = e => { drawing = true; ctx.beginPath(); ctx.moveTo(e.offsetX,e.offsetY); };
        canvas.onpointermove = e => { if(drawing){ ctx.lineTo(e.offsetX,e.offsetY); ctx.stroke(); }};
        canvas.onpointerup = () => drawing=false;

        clearSign.onclick = () => ctx.clearRect(0,0,canvas.width,canvas.height);

        /* INVIO FIRMA */
        document.querySelector(".btn-confirm").onclick = () => {
            fetch("/api/api_firma.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({
                    id_iscritto: selectedIdIscritto,
                    ora_ingresso: timeIn.value,
                    ora_uscita: timeOut.value,
                    check_firma: 1
                })
            })
            .then(r => r.json())
            .then(res => {
                if(!res.success){
                    alert("Errore salvataggio");
                    return;
                }
                signPopup.classList.remove("show");
                successPopup.classList.add("show");

                setTimeout(()=>{
                    successPopup.classList.remove("show");
                    overlay.classList.remove("show");
                },1500);
            });
        };
    </script>

    </body>
</html>
