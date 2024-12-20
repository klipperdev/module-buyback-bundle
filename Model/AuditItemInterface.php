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
use Klipper\Component\Security\Model\UserInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductableOptionalInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductCombinationableOptionalInterface;

/**
 * Audit Item interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface AuditItemInterface extends
    IdInterface,
    OrganizationalRequiredInterface,
    ProductableOptionalInterface,
    ProductCombinationableOptionalInterface,
    TimestampableInterface,
    UserTrackableInterface
{
    /**
     * @return static
     */
    public function setAuditBatch(?AuditBatchInterface $auditBatch);

    public function getAuditBatch(): ?AuditBatchInterface;

    /**
     * @return static
     */
    public function setStatus(?ChoiceInterface $status);

    public function getStatus(): ?ChoiceInterface;

    /**
     * @return static
     */
    public function setReceiptedAt(?\DateTime $receiptedAt);

    public function getReceiptedAt(): ?\DateTime;

    /**
     * @return static
     */
    public function setQualifiedAt(?\DateTime $qualifiedAt);

    public function getQualifiedAt(): ?\DateTime;

    /**
     * @return static
     */
    public function setAuditedAt(?\DateTime $auditedAt);

    public function getAuditedAt(): ?\DateTime;

    /**
     * @return static
     */
    public function setValorisedAt(?\DateTime $valorisedAt);

    public function getValorisedAt(): ?\DateTime;

    /**
     * @return static
     */
    public function setDevice(?DeviceInterface $device);

    public function getDevice(): ?DeviceInterface;

    /**
     * @return null|int|string
     */
    public function getDeviceId();

    /**
     * @return static
     */
    public function setAuditCondition(?AuditConditionInterface $auditCondition);

    public function getAuditCondition(): ?AuditConditionInterface;

    /**
     * @return static
     */
    public function setConditionPrice(?float $conditionPrice);

    public function getConditionPrice(): ?float;

    /**
     * @return static
     */
    public function setStatePrice(?float $statePrice);

    public function getStatePrice(): ?float;

    /**
     * @return static
     */
    public function setBuybackOffer(?BuybackOfferInterface $buybackOffer);

    public function getBuybackOffer(): ?BuybackOfferInterface;

    /**
     * @return static
     */
    public function setComment(?string $comment);

    public function getComment(): ?string;

    /**
     * @return static
     */
    public function setAuditor(?UserInterface $auditor);

    public function getAuditor(): ?UserInterface;

    /**
     * @return null|int|string
     */
    public function getAuditorId();

    /**
     * @return static
     */
    public function setPreviousAuditItem(?AuditItemInterface $previousAuditItem);

    public function getPreviousAuditItem(): ?AuditItemInterface;

    /**
     * @return static
     */
    public function setNextAuditItem(?AuditItemInterface $nextAuditItem);

    public function getNextAuditItem(): ?AuditItemInterface;

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
