/**
 * Loader avanzato - Attende il caricamento 100% di tutte le risorse
 * Mostra il loader solo se il caricamento dura più di 300ms
 * Nasconde il loader solo quando DOM, immagini, font e CSS sono pronti
 */

(function() {
    'use strict';
    
    const loader = document.getElementById('page-loader');
    if (!loader) return;
    
    let loaderShown = false;
    let loaderTimeout = null;
    let resourcesLoaded = false;
    let domReady = false;
    
    // Mostra il loader dopo 300ms (solo se necessario)
    function showLoader() {
        if (!loaderShown && !resourcesLoaded) {
            loader.classList.add('show');
            loaderShown = true;
        }
    }
    
    // Nascondi il loader con animazione
    function hideLoader() {
        // Cancella il timeout per mostrare il loader
        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
            loaderTimeout = null;
        }
        
        if (!loader) return;
        
        // Se il loader non è mai stato mostrato (caricamento < 300ms)
        if (!loaderShown) {
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
    
    // Verifica se tutte le immagini sono caricate
    function checkImagesLoaded() {
        const images = document.querySelectorAll('img');
        let totalImages = images.length;
        
        if (totalImages === 0) return Promise.resolve();
        
        return new Promise((resolve) => {
            let checkedImages = 0;
            
            function checkComplete() {
                checkedImages++;
                if (checkedImages >= totalImages) {
                    resolve();
                }
            }
            
            images.forEach((img) => {
                // Se l'immagine è già caricata
                if (img.complete && img.naturalHeight !== 0) {
                    checkComplete();
                } else {
                    // Altrimenti aspetta il caricamento
                    const onLoad = function() {
                        img.removeEventListener('load', onLoad);
                        img.removeEventListener('error', onError);
                        checkComplete();
                    };
                    
                    const onError = function() {
                        img.removeEventListener('load', onLoad);
                        img.removeEventListener('error', onError);
                        checkComplete();
                    };
                    
                    img.addEventListener('load', onLoad);
                    img.addEventListener('error', onError);
                }
            });
            
            // Fallback: se dopo 10 secondi non sono caricate, procedi comunque
            setTimeout(() => {
                resolve();
            }, 7000);
        });
    }
    
    // Verifica se i font sono caricati
    function checkFontsLoaded() {
        if (document.fonts && document.fonts.ready) {
            return document.fonts.ready;
        }
        return Promise.resolve();
    }
    
    // Verifica se i CSS sono caricati (con gestione errori CORS)
    function checkStylesheetsLoaded() {
        const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
        let totalSheets = stylesheets.length;
        
        if (totalSheets === 0) return Promise.resolve();
        
        return new Promise((resolve) => {
            let checkedSheets = 0;
            
            function checkComplete() {
                checkedSheets++;
                if (checkedSheets >= totalSheets) {
                    resolve();
                }
            }
            
            stylesheets.forEach((sheet) => {
                // Prova a verificare se il CSS è caricato
                try {
                    // Se il CSS è già caricato (può lanciare SecurityError per CORS)
                    if (sheet.sheet && sheet.sheet.cssRules) {
                        checkComplete();
                    } else {
                        // Aspetta il caricamento
                        const onLoad = function() {
                            sheet.removeEventListener('load', onLoad);
                            sheet.removeEventListener('error', onError);
                            checkComplete();
                        };
                        
                        const onError = function() {
                            sheet.removeEventListener('load', onLoad);
                            sheet.removeEventListener('error', onError);
                            checkComplete();
                        };
                        
                        sheet.addEventListener('load', onLoad);
                        sheet.addEventListener('error', onError);
                    }
                } catch (e) {
                    // Errore CORS o altro, considera come caricato
                    checkComplete();
                }
            });
            
            // Fallback dopo 5 secondi
            setTimeout(() => {
                resolve();
            }, 5000);
        });
    }
    
    // Funzione principale che verifica tutto
    async function checkAllResources() {
        if (resourcesLoaded) return;
        
        // Aspetta che il DOM sia pronto
        if (!domReady) {
            if (document.readyState === 'loading') {
                await new Promise(resolve => {
                    document.addEventListener('DOMContentLoaded', resolve, { once: true });
                });
            }
            domReady = true;
        }
        
        // Verifica tutte le risorse in parallelo
        await Promise.all([
            checkImagesLoaded(),
            checkFontsLoaded(),
            checkStylesheetsLoaded()
        ]);
        
        // Aspetta anche l'evento window.load se non è già stato triggerato
        if (document.readyState !== 'complete') {
            await new Promise(resolve => {
                window.addEventListener('load', resolve, { once: true });
            });
        }
        
        resourcesLoaded = true;
        hideLoader();
    }
    
    // Imposta il timeout per mostrare il loader dopo 300ms
    loaderTimeout = setTimeout(showLoader, 300);
    
    // Avvia il controllo delle risorse
    checkAllResources();
    
    // Fallback di sicurezza: nascondi dopo 15 secondi (per pagine molto pesanti)
    setTimeout(function() {
        if (!resourcesLoaded) {
            resourcesLoaded = true;
            hideLoader();
        }
    }, 15000);
    
})();
