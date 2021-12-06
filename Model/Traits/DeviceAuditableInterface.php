<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Model\Traits;

use Klipper\Module\BuybackBundle\Model\AuditConditionInterface;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface DeviceAuditableInterface
{
    /**
     * @return static
     */
    public function setAuditCondition(?AuditConditionInterface $auditCondition);

    public function getAuditCondition(): ?AuditConditionInterface;

    /**
     * @return static
     */
    public function setLastAuditItem(?AuditItemInterface $lastAuditItem);

    public function getLastAuditItem(): ?AuditItemInterface;

    /**
     * @return null|int|string
     */
    public function getLastAuditItemId();
}
