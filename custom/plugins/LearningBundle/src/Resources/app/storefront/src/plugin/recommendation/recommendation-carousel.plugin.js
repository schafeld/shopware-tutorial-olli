import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

/**
 * Product Recommendation Carousel Plugin
 * 
 * Loads and displays personalized product recommendations
 * with interactive carousel and Ajax add-to-cart.
 */
export default class RecommendationCarouselPlugin extends Plugin {

    static options = {
        // API endpoints
        recommendationsUrl: '/store-api/learning/recommendations',
        addToCartUrl: '/checkout/line-item/add',

        // Display options
        limit: 6,
        autoplay: false,
        autoplaySpeed: 5000,

        // Selectors
        carouselSelector: '[data-recommendations-carousel]',
        loadingSelector: '.recommendations-loading',
        errorSelector: '.recommendations-error',
        emptySelector: '.recommendations-empty',
        quickAddButtonSelector: '[data-quick-add]',

        // Animation
        slideSpeed: 300,
    };

    init() {
        this.httpClient = new HttpClient();
        this.productId = this.el.dataset.productId;

        this.carousel = this.el.querySelector(this.options.carouselSelector);
        this.loadingEl = this.el.querySelector(this.options.loadingSelector);
        this.errorEl = this.el.querySelector(this.options.errorSelector);
        this.emptyEl = this.el.querySelector(this.options.emptySelector);

        this.currentIndex = 0;
        this.recommendations = [];

        this.loadRecommendations();
    }

    /**
     * Load recommendations from API
     */
    async loadRecommendations() {
        try {
            this.showLoading();

            const url = `${this.options.recommendationsUrl}/${this.productId}?limit=${this.options.limit}`;

            const response = await this.httpClient.get(url, (responseText) => {
                return JSON.parse(responseText);
            });

            if (response.success && response.data && response.data.length > 0) {
                this.recommendations = response.data;
                this.renderRecommendations();
                this.initializeCarousel();
                this.registerQuickAddHandlers();
            } else {
                this.showEmpty();
            }
        } catch (error) {
            console.error('Error loading recommendations:', error);
            this.showError();
        } 
    }

    /**
     * Render recommendation cards into the carousel
     */
    renderRecommendations() {
        this.hideLoading();

        const cardsHtml = this.recommendations.map((rec, index) => {
            return this.createProductCard(rec, index);
        }).join('');

        this.carousel.innerHTML = `
            <div class="carousel-track">
                ${cardsHtml}
            </div>
            <button class="carousel-nav carousel-nav-prev" data-carousel-prev>
                <svg width="24" height="24">
                    <use xlink:href="#icon-chevron-left"></use>
                </svg>
            </button>
            <button class="carousel-nav carousel-nav-next" data-carousel-next>
                <svg width="24" height="24">
                    <use xlink:href="#icon-chevron-right"></use>
                </svg>
            </button>
            <div class="carousel-indicators">
               ${this.createIndicators()}
            </div> 
        `;

        this.carousel.style.display = 'block';
    }

    /**
     * Create HTML for a single product card
     */
    createProductCard(recommendation, index) {
        const product = recommendation.product;
        const score = recommendation.affinityScore;

        return `
            <div class="recommendation-card"
                data-index="${index}"
                data-product-id="${product.id}">

                <a href="/detail/${product.id}" class="recommendation-card-link">
                    ${this.createProductImage(product)}
                    ${score ? `<span class="recommendation-badge">${Math.round(score)}% Match</span>` : ''}
                </a>

                <div class="recommendation-card-body">
                    <a href="/detail/${product.id}" class="recommendation-card-title">
                        ${product.translated.name}
                    </a>

                    <div class="recommendation-card-price">
                        <span class="price">${this.formatPrice(product.calculatedPrice)}</span>
                    </div>

                    <div class="recommendation-card-actions">
                        <button type="button"
                            class="btn btn-sm btn-primary recommendation-quick-add"
                            data-product-id="${product.id}"
                            data-quick-add="true">
                            <span class="button-text">Add to Cart</span>
                            <span class="button-loading" style="display: none;">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Create product image HTML
     */
    createProductImage(product) {
        if (product.cover && product.cover.media) {
            return `<img src="${product.cover.media.url}"
                alt="${product.translated.name}"
                class="recommendation-card-image" 
                loading="lazy">`;
        }
        return `<div class="recommendation-card-placeholder">
                    <svg width="48" height="48" fill="currentColor">
                        <use xlink:href="#icon-placeholder"></use>
                    </svg>
                </div>`;
    }

    /**
     * Initialize carousel navigation
     */
    initializeCarousel() {
        const prevBtn = this.carousel.querySelector('[data-carousel-prev]');
        const nextBtn = this.carousel.querySelector('[data-carousel-next]');

        if(prevBtn) {
            prevBtn.addEventListener('click', () => this.slideToPrev());
        }

        if(nextBtn) {
            nextBtn.addEventListener('click', () => this.slideToNext());
        }

        if(this.options.autoplay) {
            this.startAutoplay();
        }
    }

    /**
     * Slide to previous item
     */
    slideToPrev() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.updateCarouselPosition();
        }
    }

    /**
     * Slide to next item
     */
    slideToNext() {
        if (this.currentIndex < this.recommendations.length - 1) {
            this.currentIndex++;
            this.updateCarouselPosition();
        }
    }

    /**
     * Update carousel visual position
     */
    updateCarouselPosition() {
        const track = this.carousel.querySelector('.carousel-track');
        const cardWidth = 250; // Assuming each card is 250px wide
        const offset = -(this.currentIndex * cardWidth);

        track.style.transform = `translateX(${offset}px)`;
        track.style.transition = `transform ${this.options.slideSpeed}ms ease-in-out`;

        this.updateIndicators();
    }

    /**
     * Register handlers for quick add buttons
     */
    registerQuickAddHandlers() {
        const buttons = this.carousel.querySelectorAll(this.options.quickAddButtonSelector);
        
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleQuickAdd(button);
            });
        });
    }

    /**
     * Handle Ajax add to cart
     */
    async handleQuickAdd(button) {
        const productId = button.dataset.productId;
        const buttonText = button.querySelector('.button-text');
        const buttonLoading = button.querySelector('.button-loading');

        try {
            // Show loading state
            button.disabled = true;
            buttonText.style.display = 'none';
            buttonLoading.style.display = 'inline-block';

            // Prepare form data
            const formData = new FormData();
            formData.append('lineItems[0][id]', productId);
            formData.append('lineItems[0][type]', 'product');
            formData.append('lineItems[0][referenceId]', productId);
            formData.append('lineItems[0][quantity]', '1');

            // Send Ajax request
            await this.httpClient.post(
                this.options.addToCartUrl,
                formData,
                () => {
                    // Success handler
                    this.showSuccessFeedback(button);

                    // Dispatch event for cart widget update
                    this.$emitter.publish('addToCart', { productId });
                }
            );
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.showErrorFeedback(button);
        } finally {
            // Reset button state
            button.disabled = false;
            buttonText.style.display = 'inline-block';
            buttonLoading.style.display = 'none';
        }
    }

    /**
     * Show success feedback after adding to cart
     */
    showSuccessFeedback(button) {
        const originalText = button.querySelector('.button-text').textContent;
        button.querySelector('.button-text').textContent = 'Added!';
        button.classList.add('btn-success');
        
        setTimeout(() => {
            button.querySelector('.button-text').textContent = originalText;
            button.classList.remove('btn-success');
        }, 2000);
    }

    /**
     * Show error feedback
     */
    showErrorFeedback(button) {
        button.classList.add('btn-danger');
        setTimeout(() => {
            button.classList.remove('btn-danger');
        }, 2000);
    }

    // State management helpers

    showLoading() {
        this.loadingEl.style.display = 'block';
        this.carousel.style.display = 'none';
        this.errorEl.style.display = 'none';
        this.emptyEl.style.display = 'none';
    }

    hideLoading() {
        this.loadingEl.style.display = 'none';
    }

    showError() {
        this.errorEl.style.display = 'block';
        this.emptyEl.style.display = 'none';
    }

    showEmpty() {
        this.loadingEl.style.display = 'none';
        this.emptyEl.style.display = 'block';
    }

    // Helper methods
    formatPrice(priceObj) {
        if (!priceObj) return '';

        // // Simple formatting - adjust for your locale
        // return `$${priceObj.unitPrice.toFixed(2)}`;

        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: priceObj.currency,
        }).format(priceObj.unitPrice);
    }

    createIndicators() {
        return this.recommendations
            .map((_, index) => `<span class="indicator ${index === 0 ? 'active' : ''}" data-index="${index}"></span>`)
            .join('');
    }

    updateIndicators() {
        const indicators = this.carousel.querySelectorAll('.indicator');
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === this.currentIndex);
        });
    }

    startAutoplay() {
        this.autoplayInterval = setInterval(() => {
            if (this.currentIndex < this.recommendations.length - 1) {
                this.slideToNext();
            } else {
                this.currentIndex = 0;
                this.updateCarouselPosition();
            }
        }, this.options.autoplaySpeed);
    }
}