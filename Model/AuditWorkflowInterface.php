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

use Doctrine\Common\Collections\Collection;
use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\LabelableInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;

/**
 * Audit Workflow interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface AuditWorkflowInterface extends
    IdInterface,
    LabelableInterface,
    OrganizationalRequiredInterface,
    TimestampableInterface,
    UserTrackableInterface
{
    /**
     * @return static
     */
    public function setDescription(?string $description);

    public function getDescription(): ?string;

    /**
     * @return AuditWorkflowStepInterface[]|Collection
     */
    public function getSteps(): Collection;
}
