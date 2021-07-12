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

use Klipper\Module\BuybackBundle\Model\AuditItemInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RepairAuditableInterface
{
    /**
     * @return static
     */
    public function setAuditItem(?AuditItemInterface $auditItem);

    public function getAuditItem(): ?AuditItemInterface;

    /**
     * @return null|int|string
     */
    public function getAuditItemId();
}
