# Job-Widget für Shopware – Dokumentation

> **Datei:** `job-widget--no-dependency--adapted-for-shopware.html`  
> **Version:** 1.2.1  
> **Stand:** Februar 2026

Das Job-Widget zeigt die aktuellen Stellenangebote und Ausbildungsplätze von Compass24 als interaktive, filterbare Akkordeon-Liste an. Es läuft komplett ohne externe Abhängigkeiten direkt im Shopware-CMS.

---

## Teil 1: Stellenangebote pflegen (für Redakteure / nicht-technische Nutzer)

### Wo werden die Stellenangebote eingetragen?

Die Stellenangebote stehen direkt im HTML-Code der Komponente – in einem JavaScript-Abschnitt, der mit `const jobsData = [` beginnt. Jede Stelle ist ein Datensatz in geschweiften Klammern `{ ... }`, getrennt durch Kommas.

### Aufbau eines einzelnen Stellenangebots

Jedes Stellenangebot hat folgende Felder:

| Feld | Beschreibung | Beispiel | Pflicht? |
|------|-------------|---------|----------|
| `id` | Eindeutige Nummer – einfach fortlaufend vergeben | `9` | Ja |
| `title` | Stellenbezeichnung inkl. (m/w/d) | `"Lagermitarbeiter (m/w/d)"` | Ja |
| `department` | Abteilung / Bereich | `"Logistik"` | Ja |
| `employmentType` | Art der Beschäftigung | `"Vollzeit"` | Ja |
| `location` | Standort | `"Ascheberg"` | Ja |
| `workModel` | Arbeitsmodell (oder `null`) | `"Hybrid (3 Tage vor Ort, 2 Tage remote)"` | Nein |
| `startDate` | Starttermin | `"Ab sofort"` | Ja |
| `description` | Kurzbeschreibung der Stelle (1–3 Sätze) | `"Wir suchen..."` | Ja |
| `sections` | Detailabschnitte (Aufgaben, Profil, Vorteile) | siehe unten | Ja |

**Erlaubte Werte für `department`:**
- `"IT & E-Commerce"`
- `"Marketing"`
- `"Vertrieb"`
- `"Logistik"`
- `"Kundenservice"`
- `"Verwaltung"`

**Erlaubte Werte für `employmentType`:**
- `"Vollzeit"`
- `"Teilzeit"`
- `"Ausbildungsplatz"`
- `"Praktikum"`

> **Hinweis:** Die Filter-Dropdowns im Widget werden automatisch aus den tatsächlich vorhandenen Werten generiert. Wenn ein neuer Bereich oder eine neue Beschäftigungsart hinzukommt, taucht diese automatisch im Filter auf. Achte auf exakte Schreibweise (Groß-/Kleinschreibung), damit die Filter korrekt funktionieren.

**Feld `workModel`:**  
Wenn kein besonderes Arbeitsmodell angegeben werden soll (z. B. bei Ausbildungsplätzen), schreibe `null` (ohne Anführungszeichen). Ansonsten einen Text in Anführungszeichen eintragen.

### Detailabschnitte (`sections`)

Jede Stelle hat mehrere Abschnitte, typischerweise:

1. **Aufgaben** (Überschrift: `"Deine Aufgaben:"`)
2. **Profil** (Überschrift: `"Dein Profil:"` oder `"Das bringst du mit:"`)
3. **Vorteile** (Überschrift: `"Deine Vorteile:"` oder `"Wir bieten:"`)

Jeder Abschnitt sieht so aus:

```json
{
  "heading": "Deine Aufgaben:",
  "items": [
    "Erster Aufgabenpunkt",
    "Zweiter Aufgabenpunkt",
    "Dritter Aufgabenpunkt"
  ]
}
```

### Beispiel: Neue Stelle hinzufügen

Um eine neue Stelle anzulegen, kopiere einen bestehenden Eintrag und passe die Werte an. Achte auf:

- **Komma** nach der schließenden `}` jedes Eintrags (außer beim letzten)
- **Anführungszeichen** um alle Textwerte
- **Eindeutige `id`** (nächsthöhere Nummer verwenden)

```javascript
  {
    id: 9,
    title: "Einkäufer Bootszubehör (m/w/d)",
    department: "Vertrieb",
    employmentType: "Vollzeit",
    location: "Ascheberg",
    workModel: "Hybrid (3 Tage vor Ort, 2 Tage remote)",
    startDate: "Ab sofort",
    description: "Wir suchen einen erfahrenen Einkäufer für unser Bootszubehör-Sortiment.",
    sections: [
      {
        heading: "Deine Aufgaben:",
        items: [
          "Sortimentspflege und Lieferantenmanagement",
          "Preisverhandlungen und Angebotsvergleiche"
        ]
      },
      {
        heading: "Dein Profil:",
        items: [
          "Kaufmännische Ausbildung",
          "Erfahrung im Einkauf"
        ]
      },
      {
        heading: "Deine Vorteile:",
        items: [
          "30 Tage Urlaub",
          "13. Gehalt"
        ]
      }
    ]
  }
```

### Stelle entfernen

Um eine Stelle zu entfernen, lösche den gesamten Block von `{` bis `}` (inklusive des Kommas davor oder danach, sodass die Kommasetzung korrekt bleibt).

### Häufige Fehler vermeiden

| Problem | Lösung |
|---------|--------|
| Widget zeigt gar nichts an | Browser-Konsole prüfen (F12). Oft fehlt ein Komma oder ein Anführungszeichen. |
| Ein Anführungszeichen im Text (z. B. `"Best Ager"`) | Innerhalb des Textes mit Backslash maskieren: `\"Best Ager\"` |
| Filter zeigt doppelte Einträge | Schreibweise der Werte exakt einheitlich halten |
| `workModel` steht auf `""` statt `null` | Leerer String erzeugt ein leeres Arbeitsmodell-Tag. Besser `null` verwenden. |

---

### Deployment in Shopware: Zwei-Block-Aufteilung (wichtig!)

> **⚠️ In der Produktivumgebung muss der Code auf zwei separate HTML-Blöcke (Erlebniswelten-Elemente) verteilt werden!**

Shopware zeigt bei einem einzelnen großen HTML-Block gelegentlich einen Netzwerkfehler (`net::ERR_QUIC_PROTOCOL_ERROR`). Deshalb wird der Code folgendermaßen aufgeteilt:

| Block | Inhalt | Markierung im Code |
|-------|--------|-------------------|
| **Block 1** | Nur der `<style>...</style>`-Abschnitt (CSS) | Kommentar: `<!-- Compass24 Job Widget Component – PROD: Block 1/2 -->` |
| **Block 2** | Alles ab dem `<div id="job-widget-app" ...>` bis zum Ende (HTML + beide `<script>`-Blöcke) | Kommentar: `<!-- Compass24 Job Widget Component – PROD: Block 2/2 -->` |

Auf der **Staging-Umgebung** funktioniert alles auch in einem einzigen Block.

**Vorgehensweise beim Einfügen in Shopware:**
1. Öffne die Erlebniswelt der Jobs-Seite im Shopware-Backend
2. Erstelle zwei aufeinanderfolgende „HTML-Element"-Blöcke
3. Kopiere den CSS-Teil (`<style>` bis `</style>`) in Block 1
4. Kopiere den Rest (ab `<div id="job-widget-app"` bis zum letzten `</script>`) in Block 2
5. Speichern und im Frontend prüfen

---

## Teil 2: Technische Dokumentation (für Frontend-Entwickler)

### Architektur-Überblick

Das Widget ist eine **Single-File-Komponente** ohne Build-Step und ohne externe Abhängigkeiten:

- **Rendering-Engine:** [Petite Vue 0.4.1](https://github.com/vuejs/petite-vue) (~6 KB gzipped), als IIFE direkt im ersten `<script>`-Block eingebettet (kein CDN-Aufruf)
- **Daten:** Statisches JavaScript-Array `jobsData` im zweiten `<script>`-Block
- **Styling:** Gescopte CSS Custom Properties im `<style>`-Block, mit Fallbacks auf Bootstrap-Variablen der Compass24-Site (`--bs-primary` etc.)
- **Template-Syntax:** Doppelte eckige Klammern `[[ ]]` statt `{{ }}` (Shopware/Twig-Konflikt, konfiguriert via `$delimiters`)

### Dateistruktur innerhalb der HTML-Datei

```
┌─────────────────────────────────────────────┐
│  <style>                                    │  ← Block 1/2 in PROD
│    Scoped Design Tokens & Komponentenstile  │
│  </style>                                   │
├─────────────────────────────────────────────┤
│  <div id="job-widget-app">                  │  ← Block 2/2 in PROD
│    Petite Vue Template (HTML + Direktiven)  │
│  </div>                                     │
├─────────────────────────────────────────────┤
│  <script> Petite Vue 0.4.1 IIFE </script>   │  (inlined Library)
├─────────────────────────────────────────────┤
│  <script>                                   │
│    jobsData = [...]                         │  (Stellendaten)
│    PetiteVue.createApp({...}).mount(...)    │  (App-Initialisierung)
│  </script>                                  │
└─────────────────────────────────────────────┘
```

### Petite Vue: Wichtige Unterschiede zu Vue 3

- Kein Virtual DOM – Petite Vue arbeitet direkt auf dem DOM
- Kein SFC / `.vue`-Dateien – alles inline
- Dieses Widget verwendet die Standard-Initialisierung mit `PetiteVue.createApp().mount()` (das `v-scope`-Attribut wird im HTML nicht verwendet)
- Computed Properties werden als `get`-Getter im App-Objekt definiert
- Kein `ref()` / `computed()` – reaktive Daten werden direkt im Scope-Objekt gehalten
- `$delimiters` wird über die App-Konfiguration gesetzt

### Komponentenlogik (`initJobWidget`)

Die App wird mit `PetiteVue.createApp()` initialisiert und anschließend mit `.mount('#job-widget-app')` an das DOM-Element gebunden:

```javascript
const app = PetiteVue.createApp({
  $delimiters: ['[[', ']]'],        // Shopware/Twig-kompatibel
  jobs: jobsData,                   // Stellendaten-Array
  searchQuery: '',                  // Freitext-Suche
  filterDepartment: '',             // Bereichs-Filter
  filterEmployment: '',             // Beschäftigungsart-Filter
  filterLocation: '',               // Standort-Filter

  get departments()      → Set aus allen jobs.department-Werten
  get employmentTypes()  → Set aus allen jobs.employmentType-Werten
  get locations()        → Set aus allen jobs.location-Werten
  get hasActiveFilters() → Boolean
  get filteredJobs()     → gefiltertes Array

  resetFilters()         → setzt alle Filter zurück
});

app.mount('#job-widget-app');
```

Die Initialisierung prüft `document.readyState` und wartet ggf. auf `DOMContentLoaded`, um Race Conditions bei der DOM-Injektion durch Shopware zu vermeiden.

### CSS-Architektur

- **BEM-Namenskonvention:** `.job-accordion-trigger__title`, `.job-tag--employment`
- **Design Tokens:** Alle Farben, Abstände, Radien usw. als CSS Custom Properties auf `.compass24-job-widget-component` definiert
- **Bootstrap-Fallbacks:** `var(--bs-primary, #003366)` – nutzt die Shopware/Bootstrap-Variablen, wenn vorhanden; sonst Fallback-Werte für Standalone-Vorschau
- **Mobile-first:** Basis-Styles für Mobile, Anpassungen via `@media (max-width: 767px)` (hier invertiert, da die Basisstyles bereits für Desktop ausgelegt sind)
- **Animationen:** `compass24FadeInUp`-Keyframes mit `prefers-reduced-motion`-Unterstützung
- **Scoping:** Alle Styles starten mit `.compass24-job-widget-component` oder `.job-*`, um Konflikte mit dem Shopware-Theme zu vermeiden
- **Bootstrap-Override:** `.job-accordion` hat `--bs-accordion-border-color: unset !important`, um Bootstrap-Akkordeon-Styles zu neutralisieren

### Bekannte Shopware-spezifische Anpassungen

| Thema | Detail |
|-------|--------|
| **Twig-Konflikt** | `{{ }}` wird von Shopwares Twig-Engine interpretiert → `[[ ]]` als Delimiter |
| **QUIC-Fehler** | Großer HTML-Block verursacht `net::ERR_QUIC_PROTOCOL_ERROR` → Aufteilung in 2 Blöcke |
| **Bootstrap-Variablen** | CSS nutzt `var(--bs-*)` mit Fallbacks, da Shopware Bootstrap 5 einbindet |
| **Accordion-Reset** | `--bs-accordion-border-color: unset !important` verhindert ungewollte Bootstrap-Styles |
| **Outline-Reset** | `.job-accordion-trigger:focus { outline: none !important }` überschreibt Shopware-Fokusring |

### Debugging-Tipps

1. **Browser-Konsole prüfen:** Das Widget loggt bei erfolgreicher Initialisierung:  
   `Compass24 Job Widget: Successfully mounted with X jobs`
2. **JSON-Syntaxfehler:** Ein fehlerhaftes Komma oder Anführungszeichen in `jobsData` verhindert die gesamte Script-Ausführung. Die Konsole zeigt dann einen `SyntaxError` mit Zeilenangabe.
3. **Standalone testen:** Die Datei kann direkt im Browser geöffnet werden (die `<html>`-Hülle ist für die Preview vorhanden). Alle Bootstrap-Fallback-Werte greifen automatisch.
4. **Filter-Dropdowns leer:** Wenn `jobsData` leer ist oder nicht geladen wird, sind die Dropdowns leer und es erscheint die „Keine passenden Stellen"-Meldung.
5. **Petite Vue nicht geladen:** Prüfe in der Konsole, ob `PetiteVue` als globales Objekt verfügbar ist. Ist das erste `<script>` defekt oder wurde in Block 2 nicht mitgenommen, schlägt `PetiteVue.createApp()` fehl.

### Erweiterungsmöglichkeiten

- **Neues Filterfeld hinzufügen** (z. B. `workModel`):
  1. Neuen State im `createApp`-Objekt anlegen: `filterWorkModel: ''`
  2. Getter für die Optionen ergänzen (analog `get departments()`)
  3. `<select>` im Filter-Bar-Template hinzufügen
  4. Filterbedingung in `get filteredJobs()` ergänzen
  5. `hasActiveFilters` und `resetFilters()` erweitern
  6. Filter-Tag im Template anlegen

- **Bewerbungs-Link ändern:** Die mailto-Adresse steht im Template bei `mailto:sekretariat@compass24.de`. Suchbegriff im Code: `sekretariat@compass24.de`

- **Neue Sections im Job-Detail:** Einfach weitere Objekte in das `sections`-Array eines Jobs einfügen. Das Template iteriert automatisch über alle Einträge.

### Referenzdateien

| Datei | Beschreibung |
|-------|-------------|
| `job-widget--no-dependency--adapted-for-shopware.html` | **Diese Datei** – Produktionsversion für Shopware |
| `job-widget--no-dependency.html` | Standalone-Version ohne Shopware-Anpassungen |
| `job-widget.html` | Ursprüngliche Version mit Vue 3 via CDN |
| `jobs-data.json` | Referenz-JSON mit allen Stellendaten (als separate Datei) |
| `data/*.html` | Einzelne Stellenangebote als HTML (Quellmaterial) |
