import template from './sw-cms-el-config-compass24-job-offers.html.twig';
import './sw-cms-el-config-compass24-job-offers.scss';

const { Component, Mixin } = Shopware;

/**
 * Configuration component for managing job offers in the CMS element sidebar.
 *
 * Job data is stored as a JSON string in element.config.jobs.value.
 * This component parses the JSON into a local reactive array (localJobs),
 * provides a full CRUD editor, and serializes changes back to the config.
 */
Component.register('sw-cms-el-config-compass24-job-offers', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            localJobs: [],
            expandedJobIndex: -1
        };
    },

    computed: {
        headerTitle: {
            get() {
                return this.element.config.headerTitle.value;
            },
            set(value) {
                this.element.config.headerTitle.value = value;
            }
        },
        headerSubtitle: {
            get() {
                return this.element.config.headerSubtitle.value;
            },
            set(value) {
                this.element.config.headerSubtitle.value = value;
            }
        },
        applicationEmail: {
            get() {
                return this.element.config.applicationEmail.value;
            },
            set(value) {
                this.element.config.applicationEmail.value = value;
            }
        },

        departmentOptions() {
            return [
                { label: 'IT & E-Commerce', value: 'IT & E-Commerce' },
                { label: 'Marketing', value: 'Marketing' },
                { label: 'Vertrieb', value: 'Vertrieb' },
                { label: 'Logistik', value: 'Logistik' },
                { label: 'Kundenservice', value: 'Kundenservice' },
                { label: 'Verwaltung', value: 'Verwaltung' }
            ];
        },

        employmentTypeOptions() {
            return [
                { label: 'Vollzeit', value: 'Vollzeit' },
                { label: 'Teilzeit', value: 'Teilzeit' },
                { label: 'Ausbildungsplatz', value: 'Ausbildungsplatz' },
                { label: 'Praktikum', value: 'Praktikum' }
            ];
        }
    },

    watch: {
        localJobs: {
            deep: true,
            handler(newVal) {
                this.element.config.jobs.value = JSON.stringify(newVal);
            }
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('compass24-job-offers');
            try {
                this.localJobs = JSON.parse(this.element.config.jobs.value || '[]');
            } catch (e) {
                this.localJobs = [];
            }
        },

        toggleJob(index) {
            this.expandedJobIndex = this.expandedJobIndex === index ? -1 : index;
        },

        addJob() {
            const newId = this.localJobs.length > 0
                ? Math.max(...this.localJobs.map(j => j.id)) + 1
                : 1;

            this.localJobs.push({
                id: newId,
                title: '',
                department: '',
                employmentType: 'Vollzeit',
                location: 'Ascheberg',
                workModel: null,
                startDate: 'Ab sofort',
                description: '',
                sections: [
                    { heading: 'Deine Aufgaben:', items: [''] },
                    { heading: 'Dein Profil:', items: [''] },
                    { heading: 'Deine Vorteile:', items: [''] }
                ]
            });
            this.expandedJobIndex = this.localJobs.length - 1;
        },

        removeJob(index) {
            this.localJobs.splice(index, 1);
            this.expandedJobIndex = -1;
        },

        duplicateJob(index) {
            const original = this.localJobs[index];
            const newId = Math.max(...this.localJobs.map(j => j.id)) + 1;
            const copy = JSON.parse(JSON.stringify(original));
            copy.id = newId;
            copy.title = copy.title + ' (Kopie)';
            this.localJobs.splice(index + 1, 0, copy);
            this.expandedJobIndex = index + 1;
        },

        moveJobUp(index) {
            if (index > 0) {
                const job = this.localJobs.splice(index, 1)[0];
                this.localJobs.splice(index - 1, 0, job);
                this.expandedJobIndex = index - 1;
            }
        },

        moveJobDown(index) {
            if (index < this.localJobs.length - 1) {
                const job = this.localJobs.splice(index, 1)[0];
                this.localJobs.splice(index + 1, 0, job);
                this.expandedJobIndex = index + 1;
            }
        },

        addSection(jobIndex) {
            this.localJobs[jobIndex].sections.push({
                heading: '',
                items: ['']
            });
        },

        removeSection(jobIndex, sectionIndex) {
            this.localJobs[jobIndex].sections.splice(sectionIndex, 1);
        },

        addSectionItem(jobIndex, sectionIndex) {
            this.localJobs[jobIndex].sections[sectionIndex].items.push('');
        },

        removeSectionItem(jobIndex, sectionIndex, itemIndex) {
            this.localJobs[jobIndex].sections[sectionIndex].items.splice(itemIndex, 1);
        },

        updateSectionItem(jobIndex, sectionIndex, itemIndex, value) {
            this.localJobs[jobIndex].sections[sectionIndex].items[itemIndex] = value;
        }
    }
});
