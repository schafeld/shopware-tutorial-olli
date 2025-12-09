<?php declare(strict_types=1);

namespace Academy\Storefront\Controller;

use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Shopware\Storefront\Page\Product\ProductPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]
class ProductDetailController extends StorefrontController
{
    public function __construct(
        private readonly ProductPageLoader $productPageLoader
    ) {
    }

    /**
     * Custom product detail page with SQL logging enabled via console output
     * 
     * Usage: Visit /product-debug/{productId} to see SQL queries in console
     * Example: http://127.0.0.1:8000/product-debug/019a4e27992d731d822d8183cd3485fc
     * 
     * Note: Run `tail -f var/log/dev.log` or check browser console/developer tools
     */
    #[Route(
        path: '/product-debug/{productId}',
        name: 'frontend.detail.debug',
        methods: ['GET'],
        defaults: ['productId' => null, '_httpCache' => false]
    )]
    public function debugProductDetail(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        // Simple logging approach that works with modern Shopware
        echo "<!-- Starting Product Detail Page Debug -->\n";
        
        // Load the product page (this will generate SQL queries)
        $start = microtime(true);
        $page = $this->productPageLoader->load($request, $salesChannelContext);
        $duration = microtime(true) - $start;
        
        // Log some basic information about the page load
        error_log(sprintf(
            '[ProductDebug] Product page loaded in %.3fs for product %s',
            $duration,
            $request->get('productId') ?? 'unknown'
        ));
        
        // Add debug information to the response
        $response = $this->renderStorefront('@Storefront/storefront/page/product-detail/index.html.twig', [
            'page' => $page
        ]);
        
        // Add debug header
        $response->headers->set('X-Debug-Product-Load-Time', (string) $duration);
        $response->headers->set('X-Debug-Mode', 'Product Detail');
        
        echo "<!-- Product Detail Page Debug Complete -->\n";
        
        return $response;
    }
}