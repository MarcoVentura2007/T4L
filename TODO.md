# TODO

## In Progress

## Completed
- [x] Fix JavaScript bug in agenda day selection where weekend days (Saturday/Sunday) cause tab disappearance. Agenda only supports Monday-Friday (0-4). When current day is Saturday (5) or Sunday (6 or -1), default to Monday (0).
  - Fixed in `public/gestionale_amministratore.php`
  - Fixed in `public/gestionale_utenti.php`
  - Fixed in `public/gestionale_contabile.php`
- [x] Add `autocomplete="off"` to password inputs in gestionale to prevent browser from showing previous passwords
  - Fixed in `public/gestionale_ergo_amministratore.php` (2 password inputs)
  - Fixed in `public/gestionale_amministratore.php` (2 password inputs)
