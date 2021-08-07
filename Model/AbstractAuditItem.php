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
use Klipper\Component\DoctrineChoice\Model\ChoiceInterface;
use Klipper\Component\DoctrineChoice\Validator\Constraints\EntityDoctrineChoice;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Klipper\Component\Security\Model\UserInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductableTrait;
use Klipper\Module\ProductBundle\Model\Traits\ProductCombinationableTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Audit Item model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="idx_audit_item_created_at", columns={"created_at"}),
 *         @ORM\Index(name="idx_audit_item_updated_at", columns={"updated_at"})
 *     }
 * )
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractAuditItem implements AuditItemInterface
{
    use OrganizationalRequiredTrait;
    use ProductableTrait;
    use ProductCombinationableTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditRequestInterface",
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
    protected ?ChoiceInterface $status = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTime $receiptedAt = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTime $qualifiedAt = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTime $auditedAt = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTime $valorisedAt = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\DeviceBundle\Model\DeviceInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?DeviceInterface $device = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditConditionInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?AuditConditionInterface $auditCondition = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     */
    protected ?float $conditionPrice = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     */
    protected ?float $statePrice = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\BuybackOfferInterface",
     *     cascade={"persist"},
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?BuybackOfferInterface $buybackOffer = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Component\Security\Model\UserInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?UserInterface $auditor = null;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditItemInterface",
     *     inversedBy="nextAuditItem",
     *     cascade={"persist"},
     *     fetch="EXTRA_LAZY"
     * )
     * @ORM\JoinColumn(
     *     name="previous_audit_item_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?AuditItemInterface $previousAuditItem = null;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditItemInterface",
     *     mappedBy="previousAuditItem",
     *     fetch="EXTRA_LAZY"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?AuditItemInterface $nextAuditItem = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     */
    protected ?string $comment = null;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected bool $closed = false;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected bool $validated = false;

    public function setAuditRequest(?AuditRequestInterface $auditRequest): self
    {
        $this->auditRequest = $auditRequest;

        return $this;
    }

    public function getAuditRequest(): ?AuditRequestInterface
    {
        return $this->auditRequest;
    }

    public function setStatus(?ChoiceInterface $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?ChoiceInterface
    {
        return $this->status;
    }

    public function setReceiptedAt(?\DateTime $receiptedAt): self
    {
        $this->receiptedAt = $receiptedAt;

        return $this;
    }

    public function getReceiptedAt(): ?\DateTime
    {
        return $this->receiptedAt;
    }

    public function setQualifiedAt(?\DateTime $qualifiedAt): self
    {
        $this->qualifiedAt = $qualifiedAt;

        return $this;
    }

    public function getQualifiedAt(): ?\DateTime
    {
        return $this->qualifiedAt;
    }

    public function setAuditedAt(?\DateTime $auditedAt): self
    {
        $this->auditedAt = $auditedAt;

        return $this;
    }

    public function getAuditedAt(): ?\DateTime
    {
        return $this->auditedAt;
    }

    public function setValorisedAt(?\DateTime $valorisedAt): self
    {
        $this->valorisedAt = $valorisedAt;

        return $this;
    }

    public function getValorisedAt(): ?\DateTime
    {
        return $this->valorisedAt;
    }

    public function setDevice(?DeviceInterface $device): self
    {
        $this->device = $device;

        return $this;
    }

    public function getDevice(): ?DeviceInterface
    {
        return $this->device;
    }

    public function getDeviceId()
    {
        return null !== $this->getDevice()
            ? $this->getDevice()->getId()
            : null;
    }

    public function setAuditCondition(?AuditConditionInterface $auditCondition): self
    {
        $this->auditCondition = $auditCondition;

        return $this;
    }

    public function getAuditCondition(): ?AuditConditionInterface
    {
        return $this->auditCondition;
    }

    public function setConditionPrice(?float $conditionPrice): self
    {
        $this->conditionPrice = $conditionPrice;

        return $this;
    }

    public function getConditionPrice(): ?float
    {
        return $this->conditionPrice;
    }

    public function setStatePrice(?float $statePrice): self
    {
        $this->statePrice = $statePrice;

        return $this;
    }

    public function getStatePrice(): ?float
    {
        return $this->statePrice;
    }

    public function setBuybackOffer(?BuybackOfferInterface $buybackOffer): self
    {
        $this->buybackOffer = $buybackOffer;

        return $this;
    }

    public function getBuybackOffer(): ?BuybackOfferInterface
    {
        return $this->buybackOffer;
    }

    public function setAuditor(?UserInterface $auditor): self
    {
        $this->auditor = $auditor;

        return $this;
    }

    public function getAuditor(): ?UserInterface
    {
        return $this->auditor;
    }

    public function getAuditorId()
    {
        return null !== $this->getAuditor()
            ? $this->getAuditor()->getId()
            : null;
    }

    public function setPreviousAuditItem(?AuditItemInterface $previousAuditItem): self
    {
        $this->previousAuditItem = $previousAuditItem;

        return $this;
    }

    public function getPreviousAuditItem(): ?AuditItemInterface
    {
        return $this->previousAuditItem;
    }

    public function setNextAuditItem(?AuditItemInterface $nextAuditItem): self
    {
        $this->nextAuditItem = $nextAuditItem;

        return $this;
    }

    public function getNextAuditItem(): ?AuditItemInterface
    {
        return $this->nextAuditItem;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
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
}
