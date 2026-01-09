<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Subscriber;

use GotoWebinarGoogleSheetsExport\Service\OrderExportService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that listens to order placement events
 * Creates export log entries for orders with products in the configured category
 */
class OrderPlacedSubscriber implements EventSubscriberInterface
{
    private const CONFIG_PREFIX = 'GotoWebinarGoogleSheetsExport.config.';

    public function __construct(
        private readonly OrderExportService $orderExportService,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger,
        private readonly EntityRepository $orderRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order_transaction.state.paid' => 'onOrderPaid',
        ];
    }

    /**
     * Handle order paid event
     */
    public function onOrderPaid(OrderStateMachineStateChangeEvent $event): void
    {
        // Check if plugin is enabled
        $enabled = $this->systemConfigService->get(self::CONFIG_PREFIX . 'enabled');
        if (!$enabled) {
            $this->logger->debug('GotoWebinar export: Plugin is disabled');
            return;
        }

        $context = $event->getContext();
        $orderId = $event->getOrder()->getId();

        $this->logger->info('GotoWebinar export: Processing paid order', [
            'orderId' => $orderId,
        ]);

        // Fetch the order with all required associations
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems.product.categories');
        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('salesChannel');

        $order = $this->orderRepository->search($criteria, $context)->first();

        if (!$order) {
            $this->logger->warning('GotoWebinar export: Order not found', [
                'orderId' => $orderId,
            ]);
            return;
        }

        try {
            // Check if order should be exported
            if (!$this->orderExportService->shouldExportOrder($order, $context)) {
                $this->logger->debug('GotoWebinar export: Order does not match category filter', [
                    'orderId' => $orderId,
                    'orderNumber' => $order->getOrderNumber(),
                ]);
                return;
            }

            $this->createExportLogs($order, $context);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create export logs for order', [
                'orderId' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create export log entries for each product in the order that matches the category
     */
    private function createExportLogs($order, Context $context): void
    {
        $categoryId = $this->systemConfigService->get(self::CONFIG_PREFIX . 'categoryId');
        
        if (!$categoryId) {
            return;
        }

        $lineItems = $order->getLineItems();
        if (!$lineItems) {
            return;
        }

        $customer = $order->getOrderCustomer();
        $salesChannel = $order->getSalesChannel();

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() !== 'product') {
                continue;
            }

            $product = $lineItem->getProduct();
            if (!$product) {
                continue;
            }

            // Check if product is in the configured category
            // This check is already done in shouldExportOrder, but we need to filter individual line items
            $customerData = [
                'first_name' => $customer ? $customer->getFirstName() : '',
                'last_name' => $customer ? $customer->getLastName() : '',
                'email' => $customer ? $customer->getEmail() : '',
                'sales_channel_name' => $salesChannel ? $salesChannel->getName() : 'Unknown',
            ];

            $this->orderExportService->createExportLog(
                $order->getId(),
                $order->getOrderNumber(),
                $product->getId(),
                $product->getProductNumber(),
                $customerData,
                $context,
                'pending'
            );

            $this->logger->info('Created export log for order line item', [
                'orderId' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'productNumber' => $product->getProductNumber(),
            ]);
        }
    }
}
