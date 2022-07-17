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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Klipper\Bundle\ApiBundle\Action\Upsert;
use Klipper\Bundle\ApiBundle\Controller\ControllerHelper;
use Klipper\Component\Content\ContentManagerInterface;
use Klipper\Component\Export\Exception\InvalidFormatException;
use Klipper\Component\Import\Choice\ImportStatus;
use Klipper\Component\Import\Model\ImportInterface;
use Klipper\Component\Metadata\MetadataManagerInterface;
use Klipper\Component\Resource\Converter\ConverterRegistryInterface;
use Klipper\Component\Resource\Domain\DomainInterface;
use Klipper\Component\Resource\Domain\DomainManagerInterface;
use Klipper\Component\Resource\Object\ObjectFactoryInterface;
use Klipper\Component\Security\Permission\PermVote;
use Klipper\Component\SecurityOauth\Scope\ScopeVote;
use Klipper\Module\BuybackBundle\Audit\AuditManagerInterface;
use Klipper\Module\BuybackBundle\Form\Type\AuditItemAuditType;
use Klipper\Module\BuybackBundle\Form\Type\AuditItemQualificationType;
use Klipper\Module\BuybackBundle\Import\Adapter\AuditItemAuditImportAdapter;
use Klipper\Module\BuybackBundle\Import\Adapter\AuditItemQualificationImportAdapter;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\BuybackOfferInterface;
use Klipper\Module\BuybackBundle\Model\Traits\AuditRepairableInterface;
use Klipper\Module\BuybackBundle\Representation\AuditItemAudit;
use Klipper\Module\BuybackBundle\Representation\AuditItemQualification;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
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
     * Upsert the audit item for qualification.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/qualification", methods={"POST"})
     */
    public function singleQualificationAction(
        Request $request,
        TranslatorInterface $translator,
        ConverterRegistryInterface $converterRegistry,
        FormFactoryInterface $formFactory,
        DomainManagerInterface $domainManager,
        AuditManagerInterface $auditManager,
        ControllerHelper $helper,
        AccountInterface $id
    ): Response {
        return $this->findAndUpsertAudit(
            $request,
            $translator,
            $converterRegistry,
            $formFactory,
            $domainManager,
            $auditManager,
            $helper,
            $id,
            true
        );
    }

    /**
     * Upsert the audit item for audit.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/audit", methods={"POST"})
     */
    public function singleAuditAction(
        Request $request,
        TranslatorInterface $translator,
        ConverterRegistryInterface $converterRegistry,
        FormFactoryInterface $formFactory,
        DomainManagerInterface $domainManager,
        AuditManagerInterface $auditManager,
        ControllerHelper $helper,
        AccountInterface $id
    ): Response {
        return $this->findAndUpsertAudit(
            $request,
            $translator,
            $converterRegistry,
            $formFactory,
            $domainManager,
            $auditManager,
            $helper,
            $id,
            false
        );
    }

    /**
     * Download the import template file for the qualification.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/import-qualification.{ext}", methods={"GET"}, requirements={"ext": "csv|ods|xls|xlsx"})
     */
    public function downloadImportQualificationTemplateAction(
        ControllerHelper $helper,
        TranslatorInterface $translator,
        DomainManagerInterface $domainManager,
        AccountInterface $id,
        string $ext
    ): Response {
        return $this->downloadImportTemplateAction(
            $helper,
            $translator,
            $domainManager,
            $ext,
            sprintf('Audit import qualification template %s.%s', $id->getName(), $ext)
        );
    }

    /**
     * Import the audit item for qualification.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/import-qualification", methods={"POST"})
     */
    public function importQualificationAction(
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
        $import->setAdapter(AuditItemQualificationImportAdapter::class);
        $import->setMetadata($metadataManager->get(AuditItemInterface::class)->getName());
        $import->setExtra([
            'qualification' => true,
            'account_id' => $id->getId(),
        ]);

        $contentManager->upload('import', $import);

        return $helper->view($import);
    }

    /**
     * Download the import template file for the audit.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/import-audit.{ext}", methods={"GET"}, requirements={"ext": "csv|ods|xls|xlsx"})
     */
    public function downloadImportAuditTemplateAction(
        ControllerHelper $helper,
        TranslatorInterface $translator,
        DomainManagerInterface $domainManager,
        AccountInterface $id,
        string $ext
    ): Response {
        return $this->downloadImportTemplateAction(
            $helper,
            $translator,
            $domainManager,
            $ext,
            sprintf('Audit import audit template %s.%s', $id->getName(), $ext)
        );
    }

    /**
     * Import the audit item for audit.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/import-audit", methods={"POST"})
     */
    public function importAuditAction(
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
        $import->setAdapter(AuditItemAuditImportAdapter::class);
        $import->setMetadata($metadataManager->get(AuditItemInterface::class)->getName());
        $import->setExtra([
            'audit' => true,
            'account_id' => $id->getId(),
        ]);

        $contentManager->upload('import', $import);

        return $helper->view($import);
    }

    /**
     * List the available products to create a buyback offer.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/buyback-offer/available-products", methods={"GET"})
     */
    public function listProductAction(
        ControllerHelper $helper,
        EntityManagerInterface $em,
        AccountInterface $id
    ): Response {
        $concatCombination = 'GROUP_CONCAT(DISTINCT CONCAT_WS(\' : \', pcaia.label, pcai.label) ORDER BY pcai.id ASC SEPARATOR \' • \')';

        $qb = $em->createQueryBuilder()
            ->select('CONCAT_WS(\'@\', p.id, pc.id) as id')
            ->addSelect('p.id as product_id')
            ->addSelect('pc.id as product_combination_id')
            ->addSelect('b.name as product_brand_name')
            ->addSelect('p.name as product_name')
            ->addSelect('CASE WHEN ai.productCombination IS NOT NULL THEN '.$concatCombination.' ELSE :null END as product_combination_name')

            ->from(AuditItemInterface::class, 'ai')
            ->join('ai.auditBatch', 'ab')
            ->join('ai.status', 'ais')
            ->join('ai.product', 'p')
            ->leftJoin('ai.productCombination', 'pc')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('pc.attributeItems', 'pcai')
            ->leftJoin('pcai.attribute', 'pcaia')

            ->where('ab.account = :account')
            ->andWhere('ab.supplierOrderNumber IS NOT NULL')
            ->andWhere('ai.buybackOffer IS NULL')
            ->andWhere('ais.value = :status')

            ->groupBy('p.id')
            ->addGroupBy('pc.id')

            ->orderBy('b.name')
            ->addOrderBy('p.name')

            ->setParameter('account', $id)
            ->setParameter('status', 'audited')
            ->setParameter('null', null)
        ;

        return $helper->views($qb);
    }

    /**
     * List the available audit conditions to create a buyback offer.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/buyback-offer/available-conditions", methods={"GET"})
     */
    public function listAuditConditionAction(
        Request $request,
        ControllerHelper $helper,
        EntityManagerInterface $em,
        AccountInterface $id
    ): Response {
        $qb = $em->createQueryBuilder()
            ->select('ac.id as id')
            ->addSelect('ac.label')
            ->addSelect('ac.name')

            ->from(AuditItemInterface::class, 'ai')
            ->join('ai.auditBatch', 'ab')
            ->join('ai.auditCondition', 'ac')
            ->join('ai.status', 'ais')

            ->where('ab.account = :account')
            ->andWhere('ab.supplierOrderNumber IS NOT NULL')
            ->andWhere('ai.buybackOffer IS NULL')
            ->andWhere('ais.value = :status')

            ->groupBy('ac.id')

            ->orderBy('ac.label')

            ->setParameter('account', $id)
            ->setParameter('status', 'audited')
        ;

        $this->filterAvailableQueryByProducts($request, $qb);

        return $helper->views($qb);
    }

    /**
     * List the available audit conditions to create a buyback offer.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/buyback-offer/available-supplier-order-numbers", methods={"GET"})
     */
    public function listAuditSupplierOrderNumberAction(
        Request $request,
        ControllerHelper $helper,
        EntityManagerInterface $em,
        AccountInterface $id
    ): Response {
        $qb = $em->createQueryBuilder()
            ->select('DISTINCT ab.supplierOrderNumber as id')
            ->addSelect('ab.supplierOrderNumber as label')

            ->from(AuditItemInterface::class, 'ai')
            ->join('ai.auditBatch', 'ab')
            ->join('ai.status', 'ais')

            ->where('ab.account = :account')
            ->andWhere('ab.supplierOrderNumber IS NOT NULL')
            ->andWhere('ai.auditCondition IS NOT NULL')
            ->andWhere('ai.buybackOffer IS NULL')
            ->andWhere('ais.value = :status')

            ->groupBy('ab.supplierOrderNumber')

            ->orderBy('ab.supplierOrderNumber')

            ->setParameter('account', $id)
            ->setParameter('status', 'audited')
        ;

        $this->filterAvailableQueryByProducts($request, $qb);
        $this->filterAvailableQueryByConditions($request, $qb);

        return $helper->views($qb);
    }

    /**
     * List the available audits to create a buyback offer.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/buyback-offer/available-audits", methods={"GET"})
     */
    public function listAvailableAuditForBuybackOfferAction(
        Request $request,
        ControllerHelper $helper,
        EntityManagerInterface $em,
        AccountInterface $id
    ): Response {
        $qb = $em->getRepository(AuditItemInterface::class)
            ->createQueryBuilder('ai')
            ->join('ai.auditBatch', 'ab')
            ->join('ai.status', 'cs')

            ->where('ab.account = :account')
            ->andWhere('ab.supplierOrderNumber IS NOT NULL')
            ->andWhere('ai.auditCondition IS NOT NULL')
            ->andWhere('ai.buybackOffer IS NULL')
            ->andWhere('cs.value = :status')

            ->setParameter('account', $id)
            ->setParameter('status', 'audited')
        ;

        $this->filterAvailableQueryByProducts($request, $qb, false);
        $this->filterAvailableQueryByConditions($request, $qb);
        $this->filterAvailableQueryBySupplierOrderNumbers($request, $qb);
        $this->filterAvailableQueryByAuditItems($request, $qb);

        return $helper->views($qb);
    }

    /**
     * Add selected audit items to an existing buyback offer.
     *
     * @Entity("id", class="App:Account")
     * @Entity("offerId", class="App:BuybackOffer")
     *
     * @Route("/audit_items/accounts/{id}/buyback-offer/{offerId}", methods={"PATCH"})
     */
    public function addAuditsToExistingBuybackOfferAction(
        Request $request,
        ControllerHelper $helper,
        ObjectFactoryInterface $objectFactory,
        DomainManagerInterface $domainManager,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        AccountInterface $id,
        BuybackOfferInterface $offerId
    ): Response {
        return $this->addAuditsToNewOrExistingBuybackOfferAction(
            $request,
            $helper,
            $objectFactory,
            $domainManager,
            $translator,
            $em,
            $id,
            $offerId
        );
    }

    /**
     * Add selected audit items to a new buyback offer.
     *
     * @Entity("id", class="App:Account")
     *
     * @Route("/audit_items/accounts/{id}/buyback-offer", methods={"POST"})
     */
    public function addAuditsToNewBuybackOfferAction(
        Request $request,
        ControllerHelper $helper,
        ObjectFactoryInterface $objectFactory,
        DomainManagerInterface $domainManager,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        AccountInterface $id
    ): Response {
        return $this->addAuditsToNewOrExistingBuybackOfferAction(
            $request,
            $helper,
            $objectFactory,
            $domainManager,
            $translator,
            $em,
            $id
        );
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
        AuditManagerInterface $auditManager,
        AuditItemInterface $id
    ): Response {
        if (class_exists(ScopeVote::class)) {
            $helper->denyAccessUnlessGranted(new ScopeVote('meta/audit_item'));
        }

        $action = Upsert::build('', $auditManager->transferToRepair($id))
            ->setProcessForm(false)
        ;

        return $helper->upsert($action);
    }

    public function downloadImportTemplateAction(
        ControllerHelper $helper,
        TranslatorInterface $translator,
        DomainManagerInterface $domainManager,
        string $ext,
        string $filename
    ): Response {
        $auditItemClass = $domainManager->get(AuditItemInterface::class)->getClass();

        if (!$helper->isGranted(new PermVote('create'), $auditItemClass)
            || !$helper->isGranted(new PermVote('update'), $auditItemClass)
            || !$helper->isGranted(new PermVote('import'))
        ) {
            throw $helper->createAccessDeniedException();
        }

        try {
            $spreadsheet = new Spreadsheet();
            $writer = IOFactory::createWriter($spreadsheet, ucfirst($ext));
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValueByColumnAndRow(1, 1, 'audit_batch_reference');
            $sheet->setCellValueByColumnAndRow(2, 1, 'device_imei_or_sn');
            $sheet->setCellValueByColumnAndRow(3, 1, 'product_reference');
            $sheet->setCellValueByColumnAndRow(4, 1, 'product_combination_reference');
            $sheet->setCellValueByColumnAndRow(5, 1, 'condition_name');
            $sheet->setCellValueByColumnAndRow(6, 1, 'repair_declared_breakdown_by_customer');
            $sheet->setCellValueByColumnAndRow(7, 1, 'comment');

            $sheet->getColumnDimensionByColumn(1)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn(2)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn(3)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn(4)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn(5)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn(6)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn(7)->setAutoSize(true);

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

    private function filterAvailableQueryByProducts(Request $request, QueryBuilder $qb, bool $addJoins = true): void
    {
        $productIds = (array) $request->query->get('p', []);

        if (!empty($productIds)) {
            $filterExpr = [];

            if ($addJoins) {
                $qb
                    ->join('ai.product', 'p')
                    ->leftJoin('ai.productCombination', 'pc')
                ;
            }

            foreach ($productIds as $i => $productId) {
                $split = explode('@', $productId);
                $combinationId = $split[1] ?? null;
                $productId = $split[0];

                if (null !== $combinationId) {
                    $filterExpr[] = 'p.id = :product_'.$i.' AND pc.id = :combination_'.$i;
                    $qb->setParameter('product_'.$i, $productId);
                    $qb->setParameter('combination_'.$i, $combinationId);
                } else {
                    $filterExpr[] = 'p.id = :product_'.$i;
                    $qb->setParameter('product_'.$i, $productId);
                }
            }

            if (!empty($filterExpr)) {
                $qb->andWhere($qb->expr()->orX(...$filterExpr));
            }
        }
    }

    private function filterAvailableQueryByConditions(Request $request, QueryBuilder $qb): void
    {
        $conditionIds = (array) $request->query->get('c', []);

        if (!empty($conditionIds)) {
            $filterExpr = [];

            foreach ($conditionIds as $i => $conditionId) {
                $filterExpr[] = 'ai.auditCondition = :condition_'.$i;
                $qb->setParameter('condition_'.$i, $conditionId);
            }

            if (!empty($filterExpr)) {
                $qb->andWhere($qb->expr()->orX(...$filterExpr));
            }
        }
    }

    private function filterAvailableQueryBySupplierOrderNumbers(Request $request, QueryBuilder $qb): void
    {
        $references = (array) $request->query->get('ref', []);

        if (!empty($references)) {
            $filterExpr = [];

            foreach ($references as $i => $reference) {
                $filterExpr[] = 'ab.supplierOrderNumber = :supplierOrderNumber_'.$i;
                $qb->setParameter('supplierOrderNumber_'.$i, $reference);
            }

            if (!empty($filterExpr)) {
                $qb->andWhere($qb->expr()->orX(...$filterExpr));
            }
        }
    }

    private function filterAvailableQueryByAuditItems(Request $request, QueryBuilder $qb): void
    {
        $auditIds = (array) $request->query->get('aid', []);

        if (!empty($auditIds)) {
            $qb
                ->andWhere('ai.id IN (:auditIds)')
                ->setParameter('auditIds', $auditIds)
            ;
        }
    }

    /**
     * Add selected audit items to an existing buyback offer or a new.
     */
    private function addAuditsToNewOrExistingBuybackOfferAction(
        Request $request,
        ControllerHelper $helper,
        ObjectFactoryInterface $objectFactory,
        DomainManagerInterface $domainManager,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        AccountInterface $id,
        ?BuybackOfferInterface $buybackOffer = null
    ): Response {
        $qb = $em->createQueryBuilder()
            ->select('ai')

            ->from(AuditItemInterface::class, 'ai')
            ->join('ai.auditBatch', 'ab')
            ->join('ai.status', 'cs')

            ->where('ab.account = :account')
            ->andWhere('ab.supplierOrderNumber IS NOT NULL')
            ->andWhere('ai.auditCondition IS NOT NULL')
            ->andWhere('ai.buybackOffer IS NULL')
            ->andWhere('cs.value = :status')

            ->setParameter('account', $id)
            ->setParameter('status', 'audited')
        ;

        $this->filterAvailableQueryByProducts($request, $qb);
        $this->filterAvailableQueryByConditions($request, $qb);
        $this->filterAvailableQueryBySupplierOrderNumbers($request, $qb);
        $this->filterAvailableQueryByAuditItems($request, $qb);

        /** @var AuditItemInterface[] $audits */
        $audits = $qb->getQuery()->getResult();
        $isCreate = null === $buybackOffer;

        if (empty($audits)) {
            throw new BadRequestHttpException($translator->trans('klipper_buyback.buyback_offer.create.no_audit_selected', [], 'validators'));
        }

        if (null === $buybackOffer) {
            $firstAudit = $audits[0];
            $firstAuditBatch = $firstAudit->getAuditBatch();

            /** @var BuybackOfferInterface $buybackOffer */
            $buybackOffer = $objectFactory->create(BuybackOfferInterface::class);
            $buybackOffer->setShippingAddress($firstAuditBatch->getShippingAddress());
            $buybackOffer->setInvoiceAddress($firstAuditBatch->getInvoiceAddress());
            $buybackOffer->setAccount($firstAuditBatch->getAccount());
            $buybackOffer->setSupplier($firstAuditBatch->getSupplier());
            $buybackOffer->setExpirationDate((new \DateTime())->add(new \DateInterval('P14D')));
            $buybackOffer->setContact($firstAuditBatch->getContact());
        }

        $domain = $domainManager->get(AuditItemInterface::class);

        foreach ($audits as $audit) {
            $audit->setBuybackOffer($buybackOffer);
        }

        $res = $domain->updates($audits);

        if ($res->hasErrors()) {
            $data = $helper->formatResultList($res, true);
            $view = $helper->createView($data, Response::HTTP_BAD_REQUEST);
        } else {
            $view = $helper->createView($buybackOffer, $isCreate ? Response::HTTP_CREATED : Response::HTTP_OK);
        }

        return $helper->handleView($view);
    }

    private function findAndUpsertAudit(
        Request $request,
        TranslatorInterface $translator,
        ConverterRegistryInterface $converterRegistry,
        FormFactoryInterface $formFactory,
        DomainManagerInterface $domainManager,
        AuditManagerInterface $auditManager,
        ControllerHelper $helper,
        AccountInterface $id,
        bool $isQualification
    ): Response {
        $domain = $domainManager->get(AuditItemInterface::class);
        $domainRepair = $domainManager->get(RepairInterface::class);
        $formType = $isQualification ? AuditItemQualificationType::class : AuditItemAuditType::class;
        $form = $formFactory->create($formType);
        $data = $converterRegistry->get('json')->convert((string) $request->getContent());

        $form->submit($data, false);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AuditItemQualification $formData */
            $formData = $form->getData();
            $audit = $this->findObjectForAudit($domain, $id, $formData);

            if (null === $audit) {
                $form->addError(new FormError(
                    $translator->trans('domain.resource_does_not_exist', [], 'KlipperResource')
                ));

                return $helper->view($helper->createView(
                    $helper->formatFormErrors($form),
                    Response::HTTP_BAD_REQUEST
                ));
            }

            if (null !== $formData->getAuditBatch() && $formData->getAuditBatch() !== $audit->getAuditBatch()) {
                $audit->setAuditBatch($formData->getAuditBatch());
            }

            if (null !== $formData->getAuditCondition() && $formData->getAuditCondition() !== $audit->getAuditCondition()) {
                $audit->setAuditCondition($formData->getAuditCondition());
            }

            if (null !== $formData->getComment() && $formData->getComment() !== $audit->getComment()) {
                $audit->setComment($formData->getComment());
            }

            if (null !== $formData->getDevice() && $formData->getDevice() !== $audit->getDevice()) {
                $audit->setDevice($formData->getDevice());
            }

            if (null !== $formData->getProduct() && $formData->getProduct() !== $audit->getProduct()) {
                $audit->setProduct($formData->getProduct());
            }

            if (null !== $formData->getProductCombination() && $formData->getProductCombination() !== $audit->getProductCombination()) {
                $audit->setProductCombination($formData->getProductCombination());
            }

            // Save the upsert
            $res = $domain->upsert($audit);

            if (!$res->isValid()) {
                return $helper->view($helper->createView(
                    $helper->mergeAllErrors($res),
                    Response::HTTP_BAD_REQUEST
                ));
            }

            // Transfer to repair
            if (null !== $formData->getRepairDeclaredBreakdownByCustomer()
                && $audit instanceof AuditRepairableInterface
                && null === $audit->getRepair()
                && null !== $audit->getDevice()
            ) {
                $repair = $auditManager->transferToRepair($audit);
                $repair->setDeclaredBreakdownByCustomer($formData->getRepairDeclaredBreakdownByCustomer());
                $resRepair = $domainRepair->upsert($repair);

                if ($resRepair->isValid()) {
                    return $helper->view($audit);
                }

                return $helper->view($helper->createView(
                    $helper->mergeAllErrors($resRepair),
                    Response::HTTP_BAD_REQUEST
                ));
            }

            return $helper->view($audit);
        }

        return $helper->view($helper->createView(
            $helper->formatFormErrors($form),
            Response::HTTP_BAD_REQUEST
        ));
    }

    private function findObjectForAudit(DomainInterface $domainTarget, ?AccountInterface $account, AuditItemQualification $data): ?AuditItemInterface
    {
        // Find audit item with the identifier
        if (null !== $data->getId()) {
            return $domainTarget->getRepository()->find($data->getId());
        }

        // Find audit item with the same value
        /** @var AuditItemInterface[] $items */
        $items = $domainTarget->getRepository()
            ->createQueryBuilder('ai')
            ->join('ai.auditBatch', 'ab')
            ->join('ai.status', 'cs')
            ->leftJoin('ai.device', 'd')
            ->leftJoin('ai.product', 'p')
            ->leftJoin('ai.productCombination', 'pc')
            ->where('ab.account = :account')
            ->andWhere('cs.value = :statusValue')
            ->andWhere('d.id = :device OR (ai.device is null AND p.id = :product AND pc.id = :productCombination)')
            ->andWhere('ai.auditBatch = :auditBatch')
            ->orderBy('ai.createdAt', 'asc')
            ->setMaxResults(1)
            ->setParameter('account', $account)
            ->setParameter('statusValue', 'confirmed')
            ->setParameter('device', $data->getDevice())
            ->setParameter('product', $data->getProduct())
            ->setParameter('productCombination', $data->getProductCombination())
            ->setParameter('auditBatch', $data->getAuditBatch())
            ->getQuery()
            ->getResult()
        ;

        if (\count($items) > 0) {
            return $items[0];
        }

        // Find empty audit item
        $items = $domainTarget->getRepository()
            ->createQueryBuilder('ai')
            ->join('ai.auditBatch', 'ab')
            ->join('ai.status', 'cs')
            ->where('ab.account = :account')
            ->andWhere('cs.value = :statusValue')
            ->andWhere('ai.device IS NULL AND ai.product IS NULL AND ai.productCombination IS NULL')
            ->andWhere('ai.auditBatch = :auditBatch')
            ->orderBy('ai.createdAt', 'asc')
            ->setMaxResults(1)
            ->setParameter('account', $account)
            ->setParameter('statusValue', $data instanceof AuditItemAudit ? 'qualified' : 'confirmed')
            ->setParameter('auditBatch', $data->getAuditBatch())
            ->getQuery()
            ->getResult()
        ;

        if (\count($items) > 0) {
            return $items[0];
        }

        // Return null or create new audit item
        if ($data instanceof AuditItemAudit) {
            $item = null;
        } else {
            /** @var AuditItemInterface $item */
            $item = $domainTarget->newInstance();
        }

        return $item;
    }
}
