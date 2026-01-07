import template from './gotowebinar-export-button.html.twig';

const { Component, Mixin } = Shopware;

/**
 * Manual export trigger button component
 */
Component.register('gotowebinar-export-button', {
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

            this.$http.post('/_action/gotowebinar-sheets/export/manual', {
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
});
