# TODO - Aggiornamento connessioni database

## Obiettivo
Modificare tutte le pagine PHP in public/ per usare db_connection.php invece di connessioni mysqli dirette.

## File da modificare

- [x] 1. centrodiurno.php (time4all)
- [x] 2. ergoterapeutica.php (time4all)
- [x] 3. fogliofirme-centro.php (time4all)
- [x] 4. gestionale_amministratore.php (time4all)
- [x] 5. gestionale_contabile.php (time4all)
- [ ] 6. gestionale_ergo_amministratore.php (time4all + time4allergo)
- [ ] 7. gestionale_ergo_utenti.php (time4all + time4allergo)
- [ ] 8. gestionale_ergo_contabile.php (time4all + time4allergo)
- [x] 9. gestionale_utenti.php (time4all)
- [x] 10. gestionale_ergo_amministratore.php (time4all + time4allergo)
- [x] 11. presenze-ergo.php (time4all)
- [x] 12. gestionale_ergo_utenti.php (time4all + time4allergo)
- [x] 13. gestionale_ergo_contabile.php (time4all + time4allergo)

## Pattern di modifica

### Per connessione singola (time4all):
```php
// Rimuovere:
$host = "localhost";    
$user = "root";         
$pass = "";             
$db   = "time4all"; 
$conn = new mysqli($host, $user, $pass, $db);

// Aggiungere:
require __DIR__ . '/../data/db_connection.php';
$conn = getDbConnection('time4all');
```

### Per connessione singola (time4allergo):
```php
// Rimuovere:
$host = "localhost";    
$user = "root";         
$pass = "";             
$db   = "time4allergo"; 
$conn = new mysqli($host, $user, $pass, $db);

// Aggiungere:
require __DIR__ . '/../data/db_connection.php';
$conn = getDbConnection('time4allergo');
```

### Per doppia connessione:
```php
// Rimuovere entrambe le connessioni hardcoded
// Aggiungere require una volta
// Usare getDbConnection() per ogni connessione
```

