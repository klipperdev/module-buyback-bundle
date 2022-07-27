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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Module\BuybackBundle\Model\AuditConditionInterface;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
trait DeviceAuditableTrait
{
    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditConditionInterface"
     * )
     *
     * @Serializer\Expose
     */
    protected ?AuditConditionInterface $auditCondition = null;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditItemInterface"
     * )
     * @ORM\JoinColumn(
     *     name="last_audit_item_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\ReadOnlyProperty
     */
    protected ?AuditItemInterface $lastAuditItem = null;

    /**
     * @var null|AuditItemInterface[]|Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditItemInterface",
     *     fetch="EXTRA_LAZY",
     *     mappedBy="device"
     * )
     *
     * @Serializer\Expose
     * @Serializer\Groups({"Filter"})
     */
    protected ?Collection $auditItems = null;

    public function setAuditCondition(?AuditConditionInterface $auditCondition): self
    {
        $this->auditCondition = $auditCondition;

        return $this;
    }

    public function getAuditCondition(): ?AuditConditionInterface
    {
        return $this->auditCondition;
    }

    public function setLastAuditItem(?AuditItemInterface $lastAuditItem): self
    {
        $this->lastAuditItem = $lastAuditItem;

        return $this;
    }

    public function getLastAuditItem(): ?AuditItemInterface
    {
        return $this->lastAuditItem;
    }

    public function getLastAuditItemId()
    {
        return null !== $this->getLastAuditItem() ? $this->getLastAuditItem()->getId() : null;
    }

    public function getAuditItems(): Collection
    {
        return $this->auditItems ?: $this->auditItems = new ArrayCollection();
    }
}
