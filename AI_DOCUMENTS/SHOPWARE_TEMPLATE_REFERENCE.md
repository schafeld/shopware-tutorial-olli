# Shopware Core Template Reference

This document helps you find the original Shopware Twig templates in the vendor directory for Day 2.5 frontend development.

## Key Template Locations

### Main Storefront Path
```
vendor/shopware/storefront/Resources/views/storefront/
```

### Product Detail Templates
```
vendor/shopware/storefront/Resources/views/storefront/page/content/product-detail.html.twig
vendor/shopware/storefront/Resources/views/storefront/page/product-detail/meta.html.twig
```

### Buy Widget Components (mentioned in Day 2.5)
```
vendor/shopware/storefront/Resources/views/storefront/component/buy-widget/buy-widget.html.twig
vendor/shopware/storefront/Resources/views/storefront/component/buy-widget/buy-widget-form.html.twig
vendor/shopware/storefront/Resources/views/storefront/component/buy-widget/buy-widget-price.html.twig
vendor/shopware/storefront/Resources/views/storefront/element/cms-element-buy-box.html.twig
```

### Product Components
```
vendor/shopware/storefront/Resources/views/storefront/component/product/
├── card/
│   ├── action.html.twig
│   ├── badges.html.twig
│   ├── box.html.twig
│   ├── box-image.html.twig
│   ├── box-minimal.html.twig
│   ├── box-standard.html.twig
│   ├── box-wishlist.html.twig
│   ├── price-unit.html.twig
│   └── wishlist.html.twig
├── description.html.twig
├── listing.html.twig
├── properties.html.twig
└── quickview/
    └── minimal.html.twig
```

### Layout Templates
```
vendor/shopware/storefront/Resources/views/storefront/layout/
vendor/shopware/storefront/Resources/views/storefront/base.html.twig
```

### Checkout Templates
```
vendor/shopware/storefront/Resources/views/storefront/page/checkout/
├── cart/index.html.twig
├── confirm/index.html.twig
└── finish/index.html.twig
```

## How to Find Template Blocks

### Search for specific blocks:
```bash
# Find all blocks containing "product_detail"
grep -r "block page_product_detail" vendor/shopware/storefront/Resources/views/

# Find all blocks in buy-widget
grep "{% block" vendor/shopware/storefront/Resources/views/storefront/component/buy-widget/*.twig

# Find all product card blocks
grep "{% block" vendor/shopware/storefront/Resources/views/storefront/component/product/card/*.twig
```

### Find templates by CSS class:
```bash
# Example: Find where "btn-buy" class is used
grep -r "btn-buy" vendor/shopware/storefront/Resources/views/storefront/

# Find "product-detail" classes
grep -r "product-detail" vendor/shopware/storefront/Resources/views/storefront/component/
```

## Template Hierarchy

When you extend a template in your plugin:
```twig
{% sw_extends '@Storefront/storefront/page/content/product-detail.html.twig' %}
```

Your plugin path:
```
custom/plugins/YourPlugin/src/Resources/views/storefront/page/content/product-detail.html.twig
```

## Common Block Names (from Day 2.5)

- `page_product_detail_content` - Main product content wrapper
- `page_product_detail_buy_price` - Price display section
- `page_product_detail_buy_button_label` - Add to cart button
- `component_product_box` - Product card/box
- `component_product_box_image` - Product image in listings

## Tips for Frontend Development

1. **Browse the vendor templates** to understand the structure
2. **Copy block names exactly** - they're case-sensitive
3. **Use {{ parent() }}** to keep original content
4. **Check CSS classes** in the vendor templates for styling hooks
5. **Use browser DevTools** to inspect rendered HTML and find which template rendered it

## JavaScript Plugin Locations
```
vendor/shopware/storefront/Resources/app/storefront/src/plugin/
```

## SCSS Files
```
vendor/shopware/storefront/Resources/app/storefront/src/scss/
```
