<?php declare(strict_types=1);

namespace Learning\Bundle\Twig;

use Learning\Bundle\Service\Gtm\GtmDataLayerService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * GTM Twig Extension
 * 
 * Provides Twig functions for Google Tag Manager integration.
 */
class GtmTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly GtmDataLayerService $gtmDataLayerService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('gtm_encode', [$this, 'gtmEncode']),
            new TwigFunction('gtm_is_enabled', [$this, 'isGtmEnabled']),
            new TwigFunction('gtm_container_id', [$this, 'getContainerId']),
            new TwigFunction('gtm_is_debug', [$this, 'isDebugMode']),
        ];
    }

    /**
     * Encode data as JSON for GTM dataLayer
     */
    public function gtmEncode(mixed $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    }

    /**
     * Check if GTM is enabled
     */
    public function isGtmEnabled(): bool
    {
        return $this->gtmDataLayerService->isEnabled();
    }

    /**
     * Get the GTM Container ID
     */
    public function getContainerId(): string
    {
        return $this->gtmDataLayerService->getContainerId();
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebugMode(): bool
    {
        return $this->gtmDataLayerService->isDebugMode();
    }
}
