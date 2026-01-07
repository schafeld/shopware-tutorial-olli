/**
 * GotoWebinar Sheets Export Admin Module
 * 
 * Provides admin UI for managing Google Sheets exports
 */
import './page/gotowebinar-sheets-dashboard';
import './component/gotowebinar-stats-card';
import './component/gotowebinar-export-button';
import './component/gotowebinar-oauth-button';
import './component/gotowebinar-export-list';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Module.register('gotowebinar-sheets', {
    type: 'plugin',
    name: 'GotoWebinarSheetsExport',
    title: 'gotowebinar-sheets.general.mainMenuItemGeneral',
    description: 'gotowebinar-sheets.general.descriptionTextModule',
    color: '#ff3d58',
    icon: 'default-action-share',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        dashboard: {
            component: 'gotowebinar-sheets-dashboard',
            path: 'dashboard',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    },

    navigation: [{
        id: 'gotowebinar-sheets',
        label: 'gotowebinar-sheets.general.mainMenuItemGeneral',
        color: '#ff3d58',
        path: 'gotowebinar.sheets.dashboard',
        icon: 'default-action-share',
        parent: 'sw-settings',
        position: 100
    }],

    settingsItem: [{
        group: 'plugins',
        to: 'gotowebinar.sheets.dashboard',
        icon: 'default-action-share',
        label: 'gotowebinar-sheets.general.mainMenuItemGeneral'
    }]
});
