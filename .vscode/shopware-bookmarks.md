# Shopware Template Bookmarks

Quick access to commonly used Shopware core templates. Click to open in VS Code.

## Product Templates

### Product Detail
- [product-detail.html.twig](../vendor/shopware/storefront/Resources/views/storefront/page/content/product-detail.html.twig) - Main product detail page
- [meta.html.twig](../vendor/shopware/storefront/Resources/views/storefront/page/product-detail/meta.html.twig) - Product meta tags

### Buy Widget
- [buy-widget.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/buy-widget/buy-widget.html.twig) - Main buy widget
- [buy-widget-form.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/buy-widget/buy-widget-form.html.twig) - Add to cart form
- [buy-widget-price.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/buy-widget/buy-widget-price.html.twig) - Price display

### Product Cards
- [box.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/product/card/box.html.twig) - Product box wrapper
- [box-standard.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/product/card/box-standard.html.twig) - Standard product card
- [box-minimal.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/product/card/box-minimal.html.twig) - Minimal product card
- [action.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/product/card/action.html.twig) - Product card actions
- [badges.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/product/card/badges.html.twig) - Product badges

### Product Listing
- [listing.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/product/listing.html.twig) - Product listing grid
- [description.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/product/description.html.twig) - Product description
- [properties.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/product/properties.html.twig) - Product properties

## Layout Templates

- [base.html.twig](../vendor/shopware/storefront/Resources/views/storefront/base.html.twig) - Base template
- [breadcrumb.html.twig](../vendor/shopware/storefront/Resources/views/storefront/layout/breadcrumb.html.twig) - Breadcrumb navigation

## Checkout Templates

- [cart/index.html.twig](../vendor/shopware/storefront/Resources/views/storefront/page/checkout/cart/index.html.twig) - Shopping cart
- [confirm/index.html.twig](../vendor/shopware/storefront/Resources/views/storefront/page/checkout/confirm/index.html.twig) - Order confirmation
- [finish/index.html.twig](../vendor/shopware/storefront/Resources/views/storefront/page/checkout/finish/index.html.twig) - Order finish page

## CMS Elements

- [cms-element-buy-box.html.twig](../vendor/shopware/storefront/Resources/views/storefront/element/cms-element-buy-box.html.twig) - Buy box CMS element

## Common Components

- [line-item/type/product.html.twig](../vendor/shopware/storefront/Resources/views/storefront/component/line-item/type/product.html.twig) - Line item product display

## Quick Search Commands

### In VS Code Command Palette (Cmd/Ctrl + Shift + P):

1. **"Tasks: Run Task"** → Select one of:
   - "Open Buy Widget Template"
   - "Open Product Detail Template"
   - "Find Template Block"
   - "Find CSS Class in Templates"

### In VS Code Search (Cmd/Ctrl + Shift + F):

Search in: `vendor/shopware/storefront/Resources/views/storefront/`

Common searches:
- `{% block` - Find all block definitions
- `class="btn-buy"` - Find buy button
- `data-product` - Find product data attributes
- `itemprop=` - Find schema.org markup

### Quick File Open (Cmd/Ctrl + P):

Type `buy-widget` or `product-detail` to quickly jump to files.

## VS Code Extensions (Recommended)

- **Twig Language 2** - Syntax highlighting for Twig
- **Better Comments** - Highlight TODO, FIXME in comments
- **Path Intellisense** - Autocomplete filenames
