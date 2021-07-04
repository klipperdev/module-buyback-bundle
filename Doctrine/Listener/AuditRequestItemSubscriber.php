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
use Klipper\Module\BuybackBundle\Model\AuditRequestInterface;
use Klipper\Module\BuybackBundle\Model\AuditRequestItemInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditRequestItemSubscriber implements EventSubscriber
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

        if ($object instanceof AuditRequestItemInterface) {
            if (isset($changeSet['expectedQuantity']) || isset($changeSet['receivedQuantity'])) {
                $this->reCalculateAuditRequestQuantities($object->getAuditRequest());
            }
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $object) {
            if ($object instanceof AuditRequestItemInterface && null !== ($auditRequest = $object->getAuditRequest())) {
                $this->reCalculateAuditRequestQuantities($auditRequest);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();

        if (\count($this->updateQuantities) > 0) {
            $res = $em->createQueryBuilder()
                ->addSelect('ar.id as auditRequestId')
                ->addSelect('COUNT(ari.id) as total')
                ->addSelect('SUM(CASE WHEN ari.receivedQuantity is null THEN 1 ELSE 0 END) as emptyReceivedItem')
                ->addSelect('SUM(ari.expectedQuantity) as expectedQuantity')
                ->addSelect('SUM(ari.receivedQuantity) as receivedQuantity')
                ->from(AuditRequestItemInterface::class, 'ari')
                ->leftJoin('ari.auditRequest', 'ar')
                ->groupBy('ar.id')
                ->where('ar.id in (:ids)')
                ->setParameter('ids', $this->updateQuantities)
                ->getQuery()
                ->getResult()
            ;

            foreach ($res as $resItem) {
                // Do not the persist/flush in postFlush event
                $em->createQueryBuilder()
                    ->update(AuditRequestInterface::class, 'ar')
                    ->set('ar.expectedQuantity', ':expectedQuantity')
                    ->set('ar.receivedQuantity', ':receivedQuantity')
                    ->set('ar.completed', ':completed')
                    ->where('ar.id = :id')
                    ->setParameter('id', $resItem['auditRequestId'])
                    ->setParameter('expectedQuantity', (int) $resItem['expectedQuantity'])
                    ->setParameter('receivedQuantity', (int) $resItem['receivedQuantity'])
                    ->setParameter('completed', 0 === (int) $resItem['emptyReceivedItem'])
                    ->getQuery()
                    ->execute()
                ;
            }
        }

        $this->updateQuantities = [];
    }

    private function reCalculateAuditRequestQuantities(AuditRequestInterface $auditRequest): void
    {
        $this->updateQuantities[] = $auditRequest->getId();
        $this->updateQuantities = array_unique($this->updateQuantities);
    }
}
