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

use Klipper\Component\Model\Traits\EnableInterface;
use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\LabelableInterface;
use Klipper\Component\Model\Traits\NameableInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\SortableInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;

/**
 * Audit Condition interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface AuditConditionInterface extends
    IdInterface,
    EnableInterface,
    LabelableInterface,
    NameableInterface,
    OrganizationalRequiredInterface,
    SortableInterface,
    TimestampableInterface,
    UserTrackableInterface
{
    /**
     * @return static
     */
    public function setState(?string $state);

    public function getState(): ?string;

    /**
     * @return static
     */
    public function setDescription(?string $description);

    public function getDescription(): ?string;
}
