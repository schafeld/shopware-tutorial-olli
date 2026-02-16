# Reusable Shopware CMS Block "Job Offers"

This folder contains feature requests and design documents for the creation of reusable Shopware CMS blocks.

## General Task

A specified HTML template file containing a UI widget – composed of HTML, CSS, and Javascript (and an inlined version of [Petite Vue](https://github.com/vuejs/petite-vue)) – needs to be reimplemented as a Shopware extension with an user interface to be inserted as reusable CMS block within this project. The reusable CMS Block needs to follow Shopware standards (using the proper file locations and names, integrate with Twig templates, transpose CSS to SCSS, etc.)

Any individual CMS Block or Shopware extension needs to be documented in its extension/plugin folder.
Text and images found in the HTML widget template file should be made editable through Shopware.
The Json in the HTML is only a mock up for actual job offers and apprenticeships that in the extension should be maintained in a proper Shopware database.

It should be well documented how to install and configure the extension and its CMS Block in Shopware, i.e.how to use it as an editor.

## Features
- The UI (including accordions, pagination, and filters) should be taken from the template
- The extension should be able to handle up to 100 jobs or apprenticeships
- The job offers need to be editable and deletable in a Shopware dashboard.
- The job offers should also be imported or exported as CSV
- This extension should be easy to install in other Shopware instances

## Documentation

Relevant Shopware and framework documentation:

- [Shopping Experiences (CMS)](https://developer.shopware.com/docs/concepts/commerce/content/shopping-experiences-cms.html)
- [Add CMS Blocks](https://developer.shopware.com/docs/guides/plugins/plugins/content/cms/add-cms-block.html)
- [Create Blocks (CMS)](https://developer.shopware.com/frontends/getting-started/cms/create-blocks.html)
- [Petite Vue](https://github.com/vuejs/petite-vue)