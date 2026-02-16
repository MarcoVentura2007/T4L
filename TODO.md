# Fix Tab Persistence in Gestionale Files

## Problem
Inconsistent storage mechanisms causing tabs to not persist in desktop version:
- Some files use `sessionStorage` (temporary, clears on browser close)
- Some files use `localStorage` (persistent)

## Files to Fix
- [x] gestionale_amministratore.php - Change sessionStorage to localStorage
- [x] gestionale_contabile.php - Change sessionStorage to localStorage
- [x] gestionale_ergo_utenti.php - Change sessionStorage to localStorage



## Solution
Standardize all files to use `localStorage` for tab persistence.
