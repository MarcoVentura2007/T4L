<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

require __DIR__ . '/../data/db_connection.php';
$conn = getDbConnection('time4all');

$resultClasse = $conn->query("SELECT classe, codice_univoco FROM Account WHERE nome_utente = '$username'");
if ($resultClasse && $resultClasse->num_rows > 0) {
    $rowClasse = $resultClasse->fetch_assoc();
    $classe = $rowClasse['classe'];
    $codice = $rowClasse['codice_univoco'];
} else {
    $classe = "";
}
$conn->close();
$conn = getDbConnection('time4allergo');
$oggi = date('Y-m-d');

$sql = "
    SELECT i.id, i.nome, i.cognome, i.fotografia
    FROM iscritto i
    WHERE NOT EXISTS (
        SELECT 1
        FROM Presenza p
        WHERE p.ID_Iscritto = i.id
        AND DATE(p.Ingresso) = '$oggi'
    )
    ORDER BY i.cognome ASC
";
$result = $conn->query($sql);

$userMap = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fullName = $row['nome'] . " " . $row['cognome'];
        $userMap[$fullName] = [
            'id'         => $row['id'],
            'fotografia' => $row['fotografia']
        ];
    }
}
$conn->close();

$assetBase  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$styleHref  = ($assetBase !== '' ? $assetBase : '') . '/style.css?v=20260309';
$faviconHref = ($assetBase !== '' ? $assetBase : '') . '/immagini/Icona.ico';
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>T4L | Selezione</title>
    <link rel="icon" href="<?php echo htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($styleHref, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script>
        var userMap = <?php echo json_encode($userMap); ?>;
    </script>

    <style>

        .check-check {
            stroke: #0b516c;
        }

        .check-circle {
            stroke: #0b516c;
        }

        /* ═══════════════════ MODAL VEIL ═══════════════════ */
        #modalVeil {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 800;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }

        #modalVeil.show {
            display: block;
        }

        /* ═══════════════════ TIME MODAL ═══════════════════ */

        .time-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -46%) scale(.97);
            z-index: 900;
            width: min(720px, calc(100vw - 32px));
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 24px 64px rgba(0, 0, 0, .18);
            opacity: 0;
            pointer-events: none;
            transition: opacity .22s ease, transform .26s cubic-bezier(.34, 1.2, .64, 1);
            overflow: hidden;
            font-family: var(--font);
        }

        .time-modal.show {
            opacity: 1;
            pointer-events: all;
            transform: translate(-50%, -50%) scale(1);
        }

        /* ── Header ── */
        .tm-head {
            padding: 32px 40px 28px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .tm-avatar-ring {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            background: #eaf4f8;
            border: 2px solid #d4eaf3;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tm-avatar-ring img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .tm-head-meta {
            flex: 1;
        }

        .tm-head-title {
            font-size: 17px;
            font-weight: 700;
            color: #111;
            margin: 0 0 3px;
        }

        .tm-head-sub {
            font-size: 13px;
            color: #888;
            margin: 0;
        }

        .tm-duration-wrap {
            text-align: right;
            flex-shrink: 0;
        }

        .tm-duration-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: #aaa;
            margin: 0 0 2px;
        }

        .tm-duration-val {
            font-size: 32px;
            font-weight: 700;
            color: #0b516c;
            font-variant-numeric: tabular-nums;
            letter-spacing: -1px;
            margin: 0;
        }

        /* ── Body ── */
        .tm-body {
            padding: 40px 40px 16px;
        }

        .tm-time-row {
            display: grid;
            grid-template-columns: 1fr 1px 1fr;
            align-items: end;
            margin-bottom: 24px;
        }

        .tm-divider {
            background: #e8e8e8;
            height: 60px;
            align-self: end;
            margin-bottom: 12px;
        }

        .tm-field {
            padding: 0 40px;
        }

        .tm-field:first-child {
            padding-left: 0;
        }

        .tm-field:last-child {
            padding-right: 0;
        }

        .tm-field label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #aaa;
            margin-bottom: 12px;
        }

        .tm-field input[type="time"] {
            width: 100%;
            font-size: 52px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            letter-spacing: -1px;
            color: #111;
            background: transparent;
            border: none;
            border-bottom: 2px solid #e5e5e5;
            border-radius: 0;
            outline: none;
            padding: 0 0 10px;
            box-sizing: border-box;
            cursor: pointer;
            transition: border-color .18s;
            color-scheme: light;
        }

        .tm-field input[type="time"]:focus {
            border-bottom-color: #0b516c;
        }

        .tm-field input[type="time"]::-webkit-calendar-picker-indicator {
            opacity: 0.3;
            cursor: pointer;
            filter: invert(20%) sepia(80%) saturate(400%) hue-rotate(160deg);
        }

        /* ── Error ── */
        .tm-err {
            font-size: 13px;
            color: #c0392b;
            margin: 0 0 8px;
            padding: 10px 14px;
            background: #fdf2f2;
            border-radius: 8px;
            border: 1px solid #f5c6c6;
        }

        /* ── Footer ── */
        .tm-footer {
            padding: 8px 40px 36px;
            display: flex;
            gap: 12px;
        }

        .tm-btn-cancel {
            flex: 1;
            padding: 15px;
            font-size: 14px;
            font-weight: 600;
            font-family: var(--font);
            color: #888;
            background: #fff;
            border: 1.5px solid #e5e5e5;
            border-radius: 12px;
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s;
        }

        .tm-btn-cancel:hover {
            background: #f5f5f5;
            color: #555;
            border-color: #ccc;
        }

        .tm-btn-submit {
            flex: 3;
            padding: 15px;
            font-size: 15px;
            font-weight: 700;
            font-family: var(--font);
            color: #fff;
            background: #0b516c;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background .15s;
        }

        .tm-btn-submit:hover {
            background: #0d6a8a;
        }

        .tm-btn-submit:active {
            background: #073a52;
        }

        .tm-btn-submit:disabled {
            opacity: .7;
            cursor: not-allowed;
        }

        /* ── Spinner ── */
        .tm-spinner {
            width: 16px;
            height: 16px;
            border: 2.5px solid rgba(255, 255, 255, .3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: tm-spin .7s linear infinite;
            flex-shrink: 0;
        }

        @keyframes tm-spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ══════════════════════════════════════════════
   RESET & BASE
══════════════════════════════════════════════ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --navy: #0d1b2a;
            --navy-mid: #162033;
            --navy-soft: #1e2d42;
            --accent: #3b82f6;
            --accent-bright: #60a5fa;
            --accent-glow: rgba(59, 130, 246, .18);
            --success: #10b981;
            --danger: #ef4444;
            --text-1: #f1f5f9;
            --text-2: #94a3b8;
            --text-3: #64748b;
            --border: rgba(255, 255, 255, .08);
            --border-mid: rgba(255, 255, 255, .13);
            --surface: rgba(255, 255, 255, .04);
            --surface-2: rgba(255, 255, 255, .07);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --radius-xl: 24px;
            --font: 'DM Sans', system-ui, sans-serif;
            --font-mono: 'DM Mono', monospace;
            --nav-h: 64px;
            --shadow-card: 0 4px 24px rgba(0, 0, 0, .35), 0 1px 4px rgba(0, 0, 0, .2);
            --shadow-glow: 0 0 32px rgba(59, 130, 246, .15);
        }


        body.popup-open {
            overflow: hidden;
        }

        /* ══════════════════════════════════════════════
   MAIN LAYOUT
══════════════════════════════════════════════ */
        .page-main {
            min-height: 100vh;
            padding: calc(var(--nav-h) + 40px) 24px 60px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .page-center {
            width: 100%;
            max-width: 560px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 28px;
        }

        /* ══════════════════════════════════════════════
   PAGE HEADER
══════════════════════════════════════════════ */
        .page-header {
            text-align: center;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: black;
            letter-spacing: -.02em;
            margin-bottom: 6px;
        }

        .page-header p {
            font-size: 16px;
            color: black;
            line-height: 1.5;
        }

        /* ══════════════════════════════════════════════
            WEBCAM CARD
        ══════════════════════════════════════════════ */
        .webcam-card {
            width: 100%;
            background: var(--navy-mid);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-card);
            transition: border-color .3s;
        }

        .webcam-card.scanning {
            border-color: rgba(59, 130, 246, .4);
            box-shadow: var(--shadow-card), var(--shadow-glow);
        }

        /* Card top bar */
        .webcam-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px 12px;
            border-bottom: 1px solid var(--border);
        }

        .webcam-topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .webcam-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-3);
            transition: background .3s, box-shadow .3s;
        }

        .webcam-dot.active {
            background: var(--success);
            box-shadow: 0 0 8px rgba(16, 185, 129, .5);
        }

        .webcam-dot.scanning-dot {
            background: var(--accent);
            box-shadow: 0 0 8px rgba(59, 130, 246, .5);
            animation: dot-pulse 1.2s ease-in-out infinite;
        }

        @keyframes dot-pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .35;
            }
        }

        .webcam-status-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-3);
            letter-spacing: .04em;
            text-transform: uppercase;
            font-family: var(--font-mono);
            transition: color .3s;
        }

        .webcam-status-label.active {
            color: var(--success);
        }

        .webcam-status-label.scanning {
            color: var(--accent-bright);
        }

        .webcam-badge {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 3px 9px;
            border-radius: 20px;
            background: var(--surface-2);
            color: var(--text-3);
            border: 1px solid var(--border);
            font-family: var(--font-mono);
        }

        /* Video wrapper */
        .webcam-video-wrap {
            position: relative;
            background: #050c14;
            aspect-ratio: 4/3;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transform: scaleX(-1);
            /* mirror */
        }

        /* Corner guides */
        .corner-guides {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .cg {
            position: absolute;
            width: 32px;
            height: 32px;
            border-color: rgba(59, 130, 246, .6);
            border-style: solid;
            transition: border-color .3s;
        }

        .scanning-active .cg {
            border-color: rgba(59, 130, 246, 1);
        }

        .cg.tl {
            top: 16px;
            left: 16px;
            border-width: 2px 0 0 2px;
            border-radius: 4px 0 0 0;
        }

        .cg.tr {
            top: 16px;
            right: 16px;
            border-width: 2px 2px 0 0;
            border-radius: 0 4px 0 0;
        }

        .cg.bl {
            bottom: 16px;
            left: 16px;
            border-width: 0 0 2px 2px;
            border-radius: 0 0 0 4px;
        }

        .cg.br {
            bottom: 16px;
            right: 16px;
            border-width: 0 2px 2px 0;
            border-radius: 0 0 4px 0;
        }

        /* Scan line animation */
        .scan-line {
            position: absolute;
            left: 14px;
            right: 14px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, .8), transparent);
            top: 14px;
            opacity: 0;
            transition: opacity .3s;
        }

        .scan-line.active {
            opacity: 1;
            animation: scan-travel 1.8s ease-in-out infinite;
        }

        @keyframes scan-travel {
            0% {
                top: 14px;
            }

            50% {
                top: calc(100% - 14px);
            }

            100% {
                top: 14px;
            }
        }

        /* Face target oval */
        .face-target {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 160px;
            height: 200px;
            border: 1.5px solid rgba(59, 130, 246, .25);
            border-radius: 50%;
            pointer-events: none;
            transition: border-color .3s, box-shadow .3s;
        }

        .scanning-active .face-target {
            border-color: rgba(59, 130, 246, .5);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, .06), inset 0 0 30px rgba(59, 130, 246, .04);
        }

        /* No camera placeholder */
        .no-camera {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: var(--navy);
            color: var(--text-3);
            font-size: 13px;
            text-align: center;
            padding: 20px;
        }

        .no-camera svg {
            opacity: .4;
        }

        /* Card bottom: snap button + result */
        .webcam-bottom {
            padding: 16px 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* Snap button */
        .snap-btn {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: #fff;
            font-family: var(--font);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .02em;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .18s, transform .12s, box-shadow .18s;
            box-shadow: 0 4px 14px rgba(59, 130, 246, .3);
        }

        .snap-btn:hover {
            background: #2563eb;
            box-shadow: 0 6px 20px rgba(59, 130, 246, .4);
        }

        .snap-btn:active {
            transform: scale(.98);
        }

        .snap-btn:disabled {
            background: var(--text-3);
            box-shadow: none;
            cursor: not-allowed;
        }

        .snap-btn svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        @keyframes snap-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .snap-spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, .3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: snap-spin .6s linear infinite;
            display: none;
        }

        /* Result bar */
        .result-bar {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.4;
        }

        .result-bar.show {
            display: flex;
        }

        .result-bar.success {
            background: rgba(16, 185, 129, .1);
            border-color: rgba(16, 185, 129, .25);
            color: #34d399;
        }

        .result-bar.error {
            background: rgba(239, 68, 68, .1);
            border-color: rgba(239, 68, 68, .25);
            color: #f87171;
        }

        .result-bar.info {
            background: rgba(59, 130, 246, .1);
            border-color: rgba(59, 130, 246, .25);
            color: var(--accent-bright);
        }

        .result-bar .rb-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .result-bar .rb-text {
            flex: 1;
        }

        .result-bar .rb-title {
            font-weight: 700;
            display: block;
        }

        .result-bar .rb-msg {
            opacity: .8;
            font-size: 12px;
            display: block;
            margin-top: 1px;
        }
    </style>
</head>

<body>

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

        <div class="logo-area">
            <a href="centrodiurno.php"><img src="immagini/Logo-centrodiurno.png"></a>
            <a href="index.php"><img src="immagini/TIME4ALL_LOGO-removebg-preview.png"></a>
            <a href="ergoterapeutica.php"><img src="immagini/Logo-Cooperativa-Ergaterapeutica.png"></a>
        </div>

        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="dropdown" id="dropdown">

            <div class="menu-group">

                <div class="menu-main" data-target="centroMenu">
                    <img src="immagini/Logo-centrodiurno.png">
                    Centro Diurno
                </div>

                <div class="submenu" id="centroMenu">
                    <div class="menu-item" data-link="fogliofirme-centro.php">
                        <img src="immagini/foglio-over.png" alt="">
                        Foglio firme
                    </div>
                    <?php
                    if ($classe === 'Educatore') {
                        $gestionalePage = "gestionale_utenti.php";
                    } else if ($classe === 'Contabile') {
                        $gestionalePage = "gestionale_contabile.php";
                    } else if ($classe === 'Amministratore') {
                        $gestionalePage = "gestionale_amministratore.php";
                    } else {
                        $gestionalePage = "#";
                    }
                    ?>
                    <div class="menu-item" data-link=<?php echo $gestionalePage; ?>>
                        <img src="immagini/gestionale-over.png" alt="">
                        Gestionale
                    </div>
                </div>

            </div>


            <div class="menu-group">

                <div class="menu-main" data-target="ergoMenu">
                    <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png">
                    Ergoterapeutica
                </div>

                <div class="submenu" id="ergoMenu">
                    <div class="menu-item" data-link="presenze-ergo.php">
                        <img src="immagini/presenze-ergo.png" alt="">
                        Presenze
                    </div>
                    <?php
                    if ($classe === 'Educatore') {
                        $gestionalePageErgo = "gestionale_ergo_utenti.php";
                    } else if ($classe === 'Contabile') {
                        $gestionalePageErgo = "gestionale_ergo_contabile.php";
                    } else if ($classe === 'Amministratore') {
                        $gestionalePageErgo = "gestionale_ergo_amministratore.php";
                    } else {
                        $gestionalePageErgo = "#";
                    }
                    ?>

                    <div class="menu-item" data-link=<?php echo $gestionalePageErgo; ?>>
                        <img src="immagini/gestionale-ergo.png" alt="">
                        Gestionale
                    </div>
                </div>

            </div>

        </div>



    </header>

    <!-- Logout overlay + modal (unici, non duplicati) -->
    <div class="logout-overlay" id="logoutOverlay"></div>
    <div class="logout-modal" id="logoutModal">
        <h3>Conferma logout</h3>
        <p>Sei sicuro di voler uscire dal tuo account?</p>
        <div class="logout-actions">
            <button class="btn-cancel" id="cancelLogout">Annulla</button>
            <button class="btn-logout" id="confirmLogout">Logout</button>
        </div>
    </div>


    <!-- ═══════════════════ MAIN ═══════════════════ -->
    <main class="page-main">
        <div class="page-center">

            <div class="page-header">
                <h1>Riconoscimento facciale</h1>
                <p>Posizionati davanti alla webcam, assicurati che il volto sia ben illuminato e premi il pulsante.</p>
            </div>

            <!-- WEBCAM CARD -->
            <div class="webcam-card" id="webcamCard">

                <div class="webcam-topbar">
                    <div class="webcam-topbar-left">
                        <div class="webcam-dot" id="camDot"></div>
                        <span class="webcam-status-label" id="camLabel">Inizializzazione…</span>
                    </div>
                    <span class="webcam-badge">Face ID</span>
                </div>

                <div class="webcam-video-wrap" id="videoWrap">
                    <video id="video" autoplay playsinline muted></video>
                    <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>

                    <!-- Face guide -->
                    <div class="face-target" id="faceTarget"></div>

                    <!-- Corner guides -->
                    <div class="corner-guides" id="cornerGuides">
                        <div class="cg tl"></div>
                        <div class="cg tr"></div>
                        <div class="cg bl"></div>
                        <div class="cg br"></div>
                    </div>

                    <!-- Scan line -->
                    <div class="scan-line" id="scanLine"></div>

                    <!-- No camera placeholder (hidden by default) -->
                    <div class="no-camera" id="noCamPlaceholder" style="display:none;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                        </svg>
                        <span>Impossibile accedere alla fotocamera.<br>Verifica i permessi del browser.</span>
                    </div>
                </div>

                <div class="webcam-bottom">
                    <button class="snap-btn" id="snap">
                        <svg id="snapIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3" />
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                        </svg>
                        <div class="snap-spinner" id="snapSpinner"></div>
                        <span id="snapLabel">Scatta e verifica</span>
                    </button>

                    <div class="result-bar" id="resultBar">
                        <svg class="rb-icon" id="rbIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"></svg>
                        <div class="rb-text">
                            <span class="rb-title" id="rbTitle"></span>
                            <span class="rb-msg" id="rbMsg"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="popup success-popup" id="successPopup">
                <div class="success-content">
                    <div class="success-icon"><svg viewBox="-2 -2 56 56">
                            <circle class="check-circle" cx="26" cy="26" r="25" fill="none" />
                            <path class="check-check" d="M14 27 L22 35 L38 19" fill="none" />
                        </svg></div>
                    <p class="success-text" id="success-text">Operazione completata!</p>
                </div>
            </div>



        </div>
    </main>


    <!-- ═══════════════════ MODAL VEIL ═══════════════════ -->
    <!-- FIX: era class="overlya" (typo), ora è id="modalVeil" con stili CSS corretti -->
    <div id="modalVeil"></div>

    <!-- ═══════════════════ TIME MODAL ═══════════════════ -->
    <div class="time-modal" id="timeModal" role="dialog" aria-modal="true">

        <div class="tm-head">
            <div class="tm-avatar-ring">
                <img id="tmAvatar" src="immagini/profile-picture.png" alt="">
            </div>
            <div class="tm-head-meta">
                <p class="tm-head-title" id="tmUserName"></p>
                <p class="tm-head-sub" id="tmDateText"></p>
            </div>
            <div class="tm-duration-wrap">
                <p class="tm-duration-label">Durata</p>
                <p class="tm-duration-val" id="tmDuration">—</p>
            </div>
        </div>

        <div class="tm-body">
            <div class="tm-time-row">
                <div class="tm-field">
                    <label for="tmTimeIn">Ingresso</label>
                    <input type="time" id="tmTimeIn" required>
                </div>
                <div class="tm-divider"></div>
                <div class="tm-field">
                    <label for="tmTimeOut">Uscita</label>
                    <input type="time" id="tmTimeOut" required>
                </div>
            </div>

            <p class="tm-err" id="tmErr" style="display:none;"></p>
        </div>

        <div class="tm-footer">
            <button class="tm-btn-cancel" id="tmCancel">Annulla</button>
            <button class="tm-btn-submit" id="tmSubmit">
                <svg id="tmSubmitIcon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                <span class="tm-spinner" id="tmSpinner" style="display:none;"></span>
                <span id="tmSubmitLabel">Conferma presenza</span>
            </button>
        </div>

    </div>

    <!-- ═══════════════════ CODE MODALS ═══════════════════ -->


    <div id="code-popup" class="popup">
        <div class="content">
            <p class="codice-text">Inserisci il codice di accesso</p>
            <input
                type="password"
                placeholder="Codice d'accesso"
                id="password"
                inputmode="numeric"
                pattern="[0-9]*"
                autocomplete="off"
                required
                oninput="this.value = this.value.replace(/[^0-9]/g, '')">


            <button class="learn-more" id="button-gestionale">
                <span class="circle" aria-hidden="true">
                    <span class="icon arrow"></span>
                </span>
                <span class="button-text">Continua</span>
            </button>

            <div id="notify" class="notify hidden">
                <div class="icon" id="notify-icon"></div>
                <div class="text" id="notify-text"></div>
            </div>
        </div>
    </div>

    <div id="code-popup-ergo" class="popupErgo">
        <div class="content">
            <p class="codice-text">Inserisci il codice di accesso</p>
            <input
                type="password"
                placeholder="Codice d'accesso"
                id="password-ergo"
                inputmode="numeric"
                pattern="[0-9]*"
                autocomplete="off"
                required
                oninput="this.value = this.value.replace(/[^0-9]/g, '')">


            <button class="learn-more" id="button-gestionale-ergo">
                <span class="circle" aria-hidden="true">
                    <span class="icon arrow"></span>
                </span>
                <span class="button-text">Continua</span>
            </button>

            <div id="notify-ergo" class="notify hidden">
                <div class="icon" id="notify-icon-ergo"></div>
                <div class="text" id="notify-text-ergo"></div>
            </div>
        </div>
    </div>

    <div class="popup-overlay" id="popupOverlay"></div>


    <!-- ═══════════════════ FOOTER ═══════════════════ -->
    <footer class="footer-bar">
        <div class="footer-left">© Time4All • 2026</div>
        <div class="footer-top">
            <a href="#top" class="footer-image"></a>
        </div>
        <div class="footer-right">
            <a href="privacy_policy.php" class="hover-underline-animation">PRIVACY POLICY</a>
        </div>
    </footer>


    <script>
        /* ════════════════════════════════════════════
   UTILS
════════════════════════════════════════════ */
        function toast(msg, ok = true) {
            const wrap = document.getElementById('toastWrap');
            const t = document.createElement('div');
            t.className = 'toast ' + (ok ? 'success' : 'error');

            const iconPath = ok ?
                '<polyline points="20 6 9 17 4 12"/>' :
                '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>';

            t.innerHTML = `<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">${iconPath}</svg><span>${msg}</span>`;
            wrap.appendChild(t);
            requestAnimationFrame(() => {
                requestAnimationFrame(() => t.classList.add('show'));
            });
            setTimeout(() => {
                t.classList.add('hide');
                t.addEventListener('transitionend', () => t.remove());
            }, 2400);
        }

        function formatDateIT(d) {
            const days = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
            const months = ['gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'];
            return days[d.getDay()] + ' ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        }

        /* ════════════════════════════════════════════
           WEBCAM
        ════════════════════════════════════════════ */
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const camDot = document.getElementById('camDot');
        const camLabel = document.getElementById('camLabel');
        const snap = document.getElementById('snap');
        const snapIcon = document.getElementById('snapIcon');
        const snapSpinner = document.getElementById('snapSpinner');
        const snapLabel = document.getElementById('snapLabel');
        const resultBar = document.getElementById('resultBar');
        const rbIcon = document.getElementById('rbIcon');
        const rbTitle = document.getElementById('rbTitle');
        const rbMsg = document.getElementById('rbMsg');
        const videoWrap = document.getElementById('videoWrap');
        const scanLine = document.getElementById('scanLine');
        const webcamCard = document.getElementById('webcamCard');
        const noCamPlaceholder = document.getElementById('noCamPlaceholder');

        navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: {
                        ideal: 640
                    },
                    height: {
                        ideal: 480
                    }
                }
            })
            .then(stream => {
                video.srcObject = stream;
                camDot.classList.add('active');
                camLabel.textContent = 'Camera attiva';
                camLabel.classList.add('active');
            })
            .catch(() => {
                video.style.display = 'none';
                noCamPlaceholder.style.display = 'flex';
                camLabel.textContent = 'Camera non disponibile';
                snap.disabled = true;
            });

        function showResult(type, title, msg) {
            resultBar.classList.remove('success', 'error', 'info', 'show');
            void resultBar.offsetWidth;
            resultBar.classList.add(type, 'show');
            rbTitle.textContent = title;
            rbMsg.textContent = msg;

            const icons = {
                success: '<polyline points="20 6 9 17 4 12"/>',
                error: '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
                info: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'
            };
            rbIcon.innerHTML = icons[type] || icons.info;

            if (type === 'success') setTimeout(() => resultBar.classList.remove('show'), 6000);
        }

        function setScanning(on) {
            if (on) {
                webcamCard.classList.add('scanning');
                videoWrap.classList.add('scanning-active');
                scanLine.classList.add('active');
                camDot.className = 'webcam-dot scanning-dot';
                camLabel.textContent = 'Analisi in corso…';
                camLabel.className = 'webcam-status-label scanning';
                snap.disabled = true;
                snapIcon.style.display = 'none';
                snapSpinner.style.display = '';
                snapLabel.textContent = 'Analisi…';
            } else {
                webcamCard.classList.remove('scanning');
                videoWrap.classList.remove('scanning-active');
                scanLine.classList.remove('active');
                camDot.className = 'webcam-dot active';
                camLabel.textContent = 'Camera attiva';
                camLabel.className = 'webcam-status-label active';
                snap.disabled = false;
                snapIcon.style.display = '';
                snapSpinner.style.display = 'none';
                snapLabel.textContent = 'Scatta e verifica';
            }
        }

        snap.addEventListener('click', () => {
            setScanning(true);

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(blob => {
                const fd = new FormData();
                fd.append('image', blob, 'photo.png');

                fetch('../faceid/public/upload.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.text())
                    .then(text => {
                        setScanning(false);
                        try {
                            const data = JSON.parse(text);
                            if (data.result?.error) {
                                showResult('error', 'Errore', data.result.error);
                                return;
                            }
                            if (!data.result) {
                                showResult('error', 'Errore server', 'Risposta non valida.');
                                return;
                            }

                            if (data.result.known) {
                                const recognizedName = data.result.name;
                                if (userMap[recognizedName]) {
                                    const user = userMap[recognizedName];
                                    const foto = user.fotografia ?
                                        user.fotografia :
                                        'immagini/profile-picture.png';

                                    showResult('success', 'Volto riconosciuto', 'Ciao ' + recognizedName + ' — apertura scheda…');

                                    setTimeout(() => {
                                        openTimeModal(user.id, recognizedName, foto);
                                        resultBar.classList.remove('show');
                                    }, 1200);
                                } else {
                                    showResult('info', 'Già presente', recognizedName + ' ha già registrato la presenza oggi.');
                                }
                            } else {
                                showResult('error', 'Volto non riconosciuto', 'Riposizionati davanti alla webcam e riprova.');
                            }
                        } catch (e) {
                            showResult('error', 'Errore parsing', 'Risposta non valida dal server.');
                        }
                    })
                    .catch(() => {
                        setScanning(false);
                        showResult('error', 'Errore connessione', 'Impossibile raggiungere il server. Riprova.');
                    });
            }, 'image/png');
        });

        /* ════════════════════════════════════════════
           TIME MODAL
        ════════════════════════════════════════════ */
        const modalVeil = document.getElementById('modalVeil');
        const timeModal = document.getElementById('timeModal');
        const tmAvatar = document.getElementById('tmAvatar');
        const tmUserName = document.getElementById('tmUserName');
        const tmDateText = document.getElementById('tmDateText');
        const tmDuration = document.getElementById('tmDuration');
        const tmTimeIn = document.getElementById('tmTimeIn');
        const tmTimeOut = document.getElementById('tmTimeOut');
        const tmSubmit = document.getElementById('tmSubmit');
        const tmSubmitIcon = document.getElementById('tmSubmitIcon');
        const tmSubmitLabel = document.getElementById('tmSubmitLabel');
        const tmSpinner = document.getElementById('tmSpinner');
        const tmErr = document.getElementById('tmErr');
        const tmCancel = document.getElementById('tmCancel');

        let currentUserId = null;

        // Calcolo durata in tempo reale
        function calcDuration() {
            const inVal = tmTimeIn.value;
            const outVal = tmTimeOut.value;
            if (!inVal || !outVal) {
                tmDuration.textContent = '—';
                return;
            }
            const [ih, im] = inVal.split(':').map(Number);
            const [oh, om] = outVal.split(':').map(Number);
            const diff = (oh * 60 + om) - (ih * 60 + im);
            if (diff <= 0) {
                tmDuration.textContent = '—';
                return;
            }
            const h = Math.floor(diff / 60);
            const m = diff % 60;
            tmDuration.textContent = m === 0 ? `${h}h` : `${h}h ${m}m`;
        }

        tmTimeIn.addEventListener('input', calcDuration);
        tmTimeOut.addEventListener('input', calcDuration);

        // Apri modal
        function openTimeModal(userId, name, fotoPath) {
            currentUserId = userId;

            tmAvatar.src = fotoPath && fotoPath !== '' ? fotoPath : 'immagini/profile-picture.png';
            tmUserName.textContent = name;

            const now = new Date();
            tmDateText.textContent = formatDateIT(now);

            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            tmTimeIn.value = hh + ':' + mm;
            tmTimeOut.value = '';

            tmDuration.textContent = '—';
            tmErr.style.display = 'none';
            tmErr.textContent = '';
            tmSubmit.disabled = false;
            tmSubmitLabel.textContent = 'Conferma presenza';
            tmSubmitIcon.style.display = '';
            tmSpinner.style.display = 'none';

            // FIX: apre sia il modal che il veil
            timeModal.classList.add('show');
            modalVeil.classList.add('show');
            document.body.classList.add('popup-open');
            setTimeout(() => tmTimeOut.focus(), 350);
        }

        // Chiudi modal
        function closeTimeModal(keepVeil = false) {
            timeModal.classList.remove('show');
            document.body.classList.remove('popup-open');
            if (!keepVeil) {
                modalVeil.classList.remove('show');
            }
            currentUserId = null;
        }

        tmCancel.addEventListener('click', () => closeTimeModal(false));

        // Submit
        tmSubmit.addEventListener('click', async () => {
            const tIn = tmTimeIn.value;
            const tOut = tmTimeOut.value;

            if (!tIn || !tOut) {
                showErr('Inserisci sia l\'ora di ingresso che di uscita.');
                return;
            }
            if (tIn >= tOut) {
                showErr('L\'ora di uscita deve essere successiva all\'ora di ingresso.');
                return;
            }
            if (!currentUserId) {
                showErr('Errore: nessun utente selezionato.');
                return;
            }

            tmErr.style.display = 'none';
            tmSubmit.disabled = true;
            tmSubmitLabel.textContent = 'Registrazione…';
            tmSubmitIcon.style.display = 'none';
            tmSpinner.style.display = '';

            try {
                const res = await fetch('api/api_firma_ergo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        id_iscritto: currentUserId,
                        ora_ingresso: tIn,
                        ora_uscita: tOut,
                        face_verified: true
                    })
                });
                const data = await res.json();

                if (data.success) {
                    const userName = tmUserName.textContent;
                    closeTimeModal(true); // chiude modal ma tiene il veil
                    showSuccess(`${userName} • ${tIn} → ${tOut}`);
                    setTimeout(() => {
                        hideSuccess();
                        modalVeil.classList.remove('show');
                        location.reload();
                    }, 2000);
                } else {
                    resetSubmitBtn();
                    showErr(data.error || 'Si è verificato un errore. Riprova.');
                }
            } catch (err) {
                resetSubmitBtn();
                showErr('Errore di connessione al server.');
            }
        });

        function showErr(msg) {
            tmErr.textContent = msg;
            tmErr.style.display = '';
        }

        function resetSubmitBtn() {
            tmSubmit.disabled = false;
            tmSubmitLabel.textContent = 'Conferma presenza';
            tmSubmitIcon.style.display = '';
            tmSpinner.style.display = 'none';
        }

        // ELEMENTI CODE POPUP
        const overlay = document.getElementById("popupOverlay");
        const codePopup = document.getElementById("code-popup");
        const buttonGestionale = document.getElementById("button-gestionale");
        const passwordField = document.getElementById("password");

        // FUNZIONE NOTIFICATION
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

            setTimeout(() => notify.classList.add('show'), 10);

            setTimeout(() => {
                notify.classList.remove('show');
                notify.classList.add('hide');
                notify.addEventListener('animationend', () => notify.remove());
            }, 2000);
        }

        // CHIUDI POPUP CLICCANDO FUORI
        overlay.addEventListener("click", () => {
            overlay.classList.remove("show");
            codePopup.classList.remove("show");
            document.body.classList.remove("popup-open");
        });

        const passwordFieldErgo = document.getElementById("password-ergo");
        const codePopupErgo = document.getElementById("code-popup-ergo");
        const buttonGestionaleErgo = document.getElementById("button-gestionale-ergo");

        buttonGestionaleErgo.addEventListener("click", verificaCodiceErgo);

        async function verificaCodiceErgo() {
            const codice = passwordFieldErgo.value.trim();

            if (!codice) {
                showNotification(false, "Inserisci il codice");
                return;
            }

            try {
                const response = await fetch("api/api_codice_gestionale_ergo.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `codice=${encodeURIComponent(codice)}`
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(true, "Accesso consentito");
                    passwordFieldErgo.value = "";
                    overlay.classList.remove("show");
                    codePopupErgo.classList.remove("show");
                    document.body.classList.remove("popup-open");
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 2000);
                } else {
                    showNotification(false, result.message);
                    passwordFieldErgo.value = "";
                }

            } catch (err) {
                showNotification(false, "Errore server");
                console.error(err);
            }
        }

        function showSuccess(text) {
            const popup = document.getElementById('successPopup');
            const textEl = document.getElementById('success-text');
            if (textEl) textEl.textContent = text;
            popup.classList.add('show');
        }

        function hideSuccess() {
            document.getElementById('successPopup').classList.remove('show');
        }

        async function verificaCodice() {
            const codice = passwordField.value.trim();

            if (!codice) {
                showNotification(false, "Inserisci il codice");
                return;
            }

            try {
                const response = await fetch("api/api_codice_gestionale.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `codice=${encodeURIComponent(codice)}`
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(true, "Accesso consentito");
                    passwordField.value = "";
                    overlay.classList.remove("show");
                    codePopup.classList.remove("show");
                    document.body.classList.remove("popup-open");
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 2000);
                } else {
                    showNotification(false, result.message);
                    passwordField.value = "";
                }

            } catch (err) {
                showNotification(false, "Errore server");
                console.error(err);
            }
        }

        buttonGestionale.addEventListener("click", verificaCodice);

        passwordField.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                verificaCodice();
            }
        });

        passwordFieldErgo.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                verificaCodiceErgo();
            }
        });

        function closePopups() {
            overlay.classList.remove("show");
            codePopup.classList.remove("show");
            codePopupErgo.classList.remove("show");
            document.body.classList.remove("popup-open");
        }

        overlay.onclick = closePopups;

        /* HAMBURGER */
        const ham = document.getElementById("hamburger");
        const drop = document.getElementById("dropdown");
        ham.onclick = () => {
            ham.classList.toggle("active");
            drop.classList.toggle("show");
        };

        document.querySelectorAll(".menu-main").forEach(main => {
            main.addEventListener("click", () => {
                const targetId = main.dataset.target;
                const targetMenu = document.getElementById(targetId);

                document.querySelectorAll(".submenu").forEach(menu => {
                    if (menu !== targetMenu) {
                        menu.classList.remove("open");
                        menu.previousElementSibling.classList.remove("open");
                    }
                });

                targetMenu.classList.toggle("open");
                main.classList.toggle("open");
            });
        });

        document.querySelectorAll(".menu-item[data-link]").forEach(item => {
            const link = item.dataset.link;
            if (link.includes("gestionale_ergo")) {
                item.addEventListener("click", (e) => {
                    e.preventDefault();
                    overlay.classList.add("show");
                    codePopupErgo.classList.add("show");
                    document.body.classList.add("popup-open");
                    passwordFieldErgo.focus();
                });
            } else if (link.includes("gestionale")) {
                item.addEventListener("click", (e) => {
                    e.preventDefault();
                    overlay.classList.add("show");
                    codePopup.classList.add("show");
                    document.body.classList.add("popup-open");
                    passwordField.focus();
                });
            } else {
                item.addEventListener("click", () => {
                    window.location.href = link;
                });
            }
        });

        /* USER DROPDOWN */
        const userBox = document.getElementById("userBox");
        const userDropdown = document.getElementById("userDropdown");
        userBox.addEventListener("click", (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle("show");
        });
        document.addEventListener("click", (e) => {
            if (!userBox.contains(e.target)) {
                userDropdown.classList.remove("show");
            }
        });

        /* LOGOUT */
        const logoutBtn = document.getElementById("logoutBtn");
        const logoutOverlay = document.getElementById("logoutOverlay");
        const logoutModal = document.getElementById("logoutModal");
        const cancelLogout = document.getElementById("cancelLogout");
        const confirmLogout = document.getElementById("confirmLogout");

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

        // Blocca scroll del body quando un popup è aperto
        const popupTargetsSelector = ".modal-box, .popup, .logout-modal, .success-popup, .modal-overlay, .popup-overlay, .logout-overlay";
        const popupShowSelector = ".modal-box.show, .popup.show, .logout-modal.show, .success-popup.show, .modal-overlay.show, .popup-overlay.show, .logout-overlay.show";

        function syncBodyScrollLock() {
            const anyOpen = document.querySelector(popupShowSelector);
            document.body.classList.toggle("popup-open", Boolean(anyOpen));
        }

        const popupObserver = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                const target = mutation.target;
                if (target === document.body || (target instanceof Element && target.matches(popupTargetsSelector))) {
                    syncBodyScrollLock();
                    break;
                }
            }
        });

        popupObserver.observe(document.body, {
            subtree: true,
            attributes: true,
            attributeFilter: ["class"]
        });
        syncBodyScrollLock();
    </script>

</body>

</html>