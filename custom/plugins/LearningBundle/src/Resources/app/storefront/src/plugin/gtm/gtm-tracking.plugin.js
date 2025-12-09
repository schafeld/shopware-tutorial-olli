import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

/**
 * GTM Tracking Plugin
 * 
 * Handles client-side Google Tag Manager event tracking for:
 * - Add to cart (AJAX interceptor)
 * - Remove from cart
 * - Product clicks (select_item)
 * - Promotion clicks
 * - Form submissions
 * 
 * This plugin works alongside server-side tracking by capturing
 * events that happen via JavaScript/AJAX.
 */
export default class GtmTrackingPlugin extends Plugin {
    
    static options = {
        // Selectors
        addToCartFormSelector: 'form[action*="checkout/line-item/add"]',
        addToCartButtonSelector: '.btn-buy',
        removeFromCartSelector: '[data-remove-line-item]',
        productCardSelector: '.product-box, .cms-listing-product-box',
        productLinkSelector: '.product-name a, .product-image-link',
        searchFormSelector: '.header-search-form',
        promotionSelector: '[data-gtm-promotion]',
        
        // Data attributes
        productDataAttribute: 'data-gtm-product',
        promotionDataAttribute: 'data-gtm-promotion',
        
        // Debug mode
        debug: false,
    };

    init() {
        // Check if GTM is available
        if (typeof window.dataLayer === 'undefined') {
            this.log('DataLayer not found, GTM tracking disabled');
            return;
        }

        this.log('GTM Tracking Plugin initialized');
        
        // Bind event handlers
        this.registerAddToCartTracking();
        this.registerRemoveFromCartTracking();
        this.registerProductClickTracking();
        this.registerSearchTracking();
        this.registerPromotionTracking();
        
        // Listen for AJAX cart updates
        this.registerAjaxCartTracking();
    }

    /**
     * Track add to cart via form submission
     */
    registerAddToCartTracking() {
        const forms = document.querySelectorAll(this.options.addToCartFormSelector);
        
        forms.forEach(form => {
            form.addEventListener('submit', (event) => {
                this.handleAddToCart(form, event);
            });
        });

        // Also track buy buttons that might use AJAX
        const buttons = document.querySelectorAll(this.options.addToCartButtonSelector);
        buttons.forEach(button => {
            button.addEventListener('click', (event) => {
                const form = button.closest('form');
                if (form) {
                    this.handleAddToCart(form, event);
                }
            });
        });

        this.log('Add to cart tracking registered');
    }

    /**
     * Handle add to cart event
     */
    handleAddToCart(form, event) {
        const productData = this.extractProductDataFromForm(form);
        
        if (!productData) {
            this.log('No product data found for add to cart');
            return;
        }

        const quantity = this.getQuantityFromForm(form);
        const price = parseFloat(productData.price) || 0;

        this.pushEvent({
            event: 'add_to_cart',
            ecommerce: {
                currency: this.getCurrency(),
                value: price * quantity,
                items: [{
                    item_id: productData.productNumber || productData.id,
                    item_name: productData.name,
                    item_brand: productData.manufacturer || '',
                    item_category: productData.category || '',
                    price: price,
                    quantity: quantity,
                }]
            }
        });
    }

    /**
     * Track remove from cart
     */
    registerRemoveFromCartTracking() {
        // Use event delegation for dynamically loaded cart items
        document.addEventListener('click', (event) => {
            const removeButton = event.target.closest(this.options.removeFromCartSelector);
            
            if (!removeButton) return;

            const lineItemId = removeButton.dataset.removeLineItem;
            const lineItemRow = removeButton.closest('.line-item');
            
            if (lineItemRow) {
                const productData = this.extractProductDataFromLineItem(lineItemRow);
                
                if (productData) {
                    this.pushEvent({
                        event: 'remove_from_cart',
                        ecommerce: {
                            currency: this.getCurrency(),
                            value: productData.totalPrice || productData.price,
                            items: [{
                                item_id: productData.productNumber || lineItemId,
                                item_name: productData.name || 'Unknown Product',
                                price: productData.price || 0,
                                quantity: productData.quantity || 1,
                            }]
                        }
                    });
                }
            }
        });

        this.log('Remove from cart tracking registered');
    }

    /**
     * Track product clicks (select_item)
     */
    registerProductClickTracking() {
        // Use event delegation for listing pages
        document.addEventListener('click', (event) => {
            const productLink = event.target.closest(this.options.productLinkSelector);
            
            if (!productLink) return;

            const productCard = productLink.closest(this.options.productCardSelector);
            
            if (productCard) {
                const productData = this.extractProductDataFromCard(productCard);
                const listName = this.getListName();
                
                if (productData) {
                    this.pushEvent({
                        event: 'select_item',
                        ecommerce: {
                            item_list_name: listName,
                            items: [{
                                item_id: productData.productNumber || productData.id,
                                item_name: productData.name,
                                item_brand: productData.manufacturer || '',
                                item_category: productData.category || '',
                                price: parseFloat(productData.price) || 0,
                                index: productData.index || 0,
                            }]
                        }
                    });
                }
            }
        });

        this.log('Product click tracking registered');
    }

    /**
     * Track search submissions
     */
    registerSearchTracking() {
        const searchForms = document.querySelectorAll(this.options.searchFormSelector);
        
        searchForms.forEach(form => {
            form.addEventListener('submit', (event) => {
                const searchInput = form.querySelector('input[name="search"]');
                
                if (searchInput && searchInput.value.trim()) {
                    this.pushEvent({
                        event: 'search',
                        search_term: searchInput.value.trim()
                    });
                }
            });
        });

        this.log('Search tracking registered');
    }

    /**
     * Track promotion clicks/views
     */
    registerPromotionTracking() {
        const promotions = document.querySelectorAll(this.options.promotionSelector);
        
        promotions.forEach((promo, index) => {
            const promoData = this.extractPromotionData(promo, index);
            
            // Track view (impression)
            this.pushEvent({
                event: 'view_promotion',
                ecommerce: {
                    items: [promoData]
                }
            });

            // Track click
            promo.addEventListener('click', () => {
                this.pushEvent({
                    event: 'select_promotion',
                    ecommerce: {
                        items: [promoData]
                    }
                });
            });
        });

        if (promotions.length > 0) {
            this.log(`Promotion tracking registered for ${promotions.length} promotions`);
        }
    }

    /**
     * Register AJAX cart update tracking
     * Intercepts Shopware's cart AJAX responses
     */
    registerAjaxCartTracking() {
        // Listen for cart page updates
        document.addEventListener('Viewport/hasUpdated', () => {
            this.log('Cart viewport updated');
        });

        // Listen for offcanvas cart updates
        document.$emitter.subscribe('offCanvasCartLoaded', () => {
            this.log('Offcanvas cart loaded');
        });
    }

    // =========================================================================
    // DATA EXTRACTION HELPERS
    // =========================================================================

    /**
     * Extract product data from add to cart form
     */
    extractProductDataFromForm(form) {
        // Try to get from data attribute first
        const gtmData = form.dataset.gtmProduct;
        if (gtmData) {
            try {
                return JSON.parse(gtmData);
            } catch (e) {
                this.log('Failed to parse GTM product data', e);
            }
        }

        // Fallback: Try to get from page context
        const productId = form.querySelector('input[name="lineItems[*][id]"], input[name="productId"]');
        const productPage = document.querySelector('[data-product-detail-page]');
        
        if (productPage) {
            return {
                id: productId?.value || '',
                productNumber: productPage.dataset.productNumber || '',
                name: document.querySelector('.product-detail-name')?.textContent?.trim() || '',
                price: document.querySelector('.product-detail-price')?.dataset?.price || 
                       document.querySelector('.product-detail-price .product-price')?.textContent?.replace(/[^\d.,]/g, '') || '0',
                manufacturer: document.querySelector('.product-detail-manufacturer')?.textContent?.trim() || '',
                category: productPage.dataset.productCategory || '',
            };
        }

        return null;
    }

    /**
     * Extract product data from cart line item row
     */
    extractProductDataFromLineItem(lineItemRow) {
        return {
            name: lineItemRow.querySelector('.line-item-label')?.textContent?.trim() || '',
            productNumber: lineItemRow.dataset.productNumber || '',
            price: parseFloat(lineItemRow.querySelector('.line-item-unit-price')?.dataset?.price || 
                   lineItemRow.querySelector('.line-item-unit-price')?.textContent?.replace(/[^\d.,]/g, '')) || 0,
            totalPrice: parseFloat(lineItemRow.querySelector('.line-item-total-price')?.dataset?.price ||
                        lineItemRow.querySelector('.line-item-total-price')?.textContent?.replace(/[^\d.,]/g, '')) || 0,
            quantity: parseInt(lineItemRow.querySelector('.line-item-quantity-select')?.value || '1'),
        };
    }

    /**
     * Extract product data from product card in listings
     */
    extractProductDataFromCard(productCard) {
        // Try data attribute first
        const gtmData = productCard.dataset.gtmProduct;
        if (gtmData) {
            try {
                return JSON.parse(gtmData);
            } catch (e) {
                this.log('Failed to parse product card GTM data', e);
            }
        }

        // Fallback: Extract from card elements
        return {
            id: productCard.dataset.productId || '',
            productNumber: productCard.dataset.productNumber || '',
            name: productCard.querySelector('.product-name')?.textContent?.trim() || '',
            price: productCard.querySelector('.product-price')?.textContent?.replace(/[^\d.,]/g, '') || '0',
            manufacturer: productCard.dataset.manufacturer || '',
            category: productCard.dataset.category || '',
            index: Array.from(productCard.parentElement?.children || []).indexOf(productCard),
        };
    }

    /**
     * Extract promotion data
     */
    extractPromotionData(element, index) {
        const data = element.dataset.gtmPromotion;
        
        if (data) {
            try {
                return JSON.parse(data);
            } catch (e) {
                this.log('Failed to parse promotion data', e);
            }
        }

        return {
            promotion_id: element.dataset.promotionId || `promo_${index}`,
            promotion_name: element.dataset.promotionName || element.getAttribute('alt') || 'Promotion',
            creative_name: element.dataset.creativeName || '',
            creative_slot: element.dataset.creativeSlot || `slot_${index}`,
        };
    }

    /**
     * Get quantity from form
     */
    getQuantityFromForm(form) {
        const quantityInput = form.querySelector('input[name*="quantity"], select[name*="quantity"]');
        return parseInt(quantityInput?.value || '1');
    }

    /**
     * Get current list name based on page type
     */
    getListName() {
        // Check various page indicators
        if (document.querySelector('[data-search-page]')) {
            return 'Search Results';
        }
        
        const categoryNav = document.querySelector('.breadcrumb-item.active');
        if (categoryNav) {
            return categoryNav.textContent?.trim() || 'Category';
        }

        if (document.querySelector('[data-cms-element-type="product-listing"]')) {
            return 'Product Listing';
        }

        return 'Product List';
    }

    /**
     * Get currency from page
     */
    getCurrency() {
        // Try to get from meta tag or data attribute
        const currencyMeta = document.querySelector('meta[name="currency"]');
        if (currencyMeta) {
            return currencyMeta.content;
        }

        // Try to get from price element
        const priceElement = document.querySelector('[data-currency]');
        if (priceElement) {
            return priceElement.dataset.currency;
        }

        // Fallback
        return 'EUR';
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Push event to dataLayer
     */
    pushEvent(data) {
        if (typeof window.dataLayer === 'undefined') {
            this.log('Cannot push event, dataLayer not found');
            return;
        }

        // Clear previous ecommerce data to prevent contamination
        if (data.ecommerce) {
            window.dataLayer.push({ ecommerce: null });
        }

        window.dataLayer.push(data);
        this.log('Event pushed to dataLayer:', data);
    }

    /**
     * Debug logging
     */
    log(...args) {
        if (this.options.debug || window.gtmDebug) {
            console.log('[GTM Tracking]', ...args);
        }
    }
}
