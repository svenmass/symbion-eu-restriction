# Symbion EU Restriction

**Intelligente Geo-Filterung für WooCommerce Set-Produkte**

Ein modernes WordPress-Plugin, das Set-Produkte automatisch für Besucher außerhalb der EU ausblendet.

## 🎯 Features

### ✅ Produkt-Filterung
- Einfache Checkbox im Produkt-Editor: "Ist Set (Non-EU Restriktion)"
- Bulk-Bearbeitung für mehrere Produkte gleichzeitig
- Automatisches Filtern in allen Shop-Bereichen:
  - Shop & Kategorien
  - Suche
  - WooCommerce Blöcke
  - Related Products, Upsells, Cross-Sells
  - REST API
  - Gutenberg Produktelemente

### 📁 Intelligente Kategorie-Filterung
- Kategorien, die **ausschließlich** Set-Produkte enthalten, werden automatisch ausgeblendet
- Funktioniert in Menüs, Widgets und Navigation
- Verhindert leere Kategorie-Seiten

### 🎨 CSS-Klasse "nur-eu"
- Fügen Sie beliebigen Elementen `class="nur-eu"` hinzu
- Wird automatisch für Non-EU Besucher versteckt
- CSS + JavaScript Fallback

### 🧪 Intelligenter Testmodus
- **Admin Bar Integration** mit Dropdown
- Simuliere verschiedene Länder:
  - 🇨🇭 Schweiz (Non-EU)
  - 🇺🇸 USA (Non-EU)
  - 🇬🇧 UK (Non-EU)
  - 🇩🇪 Deutschland (EU)
  - 🇫🇷 Frankreich (EU)
  - 🇦🇹 Österreich (EU)
- Visueller Indikator wenn Testmodus aktiv

### 🎨 Modernes Admin-Interface
- Schicke Settings-Seite mit Tabs
- Dashboard mit Live-Statistiken
- Symbion-Branding
- Produkt-Übersicht mit Icon-Spalte
- Ein-Klick Aktivierung

## 📋 Anforderungen

- WordPress 6.0 oder höher
- WooCommerce 7.0 oder höher
- PHP 7.4 oder höher

## 🚀 Installation

1. Plugin-Ordner nach `/wp-content/plugins/symbion-eu-restriction/` hochladen
2. Plugin über die WordPress-Plugin-Seite aktivieren
3. Zu **WooCommerce → EU Restriction** navigieren
4. Einstellungen konfigurieren
5. Produkte als Sets markieren

## 🔧 Verwendung

### Produkte als Sets markieren

**Im Produkt-Editor:**
1. Produkt öffnen
2. In den Produktdaten die Checkbox aktivieren: **"Ist Set (Non-EU Restriktion)"**
3. Speichern

**Bulk Edit:**
1. Zu Produkte → Alle Produkte
2. Mehrere Produkte auswählen
3. Bulk-Aktion → Bearbeiten
4. Bei "EU Restriktion" → "Ist Set" auswählen
5. Aktualisieren

### CSS-Klasse verwenden

```html
<div class="nur-eu">
  Dieser Inhalt ist nur für EU-Besucher sichtbar.
</div>
```

### Testmodus nutzen

1. Als Administrator einloggen
2. In der Admin Bar auf "EU Restriction" klicken
3. Land auswählen
4. Seite wird neu geladen mit simuliertem Standort

## ⚙️ Einstellungen

### Allgemein
- **Plugin aktivieren**: Aktiviert/Deaktiviert die komplette Filterung
- **Testmodus**: Admin Bar Integration für Administratoren
- **Administratoren filtern**: Ob Admins die Filterung auch sehen
- **Weiterleitung**: 404 oder Shop-Seite bei direktem Aufruf

### GeoIP
- **Provider**: WooCommerce/MaxMind (empfohlen), Cloudflare oder generische Header
- **Fallback**: Verhalten bei unbekanntem Land

### Kategorien
- **Kategorien filtern**: Versteckt Kategorien die nur Sets enthalten
- **Übersicht**: Zeigt welche Kategorien betroffen sind

### Erweitert
- **CSS-Klasse**: Dokumentation zur "nur-eu" Klasse
- **Cache leeren**: Ein-Klick Cache-Bereinigung
- **Plugin-Info**: Version und Entwickler

## 🏗️ Technische Details

### Architektur

```
symbion-eu-restriction/
├── symbion-eu-restriction.php      # Hauptdatei mit Autoloader
├── includes/
│   ├── class-core.php              # Kern-Controller
│   ├── class-geoip.php             # GeoIP + Testmodus
│   ├── class-product-filter.php    # Produkt-Filterung
│   ├── class-category-filter.php   # Kategorie-Filterung
│   ├── class-content-filter.php    # "nur-eu" CSS-Klasse
│   ├── class-product-meta.php      # Produkt Meta-Verwaltung
│   ├── class-admin.php             # Admin-Interface
│   ├── class-test-mode.php         # Testmodus-Logik
│   └── class-bulk-edit.php         # Bulk/Quick Edit
├── assets/
│   ├── css/
│   │   ├── admin.css               # Admin-Design
│   │   ├── admin-bar.css           # Admin Bar Styles
│   │   └── frontend.css            # Frontend (.nur-eu)
│   ├── js/
│   │   ├── admin.js                # Admin-Interaktivität
│   │   ├── admin-bar.js            # Admin Bar AJAX
│   │   ├── frontend.js             # Frontend-Logik
│   │   └── bulk-edit.js            # Bulk Edit Support
│   └── images/
│       └── symbion-logo-white.svg  # Logo
└── README.md
```

### Performance

- **Caching**: Set-Produkt-IDs werden 10 Minuten gecacht
- **Optimierte Queries**: Verwendet `post__not_in` für schnelle Ausschlüsse
- **Lazy Loading**: Komponenten werden nur bei Bedarf geladen

## 🎨 Design

- **Primary Color**: `#102a43`
- **Accent Color**: `#1f67b0`
- **Modern UI**: Cards, Tabs, Toggle-Switches
- **Responsive**: Funktioniert auf allen Geräten

## 🔒 Sicherheit

- Nonce-Validierung bei allen AJAX-Requests
- Capability-Checks für Admin-Funktionen
- Sanitization aller Eingaben
- Prepared Statements für Datenbank-Queries

## 👨‍💻 Entwickler

**symbion.dev**  
Homepage: [https://symbion.dev](https://symbion.dev)

## 📝 Changelog

### Version 1.0.0 (2024-10-27)
- Initial Release
- Produkt-Filterung mit Caching
- Kategorie-Filterung
- CSS-Klasse "nur-eu"
- Admin Bar Testmodus
- Modernes Admin-Interface
- Bulk Edit Support

## 📄 Lizenz

GPL v2 oder höher

---

**Entwickelt mit ❤️ von symbion.dev**

