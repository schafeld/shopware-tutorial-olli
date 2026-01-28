<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Recommendation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ProductSessionCollection extends EntityCollection
{
    /**
     * Returns the class name of the entity this collection contains.
     */
    protected function getExpectedClass(): string
    {
        return ProductSessionEntity::class;
    }
}
