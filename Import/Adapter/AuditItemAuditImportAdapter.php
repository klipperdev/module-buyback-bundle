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
use Klipper\Module\BuybackBundle\Form\Type\ImportAuditItemAuditType;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use Symfony\Component\Form\FormInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditItemAuditImportAdapter extends StandardImportAdapter
{
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
            'audit_request_reference',
            'device_imei_or_sn',
            'product_reference',
            'product_combination_reference',
            'condition_name',
            'repair_declared_breakdown_by_customer',
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
            ->where('ar.account = :account')
            ->andWhere('cs.value = :statusValue')
            ->andWhere('(d.imei = :device OR d.serialNumber = :device) OR (p.reference = :product AND pc.reference = :productCombination)')
            ->orderBy('ai.device', 'desc')
            ->addOrderBy('ai.createdAt', 'asc')
            ->setMaxResults(1)
            ->setParameter('account', $accountId)
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
            ->where('ar.account = :account')
            ->andWhere('cs.value = :statusValue')
            ->andWhere('ai.device IS NULL AND ai.product IS NOT NULL AND ai.productCombination IS NULL')
            ->orderBy('ai.createdAt', 'asc')
            ->setMaxResults(1)
            ->setParameter('account', $accountId)
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
}
