# Day 2.5: Storefront Development - Twig Templates & JavaScript

**Duration:** 1.5-2 days (10-14 hours with breaks)  
**Goal:** Master Shopware storefront customization with Twig templates and JavaScript plugins

> **Note for Frontend Developers:** This day focuses on what you'll work with most - templates, styling, and JavaScript! Take time to experiment and see your changes in the browser.

## Learning Objectives

- Understand Shopware storefront architecture
- Create and extend Twig templates
- Override and customize storefront templates
- Work with Twig variables and functions
- Create custom JavaScript plugins
- Integrate third-party JavaScript libraries
- Debug frontend issues

## Prerequisites

- Completed Day 1 and Day 2
- Basic HTML/CSS knowledge
- Basic JavaScript/ES6 knowledge
- Browser DevTools familiarity

---

## Part 1: Understanding Storefront Architecture (45 minutes)

### Theory: Shopware Storefront

Shopware 6's storefront is built with:
- **Twig**: Server-side templating engine
- **Bootstrap 5**: CSS framework (Shopware 6.5+)
- **SCSS**: For styling
- **JavaScript/TypeScript**: ES6+ compiled with Webpack
- **Stimulus**: Lightweight JavaScript framework

**Template Flow:**
```
Request → Controller → PageLoader → Page Object → Twig Template → HTML Response
```

### Official Documentation

📖 **Read these resources:**
- [Storefront Development Guide](https://developer.shopware.com/docs/guides/plugins/plugins/storefront/)
- [Customize Templates](https://developer.shopware.com/docs/guides/plugins/plugins/storefront/customize-templates)
- [Add Custom JavaScript](https://developer.shopware.com/docs/guides/plugins/plugins/storefront/add-custom-javascript)
- [Twig Reference](https://twig.symfony.com/doc/3.x/)

---

## Part 2: Your First Template Extension (90 minutes)

### Step 1: Create Template Directory Structure

```bash
cd custom/plugins/LearningBundle
mkdir -p src/Resources/views/storefront
```

### Step 2: Extend Product Detail Page

Create `src/Resources/views/storefront/page/product-detail.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/page/product-detail.html.twig' %}

{# Add a custom banner above product details #}
{% block page_product_detail_content %}
    <div class="learning-product-banner alert alert-info">
        <strong>🎉 Welcome!</strong> This is a custom banner added by LearningBundle plugin.
    </div>

    {# Keep original content #}
    {{ parent() }}
{% endblock %}
```

### Step 3: View Your Changes

```bash
# Clear cache
bin/console cache:clear

# Build storefront (if needed)
./bin/build-storefront.sh

# Visit any product page in the browser
```

### Step 4: Add Product Information Block

Create `/custom/plugins/LearningBundle/src/Resources/views/storefront/component/buy-widget/buy-widget.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/component/buy-widget/buy-widget.html.twig' %}

{# Add custom product info after price #}
{% block buy_widget_price %}
    {{ parent() }}
    
    <div class="learning-product-info mt-3 p-3 bg-light rounded">
        <h6>📦 Product Details</h6>
        <ul class="list-unstyled mb-0">
            <li><strong>Product Number:</strong> {{ page.product.productNumber }}</li>
            <li><strong>Stock:</strong> {{ page.product.stock }} units</li>
            <li><strong>Available:</strong> 
                {% if page.product.available %}
                    <span class="badge bg-success">Yes</span>
                {% else %}
                    <span class="badge bg-danger">No</span>
                {% endif %}
            </li>
        </ul>
    </div>
{% endblock %}
```

**🤓 Developer notes:** The AI-generated exercise was again faulty, i.e. had the wrong block names and file locations.
Here's how the actual template to extend from and modify was found:

- Look for CSS class `product-detail-price-container` to find the template for price display on a PDP.
- Found the class in `vendor/shopware/storefront/Resources/views/storefront/component/buy-widget/buy-widget.html.twig` in block `buy_widget_price`.
- Proper location for template modifer is thus: `vendor/shopware/storefront/Resources/views/storefront/component/buy-widget/buy-widget.html.twig`

### Step 5: Explore Block Structure

Use this command to find available blocks:

```bash
# Search for blocks in a template
grep -r "{% block" vendor/shopware/storefront/Resources/views/storefront/page/product-detail/

# Or use your IDE to browse
# vendor/shopware/storefront/Resources/views/storefront/page/product-detail/
```

---

## Part 3: Working with Twig Variables (75 minutes)

### Understanding Page Objects

Every storefront page has a page object with data:

```twig
{# In product-detail/index.html.twig #}

{# The page object contains: #}
{{ page.product }}           {# ProductEntity #}
{{ page.configuratorSettings }} {# Product variants #}
{{ page.reviews }}           {# Product reviews #}
{{ context }}                {# SalesChannelContext #}
{{ context.customer }}       {# Current customer #}
{{ context.salesChannel }}   {# Sales channel info #}
```

### Step 1: Display Product Information

Create `src/Resources/views/storefront/component/product/learning-info.html.twig`:

```twig
{# Custom component to display product information #}
<div class="learning-product-info card mb-4">
    <div class="card-header">
        <h5 class="mb-0">🔍 Product Technical Info</h5>
    </div>
    <div class="card-body">
        <table class="table table-sm table-borderless">
            <tbody>
                <tr>
                    <td><strong>Product ID:</strong></td>
                    <td><code>{{ product.id }}</code></td>
                </tr>
                <tr>
                    <td><strong>Created:</strong></td>
                    <td>{{ product.createdAt|date('Y-m-d H:i') }}</td>
                </tr>
                <tr>
                    <td><strong>Updated:</strong></td>
                    <td>{{ product.updatedAt|date('Y-m-d H:i') }}</td>
                </tr>
                <tr>
                    <td><strong>Categories:</strong></td>
                    <td>
                        {% if product.categories %}
                            {% for category in product.categories %}
                                <span class="badge bg-secondary">{{ category.name }}</span>
                            {% endfor %}
                        {% else %}
                            <em>None</em>
                        {% endif %}
                    </td>
                </tr>
                <tr>
                    <td><strong>Tags:</strong></td>
                    <td>
                        {% if product.tags %}
                            {% for tag in product.tags %}
                                <span class="badge bg-info">{{ tag.name }}</span>
                            {% endfor %}
                        {% else %}
                            <em>None</em>
                        {% endif %}
                    </td>
                </tr>
            </tbody>
        </table>
        
        {# Display custom fields if they exist #}
        {% if product.customFields %}
            <div class="mt-3">
                <h6>Custom Fields:</h6>
                <pre class="bg-light p-2 rounded">{{ product.customFields|json_encode(constant('JSON_PRETTY_PRINT')) }}</pre>
            </div>
        {% endif %}
    </div>
</div>
```

### Step 2: Include Your Component

Update `page/product-detail/index.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/page/product-detail/index.html.twig' %}

{% block page_product_detail_content %}
    {{ parent() }}
    
    {# Include our custom component #}
    {% sw_include '@LearningBundle/storefront/component/product/learning-info.html.twig' with {
        product: page.product
    } %}
{% endblock %}
```

### Step 3: Create Macro for Reusability

Create `src/Resources/views/storefront/component/learning-macros.html.twig`:

```twig
{# Reusable Twig macros #}

{% macro badge(label, variant = 'primary') %}
    <span class="badge bg-{{ variant }}">{{ label }}</span>
{% endmacro %}

{% macro productCard(product) %}
    <div class="card h-100">
        {% if product.cover %}
            <img src="{{ product.cover.url }}" 
                 class="card-img-top" 
                 alt="{{ product.name }}">
        {% endif %}
        <div class="card-body">
            <h5 class="card-title">{{ product.name }}</h5>
            {% if product.description %}
                <p class="card-text">{{ product.description|striptags|slice(0, 100) }}...</p>
            {% endif %}
            <p class="card-text">
                <strong>{{ product.calculatedPrice.totalPrice|currency }}</strong>
            </p>
        </div>
    </div>
{% endmacro %}

{% macro availabilityBadge(product) %}
    {% if product.available and product.stock > 0 %}
        {{ _self.badge('In Stock', 'success') }}
    {% elseif product.stock > 0 %}
        {{ _self.badge('Low Stock', 'warning') }}
    {% else %}
        {{ _self.badge('Out of Stock', 'danger') }}
    {% endif %}
{% endmacro %}
```

Use the macros:

```twig
{% import '@LearningBundle/storefront/component/learning-macros.html.twig' as macros %}

<div class="product-availability">
    {{ macros.availabilityBadge(page.product) }}
</div>
```

---

## Part 4: Custom JavaScript Plugin (90 minutes)

### Step 1: Create JavaScript Directory

```bash
cd custom/plugins/LearningBundle
mkdir -p src/Resources/app/storefront/src/plugin
```

### Step 2: Create Your First Plugin

Create `src/Resources/app/storefront/src/plugin/product-view-tracker.plugin.js`:

```javascript
import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

/**
 * Product View Tracker Plugin
 * Tracks when a product is viewed
 */
export default class ProductViewTrackerPlugin extends Plugin {
    
    static options = {
        /**
         * Endpoint to send tracking data
         */
        trackingUrl: '/store-api/learning/product-view',
        
        /**
         * Delay before tracking (milliseconds)
         */
        trackingDelay: 2000,
    };

    init() {
        // Get product ID from data attribute
        this.productId = this.el.dataset.productId;
        
        if (!this.productId) {
            console.warn('ProductViewTracker: No product ID found');
            return;
        }

        // Initialize HTTP client
        this.client = new HttpClient();
        
        // Track view after delay
        this.scheduleTracking();
        
        console.log(`ProductViewTracker initialized for product: ${this.productId}`);
    }

    scheduleTracking() {
        // Wait before tracking to ensure it's a real view
        setTimeout(() => {
            this.trackView();
        }, this.options.trackingDelay);
    }

    trackView() {
        const url = `${this.options.trackingUrl}/${this.productId}`;
        
        this.client.post(url, null, (response) => {
            console.log('Product view tracked successfully', response);
        });
    }
}
```

### Step 3: Register the Plugin

Create `src/Resources/app/storefront/src/main.js`:

```javascript
// Import plugins
import ProductViewTrackerPlugin from './plugin/product-view-tracker.plugin';

// Register plugins
const PluginManager = window.PluginManager;
PluginManager.register('ProductViewTracker', ProductViewTrackerPlugin, '[data-product-view-tracker]');
```

### Step 4: Add Plugin to Template

Update `page/product-detail/index.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/page/product-detail/index.html.twig' %}

{% block page_product_detail %}
    <div data-product-view-tracker
         data-product-id="{{ page.product.id }}"
         data-product-view-tracker-options='{{ {
             trackingUrl: '/store-api/learning/product-view',
             trackingDelay: 2000
         }|json_encode }}'>
        
        {{ parent() }}
    </div>
{% endblock %}
```

### Step 5: Build JavaScript

```bash
# Install dependencies (if not already done)
cd custom/plugins/LearningBundle
npm install

# Build JavaScript
./bin/build-storefront.sh

# Or watch for changes
./bin/watch-storefront.sh
```

---

## Part 5: Advanced JavaScript Patterns (90 minutes)

### Step 1: Interactive Product Gallery Plugin

Create `src/Resources/app/storefront/src/plugin/product-gallery-zoom.plugin.js`:

```javascript
import Plugin from 'src/plugin-system/plugin.class';

/**
 * Product Gallery Zoom Plugin
 * Adds zoom functionality to product images
 */
export default class ProductGalleryZoomPlugin extends Plugin {
    
    static options = {
        zoomLevel: 2,
        containerClass: 'zoom-container',
    };

    init() {
        this.images = this.el.querySelectorAll('img');
        
        if (this.images.length === 0) {
            return;
        }

        this._registerEvents();
    }

    _registerEvents() {
        this.images.forEach(img => {
            img.addEventListener('mouseenter', this.onMouseEnter.bind(this));
            img.addEventListener('mousemove', this.onMouseMove.bind(this));
            img.addEventListener('mouseleave', this.onMouseLeave.bind(this));
        });
    }

    onMouseEnter(event) {
        const img = event.target;
        img.style.cursor = 'zoom-in';
        this.createZoomContainer(img);
    }

    onMouseMove(event) {
        if (!this.zoomContainer) return;

        const img = event.target;
        const rect = img.getBoundingClientRect();
        
        // Calculate mouse position relative to image
        const x = ((event.clientX - rect.left) / rect.width) * 100;
        const y = ((event.clientY - rect.top) / rect.height) * 100;
        
        // Update zoom background position
        this.zoomContainer.style.backgroundPosition = `${x}% ${y}%`;
    }

    onMouseLeave(event) {
        this.removeZoomContainer();
        event.target.style.cursor = 'default';
    }

    createZoomContainer(img) {
        this.zoomContainer = document.createElement('div');
        this.zoomContainer.className = this.options.containerClass;
        this.zoomContainer.style.backgroundImage = `url(${img.src})`;
        this.zoomContainer.style.backgroundSize = `${this.options.zoomLevel * 100}%`;
        
        document.body.appendChild(this.zoomContainer);
    }

    removeZoomContainer() {
        if (this.zoomContainer) {
            this.zoomContainer.remove();
            this.zoomContainer = null;
        }
    }
}
```

### Step 2: AJAX Add to Cart Plugin

Create `src/Resources/app/storefront/src/plugin/ajax-add-to-cart.plugin.js`:

```javascript
import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

/**
 * AJAX Add to Cart Plugin
 * Adds products to cart without page reload
 */
export default class AjaxAddToCartPlugin extends Plugin {
    
    static options = {
        addToCartUrl: '/checkout/line-item/add',
        showNotification: true,
    };

    init() {
        this.client = new HttpClient();
        this._registerEvents();
    }

    _registerEvents() {
        // Find add to cart button
        const buyButton = this.el.querySelector('.btn-buy');
        
        if (buyButton) {
            buyButton.addEventListener('click', this.onAddToCart.bind(this));
        }
    }

    onAddToCart(event) {
        event.preventDefault();
        
        // Get product data from form
        const form = this.el.closest('form');
        const formData = new FormData(form);
        
        // Show loading state
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Adding...';
        button.disabled = true;

        // Send AJAX request
        this.client.post(
            this.options.addToCartUrl,
            formData,
            (response) => this.onSuccess(button, originalText, response),
            'application/json',
            false
        );
    }

    onSuccess(button, originalText, response) {
        // Restore button
        button.textContent = '✓ Added!';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.disabled = false;
        }, 2000);

        // Show notification
        if (this.options.showNotification) {
            this.showNotification('Product added to cart!');
        }

        // Publish event for other plugins
        this.$emitter.publish('ajaxAddToCart', {
            response: response
        });
    }

    showNotification(message) {
        // Create simple notification
        const notification = document.createElement('div');
        notification.className = 'alert alert-success position-fixed top-0 end-0 m-3';
        notification.style.zIndex = '9999';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}
```

### Step 3: Register All Plugins

Update `main.js`:

```javascript
import ProductViewTrackerPlugin from './plugin/product-view-tracker.plugin';
import ProductGalleryZoomPlugin from './plugin/product-gallery-zoom.plugin';
import AjaxAddToCartPlugin from './plugin/ajax-add-to-cart.plugin';

const PluginManager = window.PluginManager;

PluginManager.register(
    'ProductViewTracker',
    ProductViewTrackerPlugin,
    '[data-product-view-tracker]'
);

PluginManager.register(
    'ProductGalleryZoom',
    ProductGalleryZoomPlugin,
    '[data-product-gallery-zoom]'
);

PluginManager.register(
    'AjaxAddToCart',
    AjaxAddToCartPlugin,
    '[data-ajax-add-to-cart]'
);
```

---

## Part 6: Custom SCSS Styling (60 minutes)

### Step 1: Create SCSS Structure

```bash
cd custom/plugins/LearningBundle/src/Resources/app/storefront/src
mkdir -p scss/component
mkdir -p scss/layout
```

### Step 2: Create Component Styles

Create `scss/component/_product-info.scss`:

```scss
.learning-product-info {
    border-left: 4px solid $primary;
    
    h6 {
        color: $primary;
        font-weight: 600;
        margin-bottom: 1rem;
    }
    
    ul {
        li {
            padding: 0.25rem 0;
            
            strong {
                color: $dark;
            }
            
            .badge {
                font-size: 0.75rem;
            }
        }
    }
}

.learning-product-banner {
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    animation: slideDown 0.3s ease-out;
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
}

// Zoom container styles
.zoom-container {
    position: fixed;
    top: 50%;
    right: 2rem;
    transform: translateY(-50%);
    width: 300px;
    height: 300px;
    border: 2px solid $border-color;
    border-radius: 0.5rem;
    background-repeat: no-repeat;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    pointer-events: none;
    z-index: 1000;
}
```

### Step 3: Create Main SCSS Entry

Create `scss/base.scss`:

```scss
// Import Shopware variables
@import "~@shopware/storefront/src/scss/abstract/variables";

// Import components
@import "component/product-info";

// Custom animations
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

// Global custom styles
.learning-section {
    animation: fadeIn 0.5s ease-in;
}

// Responsive utilities
@media (max-width: 768px) {
    .zoom-container {
        display: none;
    }
}
```

---

## Part 7: Practical Exercises (120 minutes)

### Exercise 1: Product Comparison Feature (45-60 min)

Create a product comparison feature:

**Requirements:**
- Add "Compare" button to product cards
- Store selected products in localStorage
- Create comparison table template
- Display side-by-side comparison

**Hints:**
- Create `ProductComparePlugin` JavaScript class
- Use `localStorage.setItem()` and `getItem()`
- Create Twig template for comparison table
- Style with custom SCSS

### Exercise 2: Product Quick View Modal (45-60 min)

Add quick view functionality:

**Requirements:**
- "Quick View" button on product listings
- Load product details via AJAX
- Display in Bootstrap modal
- Include add to cart functionality

**Hints:**
- Extend existing product card template
- Create modal template
- Use HttpClient for AJAX
- Reuse existing buy widget template

### Exercise 3: Custom Product Filter (30-45 min)

Create interactive price filter:

**Requirements:**
- Price range slider with JavaScript
- Update URL parameters
- Filter products without page reload
- Show loading state

**Hints:**
- Use HTML5 range input
- Listen to 'input' event
- Update window.location.search
- Add CSS transitions

---

## Part 8: Debugging Frontend Issues (45 minutes)

### Browser DevTools

**Console Debugging:**
```javascript
// Add debug logs
console.log('Plugin initialized', this);
console.table(this.options);

// Debug plugin registration
console.log(window.PluginManager.getPluginInstances());
```

**Network Tab:**
- Check AJAX requests
- Verify API endpoints
- Inspect request/response

**Elements Tab:**
- Inspect Twig output
- Check data attributes
- Verify CSS classes

### Twig Debugging

```twig
{# Dump variable contents #}
{{ dump(page.product) }}

{# Check if variable exists #}
{% if page.product is defined %}
    Product exists
{% endif %}

{# Debug in dev environment only #}
{% if app.environment == 'dev' %}
    <pre>{{ page|json_encode(constant('JSON_PRETTY_PRINT')) }}</pre>
{% endif %}
```

### Common Issues

**1. JavaScript plugin not loading:**
```bash
# Rebuild storefront
./bin/build-storefront.sh

# Check browser console for errors
# Verify plugin registration in main.js
```

**2. Template changes not visible:**
```bash
# Clear cache
bin/console cache:clear

# Check theme compilation
bin/console theme:compile
```

**3. CSS changes not applying:**
```bash
# Rebuild with theme
./bin/build-storefront.sh

# Hard refresh in browser (Cmd+Shift+R / Ctrl+Shift+R)
```

---

## Key Takeaways

✅ **You've learned:**
- Shopware storefront template structure
- Extending templates with Twig
- Working with page objects and variables
- Creating custom JavaScript plugins
- Plugin communication and events
- SCSS/CSS customization
- Frontend debugging techniques
- Browser storage and AJAX patterns

## Next Steps

Tomorrow we'll work with:
- Database entities for frontend data
- Creating API endpoints for JavaScript
- Advanced storefront patterns
- Performance optimization

## Additional Resources

- [Twig Documentation](https://twig.symfony.com/doc/)
- [Bootstrap 5 Documentation](https://getbootstrap.com/)
- [Shopware Storefront Guide](https://developer.shopware.com/docs/guides/plugins/plugins/storefront/)
- [JavaScript Plugin System](https://developer.shopware.com/docs/guides/plugins/plugins/storefront/add-custom-javascript)

---

**Estimated Completion Time:** 10-14 hours (1.5-2 days)  
**Difficulty:** ⭐⭐⭐ Intermediate - Focus on Frontend

🎨 Great job! You're now a Shopware frontend developer!
