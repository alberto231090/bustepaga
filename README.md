# Buste Paga Plugin

Questo repository contiene un semplice plugin WordPress denominato **Buste Paga Dipendenti**. Il plugin permette di:

- Sincronizzare gli eventi da Google Calendar (mediante configurazione JSON delle API) per calcolare le ore di lavoro, ferie, malattia e recupero.
- Inviare un riepilogo in formato JSON all'indirizzo email del commercialista configurato.
- Fornire ai dipendenti (ruolo "Dipendente") l'accesso alle proprie buste paga caricate in formato PDF.

## Installazione

1. Copiare la cartella `plugin/bustepaga` all'interno dell'installazione WordPress (oppure creare un archivio ZIP della cartella e caricarlo tramite **Carica plugin**).
2. Attivare il plugin dal pannello di amministrazione.
3. Accedere alla voce **Buste Paga** per inserire il JSON delle credenziali Google e l'email del commercialista.
4. Utilizzare lo shortcode `[bustepaga_payslips]` in una pagina per mostrare ai dipendenti le proprie buste paga.

La sincronizzazione con Google Calendar avviene tramite un cron job WordPress impostato su base oraria (può essere modificato). Nella funzione `sync_calendar` è presente un esempio di struttura da personalizzare con le Google API PHP Client per recuperare gli eventi e popolare il riepilogo delle ore.

