<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Shopware\Core\System\SalesChannel\StoreApiResponse;

class ProductViewRouteResponse extends StoreApiResponse
{
    protected ProductViewResult $result;

    public function __construct(ProductViewResult $result)
    {
        parent::__construct($result);
        $this->result = $result;
    }

    public function getResult(): ProductViewResult
    {
        return $this->result;
    }
}