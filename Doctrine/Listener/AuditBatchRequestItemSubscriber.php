<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Doctrine\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Klipper\Module\BuybackBundle\Model\AuditBatchInterface;
use Klipper\Module\BuybackBundle\Model\AuditBatchRequestItemInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditBatchRequestItemSubscriber implements EventSubscriber
{
    /**
     * @var int[]|string[]
     */
    private array $updateQuantities = [];

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->preUpdate($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();
        $uow = $event->getEntityManager()->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($object);

        if ($object instanceof AuditBatchRequestItemInterface) {
            if (isset($changeSet['expectedQuantity'])
                || isset($changeSet['receivedQuantity'])
                || null === $object->getId()
            ) {
                $this->reCalculateAuditBatchRequestQuantities($object->getAuditBatch());
            }
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $object) {
            if ($object instanceof AuditBatchRequestItemInterface && null !== ($auditBatch = $object->getAuditBatch())) {
                $this->reCalculateAuditBatchRequestQuantities($auditBatch);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();

        if (\count($this->updateQuantities) > 0) {
            $res = $em->createQueryBuilder()
                ->addSelect('ab.id as auditBatchId')
                ->addSelect('COUNT(abri.id) as total')
                ->addSelect('SUM(CASE WHEN abri.receivedQuantity is null THEN 1 ELSE 0 END) as emptyReceivedItem')
                ->addSelect('SUM(abri.expectedQuantity) as expectedQuantity')
                ->addSelect('SUM(abri.receivedQuantity) as receivedQuantity')
                ->from(AuditBatchRequestItemInterface::class, 'abri')
                ->leftJoin('abri.auditBatch', 'ab')
                ->groupBy('ab.id')
                ->where('ab.id in (:ids)')
                ->setParameter('ids', $this->updateQuantities)
                ->getQuery()
                ->getResult()
            ;

            $existentQueryBatchIds = [];

            foreach ($res as $resItem) {
                $existentQueryBatchIds[] = $resItem['auditBatchId'];
            }

            foreach ($this->updateQuantities as $batchId) {
                if (!\in_array($batchId, $existentQueryBatchIds, true)) {
                    $res[] = [
                        'auditBatchId' => $batchId,
                        'total' => 0,
                        'emptyReceivedItem' => 0,
                        'expectedQuantity' => 0,
                        'receivedQuantity' => 0,
                    ];
                }
            }

            foreach ($res as $resItem) {
                // Do not the persist/flush in postFlush event
                $em->createQueryBuilder()
                    ->update(AuditBatchInterface::class, 'ab')
                    ->set('ab.numberOfRequestItems', ':numberOfRequestItems')
                    ->set('ab.expectedQuantity', ':expectedQuantity')
                    ->set('ab.receivedQuantity', ':receivedQuantity')
                    ->set('ab.completed', ':completed')
                    ->where('ab.id = :id')
                    ->setParameter('id', $resItem['auditBatchId'])
                    ->setParameter('numberOfRequestItems', $resItem['total'])
                    ->setParameter('expectedQuantity', (int) $resItem['expectedQuantity'])
                    ->setParameter('receivedQuantity', (int) $resItem['receivedQuantity'])
                    ->setParameter('completed', 0 === (int) $resItem['emptyReceivedItem'] && (int) $resItem['expectedQuantity'] > 0)
                    ->getQuery()
                    ->execute()
                ;
            }
        }

        $this->updateQuantities = [];
    }

    private function reCalculateAuditBatchRequestQuantities(AuditBatchInterface $auditBatch): void
    {
        $this->updateQuantities[] = $auditBatch->getId();
        $this->updateQuantities = array_unique($this->updateQuantities);
    }
}
