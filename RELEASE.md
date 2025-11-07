# Come Creare un Release

Questa guida spiega come creare un release del plugin pronto per l'installazione in WordPress.

## Metodo 1: GitHub Actions (Automatico) - CONSIGLIATO

GitHub Actions crea automaticamente un file ZIP quando fai un tag di versione.

### Passi:

1. **Aggiorna il numero di versione** nel file `cf7-monthly-export.php`:
   ```php
   * Version: 1.0.1  // Cambia questo numero
   ```
   E anche nella costante:
   ```php
   define('CF7_MONTHLY_EXPORT_VERSION', '1.0.1');
   ```

2. **Commit e push le modifiche**:
   ```bash
   git add cf7-monthly-export.php
   git commit -m "Bump version to 1.0.1"
   git push
   ```

3. **Crea e pusha un tag**:
   ```bash
   git tag v1.0.1
   git push origin v1.0.1
   ```

4. **Attendi la build**:
   - Vai su GitHub → Actions
   - Aspetta che il workflow "Create Release" finisca (circa 2-3 minuti)

5. **Verifica il Release**:
   - Vai su GitHub → Releases
   - Troverai il nuovo release con il file ZIP allegato
   - Il file sarà: `cf7-monthly-export-1.0.1.zip`

### Il file ZIP è pronto per essere installato in WordPress!

## Metodo 2: Build Manuale (Locale)

Se preferisci creare il ZIP manualmente sul tuo computer:

### Prerequisiti:
- Bash shell (Linux, macOS, o Git Bash su Windows)
- Composer installato
- zip command disponibile

### Passi:

1. **Aggiorna il numero di versione** (vedi sopra)

2. **Esegui lo script di build**:
   ```bash
   chmod +x build.sh
   ./build.sh
   ```

3. **Trova il file ZIP**:
   Il file sarà in `dist/cf7-monthly-export-X.X.X.zip`

4. **Testa l'installazione**:
   - Vai su WordPress Admin → Plugin → Add New
   - Upload Plugin
   - Seleziona il file ZIP
   - Installa e attiva

## Versioning

Usa il [Semantic Versioning](https://semver.org/):
- `MAJOR.MINOR.PATCH` (es: 1.2.3)
- **MAJOR**: Breaking changes (es: 1.0.0 → 2.0.0)
- **MINOR**: Nuove funzionalità (es: 1.0.0 → 1.1.0)
- **PATCH**: Bug fixes (es: 1.0.0 → 1.0.1)

## Checklist Pre-Release

Prima di creare un release, verifica:

- [ ] Aggiornato numero di versione in `cf7-monthly-export.php` (2 posti)
- [ ] Aggiornato CHANGELOG se presente
- [ ] Testato il plugin in ambiente WordPress pulito
- [ ] Verificato che tutte le dipendenze Composer siano aggiornate
- [ ] Controllato che non ci siano errori PHP
- [ ] Testato export manuale e automatico
- [ ] Verificato compatibilità con ultima versione WordPress
- [ ] Verificato compatibilità con ultima versione PHP

## Troubleshooting Build

### Errore: "composer: command not found"
```bash
# Installa Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Errore: "zip: command not found"
```bash
# Ubuntu/Debian
sudo apt-get install zip

# macOS
brew install zip

# Windows
# Usa Git Bash o WSL
```

### Build fallisce su GitHub Actions
- Verifica che il tag sia nel formato corretto: `v1.0.0` (con la 'v' all'inizio)
- Controlla i log in GitHub → Actions
- Verifica che composer.json sia valido

## Distribuzione

Dopo aver creato il release:

1. **GitHub Releases**: Il file ZIP è automaticamente allegato al release
2. **WordPress.org**: (Se pubblichi sul repository ufficiale)
   - Usa SVN per fare upload sul repository
   - Segui le [linee guida WordPress](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)

## Note di Sicurezza

⚠️ **Mai includere nei release**:
- File `.git/`
- Credenziali di test
- File `.env`
- Database dumps
- Chiavi API o credentials

Il `.gitignore` e lo script di build già escludono questi file, ma controlla sempre prima di fare il release.
