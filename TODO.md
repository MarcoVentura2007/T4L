# TODO: Update APIs and Pages for Database Schema Changes

## Task: Split `contatti` into `email` and `telefono` fields

### Phase 1: Update APIs for time4all database
- [x] `public/api/api_aggiungi_utente.php` - Update to use email and telefono
- [x] `public/api/api_aggiorna_utente.php` - Update to use email and telefono


### Phase 2: Update APIs for time4allergo database
- [x] `public/api/api_aggiungi_utente_ergo.php` - Update to use email and telefono
- [x] `public/api/api_modifica_utente_ergo.php` - Update to use email and telefono


### Phase 3: Update Pages - time4all (Centro Diurno)
- [x] `public/gestionale_utenti.php` - Update SQL, data attributes, and JavaScript
- [x] `public/gestionale_contabile.php` - Update SQL, forms, and JavaScript


### Phase 4: Update Pages - time4allergo (Ergoterapeutica)
- [x] `public/gestionale_ergo_utenti.php` - Update SQL, table, and JavaScript
- [x] `public/gestionale_ergo_contabile.php` - Update SQL, table, and JavaScript
- [x] `public/gestionale_ergo_amministratore.php` - Update SQL, table, and JavaScript



### Phase 5: Testing and Verification
- [x] Test all APIs with new schema
- [x] Verify all pages display email/telefono correctly
- [x] Test add/edit user functionality
