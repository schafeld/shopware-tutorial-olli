/**
 * GotoWebinar Sheets Export Admin Module
 * 
 * Provides admin UI for managing Google Sheets exports
 */
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

// Register components using lazy loading (Shopware 6.7 pattern)
Shopware.Component.register('gotowebinar-sheets-dashboard', () => import('./page/gotowebinar-sheets-dashboard'));
Shopware.Component.register('gotowebinar-stats-card', () => import('./component/gotowebinar-stats-card'));
Shopware.Component.register('gotowebinar-export-button', () => import('./component/gotowebinar-export-button'));
Shopware.Component.register('gotowebinar-oauth-button', () => import('./component/gotowebinar-oauth-button'));
Shopware.Component.register('gotowebinar-export-list', () => import('./component/gotowebinar-export-list'));

Shopware.Module.register('gotowebinar-sheets', {
    type: 'plugin',
    name: 'GotoWebinarSheetsExport',
    title: 'gotowebinar-sheets.general.mainMenuItemGeneral',
    description: 'gotowebinar-sheets.general.descriptionTextModule',
    color: '#ff3d58',
    icon: 'regular-external-link',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        dashboard: {
            component: 'gotowebinar-sheets-dashboard',
            path: 'dashboard',
            meta: {
                parentPath: 'sw.settings.index.plugins'
            }
        }
    },

    settingsItem: {
        group: 'plugins',
        to: 'gotowebinar.sheets.dashboard',
        icon: 'regular-external-link',
        label: 'gotowebinar-sheets.general.mainMenuItemGeneral'
    }
});
