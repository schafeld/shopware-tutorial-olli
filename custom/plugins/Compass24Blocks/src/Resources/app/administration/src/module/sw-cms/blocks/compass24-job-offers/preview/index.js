import template from './sw-cms-preview-compass24-job-offers.html.twig';
import './sw-cms-preview-compass24-job-offers.scss';

const { Component } = Shopware;

/**
 * Preview component shown in the CMS block selection sidebar
 */
Component.register('sw-cms-preview-compass24-job-offers', {
    template
});
