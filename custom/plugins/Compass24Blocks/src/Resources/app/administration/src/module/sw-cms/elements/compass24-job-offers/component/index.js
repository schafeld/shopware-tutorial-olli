import template from './sw-cms-el-compass24-job-offers.html.twig';

const { Component, Mixin } = Shopware;

/**
 * Main element component for CMS editor live preview
 */
Component.register('sw-cms-el-compass24-job-offers', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    computed: {
        jobCount() {
            try {
                const jobs = JSON.parse(this.element.config.jobs.value || '[]');
                return jobs.length;
            } catch {
                return 0;
            }
        },

        headerTitle() {
            return this.element.config.headerTitle.value || 'Stellenangebote';
        },

        previewJobs() {
            try {
                return JSON.parse(this.element.config.jobs.value || '[]').slice(0, 3);
            } catch {
                return [];
            }
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('compass24-job-offers');
        }
    }
});
