<?php
session_start();

// Se l'utente non è loggato → redirect a login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
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

<<<<<<< HEAD:public/fogliofirme-centro.html
<body style="overflow:hidden;">

<script>
const username = sessionStorage.getItem("username");
if(!username || username.trim()===""){
    window.location.href="login.html";
}
</script>

<!-- NAVBAR (NON TOCCATA) -->
<header class="navbar">

    <div class="user-box">
        <img src="immagini/profile-picture.png">
        <span id="username-nav"></span>
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
        <div data-link="centrodiurno.html">
            <img src="immagini/Logo-centrodiurno.png"> Centro Diurno
=======

  
  <!-- NAVIGAZIONE -->
      <div id="navigazione">
        <div class="username">
            <a href="login.php"><img src="immagini/profile-picture.png" alt=""></a>
             <p id="username-text"><span id="username-nav"></span></p>
>>>>>>> 1b6796a96fa400fc0cd8b216e2433a416a563389:fogliofirme-centro.php
        </div>
        <div data-link="index-ergo.html">
            <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png"> Ergoterapeutica
        </div>
    </div>

<<<<<<< HEAD:public/fogliofirme-centro.html
</header>
=======
              <a href="index.php"><img src="immagini/TIME4ALL_LOGO-removebg-preview.png" alt=""></a>
>>>>>>> 1b6796a96fa400fc0cd8b216e2433a416a563389:fogliofirme-centro.php


<!-- CONTENUTO -->

<main class="carousel-dashboard">

    <h1 class="carousel-title">
        Seleziona la persona per firmare
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

<<<<<<< HEAD:public/fogliofirme-centro.html
    </div>
=======
        <script>
            let username;
            const username_nav = document.getElementById("username-nav") ;
            username_nav.textContent = username;
>>>>>>> 1b6796a96fa400fc0cd8b216e2433a416a563389:fogliofirme-centro.php

</main>


<script>

document.getElementById("username-nav").textContent = username;

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
