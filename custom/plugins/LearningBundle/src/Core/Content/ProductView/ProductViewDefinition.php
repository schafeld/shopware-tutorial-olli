<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductViewDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'learning_product_view';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ProductViewEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ProductViewCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),

            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),

            new FkField('customer_id', 'customerId', CustomerDefinition::class),

            (new IntField('view_count', 'viewCount'))->addFlags(new Required()),
            new StringField('user_agent', 'userAgent'),
            (new DateTimeField('last_viewed_at', 'lastViewedAt'))->addFlags(new Required()),

            // Associations
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id', false),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class,'id', false),
        ]);
    }
}
