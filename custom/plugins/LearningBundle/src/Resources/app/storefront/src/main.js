// Import plugins
// Disabled: ProductViewTrackerPlugin - API route not implemented
// import ProductViewTrackerPlugin from './plugin/product-view-tracker.plugin';
import ProductComparePlugin from './plugin/product-compare.plugin';
import PriceFilterPlugin from './plugin/price-filter.plugin';
import GtmTrackingPlugin from './plugin/gtm/gtm-tracking.plugin';

// Register plugins
const PluginManager = window.PluginManager;
// Disabled: PluginManager.register('ProductViewTracker', ProductViewTrackerPlugin, '[data-product-view-tracker]');
PluginManager.register('ProductCompare', ProductComparePlugin, '[data-product-compare]');
PluginManager.register('PriceFilter', PriceFilterPlugin, '[data-price-filter]');

// GTM Tracking Plugin - auto-initialized on body
PluginManager.register('GtmTracking', GtmTrackingPlugin, '[data-gtm-tracking]');