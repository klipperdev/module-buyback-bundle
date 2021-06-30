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

use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductableOptionalInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductCombinationableOptionalInterface;

/**
 * Audit Request Item interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface AuditRequestItemInterface extends
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
    public function setAuditRequest(?AuditRequestInterface $auditRequest);

    public function getAuditRequest(): ?AuditRequestInterface;

    /**
     * @return static
     */
    public function setExpectedQuantity(?int $expectedQuantity);

    public function getExpectedQuantity(): ?int;

    /**
     * @return static
     */
    public function setReceivedQuantity(?int $receivedQuantity);

    public function getReceivedQuantity(): ?int;
}
