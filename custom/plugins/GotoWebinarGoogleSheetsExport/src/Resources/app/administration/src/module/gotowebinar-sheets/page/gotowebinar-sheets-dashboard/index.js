import template from './gotowebinar-sheets-dashboard.html.twig';
import './gotowebinar-sheets-dashboard.scss';

const { Component, Mixin } = Shopware;

/**
 * Main dashboard page for GotoWebinar Sheets Export
 */
Component.register('gotowebinar-sheets-dashboard', {
    template,

    inject: ['systemConfigApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            stats: {
                totalExports: 0,
                pendingExports: 0,
                lastExport: null
            },
            isConfigured: false,
            config: {}
        };
    },

    computed: {
        lastExportFormatted() {
            if (!this.stats.lastExport) {
                return this.$tc('gotowebinar-sheets.stats.never');
            }
            
            const date = new Date(this.stats.lastExport);
            return date.toLocaleString();
        }
    },

    created() {
        this.loadData();
        this.loadConfiguration();
    },

    methods: {
        loadData() {
            this.isLoading = true;

            // Load statistics from API
            this.$http.get('/_action/gotowebinar-sheets/export/stats')
                .then((response) => {
                    if (response.data.success) {
                        this.stats = response.data.stats;
                    }
                })
                .catch((error) => {
                    this.createNotificationError({
                        message: error.message
                    });
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        loadConfiguration() {
            this.systemConfigApiService.getValues('GotoWebinarGoogleSheetsExport.config')
                .then((config) => {
                    this.config = config;
                    this.isConfigured = !!(
                        config['GotoWebinarGoogleSheetsExport.config.googleClientId'] &&
                        config['GotoWebinarGoogleSheetsExport.config.googleClientSecret'] &&
                        config['GotoWebinarGoogleSheetsExport.config.googleRefreshToken']
                    );
                });
        },

        onRefresh() {
            this.loadData();
        },

        onExportSuccess() {
            this.loadData();
        },

        onOAuthSuccess() {
            this.loadConfiguration();
            this.createNotificationSuccess({
                message: this.$tc('gotowebinar-sheets.oauth.successMessage')
            });
        },

        openConfiguration() {
            this.$router.push({
                name: 'sw.plugin.settings',
                params: {
                    namespace: 'GotoWebinarGoogleSheetsExport'
                }
            });
        }
    }
});
