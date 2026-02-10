import template from './sw-cms-el-compass24-key-metrics.html.twig';

const { Component, Mixin } = Shopware;

/**
 * Main element component
 */
Component.register('sw-cms-el-compass24-key-metrics', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('compass24-key-metrics');
        }
    }
});
