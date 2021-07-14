<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Controller;

use Klipper\Bundle\ApiBundle\Action\Upsert;
use Klipper\Bundle\ApiBundle\Controller\ControllerHelper;
use Klipper\Component\DoctrineChoice\ChoiceManagerInterface;
use Klipper\Component\Resource\Object\ObjectFactoryInterface;
use Klipper\Component\SecurityOauth\Scope\ScopeVote;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\Traits\BuybackModuleableInterface;
use Klipper\Module\BuybackBundle\Model\Traits\RepairAuditableInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ApiAuditItemController
{
    /**
     * Create a repair for an audit item.
     *
     * @Entity("id", class="App:AuditItem")
     *
     * @Route("/audit_items/{id}/transfer-repair", methods={"PUT"})
     */
    public function transferRepair(
        ControllerHelper $helper,
        ObjectFactoryInterface $objectFactory,
        ChoiceManagerInterface $choiceManager,
        AuditItemInterface $id
    ): Response {
        if (class_exists(ScopeVote::class)) {
            $helper->denyAccessUnlessGranted(new ScopeVote('meta/audit_item'));
        }

        /** @var RepairInterface $repair */
        $repair = $objectFactory->create(RepairInterface::class);
        $account = $id->getAuditRequest()->getAccount();
        $repair->setAccount($account);
        $repair->setContact($id->getAuditRequest()->getContact());
        $repair->setWorkcenter($id->getAuditRequest()->getWorkcenter());
        $repair->setInvoiceAddress($id->getAuditRequest()->getInvoiceAddress());
        $repair->setShippingAddress($id->getAuditRequest()->getShippingAddress());
        $repair->setProduct($id->getProduct());
        $repair->setProductCombination($id->getProductCombination());
        $repair->setRepairer($id->getAuditor());
        $repair->setOwner($id->getAuditRequest()->getAccount()->getOwner());
        $repair->setDevice($id->getDevice());
        $repair->setStatus($choiceManager->getChoice('repair_status', 'received'));

        if ($repair instanceof RepairAuditableInterface) {
            $repair->setAuditItem($id);
        }

        if ($account instanceof BuybackModuleableInterface && null !== ($module = $account->getBuybackModule())) {
            $repair->setPriceList($module->getRepairPriceList());
        }

        $action = Upsert::build('', $repair)->setProcessForm(false);

        return $helper->upsert($action);
    }
}
