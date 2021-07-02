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
use Klipper\Module\DeviceBundle\Validator\Constraints as KlipperDeviceAssert;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableRequiredTrait;
use Klipper\Module\PartnerBundle\Model\Traits\ContactableOptionalTrait;
use Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Audit Request model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="idx_audit_request_created_at", columns={"created_at"}),
 *         @ORM\Index(name="idx_audit_request_updated_at", columns={"updated_at"}),
 *         @ORM\Index(name="idx_audit_request_reference", columns={"reference"}),
 *         @ORM\Index(name="idx_audit_request_identifier_type", columns={"identifier_type"}),
 *         @ORM\Index(name="idx_audit_request_supplier_order_number", columns={"supplier_order_number"}),
 *         @ORM\Index(name="idx_audit_request_customer_reference", columns={"customer_reference"}),
 *         @ORM\Index(name="idx_audit_request_date", columns={"date"}),
 *         @ORM\Index(name="idx_audit_request_estimated_delivery_date", columns={"estimated_delivery_date"}),
 *         @ORM\Index(name="idx_audit_request_estimated_processing_date", columns={"estimated_processing_date"}),
 *         @ORM\Index(name="idx_audit_request_receipted_at", columns={"receipted_at"}),
 *         @ORM\Index(name="idx_audit_request_expected_quantity", columns={"expected_quantity"}),
 *         @ORM\Index(name="idx_audit_request_received_quantity", columns={"received_quantity"}),
 *         @ORM\Index(name="idx_audit_request_closed", columns={"closed"}),
 *         @ORM\Index(name="idx_audit_request_validated", columns={"validated"})
 *     }
 * )
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractAuditRequest implements AuditRequestInterface
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
     * @Serializer\ReadOnly
     */
    protected ?string $reference = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface",
     *     fetch="EXTRA_LAZY"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?PartnerAddressInterface $shippingAddress = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\AccountInterface",
     *     fetch="EAGER"
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
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface",
     *     fetch="EXTRA_LAZY"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?PartnerAddressInterface $invoiceAddress = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface",
     *     fetch="EAGER"
     * )
     *
     * @Assert\NotBlank
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
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?string $identifierType = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface",
     *     fetch="EXTRA_LAZY"
     * )
     *
     * @EntityDoctrineChoice("audit_request_status")
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
     * @Assert\NotBlank
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
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnly
     */
    protected int $expectedQuantity = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnly
     */
    protected int $receivedQuantity = 0;

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
     * @Serializer\ReadOnly
     */
    protected bool $closed = false;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnly
     */
    protected bool $validated = false;

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
