<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Service;

use GotoWebinarGoogleSheetsExport\Core\Content\OrderExport\OrderExportEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Service for exporting order data to Google Sheets
 * Handles data extraction, formatting, and export log management
 */
class OrderExportService
{
    private const CONFIG_PREFIX = 'GotoWebinarGoogleSheetsExport.config.';

    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $exportRepository,
        private readonly CategoryFilterService $categoryFilterService,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    /**
     * Get orders that need to be exported
     * Returns pending export records, not orders directly
     */
    public function getPendingExports(Context $context, int $limit = 50): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('exportStatus', 'pending'));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        $criteria->setLimit($limit);

        $result = $this->exportRepository->search($criteria, $context);

        return $result->getElements();
    }

    /**
     * Extract export data from order and line item
     */
    public function extractExportData(OrderEntity $order, OrderLineItemEntity $lineItem): array
    {
        $customer = $order->getOrderCustomer();
        $salesChannel = $order->getSalesChannel();

        return [
            'customer_first_name' => $customer ? $customer->getFirstName() : '',
            'customer_last_name' => $customer ? $customer->getLastName() : '',
            'customer_email' => $customer ? $customer->getEmail() : '',
            'order_number' => $order->getOrderNumber(),
            'product_number' => $lineItem->getPayload()['productNumber'] ?? $lineItem->getIdentifier(),
            'sales_channel_name' => $salesChannel ? $salesChannel->getName() : 'Unknown',
        ];
    }

    /**
     * Create a new export log entry
     */
    public function createExportLog(
        string $orderId,
        string $orderNumber,
        string $productId,
        string $productNumber,
        array $customerData,
        Context $context,
        string $status = 'pending',
        ?string $errorMessage = null
    ): void {
        $data = [
            'id' => Uuid::randomHex(),
            'orderId' => $orderId,
            'orderNumber' => $orderNumber,
            'productId' => $productId,
            'productNumber' => $productNumber,
            'customerFirstName' => $customerData['first_name'] ?? '',
            'customerLastName' => $customerData['last_name'] ?? '',
            'customerEmail' => $customerData['email'] ?? '',
            'salesChannelName' => $customerData['sales_channel_name'] ?? '',
            'exportStatus' => $status,
            'errorMessage' => $errorMessage,
            'exportedAt' => $status === 'success' ? new \DateTime() : null,
        ];

        $this->exportRepository->create([$data], $context);
    }

    /**
     * Update export status
     */
    public function updateExportStatus(
        string $exportId,
        string $status,
        Context $context,
        ?string $errorMessage = null,
        ?string $googleSheetRowId = null
    ): void {
        $data = [
            'id' => $exportId,
            'exportStatus' => $status,
            'errorMessage' => $errorMessage,
        ];

        if ($status === 'success') {
            $data['exportedAt'] = new \DateTime();
        }

        if ($googleSheetRowId !== null) {
            $data['googleSheetRowId'] = $googleSheetRowId;
        }

        $this->exportRepository->update([$data], $context);
    }

    /**
     * Get recent exports for CSV generation
     */
    public function getRecentExports(Context $context, int $limit = 100): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new NotFilter(NotFilter::CONNECTION_AND, [
                new EqualsFilter('exportStatus', 'pending')
            ])
        );
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit($limit);

        $result = $this->exportRepository->search($criteria, $context);

        return $result->getElements();
    }

    /**
     * Get export statistics
     */
    public function getExportStats(Context $context): array
    {
        $criteria = new Criteria();
        $allExports = $this->exportRepository->search($criteria, $context);

        $pendingCriteria = new Criteria();
        $pendingCriteria->addFilter(new EqualsFilter('exportStatus', 'pending'));
        $pendingExports = $this->exportRepository->search($pendingCriteria, $context);

        $successCriteria = new Criteria();
        $successCriteria->addFilter(new EqualsFilter('exportStatus', 'success'));
        $successCriteria->addSorting(new FieldSorting('exportedAt', FieldSorting::DESCENDING));
        $successCriteria->setLimit(1);
        $lastSuccess = $this->exportRepository->search($successCriteria, $context);

        return [
            'totalExports' => $allExports->getTotal(),
            'pendingExports' => $pendingExports->getTotal(),
            'lastExport' => $lastSuccess->first() ? $lastSuccess->first()->getExportedAt() : null,
        ];
    }

    /**
     * Check if order should be exported based on category filter
     */
    public function shouldExportOrder(OrderEntity $order, Context $context): bool
    {
        $categoryId = $this->systemConfigService->get(self::CONFIG_PREFIX . 'categoryId');
        
        if (!$categoryId) {
            return false;
        }

        $lineItems = $order->getLineItems();
        if (!$lineItems) {
            return false;
        }

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() !== 'product') {
                continue;
            }

            $product = $lineItem->getProduct();
            if (!$product) {
                continue;
            }

            if ($this->categoryFilterService->productMatchesCategory($product, $categoryId, $context)) {
                return true;
            }
        }

        return false;
    }
}
