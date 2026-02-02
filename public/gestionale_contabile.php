 <?php
session_start();

// Se l'utente non è loggato → redirect a login.php
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Connessione al DB
$host = "localhost";    
$user = "root";         
$pass = "";             
$db   = "time4all"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Preleva i profili dal DB
$sql = "SELECT id, nome, cognome, fotografia, data_nascita, disabilita, prezzo_orario, codice_fiscale, contatti, allergie_intolleranze, note 
        FROM iscritto ORDER BY cognome ASC";
$result = $conn->query($sql);

// Presenze giornaliere di default
$oggi = date('Y-m-d')."%";
$sqlPresenze = "SELECT i.fotografia, p.id, i.nome, i.cognome, p.ingresso, p.uscita 
                FROM presenza p 
                INNER JOIN iscritto i ON p.ID_Iscritto = i.id 
                WHERE p.ingresso LIKE '$oggi'
                
                ORDER BY p.ingresso ASC";
$resultPresenze = $conn->query($sqlPresenze);
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>T4L | Gestionale utenti</title>

<link rel="stylesheet" href="style.css">
<link rel="icon" href="immagini/Icona.ico">
<script src="https://cdn.tailwindcss.com"></script>

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
            <a href="centrodiurno.php"><img src="immagini/Logo-centrodiurno.png"></a>
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
                <img src="immagini/Logo-centrodiurno.png"> Centro Diurno
            </div>
            <div data-link="#" class="data-link-ergo">
                <img src="immagini/Logo-Cooperativa-Ergaterapeutica.png"> Ergoterapeutica
            </div>
        </div>

    </header>

    <div class="app-layout">
 
        <!-- SIDEBAR -->
        <aside class="side-nav">
            <div class="brand">
                <img src="immagini/TIME4ALL_LOGO-removebg-preview.png" style="max-width:150px;">
            </div>
            <nav>
                <a class="nav-item tab-link active" data-tab="tab-utenti">Utenti</a>
                <a class="nav-item tab-link" data-tab="tab-presenze">Presenze</a>
                <a class="nav-item tab-link" data-tab="tab-agenda">Agenda</a>
                <a class="nav-item tab-link" data-tab="tab-contabilita">Contabilità</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">

            <div class="page-header">
                <h1>Utenti</h1>
                <p>Elenco iscritti registrati</p>
            </div>

            <div class="users-table-box">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Fotografia</th>
                            <th>Nome</th>
                            <th>Cognome</th>
                            <th>Data di nascita</th>
                            <th>Note</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if($result && $result->num_rows > 0){
                            while($row = $result->fetch_assoc()){
                                echo '
                                    <tr
                                        data-id="'.htmlspecialchars($row['id']).'"
                                        data-nome="'.htmlspecialchars($row['nome']).'" 
                                        data-cognome="'.htmlspecialchars($row['cognome']).'" 
                                        data-nascita="'.htmlspecialchars($row['data_nascita']).'" 
                                        data-note="'.htmlspecialchars($row['note']).'" 
                                        data-cf="'.htmlspecialchars($row['codice_fiscale']).'" 
                                        data-contatti="'.htmlspecialchars($row['contatti']).'" 
                                        data-disabilita="'.htmlspecialchars($row['disabilita']).'" 
                                        data-intolleranze="'.htmlspecialchars($row['allergie_intolleranze']).'" 
                                        data-prezzo="'.htmlspecialchars($row['prezzo_orario']).'" 
                                    >
                                        <td><img class="user-avatar" src="'.$row['fotografia'].'"></td>
                                        <td>'.$row['nome'].'</td>
                                        <td>'.$row['cognome'].'</td>
                                        <td>'.$row['data_nascita'].'</td>
                                        <td>'.$row['note'].'</td>
                                        <td>
                                            <button class="view-btn"><img src="immagini/open-eye.png"></button>
                                            <button class="edit-btn"><img src="immagini/edit.png"></button>
                                            <button class="delete-btn"><img src="immagini/delete.png"></button>
                                        </td>
                                    </tr>
                                ';
                            }
                        }
                        ?>
                    </tbody>
                </table>

                <!-- MODAL OVERLAY -->
                <div class="modal-overlay" id="modalOverlay"></div>

                <!-- VIEW USER MODAL -->
                <div class="modal-box large" id="viewModal">
                    <div class="profile-header">
                        <img id="viewAvatar" class="profile-avatar">
                        <div class="profile-main">
                            <h3 id="viewFullname"></h3>
                            <span id="viewBirth"></span>
                        </div>
                    </div>
                    <div class="profile-grid" id="viewContent">
                        
                    </div>
                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
                    </div>
                </div>

                <!-- EDIT USER MODAL -->
                <div class="modal-box large" id="editModal" style="overflow: scroll;">
                    <h3 class="modal-title">Modifica utente</h3>

                    <div class="profile-header">
                        <img id="viewAvatar-mod" class="profile-avatar">
                        <div class="profile-main">
                            <h3 id="viewFullname-mod"></h3>
                            <span id="viewBirth-mod"></span>
                        </div>
                    </div>

                    <div class="edit-grid" id="editContent">
                        <!-- Riempito da JS -->
                        <div class="edit-field">
                            <label>Nome</label>
                            <input type="text" id="editNome" placeholder="Nome">
                        </div>
                        <div class="edit-field">
                            <label>Cognome</label>
                            <input type="text" id="editCognome" placeholder="Cognome">
                        </div>
                        <div class="edit-field">
                            <label>Data di nascita</label>
                            <input type="date" id="editData">
                        </div>
                        <div class="edit-field">
                            <label>Codice Fiscale</label>
                            <input type="text" id="editCF" placeholder="Codice Fiscale">
                        </div>
                        <div class="edit-field">
                            <label>Contatti</label>
                            <input type="text" id="editContatti" placeholder="Contatti">
                        </div>
                        <div class="edit-field">
                            <label>Disabilità</label>
                            <input type="text" id="editDisabilita" placeholder="Disabilità">
                        </div>
                        <div class="edit-field">
                            <label>Intolleranze</label>
                            <input type="text" id="editIntolleranze" placeholder="Intolleranze">
                        </div>
                        <div class="edit-field">
                            <label>Prezzo orario</label>
                            <input type="number" id="editPrezzo" placeholder="Prezzo in €" step="0.01">
                        </div>
                        <div class="edit-field">
                            <label>Note</label>
                            <textarea id="editNote" placeholder="Note"></textarea>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal()">Chiudi</button>
                        <button class="btn-primary" id="saveEdit">Salva</button>
                    </div>
                </div>

                <!-- POPUP CONFERMA FIRMA -->
                <div class="popup success-popup" id="successPopup">
                    <div class="success-content">
                        <div class="success-icon">
                        <svg viewBox="-2 -2 56 56">
                            <circle class="check-circle" cx="26" cy="26" r="25" fill="none"/>
                            <path class="check-check" d="M14 27 L22 35 L38 19" fill="none"/>
                        </svg>
                        </div>
                        <p class="success-text">Utente modificato!!</p>
                    </div>
                </div>


                <!-- DELETE USER -->
                <div class="modal-box danger" id="deleteModal">
                    <h3>Elimina utente</h3>
                    <h3></h3>
                    <p>Questa azione è definitiva. Vuoi continuare?</p>

                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal()">Annulla</button>
                        <button class="btn-danger">Elimina</button>
                    </div>
                </div>

                <div class="popup success-popup" id="successPopupDelete">
                    <div class="success-content">
                        <div class="success-icon">
                        <svg viewBox="-2 -2 56 56">
                            <circle class="check-circle" cx="26" cy="26" r="25" fill="none"/>
                            <path class="check-check" d="M14 27 L22 35 L38 19" fill="none"/>
                        </svg>
                        </div>
                        <p class="success-text">Utente eliminato!!</p>
                    </div>
                </div>

            </div>
        </main>
    </div>


    <!-- TAB PRESENZE -->
            <div class="page-tab" id="tab-presenze">
                <div class="page-header">
                    <h1>Presenze</h1>
                    <p>Elenco presenze registrate</p>
                </div>

                <div class="presenze-controls">
                    
                </div>

                <div class="users-table-box">
                    <table class="users-table" id="presenzeTable">
                        <thead>
                            <tr>
                                <th>Fotografia</th>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Ingresso</th>
                                <th>Uscita</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if($resultPresenze && $resultPresenze->num_rows > 0){
                            while($row = $resultPresenze->fetch_assoc()){
                                echo '
                                    <tr
                                        data-id="'.htmlspecialchars($row['id']).'"
                                        data-nome="'.htmlspecialchars($row['nome']).'"
                                        data-cognome="'.htmlspecialchars($row['cognome']).'"
                                        data-ingresso="'.htmlspecialchars($row['ingresso']).'"
                                        data-uscita="'.htmlspecialchars($row['uscita']).'""
                                    >
                                        <td><img class="user-avatar" src="'.$row['fotografia'].'"></td>
                                        <td>'.htmlspecialchars($row['nome']).'</td>
                                        <td>'.htmlspecialchars($row['cognome']).'</td>
                                        <td>'.htmlspecialchars($row['ingresso']).'</td>
                                        <td>'.htmlspecialchars($row['uscita']).'</td>
                                    </tr>
                                ';
                            }
                        } else {
                            echo '<tr><td colspan="6">Nessuna presenza registrata oggi.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB AGENDA -->
            <div class="page-tab" id="tab-agenda">
                <div class="page-header">
                    <h1>Agenda</h1>
                    <p>Prossimi appuntamenti</p>
                </div>
                <p>Contenuto agenda da implementare...</p>
            </div>

        </main>
    </div>

    <script>
        // Cambia tab
        document.querySelectorAll(".tab-link").forEach(link=>{
            link.addEventListener("click", e=>{
                document.querySelectorAll(".tab-link").forEach(l=>l.classList.remove("active"));
                e.currentTarget.classList.add("active");
                const target = e.currentTarget.dataset.tab;
                document.querySelectorAll(".page-tab").forEach(tab=>tab.classList.remove("active"));
                document.getElementById(target).classList.add("active");
            });
        });


            /* HAMBURGER */
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




        /* USER DROPDOWN */
        const userBox = document.getElementById("userBox");
        const userDropdown = document.getElementById("userDropdown");
        userBox.addEventListener("click", (e)=>{
            e.stopPropagation();
            userDropdown.classList.toggle("show");
        });
        document.addEventListener("click",(e)=>{
            if(!userBox.contains(e.target)){
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
        function closeLogout(){
            logoutOverlay.classList.remove("show");
            logoutModal.classList.remove("show");
        }

        confirmLogout.onclick = () => {
            window.location.href = "logout.php";
        };

    // Modal
    const overlay = document.getElementById("modalOverlay");
    const viewModal = document.getElementById("viewModal");
    const editModal = document.getElementById("editModal");
    const deleteModal = document.getElementById("deleteModal");

    function openModal(modal){
        overlay.classList.add("show");
        modal.classList.add("show");
    }
    function closeModal(){
        overlay.classList.remove("show");
        viewModal.classList.remove("show");
        editModal.classList.remove("show");
        deleteModal.classList.remove("show");
    }


    // Popup view
    document.querySelectorAll(".view-btn").forEach(btn=>{
        btn.onclick = e=>{
            const row = e.target.closest("tr");

            const avatar = row.querySelector("img").src;
            const nome = row.dataset.nome;
            const cognome = row.dataset.cognome;
            const data = row.dataset.nascita;
            const note = row.dataset.note;
            const cf = row.dataset.cf;
            const contatti = row.dataset.contatti;
            const disabilita = row.dataset.disabilita;
            const intolleranze = row.dataset.intolleranze;
            const prezzo = row.dataset.prezzo;

            document.getElementById("viewAvatar").src = avatar;
            document.getElementById("viewFullname").innerText = nome + " " + cognome;
            document.getElementById("viewBirth").innerText = "Nato il " + data;

            document.getElementById("viewContent").innerHTML = `
                <div class="profile-field"><label>Nome</label><span>${nome}</span></div>
                <div class="profile-field"><label>Cognome</label><span>${cognome}</span></div>
                <div class="profile-field"><label>Data di nascita</label><span>${data}</span></div>
                <div class="profile-field"><label>Codice Fiscale</label><span>${cf || "—"}</span></div>
                <div class="profile-field"><label>Contatti</label><span>${contatti || "—"}</span></div>
                <div class="profile-field"><label>Disabilità</label><span>${disabilita || "—"}</span></div>
                <div class="profile-field"><label style="font-weight: bold;">Intolleranze ⚠️</label><span style="font-weight: bold;">${intolleranze || "—"}</span></div>
                <div class="profile-field"><label>Prezzo orario</label><span>${prezzo || "—"} €</span></div>
                <div class="profile-field" style="grid-column:1 / -1;"><label>Note</label><span>${note || "—"}</span></div>
            `;

            openModal(viewModal);
        }
    });
    overlay.onclick = closeModal;

        document.querySelectorAll(".edit-btn").forEach(btn=>{
            btn.onclick = e=>{
                const row = e.target.closest("tr");

                const avatar = row.querySelector("img").src;
                const nome = row.dataset.nome;
                const cognome = row.dataset.cognome;
                const data = row.dataset.nascita;
                const idIscritto = row.dataset.id;

                editModal.dataset.userId = idIscritto;


                document.getElementById("viewAvatar-mod").src = avatar;
                document.getElementById("viewFullname-mod").innerText = nome + " " + cognome;
                document.getElementById("viewBirth-mod").innerText = "Nato il " + data;

                document.getElementById("editNome").value = row.dataset.nome;
                document.getElementById("editCognome").value = row.dataset.cognome;
                document.getElementById("editData").value = row.dataset.nascita;
                document.getElementById("editCF").value = row.dataset.cf;
                document.getElementById("editContatti").value = row.dataset.contatti;
                document.getElementById("editDisabilita").value = row.dataset.disabilita;
                document.getElementById("editIntolleranze").value = row.dataset.intolleranze;
                document.getElementById("editPrezzo").value = row.dataset.prezzo;
                document.getElementById("editNote").value = row.dataset.note;

                openModal(editModal);
            }
        });
        succesPopupDelete = document.getElementById("successPopupDelete");

        document.querySelectorAll(".delete-btn").forEach(btn=>{
            btn.onclick = ()=>{
                const row = btn.closest("tr");
                const nomeCompleto = row.dataset.nome + " " + row.dataset.cognome;

                document.getElementById("deleteModal").querySelector("h3").innerText = "Eliminazione " + btn.closest("tr").dataset.nome + " " + btn.closest("tr").dataset.cognome;
                openModal(deleteModal);

                // Imposta il listener sul bottone "Elimina" nella modale
                const confirmDelete = deleteModal.querySelector(".btn-danger");
                confirmDelete.onclick = () => {
                    fetch("api/api_elimina_utente.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        body: JSON.stringify({ id_iscritto: row.dataset.id })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            deleteModal.classList.remove("show");
                            successPopupDelete.classList.add("show");

                            setTimeout(()=>{
                                closeModal();
                                successPopupDelete.classList.remove("show");
                                row.remove();
                                location.reload();
                            },1800); 
                        }

                    
                    });
                };
            }
        });

        const modalBoxEdit = document.getElementById("editModal");
        
        document.getElementById("saveEdit").onclick = () => {
            const id = editModal.dataset.userId; // <-- ID dell'iscritto

            const payload = {
                id: id,
                nome: document.getElementById("editNome").value,
                cognome: document.getElementById("editCognome").value,
                data_nascita: document.getElementById("editData").value,
                codice_fiscale: document.getElementById("editCF").value,
                contatti: document.getElementById("editContatti").value,
                disabilita: document.getElementById("editDisabilita").value,
                intolleranze: document.getElementById("editIntolleranze").value,
                prezzo_orario: document.getElementById("editPrezzo").value,
                note: document.getElementById("editNote").value
            };

            fetch('api/api_aggiorna_utente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    modalBoxEdit.classList.remove("show");
                    successPopup.classList.add("show");

                    setTimeout(()=>{
                        successPopup.classList.remove("show");
                        location.reload();
                    },1800); 
                } else {
                    alert("Errore: " + data.message);
                }
            });
        };

    </script>

</body>
</html>
