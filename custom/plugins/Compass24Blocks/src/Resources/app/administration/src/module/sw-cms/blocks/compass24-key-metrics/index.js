import './component';
import './preview';

/**
 * Register the CMS block for the Key Metrics component
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'compass24-key-metrics',
    label: 'Compass24 Key Metrics',
    category: 'commerce',
    component: 'sw-cms-block-compass24-key-metrics',
    previewComponent: 'sw-cms-preview-compass24-key-metrics',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '0',
        marginRight: '0',
        sizingMode: 'full_width'
    },
    slots: {
        metrics: 'compass24-key-metrics'
    }
});
