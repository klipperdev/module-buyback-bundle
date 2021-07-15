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
use Klipper\Component\Content\ContentManagerInterface;
use Klipper\Component\DoctrineChoice\ChoiceManagerInterface;
use Klipper\Component\Export\Exception\InvalidFormatException;
use Klipper\Component\Import\Choice\ImportStatus;
use Klipper\Component\Import\Model\ImportInterface;
use Klipper\Component\Metadata\MetadataManagerInterface;
use Klipper\Component\Resource\Domain\DomainManagerInterface;
use Klipper\Component\Resource\Object\ObjectFactoryInterface;
use Klipper\Component\Security\Permission\PermVote;
use Klipper\Component\SecurityOauth\Scope\ScopeVote;
use Klipper\Module\BuybackBundle\Import\Adapter\AuditItemConfirmationImportAdapter;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\Traits\BuybackModuleableInterface;
use Klipper\Module\BuybackBundle\Model\Traits\RepairAuditableInterface;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ApiAuditItemController
{
    /**
     * Import the audit item for confirmation.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/import-confirmation.{ext}", methods={"GET"}, requirements={"ext": "csv|ods|xls|xlsx"})
     */
    public function downloadImportModelAction(
        ControllerHelper $helper,
        TranslatorInterface $translator,
        DomainManagerInterface $domainManager,
        AccountInterface $id,
        string $ext
    ): Response {
        $auditItemClass = $domainManager->get(AuditItemInterface::class)->getClass();

        if (!$helper->isGranted(new PermVote('create'), $auditItemClass)
            || !$helper->isGranted(new PermVote('update'), $auditItemClass)
            || !$helper->isGranted(new PermVote('import'))
        ) {
            throw $helper->createAccessDeniedException();
        }

        try {
            $filename = sprintf('Audit import confirmation template %s.%s', $id->getName(), $ext);
            $spreadsheet = new Spreadsheet();
            $writer = IOFactory::createWriter($spreadsheet, ucfirst($ext));
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValueByColumnAndRow(1, 1, 'audit_request_reference');
            $sheet->setCellValueByColumnAndRow(2, 1, 'device_imei');
            $sheet->setCellValueByColumnAndRow(3, 1, 'product_reference');
            $sheet->setCellValueByColumnAndRow(4, 1, 'product_combination_reference');
            $sheet->setCellValueByColumnAndRow(5, 1, 'condition_name');

            $sheet->getColumnDimensionByColumn(1)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn(2)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn(3)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn(4)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn(5)->setAutoSize(true);

            $response = new StreamedResponse(
                static function () use ($writer): void {
                    $writer->save('php://output');
                }
            );

            $response->setPrivate();
            $response->headers->addCacheControlDirective('no-cache', true);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->set('Content-Type', MimeTypes::getDefault()->getMimeTypes($ext));
            $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename.'"');

            return $response;
        } catch (InvalidFormatException $e) {
            throw new BadRequestHttpException($translator->trans('klipper_api_export.invalid_format', [
                'format' => $ext,
            ], 'exceptions'), $e);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException($translator->trans('klipper_api_export.error', [], 'exceptions'), $e);
        }
    }

    /**
     * Import the audit item for confirmation.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/import-confirmation", methods={"POST"})
     */
    public function importAction(
        ControllerHelper $helper,
        ContentManagerInterface $contentManager,
        MetadataManagerInterface $metadataManager,
        DomainManagerInterface $domainManager,
        ObjectFactoryInterface $objectFactory,
        AccountInterface $id
    ): Response {
        $auditItemClass = $domainManager->get(AuditItemInterface::class)->getClass();

        if (!$helper->isGranted(new PermVote('create'), $auditItemClass)
            || !$helper->isGranted(new PermVote('update'), $auditItemClass)
            || !$helper->isGranted(new PermVote('import'))
        ) {
            throw $helper->createAccessDeniedException();
        }

        /** @var ImportInterface $import */
        $import = $objectFactory->create('App:Import');
        $import->setStatus(current(ImportStatus::getValues()));
        $import->setAdapter(AuditItemConfirmationImportAdapter::class);
        $import->setMetadata($metadataManager->get(AuditItemInterface::class)->getName());
        $import->setExtra([
            'qualification' => true,
            'account_id' => $id->getId(),
        ]);

        $contentManager->upload('import', $import);

        return $helper->view($import);
    }

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
