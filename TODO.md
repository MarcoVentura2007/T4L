# Implementazione Modifica Immagine Utente

## File da modificare:

### 1. ✅ public/api/api_aggiorna_utente.php
- [x] Modificare API per supportare multipart/form-data (file upload)
- [x] Aggiungere gestione upload file con validazione
- [x] Supportare sia richieste con file che senza file (JSON)
- [x] Aggiornare query SQL per includere fotografia quando presente

### 2. ✅ public/gestionale_amministratore.php
- [x] Aggiungere input file "Fotografia" nel modal di modifica utente
- [x] Aggiungere preview dell'immagine selezionata
- [x] Aggiungere bottone per rimuovere il file selezionato
- [x] Modificare JavaScript per gestire il cambio file
- [x] Aggiornare saveEdit per usare FormData quando c'è un file

### 3. ✅ public/gestionale_contabile.php
- [x] Aggiungere input file "Fotografia" nel modal di modifica utente
- [x] Aggiungere preview dell'immagine selezionata  
- [x] Aggiungere bottone per rimuovere il file selezionato
- [x] Modificare JavaScript per gestire il cambio file
- [x] Aggiornare saveEdit per usare FormData quando c'è un file


## Note:
- Il design del file input deve essere uguale a quello in "Aggiungi Utente"
- Mantenere compatibilità con modifiche senza cambio immagine (usa JSON)
- Quando c'è un file selezionato, inviare come FormData multipart/form-data
