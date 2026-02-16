import './component';
import './preview';

/**
 * Register the CMS block for the Job Offers component
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'compass24-job-offers',
    label: 'Compass24 Stellenangebote',
    category: 'commerce',
    component: 'sw-cms-block-compass24-job-offers',
    previewComponent: 'sw-cms-preview-compass24-job-offers',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '0',
        marginRight: '0',
        sizingMode: 'full_width'
    },
    slots: {
        jobOffers: 'compass24-job-offers'
    }
});
