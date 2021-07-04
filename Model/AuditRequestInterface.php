<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Model;

use Klipper\Component\DoctrineChoice\Model\ChoiceInterface;
use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableRequiredInterface;
use Klipper\Module\PartnerBundle\Model\Traits\ContactableRequiredInterface;
use Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface;

/**
 * Audit Request interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface AuditRequestInterface extends
    AccountableRequiredInterface,
    ContactableRequiredInterface,
    IdInterface,
    OrganizationalRequiredInterface,
    TimestampableInterface,
    UserTrackableInterface
{
    /**
     * @return static
     */
    public function setReference(?string $reference);

    public function getReference(): ?string;

    /**
     * @return static
     */
    public function setShippingAddress(?PartnerAddressInterface $shippingAddress);

    public function getShippingAddress(): ?PartnerAddressInterface;

    /**
     * @return static
     */
    public function setSupplier(?AccountInterface $supplier);

    public function getSupplier(): ?AccountInterface;

    /**
     * @return static
     */
    public function setInvoiceAddress(?PartnerAddressInterface $invoiceAddress);

    public function getInvoiceAddress(): ?PartnerAddressInterface;

    /**
     * @return static
     */
    public function setWorkcenter(?WorkcenterInterface $workcenter);

    public function getWorkcenter(): ?WorkcenterInterface;

    /**
     * @return static
     */
    public function setIdentifierType(?string $identifierType);

    public function getIdentifierType(): ?string;

    /**
     * @return static
     */
    public function setStatus(?ChoiceInterface $status);

    public function getStatus(): ?ChoiceInterface;

    /**
     * @return static
     */
    public function setSupplierOrderNumber(?string $supplierOrderNumber);

    public function getSupplierOrderNumber(): ?string;

    /**
     * @return static
     */
    public function setCustomerReference(?string $customerReference);

    public function getCustomerReference(): ?string;

    /**
     * @return static
     */
    public function setDate(?\DateTime $date);

    public function getDate(): ?\DateTime;

    /**
     * @return static
     */
    public function setEstimatedDeliveryDate(?\DateTime $estimatedDeliveryDate);

    public function getEstimatedDeliveryDate(): ?\DateTime;

    /**
     * @return static
     */
    public function setEstimatedProcessingDate(?\DateTime $estimatedProcessingDate);

    public function getEstimatedProcessingDate(): ?\DateTime;

    /**
     * @return static
     */
    public function setReceiptedAt(?\DateTime $receiptedAt);

    public function getReceiptedAt(): ?\DateTime;

    /**
     * @return static
     */
    public function setExpectedQuantity(int $expectedQuantity);

    public function getExpectedQuantity(): int;

    /**
     * @return static
     */
    public function setReceivedQuantity(int $receivedQuantity);

    public function getReceivedQuantity(): int;

    /**
     * @return static
     */
    public function setComment(?string $comment);

    public function getComment(): ?string;

    /**
     * @return static
     */
    public function setCompleted(bool $completed);

    public function isCompleted(): bool;

    /**
     * @return static
     */
    public function setClosed(bool $closed);

    public function isClosed(): bool;

    /**
     * @return static
     */
    public function setValidated(bool $validated);

    public function isValidated(): bool;
}
