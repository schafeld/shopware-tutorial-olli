# Shopware 6 Getting Started Guide

## Welcome to Shopware Development! 🚀

This document will help you get started with your Shopware 6.7.3.1 project as a new developer.

## Project Overview
- **Shopware Version**: 6.7.3.1
- **Environment**: Development (based on your .env)
- **Database**: MySQL (localhost)
- **URL**: http://127.0.0.1:8000

## Essential Concepts to Understand

### 1. Shopware Architecture
Shopware 6 is built on modern technologies:
- **Symfony Framework**: The foundation (dependency injection, routing, etc.)
- **Doctrine ORM**: Database abstraction layer
- **Twig Templates**: Templating engine
- **Vue.js**: Administration panel frontend
- **SCSS/CSS**: Storefront styling

### 2. Key Directories in Your Project
```
├── src/                    # Your custom PHP code (Controllers, Services)
├── custom/
│   ├── plugins/           # Custom plugins
│   ├── apps/              # Shopware Apps
│   └── static-plugins/    # Static plugins
├── config/                # Configuration files
├── public/                # Web-accessible files
├── var/                   # Cache, logs, compiled files
└── vendor/                # Composer dependencies
```

## Your First Steps

### 1. Understanding the Environment
Your project is configured for local development:
- Database: `mysql://root:root@localhost/shopware`
- App URL: `http://127.0.0.1:8000`
- Environment: Production mode (`APP_ENV=prod`)

**💡 Tip**: For development, consider changing `APP_ENV=dev` in your `.env` file for better debugging.

### 2. Essential Commands
```bash
# Start the development server
symfony serve

# Or use PHP built-in server
php -S 127.0.0.1:8000 -t public

# Clear cache
bin/console cache:clear

# Install/update plugins
bin/console plugin:refresh
bin/console plugin:install --activate PluginName

# Build administration (if customizing admin)
./bin/build-administration.sh

# Build storefront (if customizing frontend)
./bin/build-storefront.sh

# Watch for changes during development
./bin/watch-administration.sh  # For admin changes
./bin/watch-storefront.sh     # For storefront changes
```

### 3. Development Workflow
1. **Planning**: Understand requirements
2. **Plugin Creation**: Use `bin/console plugin:create` for new plugins
3. **Development**: Write code in `custom/plugins/YourPlugin/`
4. **Testing**: Test functionality
5. **Building**: Build assets if needed
6. **Documentation**: Document your changes

## Common Development Patterns

### Creating a Controller
Controllers go in `src/Controller/` and extend Shopware's base controller:

```php
<?php declare(strict_types=1);

namespace App\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class MyCustomController extends StorefrontController
{
    /**
     * @Route("/my-custom-page", name="frontend.my.custom.page", methods={"GET"})
     */
    public function myCustomPage(): Response
    {
        return $this->renderStorefront('@MyPlugin/storefront/page/my-custom-page.html.twig');
    }
}
```

## Next Steps
1. Read the Plugin Development Guide (`02_PLUGIN_DEVELOPMENT.md`)
2. Check out the Command Cheat Sheet (`03_COMMAND_CHEATSHEET.md`)
3. Learn about Shopware's data structure (`04_DATA_STRUCTURE_GUIDE.md`)

## Helpful Resources
- [Official Shopware Docs](https://developer.shopware.com/)
- [Shopware GitHub](https://github.com/shopware/platform)
- [Community Forums](https://forum.shopware.com/)

**Remember**: Shopware development is powerful but has a learning curve. Don't hesitate to experiment and break things in your development environment!