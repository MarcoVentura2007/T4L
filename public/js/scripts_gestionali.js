/**
 * scripts_gestionali.js - Funzioni comuni per tutti i gestionali Time4All
 * Centralizza le funzionalità duplicate per una manutenzione più semplice
 */

// ==========================================
// CONFIGURAZIONE GLOBALE
// ==========================================
const Gestionali = {
    // Selettori comuni
    selectors: {
        overlay: '#Overlay',
        successPopup: '#successPopup',
        successText: '#success-text',
        logoutOverlay: '#logoutOverlay',
        logoutModal: '#logoutModal',
        hamburger: '#hamburger',
        dropdown: '#dropdown',
        userBox: '#userBox',
        userDropdown: '#userDropdown',
        checkboxInput: '#checkbox-input',
        sidebar: '.vertical-sidebar'
    },
    
    // Stato globale
    state: {
        rowToDelete: null,
        currentEditId: null,
        currentEditType: null
    }
};

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

/**
 * Escape HTML per prevenire XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Formatta una data in formato italiano
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT');
}

/**
 * Formatta data e ora
 */
function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    const date = new Date(dateTimeString);
    return date.toLocaleString('it-IT');
}

/**
 * Ottiene la data odierna in formato YYYY-MM-DD
 */
function getTodayDate() {
    const today = new Date();
    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, '0');
    const d = String(today.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

/**
 * Ottiene l'ora corrente in formato HH:MM
 */
function getNowTime() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    return `${h}:${m}`;
}

/**
 * Ottiene data locale in formato YYYY-MM-DD
 */
function getLocalDateString(date) {
    const y = date.getFullYear();
    const m = (date.getMonth() + 1).toString().padStart(2, '0');
    const d = date.getDate().toString().padStart(2, '0');
    return `${y}-${m}-${d}`;
}

// ==========================================
// GESTIONE MODALI
// ==========================================

/**
 * Apre un modal
 */
function openModal(modal) {
    if (!modal) return;
    const overlay = document.querySelector(Gestionali.selectors.overlay);
    modal.classList.add('show');
    if (overlay) overlay.classList.add('show');
}

/**
 * Chiude un modal specifico o tutti i modali aperti
 */
function closeModal(modal) {
    const overlay = document.querySelector(Gestionali.selectors.overlay);
    if (overlay) overlay.classList.remove('show');
    
    if (modal) {
        modal.classList.remove('show');
    } else {
        document.querySelectorAll('.modal-box.show, .modal-box.active').forEach(el => {
            el.classList.remove('show');
            el.classList.remove('active');
        });
    }
}

/**
 * Mostra il popup di successo
 */
function showSuccess(popup, overlay) {
    if (popup) popup.classList.add('show');
    if (overlay) overlay.classList.add('show');
}

/**
 * Nasconde il popup di successo
 */
function hideSuccess(popup, overlay) {
    if (popup) popup.classList.remove('show');
    if (overlay) overlay.classList.remove('show');
}

// ==========================================
// GESTIONE TAB MOBILE/DESKTOP
// ==========================================

/**
 * Cambia tab (sincronizza mobile e desktop)
 */
function switchTab(tabId, navItem) {
    // Aggiorna stati mobile nav
    document.querySelectorAll('.mobile-nav-item').forEach(item => {
        item.classList.remove('active');
    });
    if (navItem) navItem.classList.add('active');

    // Aggiorna stati desktop sidebar
    document.querySelectorAll('.tab-link').forEach(link => {
        link.classList.remove('active');
        if (link.dataset.tab === tabId) {
            link.classList.add('active');
        }
    });

    // Cambia contenuto tab
    document.querySelectorAll('.page-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    const targetTab = document.getElementById(tabId);
    if (targetTab) targetTab.classList.add('active');

    // Salva in localStorage
    localStorage.setItem('activeTab', tabId);
}

/**
 * Inizializza la sincronizzazione dei tab al caricamento
 */
function initTabSync() {
    const savedTab = localStorage.getItem('activeTab');
    if (savedTab) {
        // Aggiorna mobile nav
        const mobileNavItem = document.querySelector(`.mobile-nav-item[data-tab="${savedTab}"]`);
        if (mobileNavItem) {
            document.querySelectorAll('.mobile-nav-item').forEach(item => item.classList.remove('active'));
            mobileNavItem.classList.add('active');
        }

        // Aggiorna desktop sidebar
        document.querySelectorAll('.tab-link').forEach(link => {
            link.classList.remove('active');
            if (link.dataset.tab === savedTab) {
                link.classList.add('active');
            }
        });

        // Mostra il tab salvato
        document.querySelectorAll('.page-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        const savedTabContent = document.getElementById(savedTab);
        if (savedTabContent) {
            savedTabContent.classList.add('active');
        }
    }
}

// ==========================================
// GESTIONE SIDEBAR
// ==========================================

/**
 * Inizializza la sidebar con persistenza stato
 */
function initSidebar() {
    const checkboxInput = document.querySelector(Gestionali.selectors.checkboxInput);
    const sidebar = document.querySelector(Gestionali.selectors.sidebar);
    
    if (checkboxInput && sidebar) {
        const sidebarState = localStorage.getItem('sidebarOpen');
        if (sidebarState !== null) {
            checkboxInput.checked = sidebarState === 'true';
            if (sidebarState === 'true') {
                sidebar.classList.add('open');
            } else {
                sidebar.classList.remove('open');
            }
        }

        checkboxInput.addEventListener('change', () => {
            localStorage.setItem('sidebarOpen', checkboxInput.checked);
            if (checkboxInput.checked) {
                sidebar.classList.add('open');
            } else {
                sidebar.classList.remove('open');
            }
        });
    }
}

// ==========================================
// GESTIONE HAMBURGER MENU
// ==========================================

/**
 * Inizializza l'hamburger menu
 */
function initHamburgerMenu() {
    const ham = document.getElementById('hamburger');
    const drop = document.getElementById('dropdown');

    if (ham && drop) {
        ham.addEventListener('click', () => {
            ham.classList.toggle('active');
            drop.classList.toggle('show');
        });
    }

    // Gestione submenu
    document.querySelectorAll('.menu-main').forEach(main => {
        main.addEventListener('click', () => {
            const targetId = main.dataset.target;
            const targetMenu = document.getElementById(targetId);

            document.querySelectorAll('.submenu').forEach(menu => {
                if (menu !== targetMenu) {
                    menu.classList.remove('open');
                    if (menu.previousElementSibling) {
                        menu.previousElementSibling.classList.remove('open');
                    }
                }
            });

            targetMenu.classList.toggle('open');
            main.classList.toggle('open');
        });
    });

    // Click su menu item
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', () => {
            if (item.dataset.link) {
                window.location.href = item.dataset.link;
            }
        });
    });
}

// ==========================================
// GESTIONE USER DROPDOWN
// ==========================================

/**
 * Inizializza il dropdown utente
 */
function initUserDropdown() {
    const userBox = document.querySelector(Gestionali.selectors.userBox);
    const userDropdown = document.querySelector(Gestionali.selectors.userDropdown);

    if (userBox && userDropdown) {
        userBox.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!userBox.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });
    }
}

// ==========================================
// GESTIONE LOGOUT
// ==========================================

/**
 * Inizializza il modal di logout
 */
function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutOverlay = document.querySelector(Gestionali.selectors.logoutOverlay);
    const logoutModal = document.querySelector(Gestionali.selectors.logoutModal);
    const cancelLogout = document.getElementById('cancelLogout');
    const confirmLogout = document.getElementById('confirmLogout');

    if (logoutBtn && logoutOverlay && logoutModal) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            logoutOverlay.classList.add('show');
            logoutModal.classList.add('show');
        });

        const closeLogout = () => {
            logoutOverlay.classList.remove('show');
            logoutModal.classList.remove('show');
        };

        if (cancelLogout) cancelLogout.addEventListener('click', closeLogout);
        logoutOverlay.addEventListener('click', closeLogout);

        if (confirmLogout) {
            confirmLogout.addEventListener('click', () => {
                window.location.href = 'logout.php';
            });
        }
    }
}

// ==========================================
// GESTIONE FILE/FOTO
// ==========================================

/**
 * Inizializza la preview delle foto
 */
function initFotoPreview(inputId, previewId, fileNameId, clearBtnId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const fileNameSpan = document.getElementById(fileNameId);
    const clearBtn = document.getElementById(clearBtnId);

    if (!input || !preview || !fileNameSpan || !clearBtn) return;

    input.addEventListener('change', function() {
        if (!this.files.length) {
            preview.style.display = 'none';
            fileNameSpan.innerText = 'Nessun file';
            clearBtn.style.display = 'none';
            return;
        }

        const file = this.files[0];
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
        fileNameSpan.innerText = file.name;
        clearBtn.style.display = 'block';
    });

    clearBtn.addEventListener('click', function() {
        input.value = '';
        preview.style.display = 'none';
        fileNameSpan.innerText = 'Nessun file';
        clearBtn.style.display = 'none';
    });
}

// ==========================================
// GESTIONE SCROLL LOCK PER POPUP
// ==========================================

/**
 * Blocca lo scroll del body quando un popup è aperto
 */
function initScrollLock() {
    const popupTargetsSelector = '.modal-box, .popup, .logout-modal, .success-popup, .modal-overlay, .popup-overlay, .logout-overlay';
    const popupShowSelector = '.modal-box.show, .popup.show, .logout-modal.show, .success-popup.show, .modal-overlay.show, .popup-overlay.show, .logout-overlay.show';

    function syncBodyScrollLock() {
        const anyOpen = document.querySelector(popupShowSelector);
        document.body.classList.toggle('popup-open', Boolean(anyOpen));
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

    popupObserver.observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class'] });
    syncBodyScrollLock();
}

// ==========================================
// GESTIONE CLICK SU TAB LINK
// ==========================================

/**
 * Inizializza i tab link della sidebar
 */
function initTabLinks() {
    document.querySelectorAll('.tab-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const target = e.currentTarget.dataset.tab;

            document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.page-tab').forEach(tab => tab.classList.remove('active'));

            e.currentTarget.classList.add('active');
            const targetTab = document.getElementById(target);
            if (targetTab) targetTab.classList.add('active');

            localStorage.setItem('activeTab', target);
        });
    });
}

// ==========================================
// FUNZIONI API COMUNI
// ==========================================

/**
 * Esegue una chiamata API con gestione errori standard
 */
async function apiCall(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// ==========================================
// INIZIALIZZAZIONE GLOBALE
// ==========================================

/**
 * Inizializza tutte le funzionalità comuni
 * Da chiamare nel DOMContentLoaded di ogni pagina
 */
function initGestionali() {
    initTabSync();
    initSidebar();
    initHamburgerMenu();
    initUserDropdown();
    initLogout();
    initScrollLock();
    initTabLinks();

    // Chiudi modali al click sull'overlay
    const overlay = document.querySelector(Gestionali.selectors.overlay);
    if (overlay) {
        overlay.addEventListener('click', () => closeModal());
    }
}

// Esporta le funzioni per l'uso globale
window.Gestionali = Gestionali;
window.openModal = openModal;
window.closeModal = closeModal;
window.showSuccess = showSuccess;
window.hideSuccess = hideSuccess;
window.switchTab = switchTab;
window.escapeHtml = escapeHtml;
window.formatDate = formatDate;
window.formatDateTime = formatDateTime;
window.getTodayDate = getTodayDate;
window.getNowTime = getNowTime;
window.getLocalDateString = getLocalDateString;
window.initGestionali = initGestionali;
window.initFotoPreview = initFotoPreview;
window.apiCall = apiCall;
