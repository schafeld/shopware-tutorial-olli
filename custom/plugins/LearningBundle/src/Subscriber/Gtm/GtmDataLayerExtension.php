<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber\Gtm;

use Shopware\Core\Framework\Struct\Struct;

/**
 * GTM DataLayer Extension
 * 
 * A simple struct to carry GTM dataLayer data through page extensions.
 * This allows Twig templates to access the data via page.extensions.gtmDataLayer
 * 
 * IMPORTANT: This class pre-encodes all data to JSON strings to avoid
 * memory issues with Twig's json_encode filter on complex nested structures.
 */
class GtmDataLayerExtension extends Struct
{
    protected string $pageDataJson;
    protected string $eventsJson;
    protected bool $hasEvents;

    public function __construct(array $data)
    {
        // Pre-encode to JSON immediately to avoid Twig json_encode issues
        $this->pageDataJson = json_encode(
            $data['pageData'] ?? [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
        ) ?: '{}';
        
        $this->eventsJson = json_encode(
            $data['events'] ?? [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
        ) ?: '[]';
        
        $this->hasEvents = !empty($data['events']);
    }

    public function getPageDataJson(): string
    {
        return $this->pageDataJson;
    }

    public function getEventsJson(): string
    {
        return $this->eventsJson;
    }

    public function hasEvents(): bool
    {
        return $this->hasEvents;
    }
}
