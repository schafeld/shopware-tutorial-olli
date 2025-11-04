<?php declare(strict_types=1);

namespace OllisPlugin\Core\Content\Product;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(ProductEntity $entity)
 * @method void set(string $key, ProductEntity $entity)
 * @method ProductEntity[] getIterator()
 * @method ProductEntity[] getElements()
 * @method ProductEntity|null get(string $key)
 * @method ProductEntity|null first()
 * @method ProductEntity|null last()
 */
class ProductCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductEntity::class;
    }
}
