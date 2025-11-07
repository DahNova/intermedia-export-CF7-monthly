# Guida Rapida all'Installazione

## Installazione Rapida (5 minuti)

### Step 1: Installa le Dipendenze
```bash
cd wp-content/plugins/cf7-monthly-export
composer install
```

### Step 2: Attiva il Plugin
Vai su **WordPress Admin → Plugin** e attiva "CF7 Monthly Export to Google Sheets"

### Step 3: Configura Google Cloud (Prima Volta)

#### 3.1 Crea Progetto e Abilita API
1. Vai su [console.cloud.google.com](https://console.cloud.google.com/)
2. Crea nuovo progetto
3. Abilita **Google Sheets API** (APIs & Services → Library)

#### 3.2 Crea Service Account
1. Vai su **APIs & Services → Credentials**
2. **Create Credentials → Service Account**
3. Compila nome e descrizione → **Create and Continue** → **Done**

#### 3.3 Scarica Credenziali
1. Clicca sul service account creato
2. Tab **Keys** → **Add Key → Create new key**
3. Formato **JSON** → **Create**
4. Salva il file JSON scaricato

### Step 4: Prepara Google Sheets
1. Crea o apri un Google Spreadsheet
2. Copia l'ID dall'URL:
   ```
   https://docs.google.com/spreadsheets/d/IL-TUO-SPREADSHEET-ID/edit
   ```
3. Clicca **Share** (Condividi)
4. Aggiungi l'email del service account (vedi nel JSON: `client_email`)
5. Permessi: **Editor** → **Share**

### Step 5: Configura il Plugin
1. Vai su **Settings → CF7 Export**
2. Incolla il contenuto del file JSON in **Google Credentials JSON**
3. Incolla lo **Spreadsheet ID**
4. Inserisci un nome per la sheet (es: "CF7 Exports")
5. Seleziona le **form CF7 da esportare**
6. Abilita **export automatico** se desiderato
7. **Save Settings**

### Step 6: Test
1. Clicca **Test Connection** → Dovrebbe mostrare "Success!"
2. Clicca **Export Now** per il primo export
3. Verifica che i dati appaiano nel tuo Google Sheet

## Troubleshooting Veloce

**Composer non trovato?**
```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

**Errore credenziali?**
- Verifica che il JSON sia valido (campo diventa verde)
- Ricontrolla di aver abilitato Google Sheets API

**Permission denied?**
- Verifica di aver condiviso il foglio con il service account
- Controlla di aver dato permessi di **Editor**

**No submissions found?**
- Installa e attiva **Contact Form CFDB7** plugin
- Verifica che ci siano submission nel database
- Seleziona almeno una form nelle impostazioni

## Requisiti Plugin

Prima di iniziare, assicurati di avere installati:
- ✅ Contact Form 7
- ✅ Contact Form CFDB7

Puoi installarli da **WordPress Admin → Plugin → Add New**

## Prossimi Passi

Dopo l'installazione:
1. **Export manuale**: Testa con "Export Now" per verificare tutto funzioni
2. **Export automatico**: Se abilitato, il primo export sarà il 1° del prossimo mese alle 2:00 AM
3. **Notifiche**: Configura l'email per ricevere report
4. **Monitoraggio**: Controlla le statistiche nel pannello admin

Per documentazione completa, vedi [README.md](README.md)
