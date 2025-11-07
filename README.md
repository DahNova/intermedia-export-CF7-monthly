# CF7 Monthly Export to Google Sheets

Plugin WordPress per esportare automaticamente o manualmente i dati delle submission di Contact Form 7 su Google Sheets, unificando multiple form in una singola sheet con controllo anti-duplicati.

## Caratteristiche

- ✅ **Export Automatico Mensile**: Programmazione automatica per export il primo giorno di ogni mese
- ✅ **Export Manuale**: Possibilità di esportare on-demand quando necessario
- ✅ **Multi-Form Support**: Esporta da multiple Contact Form 7 contemporaneamente
- ✅ **Unificazione Dati**: Tutti i dati vengono unificati in una singola sheet
- ✅ **Controllo Duplicati**: Sistema intelligente per evitare duplicazioni dei dati
- ✅ **Notifiche Email**: Ricevi notifiche dopo ogni export automatico
- ✅ **Interfaccia Admin User-Friendly**: Pannello di configurazione intuitivo
- ✅ **Statistiche in Tempo Reale**: Visualizza quante submission sono in attesa di export

## Requisiti

- WordPress 5.8 o superiore
- PHP 7.4 o superiore
- Contact Form 7 plugin
- **Flamingo** O **Contact Form CFDB7** (per salvare le submission nel database)
- Composer (per installare le dipendenze Google API - solo per sviluppatori)
- Account Google Cloud con Google Sheets API abilitata

## Installazione

### Metodo 1: Download dal Release (CONSIGLIATO)

Questo è il metodo più semplice e veloce, non richiede Composer.

1. **Scarica il plugin**:
   - Vai alla pagina [Releases](https://github.com/DahNova/intermedia-export-CF7-monthly/releases)
   - Scarica l'ultimo file `cf7-monthly-export-X.X.X.zip`

2. **Installa in WordPress**:
   - Vai su **WordPress Admin → Plugin → Aggiungi Nuovo**
   - Clicca su **Carica Plugin**
   - Seleziona il file ZIP scaricato
   - Clicca su **Installa Ora**
   - Clicca su **Attiva Plugin**

3. **Configura il plugin**:
   - Vai su **Impostazioni → CF7 Export**
   - Segui la guida di configurazione sotto

### Metodo 2: Installazione da Codice Sorgente (Per Sviluppatori)

Questo metodo richiede Composer e Git.

1. **Clona il repository**:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/DahNova/intermedia-export-CF7-monthly.git cf7-monthly-export
   cd cf7-monthly-export
   ```

2. **Installa le dipendenze**:
   ```bash
   composer install
   ```

3. **Attiva il plugin**:
   - Vai su **WordPress Admin → Plugin**
   - Trova "CF7 Monthly Export to Google Sheets"
   - Clicca su **Attiva**

## Configurazione

### 1. Configurazione Google Cloud

#### a. Crea un Progetto Google Cloud

1. Vai su [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuovo progetto o seleziona uno esistente
3. Annota il nome del progetto

#### b. Abilita Google Sheets API

1. Nel tuo progetto, vai su **APIs & Services → Library**
2. Cerca "Google Sheets API"
3. Clicca su "Google Sheets API" e poi su "Enable"

#### c. Crea un Service Account

1. Vai su **APIs & Services → Credentials**
2. Clicca su **Create Credentials → Service Account**
3. Compila i dettagli:
   - **Service account name**: CF7 Export (o un nome a tua scelta)
   - **Service account ID**: verrà generato automaticamente
   - **Description**: Service account per export CF7
4. Clicca su **Create and Continue**
5. Salta i passaggi opzionali e clicca su **Done**

#### d. Scarica le Credenziali

1. Clicca sul service account appena creato
2. Vai alla tab **Keys**
3. Clicca su **Add Key → Create new key**
4. Seleziona **JSON** come formato
5. Clicca su **Create** per scaricare il file JSON

### 2. Prepara Google Sheets

1. Crea un nuovo Google Spreadsheet o apri uno esistente
2. Copia l'ID del foglio dall'URL:
   ```
   https://docs.google.com/spreadsheets/d/QUESTO-E-LO-SPREADSHEET-ID/edit
   ```
3. Clicca sul pulsante **Share** (Condividi)
4. Aggiungi l'email del service account (la trovi nel file JSON scaricato come `client_email`)
5. Dai i permessi di **Editor**
6. Clicca su **Share**

### 3. Configura il Plugin

1. Vai su **WordPress Admin → Settings → CF7 Export**
2. Compila i campi:

   **Google Sheets Configuration:**
   - **Google Credentials JSON**: Incolla l'intero contenuto del file JSON scaricato
   - **Spreadsheet ID**: Incolla l'ID copiato dall'URL del tuo Google Sheet
   - **Sheet Name**: Nome della tab dove esportare i dati (es: "CF7 Exports")

   **Forms to Export:**
   - Seleziona quali form CF7 vuoi esportare

   **Export Settings:**
   - ☑️ **Enable automatic monthly export**: Abilita l'export automatico mensile
   - ☑️ **Send email notification**: Ricevi email dopo ogni export
   - **Email**: Indirizzo email per le notifiche

3. Clicca su **Save Settings**
4. Clicca su **Test Connection** per verificare la configurazione
5. Se il test ha successo, clicca su **Export Now** per il primo export

## Utilizzo

### Export Manuale

1. Vai su **Settings → CF7 Export**
2. Clicca su **Export Now (New Entries Only)**
3. Verranno esportate solo le nuove submission non ancora esportate

### Export Automatico

Se abilitato nelle impostazioni, il plugin esporterà automaticamente i dati:
- **Quando**: Il primo giorno di ogni mese alle ore 2:00 AM
- **Cosa**: Solo le nuove submission non ancora esportate
- **Notifiche**: Riceverai un'email con il risultato dell'export

### Statistiche

Il pannello admin mostra:
- **Total Forms**: Totale delle form CF7 presenti
- **Forms Selected**: Quante form sono selezionate per l'export
- **Total Submissions**: Totale submission delle form selezionate
- **Already Exported**: Quante submission sono già state esportate
- **Pending Export**: Quante submission sono in attesa di export
- **Last Export**: Data e ora dell'ultimo export
- **Next Scheduled Export**: Data del prossimo export automatico

## Struttura dei Dati Esportati

I dati vengono esportati con le seguenti colonne standard:

| Colonna | Descrizione |
|---------|-------------|
| ID Submissione | ID unico della submission |
| ID Form | ID della form CF7 |
| Titolo Form | Nome della form |
| Data Submissione | Data in formato YYYY-MM-DD |
| Ora Submissione | Ora in formato HH:MM:SS |
| [Campi Form] | Tutti i campi della form |

**Note:**
- Tutte le form vengono unificate in una singola sheet
- Se una form ha campi che altre non hanno, le celle vuote vengono riempite automaticamente
- I campi checkbox multipli vengono uniti con virgole
- La prima riga (header) viene automaticamente formattata in grassetto e congelata

## Funzionalità Avanzate

### Controllo Duplicati

Il plugin mantiene un registro delle submission già esportate per evitare duplicati. Ogni submission viene tracciata tramite il suo ID unico.

### Gestione Cron

Il cron di WordPress viene utilizzato per schedulare l'export automatico. Puoi:
- Verificare il prossimo export nella sezione Statistics
- Disabilitare l'export automatico nelle impostazioni
- Il cron viene automaticamente riprogrammato per il primo giorno del mese successivo

### Notifiche Email

Se abilitate, riceverai email per:
- ✅ **Export riuscito**: Con dettagli del numero di submission esportate
- ❌ **Export fallito**: Con messaggio di errore per debug

## Troubleshooting

### Il test di connessione fallisce

**Problema**: Errore "Failed to initialize Google Sheets client"

**Soluzione**:
1. Verifica che il JSON delle credenziali sia valido (il campo diventa verde se valido)
2. Assicurati di aver abilitato Google Sheets API nel tuo progetto
3. Verifica che il service account sia stato creato correttamente

### Export fallisce con "Permission denied"

**Problema**: Il plugin non può scrivere nel foglio

**Soluzione**:
1. Verifica di aver condiviso il foglio con l'email del service account
2. Assicurati di aver dato i permessi di **Editor** (non solo Viewer)
3. L'email del service account si trova nel JSON come `client_email`

### Nessuna submission viene trovata

**Problema**: "No submissions found to export"

**Soluzione**:
1. Verifica che Contact Form CFDB7 sia installato e attivo
2. Controlla che ci siano submission salvate nel database
3. Assicurati di aver selezionato almeno una form nelle impostazioni

### Composer non funziona

**Problema**: "composer: command not found"

**Soluzione**:
```bash
# Installa Composer se non è già installato
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

Oppure scarica manualmente da: https://getcomposer.org/download/

### Dipendenze mancanti

**Problema**: "Google API Client library not found"

**Soluzione**:
```bash
cd wp-content/plugins/cf7-monthly-export
composer install --no-dev --optimize-autoloader
```

## Sicurezza

⚠️ **IMPORTANTE**: Le credenziali Google vengono salvate nel database di WordPress. Assicurati di:

1. **Mai committare credenziali**: Il `.gitignore` esclude già i file JSON
2. **Backup sicuro**: Proteggi i backup del database
3. **Accesso limitato**: Solo gli amministratori possono accedere alle impostazioni
4. **HTTPS**: Usa sempre HTTPS sul tuo sito WordPress
5. **Permessi minimi**: Il service account dovrebbe avere accesso solo al foglio necessario

## Struttura File

```
cf7-monthly-export/
├── cf7-monthly-export.php          # File principale del plugin
├── composer.json                    # Dipendenze PHP
├── README.md                        # Questa documentazione
├── .gitignore                       # File da escludere dal versioning
├── includes/                        # Classi PHP del plugin
│   ├── class-google-sheets-client.php    # Client Google Sheets API
│   ├── class-cf7-exporter.php            # Logica export CF7
│   ├── class-cron-handler.php            # Gestione cron
│   └── class-admin-page.php              # Interfaccia admin
└── admin/                           # Assets admin
    ├── views/
    │   └── settings-page.php        # Template pagina settings
    ├── css/
    │   └── admin-style.css          # Stili admin
    └── js/
        └── admin-script.js          # JavaScript admin
```

## Changelog

### Version 1.1.0 (2025-11-07) - Flamingo Support (Beta)
- ✨ Nuova funzionalità: Supporto per **Flamingo** come storage backend **(BETA)**
- ✨ Nuova funzionalità: Selezione storage backend (Auto-detect / Flamingo / CFDB7)
- ⚡ Migliorato: Auto-detect automatico con conferma visiva
- ⚡ Migliorato: Architettura flessibile per supportare multiple fonti dati
- 📝 Aggiornato: Requisiti - ora richiede Flamingo **O** CFDB7 (non più solo CFDB7)
- 🔧 Fix: Match corretto tramite taxonomy `flamingo_inbound_channel`
- 🔧 Fix: Lettura campi da meta `_field_*` invece di `_fields`
- 🔧 Fix: Filtro corretto per `post_status` e `_submission_status`

### Version 1.0.4 (2025-11-07)
- 🔧 Fix: Supporto esteso fino a 702 colonne (A:ZZ)
- 🔧 Fix: Correzione inserimento dati dalla colonna A
- ⚡ Migliorato: Parametro insertDataOption per append affidabile

### Version 1.0.3 (2025-11-07)
- 🔧 Fix: Correzione range per append dalla colonna A corretta
- 🔧 Fix: Aggiunto parametro INSERT_ROWS

### Version 1.0.2 (2025-11-07)
- ✨ Nuova funzionalità: Schedule flessibile (Daily/Weekly/Monthly)
- ✨ Nuova funzionalità: Pulsante "Test Cron Now" per testare l'export automatico
- ⚡ Migliorato: Auto-reschedule quando si cambia frequenza

### Version 1.0.1 (2025-11-07)
- 🔧 Fix: Deduplicazione basata su Google Sheets (source of truth)
- 🔧 Fix: Statistiche accurate leggendo da Google Sheets
- 🔧 Fix: Mark as exported anche per full export
- ⚡ Migliorato: Fallback al DB locale se Sheets non raggiungibile

### Version 1.0.0 (2025-11-07)
- ✨ Release iniziale
- ✅ Export automatico mensile
- ✅ Export manuale on-demand
- ✅ Supporto multi-form
- ✅ Unificazione dati
- ✅ Controllo duplicati
- ✅ Notifiche email
- ✅ Interfaccia admin completa

## Supporto

Per problemi, bug o richieste di funzionalità:
- **GitHub Issues**: [https://github.com/DahNova/intermedia-export-CF7-monthly/issues](https://github.com/DahNova/intermedia-export-CF7-monthly/issues)

## Licenza

GPL v2 or later

## Crediti

Sviluppato da [Claudio Novaglio](https://github.com/DahNova)

### Librerie utilizzate:
- [Google API PHP Client](https://github.com/googleapis/google-api-php-client) - Apache License 2.0
- Contact Form 7 - GPL v2
- Contact Form CFDB7 - GPL v2

## Per Sviluppatori

### Creare un Release

Se vuoi creare un nuovo release del plugin:

**Metodo Automatico (GitHub Actions)**:
1. Aggiorna la versione in `cf7-monthly-export.php`
2. Committa: `git commit -am "Bump version to X.X.X"`
3. Crea tag: `git tag vX.X.X`
4. Pusha: `git push && git push --tags`
5. GitHub Actions creerà automaticamente il release con il file ZIP

**Metodo Manuale**:
```bash
./build.sh
```

Il file ZIP pronto per l'installazione sarà in `dist/cf7-monthly-export-X.X.X.zip`

Per maggiori dettagli vedi [RELEASE.md](RELEASE.md)

## Contribuire

I contributi sono benvenuti! Per contribuire:

1. Fai un fork del repository
2. Crea un branch per la tua feature (`git checkout -b feature/AmazingFeature`)
3. Committa i tuoi cambiamenti (`git commit -m 'Add some AmazingFeature'`)
4. Pusha sul branch (`git push origin feature/AmazingFeature`)
5. Apri una Pull Request

### Setup Ambiente di Sviluppo

```bash
# Clona il repository
git clone https://github.com/DahNova/intermedia-export-CF7-monthly.git
cd intermedia-export-CF7-monthly

# Installa dipendenze
composer install

# Il plugin è pronto per essere testato in WordPress
```

---

Made with ❤️ for the WordPress community
