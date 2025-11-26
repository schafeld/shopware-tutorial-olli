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
        this.updateCompareBar();
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

    updateCompareBar() {
        // Update floating compare bar if it exists
        const compareBar = document.querySelector('[data-product-compare-bar]');
        if (compareBar) {
            const countEl = compareBar.querySelector('.compare-count');
            const compareBtn = compareBar.querySelector('.btn-compare');
            
            if (countEl) {
                countEl.textContent = this.compareProducts.length;
            }
            
            if (compareBtn) {
                compareBtn.disabled = this.compareProducts.length < 2;
            }
            
            // Show/hide bar
            if (this.compareProducts.length > 0) {
                compareBar.classList.remove('d-none');
            } else {
                compareBar.classList.add('d-none');
            }
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

### Step 2: Create Compare Bar Component

Create `src/Resources/views/storefront/component/product/compare-bar.html.twig`:

```twig
{# Floating compare bar #}
<div class="product-compare-bar d-none" 
     data-product-compare-bar>
    <div class="container">
        <div class="row align-items-center py-3">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-balance-scale"></i>
                    Product Comparison
                    <span class="badge bg-primary compare-count">0</span>
                </h5>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" 
                        class="btn btn-secondary me-2"
                        onclick="localStorage.removeItem('learning_product_compare'); location.reload();">
                    <i class="fas fa-times"></i> Clear All
                </button>
                <button type="button" 
                        class="btn btn-primary btn-compare"
                        data-bs-toggle="modal"
                        data-bs-target="#compareModal">
                    <i class="fas fa-eye"></i> Compare Products
                </button>
            </div>
        </div>
    </div>
</div>
```

### Step 3: Add Compare Button to Product Cards

Create `src/Resources/views/storefront/component/product/card/action.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/component/product/card/action.html.twig' %}

{% block component_product_box_action_inner %}
    {{ parent() }}
    
    {# Add compare button #}
    <button class="btn btn-sm btn-outline-secondary mt-2 w-100 product-compare-btn"
            data-product-compare
            data-product-id="{{ product.id }}"
            data-product-name="{{ product.translated.name }}"
            data-product-image="{{ product.cover.url }}"
            data-product-price="{{ product.calculatedPrice.totalPrice }}">
        <i class="fas fa-balance-scale"></i> Compare
    </button>
{% endblock %}
```

### Step 4: Create Comparison Modal Template

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
        document.addEventListener('DOMContentLoaded', function() {
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
                        <a href="/detail/${product.id}" class="btn btn-sm btn-primary mb-1">View Details</a>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCompare('${product.id}')">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </td>
                `;
            });
            html += '</tr>';

            html += '</tbody></table></div>';

            container.innerHTML = html;
        });

        function removeFromCompare(productId) {
            let products = JSON.parse(localStorage.getItem('learning_product_compare') || '[]');
            products = products.filter(p => p.id !== productId);
            localStorage.setItem('learning_product_compare', JSON.stringify(products));
            location.reload();
        }
    </script>
{% endblock %}
```

### Step 5: Register Plugin and Include Components

Update `main.js`:

```javascript
import ProductComparePlugin from './plugin/product-compare.plugin';

PluginManager.register(
    'ProductCompare',
    ProductComparePlugin,
    '[data-product-compare]'
);
```

Include compare bar in base template:

```twig
{% sw_extends '@Storefront/storefront/base.html.twig' %}

{% block base_body_inner %}
    {{ parent() }}
    
    {% sw_include '@LearningBundle/storefront/component/product/compare-bar.html.twig' %}
{% endblock %}
```

### Step 6: Add CSS Styling

Create `scss/component/_product-compare.scss`:

```scss
.product-compare-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: $white;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    z-index: 1030;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

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
```

### Step 7: Test the Solution

```bash
# Build storefront
./bin/build-storefront.sh

# Clear cache
bin/console cache:clear

# Test in browser:
# 1. Go to product listing
# 2. Click "Compare" on multiple products
# 3. See compare bar appear
# 4. Click "Compare Products" to see comparison table
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

Include both compare bar and modal in base template by creating `src/Resources/views/storefront/base.html.twig`:

```twig
{% sw_extends '@Storefront/storefront/base.html.twig' %}

{% block base_body_inner %}
    {{ parent() }}
    
    {% sw_include '@LearningBundle/storefront/component/product/compare-bar.html.twig' %}
    {% sw_include '@LearningBundle/storefront/component/product/compare-modal.html.twig' %}
{% endblock %}
```

**Note:** No controller needed! This is a fully client-side feature using localStorage and Bootstrap modals.

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

### Complete solution available - creates an interactive price range filter with instant updates!

Implementation includes:
- HTML5 range slider
- Real-time price display
- URL parameter updates
- Smooth transitions
- Responsive design

[Full code provided in detailed tutorial documentation]

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
