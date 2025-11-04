<?php declare(strict_types=1);

namespace OllisPlugin\Service;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class CustomFieldsInstaller
{
    private const CUSTOM_FIELDSET_NAME = 'swag_example_set';

    private const CUSTOM_FIELDSET = [
        'name' => self::CUSTOM_FIELDSET_NAME,
        'config' => [
            'label' => [
                'en-GB' => 'English custom field set label',
                'de-DE' => 'German custom field set label',
                Defaults::LANGUAGE_SYSTEM => 'Mention the fallback label here'
            ]
        ],
        'customFields' => [
            [
                'name' => 'swag_example_size',
                'type' => CustomFieldTypes::INT,
                'config' => [
                    'label' => [
                        'en-GB' => 'English custom field label',
                        'de-DE' => 'German custom field label',
                        Defaults::LANGUAGE_SYSTEM => 'Mention the fallback label here'
                    ],
                    'customFieldPosition' => 1
                ]
            ]
        ]
    ];

    public function __construct(
        private readonly EntityRepository $customFieldSetRepository,
        private readonly EntityRepository $customFieldSetRelationRepository
    ) {
    }

    public function install(Context $context): void
    {
        // First cleanup any existing custom fields to prevent conflicts
        $this->cleanup($context);
        
        $this->customFieldSetRepository->upsert([
            self::CUSTOM_FIELDSET
        ], $context);
    }

    public function addRelations(Context $context): void
    {
        $customFieldSetIds = $this->getCustomFieldSetIds($context);
        
        if (empty($customFieldSetIds)) {
            return; // No custom field sets to relate
        }
        
        $this->customFieldSetRelationRepository->upsert(array_map(function (string $customFieldSetId) {
            return [
                'customFieldSetId' => $customFieldSetId,
                'entityName' => 'product',
            ];
        }, $customFieldSetIds), $context);
    }

    public function removeRelations(Context $context): void
    {
        $customFieldSetIds = $this->getCustomFieldSetIds($context);
        
        if (empty($customFieldSetIds)) {
            return;
        }

        // Find and delete relations for this plugin's custom field sets
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entityName', 'product'));
        $criteria->addFilter(new EqualsFilter('customFieldSetId', $customFieldSetIds[0]));
        
        $relationIds = $this->customFieldSetRelationRepository->searchIds($criteria, $context)->getIds();
        
        if (!empty($relationIds)) {
            $deleteData = array_map(fn($id) => ['id' => $id], $relationIds);
            $this->customFieldSetRelationRepository->delete($deleteData, $context);
        }
    }

    public function cleanup(Context $context): void
    {
        // Remove relations first
        $this->removeRelations($context);
        
        // Then remove the custom field set (which will cascade delete custom fields)
        $customFieldSetIds = $this->getCustomFieldSetIds($context);
        
        if (!empty($customFieldSetIds)) {
            $deleteData = array_map(fn($id) => ['id' => $id], $customFieldSetIds);
            $this->customFieldSetRepository->delete($deleteData, $context);
        }
    }

    /**
     * @return string[]
     */
    private function getCustomFieldSetIds(Context $context): array
    {
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('name', self::CUSTOM_FIELDSET_NAME));

        return $this->customFieldSetRepository->searchIds($criteria, $context)->getIds();
    }
}
