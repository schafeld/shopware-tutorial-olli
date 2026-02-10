import './component';
import './config';
import './preview';

/**
 * Register the CMS element
 */
Shopware.Service('cmsService').registerCmsElement({
    name: 'compass24-key-metrics',
    label: 'Compass24 Key Metrics',
    component: 'sw-cms-el-compass24-key-metrics',
    configComponent: 'sw-cms-el-config-compass24-key-metrics',
    previewComponent: 'sw-cms-el-preview-compass24-key-metrics',
    defaultConfig: {
        metric1Value: {
            source: 'static',
            value: '1979'
        },
        metric1Label: {
            source: 'static',
            value: 'Gründungsjahr'
        },
        metric2Value: {
            source: 'static',
            value: '42.000+'
        },
        metric2Label: {
            source: 'static',
            value: 'Artikel'
        },
        metric3Value: {
            source: 'static',
            value: '400+'
        },
        metric3Label: {
            source: 'static',
            value: 'Seiten Katalog'
        },
        metric4Value: {
            source: 'static',
            value: '5.000'
        },
        metric4Label: {
            source: 'static',
            value: 'Pakete täglich'
        },
        metric5Value: {
            source: 'static',
            value: '11'
        },
        metric5Label: {
            source: 'static',
            value: 'Länder weltweit'
        }
    }
});
