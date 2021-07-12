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

use Klipper\Module\RepairBundle\Model\RepairInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface AuditRepairableInterface
{
    /**
     * @return static
     */
    public function setRepair(?RepairInterface $repair);

    public function getRepair(): ?RepairInterface;

    /**
     * @return null|int|string
     */
    public function getRepairId();

    /**
     * @return static
     */
    public function setRepairPrice(?float $repairPrice);

    public function getRepairPrice(): ?float;
}
