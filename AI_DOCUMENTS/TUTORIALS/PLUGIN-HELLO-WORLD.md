# Shopware 6 HelloWorld Plugin Tutorial

## Overview 🚀

This tutorial will guide you through creating your first Shopware 6 plugin called "MyFirstPlugin" (with namespace "OllisPlugin") - a HelloWorld plugin that demonstrates core Shopware concepts including:
- Plugin structure and setup
- Working with Product entities
- Creating custom controllers and routes
- Building storefront pages
- Service injection and repository usage

## What We'll Build

A complete HelloWorld plugin that:
1. Displays a custom "Hello World" page in the storefront
2. Shows a list of products from your shop
3. Demonstrates proper Shopware development patterns
4. Includes proper service configuration

## Prerequisites ✅

- Shopware 6.7.3.1 development environment running
- Basic PHP and Twig knowledge
- Terminal/command line access

---

## Step 1: Plugin Generation (COMPLETED ✅)

You've already completed this step:

```bash
bin/console plugin:create OllisPlugin
```

**Entity chosen**: `Product`  
**Generator options**: All defaults accepted

This created the basic plugin structure at `custom/plugins/MyFirstPlugin/` with namespace `OllisPlugin`

---

## Step 2: Explore Generated Structure

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

## Step 3: Create Your First Controller

We'll create a HelloWorld controller that displays products:

### File: `src/Storefront/Controller/HelloWorldController.php`

```php
<?php declare(strict_types=1);

namespace OllisPlugin\Storefront\Controller;

use OllisPlugin\Service\ProductService;
use Shopware\Core\Framework\Context;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class HelloWorldController extends StorefrontController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    #[Route(
        path: '/hello-world',
        name: 'frontend.hello.world',
        methods: ['GET']
    )]
    public function helloWorld(Context $context): Response
    {
        // Get some products to display
        $products = $this->productService->getActiveProducts($context, 5);
        
        return $this->renderStorefront('@MyFirstPlugin/storefront/page/hello-world.html.twig', [
            'message' => 'Hello World from Olli\'s Plugin!',
            'products' => $products,
            'totalProducts' => count($products)
        ]);
    }
}
```

---

## Step 4: Enhance the ProductService

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

## Step 5: Update Service Configuration

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
            <call method="setContainer">
                <argument type="service" id="service_container"/>
            </call>
        </service>
    </services>
</container>
```

---

## Step 6: Create the Storefront Template

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
                    <p class="mb-0">This plugin demonstrates working with Product entities.</p>
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
                                </ul>
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

## Step 7: Installation & Activation Commands

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

## Step 8: Test Your Plugin

1. **Visit your HelloWorld page**: http://127.0.0.1:8000/hello-world
2. **Expected result**: A styled page showing:
   - Welcome message
   - List of products from your shop
   - Plugin information sidebar

---

## Step 9: Development Workflow

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

### Plugin Not Found
```bash
bin/console plugin:refresh
bin/console plugin:list
```

### Route Not Working
- Check controller routing syntax
- Verify services.xml configuration
- Clear cache: `bin/console cache:clear`

### Template Not Found
- Check template path: `@MyFirstPlugin/storefront/page/hello-world.html.twig`
- Verify directory structure
- Clear cache

### No Products Showing
- Add products via administration panel
- Check if products are active
- Verify database connection

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

- ✅ Plugin structure and generation
- ✅ Service injection and dependency injection
- ✅ Repository patterns and entity queries
- ✅ Custom controllers and routing
- ✅ Storefront template creation
- ✅ Twig template syntax
- ✅ Plugin lifecycle management

---

## Complete File Structure

Your plugin should now look like this:

```
custom/plugins/MyFirstPlugin/
├── composer.json
└── src/
    ├── MyFirstPlugin.php
    ├── Storefront/
    │   └── Controller/
    │       └── HelloWorldController.php
    ├── Service/
    │   └── ProductService.php
    └── Resources/
        ├── config/
        │   └── services.xml
        └── views/
            └── storefront/
                └── page/
                    └── hello-world.html.twig
```

**Great job! You've successfully created your first Shopware 6 plugin! 🎉**