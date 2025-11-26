import Plugin from 'src/plugin-system/plugin.class';

/**
 * Product Comparison Plugin
 * Allows users to compare products side-by-side
 */
export default class ProductComparePlugin extends Plugin {

    static options = {
        storageKey: 'learning_product_compare',
        maxProducts: 4,
        comparePageUrl: '/compare'
    };

    init() {
        this.storage = window.localStorage;
        this.compareProducts = this.loadCompareList();

        this._registerEvents();
        this.updateUI();

        console.log('ProductCompare Plugin initialized with', this.compareProducts.length, 'products');
    }

    _registerEvents() {
        // Listen for add to compare button clicks
        this.el.addEventListener('click', this.onToggleCompare.bind(this));

        // Listen to custom events from other instances
        document.addEventListener('compareListUpdated', this.onCompareListUpdated.bind(this));
    }

    onToggleCompare(event){
        event.preventDefault();

        const productId = this.el.dataset.productId;
        const productName = this.el.dataset.productName;
        const productImage = this.el.dataset.productImage;
        const productPrice = this.el.dataset.productPrice;

        if(this.isInCompareList(productId)){
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
        if(this.compareProducts.length >= this.options.maxProducts) {
            this.showNotification(`Maximum ${this.options.maxProducts} products can be compared.`, 'warning');
            return;
        }

        if (!this.isInCompareList(product.id)) {
            this.compareProducts.push(product);
            this.saveCompareList();
            this.showNotification(`${product.name} added to comparison`, 'success');

            // Notify other plugin instances
            this.broadcastUpdate();
            
            // Show modal if we have products to compare
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

    removeFromCompare(productId){
        this.compareProducts = this.compareProducts.filter(p => p.id !== productId);
        this.saveCompareList();
        this.showNotification(`Product removed from comparison`, 'info');

        // Notify other plugin instances
        this.broadcastUpdate();
    }

    isInCompareList(productId){
        return this.compareProducts.some(p => p.id === productId);
    }

    loadCompareList(){
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
        this.compareProducts = event.detail.products || [];
        this.updateUI();
    }

    updateUI() {
        const productId = this.el.dataset.productId;

        if(this.isInCompareList(productId)) {
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
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 2500);
    }
}