// Import all necessary Storefront plugins
import MyCustomThemePlugin from './my-custom-theme/my-custom-theme.plugin';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;

// Register plugin with the correct selector
PluginManager.register('MyCustomThemePlugin', MyCustomThemePlugin, '[data-my-custom-theme-plugin]');