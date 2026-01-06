<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractProductViewRoute
{
    abstract public function getDecorated(): AbstractProductViewRoute;
    abstract public function load(
        string $productId,
        Request $request,
        SalesChannelContext $context
    ): ProductViewRouteResponse;
}