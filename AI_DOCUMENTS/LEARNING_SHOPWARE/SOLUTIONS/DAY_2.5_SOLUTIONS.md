# Day 2.5: Complete Frontend Exercise Solutions

> **Note:** These solutions focus on practical Storefront development with Twig and JavaScript!

> **Remeber:** These AI time estimates are generally rubbish. Even just copy-typing the exercise solution with a tiny bit of typo-debugging takes longer than most of these time estimates. If you need to study the Shopware docs for propritary practices and methods you'll need even more time.
---

## Exercise 1: Product Comparison Feature (45-60 min)

### Step 1: Create JavaScript Plugin

Create `src/Resources/app/storefront/src/plugin/product-compare.plugin.js`:

```javascript
import Plugin from 'src/plugin-system/plugin.class';

/**
 * Product Comparison Plugin
 * Allows users to compare products side-by-side
 */
export default class ProductComparePlugin extends Plugin {
    
    static options = {
        storageKey: 'learning_product_compare',
        maxProducts: 4,
        comparePageUrl: '/compare',
    };

    init() {
        this.storage = window.localStorage;
        this.compareProducts = this.loadCompareList();
        
        this._registerEvents();
        this.updateUI();
        
        console.log('ProductCompare initialized with', this.compareProducts.length, 'products');
    }

    _registerEvents() {
        // Listen to compare button clicks
        this.el.addEventListener('click', this.onToggleCompare.bind(this));
        
        // Listen to custom events from other instances
        document.addEventListener('compareListUpdated', this.onCompareListUpdated.bind(this));
    }

    onToggleCompare(event) {
        event.preventDefault();
        
        const productId = this.el.dataset.productId;
        const productName = this.el.dataset.productName;
        const productImage = this.el.dataset.productImage;
        const productPrice = this.el.dataset.productPrice;
        
        if (this.isInCompareList(productId)) {
            this.removeFromCompare(productId);
        } else {
            this.addToCompare({
                id: productId,
                name: productName,
                image: productImage,
                price: productPrice
            });
        }
    }

    addToCompare(product) {
        if (this.compareProducts.length >= this.options.maxProducts) {
            this.showNotification(`Maximum ${this.options.maxProducts} products can be compared`, 'warning');
            return;
        }

        if (!this.isInCompareList(product.id)) {
            this.compareProducts.push(product);
            this.saveCompareList();
            this.showNotification(`${product.name} added to comparison`, 'success');
            
            // Notify other plugin instances
            this.broadcastUpdate();
            
            // Show modal with comparison
            this.showCompareModal();
        }
    }
    
    showCompareModal() {
        const modal = document.getElementById('compareModal');
        if (modal && this.compareProducts.length > 0) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    }

    removeFromCompare(productId) {
        this.compareProducts = this.compareProducts.filter(p => p.id !== productId);
        this.saveCompareList();
        this.showNotification('Product removed from comparison', 'info');
        this.broadcastUpdate();
    }

    isInCompareList(productId) {
        return this.compareProducts.some(p => p.id === productId);
    }

    loadCompareList() {
        const stored = this.storage.getItem(this.options.storageKey);
        return stored ? JSON.parse(stored) : [];
    }

    saveCompareList() {
        this.storage.setItem(this.options.storageKey, JSON.stringify(this.compareProducts));
    }

    broadcastUpdate() {
        const event = new CustomEvent('compareListUpdated', {
            detail: { products: this.compareProducts }
        });
        document.dispatchEvent(event);
    }

    onCompareListUpdated(event) {
        this.compareProducts = event.detail.products;
        this.updateUI();
    }

    updateUI() {
        const productId = this.el.dataset.productId;
        
        if (this.isInCompareList(productId)) {
            this.el.classList.add('is-comparing');
            this.el.innerHTML = '<i class="fas fa-check"></i> In Comparison';
        } else {
            this.el.classList.remove('is-comparing');
            this.el.innerHTML = '<i class="fas fa-balance-scale"></i> Compare';
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 2500);
    }
}
```

### Step 2: Add Compare Button to Product Cards

Create `src/Resources/views/storefront/component/product/card/action.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/component/product/card/action.html.twig' %}

{% block component_product_box_action_inner %}
    {{ parent() }}
    
    {# Add compare button - JavaScript will handle adding to list and showing modal #}
    <button class="btn btn-sm btn-outline-secondary mt-2 w-100 product-compare-btn"
            data-product-compare
            data-product-id="{{ product.id }}"
            data-product-name="{{ product.translated.name }}"
            data-product-image="{% if product.cover.media %}{{ product.cover.media.url }}{% endif %}"
            data-product-price="{{ product.calculatedPrice.totalPrice }}">
        <i class="fas fa-balance-scale"></i> Compare
    </button>
{% endblock %}
```

### Step 3: Create Comparison Modal Template

Create `src/Resources/views/storefront/component/product/compare-modal.html.twig`:

```twig
{# Product Comparison Modal - No controller needed, fully client-side #}
<div class="modal fade" id="compareModal" tabindex="-1" aria-labelledby="compareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="compareModalLabel">
                    <i class="fas fa-balance-scale"></i> Product Comparison
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="comparison-container">
                    <p class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading products...
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
        function loadComparisonData() {
            const compareProducts = JSON.parse(localStorage.getItem('learning_product_compare') || '[]');
            const container = document.getElementById('comparison-container');

            if (compareProducts.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <h4>No products to compare</h4>
                        <p>Add products to comparison from product listings or detail pages.</p>
                        <a href="/" class="btn btn-primary">Browse Products</a>
                    </div>
                `;
                return;
            }

            // Build comparison table
            let html = '<div class="table-responsive"><table class="table table-bordered">';
            
            // Header row with product images and names
            html += '<thead><tr><th>Product</th>';
            compareProducts.forEach(product => {
                html += `
                    <th class="text-center">
                        <img src="${product.image}" alt="${product.name}" style="max-width: 100px;" class="mb-2">
                        <h6>${product.name}</h6>
                    </th>
                `;
            });
            html += '</tr></thead>';

            // Price row
            html += '<tbody><tr><th>Price</th>';
            compareProducts.forEach(product => {
                html += `<td class="text-center"><strong>${product.price} €</strong></td>`;
            });
            html += '</tr>';

            // Product ID row
            html += '<tr><th>Product ID</th>';
            compareProducts.forEach(product => {
                html += `<td class="text-center"><code>${product.id}</code></td>`;
            });
            html += '</tr>';

            // Actions row
            html += '<tr><th>Actions</th>';
            compareProducts.forEach(product => {
                html += `
                    <td class="text-center">
                        <a href="/detail/${product.id}" class="btn btn-sm btn-primary">View Details</a>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCompare('${product.id}')">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </td>
                `;
            });
            html += '</tr>';

            html += '</tbody></table></div>';

            container.innerHTML = html;
        }

        function removeFromCompare(productId) {
            let products = JSON.parse(localStorage.getItem('learning_product_compare') || '[]');
            products = products.filter(p => p.id !== productId);
            localStorage.setItem('learning_product_compare', JSON.stringify(products));
            
            // Broadcast update to all plugin instances
            const event = new CustomEvent('compareListUpdated', {
                detail: { products: products }
            });
            document.dispatchEvent(event);
            
            // Reload the modal content
            loadComparisonData();
            
            // Close modal if no products left
            if (products.length === 0) {
                const modal = document.getElementById('compareModal');
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            }
        }
        
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadComparisonData();
            
            // Reload data when modal is shown
            const modal = document.getElementById('compareModal');
            if (modal) {
                modal.addEventListener('show.bs.modal', function() {
                    loadComparisonData();
                });
            }
        });
</script>
```

### Step 4: Register Plugin and Include Modal

Update `main.js`:

```javascript
import ProductComparePlugin from './plugin/product-compare.plugin';

PluginManager.register(
    'ProductCompare',
    ProductComparePlugin,
    '[data-product-compare]'
);
```

Create `src/Resources/views/storefront/base.html.twig` to include the compare modal globally:

```twig
{% sw_extends '@Storefront/storefront/base.html.twig' %}

{% block base_body_inner %}
    {{ parent() }}
    
    {# Include compare modal component #}
    {% sw_include '@LearningBundle/storefront/component/product/compare-modal.html.twig' %}
{% endblock %}
```

**Note:** This extends the base template to add the compare modal to every storefront page. The modal is hidden by default and shown when users click the compare button.

### Step 5: Add CSS Styling (Optional)

Create `scss/component/_product-compare.scss`:

```scss
// Product compare button styling
.product-compare-btn {
    &.is-comparing {
        background-color: $success;
        color: $white;
        border-color: $success;
        
        &:hover {
            background-color: darken($success, 10%);
            border-color: darken($success, 10%);
        }
    }
}

.fade-out {
    animation: fadeOut 0.3s ease-out forwards;
}

@keyframes fadeOut {
    to {
        opacity: 0;
        transform: translateX(20px);
    }
}

// Notification animations
.alert {
    &.position-fixed {
        animation: slideInRight 0.3s ease-out;
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
```

### Step 6: Test the Solution

```bash
# Build storefront
./bin/build-storefront.sh

# Clear cache
bin/console cache:clear

# Test in browser:
# 1. Go to product listing
# 2. Click "Compare" button on a product
# 3. You'll see a success notification and the comparison modal opens automatically
# 4. Click "Compare" on more products to add them (modal updates in real-time)
# 5. Remove products using the "Remove" button in the modal
```

---

## Exercise 2: Product Quick View Modal (45-60 min)

### Step 1: Create Quick View Plugin

Create `src/Resources/app/storefront/src/plugin/product-quick-view.plugin.js`:

```javascript
import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

/**
 * Product Quick View Plugin
 * Shows product details in modal without leaving page
 */
export default class ProductQuickViewPlugin extends Plugin {
    
    static options = {
        modalSelector: '#productQuickViewModal',
        productDetailUrl: '/product-quick-view',
    };

    init() {
        this.client = new HttpClient();
        this._registerEvents();
    }

    _registerEvents() {
        this.el.addEventListener('click', this.onQuickViewClick.bind(this));
    }

    onQuickViewClick(event) {
        event.preventDefault();
        
        const productId = this.el.dataset.productId;
        const productNumber = this.el.dataset.productNumber;
        
        if (!productId) {
            console.error('No product ID provided');
            return;
        }

        this.showLoading();
        this.loadProductDetails(productId, productNumber);
    }

    loadProductDetails(productId, productNumber) {
        // Use Store API to load product
        const url = `/store-api/product/${productId}`;
        
        this.client.get(url, (response) => {
            this.renderQuickView(JSON.parse(response));
        }, 'application/json');
    }

    renderQuickView(product) {
        const modal = document.querySelector(this.options.modalSelector);
        
        if (!modal) {
            console.error('Modal not found');
            return;
        }

        // Update modal content
        const modalBody = modal.querySelector('.modal-body');
        modalBody.innerHTML = this.buildQuickViewHTML(product);
        
        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Initialize add to cart functionality
        this.initializeAddToCart(modal, product);
    }

    buildQuickViewHTML(product) {
        return `
            <div class="row">
                <div class="col-md-6">
                    ${product.cover ? `
                        <img src="${product.cover.url}" 
                             alt="${product.translated.name}"
                             class="img-fluid rounded">
                    ` : '<div class="alert alert-secondary">No image available</div>'}
                </div>
                <div class="col-md-6">
                    <h3>${product.translated.name}</h3>
                    <p class="text-muted">Product #${product.productNumber}</p>
                    
                    <div class="product-price mb-3">
                        <h4 class="text-primary">
                            ${product.calculatedPrice.totalPrice.toFixed(2)} €
                        </h4>
                    </div>
                    
                    ${product.translated.description ? `
                        <div class="product-description mb-3">
                            <h6>Description</h6>
                            <p>${product.translated.description.substring(0, 200)}...</p>
                        </div>
                    ` : ''}
                    
                    <div class="product-availability mb-3">
                        <h6>Availability</h6>
                        ${product.available ? 
                            '<span class="badge bg-success">In Stock</span>' :
                            '<span class="badge bg-danger">Out of Stock</span>'}
                        <span class="ms-2">${product.stock} units</span>
                    </div>
                    
                    <div class="quick-view-actions">
                        <button class="btn btn-primary btn-lg w-100 mb-2"
                                data-quick-view-add-to-cart
                                data-product-id="${product.id}"
                                ${!product.available ? 'disabled' : ''}>
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                        <a href="/detail/${product.id}" 
                           class="btn btn-outline-secondary w-100">
                            View Full Details
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    initializeAddToCart(modal, product) {
        const addToCartBtn = modal.querySelector('[data-quick-view-add-to-cart]');
        
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', () => {
                this.addToCart(product, addToCartBtn);
            });
        }
    }

    addToCart(product, button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        button.disabled = true;

        const formData = new FormData();
        formData.append('lineItems[' + product.id + '][id]', product.id);
        formData.append('lineItems[' + product.id + '][type]', 'product');
        formData.append('lineItems[' + product.id + '][referencedId]', product.id);
        formData.append('lineItems[' + product.id + '][quantity]', '1');

        this.client.post(
            '/checkout/line-item/add',
            formData,
            () => {
                button.innerHTML = '<i class="fas fa-check"></i> Added!';
                setTimeout(() => {
                    // Reload page to update cart
                    window.location.reload();
                }, 1000);
            }
        );
    }

    showLoading() {
        const modal = document.querySelector(this.options.modalSelector);
        if (modal) {
            const modalBody = modal.querySelector('.modal-body');
            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-3">Loading product details...</p>
                </div>
            `;
            
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    }
}
```

### Step 2: Add Quick View Button to Product Cards

Create `src/Resources/views/storefront/component/product/card/action.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/component/product/card/action.html.twig' %}

{% block component_product_box_action_inner %}
    {{ parent() }}
    
    {# Add quick view button #}
    <div class="btn-group w-100 mt-2" role="group">
        <a href="{{ seoUrl('frontend.detail.page', {'productId': product.id}) }}"
           class="btn btn-primary">
            View Details
        </a>
        <button class="btn btn-outline-primary"
                data-product-quick-view
                data-product-id="{{ product.id }}"
                data-product-number="{{ product.productNumber }}"
                title="Quick View">
            <i class="fas fa-eye"></i>
        </button>
    </div>
{% endblock %}
```

### Step 3: Create Modal Template

Create `src/Resources/views/storefront/component/product/quick-view-modal.html.twig`:

```twig
{# Quick View Modal #}
<div class="modal fade" 
     id="productQuickViewModal" 
     tabindex="-1" 
     aria-labelledby="quickViewModalLabel" 
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickViewModalLabel">
                    <i class="fas fa-eye"></i> Quick View
                </h5>
                <button type="button" 
                        class="btn-close" 
                        data-bs-dismiss="modal" 
                        aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                {# Content loaded via JavaScript #}
            </div>
        </div>
    </div>
</div>
```

Update `src/Resources/views/storefront/base.html.twig` to also include the Quick View modal:

```twig
{% sw_extends '@Storefront/storefront/base.html.twig' %}

{% block base_body_inner %}
    {{ parent() }}
    
    {# Include modals #}
    {% sw_include '@LearningBundle/storefront/component/product/compare-modal.html.twig' %}
    {% sw_include '@LearningBundle/storefront/component/product/quick-view-modal.html.twig' %}
{% endblock %}
```

**Note:** Both features are fully client-side using localStorage, AJAX, and Bootstrap modals - no controllers needed!

### Step 4: Register Plugin

```javascript
import ProductQuickViewPlugin from './plugin/product-quick-view.plugin';

PluginManager.register(
    'ProductQuickView',
    ProductQuickViewPlugin,
    '[data-product-quick-view]'
);
```

### Step 5: Test

```bash
./bin/build-storefront.sh
bin/console cache:clear

# Test: Click eye icon on any product card to see quick view
```

---

## Exercise 3: Custom Product Filter (30-45 min)

### Goal
Create an interactive price range filter that updates product listings without page reload. This teaches URL manipulation, dynamic filtering, and smooth UX patterns.

### Step 1: Create Price Filter Plugin

Create `src/Resources/app/storefront/src/plugin/price-filter.plugin.js`:

```javascript
import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

/**
 * Price Filter Plugin
 * Interactive price range filter with URL updates
 */
export default class PriceFilterPlugin extends Plugin {
    
    static options = {
        minPriceInputSelector: '[data-price-filter-min]',
        maxPriceInputSelector: '[data-price-filter-max]',
        minPriceDisplaySelector: '[data-price-display-min]',
        maxPriceDisplaySelector: '[data-price-display-max]',
        applyButtonSelector: '[data-price-filter-apply]',
        resetButtonSelector: '[data-price-filter-reset]',
        productListingSelector: '.cms-element-product-listing',
        loadingClass: 'is-loading',
        updateDelay: 300, // Debounce delay in ms
    };

    init() {
        try {
            this.minPriceInput = DomAccess.querySelector(this.el, this.options.minPriceInputSelector);
            this.maxPriceInput = DomAccess.querySelector(this.el, this.options.maxPriceInputSelector);
            this.minPriceDisplay = DomAccess.querySelector(this.el, this.options.minPriceDisplaySelector);
            this.maxPriceDisplay = DomAccess.querySelector(this.el, this.options.maxPriceDisplaySelector);
            this.applyButton = DomAccess.querySelector(this.el, this.options.applyButtonSelector);
            this.resetButton = DomAccess.querySelector(this.el, this.options.resetButtonSelector, false);
        } catch (e) {
            console.error('PriceFilter: Required elements not found', e);
            return;
        }

        this.productListing = document.querySelector(this.options.productListingSelector);
        this.updateTimeout = null;

        this._registerEvents();
        this._updateDisplayValues();
        this._loadInitialValues();
        
        console.log('PriceFilter initialized');
    }

    _registerEvents() {
        // Update display values in real-time as sliders move
        this.minPriceInput.addEventListener('input', this._onPriceInputChange.bind(this));
        this.maxPriceInput.addEventListener('input', this._onPriceInputChange.bind(this));
        
        // Apply filter when button is clicked
        this.applyButton.addEventListener('click', this._onApplyFilter.bind(this));
        
        // Reset filter
        if (this.resetButton) {
            this.resetButton.addEventListener('click', this._onResetFilter.bind(this));
        }
        
        // Apply filter on Enter key
        this.minPriceInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this._onApplyFilter();
        });
        this.maxPriceInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this._onApplyFilter();
        });
    }

    _loadInitialValues() {
        // Load filter values from URL if present
        const urlParams = new URLSearchParams(window.location.search);
        const minPrice = urlParams.get('min-price');
        const maxPrice = urlParams.get('max-price');
        
        if (minPrice) {
            this.minPriceInput.value = minPrice;
        }
        if (maxPrice) {
            this.maxPriceInput.value = maxPrice;
        }
        
        this._updateDisplayValues();
    }

    _onPriceInputChange() {
        // Ensure min doesn't exceed max
        const minValue = parseFloat(this.minPriceInput.value);
        const maxValue = parseFloat(this.maxPriceInput.value);
        
        if (minValue > maxValue) {
            this.minPriceInput.value = maxValue;
        }
        
        this._updateDisplayValues();
    }

    _updateDisplayValues() {
        const minValue = this.minPriceInput.value;
        const maxValue = this.maxPriceInput.value;
        
        this.minPriceDisplay.textContent = this._formatPrice(minValue);
        this.maxPriceDisplay.textContent = this._formatPrice(maxValue);
    }

    _formatPrice(value) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR'
        }).format(value);
    }

    _onApplyFilter(event) {
        if (event) {
            event.preventDefault();
        }
        
        const minPrice = this.minPriceInput.value;
        const maxPrice = this.maxPriceInput.value;
        
        // Update URL with filter parameters
        this._updateUrl(minPrice, maxPrice);
        
        // Show loading state
        this._showLoading();
        
        // Reload page with new filters (in a real implementation, you'd use AJAX)
        setTimeout(() => {
            window.location.reload();
        }, 300);
    }

    _onResetFilter(event) {
        event.preventDefault();
        
        // Reset to default values
        const minDefault = this.minPriceInput.getAttribute('min') || '0';
        const maxDefault = this.minPriceInput.getAttribute('max') || '1000';
        
        this.minPriceInput.value = minDefault;
        this.maxPriceInput.value = maxDefault;
        
        this._updateDisplayValues();
        
        // Remove filter from URL
        const url = new URL(window.location);
        url.searchParams.delete('min-price');
        url.searchParams.delete('max-price');
        
        this._showLoading();
        
        window.location.href = url.toString();
    }

    _updateUrl(minPrice, maxPrice) {
        const url = new URL(window.location);
        
        // Update or add filter parameters
        url.searchParams.set('min-price', minPrice);
        url.searchParams.set('max-price', maxPrice);
        
        // Update browser history without reload
        window.history.pushState({}, '', url);
    }

    _showLoading() {
        this.applyButton.disabled = true;
        this.applyButton.classList.add(this.options.loadingClass);
        this.applyButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';
        
        if (this.productListing) {
            this.productListing.style.opacity = '0.5';
            this.productListing.style.pointerEvents = 'none';
        }
    }
}
```

### Step 2: Create Filter Template Component

Create `src/Resources/views/storefront/component/product/price-filter.html.twig`:

```twig
{# Price Range Filter Component #}
<div class="price-filter-widget card mb-4" 
     data-price-filter
     data-price-filter-options='{{ {
         updateDelay: 300
     }|json_encode }}'>
    
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter"></i> Price Range
        </h5>
    </div>
    
    <div class="card-body">
        {# Price range display #}
        <div class="price-filter-display d-flex justify-content-between mb-3">
            <div class="price-min">
                <small class="text-muted">Min</small>
                <div class="fw-bold" data-price-display-min>€0</div>
            </div>
            <div class="price-max">
                <small class="text-muted">Max</small>
                <div class="fw-bold" data-price-display-max>€1000</div>
            </div>
        </div>
        
        {# Range sliders #}
        <div class="price-filter-sliders mb-3">
            <div class="mb-3">
                <label for="priceMin" class="form-label">Minimum Price</label>
                <input type="range" 
                       class="form-range" 
                       id="priceMin"
                       data-price-filter-min
                       min="0" 
                       max="1000" 
                       step="10" 
                       value="0">
            </div>
            
            <div class="mb-3">
                <label for="priceMax" class="form-label">Maximum Price</label>
                <input type="range" 
                       class="form-range" 
                       id="priceMax"
                       data-price-filter-max
                       min="0" 
                       max="1000" 
                       step="10" 
                       value="1000">
            </div>
        </div>
        
        {# Action buttons #}
        <div class="price-filter-actions d-grid gap-2">
            <button type="button" 
                    class="btn btn-primary" 
                    data-price-filter-apply>
                <i class="fas fa-check"></i> Apply Filter
            </button>
            
            <button type="button" 
                    class="btn btn-outline-secondary btn-sm" 
                    data-price-filter-reset>
                <i class="fas fa-undo"></i> Reset
            </button>
        </div>
        
        {# Active filter indicator #}
        {% if app.request.get('min-price') or app.request.get('max-price') %}
            <div class="alert alert-info mt-3 mb-0">
                <small>
                    <i class="fas fa-info-circle"></i>
                    Active filter: 
                    {{ app.request.get('min-price', '0') }}€ - {{ app.request.get('max-price', '1000') }}€
                </small>
            </div>
        {% endif %}
    </div>
</div>
```

### Step 3: Add Filter to Product Listing Pages

**Option A: Add to Filter Panel (Recommended)**

Extend the existing filter panel to include your custom price filter alongside Shopware's default filters.

Create `src/Resources/views/storefront/component/listing/filter-panel.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/component/listing/filter-panel.html.twig' %}

{% block component_filter_panel_items %}
    {# Add custom price filter before default filters #}
    {% block component_filter_panel_item_custom_price %}
        <div class="filter-panel-item">
            {% sw_include '@LearningBundle/storefront/component/product/price-filter.html.twig' %}
        </div>
    {% endblock %}
    
    {# Keep all default filters (manufacturer, properties, price, rating, etc.) #}
    {{ parent() }}
{% endblock %}
```

**Option B: Add to Search Page**

For search pages specifically, add the filter above the search results.

Create `src/Resources/views/storefront/page/search/search-pagelet.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/page/search/search-pagelet.html.twig' %}

{% block element_product_listing_wrapper %}
    {# Add custom filter above search results #}
    <div class="container mb-4">
        {% sw_include '@LearningBundle/storefront/component/product/price-filter.html.twig' %}
    </div>
    
    {# Original search results with sidebar filter and listing #}
    {{ parent() }}
{% endblock %}
```

**Option C: Add to Product Detail Page**

For product detail pages, extend the base content area.

Create `src/Resources/views/storefront/page/content/product-detail.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/page/content/product-detail.html.twig' %}

{% block cms_content %}
    {# Add price filter widget above product content #}
    <div class="container mb-4">
        <div class="row">
            <div class="col-md-3">
                {% sw_include '@LearningBundle/storefront/component/product/price-filter.html.twig' %}
            </div>
            <div class="col-md-9">
                {# Original product detail content #}
                {{ parent() }}
            </div>
        </div>
    </div>
{% endblock %}
```

**Note:** For product listing/category pages, you can also extend `element/cms-element-sidebar-filter.html.twig` to add custom filters next to the default sidebar filter button.

### Step 4: Add CSS Styling

Create `src/Resources/app/storefront/src/scss/component/_price-filter.scss`:

```scss
.price-filter-widget {
    border: 1px solid $border-color;
    transition: box-shadow 0.3s ease;
    
    &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .card-header {
        background-color: $light;
        border-bottom: 2px solid $primary;
        
        h5 {
            color: $primary;
            font-size: 1rem;
        }
    }
}

.price-filter-display {
    padding: 1rem;
    background-color: $light;
    border-radius: 0.375rem;
    
    .price-min,
    .price-max {
        text-align: center;
        
        small {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        
        .fw-bold {
            font-size: 1.25rem;
            color: $primary;
        }
    }
}

.price-filter-sliders {
    .form-range {
        cursor: pointer;
        
        &::-webkit-slider-thumb {
            background-color: $primary;
            transition: all 0.2s ease;
            
            &:hover {
                transform: scale(1.2);
                box-shadow: 0 0 0 8px rgba($primary, 0.1);
            }
        }
        
        &::-moz-range-thumb {
            background-color: $primary;
            transition: all 0.2s ease;
            
            &:hover {
                transform: scale(1.2);
                box-shadow: 0 0 0 8px rgba($primary, 0.1);
            }
        }
    }
    
    label {
        font-size: 0.875rem;
        font-weight: 500;
        color: $secondary;
    }
}

.price-filter-actions {
    button {
        transition: all 0.2s ease;
        
        &.is-loading {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        &:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
    }
}

// Loading state animations
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.cms-element-product-listing {
    transition: opacity 0.3s ease;
    
    &.is-loading {
        animation: pulse 1.5s ease-in-out infinite;
    }
}

// Responsive adjustments
@media (max-width: 768px) {
    .price-filter-widget {
        margin-bottom: 1.5rem;
    }
    
    .price-filter-display {
        padding: 0.75rem;
        
        .price-min,
        .price-max {
            .fw-bold {
                font-size: 1rem;
            }
        }
    }
}
```

Import in `src/Resources/app/storefront/src/scss/base.scss`:

```scss
@import "component/price-filter";
```

### Step 5: Register the Plugin

Update `src/Resources/app/storefront/src/main.js`:

```javascript
import PriceFilterPlugin from './plugin/price-filter.plugin';

PluginManager.register(
    'PriceFilter',
    PriceFilterPlugin,
    '[data-price-filter]'
);
```

### Step 6: Optional - Add Server-Side Filtering

For the filter to actually work, you'd need to handle the URL parameters on the server side. Here's a basic example:

Create `src/Subscriber/ProductListingSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace LearningBundle\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductListingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'handlePriceFilter'
        ];
    }

    public function handlePriceFilter(ProductListingCriteriaEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return;
        }

        $minPrice = $request->query->get('min-price');
        $maxPrice = $request->query->get('max-price');

        if ($minPrice !== null || $maxPrice !== null) {
            $criteria = $event->getCriteria();

            // Build range filter parameters
            $range = [];
            if ($minPrice !== null && $minPrice >= 0) {
                $range[RangeFilter::GTE] = (float) $minPrice;
            }
            if ($maxPrice !== null && $maxPrice >= 0) {
                $range[RangeFilter::LTE] = (float) $maxPrice;
            }

            // IMPORTANT: Use 'product.cheapestPrice' field - NOT just 'price'
            // This is the correct indexed field for price filtering in Shopware
            if (!empty($range)) {
                $criteria->addFilter(
                    new RangeFilter('product.cheapestPrice', $range)
                );
            }
        }
    }
}
```

Register in `src/Resources/config/services.xml`:

```xml
<service id="LearningBundle\Subscriber\ProductListingSubscriber">
    <argument type="service" id="request_stack"/>
    <tag name="kernel.event_subscriber"/>
</service>
```

### Step 7: Test the Solution

```bash
# Build storefront
./bin/build-storefront.sh

# Clear cache
bin/console cache:clear

# Test in browser:
# 1. Navigate to a product listing or search page
# 2. Use the price range sliders to adjust min/max values
# 3. Observe the real-time price display updates
# 4. Click "Apply Filter" to filter products
# 5. Click "Reset" to clear the filter
# 6. Check URL parameters are updated correctly
```

### Features Implemented

✅ **Interactive dual range sliders** with smooth transitions  
✅ **Real-time price display** that updates as you drag  
✅ **URL parameter management** for shareable filter states  
✅ **Loading states** with visual feedback  
✅ **Reset functionality** to clear filters  
✅ **Responsive design** that works on mobile  
✅ **Server-side integration** (optional) for actual filtering  
✅ **Active filter indicator** showing current range  
✅ **Keyboard support** (Enter key to apply)  

### Enhancement Ideas

- Add debouncing for smoother slider interaction
- Implement AJAX filtering without page reload
- Add animation for product list updates
- Show product count for each price range
- Add preset price ranges (e.g., "Under €50", "€50-€100")
- Remember user's last filter preferences in localStorage

---

## Summary

You've built three real-world frontend features:

✅ **Product Comparison** - localStorage, event broadcasting, dynamic UI
✅ **Quick View Modal** - AJAX loading, Bootstrap modals, API integration  
✅ **Price Filter** - Interactive controls, URL manipulation, smooth UX

### Skills Mastered:

- JavaScript Plugin architecture
- localStorage for client-side persistence
- AJAX with Shopware Store API
- Bootstrap modal integration
- Dynamic content rendering
- Event-driven communication
- CSS animations and transitions

Continue to Day 3 to connect frontend with database entities!
