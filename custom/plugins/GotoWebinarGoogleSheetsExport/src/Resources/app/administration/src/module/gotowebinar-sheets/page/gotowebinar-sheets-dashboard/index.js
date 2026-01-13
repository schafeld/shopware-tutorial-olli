import template from './gotowebinar-sheets-dashboard.html.twig';
import './gotowebinar-sheets-dashboard.scss';

const { Mixin, Application } = Shopware;

/**
 * Main dashboard page for GotoWebinar Sheets Export
 */
export default {
    template,

    inject: ['systemConfigApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoadingStats: false,
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
        httpClient() {
            return Application.getContainer('init').httpClient;
        },

        lastExportFormatted() {
            if (!this.stats.lastExport) {
                return this.$tc('gotowebinar-sheets.stats.never');
            }
            
            const date = new Date(this.stats.lastExport);
            return date.toLocaleString();
        }
    },

    created() {
        this.loadConfiguration();
        this.loadData();
    },

    methods: {
        loadData() {
            this.isLoadingStats = true;

            // Load statistics from API
            this.httpClient.get('/_action/gotowebinar-sheets/export/stats')
                .then((response) => {
                    if (response.data.success) {
                        this.stats = response.data.stats;
                    }
                })
                .catch(() => {
                    // Silently fail - stats are not critical for initial display
                    // The warning about not being configured will show instead
                })
                .finally(() => {
                    this.isLoadingStats = false;
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
                name: 'sw.extension.config',
                params: {
                    namespace: 'GotoWebinarGoogleSheetsExport'
                }
            });
        }
    }
};

