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
use Klipper\Component\Model\Traits\EnableTrait;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Module\BuybackBundle\Model\Traits\BuybackModuleableInterface;
use Klipper\Module\DeviceBundle\Validator\Constraints as KlipperDeviceAssert;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableTrait;
use Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Buyback module model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractBuybackModule implements BuybackModuleInterface
{
    use AccountableTrait;
    use EnableTrait;
    use OrganizationalRequiredTrait;
    use TimestampableTrait;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\AccountInterface",
     *     inversedBy="buybackModule",
     *     fetch="EAGER"
     * )
     *
     * @Assert\NotNull
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?AccountInterface $account = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\AccountInterface",
     *     fetch="EAGER"
     * )
     *
     * @Assert\NotNull
     * @Assert\Expression(
     *     expression="!(!value || !value.isSupplier())",
     *     message="klipper_buyback.buyback_module.invalid_supplier"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?AccountInterface $supplier = null;

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
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface",
     *     fetch="EXTRA_LAZY"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?PartnerAddressInterface $defaultInvoiceAddress = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditWorkflowInterface",
     *     fetch="EXTRA_LAZY"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?AuditWorkflowInterface $defaultAuditWorkflow = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface",
     *     fetch="EXTRA_LAZY"
     * )
     *
     * @EntityDoctrineChoice("buyback_audit_request_status")
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?ChoiceInterface $defaultAuditRequestStatus = null;

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
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     */
    protected ?string $excludedScope = null;

    public function setAccount(?AccountInterface $account): self
    {
        $this->account = $account;

        if ($account instanceof BuybackModuleableInterface) {
            $account->setBuybackModule($this);
        }

        return $this;
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

    public function setIdentifierType(?string $identifierType): self
    {
        $this->identifierType = $identifierType;

        return $this;
    }

    public function getIdentifierType(): ?string
    {
        return $this->identifierType;
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

    public function setDefaultInvoiceAddress(?PartnerAddressInterface $defaultInvoiceAddress): self
    {
        $this->defaultInvoiceAddress = $defaultInvoiceAddress;

        return $this;
    }

    public function getDefaultInvoiceAddress(): ?PartnerAddressInterface
    {
        return $this->defaultInvoiceAddress;
    }

    /**
     * @return static
     */
    public function setDefaultAuditWorkflow(?AuditWorkflowInterface $defaultAuditWorkflow): self
    {
        $this->defaultAuditWorkflow = $defaultAuditWorkflow;

        return $this;
    }

    public function getDefaultAuditWorkflow(): ?AuditWorkflowInterface
    {
        return $this->defaultAuditWorkflow;
    }

    public function setDefaultAuditRequestStatus(?ChoiceInterface $defaultAuditRequestStatus): self
    {
        $this->defaultAuditRequestStatus = $defaultAuditRequestStatus;

        return $this;
    }

    public function getDefaultAuditRequestStatus(): ?ChoiceInterface
    {
        return $this->defaultAuditRequestStatus;
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

    public function setExcludedScope(?string $excludedScope): self
    {
        $this->excludedScope = $excludedScope;

        return $this;
    }

    public function getExcludedScope(): ?string
    {
        return $this->excludedScope;
    }
}
