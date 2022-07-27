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
use Klipper\Module\DeviceBundle\Validator\Constraints as KlipperDeviceAssert;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableRequiredTrait;
use Klipper\Module\PartnerBundle\Model\Traits\ContactableRequiredTrait;
use Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Audit Batch model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="idx_audit_batch_created_at", columns={"created_at"}),
 *         @ORM\Index(name="idx_audit_batch_updated_at", columns={"updated_at"}),
 *         @ORM\Index(name="idx_audit_batch_reference", columns={"reference"}),
 *         @ORM\Index(name="idx_audit_batch_identifier_type", columns={"identifier_type"}),
 *         @ORM\Index(name="idx_audit_batch_supplier_order_number", columns={"supplier_order_number"}),
 *         @ORM\Index(name="idx_audit_batch_customer_reference", columns={"customer_reference"}),
 *         @ORM\Index(name="idx_audit_batch_date", columns={"date"}),
 *         @ORM\Index(name="idx_audit_batch_estimated_delivery_date", columns={"estimated_delivery_date"}),
 *         @ORM\Index(name="idx_audit_batch_estimated_processing_date", columns={"estimated_processing_date"}),
 *         @ORM\Index(name="idx_audit_batch_receipted_at", columns={"receipted_at"}),
 *         @ORM\Index(name="idx_audit_batch_expected_quantity", columns={"expected_quantity"}),
 *         @ORM\Index(name="idx_audit_batch_received_quantity", columns={"received_quantity"}),
 *         @ORM\Index(name="idx_audit_batch_closed", columns={"closed"}),
 *         @ORM\Index(name="idx_audit_batch_validated", columns={"validated"})
 *     }
 * )
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractAuditBatch implements AuditBatchInterface
{
    use AccountableRequiredTrait;
    use ContactableRequiredTrait;
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
     * @Assert\Expression(
     *     expression="!(!value && (!this.getAccount() || !this.getAccount().getBuybackModule() || !this.getAccount().getBuybackModule().getShippingAddress()))",
     *     message="This value should not be blank."
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
     * @Assert\Expression(
     *     expression="!(!!value && !value.isSupplier())",
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
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?WorkcenterInterface $workcenter = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @KlipperDeviceAssert\DeviceIdentifierTypeChoice
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     *
     * @Serializer\Expose
     */
    protected ?string $identifierType = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface"
     * )
     *
     * @EntityDoctrineChoice("audit_batch_status")
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?ChoiceInterface $status = null;

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
     * @ORM\Column(type="string", length=80, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=80)
     *
     * @Serializer\Expose
     */
    protected ?string $customerReference = null;

    /**
     * @ORM\Column(type="date", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTime $date = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTime $estimatedDeliveryDate = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTime $estimatedProcessingDate = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTime $receiptedAt = null;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected int $numberOfRequestItems = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected int $expectedQuantity = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected int $receivedQuantity = 0;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\BuybackOfferInterface"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?BuybackOfferInterface $buybackOffer = null;

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
    protected bool $completed = false;

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
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected bool $converted = false;

    /**
     * @var null|AuditBatchRequestItemInterface[]|Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditBatchRequestItemInterface",
     *     fetch="EXTRA_LAZY",
     *     mappedBy="auditBatch",
     *     cascade={"persist", "remove"}
     * )
     */
    protected ?Collection $requestItems = null;

    /**
     * @var null|AuditItemInterface[]|Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditItemInterface",
     *     fetch="EXTRA_LAZY",
     *     mappedBy="auditBatch"
     * )
     *
     * @Serializer\Expose
     * @Serializer\Groups({"Filter"})
     */
    protected ?Collection $items = null;

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

    public function setWorkcenter(?WorkcenterInterface $workcenter): self
    {
        $this->workcenter = $workcenter;

        return $this;
    }

    public function getWorkcenter(): ?WorkcenterInterface
    {
        return $this->workcenter;
    }

    public function setIdentifierType(?string $identifierType): self
    {
        $this->identifierType = $identifierType;

        return $this;
    }

    public function getIdentifierType(): ?string
    {
        return $this->identifierType;
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

    public function setSupplierOrderNumber(?string $supplierOrderNumber): self
    {
        $this->supplierOrderNumber = $supplierOrderNumber;

        return $this;
    }

    public function getSupplierOrderNumber(): ?string
    {
        return $this->supplierOrderNumber;
    }

    public function setCustomerReference(?string $customerReference): self
    {
        $this->customerReference = $customerReference;

        return $this;
    }

    public function getCustomerReference(): ?string
    {
        return $this->customerReference;
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

    public function setEstimatedDeliveryDate(?\DateTime $estimatedDeliveryDate): self
    {
        $this->estimatedDeliveryDate = $estimatedDeliveryDate;

        return $this;
    }

    public function getEstimatedDeliveryDate(): ?\DateTime
    {
        return $this->estimatedDeliveryDate;
    }

    public function setEstimatedProcessingDate(?\DateTime $estimatedProcessingDate): self
    {
        $this->estimatedProcessingDate = $estimatedProcessingDate;

        return $this;
    }

    public function getEstimatedProcessingDate(): ?\DateTime
    {
        return $this->estimatedProcessingDate;
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

    public function setNumberOfRequestItems(int $numberOfRequestItems): self
    {
        $this->numberOfRequestItems = $numberOfRequestItems;

        return $this;
    }

    public function getNumberOfRequestItems(): int
    {
        return $this->numberOfRequestItems;
    }

    public function setExpectedQuantity(int $expectedQuantity): self
    {
        $this->expectedQuantity = $expectedQuantity;

        return $this;
    }

    public function getExpectedQuantity(): int
    {
        return $this->expectedQuantity;
    }

    public function setReceivedQuantity(int $receivedQuantity): self
    {
        $this->receivedQuantity = $receivedQuantity;

        return $this;
    }

    public function getReceivedQuantity(): int
    {
        return $this->receivedQuantity;
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

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setCompleted(bool $completed): self
    {
        $this->completed = $completed;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
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

    public function setConverted(bool $converted): self
    {
        $this->converted = $converted;

        return $this;
    }

    public function isConverted(): bool
    {
        return $this->converted;
    }

    public function getRequestItems(): Collection
    {
        return $this->requestItems ?: $this->requestItems = new ArrayCollection();
    }

    public function getItems(): Collection
    {
        return $this->items ?: $this->items = new ArrayCollection();
    }
}
