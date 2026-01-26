<?php
session_start();

// Se l'utente non è loggato → redirect a login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>T4L | Sezioni</title>
    <link rel="icon" href="immagini/Icona.ico">
    <link rel="stylesheet" href="style.css">
</head>
<body style="overflow: hidden;">


  
  <!-- NAVIGAZIONE -->
  <div id="navigazione">
      
      <div class="username">
          <a href="login.php"><img src="immagini/profile-picture.png" alt=""></a>
          <p id="username-text"><span id="username-nav"></span></p>
      </div>

      <div class="nav-wrapper">
          <img src="immagini/Logo-centrodiurno.png" alt="">
          <a href="index.php"><img src="immagini/TIME4ALL_LOGO-removebg-preview.png" alt=""></a>
          <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png" alt="">
      </div>

      <!-- HAMBURGER MENU -->
      <div class="menu-hamburger" id="menuHamburger">
          <span></span>
          <span></span>
      </div>

      <!-- DROPDOWN -->
      <div class="menu-dropdown hidden" id="menuDropdown">
          <p data-link="centrodiurno.php" class="data-centro"><img src="immagini/Logo-centrodiurno.png" alt="" class="img_ham">Centro Diurno</p>
          <p data-link="index-ergo.php" class="data-ergo"><img src="immagini/Logo-Cooperativa-Ergaterapeutica.png" alt="" class="img_ham">Ergoterapeutica</p>
      </div>
  </div>

  <!-- CONTENUTO PRINCIPALE -->
  <div class="contenuto-principale">
      <h1 class="titolo-principale" id="titolo-principale">Bentornato, 
          <span id="nomeutente"></span>.
      </h1>

      <h2 class="sottotitolo-principale">Cosa desideri fare?</h2>

      <div class="tow-columns" id="scelta-operazioni">
          <div class="column scelta">
              <a href="fogliofirme-centro.php">
                  <img src="immagini/foglio-over.png" alt="">
                  <h2 class="titolo-paragrafo">Foglio Firme</h2>
              </a>
          </div>

          <div class="column scelta">
              <img src="immagini/gestionale-over.png" alt="">
              <h2 class="titolo-paragrafo">Gestionale</h2>
          </div>
      </div>
  </div>

  <script>
      const nomeutente = document.getElementById("nomeutente") ;
      nomeutente.textContent = username;

      const username_nav = document.getElementById("username-nav") ;
      username_nav.textContent = username;
      

      const menuBtn = document.getElementById("menuHamburger");
      const dropdown = document.getElementById("menuDropdown");

      menuBtn.addEventListener("click", () => {
          menuBtn.classList.toggle("active");
          dropdown.classList.toggle("hidden");
      });

      // click sulle voci -> cambia pagina
      dropdown.querySelectorAll("p").forEach(item => {
          item.addEventListener("click", () => {
              window.location.href = item.getAttribute("data-link");
          });
      });


  </script>

</body>
</html>
