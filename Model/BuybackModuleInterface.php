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
use Klipper\Component\Model\Traits\EnableInterface;
use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableRequiredInterface;
use Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface;

/**
 * Buyback module interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface BuybackModuleInterface extends
    AccountableRequiredInterface,
    EnableInterface,
    IdInterface,
    OrganizationalRequiredInterface,
    TimestampableInterface
{
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
    public function setDefaultAuditRequestStatus(?ChoiceInterface $defaultAuditRequestStatus);

    public function getDefaultAuditRequestStatus(): ?ChoiceInterface;

    /**
     * @return static
     */
    public function setComment(?string $comment);

    public function getComment(): ?string;

    /**
     * @return static
     */
    public function setExcludedScope(?string $excludedScope);

    public function getExcludedScope(): ?string;
}
