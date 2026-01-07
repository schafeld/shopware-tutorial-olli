<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Core\Content\OrderExport;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Entity definition for order export records
 */
class OrderExportDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'gotowebinar_order_export';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return OrderExportEntity::class;
    }

    public function getCollectionClass(): string
    {
        return OrderExportCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new IdField('order_id', 'orderId'))->addFlags(new Required()),
            (new StringField('order_number', 'orderNumber'))->addFlags(new Required()),
            (new IdField('product_id', 'productId'))->addFlags(new Required()),
            (new StringField('product_number', 'productNumber'))->addFlags(new Required()),
            (new StringField('customer_first_name', 'customerFirstName'))->addFlags(new Required()),
            (new StringField('customer_last_name', 'customerLastName'))->addFlags(new Required()),
            (new StringField('customer_email', 'customerEmail'))->addFlags(new Required()),
            (new StringField('sales_channel_name', 'salesChannelName'))->addFlags(new Required()),
            new DateTimeField('exported_at', 'exportedAt'),
            new StringField('google_sheet_row_id', 'googleSheetRowId'),
            (new StringField('export_status', 'exportStatus'))->addFlags(new Required()),
            new LongTextField('error_message', 'errorMessage'),
        ]);
    }
}
