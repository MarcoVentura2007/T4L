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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
            max-width: 860px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            margin-bottom: 3rem;
        }

        .privacy-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
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
            line-height: 1.6;
            max-width: 640px;
            margin: 0 auto 1rem;
        }

        .privacy-header .meta {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 0.75rem;
        }

        .privacy-header .meta span {
            font-size: 0.8rem;
            color: #640a35;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .privacy-header .meta span::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #640a35;
            display: inline-block;
        }

        .toc {
            background: #f9f9f9;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            margin-bottom: 2.5rem;
            border: 1px solid #640a35;
        }

        .toc h2 {
            color: #2B2B30;
            margin-bottom: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .toc ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 2rem;
        }

        .toc li a {
            color: #555;
            text-decoration: none;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 0;
            transition: color 0.15s;
        }

        .toc li a:hover {
            color: #640a35;
        }

        .toc-num {
            font-size: 0.75rem;
            color: #bbb;
            font-weight: 600;
            min-width: 16px;
        }

        .privacy-section {
            margin-bottom: 2.5rem;
            opacity: 0;
            transform: translateY(16px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .privacy-section.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .privacy-section h2 {
            color: #2B2B30;
            font-size: 1.35rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.6rem;
            border-bottom: 2px solid #f0e6eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .privacy-section h2 .sec-num {
            width: 26px;
            height: 26px;
            background: #640a35;
            color: white;
            font-size: 0.72rem;
            font-weight: 700;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .privacy-section h3 {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin: 1.5rem 0 0.6rem;
        }

        .privacy-section p {
            line-height: 1.75;
            margin-bottom: 1rem;
            color: #444;
            font-size: 0.97rem;
        }

        .privacy-section p:last-child {
            margin-bottom: 0;
        }

        .highlight {
            background: #fdf5f7;
            padding: 1rem 1.25rem;
            border-left: 4px solid #640a35;
            margin: 1rem 0;
            border-radius: 0 6px 6px 0;
        }

        .highlight .hl-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #640a35;
            margin-bottom: 0.4rem;
        }

        .highlight p {
            margin: 0 !important;
            font-size: 0.92rem !important;
        }

        .rights-list {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0 1rem;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .rights-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.95rem;
            color: #444;
            line-height: 1.7;
        }

        .rights-list li::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #640a35;
            margin-top: 8px;
            flex-shrink: 0;
        }

        .contact-info {
            background: #f9f9f9;
            padding: 1.25rem 1.5rem;
            border-radius: 8px;
            border: 1px solid #ebebeb;
            margin-top: 2rem;
        }

        .contact-info .contact-name {
            font-weight: 600;
            color: #2B2B30;
            font-size: 0.97rem;
            margin-bottom: 0.4rem;
        }

        .contact-info p {
            font-size: 0.88rem !important;
            color: #666;
            margin: 0 !important;
            line-height: 1.7 !important;
        }

        .gdpr-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #edf7ed;
            border: 1px solid #c3e6c3;
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 0.78rem;
            color: #2e7d32;
            font-weight: 500;
        }

        .privacy-page-footer {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
            color: #aaa;
            font-size: 0.82rem;
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
            background-color: #000;
            transform-origin: bottom left;
            transition: transform 0.25s ease-out;
        }

        .cta:hover:after {
            transform: scaleX(1);
            transform-origin: bottom right;
        }

        @media (max-width: 768px) {
            .privacy-container {
                padding: 1rem 1.25rem;
            }

            .privacy-header h1 {
                font-size: 1.6rem;
            }

            .toc ul {
                grid-template-columns: 1fr;
            }

            .cta span {
                font-size: 12px;
            }

            body {
                font-size: 0.92rem;
            }

            p {
                font-size: 0.9rem !important;
            }
        }
    </style>
</head>

<body>

    <div id="page-loader" class="show">
        <div class="logo-pulse-loader">
            <div class="logo-pulse-ring"></div>
            <div class="logo-pulse-ring"></div>
            <img src="immagini/TIME4ALL_LOGO-removebg-preview.png" alt="Time4All">
        </div>
        <p style="margin-top: 30px; color: #640a35; font-size: 0.9rem; font-weight: 500; letter-spacing: 1px;">Caricamento...</p>
    </div>

    <script src="js/loader.js"></script>

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

    <main class="privacy-main" style="padding-top: 100px;">

        <button class="cta" onclick="window.history.back()">
            <svg id="arrow-horizontal" xmlns="http://www.w3.org/2000/svg" width="30" height="10" viewBox="0 0 36 16">
                <path d="M8,0,6.545,1.455l5.506,5.506H-30V9.039H12.052L6.545,14.545,8,16l8-8Z"
                    transform="translate(30) scale(-1,1)"></path>
            </svg>
            <span class="hover-underline-animation1"> Indietro </span>
        </button>

        <div class="privacy-container">

            <header class="privacy-header">
                <h1>Informativa sulla Privacy</h1>
                <p>
                    Il presente documento descrive come <strong>Time4All</strong> e <strong>ASD Overlimits</strong>
                    trattano i dati personali degli utenti nel rispetto della normativa vigente,
                    inclusa la normativa europea GDPR.
                </p>
                <div class="meta">
                    <span>Ultimo aggiornamento: Febbraio 2026</span>
                    <span>ASD Overlimits — Crema (CR)</span>
                    <span>Regolamento UE 2016/679 — GDPR</span>
                </div>
            </header>

            <nav class="toc">
                <h2>Indice</h2>
                <ul>
                    <li><a href="#quadro-giuridico"><span class="toc-num">1</span> Quadro giuridico</a></li>
                    <li><a href="#dati-raccolti"><span class="toc-num">2</span> Dati raccolti e utilizzo</a></li>
                    <li><a href="#responsabili"><span class="toc-num">3</span> Responsabili del trattamento</a></li>
                    <li><a href="#comunicazione"><span class="toc-num">4</span> Comunicazione dei dati</a></li>
                    <li><a href="#diritti"><span class="toc-num">5</span> Diritti dell'interessato</a></li>
                    <li><a href="#modifiche"><span class="toc-num">6</span> Modifiche all'informativa</a></li>
                </ul>
            </nav>

            <section id="quadro-giuridico" class="privacy-section">
                <h2><span class="sec-num">1</span> Quadro giuridico</h2>
                <p>
                    I Servizi sono gestiti da <strong>ASD Overlimits</strong>, con sede in
                    <strong>Via Silvio Pellico 2, 26013, Crema (Italia)</strong>.
                    Essi sono disciplinati dalle leggi e dai regolamenti italiani.
                </p>
                <p>
                    La Società opera in piena conformità al <strong>Regolamento UE 2016/679 (GDPR)</strong>
                    in materia di protezione dei dati personali, garantendo il rispetto dei principi di
                    liceità, correttezza e trasparenza nel trattamento.
                </p>
            </section>

            <section id="dati-raccolti" class="privacy-section">
                <h2><span class="sec-num">2</span> Finalità del trattamento e dati raccolti</h2>
                <p>
                    I dati (inclusi quelli personali) vengono raccolti al fine di garantire la migliore
                    esperienza possibile a tutti gli utilizzatori, dagli utenti agli educatori, nel massimo
                    rispetto della privacy e della sicurezza delle informazioni inserite.
                    La raccolta è limitata a quanto strettamente necessario.
                </p>

                <h3>2.1 — Creazione account utente</h3>
                <p>
                    ASD Overlimits garantisce la possibilità di registrazione di un account personale
                    per la fruizione dei servizi messi a disposizione, tra cui l'agenda e gli strumenti
                    di gestione delle attività.
                </p>
                <p>
                    I dati raccolti vengono utilizzati per associare la persona al proprio profilo,
                    per catalogare le attività tra utenti ed educatori e, in alcuni casi, per finalità
                    di retribuzione. Le informazioni vengono gestite nel rispetto degli standard di settore,
                    su infrastrutture Cloud <strong>Amazon Web Services (AWS)</strong>.
                </p>

                <h3>2.2 — Attività degli account</h3>
                <p>
                    Tutte le attività degli account (User, Admin e Manager) vengono monitorate tramite
                    log di sistema al fine di garantire la massima sicurezza e integrità dei dati.
                    Tali informazioni sono accessibili esclusivamente dal personale autorizzato con
                    accesso ai servizi AWS.
                </p>

                <h3>2.3 — Localizzazione</h3>
                <p>
                    Time4All non accede, non utilizza e non traccia in alcun modo informazioni basate
                    sulla posizione geografica del dispositivo dell'utente.
                </p>

                <h3>2.4 — Collegamenti a siti esterni</h3>
                <p>
                    La Web-App Time4All non contiene collegamenti a siti web esterni al servizio.
                </p>
            </section>

            <section id="responsabili" class="privacy-section">
                <h2><span class="sec-num">3</span> Responsabili del trattamento dei dati</h2>
                <p>
                    I responsabili del trattamento operano esclusivamente per lo scopo specifico per cui
                    sono incaricati e non memorizzano né tengono traccia dei dati al di fuori di tale scopo.
                </p>
                <div class="highlight">
                    <div class="hl-label">Responsabile interno</div>
                    <p>
                        <strong>Nicola Bettinelli</strong><br>
                        Finalità: Trattamento dei dati degli utenti registrati presso ASD Overlimits.<br>
                        Luogo di trattamento: Via Matilde di Canossa 15/a – 26013 Crema (CR)
                    </p>
                </div>
            </section>

            <section id="comunicazione" class="privacy-section">
                <h2><span class="sec-num">4</span> Comunicazione dei dati</h2>
                <p>
                    I dati personali degli utenti non vengono divulgati a terzi. La Società si riserva
                    di comunicarli esclusivamente nei casi in cui ciò sia imposto da obblighi di legge
                    o da richieste vincolanti provenienti dalle competenti autorità italiane.
                </p>
            </section>

            <section id="diritti" class="privacy-section">
                <h2><span class="sec-num">5</span> Diritti dell'interessato</h2>
                <p>
                    L'interessato può esercitare in qualunque momento i diritti previsti dagli
                    <strong>articoli 15–22 del Regolamento UE 2016/679</strong>. In particolare ha diritto di:
                </p>
                <ul class="rights-list">
                    <li>Chiedere l'accesso ai propri dati personali, la rettifica o la cancellazione degli stessi, nonché la limitazione del trattamento nei casi previsti dall'art. 18 del Regolamento.</li>
                    <li>Ottenere in formato strutturato, di uso comune e leggibile da dispositivo automatico, i dati che lo riguardano nei casi previsti dall'art. 20 (diritto alla portabilità).</li>
                    <li>Revocare in qualsiasi momento il consenso prestato ai sensi dell'art. 7, accedendo al proprio account o rivolgendosi direttamente alla struttura.</li>
                    <li>Formulare una richiesta di opposizione al trattamento ai sensi dell'art. 21, fornendo evidenza delle ragioni che la giustifichino.</li>
                    <li>Presentare reclamo all'autorità di controllo competente in caso di violazione dei propri diritti.</li>
                </ul>
                <p>
                    Gli utenti con ruolo Admin e Manager possono accedere, modificare o eliminare i dati
                    personali direttamente tramite le funzionalità dedicate della piattaforma.
                </p>
            </section>

            <section id="modifiche" class="privacy-section">
                <h2><span class="sec-num">6</span> Modifiche all'informativa</h2>
                <p>
                    Nei limiti consentiti dalla normativa applicabile, la Società si riserva il diritto
                    di rivedere e modificare la presente informativa in qualsiasi momento.
                </p>
                <p>
                    L'utilizzo continuato dei Servizi successivamente all'adozione di tali modifiche
                    costituisce accettazione delle stesse. Si raccomanda di consultare periodicamente
                    il presente documento.
                </p>
            </section>

            <div class="contact-info">
                <div class="contact-name">Ergoterapeutica Artigianale Cremasca — Società Cooperativa Sociale Onlus</div>
                <p>
                    Via Silvio Pellico 2 — 26013 Crema (CR) &nbsp;·&nbsp; C.F. e P.IVA 00797220191<br>
                    Tel. 0373 201254 &nbsp;·&nbsp; Cell. 333 3235397 &nbsp;·&nbsp; Cell. 348 4915777 &nbsp;·&nbsp;
                    <a href="https://ergoterapeutica.it" style="color:#640a35;">ergoterapeutica.it</a>
                </p>
            </div>

            <footer class="privacy-page-footer">
                <div class="gdpr-badge">✓ Conforme al Regolamento UE 2016/679 — GDPR</div>
                <p style="margin-top: 0.5rem;">Ultimo aggiornamento: Febbraio 2026</p>
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

        userBox.addEventListener("click", (e) => {
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

        function closeLogout() {
            logoutOverlay.classList.remove("show");
            logoutModal.classList.remove("show");
        }

        confirmLogout.onclick = () => {
            window.location.href = "logout.php";
        };

        document.querySelectorAll('.toc a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        });

        const fadeObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    fadeObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.07
        });

        document.querySelectorAll('.privacy-section').forEach((s, i) => {
            s.style.transitionDelay = `${i * 50}ms`;
            fadeObserver.observe(s);
        });
    </script>

</body>

</html>