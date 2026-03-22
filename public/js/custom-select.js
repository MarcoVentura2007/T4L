/* ══════════════════════════════════════════════════════════════════
   CUSTOM SELECT — JS
   Aggiungere come <script src="js/custom-select.js"></script>
   oppure incollare il contenuto in fondo allo <script> esistente
   (prima del tag </script> finale)
══════════════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    // Label leggibile da mostrare nell'header del panel
    const LABEL_MAP = {
        'utenteGruppo': 'Tipo di lavoro',
        'editGruppo': 'Tipo di lavoro',
        'apIscritto': 'Iscritto',
        'agendaData': 'Data',
        'agendaAttivita': 'Attività',
        'accountClasse': 'Classe',
        'editAccountClasse': 'Classe',
    };

    // Selects da NON trasformare (fuori dai modal, o già gestiti)
    const EXCLUDE_IDS = ['formatoDownload', 'resocontiMeseFiltro'];

    // Classi da escludere
    const EXCLUDE_CLASSES = ['ragazzo-gruppo'];

    let openPanel = null; // riferimento al panel aperto

    // ── Inizializza un singolo <select> ─────────────────────────────
    function initSelect(sel) {
        if (sel.dataset.csInit) return; // già inizializzato
        if (EXCLUDE_IDS.includes(sel.id)) return;
        if (EXCLUDE_CLASSES.some(cls => sel.classList.contains(cls))) return;
        // Solo select dentro .modal-box
        if (!sel.closest('.modal-box')) return;

        sel.dataset.csInit = '1';

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper';

        const label = LABEL_MAP[sel.id] || null;

        const trigger = document.createElement('div');
        trigger.className = 'cs-trigger';
        trigger.setAttribute('tabindex', '0');
        trigger.innerHTML = `
            <span class="cs-trigger-text cs-placeholder"></span>
            <span class="cs-arrow">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </span>`;

        const panel = document.createElement('div');
        panel.className = 'cs-panel';
        if (label) {
            const hdr = document.createElement('div');
            hdr.className = 'cs-header';
            hdr.textContent = label;
            panel.appendChild(hdr);
        }

        sel.parentNode.insertBefore(wrapper, sel);
        wrapper.appendChild(sel);   // sposta il select dentro il wrapper
        wrapper.appendChild(trigger);
        wrapper.appendChild(panel);

        // ── Popola le opzioni ──────────────────────────────────────
        function populateOptions() {
            // Rimuovi opzioni precedenti (ma mantieni header)
            const existing = panel.querySelectorAll('.cs-option');
            existing.forEach(o => o.remove());

            Array.from(sel.options).forEach(opt => {
                const item = document.createElement('div');
                item.className = 'cs-option';
                item.dataset.value = opt.value;
                item.textContent = opt.text;
                if (opt.selected) item.classList.add('cs-selected');
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectOption(opt.value);
                    closePanel();
                });
                panel.appendChild(item);
            });

            syncTrigger();
        }

        // ── Aggiorna testo trigger ─────────────────────────────────
        function syncTrigger() {
            const txt = trigger.querySelector('.cs-trigger-text');
            const selectedOpt = sel.options[sel.selectedIndex];
            if (selectedOpt && selectedOpt.value !== '') {
                txt.textContent = selectedOpt.text;
                txt.classList.remove('cs-placeholder');
            } else {
                txt.textContent = selectedOpt ? selectedOpt.text : '';
                txt.classList.add('cs-placeholder');
            }
            // Marca selected nel panel
            panel.querySelectorAll('.cs-option').forEach(o => {
                o.classList.toggle('cs-selected', o.dataset.value === sel.value);
            });
        }

        // ── Seleziona un valore ────────────────────────────────────
        function selectOption(val) {
            sel.value = val;
            // Dispara change per il codice esistente
            sel.dispatchEvent(new Event('change', { bubbles: true }));
            syncTrigger();
        }

        // ── Apri/chiudi panel ──────────────────────────────────────
        function openPanel_() {
            if (openPanel && openPanel !== panel) closeOtherPanel();
            populateOptions();
            panel.classList.add('cs-panel-open');
            trigger.classList.add('cs-open');
            openPanel = panel;
            // Scroll sull'opzione selezionata
            const sel_ = panel.querySelector('.cs-selected');
            if (sel_) sel_.scrollIntoView({ block: 'nearest' });
        }
        function closePanel() {
            panel.classList.remove('cs-panel-open');
            trigger.classList.remove('cs-open');
            if (openPanel === panel) openPanel = null;
        }
        function closeOtherPanel() {
            if (openPanel) {
                openPanel.classList.remove('cs-panel-open');
                openPanel.previousElementSibling && openPanel.previousElementSibling.classList.remove('cs-open');
                openPanel = null;
            }
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            panel.classList.contains('cs-panel-open') ? closePanel() : openPanel_();
        });
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPanel_(); }
            if (e.key === 'Escape') closePanel();
        });

        // Osserva modifiche al select (es. popolaIscritti aggiunge opzioni)
        const observer = new MutationObserver(() => {
            if (panel.classList.contains('cs-panel-open')) populateOptions();
            else syncTrigger();
        });
        observer.observe(sel, { childList: true, subtree: true, attributes: true });

        // Sync iniziale
        populateOptions();
    }

    // ── Chiudi tutti i panel cliccando fuori ─────────────────────────
    document.addEventListener('click', () => {
        if (openPanel) {
            openPanel.classList.remove('cs-panel-open');
            const wrapper_ = openPanel.closest('.custom-select-wrapper');
            if (wrapper_) wrapper_.querySelector('.cs-trigger')?.classList.remove('cs-open');
            openPanel = null;
        }
    });

    // ── Inizializza tutti i select nei modal (anche futuri) ──────────
    function initAll() {
        document.querySelectorAll('.modal-box select').forEach(initSelect);
    }

    // Inizializza al caricamento
    document.addEventListener('DOMContentLoaded', initAll);

    // Osserva nuovi modal aggiunti al DOM o modal che diventano .show
    const bodyObserver = new MutationObserver((mutations) => {
        mutations.forEach(m => {
            m.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;
                if (node.classList?.contains('modal-box') || node.querySelector?.('.modal-box')) {
                    initAll();
                }
            });
            // Quando un modal apre (.show aggiunto)
            if (m.type === 'attributes' && m.attributeName === 'class') {
                const el = m.target;
                if (el.classList?.contains('modal-box') && el.classList?.contains('show')) {
                    el.querySelectorAll('select').forEach(initSelect);
                }
            }
        });
    });
    bodyObserver.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class'],
    });

    // Esponi per uso manuale se necessario
    window.initCustomSelects = initAll;
})();