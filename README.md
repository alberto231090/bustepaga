# Buste Paga

Questo repository contiene un esempio di plugin WordPress (cartella `employee-hours`) che aiuta a calcolare le ore dei dipendenti tramite Google Calendar e a gestire le buste paga.

## Installazione

1. Copia la cartella `employee-hours` all'interno della directory `wp-content/plugins` del tuo sito WordPress.
2. Accedi al pannello di amministrazione di WordPress e attiva il plugin **Employee Hours**.
3. Il plugin crea automaticamente il ruolo "Dipendente".

## Utilizzo

- Assegna ai dipendenti il ruolo `Dipendente` e associa a ciascun utente l'ID del proprio Google Calendar tramite il campo `calendar_id` nei meta utente.
- Utilizza lo shortcode `[employee_documents]` in una pagina per mostrare i documenti (buste paga, MAV, CU) al dipendente loggato.
- Nel menu Utenti &rarr; Payslip Upload potrai caricare i file PDF delle buste paga. Al termine del caricamento verrà inviata una email al dipendente con il link al documento.
- Le buste paga e i riepiloghi mensili vengono salvati nella cartella `wp-content/uploads/payslips/<ID_UTENTE>/`.

### Calcolo ore e invio riepilogo

Richiama le funzioni `eh_fetch_events()` ed `eh_calculate_hours()` per ottenere le ore di lavoro, ferie, malattia e recupero da Google Calendar. Il risultato pu&ograve; essere salvato tramite `eh_save_summary()` e inviato al commercialista tramite `wp_mail()`.

Esempio di utilizzo in codice:

```php
$calendar = get_user_meta($user_id, 'calendar_id', true);
$events = eh_fetch_events($calendar, '2025-07-01T00:00:00Z', '2025-07-31T23:59:59Z', 'API_KEY');
$totals = eh_calculate_hours($events);
eh_save_summary($user_id, '2025-07', $totals);
wp_mail('commercialista@example.com', 'Riepilogo ore', print_r($totals, true));
```

Tutti i dati vengono gestiti in JSON per facilitare eventuali integrazioni.
