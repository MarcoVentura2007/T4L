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
    <title>T4L | Privacy Policy</title>
    <link rel="icon" href="immagini/Icona.ico">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: white;
            min-height: 100vh;
            font-size: 1rem;
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
        .cta {
        border: none;
        background: none;
        cursor: pointer;
        margin-left: 40px;
        width: fit-content;
        display: flex;
        align-items: center;
        position: fixed;
        padding-bottom: 6px;
        }

        .cta span {
        padding-bottom: 4px;
        letter-spacing: 4px;
        font-size: 14px;
        padding-right: 1px;
        text-transform: uppercase;
        }

        .cta svg {
        transform: translateX(-8px);
        transition: all 0.3s ease;
        }

        .cta:hover svg {
        transform: translateX(-15px);
        }

        .cta:active svg {
        transform: scale(0.9);
        }

        .hover-underline-animation1 {
        color: black;
        }

        .cta:after {
        content: "";
        position: absolute;
        width: 100%;
        transform: scaleX(0);
        height: 1px;
        bottom: 0;
        left: 0;
        background-color: #000000;
        transform-origin: bottom left;
        transition: transform 0.25s ease-out;
        }

        .cta:hover:after {
        transform: scaleX(1);
        transform-origin: bottom right;
        }

        @media (max-width: 768px) {
            .privacy-container {
                max-width: 95%;
                padding: 1rem;
            }
            .privacy-header h1 {
                font-size: 1.6rem;
            }
            .toc h2{
                font-size: 1.1rem !important;
            }
            body{
                font-size: 0.9rem !important;
            }
            p { 
                font-size: 0.9rem !important;
            }
            .cta span{
                font-size: 12px;
            }
            
        }
    </style>
</head>
<body>

<!-- LOADER TIKTOK-STYLE - Time4All Branded -->
<div id="page-loader" class="show">
<div class="logo-pulse-loader">
    <div class="logo-pulse-ring"></div>
    <div class="logo-pulse-ring"></div>
    <img src="immagini/TIME4ALL_LOGO-removebg-preview.png" alt="Time4All">
</div>

    <p style="margin-top: 30px; color: #640a35; font-size: 0.9rem; font-weight: 500; letter-spacing: 1px;">Caricamento...</p>
</div>

 

<script src="js/loader.js"></script>

<script src="js/loader.js"></script>


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
            <a href="centrodiurno.php"><img src="immagini/Logo-centrodiurno.png"></a>
            <a href="index.php"><img src="immagini/TIME4ALL_LOGO-removebg-preview.png"></a>
            <a href="ergoterapeutica.php"><img src="immagini/Logo-Cooperativa-Ergaterapeutica.png"></a>
        </div>

    

</header>

<!-- CONTENUTO -->

<main class="privacy-main" style="padding-top: 100px;">

    <button class="cta" onclick="window.history.back()">
    <svg
        id="arrow-horizontal"
        xmlns="http://www.w3.org/2000/svg"
        width="30"
        height="10"
        viewBox="0 0 36 16"
    >
        <path
        id="Path_10"
        data-name="Path 10"
        d="M8,0,6.545,1.455l5.506,5.506H-30V9.039H12.052L6.545,14.545,8,16l8-8Z"
        transform="translate(30) scale(-1,1)"
        ></path>
    </svg>
    <span class="hover-underline-animation1"> Indietro </span>
    </button>



    <div class="privacy-container">

    <header class="privacy-header">
        <h1>Privacy Policy</h1>
        <p>
            La presente informativa descrive come Time4All e ASD Overlimits
            trattano i dati personali degli utenti nel rispetto della normativa vigente,
            inclusa la normativa europea GDPR.
        </p>
    </header>

    <nav class="toc">
        <h2>Indice</h2>
        <ul>
            <li><a href="#quadro-giuridico">Quadro giuridico</a></li>
            <li><a href="#dati-raccolti">Dati raccolti e utilizzo</a></li>
            <li><a href="#responsabili">Responsabili del trattamento</a></li>
            <li><a href="#comunicazione">Comunicazione dei dati</a></li>
            <li><a href="#diritti">Diritti dell’utente</a></li>
            <li><a href="#modifiche">Modifiche all’informativa</a></li>
        </ul>
    </nav>

    <section id="quadro-giuridico" class="privacy-section">
        <h2>1. Quadro giuridico</h2>
        <p>
            I Servizi sono gestiti da <strong>ASD Overlimits</strong> (la “Società”, “Noi”),
            con sede in <strong>Via Silvio Pellico 2, 26013, Crema (Italia)</strong>.
        </p>
        <p>
            I servizi sono disciplinati dalle leggi e dai regolamenti italiani.
            La Società opera inoltre in conformità al <strong>Regolamento UE 2016/679 (GDPR)</strong>.
        </p>
    </section>

    <section id="dati-raccolti" class="privacy-section">
        <h2>2. Dati che Time4All raccoglie e come li utilizziamo</h2>

        <p>
            La nostra politica è raccogliere esclusivamente i dati necessari a garantire
            la migliore esperienza possibile a utenti ed educatori, nel pieno rispetto
            della privacy e della sicurezza.
        </p>
        <section class="sotto-sezione">
            <h3>2.1 Creazione di un nuovo account utente</h3>
            <p>
                Ogni utente iscritto alla società ASD Overlimits ha diritto a possedere
                un account personale.
            </p>
            <p>
                La creazione dell’account consente l’accesso ai servizi messi a disposizione
                dalla piattaforma, come ad esempio l’agenda.
            </p>
            <p>
                I dati raccolti vengono utilizzati per associare la persona al profilo,
                catalogare le attività tra utenti ed educatori e, in alcuni casi,
                per finalità di retribuzione.
            </p>
            <p>
                Le informazioni sono archiviate su infrastrutture Cloud fornite da
                <strong>Amazon Web Services (AWS)</strong>, garantendo elevati standard
                di sicurezza.
            </p>

            <h3>2.2 Attività degli account</h3>
            <p>
                Le attività effettuate dagli account (User, Admin e Manager)
                vengono monitorate tramite log di sistema.
            </p>
            <p>
                I log sono accessibili esclusivamente a soggetti autorizzati
                con accesso ai servizi di Amazon Web Services.
            </p>

            <h3>2.3 Localizzazione</h3>
            <p>
                Time4All non accede, non utilizza e non traccia informazioni
                basate sulla posizione del dispositivo dell’utente.
            </p>

            <h3>2.4 Collegamenti ad altri siti web</h3>
            <p>
                La Web-App Time4All non contiene collegamenti a siti web esterni
                al servizio.
            </p>
        </section>
    </section>


    <section id="responsabili" class="privacy-section">
        <h2>3. Responsabili del trattamento dei dati</h2>
        <p>
            I responsabili del trattamento operano esclusivamente per lo scopo
            specifico per cui sono incaricati e non memorizzano dati relativi
            all’uso quotidiano generale degli account.
        </p>

        <div class="highlight">
            <p><strong>Responsabile interno:</strong></p>
            <p>
                <strong>Nicola Bettinelli</strong><br>
                Finalità: Trattamento dei dati degli utenti registrati presso ASD Overlimits<br>
                Luogo di trattamento: Via Matilde di Canossa 15/a – Crema
            </p>
        </div>
    </section>

    <section id="comunicazione" class="privacy-section">
        <h2>4. Comunicazione dei dati</h2>
        <p>
            I dati personali degli utenti non vengono divulgati a terzi,
            salvo nei casi in cui ciò sia imposto da obblighi di legge
            o da richieste vincolanti delle competenti autorità italiane.
        </p>
    </section>

    <section id="diritti" class="privacy-section">
        <h2>5. I tuoi diritti alla privacy</h2>
        <p>
            Tramite le funzionalità dedicate, gli utenti con ruolo di
            Admin e Manager possono accedere ai dati personali trattati,
            modificarli o eliminarli.
        </p>
        <p>
            In caso di violazione dei propri diritti, l’utente ha diritto
            di presentare reclamo all’autorità di controllo competente.
        </p>
    </section>

    <section id="modifiche" class="privacy-section">
        <h2>6. Modifiche all’informativa sulla privacy</h2>
        <p>
            Nei limiti consentiti dalla normativa applicabile,
            la Società si riserva il diritto di modificare la presente
            informativa in qualsiasi momento.
        </p>
        <p>
            L’utilizzo continuato dei Servizi successivamente alle modifiche
            costituisce accettazione delle stesse.
        </p>
    </section>

    <footer style="text-align:center; margin-top:3rem; color:#666;">
        <p>Ultimo aggiornamento: Febbraio 2026</p>
    </footer>

</div>


</main>


<script>    

        const userBox = document.getElementById("userBox");
        const logoutBtn = document.getElementById("logoutBtn");
        const logoutOverlay = document.getElementById("logoutOverlay");
        const logoutModal = document.getElementById("logoutModal");
        const cancelLogout = document.getElementById("cancelLogout");
        const confirmLogout = document.getElementById("confirmLogout");

        userBox.addEventListener("click", (e)=>{
            e.stopPropagation();
            document.getElementById("userDropdown").classList.toggle("show");
        });

        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            logoutOverlay.classList.add("show");
            logoutModal.classList.add("show");
        });

        cancelLogout.onclick = closeLogout;
        logoutOverlay.onclick = closeLogout;

        function closeLogout(){
            logoutOverlay.classList.remove("show");
            logoutModal.classList.remove("show");
        }

        confirmLogout.onclick = () => {
            window.location.href = "logout.php";
        };

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
