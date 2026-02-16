# Job Widget for Shopware – Documentation

> **Original Standalone File:** `job-widget.html`  
> **CMS Block Version:** Compass24Blocks plugin → `compass24-job-offers`  
> **Version:** 2.0.0 (CMS Block)  
> **Date:** February 2026

The job widget displays current job openings and apprenticeship positions from Compass24 as an interactive, filterable accordion list. In this version it runs as a native Shopware CMS block, with job data managed entirely through the Shopware admin dashboard.

---

## Part 1: Managing Job Offers (for Editors / Non-Technical Users)

### Where Are Job Offers Managed?

Job offers are managed directly in the **Shopware Administration** through the Shopping Experiences (Erlebniswelten) editor:

1. Navigate to **Content → Shopping Experiences**
2. Open the layout containing the job offers block
3. Click on the **Compass24 Stellenangebote** element
4. Use the sidebar configuration panel to manage all job listings

### Structure of a Single Job Offer

Each job offer has the following fields:

| Field | Description | Example | Required? |
|-------|------------|---------|-----------|
| `title` | Job title including (m/w/d) | `"Lagermitarbeiter (m/w/d)"` | Yes |
| `department` | Department / area | `"Logistik"` | Yes |
| `employmentType` | Type of employment | `"Vollzeit"` | Yes |
| `location` | Location | `"Ascheberg"` | Yes |
| `workModel` | Work model (or empty) | `"Hybrid (3 Tage vor Ort, 2 Tage remote)"` | No |
| `startDate` | Start date | `"Ab sofort"` | Yes |
| `description` | Short description of the position (1–3 sentences) | `"Wir suchen..."` | Yes |
| `sections` | Detail sections (tasks, profile, benefits) | see below | Yes |

**Allowed Values for Department:**
- `"IT & E-Commerce"`
- `"Marketing"`
- `"Vertrieb"` (Sales)
- `"Logistik"` (Logistics)
- `"Kundenservice"` (Customer Service)
- `"Verwaltung"` (Administration)

**Allowed Values for Employment Type:**
- `"Vollzeit"` (Full-time)
- `"Teilzeit"` (Part-time)
- `"Ausbildungsplatz"` (Apprenticeship)
- `"Praktikum"` (Internship)

> **Note:** The filter dropdowns in the widget are automatically generated from the actual values in the job listings. When a new department or employment type is added, it automatically appears in the filter. Pay attention to exact spelling (case-sensitivity) so that filters work correctly.

**Work Model Field:**  
If no specific work model should be displayed (e.g. for apprenticeships), leave the field empty. Otherwise, enter a descriptive text.

### Detail Sections

Each job offer has multiple sections, typically:

1. **Tasks** (heading: `"Deine Aufgaben:"`)
2. **Profile** (heading: `"Dein Profil:"` or `"Das bringst du mit:"`)
3. **Benefits** (heading: `"Deine Vorteile:"` or `"Wir bieten:"`)

Each section consists of a heading and a list of bullet points, which can be managed through the admin UI by clicking **+ Punkt hinzufügen** (Add item) and **✕** to remove items.

### Adding a New Job Offer

1. Open the CMS element configuration in the admin sidebar
2. Click **+ Neue Stelle** (New Job)
3. Fill in all required fields
4. Add sections (tasks, profile, benefits) with their bullet points
5. Save the layout

### Removing a Job Offer

1. Expand the job card in the configuration panel
2. Scroll to the bottom
3. Click **Stelle löschen** (Delete Job)

### Reordering Jobs

Use the **↑** and **↓** buttons on each job card header to change the display order.

### Duplicating a Job

Click the **⧉** button on a job card header to create a copy with " (Kopie)" appended to the title.

### General Settings

At the top of the configuration panel you can set:

- **Überschrift** (Header Title): The main heading displayed above the job list
- **Untertitel** (Subtitle): A subtitle shown below the heading
- **Bewerbungs-E-Mail-Adresse** (Application Email): The email address used in "Jetzt bewerben" (Apply Now) buttons

---

## Part 2: Technical Documentation (for Frontend Developers)

### Architecture Overview (CMS Block Version)

The widget is implemented as a **Shopware CMS block** within the `Compass24Blocks` plugin:

- **Rendering Engine:** [Petite Vue 0.4.1](https://github.com/vuejs/petite-vue) (~6 KB gzipped), inlined in the storefront Twig template
- **Data:** Stored as JSON in the CMS element config, managed through a custom admin UI
- **Styling:** SCSS compiled through the Shopware storefront build (CSS Custom Properties with Bootstrap variable fallbacks)
- **Template Syntax:** Double square brackets `[[ ]]` instead of `{{ }}` (Shopware/Twig conflict, configured via `$delimiters`)
- **Admin UI:** Custom Vue 3 config component with full CRUD for job listings

### Plugin File Structure

```
custom/plugins/Compass24Blocks/src/Resources/
├── app/
│   ├── administration/src/
│   │   ├── main.js
│   │   └── module/sw-cms/
│   │       ├── blocks/compass24-job-offers/
│   │       │   ├── index.js                    (block registration)
│   │       │   ├── component/
│   │       │   │   ├── index.js
│   │       │   │   ├── *.html.twig
│   │       │   │   └── *.scss
│   │       │   └── preview/
│   │       │       ├── index.js
│   │       │       ├── *.html.twig
│   │       │       └── *.scss
│   │       └── elements/compass24-job-offers/
│   │           ├── index.js                    (element + default config)
│   │           ├── component/                  (CMS editor live preview)
│   │           ├── config/                     (admin sidebar UI)
│   │           │   ├── index.js                (Vue component with CRUD logic)
│   │           │   ├── *.html.twig             (job editor form template)
│   │           │   └── *.scss                  (admin panel styling)
│   │           └── preview/                    (element picker preview)
│   └── storefront/src/
│       ├── main.js
│       └── scss/
│           ├── base.scss
│           └── component/
│               └── _compass24-job-offers.scss  (all storefront styles)
└── views/storefront/
    ├── block/
    │   └── cms-block-compass24-job-offers.html.twig
    └── element/
        └── cms-element-compass24-job-offers.html.twig
            (Petite Vue template + inlined library + initialization)
```

### Data Storage

Job data is stored as a **JSON string** in the CMS element's `config.jobs.value` field. The admin config component:

1. Parses the JSON into a reactive `localJobs` array on load
2. Provides form fields for editing all job properties
3. Deep-watches the array and serializes changes back to JSON automatically

Additional config fields: `headerTitle`, `headerSubtitle`, `applicationEmail`.

### Petite Vue Integration in Storefront

The storefront Twig template handles several concerns:

1. **JSON Data Injection:** Job data is output via `<script type="application/json">` and parsed client-side
2. **Twig/Petite Vue Coexistence:** The Petite Vue library is wrapped in `{% verbatim %}` to prevent Twig from interpreting `{{ }}` in the library source code
3. **Config Injection:** Header text is rendered server-side via Twig; the application email is injected into the initialization script via Twig expressions
4. **Multiple Instances:** Each element uses its UUID as suffix for element IDs, supporting multiple job widgets on the same page

### Petite Vue: Key Differences from Vue 3

- No Virtual DOM – Petite Vue operates directly on the DOM
- No SFC / `.vue` files – everything is inline
- This widget uses standard initialization with `PetiteVue.createApp().mount()` (the `v-scope` attribute is not used)
- Computed properties are defined as `get` getters in the app object
- No `ref()` / `computed()` – reactive data is held directly in the scope object
- `$delimiters` is set via the app configuration

### CSS Architecture

- **BEM Naming Convention:** `.job-accordion-trigger__title`, `.job-tag--employment`
- **Design Tokens:** All colors, spacing, radii, etc. are defined as CSS Custom Properties on `.compass24-job-widget-component`
- **Bootstrap Fallbacks:** `var(--bs-primary, #003366)` – uses Shopware/Bootstrap variables when available; otherwise falls back to defaults
- **Mobile-first:** Base styles for mobile, adjustments via `@media (max-width: 767px)`
- **Animations:** `compass24FadeInUp` keyframes with `prefers-reduced-motion` support
- **Scoping:** All styles start with `.compass24-job-widget-component` or `.job-*` to prevent conflicts with the Shopware theme
- **Bootstrap Override:** `.job-accordion` uses `--bs-accordion-border-color: unset !important` to neutralize Bootstrap accordion styles

### Known Shopware-Specific Adaptations

| Topic | Detail |
|-------|--------|
| **Twig Conflict** | `{{ }}` is interpreted by Shopware's Twig engine → `[[ ]]` as delimiter; library wrapped in `{% verbatim %}` |
| **Bootstrap Variables** | CSS uses `var(--bs-*)` with fallbacks, as Shopware includes Bootstrap 5 |
| **Accordion Reset** | `--bs-accordion-border-color: unset !important` prevents unwanted Bootstrap styles |
| **Outline Reset** | `.job-accordion-trigger:focus { outline: none !important }` overrides Shopware focus ring |

### Debugging Tips

1. **Check browser console:** The widget logs on successful initialization:  
   `Compass24 Job Widget: Mounted with X jobs (element <uuid>)`
2. **JSON parse errors:** If jobs data is malformed, the console shows the error and the widget displays 0 jobs.
3. **Filter dropdowns empty:** If no jobs are configured or the JSON is invalid, the dropdowns will be empty and the "Keine passenden Stellen" message appears.
4. **Petite Vue not loaded:** Check the console for `PetiteVue` as a global object. If the `{% verbatim %}` block was accidentally removed, Twig will try to interpret `{{ }}` in the library source and break it.
5. **Styles not applying:** Rebuild storefront assets with `bin/build-storefront.sh`.

### Extension Possibilities

- **Add a new filter field** (e.g. `workModel`):
  1. Add new state in the PetiteVue `createApp` object: `filterWorkModel: ''`
  2. Add a getter for the options (analogous to `get departments()`)
  3. Add a `<select>` in the filter bar template
  4. Add the filter condition in `get filteredJobs()`
  5. Extend `hasActiveFilters` and `resetFilters()`
  6. Add a filter tag in the template

- **Change the application email:** Configurable through the admin UI (General Settings → Application Email).

- **Add new sections in job details:** Simply add more sections through the admin UI – the template iterates over all entries automatically.

### Reference Files

| File | Description |
|------|-------------|
| `job-widget.html` | Original standalone version with Petite Vue (inlined) |
| `DOKUMENTATION-de.md` | German documentation for the standalone version |
| `DOCUMENTATION.md` | **This file** – English documentation for the CMS block version |

---

## Installation & Build

```bash
# From Shopware root directory

# Refresh plugin list
bin/console plugin:refresh

# Install and activate (if not already)
bin/console plugin:install Compass24Blocks --activate

# Build assets
bin/build-administration.sh
bin/build-storefront.sh

# Clear cache
bin/console cache:clear
```

## Usage for Content Editors

1. Navigate to **Content → Shopping Experiences**
2. Create or edit a layout
3. Find **Compass24 Stellenangebote** in the Commerce category
4. Drag the block into your layout
5. Click on the element to configure:
   - Set header title and subtitle
   - Set application email
   - Add, edit, reorder, duplicate, or remove job listings
6. Save the layout
7. Assign the layout to the desired pages/categories
