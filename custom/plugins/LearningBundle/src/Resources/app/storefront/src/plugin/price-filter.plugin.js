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
        updateDelay: 500 // Debounce delay in ms
    };

    init() {
        try {
            this.minPriceInput = DomAccess.querySelector(this.el, this.options.minPriceInputSelector);
            this.maxPriceInput = DomAccess.querySelector(this.el, this.options.maxPriceInputSelector);
            this.minPriceDisplay = DomAccess.querySelector(this.el, this.options.minPriceDisplaySelector);
            this.maxPriceDisplay = DomAccess.querySelector(this.el, this.options.maxPriceDisplaySelector);
            this.applyButton = DomAccess.querySelector(this.el, this.options.applyButtonSelector);
            this.resetButton = DomAccess.querySelector(this.el, this.options.resetButtonSelector);
            this.productListing = DomAccess.querySelector(document, this.options.productListingSelector);
        } catch (e) {
            console.error('PriceFilterPlugin: Required elements not found.', e);
            return;
        }

        this.productListing = document.querySelector(this.options.productListingSelector);
        this.updateTimeout = null;

        this._registerEvents();
        this._updateDisplayValues();
        this._loadInitialValues();

        console.log('PriceFilterPlugin initialized');
    }

    _registerEvents() {
        // Update display values on input change
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
            if (e.key === 'Enter') {
                this._onApplyFilter();
            }
        });
        this.maxPriceInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this._onApplyFilter();
            }
        });
    }

    _loadInitialValues() {
        // Load filter values from url if present
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
        // Ensure min does not exceed max
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

        // Update url with filter parameters
        this._updateUrl(minPrice, maxPrice);

        // Show loading state
        this._showLoading();

        // Reload page with new filters (in real app, use AJAX)
        setTimeout(() => {
            window.location.reload();
        }, this.options.updateDelay);
    }

    _onResetFilter(event) {
        event.preventDefault();

        // Reset to default values
        const minDefault = this.minPriceInput.getAttribute('min') || '0';
        const maxDefault = this.maxPriceInput.getAttribute('max') || '1000';

        this.minPriceInput.value = minDefault;
        this.maxPriceInput.value = maxDefault;

        this._updateDisplayValues();

        // Update url to remove filter parameters
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

        // Update browser url without reloading
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