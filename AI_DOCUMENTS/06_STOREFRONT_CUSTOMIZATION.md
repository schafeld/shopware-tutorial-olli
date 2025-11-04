# Shopware 6 Storefront Customization Guide

## Storefront Architecture Overview 🏗️

The Shopware 6 storefront is built with:
- **Twig Templates**: Server-side templating
- **SCSS/CSS**: Styling with Bootstrap 4 foundation  
- **JavaScript**: ES6+ with Webpack compilation
- **Responsive Design**: Mobile-first approach

## Template Structure & Inheritance

### 1. Template Hierarchy
```
vendor/shopware/storefront/Resources/views/storefront/
├── base.html.twig                 # Root template
├── layout/
│   ├── header/                    # Header components
│   ├── footer/                    # Footer components
│   └── navigation/                # Navigation components
├── page/
│   ├── product-detail/           # Product detail page
│   ├── product-list/             # Category/listing pages
│   ├── checkout/                 # Checkout process
│   └── account/                  # Customer account
└── component/
    ├── product/                  # Product components
    ├── form/                     # Form elements
    └── buy-widget/               # Purchase components
```

### 2. Template Inheritance Example
```twig
{# custom/plugins/MyPlugin/src/Resources/views/storefront/page/product-detail/index.html.twig #}
{% sw_extends '@Storefront/storefront/page/product-detail/index.html.twig' %}

{% block page_product_detail_content %}
    <div class="custom-product-banner">
        <h2>Special Offer!</h2>
    </div>
    
    {# Keep original content #}
    {{ parent() }}
{% endblock %}
```

## Customizing Templates

### 1. Overriding Templates
Create the same path structure in your plugin:
```
custom/plugins/MyTheme/src/Resources/views/storefront/
└── page/
    └── product-detail/
        └── index.html.twig        # Overrides core template
```

### 2. Extending vs Overriding
```twig
{# Extending (recommended) - keeps core functionality #}
{% sw_extends '@Storefront/storefront/page/product-detail/index.html.twig' %}

{# Complete override - replaces entire template #}
{# Just create file without sw_extends #}
```

### 3. Block Structure
```twig
{# Understanding blocks #}
{% block page_product_detail %}
    {% block page_product_detail_header %}
        {# Header content #}
    {% endblock %}
    
    {% block page_product_detail_content %}
        {% block page_product_detail_media %}
            {# Product images #}
        {% endblock %}
        
        {% block page_product_detail_buy %}
            {# Buy widget #}
        {% endblock %}
    {% endblock %}
{% endblock %}
```

## SCSS/CSS Customization

### 1. SCSS Structure
```
custom/plugins/MyTheme/src/Resources/app/storefront/src/scss/
├── abstract/
│   ├── _variables.scss           # Custom variables
│   └── _mixins.scss             # Custom mixins
├── base/
│   ├── _base.scss               # Base styles
│   └── _typography.scss         # Typography
├── component/
│   ├── _product-card.scss       # Component styles
│   └── _navigation.scss         # Navigation styles
├── layout/
│   ├── _header.scss             # Layout styles
│   └── _footer.scss
├── page/
│   ├── _product-detail.scss     # Page-specific styles
│   └── _checkout.scss
└── base.scss                    # Main entry point
```

### 2. Main SCSS Entry Point
```scss
// custom/plugins/MyTheme/src/Resources/app/storefront/src/scss/base.scss

// Import Shopware variables first
@import "~@shopware/storefront/src/scss/abstract/variables";

// Your custom variables
@import "abstract/variables";

// Import Shopware base styles
@import "~@shopware/storefront/src/scss/base";

// Your custom styles
@import "base/base";
@import "component/product-card";
@import "layout/header";
@import "page/product-detail";
```

### 3. Custom Variables
```scss
// custom/plugins/MyTheme/src/Resources/app/storefront/src/scss/abstract/_variables.scss

// Override Shopware variables
$sw-color-brand-primary: #ff6b6b;
$sw-color-brand-secondary: #4ecdc4;

// Custom variables
$custom-border-radius: 8px;
$custom-font-family: 'Roboto', sans-serif;
$custom-box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);

// Responsive breakpoints
$custom-mobile: 768px;
$custom-tablet: 992px;
$custom-desktop: 1200px;
```

### 4. Component Styling
```scss
// custom/plugins/MyTheme/src/Resources/app/storefront/src/scss/component/_product-card.scss

.card-product {
    border-radius: $custom-border-radius;
    box-shadow: $custom-box-shadow;
    transition: transform 0.3s ease;
    
    &:hover {
        transform: translateY(-5px);
    }
    
    .card-product-image {
        border-radius: $custom-border-radius $custom-border-radius 0 0;
    }
    
    .card-product-name {
        font-family: $custom-font-family;
        font-weight: 600;
        color: $sw-color-brand-primary;
    }
    
    .btn-buy {
        background: linear-gradient(45deg, $sw-color-brand-primary, $sw-color-brand-secondary);
        border: none;
        border-radius: $custom-border-radius;
        
        &:hover {
            transform: scale(1.05);
        }
    }
}
```

## JavaScript Customization

### 1. JavaScript Structure
```
custom/plugins/MyTheme/src/Resources/app/storefront/src/
├── main.js                      # Entry point
└── plugin/
    ├── product-image-gallery.plugin.js
    ├── custom-slider.plugin.js
    └── ajax-cart.plugin.js
```

### 2. Main JavaScript Entry
```javascript
// custom/plugins/MyTheme/src/Resources/app/storefront/src/main.js

// Import Shopware plugins
import Feature from 'src/helper/feature.helper';

// Import custom plugins
import ProductImageGalleryPlugin from './plugin/product-image-gallery.plugin';
import CustomSliderPlugin from './plugin/custom-slider.plugin';

// Register plugins
const PluginManager = window.PluginManager;

PluginManager.register('ProductImageGallery', ProductImageGalleryPlugin, '[data-product-image-gallery]');
PluginManager.register('CustomSlider', CustomSliderPlugin, '[data-custom-slider]');

// Initialize plugins on page load
document.addEventListener('DOMContentLoaded', () => {
    PluginManager.initializePlugins();
});
```

### 3. Custom Plugin Example
```javascript
// custom/plugins/MyTheme/src/Resources/app/storefront/src/plugin/product-image-gallery.plugin.js

import Plugin from 'src/plugin-system/plugin.class';

export default class ProductImageGalleryPlugin extends Plugin {
    
    static options = {
        autoplay: true,
        speed: 500,
        showThumbnails: true
    };

    init() {
        this.currentIndex = 0;
        this.images = this.el.querySelectorAll('.gallery-image');
        this.thumbnails = this.el.querySelectorAll('.gallery-thumbnail');
        
        this._registerEvents();
        this._initializeGallery();
    }

    _registerEvents() {
        // Thumbnail clicks
        this.thumbnails.forEach((thumb, index) => {
            thumb.addEventListener('click', () => {
                this.showImage(index);
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.previousImage();
            if (e.key === 'ArrowRight') this.nextImage();
        });

        // Touch/swipe support
        let startX = 0;
        this.el.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        this.el.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const diff = startX - endX;
            
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    this.nextImage();
                } else {
                    this.previousImage();
                }
            }
        });
    }

    _initializeGallery() {
        this.showImage(0);
        
        if (this.options.autoplay) {
            this.startAutoplay();
        }
    }

    showImage(index) {
        this.currentIndex = index;
        
        // Hide all images
        this.images.forEach(img => img.classList.remove('active'));
        this.thumbnails.forEach(thumb => thumb.classList.remove('active'));
        
        // Show selected image
        this.images[index].classList.add('active');
        this.thumbnails[index].classList.add('active');
        
        // Trigger custom event
        this.$emitter.publish('imageChanged', { index });
    }

    nextImage() {
        const nextIndex = (this.currentIndex + 1) % this.images.length;
        this.showImage(nextIndex);
    }

    previousImage() {
        const prevIndex = this.currentIndex === 0 ? this.images.length - 1 : this.currentIndex - 1;
        this.showImage(prevIndex);
    }

    startAutoplay() {
        this.autoplayInterval = setInterval(() => {
            this.nextImage();
        }, 3000);
    }

    stopAutoplay() {
        if (this.autoplayInterval) {
            clearInterval(this.autoplayInterval);
        }
    }
}
```

## Creating Custom Components

### 1. Product Card Component
```twig
{# custom/plugins/MyTheme/src/Resources/views/storefront/component/product/card-custom.html.twig #}

{% block component_product_card_custom %}
    <div class="card product-card-custom" data-custom-slider>
        {% block component_product_card_image %}
            <div class="card-img-wrapper">
                {% if product.cover %}
                    <img src="{{ product.cover.media.url }}" 
                         alt="{{ product.cover.media.alt }}"
                         class="card-img-top">
                {% endif %}
                
                {% if product.markAsTopseller %}
                    <span class="badge badge-topseller">Bestseller</span>
                {% endif %}
            </div>
        {% endblock %}

        {% block component_product_card_body %}
            <div class="card-body">
                {% block component_product_card_name %}
                    <h5 class="card-title">
                        <a href="{{ seoUrl('frontend.detail.page', {'productId': product.id}) }}">
                            {{ product.translated.name }}
                        </a>
                    </h5>
                {% endblock %}

                {% block component_product_card_price %}
                    <div class="product-price">
                        {% if product.calculatedPrices.count > 1 %}
                            <span class="price-from">{{ "product.listingFromPrice"|trans }}</span>
                        {% endif %}
                        
                        <span class="price-current">
                            {{ product.calculatedPrice.unitPrice|currency }}
                        </span>
                        
                        {% if product.calculatedPrice.listPrice %}
                            <span class="price-list">
                                {{ product.calculatedPrice.listPrice.price|currency }}
                            </span>
                        {% endif %}
                    </div>
                {% endblock %}

                {% block component_product_card_rating %}
                    {% if product.ratingAverage %}
                        <div class="product-rating">
                            {% for i in 1..5 %}
                                <i class="fas fa-star {{ i <= product.ratingAverage ? 'active' : '' }}"></i>
                            {% endfor %}
                            <small>({{ product.reviewCount }})</small>
                        </div>
                    {% endif %}
                {% endblock %}
            </div>
        {% endblock %}

        {% block component_product_card_actions %}
            <div class="card-actions">
                {% if product.availableStock > 0 %}
                    <button type="button" 
                            class="btn btn-primary btn-buy"
                            data-add-to-cart="true"
                            data-product-id="{{ product.id }}">
                        {{ "product.addToCart"|trans }}
                    </button>
                {% else %}
                    <button type="button" class="btn btn-outline-secondary" disabled>
                        {{ "product.outOfStock"|trans }}
                    </button>
                {% endif %}
                
                <button type="button" 
                        class="btn btn-outline-primary btn-wishlist"
                        data-wishlist-add="true"
                        data-product-id="{{ product.id }}">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
        {% endblock %}
    </div>
{% endblock %}
```

### 2. Using Custom Components
```twig
{# In your listing template #}
{% for product in products %}
    {% sw_include '@MyTheme/storefront/component/product/card-custom.html.twig' %}
{% endfor %}
```

## Advanced Customization Techniques

### 1. Custom Page Controller & Template
```php
<?php declare(strict_types=1);

namespace MyTheme\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class CustomPageController extends StorefrontController
{
    private GenericPageLoader $genericPageLoader;

    public function __construct(GenericPageLoader $genericPageLoader)
    {
        $this->genericPageLoader = $genericPageLoader;
    }

    /**
     * @Route("/custom-page", name="frontend.custom.page", methods={"GET"})
     */
    public function customPage(Request $request, Context $context): Response
    {
        $page = $this->genericPageLoader->load($request, $context);
        
        // Add custom data to page
        $page->assign([
            'customData' => $this->getCustomData(),
            'specialOffers' => $this->getSpecialOffers()
        ]);

        return $this->renderStorefront('@MyTheme/storefront/page/custom-page/index.html.twig', [
            'page' => $page
        ]);
    }

    private function getCustomData(): array
    {
        return [
            'message' => 'Welcome to our custom page!',
            'features' => ['Feature 1', 'Feature 2', 'Feature 3']
        ];
    }

    private function getSpecialOffers(): array
    {
        // Fetch special offers from database
        return [];
    }
}
```

### 2. AJAX Components
```javascript
// custom/plugins/MyTheme/src/Resources/app/storefront/src/plugin/ajax-cart.plugin.js

export default class AjaxCartPlugin extends Plugin {
    init() {
        this._registerEvents();
    }

    _registerEvents() {
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-add-to-cart]')) {
                e.preventDefault();
                this.addToCart(e.target);
            }
        });
    }

    async addToCart(button) {
        const productId = button.dataset.productId;
        
        try {
            button.classList.add('loading');
            
            const response = await fetch('/checkout/line-item/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    lineItems: {
                        [productId]: {
                            id: productId,
                            type: 'product',
                            referencedId: productId,
                            quantity: 1
                        }
                    }
                })
            });

            if (response.ok) {
                this.showSuccessMessage();
                this.updateCartCount();
            } else {
                this.showErrorMessage();
            }
        } catch (error) {
            this.showErrorMessage();
        } finally {
            button.classList.remove('loading');
        }
    }

    showSuccessMessage() {
        // Show success notification
    }

    showErrorMessage() {
        // Show error notification  
    }

    updateCartCount() {
        // Update cart counter in header
    }
}
```

## Build & Development Workflow

### 1. Development Commands
```bash
# Watch for changes during development
./bin/watch-storefront.sh

# Build for production
./bin/build-storefront.sh

# Hot reload (if configured)
npm run hot
```

### 2. Asset Building Configuration
```javascript
// webpack.config.js (if custom build needed)
const { join, resolve } = require('path');

module.exports = () => {
    return {
        resolve: {
            alias: {
                '@MyTheme': resolve(
                    join(__dirname, '..', 'src/Resources/app/storefront/src')
                )
            }
        }
    };
};
```

### 3. Plugin Configuration for Assets
```xml
<!-- Resources/config/services.xml -->
<service id="MyTheme\Resources\app\storefront\build\webpack.config.js" />
```

This comprehensive guide covers the essential aspects of Shopware 6 storefront customization. Remember to always test your changes thoroughly and follow responsive design principles for the best user experience.