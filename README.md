# Shopware Tutorial Olli

This is a demo version of [Shopware](https://www.shopware.com/)©, version 6.7,  **for learning purposes only**. No licenses granted, no warranties given.

## Setup

You'll need to be able to run PHP Symfony and have Docker running.

After cloning the repo go to the root folder and run

```bash
# teminal 1
docker compose up

# terminal 2
symfony serve

# storefront
open https://https://127.0.0.1:8000/

# administration
open https://127.0.0.1:8000/admin
# login: admin
# pw: shopware
```

## Documentation

This project includes comprehensive documentation in the `AI_DOCUMENTS/` folder:

- **[AI Coding Guide](AI_DOCUMENTS/AI_CODING_GUIDE.md)** - Coding standards and best practices for AI assistants
- **[Learning Resources](AI_DOCUMENTS/README.md)** - Complete onboarding documentation
- See the [AI_DOCUMENTS](AI_DOCUMENTS/) folder for all available guides

### For AI Assistants

When generating code for this project, **always follow the guidelines in [AI_DOCUMENTS/AI_CODING_GUIDE.md](AI_DOCUMENTS/AI_CODING_GUIDE.md)**. This ensures consistency with project standards and Shopware best practices.

### Code quality tooling

Experimenting with PhpStan (standalone as [phar](https://phpstan.org/user-guide/getting-started)).
Usage example:

```bash
php phpstan.phar analyze custom/plugins/LearningBundle/src/Command/ApplyDiscountCommand.php --memory-limit 120M 
```

Added [SonarQube trial](https://sonarcloud.io/summary/overall?id=schafeld_shopware-tutorial-olli&branch=main) for this tutorial project.


### Notes

#### Shopware Tutorials

If you're learning Shopware you'll find the [tutorial series](https://github.com/schafeld/shopware-tutorial-olli/tree/main/AI_DOCUMENTS/LEARNING_SHOPWARE) included in this repo helpful. Do not be fooled by the the concept of calling the lessons "Day 1" to "Day 7". Time estimates by AI are very unreliable and much too optimistic. If you are new to Shopware and its tech stack and you type the listings by hand in order to learn, the required time will be 2 to 3 times more than "a day". The AI time estimates for the exercises are ludicrous for a junior developer – just look at the included solutions. 😉

#### Plugin Shopware Order Data Export to Google API

There is a [prototype for a Shopware plugin "GotoWebinarGoogleSheetsExport"](https://github.com/schafeld/shopware-tutorial-olli/tree/main/custom/plugins/GotoWebinarGoogleSheetsExport) that allows eporting specified category order data to a Google Sheet (see [documentation](https://github.com/schafeld/shopware-tutorial-olli/tree/main/custom/plugins/GotoWebinarGoogleSheetsExport/docs) for details).
Use with caution! It works (on my machine 🤓) but hasn't been tested for potential safety or performance issues or data privacy concerns.
