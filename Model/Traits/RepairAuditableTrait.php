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

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
trait RepairAuditableTrait
{
    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditItemInterface",
     *     mappedBy="repair",
     *     fetch="EAGER"
     * )
     * @ORM\JoinColumn(
     *     name="audit_item_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Type("AssociationId")
     * @Serializer\Expose
     * @Serializer\ReadOnly
     */
    protected ?AuditItemInterface $auditItem = null;

    public function setAuditItem(?AuditItemInterface $auditItem): self
    {
        $this->auditItem = $auditItem;

        return $this;
    }

    public function getAuditItem(): ?AuditItemInterface
    {
        return $this->auditItem;
    }

    public function getAuditItemId()
    {
        return null !== $this->getAuditItem() ? $this->getAuditItem()->getId() : null;
    }
}
