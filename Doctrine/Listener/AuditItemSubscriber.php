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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\DoctrineChoice\Listener\Traits\DoctrineListenerChoiceTrait;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditItemSubscriber implements EventSubscriber
{
    use DoctrineListenerChoiceTrait;

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::onFlush,
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->preUpdate($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();

        if ($object instanceof AuditItemInterface) {
            if (null === $object->getConditionPrice()) {
                $object->setConditionPrice(0.0);
            }

            if (null === $object->getStatePrice()) {
                $object->setStatePrice(0.0);
            }

            if (null === $object->getReceiptedAt()) {
                if (null !== $object->getAuditRequest() && null !== $object->getAuditRequest()->getReceiptedAt()) {
                    $object->setReceiptedAt($object->getAuditRequest()->getReceiptedAt());
                } else {
                    $object->setReceiptedAt(new \DateTime());
                }
            }
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $this->updateStatus($em, $object);
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->updateStatus($em, $object);
        }
    }

    private function updateStatus(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof AuditItemInterface) {
            $uow = $em->getUnitOfWork();
            $currentStatus = $object->getStatus();
            $currentStatusValue = null !== $currentStatus ? $currentStatus->getValue() : null;
            $newStatusValue = $this->findStatusValue($object);

            if ($newStatusValue !== $currentStatusValue) {
                $object->setStatus($this->getChoice($em, 'audit_item_status', $newStatusValue));

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function findStatusValue(AuditItemInterface $object): ?string
    {
        $newStatusValue = 'confirmed';

        if (null !== $object->getAuditRequest()
            && null !== $object->getAuditRequest()->getSupplierOrderNumber()
            && null !== $object->getProduct()
        ) {
            $newStatusValue = 'qualified';

            if (null !== $object->getDevice()
                && null !== $object->getAuditCondition()
            ) {
                $newStatusValue = 'audited';

                if (null !== $object->getBuybackOffer()) {
                    $newStatusValue = 'valorised';
                }
            }
        }

        return $newStatusValue;
    }
}
