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

<title>T4L | Dashboard</title>

<link rel="stylesheet" href="style.css">
<link rel="icon" href="immagini/Icona.ico">

</head>
<body>

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
        <img src="immagini/Logo-centrodiurno.png">
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
            <img src="immagini/Logo-centrodiurno.png"> 
            Centro Diurno
        </div>
        <div data-link="#" class="data-link-ergo">
            <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png">
            Ergoterapeutica
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
        <a href="attivita-centro.php" class="card" style=" opacity: 0.7;">
            <img src="immagini/gestionale-over.png" style="height: 140px;">
            <h3>Gestionale</h3>
            <span>In arrivo</span>
        </a>

    </section>

</main>


<script>

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
