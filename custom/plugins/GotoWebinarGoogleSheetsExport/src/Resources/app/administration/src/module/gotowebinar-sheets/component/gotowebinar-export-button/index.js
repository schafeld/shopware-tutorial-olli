import template from './gotowebinar-export-button.html.twig';

const { Mixin, Application } = Shopware;

/**
 * Manual export trigger button component
 */
export default {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        pendingCount: {
            type: Number,
            default: 0
        }
    },

    data() {
        return {
            isExporting: false,
            showModal: false,
            limit: 50
        };
    },

    computed: {
        httpClient() {
            return Application.getContainer('init').httpClient;
        },
        
        buttonLabel() {
            if (this.isExporting) {
                return this.$tc('gotowebinar-sheets.export.buttonLabelLoading');
            }
            return this.$tc('gotowebinar-sheets.export.buttonLabel');
        },

        isDisabled() {
            return this.isExporting || this.pendingCount === 0;
        }
    },

    methods: {
        onButtonClick() {
            if (this.pendingCount === 0) {
                this.createNotificationInfo({
                    message: this.$tc('gotowebinar-sheets.export.noPendingExports')
                });
                return;
            }

            this.showModal = true;
        },

        onConfirmExport() {
            this.showModal = false;
            this.triggerExport();
        },

        onCancelExport() {
            this.showModal = false;
        },

        triggerExport() {
            this.isExporting = true;

            this.httpClient.post('/_action/gotowebinar-sheets/export/manual', {
                limit: this.limit
            })
                .then((response) => {
                    if (response.data.success) {
                        this.createNotificationSuccess({
                            message: this.$tc(
                                'gotowebinar-sheets.export.successMessage',
                                response.data.exported,
                                { count: response.data.exported }
                            )
                        });
                        this.$emit('success', response.data);
                    } else {
                        this.createNotificationError({
                            message: this.$tc('gotowebinar-sheets.export.errorMessage', 0, {
                                message: response.data.message
                            })
                        });
                    }
                })
                .catch((error) => {
                    this.createNotificationError({
                        message: this.$tc('gotowebinar-sheets.export.errorMessage', 0, {
                            message: error.message
                        })
                    });
                })
                .finally(() => {
                    this.isExporting = false;
                });
        }
    }
};
