import template from './sw-cms-el-preview-compass24-key-metrics.html.twig';
import './sw-cms-el-preview-compass24-key-metrics.scss';

const { Component, Mixin } = Shopware;

/**
 * Preview component shown in the CMS editor
 */
Component.register('sw-cms-el-preview-compass24-key-metrics', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    computed: {
        metrics() {
            return [
                {
                    value: this.element.config.metric1Value.value,
                    label: this.element.config.metric1Label.value
                },
                {
                    value: this.element.config.metric2Value.value,
                    label: this.element.config.metric2Label.value
                },
                {
                    value: this.element.config.metric3Value.value,
                    label: this.element.config.metric3Label.value
                },
                {
                    value: this.element.config.metric4Value.value,
                    label: this.element.config.metric4Label.value
                },
                {
                    value: this.element.config.metric5Value.value,
                    label: this.element.config.metric5Label.value
                }
            ];
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
