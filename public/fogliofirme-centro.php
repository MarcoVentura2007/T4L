<?php
session_start();

// Se l'utente non è loggato → redirect a login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
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


<!-- CONTENUTO -->

<main class="carousel-dashboard">

    <h1 class="carousel-title">
        Selezionati sullo schermo per firmare
    </h1>

    <p class="carousel-subtitle">
        Scorri le schede e clicca sul profilo desiderato
    </p>

    <div class="swiper mySwiper">

        <div class="swiper-wrapper">

            <div class="swiper-slide profile-card">
                <a href="#">
                    <img src="immagini/Cristian_Moretti.png">
                    <h3>Cristian Moretti</h3>
                </a>
            </div>

            <div class="swiper-slide profile-card">
                <a href="#">
                    <img src="immagini/Gabriele_Corona.png">
                    <h3>Gabriele Corona</h3>
                </a>
            </div>

            <div class="swiper-slide profile-card">
                <a href="#">
                    <img src="immagini/Jacopo_Bertolasi.png">
                    <h3>Jacopo Bertolasi</h3>
                </a>
            </div>

            <div class="swiper-slide profile-card">
                <a href="#">
                    <img src="immagini/Cristian_Moretti.png">
                    <h3>Cristian Moretti</h3>
                </a>
            </div>

            <div class="swiper-slide profile-card">
                <a href="#">
                    <img src="immagini/Gabriele_Corona.png">
                    <h3>Gabriele Corona</h3>
                </a>
            </div>

            <div class="swiper-slide profile-card">
                <a href="#">
                    <img src="immagini/Jacopo_Bertolasi.png">
                    <h3>Jacopo Bertolasi</h3>
                </a>
            </div>

        </div>

        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>

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

    // Chiudi popup
    cancelLogout.onclick = closeLogout;
    logoutOverlay.onclick = closeLogout;

    function closeLogout(){
        logoutOverlay.classList.remove("show");
        logoutModal.classList.remove("show");
    }

    // Conferma logout
    confirmLogout.onclick = () => {
        window.location.href = "logout.php";
    };

</script>

</body>
</html>