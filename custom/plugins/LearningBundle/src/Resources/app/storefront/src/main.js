// Import plugins
import ProductViewTrackerPlugin from './plugin/product-view-tracker.plugin';

// Register plugins
const PluginManager = window.PluginManager;
PluginManager.register('ProductViewTracker', ProductViewTrackerPlugin, '[data-product-view-tracker]');