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
use Klipper\Component\Model\Traits\SortableInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;

/**
 * Audit Workflow Step interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface AuditWorkflowStepInterface extends
    IdInterface,
    OrganizationalRequiredInterface,
    SortableInterface,
    TimestampableInterface,
    UserTrackableInterface
{
    /**
     * @return static
     */
    public function setAuditWorkflow(?AuditWorkflowInterface $auditWorkflow);

    public function getAuditWorkflow(): ?AuditWorkflowInterface;

    public function getAuditWorkflowId();

    /**
     * @return static
     */
    public function setAuditStep(?AuditStepInterface $auditStep);

    public function getAuditStep(): ?AuditStepInterface;

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

    /**
     * @return static
     */
    public function setAutomaticStatus(?ChoiceInterface $automaticStatus);

    public function getAutomaticStatus(): ?ChoiceInterface;
}
