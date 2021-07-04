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
 * Audit Request Item model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="idx_audit_request_item_created_at", columns={"created_at"}),
 *         @ORM\Index(name="idx_audit_request_item_updated_at", columns={"updated_at"}),
 *         @ORM\Index(name="idx_audit_request_item_expected_quantity", columns={"expected_quantity"}),
 *         @ORM\Index(name="idx_audit_request_item_received_quantity", columns={"received_quantity"})
 *     }
 * )
 *
 * @KlipperSecurityDoctrineAssert\OrganizationalUniqueEntity(
 *     fields={"organization", "auditRequest", "product", "productCombination"},
 *     errorPath="product",
 *     repositoryMethod="findBy",
 *     ignoreNull=false,
 *     allFilters=true
 * )
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractAuditRequestItem implements AuditRequestItemInterface
{
    use OrganizationalRequiredTrait;
    use ProductableTrait;
    use ProductCombinationableTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditRequestInterface",
     *     inversedBy="items",
     *     fetch="EAGER"
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?AuditRequestInterface $auditRequest = null;

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

    public function setAuditRequest(?AuditRequestInterface $auditRequest): self
    {
        $this->auditRequest = $auditRequest;

        return $this;
    }

    public function getAuditRequest(): ?AuditRequestInterface
    {
        return $this->auditRequest;
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
