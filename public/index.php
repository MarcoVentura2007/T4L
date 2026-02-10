<?php
session_start();

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

<title>T4L | Sezioni</title>

<link rel="icon" href="immagini/Icona.ico">
<link rel="stylesheet" href="style.css">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#2B2B30">

<script src="https://cdn.tailwindcss.com"></script>

<style>
    @media (max-width: 768px) {
        .logo-area img:nth-child(2) {
            display: block;
            max-width: 50px;
        }
    }
</style>
</head>

<body >

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
        <img src="immagini/Logo-centrodiurno.png">
        <img src="immagini/TIME4ALL_LOGO-removebg-preview.png">
        <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png">
    </div>

    

</header>


<!-- CONTENUTO -->

<main class="sections-dashboard">

    <h1 class="sections-title">Scegli la sezione</h1>

    <p class="sections-subtitle">
        Accedi rapidamente all’area di lavoro desiderata
    </p>

    <section class="sections-cards">

        <a href="centrodiurno.php" class="section-card">
            <img src="immagini/Logo-centrodiurno.png">
        </a>

        <a href="#" class="section-card disabled">
            <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png">
            <span>In arrivo</span>
        </a>

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

/* MENU */


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
