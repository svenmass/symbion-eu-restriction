# Release Guide

## Automatisches Release-System

Das Plugin nutzt GitHub Actions für automatische Releases und einen integrierten Updater für automatische Updates.

## Release erstellen

### 1. Lokalen Build testen (Optional)

```bash
./build.sh 1.0.0
```

Dies erstellt eine ZIP-Datei in `build/symbion-eu-restriction-1.0.0.zip` zum lokalen Testen.

### 2. Version im Code aktualisieren

Aktualisiere die Version in `symbion-eu-restriction.php`:

```php
define( 'SYMBION_EU_VERSION', '1.0.0' );
```

### 3. Änderungen committen

```bash
git add -A
git commit -m "Release 1.0.0"
git push origin main
```

### 4. Git Tag erstellen

```bash
git tag -a v1.0.0 -m "Release 1.0.0 - Initial Release"
git push origin v1.0.0
```

### 5. Automatischer Release-Prozess

Sobald der Tag gepusht wird:

1. ✅ GitHub Actions erstellt automatisch:
   - Production-ready ZIP
   - GitHub Release
   - Release Notes

2. ✅ Der Updater im Plugin:
   - Prüft alle 12 Stunden auf neue Versionen
   - Zeigt Update-Benachrichtigung in WordPress
   - Ermöglicht 1-Click-Update

## Was passiert beim automatischen Build?

1. **Dateien kopieren**: Nur Production-Dateien
   - `includes/`
   - `assets/` (ohne `/brand`)
   - `symbion-eu-restriction.php`
   - `README.md`
   - `QUICKSTART.md`

2. **Bereinigung**:
   - Entfernt `.git`, `.github`, `.DS_Store`
   - Entfernt Development-Assets

3. **ZIP erstellen**: 
   - Optimiert für WordPress Installation
   - Enthält nur notwendige Dateien

4. **Release auf GitHub**:
   - Asset: `symbion-eu-restriction-X.Y.Z.zip`
   - Release Notes automatisch generiert

## Update-Prozess für Endnutzer

1. **Benachrichtigung**: WordPress zeigt "Update verfügbar" an
2. **1-Click-Update**: Nutzer klickt "Jetzt aktualisieren"
3. **Automatische Installation**: ZIP wird heruntergeladen und installiert
4. **Plugin bleibt aktiv**: Keine Unterbrechung

## Versioning (Semantic Versioning)

- **MAJOR** (1.x.x): Breaking Changes
- **MINOR** (x.1.x): Neue Features, abwärtskompatibel
- **PATCH** (x.x.1): Bugfixes

Beispiele:
- `v1.0.0` - Initial Release
- `v1.1.0` - Neue Feature (z.B. neue GeoIP-Provider)
- `v1.1.1` - Bugfix

## Troubleshooting

### "Tag existiert bereits"
```bash
# Lokalen Tag löschen
git tag -d v1.0.0

# Remote Tag löschen
git push origin :refs/tags/v1.0.0
```

### "GitHub Action fehlgeschlagen"
- Prüfe GitHub Actions Tab im Repository
- Logs zeigen Details zum Fehler
- Meist: Permissions oder fehlende Dateien

### "Updates werden nicht angezeigt"
- Transient-Cache leeren: WP Admin → Tools → Site Health → Transients löschen
- Oder: 12 Stunden warten (automatische Cache-Invalidierung)
- Oder: Code im Updater prüfen (`includes/class-updater.php`)

## Manueller Build (für Entwickler)

```bash
# Build erstellen
./build.sh 1.0.0

# ZIP testen
unzip -l build/symbion-eu-restriction-1.0.0.zip

# Lokal in WordPress installieren
cp build/symbion-eu-restriction-1.0.0.zip ~/Downloads/
```

## Nächste Schritte nach Release

1. **Testen**: Plugin auf Test-Site installieren
2. **Dokumentation**: README.md aktualisieren
3. **Changelog**: Release Notes pflegen
4. **Support**: GitHub Issues überwachen

