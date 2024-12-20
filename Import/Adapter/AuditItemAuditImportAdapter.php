<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Import\Adapter;

use Klipper\Component\Import\Adapter\StandardImportAdapter;
use Klipper\Component\Import\ImportContextInterface;
use Klipper\Component\Resource\ResourceInterface;
use Klipper\Module\BuybackBundle\Audit\AuditManagerInterface;
use Klipper\Module\BuybackBundle\Form\Type\ImportAuditItemAuditType;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditItemAuditImportAdapter extends StandardImportAdapter
{
    private AuditManagerInterface $auditManager;

    public function __construct(AuditManagerInterface $auditManager)
    {
        $this->auditManager = $auditManager;
    }

    public function validate(ImportContextInterface $context): bool
    {
        $class = $context->getDomainTarget()->getClass();
        $extra = $context->getImport()->getExtra();

        return is_a($class, AuditItemInterface::class, true)
            && isset($extra['audit'])
            && $extra['audit']
            && isset($extra['account_id'])
        ;
    }

    protected function buildData(ImportContextInterface $context, Row $row): array
    {
        $validColumns = [
            'audit_batch_reference',
            'device_imei_or_sn',
            'product_reference',
            'product_combination_reference',
            'condition_name',
            'repair_declared_breakdown_by_customer',
            'comment',
        ];
        $sheet = $context->getActiveSheet();
        $rowIndex = $row->getRowIndex();
        $data = [];

        foreach ($context->getMappingColumns() as $column => $colIndex) {
            if (\in_array($column, $validColumns, true)) {
                $val = $sheet->getCellByColumnAndRow($colIndex, $rowIndex)->getValue();

                if (null !== $val) {
                    $data[$column] = $val;
                }
            }
        }

        return $data;
    }

    protected function findObject(ImportContextInterface $context, $id, array $data): ?object
    {
        $domainTarget = $context->getDomainTarget();
        $accountId = $context->getImport()->getExtra()['account_id'] ?? null;

        // Find audit item with the identifier
        if (null !== $id) {
            return $domainTarget->getRepository()->find($id);
        }

        // Find audit item with the same value
        $items = $domainTarget->getRepository()
            ->createQueryBuilder('ai')
            ->join('ai.auditBatch', 'ab')
            ->join('ai.status', 'cs')
            ->leftJoin('ai.device', 'd')
            ->leftJoin('ai.product', 'p')
            ->leftJoin('ai.productCombination', 'pc')
            ->where('ab.account = :account')
            ->andWhere('ab.reference = :auditBatchReference')
            ->andWhere('cs.value = :statusValue')
            ->andWhere('(d.imei = :device OR d.serialNumber = :device) OR (ai.device is null AND p.reference = :product AND pc.reference = :productCombination)')
            ->orderBy('ai.device', 'desc')
            ->addOrderBy('ai.createdAt', 'asc')
            ->setMaxResults(1)
            ->setParameter('account', $accountId)
            ->setParameter('auditBatchReference', $data['audit_batch_reference'] ?? null)
            ->setParameter('statusValue', 'qualified')
            ->setParameter('device', $data['device_imei_or_sn'] ?? null)
            ->setParameter('product', $data['product_reference'] ?? null)
            ->setParameter('productCombination', $data['product_combination_reference'] ?? null)
            ->getQuery()
            ->getResult()
        ;

        if (\count($items) > 0) {
            return $items[0];
        }

        // Find empty audit item but with a product
        $items = $domainTarget->getRepository()
            ->createQueryBuilder('ai')
            ->join('ai.auditBatch', 'ab')
            ->join('ai.status', 'cs')
            ->where('ab.account = :account')
            ->andWhere('ab.reference = :auditBatchReference')
            ->andWhere('cs.value = :statusValue')
            ->andWhere('ai.device IS NULL AND ai.product IS NOT NULL AND ai.productCombination IS NULL')
            ->orderBy('ai.createdAt', 'asc')
            ->setMaxResults(1)
            ->setParameter('account', $accountId)
            ->setParameter('auditBatchReference', $data['audit_batch_reference'] ?? null)
            ->setParameter('statusValue', 'qualified')
            ->getQuery()
            ->getResult()
        ;

        if (\count($items) > 0) {
            return $items[0];
        }

        return null;
    }

    protected function createForm(ImportContextInterface $context, object $object): FormInterface
    {
        $metaTarget = $context->getMetadataTarget();
        $formFactory = $context->getFormFactory();
        $formType = ImportAuditItemAuditType::class;
        $formOptions = [
            'csrf_protection' => false,
            'data_class' => $metaTarget->getClass(),
        ];

        return $formFactory->create($formType, $object, $formOptions);
    }

    protected function hookAfterUpsert(ImportContextInterface $context, ResourceInterface $result): void
    {
        if (!$result->isValid()
            || !$result->isForm()
            || null === $result->getData()->get('repair_declared_breakdown_by_customer')->getData()
        ) {
            return;
        }

        $translator = $context->getTranslator();
        $em = $context->getDomainManager()->get(RepairInterface::class)->getObjectManager();
        $form = $result->getData();
        $data = $form->getData();
        $declaredBreakdownByCustomer = $result->getData()->get('repair_declared_breakdown_by_customer')->getData();

        $repair = $this->auditManager->transferToRepair($data);
        $repair->setDeclaredBreakdownByCustomer($declaredBreakdownByCustomer);

        try {
            $em->persist($repair);
            $em->flush();
        } catch (\Throwable $e) {
            $errMess = $translator->trans('domain.database_error', [], 'KlipperResource');
            $form->addError(
                new FormError($errMess, $errMess, [], null, $e)
            );
        }
    }
}
