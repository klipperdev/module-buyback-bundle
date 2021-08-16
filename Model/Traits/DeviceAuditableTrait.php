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
trait DeviceAuditableTrait
{
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
}
