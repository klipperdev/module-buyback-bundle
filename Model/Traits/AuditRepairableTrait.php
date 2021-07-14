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

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
trait AuditRepairableTrait
{
    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairInterface",
     *     inversedBy="auditItem",
     *     cascade={"persist", "remove"},
     *     fetch="EAGER"
     * )
     * @ORM\JoinColumn(
     *     name="repair_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\ReadOnly
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?RepairInterface $repair = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     */
    protected ?float $repairPrice = null;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     */
    protected bool $includedRepairPrice = false;

    public function setRepair(?RepairInterface $repair): self
    {
        if (null !== $this->repair && $this->repair instanceof RepairAuditableInterface) {
            $this->repair->setAuditItem(null);
        }

        $this->repair = $repair;

        if (null !== $repair && $repair instanceof RepairAuditableInterface && $this instanceof AuditItemInterface) {
            $repair->setAuditItem($this);
        }

        return $this;
    }

    public function getRepair(): ?RepairInterface
    {
        return $this->repair;
    }

    public function getRepairId()
    {
        return null !== $this->getRepair() ? $this->getRepair()->getId() : null;
    }

    public function setRepairPrice(?float $repairPrice): self
    {
        $this->repairPrice = $repairPrice;

        return $this;
    }

    public function getRepairPrice(): ?float
    {
        return $this->repairPrice;
    }

    public function setIncludedRepairPrice(bool $includedRepairPrice): self
    {
        $this->includedRepairPrice = $includedRepairPrice;

        return $this;
    }

    public function isIncludedRepairPrice(): bool
    {
        return $this->includedRepairPrice;
    }
}
