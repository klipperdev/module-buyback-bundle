<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Audit;

use Klipper\Component\DoctrineChoice\ChoiceManagerInterface;
use Klipper\Component\Resource\Object\ObjectFactoryInterface;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\Traits\BuybackModuleableInterface;
use Klipper\Module\BuybackBundle\Model\Traits\RepairAuditableInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditManager implements AuditManagerInterface
{
    private ObjectFactoryInterface $objectFactory;

    private ChoiceManagerInterface $choiceManager;

    public function __construct(
        ObjectFactoryInterface $objectFactory,
        ChoiceManagerInterface $choiceManager
    ) {
        $this->objectFactory = $objectFactory;
        $this->choiceManager = $choiceManager;
    }

    public function transferToRepair(AuditItemInterface $audit): RepairInterface
    {
        /** @var RepairInterface $repair */
        $repair = $this->objectFactory->create(RepairInterface::class);
        $auditBatch = $audit->getAuditBatch();

        $account = $auditBatch->getAccount();
        $repair->setAccount($account);
        $repair->setContact($auditBatch->getContact());
        $repair->setWorkcenter($auditBatch->getWorkcenter());
        $repair->setInvoiceAddress($auditBatch->getInvoiceAddress());
        $repair->setShippingAddress($auditBatch->getShippingAddress());
        $repair->setProduct($audit->getProduct());
        $repair->setProductCombination($audit->getProductCombination());
        $repair->setRepairer($audit->getAuditor());
        $repair->setOwner($auditBatch->getAccount()->getOwner());
        $repair->setDevice($audit->getDevice());
        $repair->setStatus($this->choiceManager->getChoice('repair_status', 'received'));

        if ($repair instanceof RepairAuditableInterface) {
            $repair->setAuditItem($audit);
        }

        if ($account instanceof BuybackModuleableInterface && null !== ($module = $account->getBuybackModule())) {
            $repair->setPriceList($module->getRepairPriceList());
        }

        return $repair;
    }
}
