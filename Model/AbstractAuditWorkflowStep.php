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

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Component\DoctrineChoice\Model\ChoiceInterface;
use Klipper\Component\DoctrineChoice\Validator\Constraints\EntityDoctrineChoice;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\SortableTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Audit Workflow Step model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractAuditWorkflowStep implements AuditWorkflowStepInterface
{
    use OrganizationalRequiredTrait;
    use SortableTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditWorkflowInterface",
     *     fetch="EAGER",
     *     inversedBy="steps"
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     *
     * @Gedmo\SortableGroup
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?AuditWorkflowInterface $auditWorkflow = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditStepInterface",
     *     fetch="EAGER"
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?AuditStepInterface $auditStep = null;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     */
    protected bool $closed = false;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     */
    protected bool $validated = false;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface",
     *     fetch="EXTRA_LAZY"
     * )
     *
     * @EntityDoctrineChoice("audit_item_status")
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?ChoiceInterface $automaticStatus = null;

    public function setAuditWorkflow(?AuditWorkflowInterface $auditWorkflow): self
    {
        $this->auditWorkflow = $auditWorkflow;

        return $this;
    }

    public function getAuditWorkflow(): ?AuditWorkflowInterface
    {
        return $this->auditWorkflow;
    }

    public function getAuditWorkflowId()
    {
        return null !== $this->getAuditWorkflow()
            ? $this->getAuditWorkflow()->getId()
            : null;
    }

    public function setAuditStep(?AuditStepInterface $auditStep): self
    {
        $this->auditStep = $auditStep;

        return $this;
    }

    public function getAuditStep(): ?AuditStepInterface
    {
        return $this->auditStep;
    }

    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function setValidated(bool $validated): self
    {
        $this->validated = $validated;

        return $this;
    }

    public function isValidated(): bool
    {
        return $this->validated;
    }

    public function setAutomaticStatus(?ChoiceInterface $automaticStatus): self
    {
        $this->automaticStatus = $automaticStatus;

        return $this;
    }

    public function getAutomaticStatus(): ?ChoiceInterface
    {
        return $this->automaticStatus;
    }
}
