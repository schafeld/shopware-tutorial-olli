import './component';
import './config';
import './preview';

/**
 * Default sample jobs for initial element configuration.
 */
const defaultJobs = [
    {
        id: 1,
        title: 'Full Stack Entwickler Shopware 6 PHP JavaScript (m/w/d)',
        department: 'IT & E-Commerce',
        employmentType: 'Vollzeit',
        location: 'Ascheberg',
        workModel: 'Hybrid (3 Tage vor Ort, 2 Tage remote)',
        startDate: 'Ab sofort',
        description: 'Wir suchen für unser Team einen Full Stack Entwickler Shopware 6 PHP JavaScript (m/w/d).',
        sections: [
            {
                heading: 'Deine Aufgaben:',
                items: [
                    'Entwicklung und Weiterentwicklung unserer internationalen Webshops auf Basis von Shopware 6',
                    'Entwicklung und Anpassung von Shopware 6 Plugins in PHP',
                    'Umsetzung moderner, responsiver Oberflächen mit Twig, HTML, CSS und JavaScript'
                ]
            },
            {
                heading: 'Dein Profil:',
                items: [
                    'Abgeschlossene Ausbildung oder Studium im IT Umfeld',
                    'Gute PHP Kenntnisse und Verständnis für objektorientierte Entwicklung',
                    'Erfahrung mit Shopware 6 ist Voraussetzung'
                ]
            },
            {
                heading: 'Deine Vorteile:',
                items: [
                    'Flexible Arbeitszeiten und die Möglichkeit, mobil zu arbeiten',
                    '30 Tage Urlaub',
                    '13. Gehalt als Anerkennung für deinen Einsatz'
                ]
            }
        ]
    },
    {
        id: 2,
        title: 'Kaufmann/-frau im Einzelhandel (m/w/d)',
        department: 'Vertrieb',
        employmentType: 'Ausbildungsplatz',
        location: 'Ascheberg',
        workModel: null,
        startDate: 'August 2026',
        description: 'Starte deine Karriere im maritimen Einzelhandel!',
        sections: [
            {
                heading: 'Deine Aufgaben:',
                items: [
                    'Kundenberatung und Verkauf im Bereich Bootszubehör und Wassersportartikel',
                    'Warenpräsentation und Sortimentspflege'
                ]
            },
            {
                heading: 'Das bringst du mit:',
                items: [
                    'Guter Schulabschluss (Realschule oder höher)',
                    'Interesse an Handel, Verkauf und Kundenberatung'
                ]
            },
            {
                heading: 'Wir bieten:',
                items: [
                    'Qualifizierte Ausbildung in einem modernen Unternehmen',
                    'Übernahmemöglichkeiten nach der Ausbildung',
                    '30 Tage Urlaub pro Jahr'
                ]
            }
        ]
    }
];

/**
 * Register the CMS element for Job Offers
 */
Shopware.Service('cmsService').registerCmsElement({
    name: 'compass24-job-offers',
    label: 'Compass24 Stellenangebote',
    component: 'sw-cms-el-compass24-job-offers',
    configComponent: 'sw-cms-el-config-compass24-job-offers',
    previewComponent: 'sw-cms-el-preview-compass24-job-offers',
    defaultConfig: {
        headerTitle: {
            source: 'static',
            value: 'Aktuelle Stellenangebote'
        },
        headerSubtitle: {
            source: 'static',
            value: 'Finde deinen Platz im Compass24-Team – Festanstellungen und Ausbildungsplätze'
        },
        applicationEmail: {
            source: 'static',
            value: 'sekretariat@compass24.de'
        },
        jobs: {
            source: 'static',
            value: JSON.stringify(defaultJobs)
        }
    }
});
