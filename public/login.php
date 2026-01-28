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
      <div id="notify" class="notify hidden">
        <div class="icon" id="notify-icon"></div>
        <div class="text" id="notify-text"></div>
      </div>

    </form>
  </div>


    <script>
 
const form = document.getElementById('login-form');
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

// Funzione per mostrare banner professionale con entrata/uscita
function showNotification(success = true, message = "Messaggio") {
    const notify = document.createElement('div');
    notify.classList.add('notify');
    notify.classList.add(success ? 'success' : 'error');

    const iconWrapper = document.createElement('div');
    iconWrapper.classList.add('icon-wrapper');

    const circle = document.createElement('div');
    circle.classList.add('circle');
    iconWrapper.appendChild(circle);

    const icon = document.createElement('span');
    icon.classList.add('icon');
    icon.textContent = success ? "✔" : "✖";
    iconWrapper.appendChild(icon);

    notify.appendChild(iconWrapper);

    const text = document.createElement('span');
    text.textContent = message;
    notify.appendChild(text);

    document.body.appendChild(notify);

    // Mostra con animazione
    setTimeout(() => notify.classList.add('show'), 10);

    // Nascondi dopo 3 secondi con animazione uscita
    setTimeout(() => {
        notify.classList.remove('show');
        notify.classList.add('hide');
        notify.addEventListener('animationend', () => notify.remove());
    }, 3000);
}

// Login AJAX
form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;

    try {
        const response = await fetch('./api/api_login.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest' // <-- questo serve per il blocco
        },
        body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
    });


        const result = await response.json();

        if (result.success) {
            showNotification(true, "Login avvenuto con successo");
            setTimeout(() => { window.location.href = 'index.php'; }, 1000);
        } else {
            showNotification(false, result.message);
        }
    } catch (err) {
        showNotification(false, "Errore di connessione al server");
        console.error(err);
    }
});


</script>


</body>
</html>
