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
use Klipper\Module\PartnerBundle\Model\Traits\ContactableOptionalInterface;

/**
 * Buyback Offer interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface BuybackOfferInterface extends
    AccountableRequiredInterface,
    ContactableOptionalInterface,
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
    public function setDate(?\DateTime $date);

    public function getDate(): ?\DateTime;

    /**
     * @return static
     */
    public function setExpirationDate(?\DateTime $expirationDate);

    public function getExpirationDate(): ?\DateTime;

    /**
     * @return static
     */
    public function setCalculationMethod(?string $calculationMethod);

    public function getCalculationMethod(): ?string;

    /**
     * @return static
     */
    public function setTotalConditionPrice(float $totalConditionPrice);

    public function getTotalConditionPrice(): float;

    /**
     * @return static
     */
    public function setTotalStatePrice(float $totalStatePrice);

    public function getTotalStatePrice(): float;

    /**
     * @return static
     */
    public function setTotalPrice(float $totalPrice);

    public function getTotalPrice(): float;

    /**
     * @return static
     */
    public function setStatus(?ChoiceInterface $status);

    public function getStatus(): ?ChoiceInterface;

    /**
     * @return static
     */
    public function setComment(?string $comment);

    public function getComment(): ?string;

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
