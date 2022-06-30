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
use JMS\Serializer\Annotation as Serializer;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Klipper\Component\SecurityExtra\Doctrine\Validator\Constraints as KlipperSecurityDoctrineAssert;
use Klipper\Module\ProductBundle\Model\Traits\ProductableTrait;
use Klipper\Module\ProductBundle\Model\Traits\ProductCombinationableTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Audit Batch Request Item model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="idx_audit_batch_request_item_created_at", columns={"created_at"}),
 *         @ORM\Index(name="idx_audit_batch_request_item_updated_at", columns={"updated_at"}),
 *         @ORM\Index(name="idx_audit_batch_request_item_expected_quantity", columns={"expected_quantity"}),
 *         @ORM\Index(name="idx_audit_batch_request_item_received_quantity", columns={"received_quantity"})
 *     }
 * )
 *
 * @KlipperSecurityDoctrineAssert\OrganizationalUniqueEntity(
 *     fields={"organization", "auditBatch", "product", "productCombination"},
 *     errorPath="product",
 *     repositoryMethod="findBy",
 *     ignoreNull=false,
 *     allFilters=true
 * )
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractAuditBatchRequestItem implements AuditBatchRequestItemInterface
{
    use OrganizationalRequiredTrait;
    use ProductableTrait;
    use ProductCombinationableTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditBatchInterface",
     *     inversedBy="items"
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?AuditBatchInterface $auditBatch = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     */
    protected ?int $expectedQuantity = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     */
    protected ?int $receivedQuantity = null;

    public function setAuditBatch(?AuditBatchInterface $auditBatch): self
    {
        $this->auditBatch = $auditBatch;

        return $this;
    }

    public function getAuditBatch(): ?AuditBatchInterface
    {
        return $this->auditBatch;
    }

    public function setExpectedQuantity(?int $expectedQuantity): self
    {
        $this->expectedQuantity = $expectedQuantity;

        return $this;
    }

    public function getExpectedQuantity(): ?int
    {
        return $this->expectedQuantity;
    }

    public function setReceivedQuantity(?int $receivedQuantity): self
    {
        $this->receivedQuantity = $receivedQuantity;

        return $this;
    }

    public function getReceivedQuantity(): ?int
    {
        return $this->receivedQuantity;
    }
}
