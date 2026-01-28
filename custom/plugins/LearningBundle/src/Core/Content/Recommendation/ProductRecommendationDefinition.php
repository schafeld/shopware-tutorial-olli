<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Recommendation;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductRecommendationDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'learning_product_recommendation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ProductRecommendationEntity::class;
    }

    public function getCollectionClass(): string
    {
        // You may optionally create a ProductRecommendationCollection class
        // For most use cases, EntityCollection is sufficient
        return EntityCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('source_product_id', 'sourceProductId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class, 'source_product_version_id', 'sourceProductVersionId'))->addFlags(new Required()),
            (new FkField('recommended_product_id', 'recommendedProductId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class, 'recommended_product_version_id', 'recommendedProductVersionId'))->addFlags(new Required()),
            (new FloatField('affinity_score', 'affinityScore'))->addFlags(new Required()),
            (new IntField('view_count', 'viewCount'))->addFlags(new Required()),
            (new DateTimeField('last_updated', 'lastUpdated'))->addFlags(new Required()),
            new ManyToOneAssociationField('sourceProduct', 'source_product_id', ProductDefinition::class, 'id', false),
            new ManyToOneAssociationField('recommendedProduct', 'recommended_product_id', ProductDefinition::class, 'id', false),
        ]);
    }
}
