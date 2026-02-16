# Tab State Persistence Fix - TODO

## Files to Fix:
- [x] gestionale_utenti.php - Fix tab content restoration

- [ ] gestionale_contabile.php - Fix tab content restoration  
- [ ] gestionale_amministratore.php - Fix tab content restoration
- [ ] gestionale_ergo_utenti.php - Change to localStorage + fix restoration
- [ ] gestionale_ergo_contabile.php - Add complete persistence implementation
- [ ] gestionale_ergo_amministratore.php - Add complete persistence implementation

## Key Fix Required:
The DOMContentLoaded handler must:
1. Get saved tab from localStorage
2. Show the corresponding tab content (add 'active' class)
3. Update desktop sidebar link active state
4. Update mobile nav item active state

Current code only updates nav states but doesn't show the saved tab's content.
