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
    <title>Privacy Policy - Time4All</title>
    <link rel="icon" href="immagini/Icona.ico">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: white;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            display: flex;
            flex-direction: column;
        }
        .privacy-main {
            flex: 1;
        }
        .privacy-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .privacy-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }
        .privacy-header h1 {
            color: #2B2B30;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .privacy-header p {
            color: #666;
            font-size: 1rem;
            line-height: 1.5;
        }
        .toc {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }
        .toc h2 {
            color: #2B2B30;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        .toc ul {
            list-style: none;
            padding: 0;
        }
        .toc li {
            margin-bottom: 0.5rem;
        }
        .toc a {
            color: #007bff;
            text-decoration: none;
        }
        .toc a:hover {
            text-decoration: underline;
        }
        .privacy-section {
            margin-bottom: 2rem;
        }
        .privacy-section h2 {
            color: #2B2B30;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        .privacy-section p {
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        .privacy-section ul {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        .privacy-section li {
            margin-bottom: 0.5rem;
        }
        .highlight {
            background: #f0f8ff;
            padding: 1rem;
            border-left: 4px solid #007bff;
            margin: 1rem 0;
        }
        .contact-info {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
        }
        @media (max-width: 768px) {
            .privacy-container {
                max-width: 95%;
                padding: 1rem;
            }
            .privacy-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
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
        <img src="immagini/TIME4ALL_LOGO-removebg-preview.png">
        <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png">
    </div>

</header>

<!-- CONTENUTO -->

<main class="privacy-main" style="padding-top: 100px;">

    <div class="page-header" style="text-align: left; margin-bottom: 2rem;">
        <h1>Privacy Policy</h1>
        <p>La tua privacy è importante per noi</p>
    </div>

    <div class="privacy-container">
    <header class="privacy-hero">
        <h1>Privacy Policy</h1>
        <p>
            Questa informativa sulla privacy descrive come raccogliamo, utilizziamo e proteggiamo le tue informazioni personali quando utilizzi i nostri servizi. Ci impegniamo a rispettare la tua privacy e a trattare i tuoi dati con la massima cura e responsabilità.
        </p>
    </header>

    <nav class="toc">
        <h2>Indice</h2>
        <ul>
            <li><a href="#introduzione">Introduzione</a></li>
            <li><a href="#dati-raccolti">Dati Personali Raccolti</a></li>
            <li><a href="#finalita">Finalità del Trattamento</a></li>
            <li><a href="#condivisione">Condivisione dei Dati</a></li>
            <li><a href="#sicurezza">Sicurezza dei Dati</a></li>
            <li><a href="#conservazione">Conservazione dei Dati</a></li>
            <li><a href="#diritti">I Tuoi Diritti</a></li>
            <li><a href="#contatti">Contatti</a></li>
        </ul>
    </nav>

    <section id="introduzione" class="privacy-section">
        <h2>Introduzione</h2>
        <p>
            Benvenuto nella nostra Privacy Policy. Questa informativa spiega come la nostra organizzazione, Time4All, raccoglie, utilizza, divulga e protegge le informazioni personali che ci fornisci quando utilizzi i nostri servizi online e offline.
        </p>
        <p>
            Ci impegniamo a proteggere la tua privacy e a garantire che le tue informazioni personali siano gestite in conformità con le leggi applicabili sulla protezione dei dati, inclusa la normativa europea GDPR.
        </p>
    </section>

    <section id="dati-raccolti" class="privacy-section">
        <h2>Dati Personali Raccolti</h2>
        <p>
            Raccogliamo diversi tipi di informazioni personali per fornire e migliorare i nostri servizi:
        </p>
        <ul>
            <li><strong>Informazioni di identificazione:</strong> nome, cognome, indirizzo email, numero di telefono</li>
            <li><strong>Dati sanitari:</strong> informazioni mediche necessarie per i servizi di assistenza</li>
            <li><strong>Dati di utilizzo:</strong> informazioni su come utilizzi i nostri servizi online</li>
            <li><strong>Dati tecnici:</strong> indirizzo IP, tipo di browser, sistema operativo</li>
        </ul>
        <div class="highlight">
            <p><strong>Nota importante:</strong> Tutti i dati sanitari sono trattati con la massima riservatezza e utilizzati esclusivamente per fornire cure appropriate e personalizzate.</p>
        </div>
    </section>

    <section id="finalita" class="privacy-section">
        <h2>Finalità del Trattamento</h2>
        <p>
            Utilizziamo le tue informazioni personali per le seguenti finalità:
        </p>
        <ul>
            <li>Fornire servizi di assistenza e supporto personalizzati</li>
            <li>Gestire le prenotazioni e gli appuntamenti</li>
            <li>Comunicare aggiornamenti importanti sui servizi</li>
            <li>Migliorare la qualità dei nostri servizi attraverso analisi statistiche</li>
            <li>Adempiere agli obblighi legali e normativi</li>
        </ul>
        <p>
            Il trattamento dei dati avviene sempre nel rispetto dei principi di liceità, correttezza e trasparenza stabiliti dalla normativa vigente.
        </p>
    </section>

    <section id="condivisione" class="privacy-section">
        <h2>Condivisione dei Dati</h2>
        <p>
            Non vendiamo, affittiamo o condividiamo le tue informazioni personali con terze parti per scopi commerciali. Le tue informazioni possono essere condivise solo nelle seguenti circostanze:
        </p>
        <ul>
            <li>Con il tuo consenso esplicito</li>
            <li>Con fornitori di servizi che ci aiutano a operare (sempre con garanzie di protezione)</li>
            <li>Quando richiesto dalla legge o per proteggere i diritti legali</li>
            <li>In caso di fusione o acquisizione aziendale</li>
        </ul>
    </section>

    <section id="sicurezza" class="privacy-section">
        <h2>Sicurezza dei Dati</h2>
        <p>
            Implementiamo misure di sicurezza tecniche e organizzative appropriate per proteggere le tue informazioni personali contro accessi non autorizzati, alterazioni, divulgazioni o distruzioni.
        </p>
        <p>
            Utilizziamo crittografia per la trasmissione di dati sensibili, controlli di accesso rigorosi e monitoraggio continuo dei nostri sistemi.
        </p>
    </section>

    <section id="conservazione" class="privacy-section">
        <h2>Conservazione dei Dati</h2>
        <p>
            Conserviamo le tue informazioni personali solo per il tempo necessario a raggiungere le finalità per cui sono state raccolte, o come richiesto dalla legge.
        </p>
        <ul>
            <li>Dati sanitari: conservati per 10 anni dopo la fine del rapporto</li>
            <li>Dati di contatto: conservati fino alla revoca del consenso</li>
            <li>Dati tecnici: conservati per massimo 2 anni</li>
        </ul>
    </section>

    <section id="diritti" class="privacy-section">
        <h2>I Tuoi Diritti</h2>
        <p>
            Hai il diritto di:
        </p>
        <ul>
            <li><strong>Accedere</strong> alle tue informazioni personali</li>
            <li><strong>Rettificare</strong> dati inesatti o incompleti</li>
            <li><strong>Cancellare</strong> i tuoi dati personali</li>
            <li><strong>Limitare</strong> il trattamento in determinate circostanze</li>
            <li><strong>Opporsi</strong> al trattamento per motivi legittimi</li>
            <li><strong>Portabilità</strong> dei dati in formato strutturato</li>
        </ul>
        <p>
            Per esercitare questi diritti, contattaci utilizzando le informazioni fornite di seguito.
        </p>
    </section>

    <section id="contatti" class="privacy-section">
        <h2>Contatti</h2>
        <p>
            Se hai domande sulla nostra Privacy Policy o desideri esercitare i tuoi diritti, puoi contattarci:
        </p>
        <div class="contact-info">
            <p><strong>Time4All Cooperativa Sociale</strong></p>
            <p>Email: privacy@time4all.it</p>
            <p>Telefono: +39 0123 456789</p>
            <p>Indirizzo: Via Esempio 123, 00100 Roma, Italia</p>
        </div>
        <p>
            Risponderemo alle tue richieste entro 30 giorni dalla ricezione.
        </p>
    </section>

    <footer style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #f0f0f0; color: #666;">
        <p>Ultimo aggiornamento: Dicembre 2024</p>
    </footer>
    </div>

</main>


<script>
// Smooth scrolling for table of contents
document.querySelectorAll('.toc a').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add fade-in animation
document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('.privacy-section');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });

    sections.forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(section);
    });
});
</script>

</body>
</html>
