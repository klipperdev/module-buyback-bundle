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
use Klipper\Bundle\ApiBundle\Controller\ControllerHelper;
use Klipper\Component\DoctrineExtensionsExtra\Util\ListenerUtil;
use Klipper\Component\Resource\Domain\DomainManagerInterface;
use Klipper\Module\BuybackBundle\Model\AuditBatchInterface;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ApiAuditBatchController
{
    /**
     * Convert the Audit Batch Request Items into Audit Items.
     *
     * @Entity("id", class="App:AuditBatch")
     *
     * @Route("/audit_batches/{id}/convert-request-to-audit-items", methods={"PUT"})
     */
    public function convertToAuditItemsAction(
        DomainManagerInterface $domainManager,
        EntityManagerInterface $em,
        ControllerHelper $helper,
        AuditBatchInterface $id
    ): Response {
        if (!$id->isConverted()) {
            $em->getConnection()->beginTransaction();

            try {
                $id->setConverted(true);
                $em->persist($id);

                $auditItemDomain = $domainManager->get(AuditItemInterface::class);

                foreach ($id->getRequestItems() as $requestItem) {
                    for ($i = 0; $i < (int) $requestItem->getReceivedQuantity(); ++$i) {
                        /** @var AuditItemInterface $auditItem */
                        $auditItem = $auditItemDomain->newInstance();
                        $auditItem->setAuditBatch($id);
                        $auditItem->setProduct($requestItem->getProduct());
                        $auditItem->setProductCombination($requestItem->getProductCombination());
                        $auditItem->setReceiptedAt($id->getReceiptedAt() ?? new \DateTime());

                        $em->persist($auditItem);
                    }
                }

                $em->flush();
                $em->getConnection()->commit();
            } catch (\Throwable $e) {
                try {
                    $em->getConnection()->rollBack();
                } catch (\Throwable $e) {
                    // Skip rollback exception
                }

                ListenerUtil::thrownError($e->getMessage());
            }
        }

        return $helper->handleView($helper->createView());
    }
}
