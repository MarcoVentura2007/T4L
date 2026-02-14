# Implementazione Tab Resoconti per Gestionale Ergo Amministratore

## Steps:

### 1. Create API Files
- [x] Create `public/api/api_resoconto_mensile_ergo.php`
  - Connect to time4allergo database
  - Query iscritto and presenza tables
  - Calculate total hours and cost per user
  - Return JSON response

- [x] Create `public/api/api_resoconto_giornaliero_ergo.php`
  - Connect to time4allergo database
  - Get presences for specific user and month
  - Calculate daily hours and cost
  - Return JSON response (simplified, no activities)

### 2. Update JavaScript in gestionale_ergo_amministratore.php
- [x] Update `caricaResocontiMensili()` function
  - Change API endpoint to api_resoconto_mensile_ergo.php
  - Adapt to Stipendio_Orario field name
  
- [x] Update `caricaResocontoGiorni()` function
  - Change API endpoint to api_resoconto_giornaliero_ergo.php
  - Simplify calendar display (no activity details)
  - Keep hours and salary display

### 3. Testing
- [ ] Verify monthly report loads correctly
- [ ] Verify daily calendar shows correct data
- [ ] Verify hours and salary calculations
