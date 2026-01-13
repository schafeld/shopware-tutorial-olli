<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Core\Content\OrderExport;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Entity representing a single order export record
 */
class OrderExportEntity extends Entity
{
    use EntityIdTrait;

    protected string $orderId;
    protected string $orderNumber;
    protected string $productId;
    protected string $productNumber;
    protected string $customerFirstName;
    protected string $customerLastName;
    protected string $customerEmail;
    protected string $salesChannelName;
    protected ?\DateTimeInterface $exportedAt;
    protected ?string $googleSheetRowId;
    protected string $exportStatus;
    protected ?string $errorMessage;

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductNumber(): string
    {
        return $this->productNumber;
    }

    public function setProductNumber(string $productNumber): void
    {
        $this->productNumber = $productNumber;
    }

    public function getCustomerFirstName(): string
    {
        return $this->customerFirstName;
    }

    public function setCustomerFirstName(string $customerFirstName): void
    {
        $this->customerFirstName = $customerFirstName;
    }

    public function getCustomerLastName(): string
    {
        return $this->customerLastName;
    }

    public function setCustomerLastName(string $customerLastName): void
    {
        $this->customerLastName = $customerLastName;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): void
    {
        $this->customerEmail = $customerEmail;
    }

    public function getSalesChannelName(): string
    {
        return $this->salesChannelName;
    }

    public function setSalesChannelName(string $salesChannelName): void
    {
        $this->salesChannelName = $salesChannelName;
    }

    public function getExportedAt(): ?\DateTimeInterface
    {
        return $this->exportedAt;
    }

    public function setExportedAt(?\DateTimeInterface $exportedAt): void
    {
        $this->exportedAt = $exportedAt;
    }

    public function getGoogleSheetRowId(): ?string
    {
        return $this->googleSheetRowId;
    }

    public function setGoogleSheetRowId(?string $googleSheetRowId): void
    {
        $this->googleSheetRowId = $googleSheetRowId;
    }

    public function getExportStatus(): string
    {
        return $this->exportStatus;
    }

    public function setExportStatus(string $exportStatus): void
    {
        $this->exportStatus = $exportStatus;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }
}
