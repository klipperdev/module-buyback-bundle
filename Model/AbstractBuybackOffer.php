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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Component\DoctrineChoice\Model\ChoiceInterface;
use Klipper\Component\DoctrineChoice\Validator\Constraints\EntityDoctrineChoice;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Klipper\Module\BuybackBundle\Validator\Constraints as KlipperBuybackAssert;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableRequiredTrait;
use Klipper\Module\PartnerBundle\Model\Traits\ContactableOptionalTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Buyback Offer model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="idx_buyback_offer_created_at", columns={"created_at"}),
 *         @ORM\Index(name="idx_buyback_offer_updated_at", columns={"updated_at"}),
 *         @ORM\Index(name="idx_buyback_offer_reference", columns={"reference"}),
 *         @ORM\Index(name="idx_buyback_offer_date", columns={"date"}),
 *         @ORM\Index(name="idx_buyback_offer_expiration_date", columns={"expiration_date"}),
 *         @ORM\Index(name="idx_buyback_offer_calculation_method", columns={"calculation_method"}),
 *         @ORM\Index(name="idx_buyback_offer_total_condition_price", columns={"total_condition_price"}),
 *         @ORM\Index(name="idx_buyback_offer_total_state_price", columns={"total_state_price"}),
 *         @ORM\Index(name="idx_buyback_offer_total_price", columns={"total_price"}),
 *         @ORM\Index(name="idx_buyback_offer_closed", columns={"closed"}),
 *         @ORM\Index(name="idx_buyback_offer_validated", columns={"validated"})
 *     }
 * )
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractBuybackOffer implements BuybackOfferInterface
{
    use AccountableRequiredTrait;
    use ContactableOptionalTrait;
    use OrganizationalRequiredTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=80)
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected ?string $reference = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?PartnerAddressInterface $shippingAddress = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\AccountInterface"
     * )
     *
     * @Assert\NotNull
     * @Assert\Expression(
     *     expression="!(!value || !value.isSupplier())",
     *     message="klipper_buyback.account.invalid_supplier"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?AccountInterface $supplier = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?PartnerAddressInterface $invoiceAddress = null;

    /**
     * @ORM\Column(type="date", nullable=true)
     *
     * @Assert\Type(type="datetime")
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?\DateTime $date = null;

    /**
     * @ORM\Column(type="date", nullable=true)
     *
     * @Assert\Type(type="datetime")
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?\DateTime $expirationDate = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @KlipperBuybackAssert\BuybackOfferCalculationMethodChoice
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?string $calculationMethod = null;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=80)
     *
     * @Serializer\Expose
     */
    protected ?string $supplierOrderNumber = null;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected int $numberOfItems = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected float $totalConditionPrice = 0.0;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected float $totalStatePrice = 0.0;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected float $totalRepairPrice = 0.0;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected float $totalPrice = 0.0;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface"
     * )
     *
     * @EntityDoctrineChoice("buyback_offer_status")
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?ChoiceInterface $status = null;

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

    /**
     * @ORM\Column(type="date", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected ?\DateTime $validatedAt = null;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditItemInterface",
     *     mappedBy="buybackOffer"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\Groups({"Filter"})
     */
    protected ?Collection $auditItems = null;

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setShippingAddress(?PartnerAddressInterface $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getShippingAddress(): ?PartnerAddressInterface
    {
        return $this->shippingAddress;
    }

    public function setSupplier(?AccountInterface $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getSupplier(): ?AccountInterface
    {
        return $this->supplier;
    }

    public function setInvoiceAddress(?PartnerAddressInterface $invoiceAddress): self
    {
        $this->invoiceAddress = $invoiceAddress;

        return $this;
    }

    public function getInvoiceAddress(): ?PartnerAddressInterface
    {
        return $this->invoiceAddress;
    }

    public function setDate(?\DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setExpirationDate(?\DateTime $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getExpirationDate(): ?\DateTime
    {
        return $this->expirationDate;
    }

    public function setCalculationMethod(?string $calculationMethod): self
    {
        $this->calculationMethod = $calculationMethod;

        return $this;
    }

    public function getCalculationMethod(): ?string
    {
        return $this->calculationMethod;
    }

    public function setSupplierOrderNumber(?string $supplierOrderNumber): self
    {
        $this->supplierOrderNumber = $supplierOrderNumber;

        return $this;
    }

    public function getSupplierOrderNumber(): ?string
    {
        return $this->supplierOrderNumber;
    }

    public function setNumberOfItems(int $numberOfItems): self
    {
        $this->numberOfItems = $numberOfItems;

        return $this;
    }

    public function getNumberOfItems(): int
    {
        return $this->numberOfItems;
    }

    public function setTotalConditionPrice(float $totalConditionPrice): self
    {
        $this->totalConditionPrice = $totalConditionPrice;

        return $this;
    }

    public function getTotalConditionPrice(): float
    {
        return $this->totalConditionPrice;
    }

    public function setTotalStatePrice(float $totalStatePrice): self
    {
        $this->totalStatePrice = $totalStatePrice;

        return $this;
    }

    public function getTotalStatePrice(): float
    {
        return $this->totalStatePrice;
    }

    public function setTotalRepairPrice(float $totalRepairPrice): self
    {
        $this->totalRepairPrice = $totalRepairPrice;

        return $this;
    }

    public function getTotalRepairPrice(): float
    {
        return $this->totalRepairPrice;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
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

    public function setValidatedAt(?\DateTime $validatedAt): self
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getValidatedAt(): ?\DateTime
    {
        return $this->validatedAt;
    }

    public function getItems(): Collection
    {
        return $this->auditItems ?: $this->auditItems = new ArrayCollection();
    }
}
