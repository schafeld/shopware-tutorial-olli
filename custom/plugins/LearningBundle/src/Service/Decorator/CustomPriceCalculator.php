<?php declare(strict_types=1);

namespace Learning\Bundle\Service\Decorator;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * This is an example decorator - use with caution in production!
 * 
 * Key point: Decorators MUST extend the class they decorate to maintain type compatibility
 */
class CustomPriceCalculator extends QuantityPriceCalculator
{
    private QuantityPriceCalculator $decoratedService;
    private LoggerInterface $logger;

    public function __construct(
        QuantityPriceCalculator $decoratedService,
        LoggerInterface $logger
    ) {
        $this->decoratedService = $decoratedService;
        $this->logger = $logger;
    }

    /**
     * Example: Add logging to price calculations
     */
    public function calculate(
        QuantityPriceDefinition $definition,
        SalesChannelContext $context
    ): CalculatedPrice {
        $this->logger->debug('Calculating price', [
            'quantity' => $definition->getQuantity(),
            'price' => $definition->getPrice(),
        ]);

        // Call the original service
        $calculatedPrice = $this->decoratedService->calculate($definition, $context);

        $this->logger->debug('Price calculated', [
            'total_price' => $calculatedPrice->getTotalPrice(),
            'unit_price' => $calculatedPrice->getUnitPrice(),
        ]);

        return $calculatedPrice;
    }
}