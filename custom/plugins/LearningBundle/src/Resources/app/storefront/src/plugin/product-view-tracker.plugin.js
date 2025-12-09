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
         * 
         */
        trackingUrl: '/store-api/learning/product-view',
        trackingDelay: 2000
    };

    init() {
        // Get product id from data attribute
        this.productId = this.el.dataset.productId;

        if (!this.productId) {
            console.warn('ProductViewTrackerPlugin: No product ID found.');
            return;
        }

        // Initialize HTTP client
        this.httpClient = new HttpClient();

        // Track view after delay
        this.scheduleTracking();

        console.log(`ProductViewTracker Plugin initialized for product ID: ${this.productId}`);
    }

    scheduleTracking() {
        // Delay tracking to ensure it's a real view
        setTimeout(() => {
            this.trackView();
        }, this.options.trackingDelay);
    }

    trackView() {
        const url = `${this.options.trackingUrl}/${this.productId}`;

        this.httpClient.post(
            url,
            null,
            (response) => {
                console.log('Product view tracked successfully:', response);
            },
            (error) => {
                console.error('Error tracking product view:', error);
            }
        );
    }
}