# 🚀 Symbion EU Restriction - Quick Start

## Installation

1. Plugin aktivieren über WordPress Admin → Plugins
2. Zu **EU Restriction** im Menü navigieren
3. Plugin aktivieren (Slider)
4. ✅ Fertig!

## Erste Schritte

### 1️⃣ Produkt als Set markieren

**Einzeln:**
- Produkt öffnen
- Checkbox aktivieren: **"Ist Set (Non-EU Restriktion)"**
- Speichern

**Mehrere gleichzeitig:**
- Produkte → Alle Produkte
- Produkte auswählen
- Bulk-Aktion → Bearbeiten → "EU Restriktion: Ist Set"
- Aktualisieren

### 2️⃣ Testmodus nutzen

- Admin Bar → **EU Restriction** anklicken
- Land auswählen (z.B. 🇨🇭 Schweiz)
- Seite lädt neu → Sie sehen die Seite wie ein Besucher aus diesem Land

### 3️⃣ CSS-Klasse verwenden

Füge beliebigen Elementen hinzu:

```html
<div class="nur-eu">
  Nur für EU-Besucher sichtbar
</div>
```

## Einstellungen

### Empfohlene Konfiguration

✅ **Plugin aktivieren**: An  
✅ **Testmodus**: An  
⭕ **Administratoren filtern**: Aus (damit Admins alle Produkte sehen)  
✅ **GeoIP Provider**: WooCommerce (MaxMind) - empfohlen!  
✅ **Fallback**: An (unbekannte Länder = EU)  
✅ **Kategorien filtern**: An  
✅ **Weiterleitung**: 404 Fehlerseite

## Funktionen

### ✅ Was wird automatisch gefiltert?

- Shop & Kategorien-Seiten
- Suche (WooCommerce & WordPress)
- WooCommerce Blöcke
- Related Products
- Upsells & Cross-Sells
- Gutenberg Produktelemente
- REST API
- Menüs & Navigation
- Widgets

### ✅ Kategorie-Filterung

Kategorien die **ausschließlich** Sets enthalten werden automatisch:
- Aus Menüs entfernt
- Aus Widgets entfernt
- Als 404 angezeigt bei direktem Aufruf

## Debugging

### Testmodus

Im Testmodus siehst du:
- Orange Badge oben rechts: "🧪 Testmodus aktiv"
- Outline um `.nur-eu` Elemente
- Console-Logs im Browser

### Cache leeren

Bei Problemen:
1. EU Restriction → Erweitert
2. Button "Cache jetzt leeren"

### Überprüfen ob es funktioniert

1. Testmodus: 🇨🇭 Schweiz wählen
2. Shop-Seite öffnen
3. Set-Produkte sollten **nicht** sichtbar sein
4. Testmodus deaktivieren
5. Set-Produkte sollten wieder sichtbar sein

## Support

Bei Fragen oder Problemen:
📧 sven@bolitalic.de  
🌐 symbion.dev

## Checkliste ✅

- [ ] Plugin aktiviert
- [ ] Mind. 1 Produkt als Set markiert
- [ ] Testmodus getestet (Schweiz = Produkt weg)
- [ ] Testmodus deaktiviert
- [ ] Live-Test mit VPN durchgeführt

---

**Viel Erfolg! 🚀**

