import template from './sw-extension-config.html.twig';

const { Component } = Shopware;

/**
 * Extend the extension config page to add a link to the dashboard
 */
Component.override('sw-extension-config', {
    template,

    computed: {
        isGotoWebinarPlugin() {
            return this.$route.params.namespace === 'GotoWebinarGoogleSheetsExport';
        }
    },

    methods: {
        openDashboard() {
            this.$router.push({
                name: 'gotowebinar.sheets.dashboard'
            });
        }
    }
});
