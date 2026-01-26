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

    <div class="user-box">
        <img src="immagini/profile-picture.png">
        <span id="username-nav"><?php echo htmlspecialchars($username); ?></span>
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

</script>

</body>
</html>
