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

</head>

<body style="overflow:hidden;">

<!-- NAVBAR -->
<header class="navbar">

    <div class="user-box">
        <img src="immagini/profile-picture.png">
        <span id="username-nav"><?php echo htmlspecialchars($username); ?></span>
    </div>

    <div class="logo-area">
        <img src="immagini/Logo-centrodiurno.png">
        <img src="immagini/TIME4ALL_LOGO-removebg-preview.png">
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


<script>

/* MENU */

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

</script>

</body>
</html>
