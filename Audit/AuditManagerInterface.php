<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Audit;

use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface AuditManagerInterface
{
    /**
     * Create a repair for an audit item without persist.
     */
    public function transferToRepair(AuditItemInterface $audit): RepairInterface;
}
