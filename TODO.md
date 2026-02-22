# Fix Allegati Upload - TODO

## Problemi Identificati:
1. **Mismatch Schema Database**: La tabella `allegati` ha solo `id`, `file`, `ID_Iscritto` - mancano `data_upload` e `nome_file`
2. **API Inconsistenti**: Le API cercano colonne che non esistono nel DB
3. **Integrazione JS Mancante**: La funzione `uploadAllegati()` non viene chiamata dopo aggiunta utente
4. **Possibili problemi permessi cartella**: La cartella `public/allegati/` potrebbe non essere scrivibile

## Piano di Risoluzione:

### Fase 1: Aggiornamento Database ✅ COMPLETATO
- [x] Aggiungere colonna `data_upload` (DATETIME) alla tabella `allegati` - AUTO-MIGRAZIONE implementata nelle API
- [x] Aggiungere colonna `nome_file` (VARCHAR 255) alla tabella `allegati` - AUTO-MIGRAZIONE implementata nelle API

### Fase 2: Correzione API ✅ COMPLETATO
- [x] **api_carica_allegato.php**: Corretta query INSERT con colonne `data_upload` e `nome_file`
- [x] **api_get_allegati.php**: Aggiornata query SELECT con alias `file as percorso`

### Fase 3: Integrazione JavaScript ✅ COMPLETATO
- [x] Modificare il form di aggiunta utente per chiamare `uploadAllegati()` dopo la creazione dell'utente
- [x] Passare l'ID del nuovo utente alla funzione di upload
- [x] Aggiungere sezione allegati nel modal di visualizzazione utente

**Nota**: La funzione `uploadAllegati()` è già definita nel file. È necessario modificare il handler `formAggiungiUtente.onsubmit` per:
1. Rendere la funzione `async`
2. Chiamare `await uploadAllegati(data.id)` dopo la creazione dell'utente
3. Passare l'ID del nuovo utente (restituito da `api_aggiungi_utente.php`)

**Codice da modificare in `gestionale_amministratore.php` (riga ~1610):**

```javascript
// Submit form
formAggiungiUtente.onsubmit = async function(e) {
    e.preventDefault();

    const formData = new FormData();
    formData.append("nome", document.getElementById("utenteNome").value.trim());
    // ... altri campi ...

    try {
        const res = await fetch("api/api_aggiungi_utente.php", {
            method: "POST",
            body: formData
        });
        const data = await res.json();
        
        if(data.success){       
            // Upload allegati se ce ne sono
            if (selectedAllegati.length > 0 && data.id) {
                await uploadAllegati(data.id);
            }
            
            modalAggiungiUtente.classList.remove("show");
            successText.innerText = "Utente Aggiunto!!";
            showSuccess(successPopup, Overlay);
            
            setTimeout(() => {
                hideSuccess(successPopup, Overlay);
                if(Overlay) Overlay.classList.remove("show");
                location.reload();
            }, 1800); 
        } else {
            alert("Errore: " + data.message);
        }
    } catch(err) {
        console.error(err);
        alert("Errore nel caricamento!");
    }
};
```

**Sezione Allegati nel View Modal - AGGIUNTA ✅**
- Aggiunto HTML per visualizzare gli allegati nel modal di visualizzazione utente
- Aggiunta funzione JavaScript `caricaAllegatiUtente()` per caricare gli allegati dall'API
- Aggiunta funzione `getAllegatoIcon()` per mostrare le icone appropriate per tipo di file
- Gli allegati vengono caricati automaticamente quando si apre il modal di visualizzazione



### Fase 4: Verifica Directory ✅ COMPLETATO
- [x] La cartella `public/allegati/` esiste e viene creata automaticamente se mancante


## Query SQL necessarie:
```sql
ALTER TABLE allegati ADD COLUMN data_upload DATETIME DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE allegati ADD COLUMN nome_file VARCHAR(255) DEFAULT NULL;
