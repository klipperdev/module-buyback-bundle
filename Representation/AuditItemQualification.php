<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Representation;

use Klipper\Module\BuybackBundle\Model\AuditBatchInterface;
use Klipper\Module\BuybackBundle\Model\AuditConditionInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\ProductBundle\Model\ProductCombinationInterface;
use Klipper\Module\ProductBundle\Model\ProductInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditItemQualification
{
    private ?int $id = null;

    /**
     * @Assert\NotBlank
     */
    private ?AuditBatchInterface $auditBatch = null;

    private ?DeviceInterface $device = null;

    private ?ProductInterface $product = null;

    private ?ProductCombinationInterface $productCombination = null;

    private ?AuditConditionInterface $auditCondition = null;

    private ?string $comment = null;

    private ?string $repairDeclaredBreakdownByCustomer = null;

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setAuditBatch(?AuditBatchInterface $auditBatch): self
    {
        $this->auditBatch = $auditBatch;

        return $this;
    }

    public function getAuditBatch(): ?AuditBatchInterface
    {
        return $this->auditBatch;
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

    public function setProduct(?ProductInterface $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->product;
    }

    public function setProductCombination(?ProductCombinationInterface $productCombination): self
    {
        $this->productCombination = $productCombination;

        return $this;
    }

    public function getProductCombination(): ?ProductCombinationInterface
    {
        return $this->productCombination;
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

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setRepairDeclaredBreakdownByCustomer(?string $repairDeclaredBreakdownByCustomer): self
    {
        $this->repairDeclaredBreakdownByCustomer = $repairDeclaredBreakdownByCustomer;

        return $this;
    }

    public function getRepairDeclaredBreakdownByCustomer(): ?string
    {
        return $this->repairDeclaredBreakdownByCustomer;
    }
}
