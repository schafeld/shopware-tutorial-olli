<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Event\DiscountAppliedEvent;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpKernel\Log\Logger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DiscountService
{
    private const DISCOUNT_FILE = 'var/learning_discounts.json';

    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private string $filePath;

    public function __construct(
        EventDispatcherInterface $eventDispatcher, 
        LoggerInterface $logger,
        string $projectDir
    ) {
            $this->eventDispatcher = $eventDispatcher;
            $this->logger = $logger;
            $this->filePath = $projectDir . '/' . self::DISCOUNT_FILE;
    }

    /**
     * Apply a discount and dispatch event
     */
    public function applyDiscount(
        string $discountCode, 
        float $discountAmount, 
        ?string $customerId, 
        string $orderId, 
        Context $context,
    ): array {
        // Record the discount
        $this->recordDiscount($discountCode, $discountAmount, $customerId, $orderId);

        // Dispatch event
        $event = new DiscountAppliedEvent(
            $discountCode, 
            $discountAmount, 
            $customerId, 
            $orderId, 
            $context,
            [
                'applied_at' => date('Y-m-d H:i:s'),
                'currency' => 'EUR',
            ]
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Discount applied and event dospatched', [
            'discount_code' => $discountCode,
            'amount' => $discountAmount,
            'customer_id' => $customerId,
            'order_id' => $orderId,
        ]);

        return [
            'success' => true,
            'discount_code' => $discountCode,
            'discount_amount' => $discountAmount,
            'metadata' => $event->getMetaData(),
        ];
    }

    /**
     * Record discount to file
     */
    private function recordDiscount(
        string $code,
        float $amount,
        ?string $customerId,
        string $orderId
    ): void {
        $discounts = $this->loadDiscounts();

        $discounts[] = [
            'code' => $code,
            'amount' => $amount,
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'applied_at' => date('Y-m-d H:i:s'),
        ];

        $this->saveDiscounts($discounts);
    }

    /**
     * Gett all recorded discounts
     */
    public function getAllDiscounts(): array
    {
        return $this->loadDiscounts();
    }

    /**
     * Get discount statistics
     */
    public function getStatistics(): array
    {
        $discounts = $this->loadDiscounts();

        $stats = [
            'total_discounts' => count($discounts),
            'total_amount' => 0.0,
            'by_code' => [],
        ];

        foreach ($discounts as $discount) {
            $stats['total_amount'] += $discount['amount'];

            $code = $discount['code'];
            if (!isset($stats['by_code'][$code])) {
                $stats['by_code'][$code] = [
                    'count' => 0,
                    'total_amount' => 0.0,
                ];
            }
            $stats['by_code'][$code]['count']++;
            $stats['by_code'][$code]['total_amount'] += $discount['amount'];
        }
        return $stats;
    }

    private function loadDiscounts(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            return [];
        }
        return json_decode($content, true) ?? [];
    }

    private function saveDiscounts(array $discounts): void
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($this->filePath, json_encode($discounts, JSON_PRETTY_PRINT));
    }

    public function reset(): void
    {
        $this->saveDiscounts([]);
    }
}