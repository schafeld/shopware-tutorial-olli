import template from './gotowebinar-export-list.html.twig';
import './gotowebinar-export-list.scss';

const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;

/**
 * Export log viewer component
 */
export default {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            exports: [],
            isLoading: false,
            total: 0,
            page: 1,
            limit: 25
        };
    },

    computed: {
        repository() {
            return this.repositoryFactory.create('gotowebinar_order_export');
        },

        columns() {
            return [
                {
                    property: 'exportedAt',
                    label: this.$tc('gotowebinar-sheets.exportList.columnExportedAt'),
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'orderNumber',
                    label: this.$tc('gotowebinar-sheets.exportList.columnOrderNumber'),
                    allowResize: true
                },
                {
                    property: 'productNumber',
                    label: this.$tc('gotowebinar-sheets.exportList.columnProductNumber'),
                    allowResize: true
                },
                {
                    property: 'customerName',
                    label: this.$tc('gotowebinar-sheets.exportList.columnCustomerName'),
                    allowResize: true
                },
                {
                    property: 'customerEmail',
                    label: this.$tc('gotowebinar-sheets.exportList.columnEmail'),
                    allowResize: true
                },
                {
                    property: 'exportStatus',
                    label: this.$tc('gotowebinar-sheets.exportList.columnStatus'),
                    allowResize: true
                }
            ];
        },

        showingEntriesText() {
            return this.$tc('gotowebinar-sheets.exportList.showingEntries', 0, {
                count: this.exports.length,
                total: this.total
            });
        }
    },

    created() {
        this.loadExports();
    },

    methods: {
        loadExports() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            this.repository.search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.exports = result;
                    this.total = result.total;
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

        onRefresh() {
            this.loadExports();
        },

        onDownloadCsv() {
            window.open('/_action/gotowebinar-sheets/export/csv?limit=100', '_blank');
        },

        onPageChange({ page, limit }) {
            this.page = page;
            this.limit = limit;
            this.loadExports();
        },

        getStatusVariant(status) {
            const variants = {
                pending: 'info',
                success: 'success',
                failed: 'danger'
            };
            return variants[status] || 'neutral';
        },

        getStatusLabel(status) {
            const key = `gotowebinar-sheets.exportList.status${status.charAt(0).toUpperCase()}${status.slice(1)}`;
            return this.$tc(key);
        },

        getCustomerName(item) {
            return `${item.customerFirstName} ${item.customerLastName}`;
        },

        formatDate(date) {
            if (!date) {
                return '-';
            }
            return new Date(date).toLocaleString();
        }
    }
};
