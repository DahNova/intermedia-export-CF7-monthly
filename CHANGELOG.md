# Changelog

Tutte le modifiche notevoli a questo progetto saranno documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/it/1.0.0/),
e questo progetto aderisce al [Semantic Versioning](https://semver.org/lang/it/).

## [Unreleased]

### Planned
- Supporto per pianificazione settimanale/giornaliera
- Export in formato CSV
- Filtri per data di export
- Support per Custom Post Types
- Integrazione con altri form builder

## [1.0.0] - 2025-11-07

### Added
- Export automatico mensile tramite WP Cron
- Export manuale on-demand
- Supporto multi-form unificato in singola sheet
- Sistema anti-duplicati intelligente
- Integrazione completa Google Sheets API
- Interfaccia admin con pannello configurazione
- Statistiche in tempo reale (submission totali, exported, pending)
- Test connessione Google Sheets
- Notifiche email per successo/errore
- Validazione JSON credenziali Google
- Formattazione automatica header sheet (bold, freeze)
- Gestione campi dinamici (tutte le form unificate con tutti i campi)
- Supporto array values (es: checkbox multipli)
- Tracking submission esportate per evitare duplicati
- Documentazione completa (README, INSTALL, RELEASE)
- Script build automatico per creare ZIP installabile
- GitHub Actions workflow per release automatici
- Supporto per Contact Form CFDB7
- Sanitizzazione e validazione completa degli input
- Sicurezza: credenziali solo in database, mai in file

### Security
- Nessun file credenziali incluso nel repository
- `.gitignore` configurato per escludere dati sensibili
- Sanitizzazione di tutti gli input utente
- Validazione JSON credenziali
- Accesso admin limitato a `manage_options`
- Prepared statements per query SQL

### Developer
- Architettura modulare con classi separate
- Composer per gestione dipendenze
- PSR-4 autoloading ready
- Hooks WordPress standard
- AJAX per operazioni asincrone
- Codice commentato e documentato

## [0.1.0] - 2025-11-07 (Pre-release)

### Added
- Setup iniziale progetto
- Struttura base plugin WordPress

---

## Tipi di Modifiche

- `Added` - Nuove funzionalità
- `Changed` - Modifiche a funzionalità esistenti
- `Deprecated` - Funzionalità deprecate (da rimuovere in futuro)
- `Removed` - Funzionalità rimosse
- `Fixed` - Bug fix
- `Security` - Patch di sicurezza

[Unreleased]: https://github.com/DahNova/intermedia-export-CF7-monthly/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/DahNova/intermedia-export-CF7-monthly/releases/tag/v1.0.0
[0.1.0]: https://github.com/DahNova/intermedia-export-CF7-monthly/releases/tag/v0.1.0
