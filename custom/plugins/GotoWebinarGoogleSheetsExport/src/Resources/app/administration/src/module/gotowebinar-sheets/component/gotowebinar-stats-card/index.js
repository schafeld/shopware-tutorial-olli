import template from './gotowebinar-stats-card.html.twig';
import './gotowebinar-stats-card.scss';

/**
 * Statistics card component showing export metrics
 */
export default {
    template,

    props: {
        stats: {
            type: Object,
            required: true
        },
        isLoading: {
            type: Boolean,
            default: false
        },
        isConfigured: {
            type: Boolean,
            default: false
        }
    },

    computed: {
        lastExportFormatted() {
            if (!this.stats.lastExport) {
                return this.$tc('gotowebinar-sheets.stats.never');
            }
            
            const date = new Date(this.stats.lastExport);
            return date.toLocaleString();
        },

        statusVariant() {
            if (!this.isConfigured) {
                return 'warning';
            }
            return this.stats.pendingExports > 0 ? 'info' : 'success';
        }
    }
};
