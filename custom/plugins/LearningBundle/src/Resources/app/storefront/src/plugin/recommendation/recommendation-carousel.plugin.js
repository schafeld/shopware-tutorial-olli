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
        recommendationsUrl: '/store-api/recommendation',
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
        console.log('[RecommendationCarousel] Plugin initialized');
        this.httpClient = new HttpClient();
        this.productId = this.el.dataset.productId;
        console.log('[RecommendationCarousel] Product ID:', this.productId);

        this.carousel = this.el.querySelector(this.options.carouselSelector);
        this.loadingEl = this.el.querySelector(this.options.loadingSelector);
        this.errorEl = this.el.querySelector(this.options.errorSelector);
        this.emptyEl = this.el.querySelector(this.options.emptySelector);

        console.log('[RecommendationCarousel] Elements found:', {
            carousel: !!this.carousel,
            loading: !!this.loadingEl,
            error: !!this.errorEl,
            empty: !!this.emptyEl
        });

        this.currentIndex = 0;
        this.recommendations = [];

        this.loadRecommendations();
    }

    /**
     * Load recommendations from API
     */
    async loadRecommendations() {
        try {
            console.log('[RecommendationCarousel] Starting to load recommendations...');
            this.showLoading();

            const url = `${this.options.recommendationsUrl}/${this.productId}?limit=${this.options.limit}`;
            console.log('[RecommendationCarousel] API URL:', url);

            const accessKey = window.salesChannelAccessKey || this.el.dataset.accessKey || '';
            console.log('[RecommendationCarousel] Access key available:', !!accessKey);

            // Create a configured XMLHttpRequest with Store API authentication
            const request = new XMLHttpRequest();
            request.open('GET', url);
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            request.setRequestHeader('Content-Type', 'application/json');
            
            if (accessKey) {
                request.setRequestHeader('sw-access-key', accessKey);
            }

            request.addEventListener('loadend', () => {
                console.log('[RecommendationCarousel] Response status:', request.status);
                console.log('[RecommendationCarousel] Raw response:', request.responseText);
                
                if (request.status === 200) {
                    try {
                        const responseData = JSON.parse(request.responseText);
                        console.log('[RecommendationCarousel] Parsed response:', responseData);

                        if (responseData.success && responseData.data && responseData.data.length > 0) {
                            console.log('[RecommendationCarousel] Found', responseData.data.length, 'recommendations');
                            this.recommendations = responseData.data;
                            this.renderRecommendations();
                            this.initializeCarousel();
                            this.registerQuickAddHandlers();
                        } else {
                            console.log('[RecommendationCarousel] No recommendations found');
                            this.showEmpty();
                        }
                    } catch (parseError) {
                        console.error('[RecommendationCarousel] Error parsing response:', parseError);
                        this.showError();
                    }
                } else {
                    console.error('[RecommendationCarousel] HTTP error:', request.status);
                    this.showError();
                }
            });

            request.send();

        } catch (error) {
            console.error('[RecommendationCarousel] Error loading recommendations:', error);
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
            <button class="carousel-nav carousel-nav-prev" data-carousel-prev aria-label="Previous">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button class="carousel-nav carousel-nav-next" data-carousel-next aria-label="Next">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
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
        // Handle both nested product structure and flat API response
        const product = recommendation.product || recommendation;
        const score = recommendation.affinity_score || recommendation.affinityScore || 0;
        const productId = product.id;
        const productName = product.translated?.name || product.name;
        const coverUrl = product.cover?.media?.url || product.cover?.url;
        const price = product.calculatedPrice || product.price;

        return `
            <div class="recommendation-card"
                data-index="${index}"
                data-product-id="${productId}">

                <a href="/detail/${productId}" class="recommendation-card-link">
                    ${this.createProductImage(product)}
                    ${score ? `<span class="recommendation-badge">${Math.round(score * 100)}% Match</span>` : ''}
                </a>

                <div class="recommendation-card-body">
                    <a href="/detail/${productId}" class="recommendation-card-title">
                        ${productName}
                    </a>

                    <div class="recommendation-card-price">
                        <span class="price">${this.formatPrice(price)}</span>
                    </div>

                    <div class="recommendation-card-actions">
                        <button type="button"
                            class="btn btn-sm btn-primary recommendation-quick-add"
                            data-product-id="${productId}"
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
        const coverUrl = product.cover?.media?.url || product.cover?.url;
        const productName = product.translated?.name || product.name;
        
        if (coverUrl) {
            return `<img src="${coverUrl}"
                alt="${productName}"
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
        this.prevBtn = this.carousel.querySelector('[data-carousel-prev]');
        this.nextBtn = this.carousel.querySelector('[data-carousel-next]');
        this.track = this.carousel.querySelector('.carousel-track');

        if(this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.slideToPrev());
        }

        if(this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.slideToNext());
        }

        // Initialize button states
        this.updateNavigationButtons();

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
        if (!this.track) return;
        
        const cardWidth = 250; // Card width in pixels
        const gap = 24; // Gap between cards (1.5rem = 24px)
        const offset = -(this.currentIndex * (cardWidth + gap));

        this.track.style.transform = `translateX(${offset}px)`;
        this.track.style.transition = `transform ${this.options.slideSpeed}ms ease-in-out`;

        this.updateIndicators();
        this.updateNavigationButtons();
    }

    /**
     * Update navigation button states (disable at boundaries)
     */
    updateNavigationButtons() {
        if (this.prevBtn) {
            if (this.currentIndex === 0) {
                this.prevBtn.disabled = true;
                this.prevBtn.style.opacity = '0.5';
                this.prevBtn.style.cursor = 'not-allowed';
            } else {
                this.prevBtn.disabled = false;
                this.prevBtn.style.opacity = '1';
                this.prevBtn.style.cursor = 'pointer';
            }
        }

        if (this.nextBtn) {
            if (this.currentIndex >= this.recommendations.length - 1) {
                this.nextBtn.disabled = true;
                this.nextBtn.style.opacity = '0.5';
                this.nextBtn.style.cursor = 'not-allowed';
            } else {
                this.nextBtn.disabled = false;
                this.nextBtn.style.opacity = '1';
                this.nextBtn.style.cursor = 'pointer';
            }
        }
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

        // Handle different price structures
        const price = priceObj.unitPrice || priceObj.gross;
        const currency = priceObj.currency || 'EUR';

        if (!price) return '';

        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: currency,
        }).format(price);
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