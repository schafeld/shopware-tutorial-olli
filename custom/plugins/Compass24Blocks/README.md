# Compass24 CMS Blocks Plugin

A Shopware 6 plugin providing reusable CMS blocks for the Compass24 website.

## Current Blocks

### 1. Key Metrics Banner

A full-width banner displaying 5 key company metrics with a blue gradient background and animated appearance.

**Features:**
- Responsive design (5 columns on desktop, 2 on tablet, 1 on mobile)
- Smooth fade-in animations with staggered delays
- Hover effects on individual metrics
- Fully editable metric values and labels
- Accessibility support with ARIA labels
- Matches Compass24 brand colors

**Default Metrics:**
1. **1979** - Gründungsjahr
2. **42.000+** - Artikel
3. **400+** - Seiten Katalog
4. **5.000** - Pakete täglich
5. **11** - Länder weltweit

### 2. Job Offers Widget (Stellenangebote)

An interactive job board widget powered by Petite Vue, allowing visitors to browse, search, and filter open positions. Jobs are fully manageable through the Shopware Administration CMS editor.

**Features:**
- Real-time search across job titles, descriptions, and departments
- Filter by department and employment type (dropdown filters)
- Accordion-style job detail expansion
- "Apply Now" call-to-action linked to a configurable email address
- Responsive design with mobile-optimized layout
- Smooth animations and transitions
- Multiple instances supported on the same page (unique element IDs)
- Petite Vue client-side rendering (no server round-trips for search/filter)

**Configurable Fields (per element):**
- **Header Title** — e.g., "Aktuelle Stellenangebote"
- **Header Subtitle** — e.g., "Finde deinen Platz im Compass24-Team"
- **Application Email** — used in the "Jetzt bewerben" mailto link
- **Jobs** — full CRUD management of individual job listings

**Per-Job Fields:**
- Title, Department, Employment Type, Location, Start Date, Work Model, Description
- Expandable sections with heading + bullet point items (e.g., "Deine Aufgaben", "Dein Profil", "Deine Vorteile")

**Default Jobs:**
1. **Full Stack Entwickler Shopware 6** — IT & E-Commerce, Vollzeit, Hybrid
2. **Kaufmann/-frau im Einzelhandel** — Vertrieb, Ausbildungsplatz

---

## Installation

### Requirements
- Shopware 6.7.0 or higher
- PHP 8.1 or higher
- Composer

### Step 1: Install the Plugin

```bash
# Navigate to your Shopware root directory
cd /path/to/shopware

# Install plugin via Composer (if using Composer)
composer require compass24/cms-blocks

# Or manually copy the plugin folder
cp -r Compass24Blocks custom/plugins/

# Refresh plugin list
bin/console plugin:refresh

# Install the plugin
bin/console plugin:install Compass24Blocks --activate

# Clear cache
bin/console cache:clear
```

### Step 2: Build Administration Assets

The plugin needs to compile its Administration JavaScript to work in the CMS editor:

```bash
# Build Administration
bin/build-administration.sh

# Or if you prefer watching for changes during development
bin/watch-administration.sh
```

### Step 3: Build Storefront Assets

Build the storefront styles (SCSS):

```bash
# Build Storefront
bin/build-storefront.sh

# Or watch for changes
bin/watch-storefront.sh
```

### Step 4: Verify Installation

1. Log into your Shopware Administration panel
2. Navigate to **Content** → **Shopping Experiences**
3. Create a new layout or edit an existing one
4. Look for the blocks in the block selection panel under the **Commerce** category:
   - **"Compass24 Key Metrics"**
   - **"Compass24 Stellenangebote"** (Job Offers)

---

## Usage Guide for Editors

### Adding the Key Metrics Block to a Page

1. **Open the Shopping Experience Editor**
   - Go to **Content** → **Shopping Experiences**
   - Click **"Create new layout"** or edit an existing layout

2. **Add the Block**
   - In the block selection sidebar (usually on the right), find the **Commerce** category
   - Look for **"Compass24 Key Metrics"**
   - Drag and drop it into your desired section

3. **Configure the Metrics**
   - Click on the block to select it
   - In the configuration panel on the right, you'll see 5 metric groups
   - Each metric has two fields:
     - **Value**: The large number/text (e.g., "1979", "42.000+")
     - **Label**: The description text below the value (e.g., "Gründungsjahr")

4. **Save and Publish**
   - Click **"Save"** in the top right
   - Assign the layout to your desired pages or category
   - Preview the result on your storefront

### Editing Tips

- **Values can be text or numbers**: While designed for numbers, you can use any text (e.g., "NEW!", "100%")
- **Keep labels concise**: Shorter labels work best for mobile devices
- **Test on mobile**: Use the responsive preview to check how it looks on different devices
- **Use consistent formatting**: Keep punctuation and formatting consistent across metrics (e.g., all use "+" or none)

### Best Practices

1. **Update regularly**: Keep metrics current to maintain credibility
2. **Use round numbers**: They're easier to read at a glance
3. **Mobile-first**: Check mobile appearance - text should be readable on small screens
4. **Accessibility**: The block includes ARIA labels, but ensure your values are meaningful

### Adding the Job Offers Block to a Page

1. **Open the Shopping Experience Editor**
   - Go to **Content** → **Shopping Experiences**
   - Click **"Create new layout"** or edit an existing layout

2. **Add the Block**
   - In the block selection sidebar, find the **Commerce** category
   - Look for **"Compass24 Stellenangebote"**
   - Drag and drop it into a **full-width section**

3. **Configure General Settings**
   - Click on the element and open the configuration panel
   - Set the **Header Title** (e.g., "Aktuelle Stellenangebote")
   - Set the **Header Subtitle** (e.g., "Finde deinen Platz im Compass24-Team")
   - Set the **Application Email** (used for the "Jetzt bewerben" button)

4. **Manage Job Listings**
   - In the config panel, scroll to **Stellenangebote (Jobs)**
   - Click **"+ Stelle hinzufügen"** to add a new job
   - For each job, fill in:
     - **Title**: Job title (e.g., "Full Stack Entwickler")
     - **Department**: Select from predefined list (IT & E-Commerce, Vertrieb, etc.)
     - **Employment Type**: Vollzeit, Teilzeit, Ausbildungsplatz, etc.
     - **Location**, **Start Date**, **Work Model**, **Description**
   - **Add Sections**: Each job can have multiple collapsible sections (e.g., tasks, requirements, benefits)
     - Click **"+ Abschnitt"** to add a section with a heading
     - Click **"+"** within a section to add bullet point items
   - Use **↑ / ↓** buttons to reorder jobs
   - Use the **duplicate** button to copy an existing job as a template
   - Use the **delete** button to remove a job

5. **Save and Publish**
   - Click **"Save"** in the top right
   - Preview the storefront — visitors will see the interactive job board with search, filters, and expandable details

### Job Offers Editing Tips

- **Sections are key**: Use sections (Aufgaben, Profil, Vorteile) to structure each job listing clearly
- **Departments & types**: These power the dropdown filters on the storefront — keep naming consistent
- **Email address**: The "Jetzt bewerben" button opens the visitor's email client with the configured address
- **Duplicate to save time**: Use the duplicate button when adding similar positions
- **Live preview**: The storefront widget updates instantly — no rebuild needed after content changes

---

## Technical Documentation

### File Structure

```
Compass24Blocks/
├── composer.json
├── README.md
├── DOCUMENTATION.md                                  # Detailed English documentation
├── DOKUMENTATION-de.md                               # German documentation
├── src/
│   ├── Compass24Blocks.php                           # Main plugin class
│   └── Resources/
│       ├── config/
│       │   └── services.xml                          # Service configuration
│       ├── app/
│       │   ├── administration/src/                   # Admin panel (Vue.js)
│       │   │   ├── main.js                          # Entry point
│       │   │   └── module/sw-cms/
│       │   │       ├── blocks/
│       │   │       │   ├── compass24-key-metrics/        # Key Metrics block
│       │   │       │   │   ├── index.js
│       │   │       │   │   ├── component/
│       │   │       │   │   └── preview/
│       │   │       │   └── compass24-job-offers/         # Job Offers block
│       │   │       │       ├── index.js
│       │   │       │       ├── component/
│       │   │       │       └── preview/
│       │   │       └── elements/
│       │   │           ├── compass24-key-metrics/        # Key Metrics element
│       │   │           │   ├── index.js
│       │   │           │   ├── component/
│       │   │           │   ├── config/
│       │   │           │   └── preview/
│       │   │           └── compass24-job-offers/         # Job Offers element
│       │   │               ├── index.js                 # Default config + registration
│       │   │               ├── component/               # Admin element wrapper
│       │   │               ├── config/                  # Full CRUD job editor UI
│       │   │               └── preview/                 # Block picker preview
│       │   └── storefront/src/                      # Frontend styles
│       │       └── scss/
│       │           ├── base.scss                    # Main SCSS entry
│       │           └── component/
│       │               ├── _compass24-key-metrics.scss
│       │               └── _compass24-job-offers.scss   # ~580 lines of widget styles
│       └── views/storefront/                        # Frontend templates
│           ├── block/
│           │   └── cms-block-compass24-job-offers.html.twig
│           └── element/
│               ├── cms-element-compass24-key-metrics.html.twig
│               └── cms-element-compass24-job-offers.html.twig  # Petite Vue widget
```

### CMS Architecture

**Block vs Element:**
- **CMS Block**: Container with layout structure (can contain multiple elements)
- **CMS Element**: Individual content unit with configuration

This plugin creates:
- 2 Blocks: `compass24-key-metrics`, `compass24-job-offers`
- 2 Elements: `compass24-key-metrics`, `compass24-job-offers`

### Configuration Schema

#### Key Metrics

Each metric has two config fields:

```javascript
{
    metricXValue: {
        source: 'static',
        value: '1979'  // Editable by user
    },
    metricXLabel: {
        source: 'static',
        value: 'Gründungsjahr'  // Editable by user
    }
}
```

Where X is 1-5 for the five metrics.

#### Job Offers

The element stores complex nested data as a JSON string:

```javascript
{
    headerTitle:      { source: 'static', value: 'Aktuelle Stellenangebote' },
    headerSubtitle:   { source: 'static', value: 'Finde deinen Platz...' },
    applicationEmail: { source: 'static', value: 'sekretariat@compass24.de' },
    jobs:             { source: 'static', value: '<JSON string>' }
}
```

The `jobs` value is a JSON-serialized array of job objects:

```javascript
{
    id: 1,
    title: 'Full Stack Entwickler...',
    department: 'IT & E-Commerce',       // Powers filter dropdown
    employmentType: 'Vollzeit',          // Powers filter dropdown
    location: 'Ascheberg',
    workModel: 'Hybrid (3 Tage vor Ort, 2 Tage remote)',
    startDate: 'Ab sofort',
    description: '...',
    sections: [
        {
            heading: 'Deine Aufgaben:',
            items: ['Item 1', 'Item 2', '...']
        }
    ]
}
```

### Petite Vue Integration

The Job Offers widget uses [Petite Vue](https://github.com/vuejs/petite-vue) (v0.4.1, ~6KB) for client-side interactivity:

- **Delimiter conflict**: Petite Vue defaults to `{{ }}` which conflicts with Twig. The widget uses `[[ ]]` delimiters instead.
- **Library inlining**: The Petite Vue IIFE is embedded directly in the template, wrapped in `{% verbatim %}` to prevent Twig from parsing the library's internal `{{ }}` references.
- **Data injection**: Job data is passed from Twig to Petite Vue via a `<script type="application/json">` tag, parsed at initialization.
- **Multiple instances**: Each element uses Shopware's `element.id` (UUID) to scope DOM selectors, allowing multiple job boards on the same page.

### Styling

The component uses CSS custom properties (CSS variables) for theming:

```scss
--c24-color-primary: var(--bs-primary, #003366);
--c24-color-primary-light: #0066b3;
--c24-color-primary-dark: #002244;
```

These integrate with Shopware's Bootstrap variables when available, with fallbacks for standalone use.

### Responsive Breakpoints

- **Mobile** (< 480px): 1 column, larger spacing
- **Tablet** (480px - 767px): 2 columns
- **Desktop** (768px - 1199px): 5 columns, compact spacing
- **Large Desktop** (≥ 1200px): 5 columns, generous spacing

### Accessibility Features

- Semantic HTML with `<section>` and ARIA roles
- ARIA labels on metric values for screen readers
- Respects `prefers-reduced-motion` for animations
- Proper heading hierarchy (if needed, can be extended)

### Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- CSS Grid for layout
- CSS Custom Properties (IE11 not supported)
- CSS Animations with fallbacks

---

## Customization

### Extending the Blocks

If you need to customize block appearance or behavior:

1. **Twig Template Override**: Override templates in your theme

   ```
   MyTheme/src/Resources/views/storefront/element/cms-element-compass24-key-metrics.html.twig
   MyTheme/src/Resources/views/storefront/element/cms-element-compass24-job-offers.html.twig
   ```

2. **SCSS Customization**: Override styles in your theme

   ```scss
   // Key Metrics
   .compass24-key-metrics-component {
       .metrics-section {
           background: linear-gradient(135deg, #your-color-1, #your-color-2);
       }
   }

   // Job Offers — uses CSS custom properties for easy theming
   .c24-careers-widget {
       --c24-accent: #0066b3;
       --c24-accent-hover: #004d8c;
       --c24-accent-bg: rgba(0, 51, 102, 0.06);
   }
   ```

3. **JavaScript Enhancement**: Add custom JavaScript in your theme

   ```javascript
   import Plugin from 'src/plugin-system/plugin.class';
   
   export default class KeyMetricsPlugin extends Plugin {
       init() {
           // Your custom code
       }
   }
   ```

### Adding More Metrics

To support more than 5 metrics, modify:

1. `elements/compass24-key-metrics/index.js` - Add config fields
2. `elements/compass24-key-metrics/config/index.js` - Add UI fields
3. Template - Add metric blocks

### Adding Job Fields

To add new fields to job listings (e.g., salary range), modify:

1. `elements/compass24-job-offers/index.js` - Add field to `defaultJobs` objects
2. `elements/compass24-job-offers/config/` - Add input field to the config template
3. `views/storefront/element/cms-element-compass24-job-offers.html.twig` - Render the new field

---

## Troubleshooting

### Block Not Appearing in CMS

**Problem**: The block doesn't show up in the CMS editor.

**Solutions**:
1. Rebuild administration: `bin/build-administration.sh`
2. Clear cache: `bin/console cache:clear`
3. Check browser console for JavaScript errors
4. Verify plugin is activated: `bin/console plugin:list`

### Styles Not Applying

**Problem**: The block appears unstyled on the storefront.

**Solutions**:
1. Rebuild storefront: `bin/build-storefront.sh`
2. Clear cache: `bin/console cache:clear`
3. Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
4. Check browser console for CSS loading errors

### Configuration Not Saving

**Problem**: Changes in the CMS editor don't persist.

**Solutions**:
1. Ensure you clicked "Save" in the CMS editor
2. Check for JavaScript errors in browser console
3. Verify database permissions
4. Clear Shopware cache

### Animations Not Working

**Problem**: Fade-in animations don't play.

**Possible Causes**:
1. User has `prefers-reduced-motion` enabled (intentional)
2. CSS not loaded properly - rebuild storefront
3. Browser doesn't support CSS animations (very old browser)

---

## Development

### Local Development Setup

```bash
# Clone/copy plugin to Shopware installation
cd custom/plugins/Compass24Blocks

# Install dependencies (if any)
composer install

# Watch for changes (in separate terminals)
bin/watch-administration.sh
bin/watch-storefront.sh
```

### Testing

1. **Administration Testing**:
   - Create a test Shopping Experience
   - Add the block and configure different values
   - Test responsive preview modes

2. **Storefront Testing**:
   - Assign layout to a test category/page
   - Test on real mobile devices
   - Verify animations and accessibility

3. **Browser Testing**:
   - Chrome/Edge (Chromium)
   - Firefox
   - Safari (desktop and iOS)

---

## Version History

### Version 1.0.0 (February 2026)
- Initial release
- **Key Metrics Banner** block — 5 editable metrics with animated gradient banner
- **Job Offers Widget** block — interactive Petite Vue job board with search, filters, and full CRUD admin UI
- Responsive design for both blocks
- Full administration UI integration
- Accessibility support
- Animation effects

---

## Credits

**Developed for**: Compass24  
**Based on**: Original HTML widgets (key-metrics-section.html, job-widget.html)  
**Shopware Version**: 6.7.0+  
**License**: MIT

---

## Support

For issues, questions, or feature requests:
- Internal: Contact the development team
- Technical: Check Shopware 6 documentation at https://developer.shopware.com

---

## License

MIT License - See LICENSE file for details
