<?php declare(strict_types=1);

namespace Academy\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Content\Product\ProductEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Product Debug Subscriber for logging product-related operations
 * 
 * This subscriber logs information when products are loaded and provides debug info
 */
class ProductDebugSubscriber implements EventSubscriberInterface
{
    private bool $debugMode = false;
    private array $loadedProducts = [];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            ProductEvents::PRODUCT_LOADED_EVENT => 'onProductLoaded',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Enable debug mode for our special debug routes or when sql_debug parameter is set
        $this->debugMode = $request->query->getBoolean('sql_debug') || 
                          $request->attributes->get('_route') === 'frontend.detail.debug';
        
        if ($this->debugMode) {
            error_log('[ProductDebug] Debug mode enabled for request: ' . $request->getPathInfo());
        }
    }

    public function onProductLoaded(EntityLoadedEvent $event): void
    {
        if (!$this->debugMode) {
            return;
        }

        $entities = $event->getEntities();
        $count = count($entities);
        
        error_log(sprintf('[ProductDebug] %d product entities loaded', $count));
        
        // Log details about loaded products
        foreach ($entities as $i => $entity) {
            // Check if it's a product entity
            if ($entity instanceof \Shopware\Core\Content\Product\ProductEntity) {
                $productId = $entity->getId();
                $productName = $entity->getTranslated()['name'] ?? $entity->getName() ?? 'Unknown';
                
                error_log(sprintf('[ProductDebug] Product %d: ID=%s, Name=%s', $i + 1, $productId, $productName));
                
                // Store for later analysis
                $this->loadedProducts[] = [
                    'id' => $productId,
                    'name' => $productName,
                    'timestamp' => microtime(true)
                ];
            } else {
                // Log other entity types
                $entityClass = get_class($entity);
                error_log(sprintf('[ProductDebug] Entity %d: %s', $i + 1, $entityClass));
            }
        }
        
        // Log summary
        if ($count > 0) {
            error_log(sprintf('[ProductDebug] Total products tracked: %d', count($this->loadedProducts)));
        }
    }
}