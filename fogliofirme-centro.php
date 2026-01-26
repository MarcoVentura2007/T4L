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
    <title>T4L | Carousel</title>
    <link rel="icon" href="immagini/Icona.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flickity/2.2.2/flickity.min.css" integrity="sha512-BiFZ6oflftBIwm6lYCQtQ5DIRQ6tm02svznor2GYQOfAlT3pnVJ10xCrU3XuXnUrWQ4EG8GKxntXnYEdKY0Ugg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

              <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png" alt=""></a>
          </div>
      </div>

      <div class="contenuto-principale">
        <h1 class="titolo-principale">Selezionati sullo schermo per firmare!</h1>

        <div class="swiper mySwiper">
            <div class="swiper-wrapper">

                <div class="swiper-slide card-item">
                <a href="">
                    <img src="immagini/Cristian_Moretti.png" alt="">
                    <h3>Cristian Moretti</h3>
                </a>
                </div>

                <div class="swiper-slide card-item">
                <a href="">
                    <img src="immagini/Gabriele_Corona.png" alt="">
                    <h3>Gabriele Corona</h3>
                </a>
                </div>

                <div class="swiper-slide card-item">
                <a href="">
                    <img src="immagini/Jacopo_Bertolasi.png" alt="">
                    <h3>Jacopo Bertolasi</h3>
                </a>
                </div>

                <div class="swiper-slide card-item">
                <a href="">
                    <img src="immagini/Cristian_Moretti.png" alt="">
                    <h3>Cristian Moretti</h3>
                </a>
                </div>

                <div class="swiper-slide card-item">
                <a href="">
                    <img src="immagini/Gabriele_Corona.png" alt="">
                    <h3>Gabriele Corona</h3>
                </a>
                </div>

                <div class="swiper-slide card-item">
                <a href="">
                    <img src="immagini/Jacopo_Bertolasi.png" alt="">
                    <h3>Jacopo Bertolasi</h3>
                </a>
                </div>
            </div>

            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
        </div>


        <script>
            const username_nav = document.getElementById("username-nav") ;
            username_nav.textContent = username;

            var swiper = new Swiper(".mySwiper", {
                slidesPerView: 3,
                spaceBetween: 10,
                centeredSlides: true,
                grabCursor: true,
                loop: false,
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                on: {
                    slideChangeTransitionStart: function () {
                        // Aggiorna opacità e scale
                        this.slides.forEach((slide, index) => {
                            if(index === this.activeIndex) {
                                slide.style.opacity = 1;
                                slide.style.transform = "scale(1)";
                            } else {
                                slide.style.opacity = 0.8;
                                slide.style.transform = "scale(0.8)";
                            }
                        });
                    },
                },
            });

        </script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/flickity/2.2.2/flickity.pkgd.min.js"></script>

</body>
</html>