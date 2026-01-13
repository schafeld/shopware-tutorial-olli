<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Core\Content\OrderExport;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Collection of OrderExportEntity objects
 *
 * @method void                  add(OrderExportEntity $entity)
 * @method void                  set(string $key, OrderExportEntity $entity)
 * @method OrderExportEntity[]   getIterator()
 * @method OrderExportEntity[]   getElements()
 * @method OrderExportEntity|null get(string $key)
 * @method OrderExportEntity|null first()
 * @method OrderExportEntity|null last()
 */
class OrderExportCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OrderExportEntity::class;
    }
}
