import template from './sw-cms-el-config-compass24-key-metrics.html.twig';
import './sw-cms-el-config-compass24-key-metrics.scss';

const { Component, Mixin } = Shopware;

/**
 * Configuration component for the element
 */
Component.register('sw-cms-el-config-compass24-key-metrics', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    computed: {
        metric1Value: {
            get() {
                return this.element.config.metric1Value.value;
            },
            set(value) {
                this.element.config.metric1Value.value = value;
            }
        },
        metric1Label: {
            get() {
                return this.element.config.metric1Label.value;
            },
            set(value) {
                this.element.config.metric1Label.value = value;
            }
        },
        metric2Value: {
            get() {
                return this.element.config.metric2Value.value;
            },
            set(value) {
                this.element.config.metric2Value.value = value;
            }
        },
        metric2Label: {
            get() {
                return this.element.config.metric2Label.value;
            },
            set(value) {
                this.element.config.metric2Label.value = value;
            }
        },
        metric3Value: {
            get() {
                return this.element.config.metric3Value.value;
            },
            set(value) {
                this.element.config.metric3Value.value = value;
            }
        },
        metric3Label: {
            get() {
                return this.element.config.metric3Label.value;
            },
            set(value) {
                this.element.config.metric3Label.value = value;
            }
        },
        metric4Value: {
            get() {
                return this.element.config.metric4Value.value;
            },
            set(value) {
                this.element.config.metric4Value.value = value;
            }
        },
        metric4Label: {
            get() {
                return this.element.config.metric4Label.value;
            },
            set(value) {
                this.element.config.metric4Label.value = value;
            }
        },
        metric5Value: {
            get() {
                return this.element.config.metric5Value.value;
            },
            set(value) {
                this.element.config.metric5Value.value = value;
            }
        },
        metric5Label: {
            get() {
                return this.element.config.metric5Label.value;
            },
            set(value) {
                this.element.config.metric5Label.value = value;
            }
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('compass24-key-metrics');
        }
    }
});
