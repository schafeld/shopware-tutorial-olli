// Import plugins
import ProductViewTrackerPlugin from './plugin/product-view-tracker.plugin';
import ProductComparePlugin from './plugin/product-compare.plugin';
import PriceFilterPlugin from './plugin/price-filter.plugin';

// Register plugins
const PluginManager = window.PluginManager;
PluginManager.register('ProductViewTracker', ProductViewTrackerPlugin, '[data-product-view-tracker]');
PluginManager.register('ProductCompare', ProductComparePlugin, '[data-product-compare]');
PluginManager.register('PriceFilter', PriceFilterPlugin, '[data-price-filter]');