<?php declare(strict_types=1);

namespace OllisPlugin\Controller;

use OllisPlugin\Service\ProductService;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]
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
    public function helloWorld(Request $request, SalesChannelContext $context): Response
    {
        // Get some products to display
        $products = $this->productService->getActiveProducts($context->getContext(), 5);
        
        return $this->renderStorefront('@MyFirstPlugin/storefront/page/hello-world.html.twig', [
            'message' => 'Hello World from Olli\'s Plugin!',
            'products' => $products,
            'totalProducts' => count($products)
        ]);
    }
}