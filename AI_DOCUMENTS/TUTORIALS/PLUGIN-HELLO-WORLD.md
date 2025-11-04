# Shopware 6 HelloWorld Plugin Tutorial

## Overview 🚀

This tutorial will guide you through creating your first Shopware 6 plugin called **"Olli's HelloWorld Plugin"**. 

**Important naming explanation:**
- **Plugin folder/name**: `MyFirstPlugin` (this is what Shopware creates)
- **Namespace**: `OllisPlugin` (this is like your company/developer namespace)  
- **Display name**: "Olli's HelloWorld Plugin" (this is what users see)

This HelloWorld plugin demonstrates core Shopware concepts including:
- Plugin structure and setup
- Working with Product entities  
- Creating custom controllers and routes
- Building storefront pages
- Service injection and repository usage
- **Plugin configuration system**

## What We'll Build

A complete HelloWorld plugin that:
1. Displays a custom "Hello World" page in the storefront at `/hello-world`
2. Shows a list of products from your shop with images and details
3. **Includes configurable text** that can be changed in the admin panel
4. Demonstrates proper Shopware development patterns
5. Includes proper service configuration and lifecycle management

## Prerequisites ✅

- Shopware 6.7.3.1 development environment running
- Basic PHP and Twig knowledge
- Terminal/command line access

## ⚠️ IMPORTANT WARNINGS

**Before You Start**: The Shopware plugin generator can create problematic files that break core functionality. This tutorial includes mandatory cleanup steps to prevent breaking your shop.

### Common Pitfalls to Avoid:
1. **Never override core entities** (ProductDefinition, CategoryDefinition, etc.)
2. **Always clean up generated Core/ directories** that contain entity overrides
3. **Remove problematic service registrations** for core entities
4. **Test both your plugin AND core functionality** after installation

### Signs Your Plugin Broke Core Functionality:
- Product/category pages show 500 errors (white screen with error message)
- Error: "Definition for entity 'ProductDefinition' does not exist"
- Core routes like `/Clothing/` stop working (show 404 or 500 errors)
- Shopware's database system shows massive errors for core entities

---

## Step 1: Plugin Generation (COMPLETED ✅)

You've already completed this step:

```bash
bin/console plugin:create OllisPlugin
```

**Entity chosen**: `Product`  
**Generator options**: All defaults accepted

This created the basic plugin structure at `custom/plugins/MyFirstPlugin/` with namespace `OllisPlugin`

> ⚠️ **CRITICAL WARNING**: The Shopware plugin generator often creates overly complex examples that can **break core functionality**. After generation, you may need to remove unnecessary files that override core entities. See the "Cleanup Generated Files" section below.

---

## Step 2: Cleanup Generated Files (CRITICAL 🚨)

**IMPORTANT**: The plugin generator may have created files that override Shopware's core entities, which can break your entire shop. You MUST remove these problematic files before proceeding.

### Remove Problematic Core Entity Overrides

```bash
# Remove any Core entity definitions that override Shopware's built-in entities
rm -rf custom/plugins/MyFirstPlugin/src/Core/

# This removes files like ProductDefinition.php that would break product functionality
```

### Clean Up services.xml

Check `src/Resources/config/services.xml` and remove any lines that register core entity definitions:

```xml
<!-- REMOVE lines like this that override core entities: -->
<service id="OllisPlugin\Core\Content\Product\ProductDefinition">
    <tag name="shopware.entity.definition" entity="product" />
</service>
```

### Clean Up routes.xml

Check `src/Resources/config/routes.xml` and remove references to deleted Core files:

```xml
<!-- REMOVE this line if it exists: -->
<import resource="../../Core/**/*Route.php" type="attribute" />
```

### Update Plugin After Cleanup

```bash
bin/console plugin:update MyFirstPlugin
bin/console cache:clear
```

---

## Step 3: Explore Generated Structure

Let's examine what Shopware generated for you:

```
custom/plugins/MyFirstPlugin/
├── composer.json                 # Plugin metadata
└── src/
    ├── MyFirstPlugin.php        # Main plugin class (namespace: OllisPlugin)
    ├── Service/
    │   └── ProductService.php   # Service to work with products
    └── Resources/
        └── config/
            └── services.xml     # Dependency injection configuration
```

---

## Step 4: Create Your First Controller

We'll create a HelloWorld controller that displays products:

### File: `src/Storefront/Controller/HelloWorldController.php`

```php
<?php declare(strict_types=1);

namespace OllisPlugin\Storefront\Controller;

use OllisPlugin\Service\ProductService;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]
class HelloWorldController extends StorefrontController
{
    private ProductService $productService;
    private SystemConfigService $systemConfigService;

    public function __construct(
        ProductService $productService,
        SystemConfigService $systemConfigService
    ) {
        $this->productService = $productService;
        $this->systemConfigService = $systemConfigService;
    }

    #[Route(
        path: '/hello-world',
        name: 'frontend.hello.world',
        methods: ['GET']
    )]
    public function helloWorld(Request $request, SalesChannelContext $context): Response
    {
        // Get some products to display
        $products = $this->productService->getActiveProducts($context->getContext(), 5);
        
        // Get the configurable text from system configuration
        $configText = $this->systemConfigService->get(
            'MyFirstPlugin.config.textField',
            $context->getSalesChannel()->getId()
        );
        
        return $this->renderStorefront('@MyFirstPlugin/storefront/page/hello-world.html.twig', [
            'message' => 'Hello World from Olli\'s Plugin!',
            'products' => $products,
            'totalProducts' => count($products),
            'configText' => $configText ?? 'This plugin demonstrates working with Product entities.'
        ]);
    }
}
```

---

## Step 5: Enhance the ProductService

Update the generated ProductService to be more useful:

### File: `src/Service/ProductService.php`

```php
<?php declare(strict_types=1);

namespace OllisPlugin\Service;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class ProductService
{
    private EntityRepository $productRepository;

    public function __construct(EntityRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getActiveProducts(Context $context, int $limit = 10): ProductCollection
    {
        $criteria = new Criteria();
        
        // Only get active products
        $criteria->addFilter(new EqualsFilter('active', true));
        
        // Load associations we need
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('cover.media');
        
        // Sort by name
        $criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
        
        // Limit results
        $criteria->setLimit($limit);

        $result = $this->productRepository->search($criteria, $context);
        
        return $result->getEntities();
    }

    public function getProductCount(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        
        $result = $this->productRepository->search($criteria, $context);
        
        return $result->getTotal();
    }
}
```

---

## Step 6: Update Service Configuration

Update the services.xml to register your new controller:

### File: `src/Resources/config/services.xml`

```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services 
           http://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>
        <!-- Product Service -->
        <service id="OllisPlugin\Service\ProductService">
            <argument type="service" id="product.repository"/>
        </service>

        <!-- HelloWorld Controller -->
        <service id="OllisPlugin\Storefront\Controller\HelloWorldController" public="true">
            <argument type="service" id="OllisPlugin\Service\ProductService"/>
            <argument type="service" id="Shopware\Core\System\SystemConfig\SystemConfigService"/>
            <call method="setContainer">
                <argument type="service" id="service_container"/>
            </call>
        </service>
    </services>
</container>
```

---

## Step 7: Add Plugin Configuration

Let's add a configuration option that allows admins to customize text in our plugin.

### File: `src/Resources/config/config.xml`

Create this new file to define configuration options:

```xml
<?xml version="1.0" encoding="UTF-8"?>

<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/shopware/trunk/src/Core/System/SystemConfig/Schema/config.xsd">

    <card>
        <title>HelloWorld Plugin Configuration</title>

        <input-field type="text">
            <name>textField</name>
            <label>Hero section text</label>
            <helpText>This text will appear in the hero section of the HelloWorld page</helpText>
            <defaultValue>This plugin demonstrates working with Product entities.</defaultValue>
        </input-field>
    </card>

</config>
```

**What this does:**
- Creates a configuration page in Settings → Extensions → MyFirstPlugin
- Adds a text field that admins can edit
- Sets a default value for the text
- Includes helpful description text

---

## Step 8: Create the Storefront Template

Create the template directory and HelloWorld page:

### Directory Structure:
```
src/Resources/views/storefront/page/hello-world.html.twig
```

### File: `src/Resources/views/storefront/page/hello-world.html.twig`

```twig
{% sw_extends '@Storefront/storefront/base.html.twig' %}

{% block base_content %}
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <!-- Hero Section -->
                <div class="jumbotron bg-primary text-white text-center p-5 mb-5">
                    <h1 class="display-4">🎉 {{ message }}</h1>
                    <p class="lead">Welcome to your first Shopware 6 plugin!</p>
                    <hr class="my-4">
                    <!-- This text comes from the plugin configuration -->
                    <p class="mb-0">{{ configText }}</p>
                </div>

                <!-- Products Section -->
                <div class="row">
                    <div class="col-md-8">
                        <h2>Featured Products</h2>
                        <p class="text-muted">Showing {{ totalProducts }} products from your shop</p>
                        
                        {% if products|length > 0 %}
                            <div class="row">
                                {% for product in products %}
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            {% if product.cover %}
                                                <img src="{{ product.cover.media.url }}" 
                                                     class="card-img-top" 
                                                     alt="{{ product.name }}"
                                                     style="height: 200px; object-fit: cover;">
                                            {% else %}
                                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                                     style="height: 200px;">
                                                    <i class="fas fa-image fa-3x text-muted"></i>
                                                </div>
                                            {% endif %}
                                            
                                            <div class="card-body">
                                                <h5 class="card-title">{{ product.name }}</h5>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        Product #: {{ product.productNumber }}
                                                    </small>
                                                </p>
                                                {% if product.manufacturer %}
                                                    <p class="card-text">
                                                        <strong>Brand:</strong> {{ product.manufacturer.name }}
                                                    </p>
                                                {% endif %}
                                                <p class="card-text">
                                                    <span class="badge badge-{{ product.active ? 'success' : 'danger' }}">
                                                        {{ product.active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </p>
                                            </div>
                                            
                                            <div class="card-footer">
                                                <a href="{{ seoUrl('frontend.detail.page', {'productId': product.id}) }}" 
                                                   class="btn btn-primary btn-sm">
                                                    View Product
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                {% endfor %}
                            </div>
                        {% else %}
                            <div class="alert alert-info">
                                <h4>No Products Found</h4>
                                <p>It looks like your shop doesn't have any active products yet.</p>
                                <p class="mb-0">Add some products in the administration panel to see them here!</p>
                            </div>
                        {% endif %}
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Plugin Information</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li><strong>Plugin Name:</strong> MyFirstPlugin</li>
                                    <li><strong>Namespace:</strong> OllisPlugin</li>
                                    <li><strong>Route:</strong> /hello-world</li>
                                    <li><strong>Products Shown:</strong> {{ totalProducts }}</li>
                                    <li><strong>Shopware Version:</strong> 6.7.3.1</li>
                                </ul>
                                
                                <hr>
                                
                                <h6>What This Demonstrates:</h6>
                                <ul class="small">
                                    <li>Custom controller routing</li>
                                    <li>Service injection</li>
                                    <li>Repository usage</li>
                                    <li>Template rendering</li>
                                    <li>Entity associations</li>
                                    <li>System configuration</li>
                                </ul>
                                
                                <hr class="my-3">
                                
                                <p class="small mb-2">
                                    <strong>Configuration:</strong><br>
                                    The text in the hero section can be configured in Settings → Extensions → MyFirstPlugin
                                </p>
                                <p class="small mb-0">
                                    <strong>Current value:</strong> "{{ configText }}"
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{% endblock %}
```

---

## Step 9: Installation & Activation Commands

Now let's install and activate your plugin:

```bash
# 1. Refresh plugin list to detect new plugin
bin/console plugin:refresh

# Install and activate the plugin
bin/console plugin:install --activate MyFirstPlugin

# 3. Clear cache
bin/console cache:clear
```

---

## Step 10: Test Your Plugin

1. **Visit your HelloWorld page**: http://127.0.0.1:8000/hello-world
2. **Expected result**: A styled page showing:
   - Welcome message
   - Configurable text in the hero section  
   - List of products from your shop with images
   - Plugin information sidebar

## Step 11: Test Plugin Configuration

1. **Open Shopware Admin**: Go to your admin panel (usually http://127.0.0.1:8000/admin)
2. **Navigate to Settings**: Settings → System → Extensions
3. **Find your plugin**: Look for "Olli's HelloWorld Plugin" 
4. **Click the settings icon** (gear icon) next to your plugin
5. **Edit the text**: Change "Hero section text" to something like "Welcome to my custom plugin!"
6. **Save**: Click the save button
7. **Test the change**: Refresh http://127.0.0.1:8000/hello-world to see your custom text

**Troubleshooting configuration:**
- If you don't see the settings icon, make sure the plugin is activated
- If changes don't appear, clear cache: `bin/console cache:clear`

---

## Step 12: Development Workflow

When making changes to your plugin:

```bash
# After code changes
bin/console cache:clear

# If you change services.xml
bin/console plugin:update MyFirstPlugin
bin/console cache:clear

# If you add frontend assets later
./bin/build-storefront.sh
```

---

## Troubleshooting 🔧

### 🚨 CRITICAL: Core Functionality Broken (Products/Categories Don't Work)

**What happened**: If your shop's product pages suddenly show 500 errors or "Definition for entity 'ProductDefinition' does not exist", your plugin accidentally broke Shopware's core functionality.

**Why this happens**: The plugin generator sometimes creates files that override Shopware's built-in product system.

**How to fix**:
```bash
# 1. Remove problematic files that override core functionality  
rm -rf custom/plugins/MyFirstPlugin/src/Core/

# 2. Edit services.xml and remove any lines that look like this:
# <service id="OllisPlugin\Core\Content\Product\ProductDefinition">

# 3. Edit routes.xml and remove this line if it exists:
# <import resource="../../Core/**/*Route.php" type="attribute" />

# 4. Reload your plugin
bin/console plugin:update MyFirstPlugin
bin/console cache:clear

# 5. Test that product pages work again
```

### Plugin Not Found
```bash
bin/console plugin:refresh
bin/console plugin:list
```

### Route Not Working
- Check controller routing syntax
- Verify services.xml configuration
- Clear cache: `bin/console cache:clear`
- Ensure controller is in `Storefront/Controller/` directory

### Template Not Found
- Check template path: `@MyFirstPlugin/storefront/page/hello-world.html.twig`
- Verify directory structure
- Clear cache

### No Products Showing
- Add products via administration panel
- Check if products are active
- Verify database connection

### Plugin Configuration Not Showing
**Problem**: You don't see the configuration options in Settings → Extensions
**Solution**: 
- Make sure the plugin is activated (not just installed)
- Check that `config.xml` exists in `src/Resources/config/`
- Run `bin/console plugin:update MyFirstPlugin` and `bin/console cache:clear`

### Configuration Changes Not Appearing  
**Problem**: You changed the text in admin but it doesn't show on the page
**Solution**:
- Clear cache: `bin/console cache:clear`  
- Make sure you saved the configuration in the admin panel
- Check browser cache (try hard refresh: Ctrl+F5 or Cmd+Shift+R)

---

## Next Steps 🚀

Congratulations! You've built your first Shopware plugin. Here's what you can explore next:

1. **Add API endpoints** - Create REST API controllers
2. **Add administration module** - Build admin panel extensions
3. **Create custom entities** - Define your own data structures
4. **Add event subscribers** - React to Shopware events
5. **Implement configuration** - Add plugin settings
6. **Add SCSS/JavaScript** - Custom frontend assets

---

## Key Concepts Learned ✅

- ✅ **Plugin naming system** (folder name vs namespace vs display name)
- ✅ Plugin structure and generation  
- ✅ **Plugin cleanup and avoiding core entity overrides**
- ✅ Service injection and dependency injection
- ✅ Repository patterns and entity queries  
- ✅ Custom controllers and routing
- ✅ **System configuration integration** (`config.xml`)
- ✅ **SystemConfigService usage** (reading configuration values)
- ✅ Storefront template creation
- ✅ Twig template syntax and variables
- ✅ Plugin lifecycle management (install/activate/deactivate)
- ✅ Troubleshooting common plugin issues

## Plugin Development Best Practices 🎯

### For HelloWorld/Simple Plugins:
- **Keep it minimal** - Only create what you actually need
- **Use existing entities** - Don't create custom Product/Category definitions
- **Work with repositories** - Use `product.repository` service, don't override entities
- **Test core functionality** - Always verify product/category pages still work

### Safe Plugin Structure for Beginners:
```
custom/plugins/MyPlugin/
├── src/
│   ├── Storefront/Controller/     # Your controllers
│   ├── Service/                   # Your business logic
│   └── Resources/
│       ├── config/services.xml    # Service definitions
│       ├── config/routes.xml      # Route imports
│       └── views/                 # Your templates
```

### What NOT to Create for Simple Plugins:
- ❌ `src/Core/Content/Product/` - Overrides core Product entity
- ❌ `src/Core/Content/Category/` - Overrides core Category entity  
- ❌ Custom entity definitions for existing core entities
- ❌ Complex entity relationships on first plugin

---

## Complete File Structure

Your **cleaned** plugin should look like this (note: NO Core/ directory):

```
custom/plugins/MyFirstPlugin/
├── composer.json
└── src/
    ├── MyFirstPlugin.php        # Main plugin class
    ├── Storefront/
    │   └── Controller/
    │       └── HelloWorldController.php  # Our HelloWorld page controller
    ├── Service/
    │   └── ProductService.php   # Service to get products
    └── Resources/
        ├── config/
        │   ├── services.xml      # Service definitions (clean!)
        │   ├── routes.xml        # Route imports (clean!)
        │   └── config.xml        # Plugin configuration schema
        └── views/
            └── storefront/
                └── page/
                    └── hello-world.html.twig  # Our template
```

### ✅ Verification Checklist

Before considering your plugin complete:

- [ ] **HelloWorld page works**: Visit http://127.0.0.1:8000/hello-world and see the page
- [ ] **Products display**: The page shows a list of products from your shop
- [ ] **Configuration works**: You can change the hero text via Settings → Extensions → MyFirstPlugin  
- [ ] **Core functionality intact**: Product pages like `/Clothing/` still work normally
- [ ] **No Core/ directory**: `custom/plugins/MyFirstPlugin/src/Core/` does not exist
- [ ] **Plugin shows in admin**: "Olli's HelloWorld Plugin" appears in Extensions list

**Great job! You've successfully created your first Shopware 6 plugin without breaking core functionality! 🎉**

---

## 🎓 Graduation: You're Ready For More Complex Plugins!

Now that you understand the fundamentals and common pitfalls, you can safely explore:
- Creating custom entities (that don't conflict with core ones)
- Building administration panels  
- Adding API endpoints
- Event subscribers and hooks
- Complex entity relationships

**Remember**: Always start simple, test thoroughly, and never override core entities unless you have a very specific advanced use case!