/**
 * Index Loader - Mostra il loader solo se il caricamento dura più di 400ms
 * Se la pagina carica in meno di 400ms, il loader non viene mai mostrato
 */

(function() {
    'use strict';
    
    const loader = document.getElementById('page-loader');
    if (!loader) return;
    
    let loaderTimeout = null;
    let loaderVisible = false;
    
    // Mostra il loader dopo 400ms (solo se la pagina non è ancora caricata)
    function showLoader() {
        if (loader && !loaderVisible) {
            loader.classList.add('show');
            loaderVisible = true;
        }
    }
    
    // Nascondi il loader
    function hideLoader() {
        // Cancella il timeout per mostrare il loader
        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
            loaderTimeout = null;
        }
        
        if (!loader) return;
        
        // Se il loader non è mai stato mostrato (caricamento < 400ms)
        // lo rimuoviamo immediatamente senza animazione
        if (!loaderVisible) {
            loader.style.display = 'none';
            loader.style.visibility = 'hidden';
            loader.style.opacity = '0';
        } else {
            // Se il loader è visibile, usiamo la transizione di uscita
            loader.classList.remove('show');
            loader.classList.add('hidden');
            setTimeout(function() {
                loader.style.display = 'none';
            }, 400);
        }
    }
    
    // Imposta il timeout per mostrare il loader dopo 400ms
    loaderTimeout = setTimeout(showLoader, 700);
    
    // Nascondi il loader quando tutto è caricato
    if (document.readyState === 'complete') {
        // Pagina già caricata
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader);
    }
    
    // Fallback di sicurezza: nascondi dopo 5 secondi
    setTimeout(hideLoader, 5000);
})();
