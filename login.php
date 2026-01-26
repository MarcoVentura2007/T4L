<?php
session_start();
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>T4L | Login</title>
    <link rel="icon" href="immagini/Icona.ico">
    <link rel="stylesheet" href="style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2B2B30">
</head>
<body style="overflow: hidden;">
  <img src="immagini/top-right.png" alt="" class="top-right">
  <img src="immagini/bottom-left.png" alt="" class="bottom-left">
  <div class="three-columns">
    <div class="col">
      <img src="immagini/overlimits.png" alt="" class="col-image" style="max-width: 75%; float: right; margin-right: 17px;">
    </div>
    <div class="col">
      <img src="immagini/TIME4ALL_LOGO-removebg-preview.png" alt="" class="col-image">
    </div>
    <div class="col">
      <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png" alt="" class="col-image" style="margin-bottom: 5px;">
    </div>
  </div>

  <div class="login-container">
    <form class="login-form" id="login-form">
      <h2 id="titolo-login">LOGIN</h2>
      <div class="input-wrap">
        <input id="username" class="colored-input" type="text" autocomplete="off" name="username" placeholder="Username" required>
      </div>
      <div class="input-wrap">
        <input id="password" class="colored-input" type="password" autocomplete="off" name="password" placeholder="Password" required>
        <img draggable="false" id="eyeicon" src="immagini/view.png" style="cursor:pointer;" />
      </div>
      <button class="login-button">Accedi</button>
      <div id="message" style="opacity:0;"></div>
    </form>
  </div>

  <script>
    const form = document.getElementById('login-form');
    const messageDiv = document.getElementById('message');
    const eyeicon = document.getElementById('eyeicon');
    const passwordInput = document.getElementById('password');

    // Mostra/nascondi password
    eyeicon.onclick = () => {
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyeicon.src = "immagini/hide.png";
      } else {
        passwordInput.type = "password";
        eyeicon.src = "immagini/view.png";
      }
    };

    // login/registrazione
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;

      try {
        const response = await fetch('api/api_login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        });

        const result = await response.json();

        messageDiv.textContent = result.message;
        messageDiv.style.opacity = "1";
        messageDiv.style.color = result.success ? "green" : "red";

        if (result.success) {
          setTimeout(() => {
            window.location.href = 'index.php';
          }, 300);
        }
      } catch (err) {
        messageDiv.style.color = 'red';
        messageDiv.textContent = 'Errore di connessione al server';
        messageDiv.style.opacity = "1";
        console.error(err);
      }
    });
  </script>
</body>
</html>
