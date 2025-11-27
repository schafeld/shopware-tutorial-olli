<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                    add(ProductViewEntity $entity)
 * @method void                    set(string $key, ProductViewEntity $entity)
 * @method ProductViewEntity[]     getIterator()
 * @method ProductViewEntity[]     getElements()
 * @method ProductViewEntity|null  get(string $key)
 * @method ProductViewEntity|null  first()
 * @method ProductViewEntity|null  last()
 */
class ProductViewCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductViewEntity::class;
    }
}